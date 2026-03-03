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

namespace bbbext_bnx\task;

use bbbext_bnx\reminders_utils;
use bbbext_bnx\subscription_utils;
use core\task\adhoc_task;
use core_user;
use moodle_url;
use mod_bigbluebuttonbn\instance;

/**
 * Adhoc task to send reminder messages to enrolled users via the messaging API.
 *
 * @package   bbbext_bnx
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class send_email_reminders_message extends adhoc_task {
    /**
     * Execute the task.
     *
     * @return void
     */
    public function execute() {
        if (!reminders_utils::is_reminders_enabled()) {
            return;
        }

        $customdata = $this->get_custom_data();
        $instance = instance::get_from_instanceid($customdata->instanceid);
        $cmid = $instance->get_cm_id();
        $options = [
            'context' => $instance->get_context(),
        ];
        $emailsubject = $customdata->subject;
        $emailhtmlmessage = format_text($customdata->htmlmessage, FORMAT_MOODLE, $options);
        $emailfooter = format_text($customdata->emailfooter, FORMAT_MOODLE, $options);

        foreach ($customdata->usersid as $userid) {
            $user = core_user::get_user($userid);
            $message = new \core\message\message();
            $message->component = 'bbbext_bnx';
            $message->name = 'reminder';
            $message->userfrom = core_user::get_noreply_user();
            $message->userto = $user;
            $message->subject = $emailsubject;
            $message->fullmessage = $emailhtmlmessage;
            $message->fullmessageformat = FORMAT_HTML;
            $message->fullmessagehtml = $emailhtmlmessage;
            $message->smallmessage = html_to_text($emailhtmlmessage);
            $message->notification = 1;
            $message->contexturl = (new moodle_url(
                '/mod/bigbluebuttonbn/view.php?id',
                ['id' => $cmid]
            ))->out(false);
            $message->contexturlname = $instance->get_meeting_name();

            // Extra content for specific processor.
            $unsubscribeurl = subscription_utils::get_unsubscribe_url($cmid, null, $userid);
            $unsubscribemessage = get_string(
                'emailunsubscribemessage',
                'bbbext_bnx',
                ['unsubscribeurl' => $unsubscribeurl->out(false)]
            );
            $content = [
                '*' => [
                    'footer' => $unsubscribemessage . $emailfooter,
                ],
            ];
            $message->set_additional_content('email', $content);
            message_send($message);
        }
    }
}
