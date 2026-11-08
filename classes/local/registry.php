<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace local_transcript\local;

/**
 * Issues, verifies and revokes transcripts.
 *
 * An issued transcript is a frozen JSON copy of the student's record, signed with HMAC-SHA256 using a
 * secret only this site knows. Verifying recomputes the signature, so a record edited in the database
 * shows as altered, and later grade changes never change a transcript that was already issued.
 *
 * @package    local_transcript
 * @copyright  2026 DevLMS <contact@devlms.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class registry {
    /** @var string The record exists and its signature matches. */
    public const VALID = 'valid';
    /** @var string The record exists but was revoked. */
    public const REVOKED = 'revoked';
    /** @var string The record exists but its contents no longer match the signature. */
    public const ALTERED = 'altered';
    /** @var string No transcript has this code. */
    public const UNKNOWN = 'unknown';

    /** @var string Code alphabet without 0, 1, I and O, which are easy to misread. */
    private const ALPHABET = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';

    /**
     * The site's signing secret.
     *
     * $CFG->local_transcript_secret in config.php wins, so the secret can live outside the database.
     * Otherwise it is created in the plugin config the first time it is needed. Once transcripts exist a
     * missing secret is an error, never a new secret: a new one would mark every transcript as altered.
     *
     * @return string
     */
    public static function secret(): string {
        global $CFG, $DB;
        if (!empty($CFG->local_transcript_secret) && strlen((string) $CFG->local_transcript_secret) >= 32) {
            return (string) $CFG->local_transcript_secret;
        }
        $secret = (string) get_config('local_transcript', 'secret');
        if (strlen($secret) < 32) {
            if ($DB->record_exists('local_transcript', [])) {
                throw new \moodle_exception('error:nosecret', 'local_transcript');
            }
            $secret = bin2hex(random_bytes(32));
            set_config('secret', $secret, 'local_transcript');
        }
        return $secret;
    }

    /**
     * Issue a transcript for a student, signed and stored.
     *
     * @param int $userid The student.
     * @return \stdClass The stored record.
     */
    public static function issue(int $userid): \stdClass {
        global $DB, $USER;
        $record = (object) [
            'userid' => $userid,
            'code' => self::new_code(),
            'payload' => json_encode(collector::collect($userid), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'issuedby' => (int) $USER->id,
            'timecreated' => time(),
        ];
        $record->signature = self::sign($record);
        $record->id = $DB->insert_record('local_transcript', $record);
        \local_transcript\event\transcript_issued::create([
            'objectid' => $record->id,
            'relateduserid' => $userid,
            'context' => \context_system::instance(),
        ])->trigger();
        return $record;
    }

    /**
     * Look a code up and check it.
     *
     * @param string $code As typed: case, spaces and dashes do not matter.
     * @return \stdClass {status, record|null, data|null}
     */
    public static function verify(string $code): \stdClass {
        global $DB;
        $code = self::normalise($code);
        $record = $code === null ? false : $DB->get_record('local_transcript', ['code' => $code]);
        if (!$record) {
            return (object) ['status' => self::UNKNOWN, 'record' => null, 'data' => null];
        }
        if (!hash_equals(self::sign($record), (string) $record->signature)) {
            return (object) ['status' => self::ALTERED, 'record' => $record, 'data' => null];
        }
        // A transcript of a deleted account no longer stands, whatever the record says.
        $deleted = (int) $DB->get_field('user', 'deleted', ['id' => $record->userid]) !== 0
            || !$DB->record_exists('user', ['id' => $record->userid]);
        $status = $record->timerevoked || $deleted ? self::REVOKED : self::VALID;
        return (object) ['status' => $status, 'record' => $record, 'data' => json_decode($record->payload, true)];
    }

    /**
     * Revoke a transcript. It still verifies, as revoked, so anyone holding a copy finds out.
     *
     * @param int $id
     * @param string $reason
     */
    public static function revoke(int $id, string $reason): void {
        global $DB, $USER;
        $record = $DB->get_record('local_transcript', ['id' => $id], '*', MUST_EXIST);
        if ($record->timerevoked) {
            return;
        }
        $DB->update_record('local_transcript', (object) ['id' => $id, 'revokedby' => (int) $USER->id,
            'revokedreason' => trim($reason), 'timerevoked' => time()]);
        \local_transcript\event\transcript_revoked::create([
            'objectid' => $id,
            'relateduserid' => $record->userid,
            'context' => \context_system::instance(),
            'other' => ['reason' => trim($reason)],
        ])->trigger();
    }

    /**
     * Revoke every transcript of a user whose account was deleted.
     *
     * @param int $userid
     */
    public static function revoke_all_for_user(int $userid): void {
        global $DB;
        $DB->set_field_select(
            'local_transcript',
            'timerevoked',
            time(),
            'userid = :userid AND (timerevoked IS NULL OR timerevoked = 0)',
            ['userid' => $userid]
        );
    }

    /**
     * The URL of the public verify page for a code.
     *
     * @param string $code
     * @return \moodle_url
     */
    public static function verify_url(string $code): \moodle_url {
        return new \moodle_url('/local/transcript/verify.php', ['code' => $code]);
    }

    /**
     * A code as stored (XXXX-XXXX-XXXX), or null when it cannot be one.
     *
     * @param string $code
     * @return string|null
     */
    public static function normalise(string $code): ?string {
        $plain = preg_replace('/[^0-9A-Z]/', '', strtoupper($code));
        if (strlen($plain) !== 12) {
            return null;
        }
        return implode('-', str_split($plain, 4));
    }

    /**
     * Signature over the fields that make a transcript what it is.
     *
     * @param \stdClass $record
     * @return string
     */
    private static function sign(\stdClass $record): string {
        $data = implode("\n", [$record->code, (int) $record->userid, (int) $record->timecreated, $record->payload]);
        return hash_hmac('sha256', $data, self::secret());
    }

    /**
     * A new, unused code.
     *
     * @return string
     */
    private static function new_code(): string {
        global $DB;
        do {
            $chars = '';
            for ($i = 0; $i < 12; $i++) {
                $chars .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
            }
            $code = implode('-', str_split($chars, 4));
        } while ($DB->record_exists('local_transcript', ['code' => $code]));
        return $code;
    }
}
