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
 * English strings for local_transcript.
 *
 * @package    local_transcript
 * @copyright  2026 DevLMS <contact@devlms.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['altered'] = 'This record has been changed since it was issued. Do not trust it.';
$string['alteredtitle'] = 'Altered record';
$string['checkonline'] = 'Check online';
$string['code'] = 'Verification code';
$string['code_help'] = 'The 12 character code printed on the transcript, for example K7QM-4XRT-9HCP.';
$string['completed'] = 'Completed';
$string['course'] = 'Course';
$string['download'] = 'Download PDF';
$string['error:nosecret'] = 'The signing secret is missing, but transcripts exist. Restore the secret (plugin setting or $CFG->local_transcript_secret) instead of creating a new one, or every transcript will show as altered.';
$string['error:notvalid'] = 'This transcript is revoked or no longer matches its signature, so it cannot be downloaded.';
$string['event_issued'] = 'Transcript issued';
$string['event_revoked'] = 'Transcript revoked';
$string['grade'] = 'Grade';
$string['include'] = 'Courses on a transcript';
$string['include_completed'] = 'Completed courses only';
$string['include_desc'] = 'Which of the student\'s visible courses appear on an issued transcript.';
$string['include_graded'] = 'Completed courses, and courses with a final grade';
$string['institution'] = 'Institution name';
$string['institution_desc'] = 'Printed at the top of every transcript. Leave empty to use the site name.';
$string['issue'] = 'Issue a transcript';
$string['issueconfirm'] = 'Issue a transcript for {$a}? It records the courses below as they are now. Grades that change later will not change it.';
$string['issued'] = 'Transcript issued. Its code is {$a}.';
$string['issuedby'] = 'Issued by';
$string['issuedon'] = 'Issued on';
$string['issuefor'] = 'Issue for {$a}';
$string['manage'] = 'Issued transcripts';
$string['mytranscripts'] = 'Transcripts';
$string['nocourses'] = 'No courses to show yet.';
$string['notranscripts'] = 'No transcripts issued yet.';
$string['pluginname'] = 'Verifiable transcripts';
$string['preview'] = 'What a new transcript would show';
$string['privacy:metadata:transcript'] = 'Transcripts issued for a student: a frozen copy of their courses, grades and completion dates.';
$string['privacy:metadata:transcript:code'] = 'The verification code.';
$string['privacy:metadata:transcript:issuedby'] = 'Who issued the transcript.';
$string['privacy:metadata:transcript:payload'] = 'The student\'s name, ID number, courses, grades and completion dates when the transcript was issued.';
$string['privacy:metadata:transcript:revokedby'] = 'Who revoked the transcript.';
$string['privacy:metadata:transcript:revokedreason'] = 'Why the transcript was revoked.';
$string['privacy:metadata:transcript:timecreated'] = 'When the transcript was issued.';
$string['privacy:metadata:transcript:timerevoked'] = 'When the transcript was revoked.';
$string['privacy:metadata:transcript:userid'] = 'The student the transcript is for.';
$string['revoke'] = 'Revoke';
$string['revokeconfirm'] = 'Revoke transcript {$a}? Anyone who checks this code will see it was revoked, with the date.';
$string['revoked'] = 'This transcript was revoked on {$a}.';
$string['revokedtitle'] = 'Revoked';
$string['revokereason'] = 'Reason (kept for staff, not shown on the verify page)';
$string['scan'] = 'Check this transcript at';
$string['showgrades'] = 'Show grades';
$string['showgrades_desc'] = 'Print each course\'s final grade, as the course gradebook shows it.';
$string['status'] = 'Status';
$string['student'] = 'Student';
$string['studentid'] = 'Student ID';
$string['transcript:issueown'] = 'Issue and download your own transcript';
$string['transcript:manage'] = 'Issue, see and revoke transcripts for any student';
$string['transcriptfor'] = 'Transcript for {$a}';
$string['unknown'] = 'No transcript has this code. Check that it was typed correctly.';
$string['unknowntitle'] = 'Not found';
$string['valid'] = 'This transcript was issued by {$a->institution} on {$a->date} and has not been changed since.';
$string['validtitle'] = 'Valid';
$string['verify'] = 'Verify a transcript';
$string['verifybutton'] = 'Check';
$string['verifyintro'] = 'Enter the code printed on a transcript to see what was issued and whether it is still valid.';
