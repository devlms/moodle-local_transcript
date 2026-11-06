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
 * Tests for the transcript PDF.
 *
 * @package    local_transcript
 * @copyright  2026 DevLMS <contact@devlms.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_transcript\local\pdf
 */
final class pdf_test extends \advanced_testcase {
    public function test_the_pdf_is_built_from_the_stored_copy(): void {
        $this->resetAfterTest();
        $u = $this->getDataGenerator()->create_user(['firstname' => 'Kai', 'lastname' => 'Brandt']);
        $t = registry::issue($u->id);

        $out = pdf::build($t);

        $this->assertStringStartsWith('%PDF-', $out);
        $this->assertGreaterThan(2000, strlen($out));
    }
}
