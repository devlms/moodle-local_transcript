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
 * Tests for the transcript collector: which courses go on a transcript, with what grade and date.
 *
 * @package    local_transcript
 * @copyright  2026 DevLMS <contact@devlms.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_transcript\local\collector
 */
final class collector_test extends \advanced_testcase {
    /**
     * A course with completion on, a student, a final grade, and optionally a completion date.
     *
     * @param \stdClass $user
     * @param string $shortname
     * @param float|null $grade Final course grade out of 100, or null for none.
     * @param int|null $completed Completion time, or null when not completed.
     * @return \stdClass The course.
     */
    private function course_for(\stdClass $user, string $shortname, ?float $grade, ?int $completed): \stdClass {
        global $DB, $CFG;
        require_once($CFG->libdir . '/gradelib.php');
        $course = $this->getDataGenerator()->create_course(['shortname' => $shortname, 'fullname' => "Course $shortname",
            'enablecompletion' => 1]);
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');
        if ($grade !== null) {
            $item = \grade_item::fetch_course_item($course->id);
            $item->update_final_grade($user->id, $grade, 'test');
        }
        if ($completed !== null) {
            $DB->insert_record('course_completions', ['userid' => $user->id, 'course' => $course->id,
                'timeenrolled' => $completed - 1000, 'timestarted' => $completed - 1000, 'timecompleted' => $completed]);
        }
        return $course;
    }

    public function test_completed_courses_are_listed_oldest_first_with_grade_and_date(): void {
        $this->resetAfterTest();
        $u = $this->getDataGenerator()->create_user(['firstname' => 'Ines', 'lastname' => 'Duarte', 'idnumber' => 'S-1001']);
        $this->course_for($u, 'BIO2', 72.5, 1767225600);
        $this->course_for($u, 'BIO1', 88, 1735689600);
        $this->course_for($u, 'BIO3', 60, null);

        $data = collector::collect($u->id);

        $this->assertSame('Ines Duarte', $data['student']['fullname']);
        $this->assertSame('S-1001', $data['student']['idnumber']);
        $this->assertSame(['BIO1', 'BIO2'], array_column($data['courses'], 'shortname'));
        $this->assertSame('Course BIO1', $data['courses'][0]['fullname']);
        $this->assertSame(1735689600, $data['courses'][0]['completed']);
        $this->assertSame('88.00', $data['courses'][0]['grade']);
        $this->assertSame('72.50', $data['courses'][1]['grade']);
    }

    public function test_graded_mode_also_lists_courses_with_a_grade_but_no_completion(): void {
        $this->resetAfterTest();
        set_config('include', 'graded', 'local_transcript');
        $u = $this->getDataGenerator()->create_user();
        $this->course_for($u, 'MATH1', 91, 1735689600);
        $this->course_for($u, 'MATH2', 64, null);
        $this->course_for($u, 'MATH3', null, null);

        $data = collector::collect($u->id);

        $this->assertSame(['MATH1', 'MATH2'], array_column($data['courses'], 'shortname'));
        $this->assertNull($data['courses'][1]['completed']);
    }

    public function test_grades_can_be_left_off(): void {
        $this->resetAfterTest();
        set_config('showgrades', 0, 'local_transcript');
        $u = $this->getDataGenerator()->create_user();
        $this->course_for($u, 'ART1', 77, 1735689600);

        $data = collector::collect($u->id);

        $this->assertSame('', $data['courses'][0]['grade']);
    }

    public function test_hidden_courses_and_other_students_stay_off(): void {
        $this->resetAfterTest();
        $u = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();
        $hidden = $this->course_for($u, 'HID1', 80, 1735689600);
        update_course((object) ['id' => $hidden->id, 'visible' => 0]);
        $this->course_for($other, 'OTH1', 80, 1735689600);

        $this->assertSame([], collector::collect($u->id)['courses']);
    }

    public function test_institution_name_defaults_to_the_site_name(): void {
        $this->resetAfterTest();
        $u = $this->getDataGenerator()->create_user();
        $this->assertSame(format_string(get_site()->fullname, true, ['escape' => false]), collector::collect($u->id)['institution']);
        set_config('institution', 'Northfield College', 'local_transcript');
        $this->assertSame('Northfield College', collector::collect($u->id)['institution']);
    }

    public function test_names_are_stored_as_plain_text_not_html(): void {
        $this->resetAfterTest();
        set_config('institution', 'Arts & Sciences College', 'local_transcript');
        $u = $this->getDataGenerator()->create_user(['firstname' => "D'Arcy", 'lastname' => 'Neil']);
        $this->course_for($u, 'A&S1', 70, 1735689600);

        $data = collector::collect($u->id);

        $this->assertSame('A&S1', $data['courses'][0]['shortname']);
        $this->assertSame('Course A&S1', $data['courses'][0]['fullname']);
        $this->assertSame('Arts & Sciences College', $data['institution']);
        $this->assertStringContainsString("D'Arcy", $data['student']['fullname']);
    }
}
