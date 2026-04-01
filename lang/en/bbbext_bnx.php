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
 * English language pack for BigBlueButton BN Experience
 *
 * @package   bbbext_bnx
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

defined('MOODLE_INTERNAL') || die();

$string['activitynotfound'] = 'Activity not found';
$string['addreminder'] = 'Add reminder';
$string['approvalbeforejoin'] = 'Moderator approval required to join session +';
$string['approvalbeforejoin_default'] = 'Waiting Room enabled by default';
$string['approvalbeforejoin_default_desc'] = 'When enabled, participants must wait for moderator approval before joining a session by default.';
$string['approvalbeforejoin_editable'] = 'Allow teachers to change the Waiting Room setting per activity';
$string['approvalbeforejoin_editable_desc'] = 'When enabled, teachers can turn the Waiting Room on or off for individual activities.';
$string['approvalbeforejoin_help'] = 'If enabled, participants must be approved by a moderator before joining the session.';
$string['check_emails_reminder'] = 'Check emails reminder';
$string['config_general_description_credentials_preconfigured'] = 'BigBlueButton server credentials are configured in config.php and cannot be edited here.';
$string['emailcontent'] = 'Email Customization';
$string['emailcontent:desc'] = 'These settings will customize the message sent to users.';
$string['emailfooter'] = 'Footer information';
$string['emailfooter:desc'] = 'Add extra information such as institution location and contact details as a footer to emails.';
$string['emailsubject'] = 'Email Subject';
$string['emailsubject:default'] = 'Reminder for the meeting {$name}';
$string['emailsubject:desc'] = 'The subject of the email.';
$string['emailtemplate'] = 'Email template';
$string['emailtemplate:default'] = '<p>
Hi,<br><br>
This is a reminder about the upcoming meeting <a href="{$url}">{$name}</a> in {$course_fullname} scheduled to start on {$date}.
</p>';
$string['emailtemplate:desc'] = 'Email template when sending reminders. The following variables can be used:<ul>
    <li>{$course_fullname}: the course fullname</li>
    <li>{$course_shortname}: the course shortname</li>
    <li>{$date}: the meeting date and time</li>
    <li>{$name}: the meeting name</li>
</ul>';
$string['emailunsubscribemessage'] = '<span>
You can unsubscribe to this reminder by clicking on the following <a href="{$a->unsubscribeurl}">Unsubscribe link</a>.
</span>';
$string['error:duplicate'] = 'You have already one reminder for this meeting for the same time span';
$string['invalidinstanceid'] = 'Invalid instance id';
$string['messageprovider:reminder'] = 'BigBlueButton email reminder';
$string['mod_form_block_guestaccess'] = 'Guest access +';
$string['mod_form_block_room'] = 'Room settings +';
$string['mod_form_reminders'] = 'Email notifications';
$string['mod_form_reminders_desc'] = 'Send reminders to students as notifications.';
$string['navlabel'] = 'BigBlueButton +';
$string['options_disabled'] = 'Disabled';
$string['options_enabled'] = 'Enabled';
$string['pluginname'] = 'BigBlueButton BN Experience';
$string['preview_toggle_label_close'] = 'Hide additional preview thumbnails';
$string['preview_toggle_label_plural'] = 'Show {$a} more preview thumbnails';
$string['preview_toggle_label_singular'] = 'Show one more preview thumbnail';
$string['privacy:metadata'] = 'The BigBlueButton BN Experience plugin stores user subscription preferences for email reminders.';
$string['privacy:metadata:preference:bbbext_bnx_reminder'] = 'Whether to receive reminders about upcoming sessions of a BigBlueButton activity.';
$string['privacy:reminderpreferenceno'] = 'Do not receive reminders for upcoming BigBlueButton activity ID {$a->activityid} sessions.';
$string['privacy:reminderpreferenceyes'] = 'Receive reminders for upcoming BigBlueButton activity ID {$a->activityid} sessions.';
$string['reminder'] = 'Reminder';
$string['reminder:message'] = 'before meeting starts';
$string['reminder:openingtime:disabled'] = 'Opening time is disabled';
$string['reminder_default'] = 'Reminders enabled by default';
$string['reminder_default_desc'] = 'When enabled, email reminders for upcoming sessions are turned on by default for new activities.';
$string['reminder_editable'] = 'Allow teachers to change the Reminder setting per activity';
$string['reminder_editable_desc'] = 'When enabled, teachers can turn email reminders on or off for individual activities.';
$string['reminders'] = 'Reminders';
$string['reminders:enabled'] = 'Send email reminders before session';
$string['reminders:guestenabled'] = 'Add guests to the list of users to send the reminder to';
$string['reminders:preferences'] = 'BigBlueButton reminders preferences';
$string['reminders_help'] = 'If enabled and a start date is set, send email reminders for users registered to the activity.';
$string['section_reminders_desc'] = 'Send email reminders to participants before a session starts.';
$string['section_reminders_heading'] = 'Email Reminders';
$string['section_waitingroom_desc'] = 'Require moderator approval before participants can join a session.';
$string['section_waitingroom_heading'] = 'Waiting Room';
$string['subscribed'] = 'Subscribed';
$string['subscribed:cancel'] = 'No changes have been made to your subscription';
$string['subscribed:success'] = 'Subscribed to {$a->name} reminders successfully!';
$string['subscriptions'] = 'Subscriptions';
$string['timespan'] = 'Time span';
$string['timespan:bell'] = 'Timespan';
$string['timespan:p1d'] = 'One day';
$string['timespan:p1w'] = 'One week';
$string['timespan:p2d'] = 'Two days';
$string['timespan:pt1h'] = 'One hour';
$string['timespan:pt2h'] = 'Two hours';
$string['unsubscribe'] = 'Unsubscribe';
$string['unsubscribe:label'] = 'Are you sure you want to unsubscribe ?';
$string['unsubscribe:managepreferences'] = 'Manage reminder preferences';
$string['unsubscribe:title'] = 'Manage BigBlueButton Reminder Subscriptions';
$string['unsubscribe:title:meeting'] = 'Unsubscribe to the reminder for BigBlueButton activity {$a}';
$string['unsubscribed'] = 'Unsubscribed';
$string['unsubscribed:success'] = 'Unsubscribed from {$a->name} reminders successfully!';
$string['view_recording_list_actionbar_publish'] = 'Make it visible';
$string['view_recording_list_actionbar_unpublish'] = 'Make it hidden';
$string['view_recording_protect_confirmation'] = 'Are you sure you want to make this {$a} private?';
$string['view_recording_publish_confirmation'] = 'Are you sure you want to make this {$a} visible?';
$string['view_recording_search'] = 'Search';
$string['view_recording_search_placeholder'] = 'Search for recordings';
$string['view_recording_unprotect_confirmation'] = 'Are you sure you want to make this {$a} public?';
$string['view_recording_unpublish_confirmation'] = 'Are you sure you want to make this {$a} hidden?';
