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

namespace bbbext_bnx\local;

/**
 * Manages BNX sidecar enable/disable state based on the BNX plugin status.
 *
 * @package    bbbext_bnx
 * @copyright  2026 onwards, Blindside Networks Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */
class sidecar_state_manager {
    /** @var string config key storing sidecars that were enabled before BNX was disabled */
    public const SNAPSHOT_CONFIG_KEY = 'cascade_enabled_sidecars';

    /**
     * Apply sidecar state according to BNX plugin state.
     *
     * @param bool $bnxdisabled
     * @return void
     */
    public static function apply_for_bnx_state(bool $bnxdisabled): void {
        if ($bnxdisabled) {
            self::disable_dependent_sidecars();
            return;
        }

        self::restore_sidecars_from_snapshot();
    }

    /**
     * Disable dependent sidecars and remember which ones were previously enabled.
     *
     * @return void
     */
    private static function disable_dependent_sidecars(): void {
        $sidecars = self::get_bnx_sidecars();
        $previouslyenabled = [];

        foreach ($sidecars as $pluginname) {
            $component = 'bbbext_' . $pluginname;
            $oldvalue = get_config($component, 'disabled');
            if (!empty($oldvalue)) {
                continue;
            }

            $previouslyenabled[] = $pluginname;
            set_config('disabled', 1, $component);
            add_to_config_log('disabled', $oldvalue, 1, $component);
        }

        if (empty($previouslyenabled)) {
            unset_config(self::SNAPSHOT_CONFIG_KEY, 'bbbext_bnx');
        } else {
            set_config(self::SNAPSHOT_CONFIG_KEY, json_encode($previouslyenabled), 'bbbext_bnx');
        }

        \core_plugin_manager::reset_caches();
    }

    /**
     * Restore sidecars that were previously enabled before BNX disable.
     *
     * @return void
     */
    private static function restore_sidecars_from_snapshot(): void {
        $encoded = get_config('bbbext_bnx', self::SNAPSHOT_CONFIG_KEY);
        if (empty($encoded)) {
            return;
        }

        $sidecars = json_decode($encoded, true);
        if (!is_array($sidecars)) {
            unset_config(self::SNAPSHOT_CONFIG_KEY, 'bbbext_bnx');
            return;
        }

        $haschanged = false;
        foreach ($sidecars as $pluginname) {
            $component = 'bbbext_' . $pluginname;
            $oldvalue = get_config($component, 'disabled');
            if (empty($oldvalue)) {
                continue;
            }

            unset_config('disabled', $component);
            add_to_config_log('disabled', $oldvalue, 0, $component);
            $haschanged = true;
        }

        unset_config(self::SNAPSHOT_CONFIG_KEY, 'bbbext_bnx');

        if ($haschanged) {
            \core_plugin_manager::reset_caches();
        }
    }

    /**
     * Get all bbbext sidecars that directly or transitively depend on bbbext_bnx.
     *
     * @return array
     */
    private static function get_bnx_sidecars(): array {
        $plugininfos = \core_plugin_manager::instance()->get_plugins_of_type('bbbext');
        if (empty($plugininfos)) {
            return [];
        }

        $dependentsbyrequired = [];
        foreach ($plugininfos as $pluginname => $plugininfo) {
            $dependencies = $plugininfo->get_other_required_plugins();
            foreach (array_keys($dependencies) as $component) {
                if (!str_starts_with($component, 'bbbext_')) {
                    continue;
                }
                $requiredpluginname = substr($component, strlen('bbbext_'));
                $dependentsbyrequired[$requiredpluginname][] = $pluginname;
            }
        }

        $sidecars = [];
        $queue = ['bnx'];
        while (!empty($queue)) {
            $requiredpluginname = array_shift($queue);
            foreach ($dependentsbyrequired[$requiredpluginname] ?? [] as $dependentpluginname) {
                if (isset($sidecars[$dependentpluginname])) {
                    continue;
                }
                if ($dependentpluginname === 'bnx') {
                    continue;
                }

                $sidecars[$dependentpluginname] = $dependentpluginname;
                $queue[] = $dependentpluginname;
            }
        }

        return array_values($sidecars);
    }
}
