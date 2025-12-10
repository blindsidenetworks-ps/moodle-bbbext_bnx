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

/**
 * Test definitions for the bnx instance helper.
 *
 * @package   bbbext_bnx
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

namespace bbbext_bnx;

use bbbext_bnx\bigbluebuttonbn\mod_instance_helper;
use bbbext_bnx\local\services\bnx_settings_service;

/**
 * Tests for the BNX mod_instance_helper lifecycle hooks.
 *
 * @package   bbbext_bnx
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @covers    \bbbext_bnx\bigbluebuttonbn\mod_instance_helper
 */
final class mod_instance_helper_test extends \advanced_testcase {
    /**
     * Setup test case.
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * Test add_instance persists settings.
     *
     * @return void
     */
    public function test_add_instance_persists_settings(): void {
        $module = $this->create_bigbluebutton_activity();
        $bnxid = $this->ensure_bnx_record($module->id);

        $configplugin = 'bbbext_bnx_locksettings';
        set_config('cam_editable', 1, $configplugin);
        set_config('mic_editable', 1, $configplugin);

        $helper = new mod_instance_helper();
        $helper->add_instance((object) [
            'id' => $module->id,
            'enablecam' => 1,
            'enablemic' => 0,
        ]);

        $service = bnx_settings_service::get_service();
        $settings = $service->get_settings($bnxid);

        $this->assertSame('1', $settings['enablecam']);
        $this->assertSame('0', $settings['enablemic']);
    }

    /**
     * Test add_instance ignores unconfigured settings.
     *
     * @return void
     */
    public function test_update_instance_overwrites_settings(): void {
        $module = $this->create_bigbluebutton_activity();
        $helper = new mod_instance_helper();

        $bnxid = $this->ensure_bnx_record($module->id);
        $service = bnx_settings_service::get_service();
        $service->set_settings($bnxid, ['enablecam' => 0]);

        $helper->update_instance((object) [
            'id' => $module->id,
            'enablecam' => 1,
        ]);

        $this->assertSame('1', $service->get_setting($bnxid, 'enablecam'));
    }

    /**
     * Test delete_instance removes settings only.
     *
     * @return void
     */
    public function test_delete_instance_removes_settings_only(): void {
        global $DB;

        $module = $this->create_bigbluebutton_activity();
        $helper = new mod_instance_helper();
        $bnxid = $this->ensure_bnx_record($module->id);

        $DB->insert_record('bbbext_bnx_settings', (object) [
            'bnxid' => $bnxid,
            'name' => 'feature_flag',
            'value' => '1',
            'timecreated' => time(),
            'timemodified' => time(),
        ]);

        $helper->delete_instance($module->id);

        $this->assertTrue($DB->record_exists('bbbext_bnx', ['bigbluebuttonbnid' => $module->id]));
        $this->assertFalse($DB->record_exists('bbbext_bnx_settings', ['bnxid' => $bnxid]));
    }

    /**
     * Test get_join_tables method.
     *
     * @return void
     */
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

    /**
     * Ensure a BNX record exists for the given module id, returning its id.
     *
     * @param int $moduleid module identifier
     * @return int
     */
    private function ensure_bnx_record(int $moduleid): int {
        global $DB;

        $record = $DB->get_record('bbbext_bnx', ['bigbluebuttonbnid' => $moduleid]);
        if ($record) {
            return (int)$record->id;
        }

        $now = time();
        return (int)$DB->insert_record('bbbext_bnx', (object) [
            'bigbluebuttonbnid' => $moduleid,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
    }
}
