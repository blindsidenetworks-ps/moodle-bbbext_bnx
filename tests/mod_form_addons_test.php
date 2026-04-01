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
 * Test definitions for the bnx mod form addons.
 *
 * @package   bbbext_bnx
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

namespace bbbext_bnx;

use bbbext_bnx\bigbluebuttonbn\mod_form_addons;
use bbbext_bnx\local\services\bnx_settings_service;

/**
 * Tests for the BNX mod_form_addons hooks.
 *
 * @package   bbbext_bnx
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 * @covers    \bbbext_bnx\bigbluebuttonbn\mod_form_addons
 */
final class mod_form_addons_test extends \advanced_testcase {
    /**
     * Setup test case.
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    /**
     * Test data_preprocessing populates defaults from settings.
     *
     * @return void
     */
    public function test_data_preprocessing_populates_defaults_from_settings(): void {
        global $CFG;

        require_once($CFG->libdir . '/formslib.php');
        $form = new \MoodleQuickForm('bnxform', 'post', '');
        $addons = new mod_form_addons($form);

        $module = $this->create_bigbluebutton_activity();
        $bnxid = $this->ensure_bnx_record($module->id);

        $service = bnx_settings_service::get_service();
        $service->set_settings($bnxid, ['approvalbeforejoin' => 1]);

        $defaults = ['id' => $module->id];
        $addons->data_preprocessing($defaults);

        $this->assertSame(1, $defaults['approvalbeforejoin']);
    }

    /**
     * Test data_preprocessing ignores missing BNX record.
     *
     * @return void
     */
    public function test_data_preprocessing_ignores_missing_bnx_record(): void {
        global $CFG;

        require_once($CFG->libdir . '/formslib.php');
        $form = new \MoodleQuickForm('bnxform', 'post', '');
        $addons = new mod_form_addons($form);

        $defaults = ['id' => 9999, 'preset' => 123];
        $addons->data_preprocessing($defaults);

        $this->assertSame(['id' => 9999, 'preset' => 123], $defaults);
    }

    /**
     * Test other hooks remain no-ops.
     *
     * @return void
     */
    public function test_other_hooks_remain_noops(): void {
        global $CFG;

        require_once($CFG->libdir . '/formslib.php');
        $form = new \MoodleQuickForm('bnxform', 'post', '');
        $addons = new mod_form_addons($form);

        $data = (object) ['existing' => 'value'];
        $addons->data_postprocessing($data);
        $this->assertSame('value', $data->existing);

        $this->assertSame([], $addons->add_completion_rules());
        $this->assertSame([], $addons->validation([], []));

        $addons->add_fields();
    }

    /**
     * Test validation rejects duplicate reminder timespans.
     *
     * @return void
     */
    public function test_validation_rejects_duplicate_reminder_timespans(): void {
        global $CFG;

        require_once($CFG->libdir . '/formslib.php');
        $form = new \MoodleQuickForm('bnxform', 'post', '');
        $addons = new mod_form_addons($form);

        $data = [
            'bnx_timespan' => ['PT1H', 'PT1H'],
        ];

        $errors = $addons->validation($data, []);
        $this->assertArrayHasKey('bnx_addparamgroup', $errors);
    }

    /**
     * Test reminder fields are still added when approval-before-join is not editable.
     *
     * @return void
     */
    public function test_add_fields_adds_reminders_even_if_approvalbeforejoin_not_editable(): void {
        global $CFG;

        require_once($CFG->libdir . '/formslib.php');

        set_config('approvalbeforejoin_editable', 0, 'bbbext_bnx');
        set_config('reminder_editable', 1, 'bbbext_bnx');
        set_config('reminder_default', 1, 'bbbext_bnx');

        $form = new \MoodleQuickForm('bnxform', 'post', '');
        $addons = new mod_form_addons($form);

        $addons->add_fields();

        $this->assertTrue($form->elementExists('bnx_reminders'));
    }

    /**
     * Helper to create a BigBlueButton activity for tests.
     *
     * @return \stdClass
     */
    private function create_bigbluebutton_activity(): \stdClass {
        $course = $this->getDataGenerator()->create_course();
        return $this->getDataGenerator()->create_module('bigbluebuttonbn', ['course' => $course->id]);
    }

    /**
     * Ensure bnx base record exists for module id, returning id.
     *
     * @param int $moduleid module identifier
     * @return int
     */
    private function ensure_bnx_record(int $moduleid): int {
        global $DB;

        $record = $DB->get_record('bbbext_bnx', ['bigbluebuttonbnid' => $moduleid]);
        if ($record) {
            return (int)$record->id;
        }

        $now = time();
        return (int)$DB->insert_record('bbbext_bnx', (object) [
            'bigbluebuttonbnid' => $moduleid,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
    }
}
