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

#[\PHPUnit\Framework\Attributes\CoversClass(\local_transcript\local\registry::class)]
/**
 * Tests for issuing, verifying and revoking transcripts.
 *
 * @package    local_transcript
 * @copyright  2026 DevLMS <contact@devlms.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_transcript\local\registry
 */
final class registry_test extends \advanced_testcase {
    /**
     * A student with one completed, graded course.
     *
     * @return \stdClass
     */
    private function student(): \stdClass {
        global $DB, $CFG;
        require_once($CFG->libdir . '/gradelib.php');
        $u = $this->getDataGenerator()->create_user(['firstname' => 'Omar', 'lastname' => 'Haddad']);
        $c = $this->getDataGenerator()->create_course(['shortname' => 'CHEM1', 'enablecompletion' => 1]);
        $this->getDataGenerator()->enrol_user($u->id, $c->id, 'student');
        \grade_item::fetch_course_item($c->id)->update_final_grade($u->id, 81, 'test');
        $DB->insert_record('course_completions', ['userid' => $u->id, 'course' => $c->id, 'timeenrolled' => 1,
            'timestarted' => 1, 'timecompleted' => 1735689600]);
        return $u;
    }

    public function test_issue_stores_a_snapshot_with_a_readable_code(): void {
        $this->resetAfterTest();
        $u = $this->student();
        $this->setUser($u);
        $sink = $this->redirectEvents();

        $t = registry::issue($u->id);

        $this->assertMatchesRegularExpression('/^[2-9A-HJ-NP-Z]{4}-[2-9A-HJ-NP-Z]{4}-[2-9A-HJ-NP-Z]{4}$/', $t->code);
        $this->assertEquals($u->id, $t->userid);
        $this->assertEquals($u->id, $t->issuedby);
        $this->assertSame('CHEM1', json_decode($t->payload, true)['courses'][0]['shortname']);
        $events = array_filter($sink->get_events(), fn($e) => $e instanceof \local_transcript\event\transcript_issued);
        $this->assertCount(1, $events);
    }

    public function test_a_fresh_transcript_verifies_and_later_grade_changes_do_not_alter_it(): void {
        global $DB;
        $this->resetAfterTest();
        $u = $this->student();
        $t = registry::issue($u->id);
        $course = $DB->get_record('course', ['shortname' => 'CHEM1']);
        \grade_item::fetch_course_item($course->id)->update_final_grade($u->id, 40, 'test');

        $v = registry::verify(strtolower(str_replace('-', ' ', $t->code)));

        $this->assertSame(registry::VALID, $v->status);
        $this->assertSame('81.00', $v->data['courses'][0]['grade']);
    }

    public function test_an_edited_record_shows_as_altered(): void {
        global $DB;
        $this->resetAfterTest();
        $u = $this->student();
        $t = registry::issue($u->id);
        $DB->set_field('local_transcript', 'payload', str_replace('81.00', '99.00', $t->payload), ['id' => $t->id]);

        $this->assertSame(registry::ALTERED, registry::verify($t->code)->status);
    }

    public function test_revoked_and_unknown_codes(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $u = $this->student();
        $t = registry::issue($u->id);
        $sink = $this->redirectEvents();

        registry::revoke($t->id, 'Issued with the wrong name');

        $v = registry::verify($t->code);
        $this->assertSame(registry::REVOKED, $v->status);
        $this->assertSame('Issued with the wrong name', $v->record->revokedreason);
        $revoked = fn($e) => $e instanceof \local_transcript\event\transcript_revoked;
        $this->assertCount(1, array_filter($sink->get_events(), $revoked));
        $this->assertSame(registry::UNKNOWN, registry::verify('AAAA-BBBB-CCCC')->status);
        $this->assertSame(registry::UNKNOWN, registry::verify('')->status);
    }

    public function test_a_transcript_signed_with_another_sites_secret_is_altered(): void {
        $this->resetAfterTest();
        $u = $this->student();
        $t = registry::issue($u->id);
        set_config('secret', str_repeat('x', 64), 'local_transcript');

        $this->assertSame(registry::ALTERED, registry::verify($t->code)->status);
    }
}
