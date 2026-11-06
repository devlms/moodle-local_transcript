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
 * Every issued transcript, newest first, with search by code or name.
 *
 * @package    local_transcript
 * @copyright  2026 DevLMS <contact@devlms.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_transcript\local\registry;

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

admin_externalpage_setup('local_transcript_manage');
$q = trim(optional_param('q', '', PARAM_TEXT));
$page = optional_param('page', 0, PARAM_INT);
$perpage = 50;

$userfields = \core_user\fields::for_name()->get_sql('u', false, '', '', false)->selects;
$where = '1 = 1';
$params = [];
if ($q !== '') {
    $code = registry::normalise($q);
    $like = $DB->sql_like($DB->sql_fullname('u.firstname', 'u.lastname'), ':name', false);
    $where = $code ? 't.code = :code' : $like;
    $params = $code ? ['code' => $code] : ['name' => '%' . $DB->sql_like_escape($q) . '%'];
}
$from = "FROM {local_transcript} t JOIN {user} u ON u.id = t.userid WHERE $where";
$total = $DB->count_records_sql("SELECT COUNT(1) $from", $params);
$rows = $DB->get_records_sql(
    "SELECT t.*, $userfields $from ORDER BY t.timecreated DESC, t.id DESC",
    $params,
    $page * $perpage,
    $perpage
);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('manage', 'local_transcript'));
echo html_writer::start_tag('form', ['method' => 'get', 'class' => 'mb-3 d-flex gap-2']);
echo html_writer::empty_tag('input', ['type' => 'search', 'name' => 'q', 'value' => $q, 'class' => 'form-control w-auto',
    'aria-label' => get_string('search')]);
echo html_writer::empty_tag('input', ['type' => 'submit', 'class' => 'btn btn-secondary', 'value' => get_string('search')]);
echo html_writer::end_tag('form');

if (!$rows) {
    echo $OUTPUT->notification(get_string('notranscripts', 'local_transcript'), 'info', false);
} else {
    $table = new html_table();
    $table->head = [get_string('student', 'local_transcript'), get_string('code', 'local_transcript'),
        get_string('issuedon', 'local_transcript'), get_string('status', 'local_transcript')];
    foreach ($rows as $r) {
        $table->data[] = [
            html_writer::link(new moodle_url('/local/transcript/index.php', ['userid' => $r->userid]), fullname($r)),
            html_writer::link(registry::verify_url($r->code), html_writer::tag('code', s($r->code))),
            userdate($r->timecreated),
            get_string(registry::verify($r->code)->status . 'title', 'local_transcript'),
        ];
    }
    echo html_writer::table($table);
    echo $OUTPUT->paging_bar($total, $page, $perpage, new moodle_url($PAGE->url, ['q' => $q]));
}
echo $OUTPUT->footer();
