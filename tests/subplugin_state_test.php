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

namespace bbbext_bnx_teststub;

/**
 * Stub plugininfo_callbacks used by subplugin_state_test.
 *
 * Simulates a sidecar plugin that defines an on_enable() callback,
 * without requiring the real plugin to be installed.
 *
 * @package   bbbext_bnx
 * @copyright 2026 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class plugininfo_callbacks {
    /**
     * Stub on_enable callback that sets a traceable config value.
     *
     * @return void
     */
    public static function on_enable(): void {
        set_config('bbbext_bnx_teststub_on_enable_called', 1);
    }
}

// phpcs:ignore PSR1.Classes.ClassDeclaration.MultipleClasses
namespace bbbext_bnx;

/**
 * Conformance tests for BNX subplugin state change handling.
 *
 * The parent plugin (bbbext_bnx) uses a generic callback discovery pattern:
 * when any bbbext subplugin is enabled, it checks for a
 * `\<plugin>\plugininfo_callbacks::on_enable()` method and invokes it.
 * These tests verify that mechanism without referencing specific sidecars.
 *
 * @package   bbbext_bnx
 * @copyright 2026 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */
final class subplugin_state_test extends \advanced_testcase {
    /**
     * Enabling a subplugin that defines plugininfo_callbacks::on_enable() must invoke it.
     *
     * Uses a test stub as a concrete example; the observer logic is generic.
     *
     * @covers \bbbext_bnx\observer::subplugin_config_log_created
     * @return void
     */
    public function test_enabling_subplugin_invokes_on_enable_callback(): void {
        $this->resetAfterTest(true);

        unset_config('bbbext_bnx_teststub_on_enable_called');
        $this->assertFalse(get_config(null, 'bbbext_bnx_teststub_on_enable_called'));

        $event = \core\event\config_log_created::create([
            'context' => \context_system::instance(),
            'other' => [
                'name'      => 'disabled',
                'plugin'    => 'bbbext_bnx_teststub',
                'oldvalue'  => '1',
                'value'     => '0',
            ],
        ]);

        observer::subplugin_config_log_created($event);

        $this->assertSame(1, (int) get_config(null, 'bbbext_bnx_teststub_on_enable_called'));
    }

    /**
     * Disabling a subplugin must NOT invoke the on_enable callback.
     *
     * @covers \bbbext_bnx\observer::subplugin_config_log_created
     * @return void
     */
    public function test_disabling_subplugin_does_not_invoke_callback(): void {
        $this->resetAfterTest(true);

        unset_config('bbbext_bnx_teststub_on_enable_called');

        $event = \core\event\config_log_created::create([
            'context' => \context_system::instance(),
            'other' => [
                'name'      => 'disabled',
                'plugin'    => 'bbbext_bnx_teststub',
                'oldvalue'  => '0',
                'value'     => '1',
            ],
        ]);

        observer::subplugin_config_log_created($event);

        // Callback must NOT have been invoked when the plugin is being disabled.
        $this->assertFalse(get_config(null, 'bbbext_bnx_teststub_on_enable_called'));
    }

    /**
     * Events for plugins without plugininfo_callbacks must be silently ignored.
     *
     * @covers \bbbext_bnx\observer::subplugin_config_log_created
     * @return void
     */
    public function test_subplugin_without_callback_is_ignored(): void {
        $this->resetAfterTest(true);

        set_config('bigbluebuttonbn_preuploadpresentation_editable', 0);

        $event = \core\event\config_log_created::create([
            'context' => \context_system::instance(),
            'other' => [
                'name'      => 'disabled',
                'plugin'    => 'bbbext_some_other_plugin',
                'oldvalue'  => '1',
                'value'     => '0',
            ],
        ]);

        observer::subplugin_config_log_created($event);

        // Setting must remain unchanged — no callback class exists for that plugin.
        $this->assertSame(0, (int) get_config(null, 'bigbluebuttonbn_preuploadpresentation_editable'));
    }

    /**
     * Non-bbbext plugins must be ignored entirely.
     *
     * @covers \bbbext_bnx\observer::subplugin_config_log_created
     * @return void
     */
    public function test_non_bbbext_plugin_is_ignored(): void {
        $this->resetAfterTest(true);

        set_config('bigbluebuttonbn_preuploadpresentation_editable', 0);

        $event = \core\event\config_log_created::create([
            'context' => \context_system::instance(),
            'other' => [
                'name'      => 'disabled',
                'plugin'    => 'mod_assign',
                'oldvalue'  => '1',
                'value'     => '0',
            ],
        ]);

        observer::subplugin_config_log_created($event);

        $this->assertSame(0, (int) get_config(null, 'bigbluebuttonbn_preuploadpresentation_editable'));
    }
}
