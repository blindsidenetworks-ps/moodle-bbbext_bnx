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
 * Restore support for the BN Experience subplugin.
 *
 * @package   bbbext_bnx
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */
class restore_bbbext_bnx_subplugin extends restore_subplugin {
    /**
     * Declare the paths handled by this subplugin during restore.
     *
     * @return restore_path_element[]
     */
    protected function define_bigbluebuttonbn_subplugin_structure() {
        $paths = [];

        $paths[] = new restore_path_element(
            $this->get_namefor(''),
            $this->get_pathfor('/bbbext_bnx')
        );

        $paths[] = new restore_path_element(
            $this->get_namefor('bnxsetting'),
            $this->get_pathfor('/bbbext_bnx/bbbext_bnx_settings')
        );

        $paths[] = new restore_path_element(
            $this->get_namefor('reminder'),
            $this->get_pathfor('/bbbext_bnx_reminders')
        );

        $paths[] = new restore_path_element(
            $this->get_namefor('reminderguest'),
            $this->get_pathfor('/bbbext_bnx_reminders_guests')
        );

        return $paths;
    }

    /**
     * Persist the BN Experience record for a restored activity.
     *
     * @param array $data
     */
    public function process_bbbext_bnx($data) {
        global $DB;

        $data = (object) $data;
        $data->bigbluebuttonbnid = $this->get_new_parentid('bigbluebuttonbn');

        if (empty($data->timecreated)) {
            $data->timecreated = time();
        }

        if (empty($data->timemodified)) {
            $data->timemodified = $data->timecreated;
        }

        $newid = $DB->insert_record('bbbext_bnx', $data);
        $this->set_mapping('bbbext_bnx', $data->id, $newid);
    }

    /**
     * Persist the BN Experience setting records for a restored activity.
     *
     * @param array $data
     */
    public function process_bbbext_bnx_bnxsetting($data) {
        global $DB;

        $data = (object) $data;
        $data->bnxid = $this->get_new_parentid('bbbext_bnx');

        if (empty($data->bnxid)) {
            // Parent record not restored (unlikely but safe guard).
            return;
        }

        if (empty($data->timemodified)) {
            $data->timemodified = time();
        }
        $DB->insert_record('bbbext_bnx_settings', $data);
    }

    /**
     * Restore a reminder timespan record.
     *
     * @param array $data
     */
    public function process_bbbext_bnx_reminder($data) {
        global $DB;

        $data = (object) $data;
        $data->bigbluebuttonbnid = $this->get_new_parentid('bigbluebuttonbn');
        $DB->insert_record('bbbext_bnx_reminders', $data);
    }

    /**
     * Restore a guest email record.
     *
     * @param array $data
     */
    public function process_bbbext_bnx_reminderguest($data) {
        global $DB;

        $data = (object) $data;
        $data->bigbluebuttonbnid = $this->get_new_parentid('bigbluebuttonbn');
        $data->usermodified = $this->get_mappingid('user', $data->usermodified);
        $data->userfrom = $this->get_mappingid('user', $data->userfrom);
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);
        $newitemid = $DB->insert_record('bbbext_bnx_reminders_guests', $data);
        $this->set_mapping('bbbext_bnx_reminders_guests', $data->id, $newitemid);
    }
}
