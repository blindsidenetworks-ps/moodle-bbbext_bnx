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
     * Check if a sidecar plugin is installed, enabled, and optionally has a required class.
     *
     * @param string $sidecarname The name of the sidecar plugin (e.g., 'bnx_preuploads', 'bnx_insights').
     * @param string|null $requiredclass Optional fully qualified class name that must exist.
     * @return bool True if the sidecar is available for use.
     */
    public static function is_available(string $sidecarname, ?string $requiredclass = null): bool {
        // Check if the plugin is enabled via plugin manager.
        $enabledplugins = \core_plugin_manager::instance()->get_enabled_plugins('bbbext');
        if (!isset($enabledplugins[$sidecarname])) {
            return false;
        }
        // Optionally check if a specific class exists (plugin properly installed).
        if ($requiredclass !== null && !class_exists($requiredclass)) {
            return false;
        }
        return true;
    }
}
