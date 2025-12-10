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
 * External functions and service declaration for the BNX extension.
 *
 * Documentation: {@link https://moodledev.io/docs/apis/subsystems/external/description}
 *
 * @package    bbbext_bnx
 * @category   webservice
 * @copyright  2025 onwards, Blindside Networks Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'bbbext_bnx_get_recordings' => [
        'classname' => 'bbbext_bnx\\external\\get_recordings',
        'methodname' => 'execute',
        'description' => 'Returns a list of recordings ready to be processed by a datatable.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/bigbluebuttonbn:view',
        'services' => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],
    'bbbext_bnx_get_recordings_to_import' => [
        'classname' => 'bbbext_bnx\\external\\get_recordings_to_import',
        'methodname' => 'execute',
        'description' => 'Returns a list of recordings ready to import to be processed by a datatable.',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'mod/bigbluebuttonbn:importrecordings',
        'services' => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],
];
