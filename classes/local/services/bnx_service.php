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
 * BNX service implementation.
 *
 * @package   bbbext_bnx
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Shamiso Jaravaza  (shamiso [dt] jaravaza [at] blindsidenetworks [dt] com)
 */

namespace bbbext_bnx\local\services;

/**
 * Service wrapper for BNX.
 *
 * @package   bbbext_bnx
 */
class bnx_service implements bnx_service_interface {
    /**
     * Cached instance for factory.
     *
     * @var bnx_service_interface|null
     */
    protected static ?bnx_service_interface $service = null;

    /**
     * Get the shared service instance (factory).
     *
     * @return bnx_service_interface
     */
    public static function get_service(): bnx_service_interface {
        if (self::$service === null) {
            self::$service = new self();
        }
        return self::$service;
    }

    /**
     * Test hook to override the service instance.
     *
     * @param bnx_service_interface|null $service
     * @return void
     */
    public static function set_service(?bnx_service_interface $service = null): void {
        self::$service = $service;
    }

    /**
     * Table storing bnx records.
     */
    public const BNX_TABLE = 'bbbext_bnx';

    /**
     * Delete a BNX record and associated data.
     *
     * @param int $bbbid The BNX record ID
     * @return bool True if deletion was successful
     */
    public function delete_bnx($bbbid) {
        global $DB;
        return $DB->delete_records(self::BNX_TABLE, ['bigbluebuttonbnid' => $bbbid]);
    }
}
