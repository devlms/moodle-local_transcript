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

namespace local_transcript\event;

use local_transcript\local\registry;

defined('MOODLE_INTERNAL') || die();

global $CFG;
// Coverage resolves CoversFunction before any test runs, so the functions must be loaded here.
require_once($CFG->dirroot . '/local/transcript/lib.php');

#[\PHPUnit\Framework\Attributes\CoversClass(\local_transcript\event\transcript_issued::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\local_transcript\event\transcript_revoked::class)]
#[\PHPUnit\Framework\Attributes\CoversFunction('local_transcript_myprofile_navigation')]
/**
 * Tests for the issue and revoke events, and the profile link.
 *
 * @package    local_transcript
 * @copyright  2026 DevLMS <contact@devlms.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_transcript\event\transcript_issued
 * @covers     \local_transcript\event\transcript_revoked
 * @covers     ::local_transcript_myprofile_navigation
 */
final class events_test extends \advanced_testcase {
    public function test_events_describe_who_did_what(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $student = $this->getDataGenerator()->create_user();
        $sink = $this->redirectEvents();

        $t = registry::issue($student->id);
        registry::revoke($t->id, 'Typo in the name');
        registry::revoke($t->id, 'Again');

        [$issued, $revoked] = $sink->get_events();
        $this->assertCount(2, $sink->get_events(), 'revoking twice logs once');
        $this->assertInstanceOf(transcript_issued::class, $issued);
        $this->assertInstanceOf(transcript_revoked::class, $revoked);
        $this->assertEquals($student->id, $issued->relateduserid);
        $this->assertStringContainsString("transcript '{$t->id}'", $issued->get_description());
        $this->assertStringContainsString('revoked', $revoked->get_description());
        $this->assertSame('Typo in the name', $revoked->other['reason']);
        $this->assertSame(get_string('event_issued', 'local_transcript'), transcript_issued::get_name());
        $this->assertSame(get_string('event_revoked', 'local_transcript'), transcript_revoked::get_name());
        $this->assertEquals(new \moodle_url('/local/transcript/index.php', ['userid' => $student->id]), $issued->get_url());
        $this->assertSame('local_transcript', transcript_issued::get_objectid_mapping()['db']);
    }

    /**
     * The profile link shows for the student themselves and for managers looking at anyone.
     */
    public function test_profile_link_for_the_student_and_for_managers_only(): void {
        global $CFG;
        require_once($CFG->dirroot . '/local/transcript/lib.php');
        $this->resetAfterTest();
        $student = $this->getDataGenerator()->create_user();
        $other = $this->getDataGenerator()->create_user();

        $this->setUser($student);
        $tree = new \core_user\output\myprofile\tree();
        $this->assertTrue(local_transcript_myprofile_navigation($tree, $student, true, null));
        $this->assertFalse(local_transcript_myprofile_navigation(new \core_user\output\myprofile\tree(), $other, false, null));

        $this->setAdminUser();
        $this->assertTrue(local_transcript_myprofile_navigation(new \core_user\output\myprofile\tree(), $student, false, null));
    }
}
