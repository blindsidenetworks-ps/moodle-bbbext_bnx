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
 * Interface for BNX service operations.
 *
 * @package   bbbext_bnx
 * @copyright 2025 onwards, Blindside Networks Inc
 * @author    Shamiso Jaravaza  (shamiso [dt] jaravaza [at] blindsidenetworks [dt] com)
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace bbbext_bnx\local\services;

/**
 * Interface for BNX services.
 *
 * @package   bbbext_bnx
 */
interface bnx_service_interface {
    /**
     * Delete a BNX record and associated data.
     *
     * @param int $bigbluebuttonbnid The BNX record ID
     * @return bool True if deletion was successful
     */
    public function delete_bnx($bigbluebuttonbnid);
}
