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

use bbbext_bnx\local\services\bnx_settings_service;
use bbbext_bnx\bigbluebuttonbn\mod_instance_helper;
use bbbext_bnx\local\persistent\guest_email;
use bbbext_bnx\reminders_utils;
use bbbext_bnx\subscription_utils;
use core\task\scheduled_task;
use DateInterval;
use DateTime;
use mod_bigbluebuttonbn\instance;

/**
 * Scheduled task to check and dispatch email reminders.
 *
 * @package   bbbext_bnx
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class check_emails_reminder extends scheduled_task {
    /** Maximum number of emails per adhoc task batch. */
    const MAX_EMAIL_PER_TASK = 100;

    /**
     * Get the task name.
     *
     * @return string
     */
    public function get_name() {
        return get_string('check_emails_reminder', 'bbbext_bnx');
    }

    /**
     * Execute the scheduled task.
     *
     * @return void
     */
    public function execute() {
        global $DB;

        if (!reminders_utils::is_reminders_enabled()) {
            return;
        }

        $allinstancesreminder = $DB->get_recordset_sql(
            "SELECT bnx.bigbluebuttonbnid,
                    COALESCE(MAX(CASE WHEN s2.name = 'remindertoguestsenabled' THEN s2.value END), '0')
                        AS remindertoguestsenabled
               FROM {bbbext_bnx} bnx
               JOIN {bbbext_bnx_settings} s ON s.bnxid = bnx.id
                    AND s.name = 'reminderenabled' AND s.value = '1'
          LEFT JOIN {bbbext_bnx_settings} s2 ON s2.bnxid = bnx.id
                    AND s2.name = 'remindertoguestsenabled'
           GROUP BY bnx.bigbluebuttonbnid"
        );

        foreach ($allinstancesreminder as $instancereminder) {
            $instance = instance::get_from_instanceid($instancereminder->bigbluebuttonbnid);
            if (empty($instance->get_instance_var('openingtime'))) {
                continue;
            }

            $allreminders = $DB->get_recordset(
                mod_instance_helper::REMINDERS_TABLE,
                ['bigbluebuttonbnid' => $instancereminder->bigbluebuttonbnid]
            );

            $emailsubject = $this->get_subject($instance);
            $emailhtmlmessage = $this->get_html_message($instance);
            $emailfooter = $this->get_footer($instance);

            foreach ($allreminders as $reminder) {
                $interval = new DateInterval($reminder->timespan);
                $openingtime = $instance->get_instance_var('openingtime');
                if (empty($openingtime)) {
                    continue;
                }

                $reminderstart = new DateTime();
                $reminderstart->setTimezone(\core_date::get_server_timezone_object());
                $reminderstart->setTimestamp($openingtime);
                $reminderstart->sub($interval);
                $now = new DateTime('now', \core_date::get_server_timezone_object());

                // Calculate the difference between now and reminder start.
                $diff = $now->diff($reminderstart);
                if ($diff->invert == 1 && empty($reminder->lastsent)) {
                    // Send to enrolled users via messaging API.
                    $allusers = get_enrolled_users($instance->get_context(), 'mod/bigbluebuttonbn:join');
                    $userstoemail = [];
                    foreach ($allusers as $user) {
                        if (!subscription_utils::is_user_subscribed($user->id, $instance)) {
                            continue;
                        }
                        $userstoemail[] = $user;
                    }

                    // Batch enrolled users.
                    $useremails = count($userstoemail);
                    for ($i = 0; $i < $useremails; $i += self::MAX_EMAIL_PER_TASK) {
                        $emailreminder = new send_email_reminders_message();
                        $emailreminder->set_custom_data([
                            'usersid' => array_map(fn($user) => $user->id, array_slice($userstoemail, $i, 100)),
                            'instanceid' => $instance->get_instance_id(),
                            'reminderid' => $reminder->id,
                            'subject' => $emailsubject,
                            'htmlmessage' => $emailhtmlmessage,
                            'emailfooter' => $emailfooter,
                        ]);
                        \core\task\manager::queue_adhoc_task($emailreminder);
                    }

                    // Send to guests if enabled.
                    if (!empty($instancereminder->remindertoguestsenabled)) {
                        $guestemails = guest_email::get_records([
                            'bigbluebuttonbnid' => $instance->get_instance_id(),
                            'isenabled' => true,
                        ]);
                        $allemails = [];
                        foreach ($guestemails as $guestemail) {
                            $email = $guestemail->get('email');
                            if (!subscription_utils::is_user_email_subscribed($email, $instance)) {
                                continue;
                            }
                            $allemails[] = $email;
                        }
                        sort($allemails);

                        // Batch guest emails.
                        $alluseremails = count($allemails);
                        for ($i = 0; $i < $alluseremails; $i += self::MAX_EMAIL_PER_TASK) {
                            $emailreminder = new send_email_reminders();
                            $emailreminder->set_custom_data([
                                'emails' => array_slice($allemails, $i, 100),
                                'instanceid' => $instance->get_instance_id(),
                                'reminderid' => $reminder->id,
                                'subject' => $emailsubject,
                                'htmlmessage' => $emailhtmlmessage,
                                'emailfooter' => $emailfooter,
                            ]);
                            \core\task\manager::queue_adhoc_task($emailreminder);
                        }
                    }

                    $DB->set_field(
                        mod_instance_helper::REMINDERS_TABLE,
                        'lastsent',
                        time(),
                        ['id' => $reminder->id]
                    );
                }
            }
            $allreminders->close();
        }
        $allinstancesreminder->close();
    }

    /**
     * Get the subject of the notification.
     *
     * @param instance $instance
     * @return string
     */
    protected function get_subject(instance $instance): string {
        return $this->get_email_content('emailsubject', $instance);
    }

    /**
     * Get variables to make available to strings.
     *
     * @param instance $instance
     * @return array
     */
    protected function get_string_vars(instance $instance): array {
        return [
            'course_fullname' => $instance->get_course()->fullname,
            'course_shortname' => $instance->get_course()->shortname,
            'name' => $instance->get_cm()->name,
            'url' => (new \moodle_url(
                '/mod/bigbluebuttonbn/view.php',
                ['id' => $instance->get_cm_id()]
            ))->out(false),
            'date' => userdate($instance->get_instance_var('openingtime')),
        ];
    }

    /**
     * Get the HTML message content.
     *
     * @param instance $instance
     * @return string
     */
    protected function get_html_message(instance $instance): string {
        return $this->get_email_content('emailtemplate', $instance);
    }

    /**
     * Get the footer of the email.
     *
     * @param instance $instance
     * @return string
     */
    protected function get_footer(instance $instance): string {
        $htmlfooter = $this->get_email_content('emailfooter', $instance);
        if (!empty($htmlfooter)) {
            $htmlfooter = '<br>' . $htmlfooter;
        }
        return $htmlfooter ?? '';
    }

    /**
     * Get the processed message content.
     *
     * @param string $config The configuration setting key
     * @param instance $instance
     * @return string
     */
    protected function get_email_content(string $config, instance $instance): string {
        $text = get_config('bbbext_bnx', $config);
        $vars = $this->get_string_vars($instance);
        return reminders_utils::replace_vars_in_text($vars, $text);
    }
}
