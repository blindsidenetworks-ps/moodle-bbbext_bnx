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
 * Backup support for the BN Experience subplugin.
 *
 * @package   bbbext_bnx
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */
class backup_bbbext_bnx_subplugin extends backup_subplugin {
    /**
     * Append the BN Experience data to the activity structure.
     *
     * @return backup_subplugin_element
     */
    protected function define_bigbluebuttonbn_subplugin_structure() {
        $subplugin = $this->get_subplugin_element();
        $wrapper = new backup_nested_element($this->get_recommended_name());

        $bnxelement = new backup_nested_element('bbbext_bnx', ['id'], [
            'timecreated',
            'timemodified',
        ]);

        $settings = new backup_nested_element('bbbext_bnx_settings', ['id'], [
            'name',
            'value',
            'timemodified',
        ]);

        $subplugin->add_child($wrapper);
        $wrapper->add_child($bnxelement);
        $bnxelement->add_child($settings);

        $bnxelement->set_source_table('bbbext_bnx', [
            'bigbluebuttonbnid' => backup::VAR_PARENTID,
        ]);

        $settings->set_source_table('bbbext_bnx_settings', [
            'bnxid' => backup::VAR_PARENTID,
        ]);

        // Reminder timespan table.
        $remindersrem = new backup_nested_element(
            'bbbext_bnx_reminders',
            null,
            ['timespan', 'lastsent']
        );
        $remindersguests = new backup_nested_element(
            'bbbext_bnx_reminders_guests',
            ['id'],
            [
                'bigbluebuttonbnid',
                'email',
                'userfrom',
                'issent',
                'isenabled',
                'usermodified',
                'timemodified',
                'timecreated',
            ]
        );

        $wrapper->add_child($remindersrem);
        $wrapper->add_child($remindersguests);

        $remindersrem->set_source_table(
            'bbbext_bnx_reminders',
            ['bigbluebuttonbnid' => backup::VAR_PARENTID]
        );

        // Only include guest data if user info is being backed up.
        $userinfo = $this->get_setting_value('userinfo');
        if ($userinfo) {
            $remindersguests->set_source_table(
                'bbbext_bnx_reminders_guests',
                ['bigbluebuttonbnid' => backup::VAR_PARENTID]
            );
        }

        return $subplugin;
    }
}
