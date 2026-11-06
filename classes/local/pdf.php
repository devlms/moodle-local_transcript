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
 * Builds the PDF of an issued transcript, from the stored copy, never from live grades.
 *
 * @package    local_transcript
 * @copyright  2026 DevLMS <contact@devlms.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pdf {
    /**
     * The PDF as a string.
     *
     * @param \stdClass $record A local_transcript row.
     * @return string
     */
    public static function build(\stdClass $record): string {
        global $CFG;
        require_once($CFG->libdir . '/pdflib.php');
        $data = json_decode($record->payload, true);
        $fmt = get_string('strftimedate', 'langconfig');
        $url = registry::verify_url($record->code)->out(false);

        $doc = new \pdf('P', 'mm', 'A4');
        $doc->SetTitle(get_string('transcriptfor', 'local_transcript', $data['student']['fullname']));
        $doc->SetCreator('local_transcript');
        $doc->setPrintHeader(false);
        $doc->setPrintFooter(false);
        $doc->SetMargins(18, 18, 18);
        $doc->AddPage();

        $doc->SetFont('helvetica', 'B', 16);
        $doc->Cell(0, 9, $data['institution'], 0, 1);
        $doc->SetFont('helvetica', '', 11);
        $doc->Cell(0, 6, get_string('transcriptfor', 'local_transcript', $data['student']['fullname']), 0, 1);
        if ($data['student']['idnumber'] !== '') {
            $doc->Cell(0, 6, get_string('studentid', 'local_transcript') . ': ' . $data['student']['idnumber'], 0, 1);
        }
        $doc->Cell(0, 6, get_string('issuedon', 'local_transcript') . ': ' . userdate($record->timecreated, $fmt), 0, 1);
        $doc->Ln(4);

        $grades = (bool) array_filter(array_column($data['courses'], 'grade'), fn($g) => $g !== '');
        $rows = '';
        foreach ($data['courses'] as $c) {
            $rows .= '<tr><td width="' . ($grades ? '60%' : '75%') . '"><b>' . s($c['shortname']) . '</b> '
                . s($c['fullname']) . '</td>'
                . ($grades ? '<td width="15%">' . s($c['grade']) . '</td>' : '')
                . '<td width="25%">' . ($c['completed'] ? userdate($c['completed'], $fmt) : '') . '</td></tr>';
        }
        if ($rows === '') {
            $rows = '<tr><td>' . s(get_string('nocourses', 'local_transcript')) . '</td></tr>';
        }
        $head = '<tr style="background-color:#eeeeee;"><th width="' . ($grades ? '60%' : '75%') . '"><b>'
            . s(get_string('course', 'local_transcript')) . '</b></th>'
            . ($grades ? '<th width="15%"><b>' . s(get_string('grade', 'local_transcript')) . '</b></th>' : '')
            . '<th width="25%"><b>' . s(get_string('completed', 'local_transcript')) . '</b></th></tr>';
        $doc->SetFont('helvetica', '', 10);
        $doc->writeHTML('<table cellpadding="4" border="0.2">' . $head . $rows . '</table>', true, false, true);

        $doc->Ln(8);
        $y = $doc->GetY();
        $doc->write2DBarcode($url, 'QRCODE,M', 18, $y, 32, 32);
        $doc->SetXY(56, $y + 4);
        $doc->SetFont('helvetica', 'B', 12);
        $doc->Cell(0, 7, get_string('code', 'local_transcript') . ': ' . $record->code, 0, 2);
        $doc->SetFont('helvetica', '', 9);
        $doc->MultiCell(0, 5, get_string('scan', 'local_transcript') . ' ' . $url, 0, 'L', false, 1, 56);
        return $doc->Output('', 'S');
    }

    /**
     * Send the PDF to the browser and stop.
     *
     * @param \stdClass $record
     */
    public static function send(\stdClass $record): void {
        send_file(self::build($record), 'transcript-' . $record->code . '.pdf', 0, 0, true, true, 'application/pdf');
    }
}
