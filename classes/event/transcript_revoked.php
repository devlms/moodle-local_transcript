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

namespace local_transcript\event;

/**
 * A transcript was revoked.
 *
 * @package    local_transcript
 * @copyright  2026 DevLMS <contact@devlms.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class transcript_revoked extends \core\event\base {
    /**
     * Init.
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'local_transcript';
    }

    /**
     * Name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_revoked', 'local_transcript');
    }

    /**
     * Description.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' revoked transcript '$this->objectid' for the user with id '$this->relateduserid'.";
    }

    /**
     * Link to the student's transcripts.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/local/transcript/index.php', ['userid' => $this->relateduserid]);
    }

    /**
     * Object id mapping for restore: transcripts are not backed up.
     *
     * @return array
     */
    public static function get_objectid_mapping() {
        return ['db' => 'local_transcript', 'restore' => \core\event\base::NOT_MAPPED];
    }
}
