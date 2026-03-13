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

/**
 * Conformance tests for BNX subplugin state change handling.
 *
 * When a subplugin (e.g. bnx_preuploads) is disabled, its own event observers
 * are not active. By running observers in the always-enabled parent (bbbext_bnx),
 * we ensure that cascade config changes still fire correctly on enable.
 *
 * @package   bbbext_bnx
 * @copyright 2026 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */
final class subplugin_state_test extends \advanced_testcase {
    /**
     * Enabling bnx_preuploads via config change must enable pre-upload presentations.
     *
     * This test covers the case where bnx_preuploads was disabled (so its own
     * observers are inactive) and is then re-enabled.
     *
     * @covers \bbbext_bnx\observer::subplugin_config_log_created
     * @return void
     */
    public function test_enabling_bnx_preuploads_enables_preupload_setting(): void {
        $this->resetAfterTest(true);

        set_config('bigbluebuttonbn_preuploadpresentation_editable', 0);
        $this->assertSame(0, (int) get_config(null, 'bigbluebuttonbn_preuploadpresentation_editable'));

        $event = \core\event\config_log_created::create([
            'context' => \context_system::instance(),
            'other' => [
                'name'      => 'disabled',
                'plugin'    => 'bbbext_bnx_preuploads',
                'oldvalue'  => '1',
                'value'     => '0',
            ],
        ]);

        observer::subplugin_config_log_created($event);

        $this->assertSame(1, (int) get_config(null, 'bigbluebuttonbn_preuploadpresentation_editable'));
    }

    /**
     * Disabling bnx_preuploads must NOT touch the pre-upload setting.
     *
     * @covers \bbbext_bnx\observer::subplugin_config_log_created
     * @return void
     */
    public function test_disabling_bnx_preuploads_does_not_change_preupload_setting(): void {
        $this->resetAfterTest(true);

        set_config('bigbluebuttonbn_preuploadpresentation_editable', 1);

        $event = \core\event\config_log_created::create([
            'context' => \context_system::instance(),
            'other' => [
                'name'      => 'disabled',
                'plugin'    => 'bbbext_bnx_preuploads',
                'oldvalue'  => '0',
                'value'     => '1',
            ],
        ]);

        observer::subplugin_config_log_created($event);

        // Setting must be untouched when the plugin is being disabled.
        $this->assertSame(1, (int) get_config(null, 'bigbluebuttonbn_preuploadpresentation_editable'));
    }

    /**
     * Events for unrelated plugins must be ignored.
     *
     * @covers \bbbext_bnx\observer::subplugin_config_log_created
     * @return void
     */
    public function test_unrelated_plugin_enable_is_ignored(): void {
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

        // Pre-upload setting must remain unchanged.
        $this->assertSame(0, (int) get_config(null, 'bigbluebuttonbn_preuploadpresentation_editable'));
    }
}
