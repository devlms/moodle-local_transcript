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
 * Revoke an issued transcript, with a reason.
 *
 * @package    local_transcript
 * @copyright  2026 DevLMS <contact@devlms.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_transcript\form\revoke_form;
use local_transcript\local\registry;

require_once(__DIR__ . '/../../config.php');

require_login(null, false);
$sys = context_system::instance();
require_capability('local/transcript:manage', $sys);
$id = required_param('id', PARAM_INT);
$record = $DB->get_record('local_transcript', ['id' => $id], '*', MUST_EXIST);
$back = new moodle_url('/local/transcript/index.php', ['userid' => $record->userid]);

$PAGE->set_context($sys);
$PAGE->set_url(new moodle_url('/local/transcript/revoke.php', ['id' => $id]));
$PAGE->set_title(get_string('revoke', 'local_transcript'));
$PAGE->set_heading(get_string('revoke', 'local_transcript'));

$form = new revoke_form(null, ['id' => $id, 'code' => $record->code]);
if ($form->is_cancelled()) {
    redirect($back);
} else if ($data = $form->get_data()) {
    registry::revoke($id, $data->reason);
    redirect(
        $back,
        get_string('revokedtitle', 'local_transcript') . ': ' . $record->code,
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();
$form->display();
echo $OUTPUT->footer();
