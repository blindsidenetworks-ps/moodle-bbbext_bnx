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

namespace bbbext_bnx\local\persistent;

use core\persistent;

/**
 * Persistent class for guest email records in reminders.
 *
 * @package   bbbext_bnx
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class guest_email extends persistent {
    /** Table name. */
    const TABLE = 'bbbext_bnx_reminders_guests';

    /**
     * Define the properties of this persistent.
     *
     * @return array
     */
    protected static function define_properties(): array {
        return [
            'bigbluebuttonbnid' => [
                'type' => PARAM_INT,
                'null' => NULL_ALLOWED,
            ],
            'email' => [
                'type' => PARAM_EMAIL,
                'null' => NULL_ALLOWED,
            ],
            'userfrom' => [
                'type' => PARAM_INT,
                'default' => 0,
            ],
            'issent' => [
                'type' => PARAM_INT,
                'default' => 0,
                'null' => NULL_ALLOWED,
            ],
            'isenabled' => [
                'type' => PARAM_INT,
                'default' => 1,
                'null' => NULL_ALLOWED,
            ],
        ];
    }

    /**
     * Create a guest email record, or update if already exists.
     *
     * @param string $email the guest email
     * @param int $instanceid the BigBlueButton instance id
     * @param int $userfrom the user id who added this guest
     * @return static
     */
    public static function create_guest_mail_record(string $email, int $instanceid, int $userfrom): self {
        global $DB;
        $existing = $DB->get_record(self::TABLE, [
            'email' => $email,
            'bigbluebuttonbnid' => $instanceid,
            'userfrom' => $userfrom,
        ]);
        if ($existing) {
            $persistent = new self($existing->id, $existing);
            $persistent->set('isenabled', 1);
            $persistent->save();
            return $persistent;
        }
        $persistent = new self(0, (object) [
            'bigbluebuttonbnid' => $instanceid,
            'email' => $email,
            'userfrom' => $userfrom,
        ]);
        $persistent->create();
        return $persistent;
    }
}
