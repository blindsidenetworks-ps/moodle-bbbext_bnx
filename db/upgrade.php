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
 * Upgrade.
 *
 * @package   bbbext_bnx
 * @copyright 2026 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

/**
 * Perform the upgrade procedures.
 *
 * @param int $oldversion The old version number.
 * @return bool Whether the upgrade was successful.
 */
function xmldb_bbbext_bnx_upgrade($oldversion) {
    global $DB;

    if ($oldversion < 2026031301) {
        // Ensure BigBlueButtonBN module is enabled for already-installed BNX sites.
        if ($DB->record_exists('modules', ['name' => 'bigbluebuttonbn'])) {
            \core\plugininfo\mod::enable_plugin('bigbluebuttonbn', 1);
        }

        upgrade_plugin_savepoint(true, 2026031301, 'bbbext', 'bnx');
    }

    return true;
}
