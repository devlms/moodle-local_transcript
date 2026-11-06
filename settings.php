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
 * Admin settings for local_transcript.
 *
 * @package    local_transcript
 * @copyright  2026 DevLMS <contact@devlms.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_transcript', get_string('pluginname', 'local_transcript'));
    $settings->add(new admin_setting_configtext(
        'local_transcript/institution',
        get_string('institution', 'local_transcript'),
        get_string('institution_desc', 'local_transcript'),
        '',
        PARAM_TEXT
    ));
    $settings->add(new admin_setting_configselect(
        'local_transcript/include',
        get_string('include', 'local_transcript'),
        get_string('include_desc', 'local_transcript'),
        'completed',
        [
            'completed' => get_string('include_completed', 'local_transcript'),
            'graded' => get_string('include_graded', 'local_transcript'),
        ]
    ));
    $settings->add(new admin_setting_configcheckbox(
        'local_transcript/showgrades',
        get_string('showgrades', 'local_transcript'),
        get_string('showgrades_desc', 'local_transcript'),
        1
    ));
    $ADMIN->add('localplugins', $settings);
}
$ADMIN->add('accounts', new admin_externalpage(
    'local_transcript_manage',
    get_string('manage', 'local_transcript'),
    new moodle_url('/local/transcript/manage.php'),
    'local/transcript:manage'
));
