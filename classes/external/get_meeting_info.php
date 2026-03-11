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

namespace bbbext_bnx\external;

use bbbext_bnx\local\helpers\ui_string_helper;
use core_external\external_single_structure;
use core_external\external_value;
use core_external\restricted_context_exception;
use mod_bigbluebuttonbn\instance;

/**
 * External service to fetch meeting information with BNX extensions.
 *
 * @package   bbbext_bnx
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */
class get_meeting_info extends \mod_bigbluebuttonbn\external\meeting_info {
    /**
     * Fetch meeting information.
     *
     * @param int $bigbluebuttonbnid the bigbluebuttonbn instance id
     * @param int $groupid
     * @param bool $updatecache
     * @return array
     * @throws \moodle_exception
     * @throws restricted_context_exception
     */
    public static function execute(
        int $bigbluebuttonbnid,
        int $groupid,
        bool $updatecache = false
    ): array {
        // Reuse the parent's implementation for parameter validation, permission checks,
        // server availability checks and to build the WS-friendly meeting info.
        $result = parent::execute($bigbluebuttonbnid, $groupid, $updatecache);

        // Obtain our local meeting info and copy only the fields we want to override.
        $instance = instance::get_from_instanceid($bigbluebuttonbnid);
        $instance->set_group_id($groupid);
        $meetinginfo = (array) \bbbext_bnx\meeting::get_meeting_info_for_instance($instance, $updatecache);

        if (isset($meetinginfo['joinurl'])) {
            $result['joinurl'] = $meetinginfo['joinurl'];
        }
        if (array_key_exists('presentations', $meetinginfo)) {
            $result['presentations'] = $meetinginfo['presentations'];
        }
        if (array_key_exists('showpresentations', $meetinginfo)) {
            $result['showpresentations'] = $meetinginfo['showpresentations'];
        }
        if (array_key_exists('guestjoinurl', $meetinginfo)) {
            $result['guestjoinurl'] = $meetinginfo['guestjoinurl'];
        }
        if (array_key_exists('guestpassword', $meetinginfo)) {
            $result['guestpassword'] = $meetinginfo['guestpassword'];
        }
        if (array_key_exists('statusmessage', $meetinginfo)) {
            $result['statusmessage'] = $meetinginfo['statusmessage'];
        }
        if (array_key_exists('canjoin', $meetinginfo)) {
            $result['canjoin'] = $meetinginfo['canjoin'];
        }
        if (array_key_exists('statusrunning', $meetinginfo)) {
            $result['statusrunning'] = $meetinginfo['statusrunning'];
        }
        $result['presentationtitle'] = ui_string_helper::get('view_section_title_presentation');

        return $result;
    }

    /**
     * Describe the return structure of the external service.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        $structure = parent::execute_returns();
        $structure->keys['presentationtitle'] = new external_value(PARAM_TEXT, 'Presentation section title', VALUE_OPTIONAL);
        return $structure;
    }
}
