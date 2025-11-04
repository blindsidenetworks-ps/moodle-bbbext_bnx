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
 * Test definitions for the bnx settings service.
 *
 * @package   bbbext_bnx
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace bbbext_bnx;

use bbbext_bnx\local\service\bnx_settings_service;

/**
 * Unit tests for the BNX settings service.
 *
 * @package   bbbext_bnx
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @covers    \bbbext_bnx\local\service\bnx_settings_service
 */
final class bnx_settings_service_test extends \advanced_testcase {
    /** @var bnx_settings_service */
    private $service;

    /** @var int */
    private $bnxid;

    /** @var int */
    private $moduleid;

    /**
     * Setup test case.
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);

        $this->service = new bnx_settings_service();
        $this->bnxid = $this->create_bnx_record();
        $this->service->delete_settings($this->bnxid);
    }

    /**
     * Test set and get settings.
     *
     * @return void
     */
    public function test_set_and_get_settings(): void {
        $this->service->set_settings($this->bnxid, [
            'feature_one' => true,
            'feature_two' => 0,
        ]);

        $settings = $this->service->get_settings($this->bnxid);

        $this->assertSame([
            'feature_one' => 1,
            'feature_two' => 0,
        ], $settings);

        // Update one value and ensure the change is persisted.
        $this->service->set_settings($this->bnxid, ['feature_one' => false]);
        $this->assertSame(0, $this->service->get_setting($this->bnxid, 'feature_one'));
    }

    /**
     * Test get setting returns null when missing.
     *
     * @return void
     */
    public function test_get_setting_returns_null_when_missing(): void {
        $this->assertNull($this->service->get_setting($this->bnxid, 'missing'));
    }

    /**
     * Test retrieving a setting using the module identifier.
     *
     * @return void
     */
    public function test_get_setting_for_module_returns_value(): void {
        $this->service->set_settings($this->bnxid, ['feature_flag' => 1]);

        $this->assertSame(1, $this->service->get_setting_for_module($this->moduleid, 'feature_flag'));
    }

    /**
     * Test delete setting.
     *
     * @return void
     */
    public function test_delete_setting(): void {
        $this->service->set_settings($this->bnxid, ['feature_flag' => 1]);
        $this->assertSame(1, $this->service->get_setting($this->bnxid, 'feature_flag'));

        $this->service->delete_setting($this->bnxid, 'feature_flag');
        $this->assertNull($this->service->get_setting($this->bnxid, 'feature_flag'));
    }

    /**
     * Test delete settings.
     *
     * @return void
     */
    public function test_delete_settings(): void {
        $this->service->set_settings($this->bnxid, [
            'first' => 1,
            'second' => 0,
        ]);
        $this->assertNotEmpty($this->service->get_settings($this->bnxid));

        $this->service->delete_settings($this->bnxid);
        $this->assertSame([], $this->service->get_settings($this->bnxid));
    }

    /**
     * Create a bnx base record linked to a generated BigBlueButton activity.
     *
     * @return int bnx record id
     */
    private function create_bnx_record(): int {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module('bigbluebuttonbn', ['course' => $course->id]);
        $this->moduleid = (int)$module->id;

        $record = $DB->get_record('bbbext_bnx', ['bigbluebuttonbnid' => $module->id]);
        if ($record) {
            return (int)$record->id;
        }

        $now = time();
        $bnxid = $DB->insert_record('bbbext_bnx', (object) [
            'bigbluebuttonbnid' => $module->id,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        return (int)$bnxid;
    }
}
