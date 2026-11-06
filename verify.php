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
 * Public page: anyone can check a transcript code, no login needed.
 *
 * @package    local_transcript
 * @copyright  2026 DevLMS <contact@devlms.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_transcript\local\registry;
use local_transcript\output\transcript;

// Verifiers are employers and other schools: no account, no login, and no session cookie.
define('NO_MOODLE_COOKIES', true);
require_once(__DIR__ . '/../../config.php');

$code = optional_param('code', '', PARAM_ALPHANUMEXT);
$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/transcript/verify.php', $code !== '' ? ['code' => $code] : []));
$PAGE->set_pagelayout('standard');
$PAGE->set_heading(format_string($SITE->fullname));
$PAGE->set_title(get_string('verify', 'local_transcript'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('verify', 'local_transcript'));
echo html_writer::tag('p', get_string('verifyintro', 'local_transcript'));

echo html_writer::start_tag('form', ['method' => 'get', 'action' => $PAGE->url->out_omit_querystring(),
    'class' => 'form-inline mb-3 d-flex gap-2']);
$hidden = ['class' => 'sr-only visually-hidden'];
echo html_writer::label(get_string('code', 'local_transcript'), 'local_transcript_code', false, $hidden);
echo html_writer::empty_tag('input', ['type' => 'text', 'name' => 'code', 'id' => 'local_transcript_code', 'value' => $code,
    'class' => 'form-control', 'placeholder' => 'K7QM-4XRT-9HCP', 'autocomplete' => 'off', 'size' => 18]);
echo html_writer::empty_tag('input', ['type' => 'submit', 'class' => 'btn btn-primary',
    'value' => get_string('verifybutton', 'local_transcript')]);
echo html_writer::end_tag('form');

if ($code !== '') {
    $v = registry::verify($code);
    $title = get_string($v->status . 'title', 'local_transcript');
    switch ($v->status) {
        case registry::VALID:
            $msg = get_string('valid', 'local_transcript', ['institution' => s($v->data['institution']),
                'date' => userdate($v->record->timecreated, get_string('strftimedate', 'langconfig'))]);
            $type = 'success';
            break;
        case registry::REVOKED:
            $when = userdate($v->record->timerevoked, get_string('strftimedate', 'langconfig'));
            $msg = get_string('revoked', 'local_transcript', $when);
            $type = 'error';
            break;
        case registry::ALTERED:
            $msg = get_string('altered', 'local_transcript');
            $type = 'error';
            break;
        default:
            $msg = get_string('unknown', 'local_transcript');
            $type = 'warning';
    }
    echo $OUTPUT->notification(html_writer::tag('strong', $title) . ' ' . $msg, $type, false);
    if ($v->data) {
        echo $OUTPUT->render_from_template(
            'local_transcript/transcript',
            transcript::context($v->data, $v->record->code, (int) $v->record->timecreated)
        );
    }
}

echo $OUTPUT->footer();
