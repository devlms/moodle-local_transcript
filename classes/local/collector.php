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
 * Collects what goes on a student's transcript: completed (or graded) visible courses with grade and date.
 *
 * @package    local_transcript
 * @copyright  2026 DevLMS <contact@devlms.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class collector {
    /**
     * The transcript data for a student, as stored and signed.
     *
     * @param int $userid
     * @return array{institution: string, student: array, courses: array}
     */
    public static function collect(int $userid): array {
        global $CFG, $DB;
        require_once($CFG->libdir . '/gradelib.php');
        require_once($CFG->libdir . '/enrollib.php');

        $user = \core_user::get_user($userid, '*', MUST_EXIST);
        $include = get_config('local_transcript', 'include') ?: 'completed';
        $showgrades = get_config('local_transcript', 'showgrades');
        $showgrades = $showgrades === false || (bool) $showgrades;
        $done = $DB->get_records_menu('course_completions', ['userid' => $userid], '', 'course, timecompleted');

        $courses = [];
        foreach (enrol_get_all_users_courses($userid, false, 'id, shortname, fullname, visible') as $course) {
            if (!$course->visible) {
                continue;
            }
            $completed = empty($done[$course->id]) ? null : (int) $done[$course->id];
            $grade = self::grade($course->id, $userid);
            if ($completed === null && !($include === 'graded' && $grade !== null)) {
                continue;
            }
            $context = \context_course::instance($course->id);
            $courses[] = [
                'shortname' => format_string($course->shortname, true, ['context' => $context]),
                'fullname' => format_string($course->fullname, true, ['context' => $context]),
                'completed' => $completed,
                'grade' => $showgrades && $grade !== null ? $grade : '',
            ];
        }
        usort($courses, fn($a, $b) => [$a['completed'] ?? PHP_INT_MAX, $a['shortname']]
            <=> [$b['completed'] ?? PHP_INT_MAX, $b['shortname']]);

        $institution = trim((string) get_config('local_transcript', 'institution'));
        return [
            'institution' => $institution !== '' ? $institution : format_string(get_site()->fullname),
            'student' => ['fullname' => fullname($user), 'idnumber' => (string) $user->idnumber],
            'courses' => $courses,
        ];
    }

    /**
     * The student's final course grade, formatted the way the course's gradebook shows it, or null.
     *
     * @param int $courseid
     * @param int $userid
     * @return string|null
     */
    private static function grade(int $courseid, int $userid): ?string {
        grade_regrade_final_grades_if_required(get_course($courseid));
        $item = \grade_item::fetch_course_item($courseid);
        $grade = $item->get_grade($userid, false);
        if (!$grade || $grade->finalgrade === null) {
            return null;
        }
        return grade_format_gradevalue($grade->finalgrade, $item, true);
    }
}
