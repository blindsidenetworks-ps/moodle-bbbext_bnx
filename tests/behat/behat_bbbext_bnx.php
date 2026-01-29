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
 * Behat step definitions for bbbext_bnx.
 *
 * @package   bbbext_bnx
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

require_once(__DIR__ . '/../../../../../../lib/behat/behat_base.php');

/**
 * Behat steps for BigBlueButton BN Experience.
 *
 * @package   bbbext_bnx
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_bbbext_bnx extends behat_base {
    /**
     * Enable a BigBlueButton extension plugin.
     *
     * @Given /^the bbbext "(?P<pluginname>(?:[^"]|\\")*)" plugin is enabled$/
     * @param string $pluginname The plugin name (e.g., 'bnx', 'bnx_datahub').
     */
    public function the_bbbext_plugin_is_enabled(string $pluginname): void {
        // The bbbext plugininfo checks the 'disabled' config key.
        // To enable, we unset the 'disabled' config (see bbbext::enable_plugin).
        unset_config('disabled', 'bbbext_' . $pluginname);
        \core_plugin_manager::reset_caches();
    }

    /**
     * Disable a BigBlueButton extension plugin.
     *
     * @Given /^the bbbext "(?P<pluginname>(?:[^"]|\\")*)" plugin is disabled$/
     * @param string $pluginname The plugin name (e.g., 'bnx', 'bnx_datahub').
     */
    public function the_bbbext_plugin_is_disabled(string $pluginname): void {
        // The bbbext plugininfo checks the 'disabled' config key.
        // To disable, we set 'disabled' to 1 (see bbbext::enable_plugin).
        set_config('disabled', 1, 'bbbext_' . $pluginname);
        \core_plugin_manager::reset_caches();
    }
}
