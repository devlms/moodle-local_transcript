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
 * A student's transcripts: issue a new one, download or revoke issued ones.
 *
 * @package    local_transcript
 * @copyright  2026 DevLMS <contact@devlms.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_transcript\local\collector;
use local_transcript\local\registry;
use local_transcript\output\transcript;

require_once(__DIR__ . '/../../config.php');

require_login(null, false);
$sys = context_system::instance();
$userid = optional_param('userid', $USER->id, PARAM_INT);
$own = $userid == $USER->id;
$cap = $own && !has_capability('local/transcript:manage', $sys) ? 'local/transcript:issueown' : 'local/transcript:manage';
require_capability($cap, $sys);
$user = core_user::get_user($userid, '*', MUST_EXIST);
$manage = has_capability('local/transcript:manage', $sys);

$url = new moodle_url('/local/transcript/index.php', $own ? [] : ['userid' => $userid]);
$PAGE->set_context($sys);
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('transcriptfor', 'local_transcript', fullname($user)));
$PAGE->set_heading(get_string('transcriptfor', 'local_transcript', fullname($user)));

if (optional_param('issue', 0, PARAM_BOOL) && confirm_sesskey()) {
    $t = registry::issue($userid);
    redirect($url, get_string('issued', 'local_transcript', $t->code), null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();

$rows = $DB->get_records('local_transcript', ['userid' => $userid], 'timecreated DESC, id DESC');
if ($rows) {
    $table = new html_table();
    $table->head = [get_string('code', 'local_transcript'), get_string('issuedon', 'local_transcript'),
        get_string('status', 'local_transcript'), ''];
    foreach ($rows as $r) {
        $status = registry::verify($r->code)->status;
        $actions = [html_writer::link(
            new moodle_url('/local/transcript/download.php', ['id' => $r->id]),
            get_string('download', 'local_transcript')
        ),
            html_writer::link(registry::verify_url($r->code), get_string('checkonline', 'local_transcript'))];
        if ($manage && !$r->timerevoked) {
            $actions[] = html_writer::link(
                new moodle_url('/local/transcript/revoke.php', ['id' => $r->id]),
                get_string('revoke', 'local_transcript')
            );
        }
        $table->data[] = [html_writer::tag('code', s($r->code)), userdate($r->timecreated),
            get_string($status . 'title', 'local_transcript'), implode(' · ', $actions)];
    }
    echo html_writer::table($table);
} else {
    echo $OUTPUT->notification(get_string('notranscripts', 'local_transcript'), 'info', false);
}

echo $OUTPUT->heading(get_string('preview', 'local_transcript'), 3);
$data = collector::collect($userid);
echo $OUTPUT->render_from_template('local_transcript/transcript', transcript::context($data));
$confirm = get_string('issueconfirm', 'local_transcript', fullname($user));
$button = new single_button(
    new moodle_url($url, ['issue' => 1]),
    get_string('issue', 'local_transcript'),
    'post',
    single_button::BUTTON_PRIMARY
);
$button->add_confirm_action($confirm);
echo $OUTPUT->render($button);

echo $OUTPUT->footer();
