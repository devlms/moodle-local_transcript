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

/**
 * Navigation for local_transcript.
 *
 * @package    local_transcript
 * @copyright  2026 DevLMS <contact@devlms.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Add a Transcripts link to the profile page.
 *
 * @param \core_user\output\myprofile\tree $tree
 * @param stdClass $user
 * @param bool $iscurrentuser
 * @param stdClass|null $course
 * @return bool
 */
function local_transcript_myprofile_navigation(\core_user\output\myprofile\tree $tree, $user, $iscurrentuser, $course) {
    $sys = context_system::instance();
    $own = $iscurrentuser && has_capability('local/transcript:issueown', $sys);
    if (!$own && !has_capability('local/transcript:manage', $sys)) {
        return false;
    }
    $url = new moodle_url('/local/transcript/index.php', $iscurrentuser ? [] : ['userid' => $user->id]);
    $tree->add_node(new \core_user\output\myprofile\node(
        'miscellaneous',
        'local_transcript',
        get_string('mytranscripts', 'local_transcript'),
        null,
        $url
    ));
    return true;
}
