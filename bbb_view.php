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
 * View for BigBlueButton interaction.
 *
 * @package   bbbext_bnx
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

use mod_bigbluebuttonbn\instance;
use mod_bigbluebuttonbn\local\exceptions\server_not_available_exception;
use mod_bigbluebuttonbn\local\proxy\bigbluebutton_proxy;
use mod_bigbluebuttonbn\logger;

require_once(dirname(dirname(dirname(dirname(dirname(__FILE__))))) . '/config.php');

global $SESSION, $PAGE, $CFG, $DB, $USER, $OUTPUT;

$action = required_param('action', PARAM_TEXT);
$id = optional_param('id', 0, PARAM_INT);
$bn = optional_param('bn', 0, PARAM_INT);
$rid = optional_param('rid', '', PARAM_TEXT);
$rtype = optional_param('rtype', 'presentation', PARAM_TEXT);
$errors = optional_param('errors', '', PARAM_TEXT);
$timeline = optional_param('timeline', 0, PARAM_INT);
$index = optional_param('index', 0, PARAM_INT);
$group = optional_param('group', -1, PARAM_INT);

// Get the bbb instance from either the cmid (id), or the instanceid (bn).
if ($id) {
    $instance = instance::get_from_cmid($id);
} else {
    if ($bn) {
        $instance = instance::get_from_instanceid($bn);
    }
}

if (!$instance) {
    $courseid = optional_param('courseid', 1, PARAM_INT);
    \core\notification::error(get_string('general_error_not_found', 'mod_bigbluebuttonbn', $id));
    redirect(new moodle_url('/course/view.php', ['id' => $courseid]));
}

$cm = $instance->get_cm();
$course = $instance->get_course();
$bigbluebuttonbn = $instance->get_instance_data();
$context = $instance->get_context();

require_login($course, true, $cm);

// Determine group based on actual membership, not session preference.
// This ensures users in the same group join the same meeting instance for guest lobby to work.
$groupid = null;
$groupmode = groups_get_activity_groupmode($cm);

if ($groupmode != NOGROUPS) {
    $context = context_module::instance($cm->id);

    // For users with accessallgroups (e.g., teachers/moderators), check URL parameter first.
    if (has_capability('moodle/site:accessallgroups', $context)) {
        // Allow explicit group selection via URL parameter.
        $explicitgroup = optional_param('group', -1, PARAM_INT);
        if ($explicitgroup > 0) {
            // Validate the group exists and is allowed for this activity.
            $allowedgroups = groups_get_all_groups($cm->course, 0, $cm->groupingid);
            if (array_key_exists($explicitgroup, $allowedgroups)) {
                $groupid = $explicitgroup;
            }
        }

        // If no explicit group specified, use moderator's actual group membership.
        if ($groupid === null) {
            $usergroups = groups_get_all_groups($cm->course, $USER->id, $cm->groupingid);
            if (!empty($usergroups)) {
                // Use the first group the moderator is actually a member of.
                $firstgroup = reset($usergroups);
                $groupid = $firstgroup->id;
            }
        }
    } else {
        // Students: use their actual group membership (no session preference).
        $usergroups = groups_get_all_groups($cm->course, $USER->id, $cm->groupingid);
        if (!empty($usergroups)) {
            // In SEPARATEGROUPS, users only access groups they're members of.
            $firstgroup = reset($usergroups);
            $groupid = $firstgroup->id;
        } else if ($groupmode == SEPARATEGROUPS) {
            // User not in any group in SEPARATEGROUPS mode.
            $groupid = 0;
        }
    }
}

if ($groupid) {
    $instance->set_group_id($groupid);
}

// Print the page header.
$PAGE->set_context($context);
$PAGE->set_url('/mod/bigbluebuttonbn/extension/bnx/bbb_view.php', ['id' => $cm->id, 'bigbluebuttonbn' => $bigbluebuttonbn->id]);
$PAGE->set_title(format_string($bigbluebuttonbn->name));
$PAGE->set_cacheable(false);
$PAGE->set_heading($course->fullname);
$PAGE->blocks->show_only_fake_blocks();

switch (strtolower($action)) {
    case 'join':
        if (empty($bigbluebuttonbn)) {
            throw new moodle_exception('view_error_unable_join', 'bigbluebuttonbn');
            break;
        }
        // Check the origin page.
        $origin = logger::ORIGIN_BASE;
        if ($timeline) {
            $origin = logger::ORIGIN_TIMELINE;
        } else if ($index) {
            $origin = logger::ORIGIN_INDEX;
        }

        try {
            $url = \bbbext_bnx\meeting::join_meeting($instance, $origin);
            redirect($url);
        } catch (server_not_available_exception $e) {
            bigbluebutton_proxy::handle_server_not_available($instance);
        }
        // We should never reach this point.
        break;
}

// When we reach this point, we can close the tab or window where BBB was opened.
echo $OUTPUT->header();
// Behat does not like when we close the Windows as it is expecting to locate
// on click part of the pages (bug with selenium raising an exception). So this is a workaround.
if (!defined('BEHAT_SITE_RUNNING')) {
    $PAGE->requires->js_call_amd('mod_bigbluebuttonbn/rooms', 'setupWindowAutoClose');
}
echo $OUTPUT->footer();
