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

namespace bbbext_bnx;

use mod_bigbluebuttonbn\instance;
use moodle_url;

/**
 * Subscription utilities for email reminders.
 *
 * @package   bbbext_bnx
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class subscription_utils {
    /**
     * Change the reminder subscription for a user.
     *
     * @param bool $subscribe whether to subscribe or unsubscribe
     * @param int $userid the user id
     * @param instance $instance the BigBlueButton instance
     * @return void
     */
    public static function change_reminder_subcription_user(bool $subscribe, int $userid, instance $instance): void {
        $prefname = 'bbbext_bnx_reminder_' . $instance->get_instance_id();
        set_user_preference($prefname, $subscribe ? 1 : 0, $userid);
    }

    /**
     * Change the reminder subscription for a guest email.
     *
     * @param bool $subscribe whether to subscribe or unsubscribe
     * @param string $email the guest email address
     * @param instance $instance the BigBlueButton instance
     * @return void
     */
    public static function change_reminder_subcription_email(bool $subscribe, string $email, instance $instance): void {
        global $DB;
        $DB->set_field('bbbext_bnx_reminders_guests', 'isenabled', $subscribe ? 1 : 0, [
            'email' => $email,
            'bigbluebuttonbnid' => $instance->get_instance_id(),
        ]);
    }

    /**
     * Check if a user is subscribed to reminders for an instance.
     *
     * @param int $userid the user id
     * @param instance $instance the BigBlueButton instance
     * @return bool
     */
    public static function is_user_subscribed(int $userid, instance $instance): bool {
        $prefname = 'bbbext_bnx_reminder_' . $instance->get_instance_id();
        $pref = get_user_preferences($prefname, null, $userid);
        // Default to subscribed if no preference is set.
        return $pref === null || (int) $pref === 1;
    }

    /**
     * Check if a guest email is subscribed to reminders for an instance.
     *
     * @param string $email the guest email address
     * @param instance $instance the BigBlueButton instance
     * @return bool
     */
    public static function is_user_email_subscribed(string $email, instance $instance): bool {
        $guestemail = \bbbext_bnx\local\persistent\guest_email::get_record([
            'email' => $email,
            'bigbluebuttonbnid' => $instance->get_instance_id(),
        ]);
        if (empty($guestemail)) {
            return false;
        }
        return !empty($guestemail->get('isenabled'));
    }

    /**
     * Get the unsubscribe URL for a given context.
     *
     * @param int $cmid course module id
     * @param string|null $email guest email (null for enrolled users)
     * @param int|null $userid user id (null for guests)
     * @return moodle_url
     */
    public static function get_unsubscribe_url(int $cmid, ?string $email = null, ?int $userid = null): moodle_url {
        $params = ['cmid' => $cmid, 'state' => 0];
        if ($email) {
            $params['email'] = $email;
        }
        if ($userid) {
            $params['userid'] = $userid;
        }
        return new moodle_url('/mod/bigbluebuttonbn/extension/bnx/subscription.php', $params);
    }
}
