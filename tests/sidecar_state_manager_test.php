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

use bbbext_bnx\local\sidecar_state_manager;

/**
 * Tests for BNX sidecar state manager behaviour.
 *
 * @package   bbbext_bnx
 * @copyright 2026 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */
final class sidecar_state_manager_test extends \advanced_testcase {
    /**
     * Test that disabling BNX effectively disables sidecars and re-enabling restores remembered enabled ones.
     *
     * @covers \bbbext_bnx\local\sidecar_state_manager::apply_for_bnx_state
     * @return void
     */
    public function test_sidecar_state_manager_disable_and_restore(): void {
        $this->resetAfterTest(true);
        $this->skip_if_missing_plugins(['bnx', 'bnx_datahub']);

        \core\plugininfo\mod::enable_plugin('bigbluebuttonbn', 1);
        unset_config('disabled', 'bbbext_bnx');
        unset_config('disabled', 'bbbext_bnx_datahub');

        sidecar_state_manager::apply_for_bnx_state(true);
        $this->assertEquals(1, (int)get_config('bbbext_bnx_datahub', 'disabled'));
        $snapshot = json_decode((string)get_config('bbbext_bnx', sidecar_state_manager::SNAPSHOT_CONFIG_KEY), true);
        $this->assertIsArray($snapshot);
        $this->assertContains('bnx_datahub', $snapshot);

        sidecar_state_manager::apply_for_bnx_state(false);
        $this->assertFalse(get_config('bbbext_bnx_datahub', 'disabled'));
        $this->assertFalse(get_config('bbbext_bnx', sidecar_state_manager::SNAPSHOT_CONFIG_KEY));
    }

    /**
     * Test that sidecars previously disabled remain disabled after BNX is re-enabled.
     *
     * @covers \bbbext_bnx\local\sidecar_state_manager::apply_for_bnx_state
     * @return void
     */
    public function test_sidecar_state_manager_preserves_previously_disabled_state(): void {
        $this->resetAfterTest(true);
        $this->skip_if_missing_plugins(['bnx', 'bnx_datahub']);

        \core\plugininfo\mod::enable_plugin('bigbluebuttonbn', 1);
        unset_config('disabled', 'bbbext_bnx');
        set_config('disabled', 1, 'bbbext_bnx_datahub');

        sidecar_state_manager::apply_for_bnx_state(true);
        $snapshot = json_decode((string)get_config('bbbext_bnx', sidecar_state_manager::SNAPSHOT_CONFIG_KEY), true);
        $this->assertIsArray($snapshot);
        $this->assertNotContains('bnx_datahub', $snapshot);

        sidecar_state_manager::apply_for_bnx_state(false);
        $this->assertEquals(1, (int)get_config('bbbext_bnx_datahub', 'disabled'));
    }

    /**
     * Skip the test when required plugins are not installed in this environment.
     *
     * @param array $plugins
     * @return void
     */
    private function skip_if_missing_plugins(array $plugins): void {
        $installed = \core_plugin_manager::instance()->get_plugins_of_type('bbbext');
        $missing = array_diff($plugins, array_keys($installed));
        if (!empty($missing)) {
            $this->markTestSkipped('Missing required bbbext plugins: ' . implode(', ', $missing));
        }
    }
}
