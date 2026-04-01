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
use pix_icon;

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
     * Apply header label overrides using language string keys.
     *
     * @param \MoodleQuickForm $mform The form instance
     * @param array $headerstringoverrides header name => lang string key
     * @param string $component Language component for string lookup
     * @return void
     */
    public static function apply_header_overrides(
        \MoodleQuickForm &$mform,
        array $headerstringoverrides,
        string $component = 'bbbext_bnx'
    ): void {
        foreach ($headerstringoverrides as $headername => $stringkey) {
            self::rename_header(
                $mform,
                $headername,
                get_string($stringkey, $component)
            );
        }
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
        foreach ($elements as $element) {
            $mform->insertElementBefore(
                $element,
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
        global $DB, $OUTPUT;

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
        $mform->disabledIf('bnx_remindertoguestsenabled', 'openingtime[enabled]', 'notchecked', 0);

        // Determine how many timespans to show.
        $existingtimespans = [];
        if (!empty($bigbluebuttonbndata->id)) {
            $existingtimespans = $DB->get_records(
                'bbbext_bnx_reminders',
                ['bigbluebuttonbnid' => $bigbluebuttonbndata->id]
            );
        }
        $defaultcount = max(1, count($existingtimespans));
        $paramcount = optional_param('bnx_paramcount', $defaultcount, PARAM_INT);
        if (optional_param('bnx_addparamgroup', 0, PARAM_RAW)) {
            $paramcount++;
        }

        $isdeleting = optional_param_array('bnx_paramdelete', [], PARAM_RAW);
        foreach (array_keys($isdeleting) as $index) {
            $mform->registerNoSubmitButton("bnx_paramdelete[$index]");
        }

        $mform->addElement('hidden', 'bnx_paramcount');
        $mform->setType('bnx_paramcount', PARAM_INT);
        $mform->setConstants(['bnx_paramcount' => $paramcount]);

        $mform->addElement('hidden', 'bnx_openingtime');
        $mform->setType('bnx_openingtime', PARAM_INT);
        $mform->setConstants(['bnx_openingtime' => $bigbluebuttonbndata->openingtime ?? 0]);

        $timespanoptions = reminders_utils::get_timespan_options();
        $timespanvalues = array_values(array_map(fn($r) => $r->timespan, $existingtimespans));
        $bellicon = new pix_icon('i/bell', get_string('timespan:bell', 'bbbext_bnx'), 'bbbext_bnx');

        for ($i = 0; $i < $paramcount; $i++) {
            $group = [];
            $group[] = $mform->createElement('html', $OUTPUT->render($bellicon));
            $group[] = $mform->createElement('select', "bnx_timespan[$i]", '', $timespanoptions);
            $group[] = $mform->createElement(
                'static',
                "bnx_timespanlabel[$i]",
                '',
                get_string('reminder:message', 'bbbext_bnx')
            );
            $group[] = $mform->createElement(
                'submit',
                "bnx_paramdelete[$i]",
                get_string('delete'),
                [],
                false,
                ['customclassoverride' => 'btn btn-secondary float-left']
            );

            $mform->addGroup($group, "bnx_timespangroup[$i]", '', [' '], false);
            $mform->hideIf("bnx_timespangroup[$i]", 'bnx_reminderenabled', 'notchecked', 0);
            $mform->disabledIf("bnx_timespangroup[$i]", 'openingtime[enabled]', 'notchecked', 0);
            $mform->setType("bnx_timespan[$i]", PARAM_ALPHANUM);
            $mform->setType("bnx_paramdelete[$i]", PARAM_RAW);
            $mform->disabledIf("bnx_timespan[$i]", 'openingtime[enabled]', 'notchecked', 0);
            $mform->registerNoSubmitButton("bnx_paramdelete[$i]");

            if (isset($timespanvalues[$i])) {
                $mform->setDefault("bnx_timespan[$i]", $timespanvalues[$i]);
            }
        }

        $mform->addElement('submit', 'bnx_addparamgroup', get_string('addreminder', 'bbbext_bnx'));
        $mform->hideIf('bnx_addparamgroup', 'bnx_reminderenabled');
        $mform->disabledIf('bnx_addparamgroup', 'openingtime[enabled]', 'notchecked', 0);
        $mform->setType('bnx_addparamgroup', PARAM_TEXT);
        $mform->registerNoSubmitButton('bnx_addparamgroup');
    }

    /**
     * Apply post-data cleanup for reminder rows when a delete button is pressed.
     *
     * @param \MoodleQuickForm $mform The form instance
     * @return void
     */
    public static function reminder_definition_after_data(\MoodleQuickForm &$mform): void {
        $isdeleting = optional_param_array('bnx_paramdelete', [], PARAM_RAW);
        if (empty($isdeleting)) {
            return;
        }

        $firstindex = array_key_first($isdeleting);
        $paramcount = optional_param('bnx_paramcount', 0, PARAM_INT);
        for ($index = $firstindex; $index < $paramcount; $index++) {
            $nextindex = $index + 1;
            if ($mform->elementExists("bnx_timespan[$nextindex]")) {
                $mform->getElement("bnx_timespan[$index]")
                    ->setValue($mform->getElementValue("bnx_timespan[$nextindex]"));
            }
        }

        $newparamcount = $paramcount - 1;
        $mform->removeElement("bnx_timespangroup[$newparamcount]");
        if ($mform->elementExists('bnx_paramcount')) {
            $mform->getElement('bnx_paramcount')->setValue($newparamcount);
        }
    }
}
