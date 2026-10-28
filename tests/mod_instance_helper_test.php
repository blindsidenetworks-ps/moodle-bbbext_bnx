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
 */
#[\PHPUnit\Framework\Attributes\CoversClass(mod_instance_helper::class)]
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

        set_config('approvalbeforejoin_editable', 1, 'bbbext_bnx');

        $helper = new mod_instance_helper();
        $helper->add_instance((object) [
            'id' => $module->id,
            'approvalbeforejoin' => 1,
            'enablecam' => 0,
            'enablenote' => 1,
        ]);

        $service = bnx_settings_service::get_service();
        $settings = $service->get_settings($bnxid);

        $this->assertSame('1', $settings['approvalbeforejoin']);
        $this->assertSame('0', $settings['enablecam']);
        $this->assertSame('1', $settings['enablenotes']);
    }

    /**
     * Missing creation fields inherit defaults while explicit false values are retained.
     *
     * @return void
     */
    public function test_add_instance_applies_missing_defaults(): void {
        global $DB;

        $this->setAdminUser();
        \core\plugininfo\mod::enable_plugin('bigbluebuttonbn', 1);
        \mod_bigbluebuttonbn\plugininfo\bbbext::enable_plugin('bnx', 1);
        \core_plugin_manager::reset_caches();
        $module = $this->create_bigbluebutton_activity();
        set_config('approvalbeforejoin_editable', 1, 'bbbext_bnx');
        set_config('approvalbeforejoin_default', 1, 'bbbext_bnx');
        set_config('cam_default', 0, 'bbbext_bnx');
        set_config('mic_default', 1, 'bbbext_bnx');
        set_config('reminder_editable', 1, 'bbbext_bnx');
        set_config('reminder_default', 1, 'bbbext_bnx');
        $DB->set_field('bigbluebuttonbn', 'wait', 1, ['id' => $module->id]);

        $helper = new mod_instance_helper();
        $helper->add_instance((object) [
            'id' => $module->id,
            'openingtime' => time() + 7200,
            'enablemic' => 0,
        ]);
        $service = bnx_settings_service::get_service();
        $this->assertSame('1', $service->get_setting_for_module($module->id, 'approvalbeforejoin'));
        $this->assertSame('0', $service->get_setting_for_module($module->id, 'enablecam'));
        $this->assertSame('0', $service->get_setting_for_module($module->id, 'enablemic'));
        $this->assertSame('1', $service->get_setting_for_module($module->id, 'reminderenabled'));
        $this->assertSame(0, (int) $DB->get_field('bigbluebuttonbn', 'wait', ['id' => $module->id]));
        $this->assertTrue($DB->record_exists('bbbext_bnx_reminders', [
            'bigbluebuttonbnid' => $module->id,
            'timespan' => 'PT1H',
        ]));
    }

    /**
     * Programmatic creation applies reminder defaults without overwriting supplied schedules.
     *
     * @param int $editable Whether the reminder setting is editable.
     * @param int $default The site reminder default.
     * @param array $fields Explicit programmatic creation fields.
     * @param string $enabled Expected persisted reminder state.
     * @param array $intervals Expected reminder intervals.
     * @return void
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('creation_reminder_defaults_provider')]
    public function test_creation_reminder_defaults(
        int $editable,
        int $default,
        array $fields,
        string $enabled,
        array $intervals,
    ): void {
        global $DB;

        $this->setAdminUser();
        \core\plugininfo\mod::enable_plugin('bigbluebuttonbn', 1);
        \mod_bigbluebuttonbn\plugininfo\bbbext::enable_plugin('bnx', 1);
        \core_plugin_manager::reset_caches();
        $module = $this->create_bigbluebutton_activity();
        set_config('reminder_editable', $editable, 'bbbext_bnx');
        set_config('reminder_default', $default, 'bbbext_bnx');
        $data = (object) (['id' => $module->id, 'openingtime' => time() + 7200] + $fields);
        (new mod_instance_helper())->add_instance($data);

        $service = bnx_settings_service::get_service();
        $this->assertSame($enabled, $service->get_setting_for_module($module->id, 'reminderenabled'));
        $records = $DB->get_records('bbbext_bnx_reminders', ['bigbluebuttonbnid' => $module->id], 'timespan', 'id, timespan');
        $actual = array_values(array_map(static fn($record) => $record->timespan, $records));
        sort($intervals);
        $this->assertSame($intervals, $actual);
    }

    /**
     * Updates preserve hidden and omitted flags but accept unchecked and explicit values.
     *
     * @return void
     */
    public function test_update_preserves_hidden_reminder_flags(): void {
        global $DB;

        $this->setAdminUser();
        \core\plugininfo\mod::enable_plugin('bigbluebuttonbn', 1);
        \mod_bigbluebuttonbn\plugininfo\bbbext::enable_plugin('bnx', 1);
        \core_plugin_manager::reset_caches();
        set_config('reminder_editable', 0, 'bbbext_bnx');
        set_config('reminder_default', 1, 'bbbext_bnx');
        $module = $this->create_bigbluebutton_activity();
        $helper = new mod_instance_helper();
        $helper->add_instance((object) [
            'id' => $module->id,
            'openingtime' => time() + 7200,
            'bnx_remindertoguestsenabled' => 1,
        ]);
        $service = bnx_settings_service::get_service();
        $helper->update_instance((object) ['id' => $module->id, 'bnx_form_submitted' => 1]);
        $this->assertSame('1', $service->get_setting_for_module($module->id, 'reminderenabled'));
        $this->assertSame('1', $service->get_setting_for_module($module->id, 'remindertoguestsenabled'));
        $this->assertTrue($DB->record_exists('bbbext_bnx_reminders', [
            'bigbluebuttonbnid' => $module->id,
            'timespan' => 'PT1H',
        ]));

        $helper->update_instance((object) ['id' => $module->id, 'bnx_reminderenabled' => 0]);
        $this->assertSame('0', $service->get_setting_for_module($module->id, 'reminderenabled'));
        $this->assertSame('1', $service->get_setting_for_module($module->id, 'remindertoguestsenabled'));

        set_config('reminder_editable', 1, 'bbbext_bnx');
        $helper->update_instance((object) ['id' => $module->id, 'bnx_form_submitted' => 1]);
        $this->assertSame('0', $service->get_setting_for_module($module->id, 'reminderenabled'));
        $this->assertSame('0', $service->get_setting_for_module($module->id, 'remindertoguestsenabled'));
    }

    /**
     * An undated activity retains a default interval and resets it on rescheduling.
     *
     * @return void
     */
    public function test_undated_creation_and_hidden_reminder_rescheduling(): void {
        global $DB;

        $this->setAdminUser();
        \core\plugininfo\mod::enable_plugin('bigbluebuttonbn', 1);
        \mod_bigbluebuttonbn\plugininfo\bbbext::enable_plugin('bnx', 1);
        \core_plugin_manager::reset_caches();
        set_config('reminder_editable', 0, 'bbbext_bnx');
        set_config('reminder_default', 1, 'bbbext_bnx');
        $module = $this->create_bigbluebutton_activity();
        $helper = new mod_instance_helper();
        $helper->add_instance((object) ['id' => $module->id, 'openingtime' => 0]);
        $conditions = ['bigbluebuttonbnid' => $module->id, 'timespan' => 'PT1H'];
        $reminder = $DB->get_record('bbbext_bnx_reminders', $conditions, '*', MUST_EXIST);
        $DB->set_field('bbbext_bnx_reminders', 'lastsent', 123, ['id' => $reminder->id]);

        $helper->update_instance((object) [
            'id' => $module->id, 'bnx_form_submitted' => 1, 'bnx_openingtime' => 0, 'openingtime' => 0,
        ]);
        $this->assertSame(123, (int) $DB->get_field('bbbext_bnx_reminders', 'lastsent', ['id' => $reminder->id]));
        $helper->update_instance((object) [
            'id' => $module->id, 'bnx_form_submitted' => 1, 'bnx_openingtime' => 0, 'openingtime' => time() + 7200,
        ]);
        $this->assertSame(0, (int) $DB->get_field('bbbext_bnx_reminders', 'lastsent', ['id' => $reminder->id]));
        $this->assertSame(1, $DB->count_records('bbbext_bnx_reminders', $conditions));
    }

    /**
     * Reminder creation variants.
     *
     * @return array
     */
    public static function creation_reminder_defaults_provider(): array {
        return [
            'noneditable default enabled' => [0, 1, [], '1', ['PT1H']],
            'explicit enabled with missing schedule' => [1, 0, ['bnx_reminderenabled' => 1], '1', ['PT1H']],
            'supplied intervals without count' => [1, 1, ['bnx_timespan' => ['P1D', 'PT2H']], '1', ['P1D', 'PT2H']],
            'supplied scalar interval' => [1, 1, ['bnx_timespan' => 'P1D'], '1', ['P1D']],
            'explicit empty intervals' => [1, 1, ['bnx_timespan' => []], '1', []],
            'explicit zero count' => [1, 1, ['bnx_paramcount' => 0], '1', []],
            'explicit disabled' => [0, 1, ['bnx_reminderenabled' => 0], '0', ['PT1H']],
        ];
    }

    /**
     * A form submission must not turn omitted unchecked controls back on.
     *
     * @return void
     */
    public function test_add_instance_preserves_form_submission(): void {
        $this->setAdminUser();
        $module = $this->create_bigbluebutton_activity();
        $bnxid = $this->ensure_bnx_record($module->id);
        $service = bnx_settings_service::get_service();
        set_config('approvalbeforejoin_editable', 1, 'bbbext_bnx');
        set_config('approvalbeforejoin_default', 1, 'bbbext_bnx');
        set_config('cam_default', 1, 'bbbext_bnx');
        $service->set_settings($bnxid, ['approvalbeforejoin' => 0, 'enablecam' => 0]);

        (new mod_instance_helper())->add_instance((object) [
            'id' => $module->id,
            'bnx_form_submitted' => 1,
        ]);

        $this->assertSame('0', $service->get_setting($bnxid, 'approvalbeforejoin'));
        $this->assertSame('0', $service->get_setting($bnxid, 'enablecam'));
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
        $service->set_settings($bnxid, ['approvalbeforejoin' => 0]);

        $helper->update_instance((object) [
            'id' => $module->id,
            'approvalbeforejoin' => 1,
        ]);

        $this->assertSame('1', $service->get_setting($bnxid, 'approvalbeforejoin'));
    }

    /**
     * Test delete_instance.
     *
     * @return void
     */
    public function test_delete_instance(): void {
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

        $this->assertFalse($DB->record_exists('bbbext_bnx', ['bigbluebuttonbnid' => $module->id]));
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
