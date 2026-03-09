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

namespace bbbext_bnx\local\helpers;

use mod_bigbluebuttonbn\instance;
use stdClass;

/**
 * Helper for checking sidecar plugin availability.
 *
 * @package   bbbext_bnx
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */
class sidecar_helper {
    /**
     * Get the list of enabled bbbext plugins.
     *
     * @return array Associative array of enabled plugin names to paths.
     */
    private static function get_enabled_plugins(): array {
        return \core_plugin_manager::instance()->get_enabled_plugins('bbbext');
    }

    /**
     * Check if a sidecar plugin is installed, enabled, and optionally has a required class.
     *
     * @param string $sidecarname The name of the sidecar plugin (e.g., 'bnx_preuploads', 'bnx_insights').
     * @param string|null $requiredclass Optional fully qualified class name that must exist.
     * @return bool True if the sidecar is available for use.
     */
    public static function is_available(string $sidecarname, ?string $requiredclass = null): bool {
        $enabledplugins = self::get_enabled_plugins();
        if (!isset($enabledplugins[$sidecarname])) {
            return false;
        }
        // Optionally check if a specific class exists (plugin properly installed).
        if ($requiredclass !== null && !class_exists($requiredclass)) {
            return false;
        }
        return true;
    }

    /**
     * Apply room adjustments from first available sidecar plugin.
     *
     * @param instance $instance
     * @param stdClass $roomdata
     * @return stdClass
     */
    public static function apply_room_adjustments(instance $instance, stdClass $roomdata): stdClass {
        $requiredclass = "\\bbbext_{pluginname}\\local\\helpers\\meeting_helper";
        $sortedplugins = self::get_sorted_sidecar_plugins($requiredclass);

        // Override room data with first available sidecar plugin that implements class.
        if (!empty($sortedplugins)) {
            $pluginname = reset($sortedplugins);
            $helperclass = "\\bbbext_{$pluginname}\\local\\helpers\\meeting_helper";
            return $helperclass::adjust_meeting_data($instance, $roomdata);
        }

        return $roomdata;
    }

    /**
     * Get ordered sidecar plugin names based on extension sort order.
     *
     * @return array
     */
    public static function get_ordered_sidecar_plugins(): array {
        return array_values(self::get_sorted_sidecar_plugins());
    }

    /**
     * Get sorted sidecar plugins by sortorder, optionally filtered by class.
     *
     * @param string|null $requiredclass Class pattern with {pluginname} placeholder.
     * @return array
     */
    private static function get_sorted_sidecar_plugins(?string $requiredclass = null): array {
        $enabledplugins = self::get_enabled_plugins();
        $result = [];
        foreach (array_keys($enabledplugins) as $name) {
            // Only sort bnx sidecar plugins.
            if (!str_starts_with($name, 'bnx_')) {
                continue;
            }

            // If a required class is specified, check if it exists.
            if ($requiredclass !== null) {
                $classname = str_replace('{pluginname}', $name, $requiredclass);
                if (!class_exists($classname) || !method_exists($classname, 'adjust_meeting_data')) {
                    continue;
                }
            }

            $idx = get_config('bbbext_' . $name, 'sortorder');
            if (!$idx) {
                $idx = 0;
            }
            while (array_key_exists($idx, $result)) {
                $idx += 1;
            }
            $result[$idx] = $name;
        }
        ksort($result);
        return $result;
    }
}
