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

namespace local_transcript\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider. Transcripts live in the student's user context.
 *
 * Deleting a student deletes their transcripts, so their codes stop verifying. Deleting the staff
 * member who issued or revoked one only removes their id: the signature does not cover it, so the
 * transcript still verifies.
 *
 * @package    local_transcript
 * @copyright  2026 DevLMS <contact@devlms.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    /**
     * Describe the stored data.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('local_transcript', [
            'userid' => 'privacy:metadata:transcript:userid',
            'code' => 'privacy:metadata:transcript:code',
            'payload' => 'privacy:metadata:transcript:payload',
            'issuedby' => 'privacy:metadata:transcript:issuedby',
            'timecreated' => 'privacy:metadata:transcript:timecreated',
            'revokedby' => 'privacy:metadata:transcript:revokedby',
            'revokedreason' => 'privacy:metadata:transcript:revokedreason',
            'timerevoked' => 'privacy:metadata:transcript:timerevoked',
        ], 'privacy:metadata:transcript');
        return $collection;
    }

    /**
     * User contexts of students the user has a transcript for, issued or revoked.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $list = new contextlist();
        $list->add_from_sql(
            "SELECT ctx.id
                               FROM {local_transcript} t
                               JOIN {context} ctx ON ctx.instanceid = t.userid AND ctx.contextlevel = :lvl
                              WHERE t.userid = :u1 OR t.issuedby = :u2 OR t.revokedby = :u3",
            ['lvl' => CONTEXT_USER, 'u1' => $userid, 'u2' => $userid, 'u3' => $userid]
        );
        return $list;
    }

    /**
     * The student and the staff who issued or revoked their transcripts.
     *
     * @param userlist $userlist
     */
    public static function get_users_in_context(userlist $userlist) {
        $ctx = $userlist->get_context();
        if (!$ctx instanceof \context_user) {
            return;
        }
        foreach (['userid', 'issuedby', 'revokedby'] as $field) {
            $userlist->add_from_sql(
                $field,
                "SELECT $field FROM {local_transcript} WHERE userid = :s AND $field > 0",
                ['s' => $ctx->instanceid]
            );
        }
    }

    /**
     * Export transcripts: in full for the student, the code and dates for staff.
     *
     * @param approved_contextlist $contextlist
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;
        $userid = (int) $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $ctx) {
            if (!$ctx instanceof \context_user) {
                continue;
            }
            $rows = $DB->get_records_select(
                'local_transcript',
                'userid = :s AND (userid = :u1 OR issuedby = :u2 OR revokedby = :u3)',
                ['s' => $ctx->instanceid, 'u1' => $userid, 'u2' => $userid, 'u3' => $userid],
                'id'
            );
            foreach ($rows as $r) {
                $data = (object) [
                    'code' => $r->code,
                    'issued' => transform::datetime($r->timecreated),
                    'issuedbyyou' => transform::yesno($r->issuedby == $userid),
                    'revoked' => $r->timerevoked ? transform::datetime($r->timerevoked) : '',
                    'revokedreason' => (string) $r->revokedreason,
                ];
                if ($r->userid == $userid) {
                    $payload = json_decode($r->payload);
                    $data->student = $payload->student ?? null;
                    $data->courses = $payload->courses ?? [];
                }
                writer::with_context($ctx)->export_data([get_string('pluginname', 'local_transcript'), $r->code], $data);
            }
        }
    }

    /**
     * Delete everything in a student's user context.
     *
     * @param \context $context
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;
        if ($context instanceof \context_user) {
            $DB->delete_records('local_transcript', ['userid' => $context->instanceid]);
        }
    }

    /**
     * Delete one user's data in the given contexts.
     *
     * @param approved_contextlist $contextlist
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        $userid = (int) $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $ctx) {
            self::delete_users($ctx, [$userid]);
        }
    }

    /**
     * Delete several users' data in one context.
     *
     * @param approved_userlist $userlist
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        self::delete_users($userlist->get_context(), $userlist->get_userids());
    }

    /**
     * Students lose their transcripts; staff lose only their id on them.
     *
     * @param \context $ctx
     * @param int[] $userids
     */
    private static function delete_users(\context $ctx, array $userids): void {
        global $DB;
        if (!$ctx instanceof \context_user || !$userids) {
            return;
        }
        if (in_array((int) $ctx->instanceid, array_map('intval', $userids), true)) {
            $DB->delete_records('local_transcript', ['userid' => $ctx->instanceid]);
            return;
        }
        [$in, $params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $params['s'] = $ctx->instanceid;
        $DB->execute("UPDATE {local_transcript} SET issuedby = 0 WHERE userid = :s AND issuedby $in", $params);
        [$in, $params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $params['s'] = $ctx->instanceid;
        $DB->execute("UPDATE {local_transcript} SET revokedby = 0 WHERE userid = :s AND revokedby $in", $params);
    }
}
