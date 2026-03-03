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

use bbbext_bnx\bigbluebuttonbn\mod_instance_helper;
use bbbext_bnx\local\services\bnx_settings_service;
use bbbext_bnx\reminders_utils;

/**
 * Test data generator for bbbext_bnx reminders.
 *
 * @package   bbbext_bnx
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bbbext_bnx_generator extends \component_generator_base {
    /**
     * Enable reminder for a BigBlueButton instance.
     *
     * @param int $bbbinstanceid
     * @return void
     */
    public function enable_reminder(int $bbbinstanceid): void {
        $bnxid = $this->resolve_bnxid($bbbinstanceid);
        $service = bnx_settings_service::get_service();
        $service->set_settings($bnxid, ['reminderenabled' => '1']);
    }

    /**
     * Disable reminder for a BigBlueButton instance.
     *
     * @param int $bbbinstanceid
     * @return void
     */
    public function disable_reminder(int $bbbinstanceid): void {
        $bnxid = $this->resolve_bnxid($bbbinstanceid);
        $service = bnx_settings_service::get_service();
        $service->set_settings($bnxid, ['reminderenabled' => '0']);
    }

    /**
     * Enable guest reminders for a BigBlueButton instance.
     *
     * @param int $bbbinstanceid
     * @return void
     */
    public function enable_reminder_for_guest(int $bbbinstanceid): void {
        $bnxid = $this->resolve_bnxid($bbbinstanceid);
        $service = bnx_settings_service::get_service();
        $service->set_settings($bnxid, ['remindertoguestsenabled' => '1']);
    }

    /**
     * Add a reminder timespan for a BigBlueButton instance.
     *
     * @param object|array $record
     * @return stdClass
     */
    public function add_reminder($record): stdClass {
        global $DB;
        if (is_object($record)) {
            $record = (array) $record;
        }
        $reminder = (object) array_merge([
            'timespan' => reminders_utils::ONE_HOUR,
            'lastsent' => 0,
        ], $record);
        $reminder->id = $DB->insert_record(mod_instance_helper::REMINDERS_TABLE, $reminder);
        return $reminder;
    }

    /**
     * Add a guest email to a BigBlueButton instance.
     *
     * @param object|array $record
     * @return stdClass
     */
    public function add_guest($record): stdClass {
        global $DB, $USER;
        if (is_object($record)) {
            $record = (array) $record;
        }
        $now = time();
        $guest = (object) array_merge([
            'userfrom' => $USER->id,
            'isenabled' => 1,
            'issent' => 0,
            'email' => 'randomemail@moodle.com',
            'usermodified' => $USER->id,
            'timecreated' => $now,
            'timemodified' => $now,
        ], $record);
        $guest->id = $DB->insert_record(mod_instance_helper::REMINDERS_GUESTS_TABLE, $guest);
        return $guest;
    }

    /**
     * Resolve the bnxid for a bbb instance, creating the bnx record if needed.
     *
     * @param int $bbbinstanceid
     * @return int
     */
    private function resolve_bnxid(int $bbbinstanceid): int {
        global $DB;
        $record = $DB->get_record('bbbext_bnx', ['bigbluebuttonbnid' => $bbbinstanceid]);
        if ($record) {
            return (int)$record->id;
        }
        $now = time();
        return (int)$DB->insert_record('bbbext_bnx', (object) [
            'bigbluebuttonbnid' => $bbbinstanceid,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
    }
}
