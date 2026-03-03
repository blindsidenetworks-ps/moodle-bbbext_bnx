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

namespace bbbext_bnx\local\helpers;

use bbbext_bnx\reminders_utils;

/**
 * Helper class for mod_form functionality.
 *
 * @package   bbbext_bnx
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Shamiso Jaravaza (shamiso [dt] jaravaza [at] blindsidenetworks [dt] com)
 */
class mod_form_helper {
    /**
     * Add the approval before join form checkbox.
     *
     * @param \MoodleQuickForm $mform The form instance
     * @param int $featuredefault The default value for the feature
     * @return void
     */
    public static function add_approval_before_join_checkbox(
        \MoodleQuickForm &$mform,
        int $featuredefault
    ): void {
        $approvalcheckbox = $mform->createElement(
            'advcheckbox',
            'approvalbeforejoin',
            null,
            get_string('approvalbeforejoin', 'bbbext_bnx')
        );
        self::insert_elements_above($mform, 'wait', [$approvalcheckbox]);
        $mform->addHelpButton('approvalbeforejoin', 'approvalbeforejoin', 'bbbext_bnx');
        $mform->setDefault('approvalbeforejoin', $featuredefault);
        $mform->setType('approvalbeforejoin', PARAM_BOOL);
    }

    /**
     * Rename an existing header element.
     *
     * @param \MoodleQuickForm $mform The form instance
     * @param string $name The name of the header element.
     * @param string $newlabel The new label for the header.
     * @return void
     */
    public static function rename_header(\MoodleQuickForm &$mform, string $name, string $newlabel): void {
        if (!$mform->elementExists($name)) {
            return;
        }
        $header = $mform->getElement($name);
        $header->setText($newlabel);
    }

    /**
     * Add elements above existing form elements e.g headers.
     *
     * @param \MoodleQuickForm $mform The form instance
     * @param string $name The name of the existing element.
     * @param array $elements The elements to add.
     * @return void
     */
    public static function insert_elements_above(\MoodleQuickForm &$mform, string $name, array $elements): void {
        if (!$mform->elementExists($name)) {
            return;
        }
        for ($i = 0; $i < count($elements); $i++) {
            $mform->insertElementBefore(
                $elements[$i],
                $name
            );
        }
    }

    /**
     * Remove existing form elements.
     *
     * @param \MoodleQuickForm $mform The form instance
     * @param string $name The name of the element to remove.
     * @return void
     */
    public static function remove_element(\MoodleQuickForm &$mform, string $name): void {
        if ($mform->elementExists($name)) {
            $mform->removeElement($name);
        }
    }

    /**
     * Determine whether a feature can be edited in the activity form.
     *
     * @param string $feature Feature key
     * @return bool
     */
    public static function is_feature_editable(string $feature): bool {
        return (bool)get_config('bbbext_bnx', $feature . '_editable');
    }

    /**
     * Fetch the default value for a feature from the global configuration.
     *
     * @param string $feature Feature key
     * @return int
     */
    public static function get_feature_default(string $feature): int {
        $default = get_config('bbbext_bnx', $feature . '_default');

        return (int)!empty($default);
    }

    /**
     * Resolve the bnx identifier for a module.
     *
     * @param int $moduleid module identifier
     * @return int|null
     */
    public static function get_bnx_id(int $moduleid): ?int {
        global $DB;

        $record = $DB->get_record('bbbext_bnx', ['bigbluebuttonbnid' => $moduleid], 'id');
        return $record ? (int)$record->id : null;
    }

    /**
     * Add the reminder form fields to the activity form.
     *
     * @param \MoodleQuickForm $mform The form instance
     * @param \stdClass|null $bigbluebuttonbndata Existing module data
     * @return void
     */
    public static function add_reminder_fields(\MoodleQuickForm &$mform, ?\stdClass $bigbluebuttonbndata = null): void {
        global $DB;

        $mform->addElement('header', 'bnx_reminders', get_string('mod_form_reminders', 'bbbext_bnx'));
        $mform->addElement('static', 'bnx_reminders_desc', '', get_string('mod_form_reminders_desc', 'bbbext_bnx'));

        $mform->addElement(
            'advcheckbox',
            'bnx_reminderenabled',
            get_string('reminders:enabled', 'bbbext_bnx'),
        );
        $mform->addHelpButton('bnx_reminderenabled', 'reminders', 'bbbext_bnx');
        $mform->setDefault('bnx_reminderenabled', self::get_feature_default('reminder'));
        $mform->setType('bnx_reminderenabled', PARAM_BOOL);

        $mform->addElement(
            'advcheckbox',
            'bnx_remindertoguestsenabled',
            get_string('reminders:guestenabled', 'bbbext_bnx')
        );
        $mform->setDefault('bnx_remindertoguestsenabled', 0);
        $mform->setType('bnx_remindertoguestsenabled', PARAM_BOOL);
        $mform->hideIf('bnx_remindertoguestsenabled', 'bnx_reminderenabled', 'eq', 0);

        // Determine how many timespans to show.
        $existingtimespans = [];
        if (!empty($bigbluebuttonbndata->id)) {
            $existingtimespans = $DB->get_records(
                'bbbext_bnx_reminders',
                ['bigbluebuttonbnid' => $bigbluebuttonbndata->id]
            );
        }
        $paramcount = max(1, count($existingtimespans));

        $mform->addElement('hidden', 'bnx_paramcount', $paramcount);
        $mform->setType('bnx_paramcount', PARAM_INT);

        $timespanoptions = reminders_utils::get_timespan_options();
        $existingtimespanvalues = array_values(array_map(fn($r) => $r->timespan, $existingtimespans));

        for ($i = 0; $i < $paramcount; $i++) {
            $group = [];
            $group[] = $mform->createElement('select', "bnx_timespan[$i]", '', $timespanoptions);
            $group[] = $mform->createElement(
                'static',
                "bnx_timespanlabel[$i]",
                '',
                get_string('reminder:message', 'bbbext_bnx')
            );
            $mform->addGroup($group, "bnx_timespangroup[$i]", get_string('timespan:bell', 'bbbext_bnx'), ' ', false);
            $mform->hideIf("bnx_timespangroup[$i]", 'bnx_reminderenabled', 'eq', 0);

            if (isset($existingtimespanvalues[$i])) {
                $mform->setDefault("bnx_timespan[$i]", $existingtimespanvalues[$i]);
            }
        }
    }
}
