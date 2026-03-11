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
 * Subscription endpoint for toggle/unsubscribe actions.
 *
 * @package   bbbext_bnx
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../config.php');

use bbbext_bnx\reminders_utils;
use bbbext_bnx\subscription_utils;
use bbbext_bnx\form\unsubscribe;
use mod_bigbluebuttonbn\instance;

$cmid = required_param('cmid', PARAM_INT);
$email = optional_param('email', null, PARAM_EMAIL);
$userid = optional_param('userid', null, PARAM_INT);
$state = optional_param('state', null, PARAM_INT);

if (!reminders_utils::is_reminders_enabled()) {
    throw new moodle_exception('activitynotfound', 'bbbext_bnx');
}

$bbbinstance = instance::get_from_cmid($cmid);
$meetingname = $bbbinstance->get_meeting_name();

$PAGE->set_url(new moodle_url('/mod/bigbluebuttonbn/extension/bnx/subscription.php', ['cmid' => $cmid]));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('unsubscribe:title:meeting', 'bbbext_bnx', $meetingname));
$PAGE->set_heading(get_string('unsubscribe:title:meeting', 'bbbext_bnx', $meetingname));

// If state is explicitly set (from email link), handle directly.
if ($state !== null && ($email || $userid)) {
    if ($email) {
        subscription_utils::change_reminder_subcription_email((bool) $state, $email, $bbbinstance);
    }
    if ($userid) {
        subscription_utils::change_reminder_subcription_user((bool) $state, $userid, $bbbinstance);
    }
    $message = $state
        ? get_string('subscribed:success', 'bbbext_bnx', ['name' => $meetingname])
        : get_string('unsubscribed:success', 'bbbext_bnx', ['name' => $meetingname]);
    redirect(
        new moodle_url('/'),
        $message,
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

// Interactive form flow requires authentication.
require_login();

// Show confirmation form for manual unsubscription.
$customdata = [
    'cmid' => $cmid,
    'email' => $email,
    'userid' => $userid,
    'meetingname' => $meetingname,
];

$mform = new unsubscribe(null, $customdata);

if ($mform->is_cancelled()) {
    redirect(
        new moodle_url('/'),
        get_string('subscribed:cancel', 'bbbext_bnx'),
        null,
        \core\output\notification::NOTIFY_INFO
    );
} else if ($data = $mform->get_data()) {
    if (!empty($data->email)) {
        subscription_utils::change_reminder_subcription_email(false, $data->email, $bbbinstance);
    }
    if (!empty($data->userid)) {
        subscription_utils::change_reminder_subcription_user(false, (int) $data->userid, $bbbinstance);
    }
    redirect(
        new moodle_url('/'),
        get_string('unsubscribed:success', 'bbbext_bnx', ['name' => $meetingname]),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
