<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace bbbext_bnx;

use bbbext_bnx\bigbluebuttonbn\mod_instance_helper;

/**
 * Tests for the BNX mod_instance_helper lifecycle hooks.
 *
 * @package    bbbext_bnx
 * @covers     \bbbext_bnx\bigbluebuttonbn\mod_instance_helper
 */
final class mod_instance_helper_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    public function test_add_instance_creates_bnx_record(): void {
        global $DB;

        $module = $this->create_bigbluebutton_activity();
        $DB->delete_records('bbbext_bnx', ['bigbluebuttonbnid' => $module->id]);

        $helper = new mod_instance_helper();
        $helper->add_instance((object) ['id' => $module->id]);

        $this->assertTrue($DB->record_exists('bbbext_bnx', ['bigbluebuttonbnid' => $module->id]));
    }

    public function test_update_instance_refreshes_timestamp(): void {
        global $DB;

        $module = $this->create_bigbluebutton_activity();
        $helper = new mod_instance_helper();

        $helper->add_instance((object) ['id' => $module->id]);
        $record = $DB->get_record('bbbext_bnx', ['bigbluebuttonbnid' => $module->id], '*', MUST_EXIST);
        $DB->set_field('bbbext_bnx', 'timemodified', 1, ['id' => $record->id]);

        $helper->update_instance((object) ['id' => $module->id]);

        $updated = $DB->get_record('bbbext_bnx', ['id' => $record->id], 'timemodified', MUST_EXIST);
        $this->assertGreaterThan(1, (int)$updated->timemodified);
    }

    public function test_delete_instance_removes_bnx_records(): void {
        global $DB;

        $module = $this->create_bigbluebutton_activity();
        $helper = new mod_instance_helper();
        $helper->add_instance((object) ['id' => $module->id]);

        $bnxrecord = $DB->get_record('bbbext_bnx', ['bigbluebuttonbnid' => $module->id], '*', MUST_EXIST);
        $DB->insert_record('bbbext_bnx_settings', (object) [
            'bnxid' => $bnxrecord->id,
            'setting' => 'feature_flag',
            'value' => 1,
            'timemodified' => time(),
        ]);

        $helper->delete_instance($module->id);

        $this->assertFalse($DB->record_exists('bbbext_bnx', ['bigbluebuttonbnid' => $module->id]));
        $this->assertFalse($DB->record_exists('bbbext_bnx_settings', ['bnxid' => $bnxrecord->id]));
    }

    public function test_get_join_tables(): void {
        $helper = new mod_instance_helper();
        $this->assertSame(['bbbext_bnx'], $helper->get_join_tables());
    }

    /**
     * Helper to create a BigBlueButton activity for tests.
     *
     * @return \stdClass
     */
    private function create_bigbluebutton_activity(): \stdClass {
        $course = $this->getDataGenerator()->create_course();
        return $this->getDataGenerator()->create_module('bigbluebuttonbn', ['course' => $course->id]);
    }
}
