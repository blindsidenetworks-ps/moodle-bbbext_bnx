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
 * Upgrade steps for bbbext_bnx.
 *
 * @package   bbbext_bnx
 * @copyright 2026 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

/**
 * Execute the upgrade steps from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_bbbext_bnx_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2026031101) {
        // Ensure BigBlueButtonBN module is enabled for already-installed BNX sites.
        if ($DB->record_exists('modules', ['name' => 'bigbluebuttonbn'])) {
            \core\plugininfo\mod::enable_plugin('bigbluebuttonbn', 1);
        }

        $plugins = \core_plugin_manager::instance()->get_installed_plugins('bbbext');
        if ($plugins) {
            foreach ($plugins as $name => $version) {
                $component = 'bbbext_' . $name;
                $disabled = get_config($component, 'disabled');
                if (!empty($disabled)) {
                    continue;
                }
                $callbackclass = '\\' . $component . '\\plugininfo_callbacks';
                if (class_exists($callbackclass) && method_exists($callbackclass, 'on_enable')) {
                    $callbackclass::on_enable();
                }
            }
        }

        upgrade_plugin_savepoint(true, 2026031101, 'bbbext', 'bnx');
    }

    if ($oldversion < 2026040100) {
        // Create bbbext_bnx_reminders table (stores individual reminder timespans).
        $table = new xmldb_table('bbbext_bnx_reminders');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('bigbluebuttonbnid', XMLDB_TYPE_INTEGER, '10');
        $table->add_field('timespan', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
        $table->add_field('lastsent', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('fk_bigbluebuttonbnid', XMLDB_KEY_FOREIGN, ['bigbluebuttonbnid'], 'bigbluebuttonbn', ['id']);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Create bbbext_bnx_reminders_guests table.
        $table = new xmldb_table('bbbext_bnx_reminders_guests');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('bigbluebuttonbnid', XMLDB_TYPE_INTEGER, '10');
        $table->add_field('email', XMLDB_TYPE_CHAR, '254');
        $table->add_field('userfrom', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('issent', XMLDB_TYPE_INTEGER, '1', null, null, null, '0');
        $table->add_field('isenabled', XMLDB_TYPE_INTEGER, '1', null, null, null, '1');
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('bigbluebuttonbnid_fk', XMLDB_KEY_FOREIGN, ['bigbluebuttonbnid'], 'bigbluebuttonbn', ['id']);
        $table->add_key('usermodified', XMLDB_KEY_FOREIGN, ['usermodified'], 'user', ['id']);
        $table->add_key('userfrom', XMLDB_KEY_FOREIGN, ['userfrom'], 'user', ['id']);
        $table->add_key('bbbemail_ux', XMLDB_KEY_UNIQUE, ['email', 'bigbluebuttonbnid', 'userfrom']);
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Migrate data from bbbext_bnreminders if the old tables exist.
        // Reminder settings (reminderenabled, remindertoguestsenabled) go into bbbext_bnx_settings.
        if ($dbman->table_exists(new xmldb_table('bbbext_bnreminders'))) {
            $records = $DB->get_records('bbbext_bnreminders');
            foreach ($records as $record) {
                $bnxrecord = $DB->get_record('bbbext_bnx', [
                    'bigbluebuttonbnid' => $record->bigbluebuttonbnid,
                ]);
                if (!$bnxrecord) {
                    continue;
                }
                $now = time();
                foreach (['reminderenabled', 'remindertoguestsenabled'] as $settingname) {
                    if (
                        !$DB->record_exists('bbbext_bnx_settings', [
                            'bnxid' => $bnxrecord->id,
                            'name' => $settingname,
                        ])
                    ) {
                        $DB->insert_record('bbbext_bnx_settings', (object) [
                            'bnxid' => $bnxrecord->id,
                            'name' => $settingname,
                            'value' => (string)$record->{$settingname},
                            'timecreated' => $now,
                            'timemodified' => $now,
                        ]);
                    }
                }
            }
        }

        if ($dbman->table_exists(new xmldb_table('bbbext_bnreminders_rem'))) {
            $records = $DB->get_records('bbbext_bnreminders_rem');
            foreach ($records as $record) {
                unset($record->id);
                if (
                    !$DB->record_exists('bbbext_bnx_reminders', [
                        'bigbluebuttonbnid' => $record->bigbluebuttonbnid,
                        'timespan' => $record->timespan,
                    ])
                ) {
                    $DB->insert_record('bbbext_bnx_reminders', $record);
                }
            }
        }

        if ($dbman->table_exists(new xmldb_table('bbbext_bnreminders_guests'))) {
            $records = $DB->get_records('bbbext_bnreminders_guests');
            foreach ($records as $record) {
                unset($record->id);
                if (
                    !$DB->record_exists('bbbext_bnx_reminders_guests', [
                        'email' => $record->email,
                        'bigbluebuttonbnid' => $record->bigbluebuttonbnid,
                        'userfrom' => $record->userfrom,
                    ])
                ) {
                    $DB->insert_record('bbbext_bnx_reminders_guests', $record);
                }
            }
        }

        // Migrate admin config settings.
        $oldconfigs = ['emailsubject', 'emailtemplate', 'emailfooter', 'emailcontent'];
        foreach ($oldconfigs as $configname) {
            $oldvalue = get_config('bbbext_bnreminders', $configname);
            if ($oldvalue !== false) {
                set_config($configname, $oldvalue, 'bbbext_bnx');
            }
        }

        // Migrate user preferences.
        $oldprefs = $DB->get_records_sql(
            "SELECT * FROM {user_preferences} WHERE name LIKE ?",
            ['bbbext_bnreminders_%']
        );
        foreach ($oldprefs as $pref) {
            $newname = str_replace('bbbext_bnreminders_', 'bbbext_bnx_reminder_', $pref->name);
            if (!$DB->record_exists('user_preferences', ['userid' => $pref->userid, 'name' => $newname])) {
                $DB->insert_record('user_preferences', (object) [
                    'userid' => $pref->userid,
                    'name' => $newname,
                    'value' => $pref->value,
                ]);
            }
        }

        // If bnreminders is installed, disable it after migration.
        $bnremindersplugin = \core_plugin_manager::instance()->get_plugin_info('bbbext_bnreminders');
        if ($bnremindersplugin) {
            $oldvalue = get_config('bbbext_bnreminders', 'disabled');
            if (empty($oldvalue)) {
                set_config('disabled', 1, 'bbbext_bnreminders');
                add_to_config_log('disabled', $oldvalue, 1, 'bbbext_bnreminders');
                \core_plugin_manager::reset_caches();
            }
        }

        upgrade_plugin_savepoint(true, 2026040100, 'bbbext', 'bnx');
    }

    return true;
}
