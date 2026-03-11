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
 * Manage reminder subscriptions page.
 *
 * @package   bbbext_bnx
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../config.php');

use bbbext_bnx\reminders_utils;
use bbbext_bnx\subscription_utils;
use bbbext_bnx\output\subscriptions;
use mod_bigbluebuttonbn\instance;

require_login();

if (!reminders_utils::is_reminders_enabled()) {
    throw new moodle_exception('activitynotfound', 'bbbext_bnx');
}

$cmid = optional_param('cmid', null, PARAM_INT);
$state = optional_param('state', null, PARAM_INT);

// Handle subscription toggle if parameters are provided.
if ($cmid !== null && $state !== null) {
    $bbbinstance = instance::get_from_cmid($cmid);
    subscription_utils::change_reminder_subcription_user($state, $USER->id, $bbbinstance);
    $meetingname = $bbbinstance->get_meeting_name();
    if ($state) {
        $message = get_string('subscribed:success', 'bbbext_bnx', ['name' => $meetingname]);
    } else {
        $message = get_string('unsubscribed:success', 'bbbext_bnx', ['name' => $meetingname]);
    }
    redirect(
        new moodle_url('/mod/bigbluebuttonbn/extension/bnx/managesubscriptions.php'),
        $message,
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

$PAGE->set_url(new moodle_url('/mod/bigbluebuttonbn/extension/bnx/managesubscriptions.php'));
$PAGE->set_context(context_user::instance($USER->id));
$PAGE->set_title(get_string('unsubscribe:title', 'bbbext_bnx'));
$PAGE->set_heading(get_string('unsubscribe:title', 'bbbext_bnx'));

$renderer = $PAGE->get_renderer('bbbext_bnx');
$subscriptionsoutput = new subscriptions($USER->id);

echo $OUTPUT->header();
echo $renderer->render($subscriptionsoutput);
echo $OUTPUT->footer();
