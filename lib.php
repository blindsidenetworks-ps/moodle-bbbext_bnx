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
 * Callback implementations for the BN Experience extension.
 *
 * @package    bbbext_bnx
 * @copyright  2025 onwards, Blindside Networks Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */
/**
 * In place editable callback for BNX-managed recording fields.
 *
 * @param string $itemtype editable item type
 * @param string $itemid identifier for the editable item
 * @param mixed $newvalue new value to persist
 * @return \core\output\inplace_editable|null
 */
function bbbext_bnx_inplace_editable(string $itemtype, string $itemid, $newvalue) {
    $editableclass = "\\bbbext_bnx\\output\\recording_{$itemtype}_editable";
    if (class_exists($editableclass) && method_exists($editableclass, 'update')) {
        return $editableclass::update($itemid, $newvalue);
    }

    return null; // Let core throw the standard exception for unknown editables.
}
