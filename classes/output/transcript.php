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
 * Turns stored transcript data into template context.
 *
 * @package    local_transcript
 * @copyright  2026 DevLMS <contact@devlms.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_transcript\output;

/**
 * Template context for local_transcript/transcript.
 *
 * @package    local_transcript
 * @copyright  2026 DevLMS <contact@devlms.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class transcript {
    /**
     * Context for the template, escaped by Mustache.
     *
     * @param array $data From collector::collect or a stored payload.
     * @param string $code Empty for a preview.
     * @param int $issued Issue time, 0 for a preview.
     * @return array
     */
    public static function context(array $data, string $code = '', int $issued = 0): array {
        $fmt = get_string('strftimedate', 'langconfig');
        $courses = array_map(fn($c) => [
            'shortname' => $c['shortname'],
            'fullname' => $c['fullname'],
            'grade' => $c['grade'],
            'completed' => $c['completed'] ? userdate($c['completed'], $fmt) : '',
        ], $data['courses'] ?? []);
        return [
            'institution' => $data['institution'] ?? '',
            'fullname' => $data['student']['fullname'] ?? '',
            'idnumber' => $data['student']['idnumber'] ?? '',
            'hasidnumber' => !empty($data['student']['idnumber']),
            'code' => $code,
            'issued' => $issued ? userdate($issued, $fmt) : '',
            'courses' => $courses,
            'hascourses' => !empty($courses),
            'showgrades' => (bool) array_filter(array_column($courses, 'grade'), fn($g) => $g !== ''),
        ];
    }
}
