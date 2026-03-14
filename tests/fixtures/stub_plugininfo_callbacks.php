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
 * Stub plugininfo_callbacks fixture for subplugin_state_test.
 *
 * Simulates a sidecar plugin that defines an on_enable() callback,
 * without requiring the real plugin to be installed.
 *
 * @package   bbbext_bnx
 * @copyright 2026 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace bbbext_bnx_teststub;

/**
 * Stub plugininfo_callbacks for use in unit tests.
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
