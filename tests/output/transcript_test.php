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


namespace local_transcript\output;

/**
 * Tests for the transcript template context.
 *
 * @package    local_transcript
 * @copyright  2026 DevLMS <contact@devlms.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_transcript\output\transcript
 */
final class transcript_test extends \advanced_testcase {
    public function test_context_formats_dates_and_hides_empty_grade_column(): void {
        $this->resetAfterTest();
        $data = [
            'institution' => 'Northfield College',
            'student' => ['fullname' => 'Ines Moreau', 'idnumber' => ''],
            'courses' => [
                ['shortname' => 'BIO1', 'fullname' => 'Biology', 'completed' => 1735689600, 'grade' => ''],
                ['shortname' => 'BIO2', 'fullname' => 'Genetics', 'completed' => null, 'grade' => ''],
            ],
        ];

        $ctx = transcript::context($data, 'AAAA-BBBB-CCCC', 1735689600);

        $this->assertSame('Northfield College', $ctx['institution']);
        $this->assertFalse($ctx['hasidnumber']);
        $this->assertFalse($ctx['showgrades']);
        $this->assertTrue($ctx['hascourses']);
        $this->assertSame(userdate(1735689600, get_string('strftimedate', 'langconfig')), $ctx['courses'][0]['completed']);
        $this->assertSame('', $ctx['courses'][1]['completed']);
        $this->assertNotSame('', $ctx['issued']);
    }

    public function test_preview_has_no_code_or_date_and_shows_grades_when_present(): void {
        $data = ['institution' => 'X', 'student' => ['fullname' => 'A', 'idnumber' => 'S1'],
            'courses' => [['shortname' => 'C', 'fullname' => 'C', 'completed' => null, 'grade' => '70.00']]];

        $ctx = transcript::context($data);

        $this->assertSame('', $ctx['code']);
        $this->assertSame('', $ctx['issued']);
        $this->assertTrue($ctx['showgrades']);
        $this->assertTrue($ctx['hasidnumber']);
    }

    public function test_an_empty_record(): void {
        $ctx = transcript::context(['institution' => 'X', 'student' => ['fullname' => 'A', 'idnumber' => ''], 'courses' => []]);
        $this->assertFalse($ctx['hascourses']);
    }
}
