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
 * Provider for collecting action URL parameters based on enabled features.
 *
 * @package   bbbext_bnx
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Shamiso Jaravaza (shamiso [dt] jaravaza [at] blindsidenetworks [dt] com)
 */

namespace bbbext_bnx\local\bigbluebutton;

use bbbext_bnx\local\services\bnx_settings_service;
use bbbext_bnx\local\services\bnx_settings_service_interface;

/**
 * Collects action URL parameters based on enabled features.
 *
 * This class determines which parameters should be included in the action URL
 * based on which features are enabled for a given module instance.
 *
 * @package   bbbext_bnx
 */
class action_url_parameters {
    /**
     * Collect all parameters that should be added to the action URL.
     *
     * Examines enabled features for the given module instance and returns
     * parameters that should be included in the URL.
     *
     * @param string $action the action being performed
     * @param int $instanceid the BBB instance ID
     * @return array<string, mixed> parameters keyed by name
     */
    public static function get_parameters(string $action, int $instanceid): array {
        if (!self::is_approval_before_join_enabled($instanceid)) {
            return [];
        }
        if ($action === 'create') {
            return [
                'guestPolicy' => 'ASK_MODERATOR',
            ];
        }
        if ($action === 'join') {
            return [
                'guest' => 'true',
            ];
        }
        return [];
    }

    /**
     * Check if moderator approval before join is enabled for the instance.
     *
     * @param int $instanceid the BigBlueButton module instance ID
     * @return bool true if approval before join is enabled
     */
    private static function is_approval_before_join_enabled(int $instanceid): bool {
        if (get_config('bbbext_bnx', 'approvalbeforejoin_editable')) {
            $service = bnx_settings_service::get_service();
            $value = $service->get_setting_for_module($instanceid, 'approvalbeforejoin');
            return (bool) $value;
        }
        $default = get_config('bbbext_bnx', 'approvalbeforejoin_default');
        return (bool) $default;
    }
}
