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

use mod_bigbluebuttonbn\instance as core_instance;
use moodle_url;

/**
 * Small helper to build join URL pointing to the subplugin join handler.
 *
 * @package   bbbext_bnx
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */
class joinurl_helper {
    /**
     * Build a moodle_url pointing to the subplugin bbb_view join handler.
     *
     * @param core_instance $instance
     * @return moodle_url
     */
    public static function build_join_url(core_instance $instance): moodle_url {
        return new moodle_url('/mod/bigbluebuttonbn/extension/bnx/bbb_view.php', [
            'action' => 'join',
            'id' => $instance->get_cm()->id,
            'bn' => $instance->get_instance_id(),
        ]);
    }
}
