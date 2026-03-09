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
 * Resolve UI strings with optional sidecar-aware fallback to core components.
 *
 * @package   bbbext_bnx
 * @copyright 2026 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */
class ui_string_helper {
    /**
     * Get localized string from the first enabled bnx sidecar that implements it.
     * Falls back to the provided fallback component when no extension defines the key.
     *
     * @param string $identifier String key identifier.
     * @param mixed|null $a Optional language string data.
     * @param string $fallbackcomponent Fallback component when no extension defines the identifier.
     * @return string
     */
    public static function get(string $identifier, $a = null, string $fallbackcomponent = 'mod_bigbluebuttonbn'): string {
        $stringmanager = get_string_manager();
        foreach (self::get_ordered_sidecar_components() as $component) {
            if ($stringmanager->string_exists($identifier, $component)) {
                return get_string($identifier, $component, $a);
            }
        }

        return get_string($identifier, $fallbackcomponent, $a);
    }

    /**
     * Get enabled bnx sidecar components ordered by extension manager sort order.
     *
     * @return array
     */
    private static function get_ordered_sidecar_components(): array {
        $components = [];
        foreach (sidecar_helper::get_ordered_sidecar_plugins() as $pluginname) {
            $components[] = 'bbbext_' . $pluginname;
        }
        return $components;
    }
}
