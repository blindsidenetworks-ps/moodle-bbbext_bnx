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
 * Privacy Subsystem for bbbext_bnx.
 *
 * @package   bbbext_bnx
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace bbbext_bnx\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\writer;

/**
 * Privacy Subsystem for bbbext_bnx implementing metadata and user preference providers.
 *
 * @package   bbbext_bnx
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\user_preference_provider {
    /**
     * Provides metadata about the personal data stored.
     *
     * @param collection $collection The metadata collection to update.
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_user_preference(
            'bbbext_bnx_reminder',
            'privacy:metadata:preference:bbbext_bnx_reminder'
        );
        return $collection;
    }

    /**
     * Export the user preferences for reminders.
     *
     * @param int $userid The user ID to export data for.
     * @return void
     */
    public static function export_user_preferences(int $userid): void {
        global $DB;

        $preferences = $DB->get_records_sql(
            "SELECT * FROM {user_preferences} WHERE userid = ? AND name LIKE ?",
            [$userid, 'bbbext_bnx_reminder_%']
        );

        foreach ($preferences as $pref) {
            // Extract the activity ID from the preference name.
            $activityid = str_replace('bbbext_bnx_reminder_', '', $pref->name);
            $preference = (int) $pref->value
                ? 'privacy:reminderpreferenceyes'
                : 'privacy:reminderpreferenceno';
            $description = get_string($preference, 'bbbext_bnx', ['activityid' => $activityid]);

            writer::export_user_preference(
                'bbbext_bnx',
                $pref->name,
                $pref->value,
                $description
            );
        }
    }
}
