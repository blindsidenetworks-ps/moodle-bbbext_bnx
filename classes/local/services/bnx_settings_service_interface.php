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
 * Interface for BNX settings services (named for clarity).
 *
 * @package   bbbext_bnx
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

namespace bbbext_bnx\local\services;

/**
 * Interface for BNX settings services (named for clarity).
 *
 * @package   bbbext_bnx
 */
interface bnx_settings_service_interface {
    /**
     * Get all settings for a BNX record.
     *
     * @param int $bnxid
     * @return array<string,string>
     */
    public function get_settings(int $bnxid): array;

    /**
     * Get a single setting value.
     *
     * @param int $bnxid
     * @param string $name
     * @return string|null
     */
    public function get_setting(int $bnxid, string $name): ?string;

    /**
     * Get a single setting value using the module identifier.
     *
     * @param int $moduleid
     * @param string $name
     * @return string|null
     */
    public function get_setting_for_module(int $moduleid, string $name): ?string;

    /**
     * Upsert multiple settings for a BNX record.
     *
     * @param int $bnxid
     * @param array $values
     * @return void
     */
    public function set_settings(int $bnxid, array $values): void;

    /**
     * Delete all settings for a BNX record.
     *
     * @param int $bnxid
     * @return void
     */
    public function delete_settings(int $bnxid): void;

    /**
     * Delete a single setting for a BNX record.
     *
     * @param int $bnxid
     * @param string $name
     * @return void
     */
    public function delete_setting(int $bnxid, string $name): void;
}
