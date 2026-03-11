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
use mod_bigbluebuttonbn\instance;

/**
 * Adhoc task to send email reminders to guest users.
 *
 * @package   bbbext_bnx
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class send_email_reminders extends adhoc_task {
    /**
     * Execute the task.
     *
     * @return void
     */
    public function execute(): void {
        if (!reminders_utils::is_reminders_enabled()) {
            return;
        }

        $customdata = $this->get_custom_data();
        $emailsubject = $customdata->subject;
        $emailhtmlmessage = $customdata->htmlmessage;
        $emailfooter = $customdata->emailfooter;
        $instance = instance::get_from_instanceid($customdata->instanceid);
        $cmid = $instance->get_cm_id();

        foreach ($customdata->emails as $email) {
            $user = core_user::get_noreply_user();
            $user->email = $email;
            $user->mailformat = FORMAT_HTML;
            $unsubscribeurl = subscription_utils::get_unsubscribe_url($cmid, $email);
            $fullmessage = $emailhtmlmessage . '<br><br>'
                . get_string(
                    'emailunsubscribemessage',
                    'bbbext_bnx',
                    ['unsubscribeurl' => $unsubscribeurl->out(false)]
                );
            $fullmessage .= $emailfooter;
            email_to_user(
                $user,
                core_user::get_noreply_user(),
                $emailsubject,
                format_text(html_to_text($fullmessage), FORMAT_MOODLE, ['filter' => true]),
                $fullmessage
            );
        }
    }
}
