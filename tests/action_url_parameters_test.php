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
 * Test definitions for action_url_parameters.
 *
 * @package   bbbext_bnx
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Shamiso Jaravaza (shamiso [dt] jaravaza [at] blindsidenetworks [dt] com)
 */

namespace bbbext_bnx;

use bbbext_bnx\local\bigbluebutton\action_url_parameters;
use bbbext_bnx\local\services\bnx_settings_service;

/**
 * Tests for the approval before join feature in action_url_parameters.
 *
 * @package   bbbext_bnx
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Shamiso Jaravaza (shamiso [dt] jaravaza [at] blindsidenetworks [dt] com)
 * @covers    \bbbext_bnx\local\bigbluebutton\action_url_parameters
 */
final class action_url_parameters_test extends \advanced_testcase {
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
     * Test approval before join parameters.
     *
     * @dataProvider approval_before_join_provider
     * @param bool $editable Whether teachers can override the admin setting
     * @param bool $default Admin-configured default value
     * @param bool|null $instancesetting Instance-specific setting
     * @param array $expectedcreate Expected parameters for create action
     * @param array $expectedjoin Expected parameters for join action
     *
     * @return void
     */
    public function test_approval_before_join_parameters(
        bool $editable,
        bool $default,
        ?bool $instancesetting,
        array $expectedcreate,
        array $expectedjoin,
    ): void {
        global $DB;

        // Configure admin settings.
        set_config('approvalbeforejoin_editable', $editable, 'bbbext_bnx');
        set_config('approvalbeforejoin_default', $default, 'bbbext_bnx');

        // Create a BBB instance.
        $course = $this->getDataGenerator()->create_course();
        $bbb = $this->getDataGenerator()->create_module('bigbluebuttonbn', ['course' => $course->id]);
        $instanceid = $bbb->id;

        $bnxid = $this->ensure_bnx_record($instanceid);
        if ($editable) {
            $DB->insert_record('bbbext_bnx_settings', [
                'bnxid' => $bnxid,
                'name' => 'approvalbeforejoin',
                'value' => (string)(int)$instancesetting,
                'timecreated' => time(),
                'timemodified' => time(),
            ]);
        }

        // Test create action.
        $resultcreate = action_url_parameters::get_parameters('create', $instanceid);
        $this->assertEquals($expectedcreate, $resultcreate, 'Create action parameters mismatch');

        // Test join action.
        $resultjoin = action_url_parameters::get_parameters('join', $instanceid);
        $this->assertEquals($expectedjoin, $resultjoin, 'Join action parameters mismatch');
    }

    /**
     * Data provider for test_approval_before_join_parameters.
     *
     * @return array
     */
    public static function approval_before_join_provider(): array {
        return [
            // Admin default enabled, not editable.
            'default_enabled_not_editable' => [
                'editable' => false,
                'default' => true,
                'instancesetting' => true,
                'expectedcreate' => ['guestPolicy' => 'ASK_MODERATOR'],
                'expectedjoin' => ['guest' => 'true'],
            ],
            // Editable enabled, default disabled.
            'editable_enabled_default_disabled_instance_enabled' => [
                'editable' => true,
                'default' => false,
                'instancesetting' => true,
                'expectedcreate' => ['guestPolicy' => 'ASK_MODERATOR'],
                'expectedjoin' => ['guest' => 'true'],
            ],
            // Admin default disabled, not editable.
            'default_disabled_not_editable' => [
                'editable' => false,
                'default' => false,
                'instancesetting' => null,
                'expectedcreate' => [],
                'expectedjoin' => [],
            ],
        ];
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
