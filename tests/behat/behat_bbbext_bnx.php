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

use bbbext_bnx\local\helpers\joinurl_helper;
use mod_bigbluebuttonbn\instance;

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

    /**
     * Navigate to BNX guest page for a BigBlueButton activity.
     *
     * @Given /^I am on the "(?P<identifier_string>(?:[^"\\]|\\.)*)" "bbbext_bnx > BigblueButtonBN Guest" page$/
     * @param string $identifier activity name identifier
     * @return void
     */
    public function i_am_on_bnx_guest_page(string $identifier): void {
        $cm = $this->get_cm_by_activity_name('bigbluebuttonbn', $identifier);
        $instance = instance::get_from_cmid($cm->id);
        $url = joinurl_helper::build_guest_join_url($instance);
        // Pre-fill password to simplify guest submission in Behat.
        $url->param('password', $instance->get_guest_access_password());
        $this->getSession()->visit($this->locate_path($url->out(false)));
    }
}
