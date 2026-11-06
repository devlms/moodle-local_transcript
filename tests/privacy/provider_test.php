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

namespace local_transcript\privacy;

use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use local_transcript\local\registry;

/**
 * Privacy provider tests.
 *
 * @package    local_transcript
 * @copyright  2026 DevLMS <contact@devlms.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_transcript\privacy\provider
 */
final class provider_test extends \core_privacy\tests\provider_testcase {
    /**
     * A student with a transcript issued by a registrar, and the registrar.
     *
     * @return array [student, registrar, transcript]
     */
    private function setup_data(): array {
        $student = $this->getDataGenerator()->create_user();
        $registrar = $this->getDataGenerator()->create_user();
        $this->setUser($registrar);
        $t = registry::issue($student->id);
        registry::revoke($t->id, 'Wrong programme');
        return [$student, $registrar, $t];
    }

    public function test_contexts_and_users(): void {
        $this->resetAfterTest();
        [$student, $registrar] = $this->setup_data();
        $ctx = \context_user::instance($student->id);

        $this->assertEquals([$ctx->id], provider::get_contexts_for_userid($student->id)->get_contextids());
        $this->assertEquals([$ctx->id], provider::get_contexts_for_userid($registrar->id)->get_contextids());

        $list = new userlist($ctx, 'local_transcript');
        provider::get_users_in_context($list);
        $this->assertEqualsCanonicalizing([$student->id, $registrar->id], $list->get_userids());
    }

    public function test_export_for_the_student_and_the_registrar(): void {
        $this->resetAfterTest();
        [$student, $registrar, $t] = $this->setup_data();
        $ctx = \context_user::instance($student->id);

        provider::export_user_data(new approved_contextlist($student, 'local_transcript', [$ctx->id]));
        $data = writer::with_context($ctx)->get_data([get_string('pluginname', 'local_transcript'), $t->code]);
        $this->assertSame($t->code, $data->code);
        $this->assertSame('Wrong programme', $data->revokedreason);

        writer::reset();
        provider::export_user_data(new approved_contextlist($registrar, 'local_transcript', [$ctx->id]));
        $data = writer::with_context($ctx)->get_data([get_string('pluginname', 'local_transcript'), $t->code]);
        $this->assertSame($t->code, $data->code);
        $this->assertObjectNotHasProperty('courses', $data);
    }

    public function test_deleting_the_student_removes_transcripts_and_the_registrar_keeps_none(): void {
        global $DB;
        $this->resetAfterTest();
        [$student, $registrar, $t] = $this->setup_data();
        $ctx = \context_user::instance($student->id);

        provider::delete_data_for_user(new approved_contextlist($registrar, 'local_transcript', [$ctx->id]));
        $row = $DB->get_record('local_transcript', ['id' => $t->id]);
        $this->assertEquals(0, $row->issuedby);
        $this->assertEquals(0, $row->revokedby);
        $this->assertSame(registry::REVOKED, registry::verify($t->code)->status, 'still verifies after staff removal');

        provider::delete_data_for_users(new approved_userlist($ctx, 'local_transcript', [$student->id]));
        $this->assertFalse($DB->record_exists('local_transcript', ['userid' => $student->id]));
    }

    public function test_delete_all_in_context(): void {
        global $DB;
        $this->resetAfterTest();
        [$student] = $this->setup_data();
        provider::delete_data_for_all_users_in_context(\context_user::instance($student->id));
        $this->assertFalse($DB->record_exists('local_transcript', ['userid' => $student->id]));
    }
}
