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
 */
#[\PHPUnit\Framework\Attributes\CoversClass(action_url_parameters::class)]
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
     * @param bool $editable Whether teachers can override the admin setting
     * @param bool $default Admin-configured default value
     * @param bool|null $instancesetting Instance-specific setting
     * @param array $expectedcreate Expected parameters for create action
     * @param array $expectedjoin Expected parameters for join action
     * @param array $joindata Join action input payload
     *
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('approval_before_join_provider')]
    public function test_approval_before_join_parameters(
        bool $editable,
        bool $default,
        ?bool $instancesetting,
        array $expectedcreate,
        array $expectedjoin,
        array $joindata,
    ): void {
        // Configure admin settings.
        set_config('approvalbeforejoin_editable', $editable, 'bbbext_bnx');
        set_config('approvalbeforejoin_default', $default, 'bbbext_bnx');

        // Create a BBB instance.
        $course = $this->getDataGenerator()->create_course();
        $bbb = $this->getDataGenerator()->create_module('bigbluebuttonbn', ['course' => $course->id]);
        $instanceid = $bbb->id;

        $bnxid = $this->ensure_bnx_record($instanceid);
        if ($editable) {
            bnx_settings_service::get_service()->set_settings($bnxid, [
                'approvalbeforejoin' => (int)$instancesetting,
            ]);
        }

        // Test create action.
        $resultcreate = action_url_parameters::get_parameters('create', $instanceid);
        $this->assertEquals(
            array_merge(self::default_lock_parameters(), $expectedcreate),
            $resultcreate,
            'Create action parameters mismatch'
        );

        // Test join action.
        $resultjoin = action_url_parameters::get_parameters('join', $instanceid, $joindata);
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
                'expectedjoin' => ['guest' => null],
                'joindata' => ['guest' => 'false', 'role' => 'VIEWER'],
            ],
            // Editable enabled, default disabled.
            'editable_enabled_default_disabled_instance_enabled' => [
                'editable' => true,
                'default' => false,
                'instancesetting' => true,
                'expectedcreate' => ['guestPolicy' => 'ASK_MODERATOR'],
                'expectedjoin' => ['guest' => null],
                'joindata' => ['guest' => 'false', 'role' => 'VIEWER'],
            ],
            // Admin default disabled, not editable.
            'default_disabled_not_editable' => [
                'editable' => false,
                'default' => false,
                'instancesetting' => null,
                'expectedcreate' => [],
                'expectedjoin' => [],
                'joindata' => ['guest' => 'true', 'role' => 'VIEWER'],
            ],
        ];
    }

    /**
     * Test lock settings parameters use per-instance overrides.
     *
     * @return void
     */
    public function test_lock_settings_parameters_use_instance_values(): void {
        set_config('approvalbeforejoin_default', 0, 'bbbext_bnx');
        set_config('approvalbeforejoin_editable', 0, 'bbbext_bnx');
        foreach (['cam', 'mic', 'privatechat', 'publicchat', 'notes', 'userlist', 'hideviewerscursor'] as $feature) {
            set_config($feature . '_editable', 1, 'bbbext_bnx');
            set_config($feature . '_default', 1, 'bbbext_bnx');
        }

        $course = $this->getDataGenerator()->create_course();
        $bbb = $this->getDataGenerator()->create_module('bigbluebuttonbn', ['course' => $course->id]);
        $instanceid = $bbb->id;

        $bnxid = $this->ensure_bnx_record($instanceid);
        $settings = [
            'enablecam' => 0,
            'enablemic' => 1,
            'enableprivatechat' => 0,
            'enablepublicchat' => 1,
            'enablenotes' => 0,
            'enableuserlist' => 1,
            'hideviewerscursor' => 0,
        ];
        bnx_settings_service::get_service()->set_settings($bnxid, $settings);

        $expected = [
            'lockSettingsDisableCam' => 'true',
            'lockSettingsDisableMic' => 'false',
            'lockSettingsDisablePrivateChat' => 'true',
            'lockSettingsDisablePublicChat' => 'false',
            'lockSettingsDisableNotes' => 'true',
            'lockSettingsHideUserList' => 'false',
            'lockSettingsHideViewersCursor' => 'true',
            'lockSettingsLockOnJoin' => 'true',
        ];

        $this->assertEquals($expected, action_url_parameters::get_parameters('create', $instanceid));
    }

    /**
     * Test approval-before-join does not force moderators to join as guests.
     *
     * @return void
     */
    public function test_join_parameters_do_not_force_guest_for_moderator(): void {
        set_config('approvalbeforejoin_editable', 0, 'bbbext_bnx');
        set_config('approvalbeforejoin_default', 1, 'bbbext_bnx');

        $course = $this->getDataGenerator()->create_course();
        $bbb = $this->getDataGenerator()->create_module('bigbluebuttonbn', ['course' => $course->id]);
        $instanceid = $bbb->id;

        $resultjoin = action_url_parameters::get_parameters('join', $instanceid, ['role' => 'MODERATOR', 'guest' => 'false']);
        $this->assertEquals(['guest' => null], $resultjoin);
    }

    /**
     * Test approval-before-join does not force authenticated viewers to join as guests.
     *
     * @return void
     */
    public function test_join_parameters_do_not_force_guest_for_authenticated_viewer(): void {
        set_config('approvalbeforejoin_editable', 0, 'bbbext_bnx');
        set_config('approvalbeforejoin_default', 1, 'bbbext_bnx');

        $course = $this->getDataGenerator()->create_course();
        $bbb = $this->getDataGenerator()->create_module('bigbluebuttonbn', ['course' => $course->id]);
        $instanceid = $bbb->id;

        $resultjoin = action_url_parameters::get_parameters('join', $instanceid, ['role' => 'VIEWER', 'guest' => 'false']);
        $this->assertEquals(['guest' => null], $resultjoin);
    }

    /**
     * Test approval-before-join preserves guest joins.
     *
     * @return void
     */
    public function test_join_parameters_preserve_guest_for_guest_user(): void {
        set_config('approvalbeforejoin_editable', 0, 'bbbext_bnx');
        set_config('approvalbeforejoin_default', 1, 'bbbext_bnx');

        $course = $this->getDataGenerator()->create_course();
        $bbb = $this->getDataGenerator()->create_module('bigbluebuttonbn', ['course' => $course->id]);
        $instanceid = $bbb->id;

        $resultjoin = action_url_parameters::get_parameters('join', $instanceid, ['role' => 'VIEWER', 'guest' => 'true']);
        $this->assertEquals(['guest' => 'true'], $resultjoin);
    }

    /**
     * Default lock parameters emitted when no per-instance overrides exist.
     *
     * @return array<string, string>
     */
    private static function default_lock_parameters(): array {
        return [
            'lockSettingsDisableCam' => 'false',
            'lockSettingsDisableMic' => 'false',
            'lockSettingsDisablePrivateChat' => 'false',
            'lockSettingsDisablePublicChat' => 'false',
            'lockSettingsDisableNotes' => 'false',
            'lockSettingsHideUserList' => 'false',
            'lockSettingsHideViewersCursor' => 'false',
            'lockSettingsLockOnJoin' => 'true',
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
