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

use restore_date_testcase;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir . "/phpunit/classes/restore_date_testcase.php");

/**
 * Tests for BigBlueButton BN Experience
 *
 * @package    bbbext_bnx
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Shamiso Jaravaza (shamiso [dt] jaravaza [at] blindsidenetworks [dt] com)
 */
final class backup_restore_test extends \restore_date_testcase {
    /** @var int */
    private $course;

    /** @var int */
    private $bbbactivity;

    /** @var object */
    private $bnxdata;

    /** @var object */
    private $bnxsettings;

    /**
     * Test backup restore
     *
     * @covers \bbbext_bnx\backup_bbbext_bnx_subplugin::define_bigbluebuttonbn_subplugin_structure
     * @covers \bbbext_bnx\restore_bbbext_bnx_subplugin::define_bigbluebuttonbn_subplugin_structure
     */
    public function test_backup_restore(): void {
        global $DB;
        $this->resetAfterTest();

        // Create a BigBlueButton activity.
        $this->bbbactivity = $this->create_bigbluebutton_activity();
        // Create sample BN Experience data.
        $this->create_bnx_data();

        $this->setAdminUser();

        // Backup and restore the course.
        $newcourseid = $this->backup_and_restore($this->course);

        // Verify that the BN Experience data was restored correctly.
        $restoredactivity = $DB->get_record('bigbluebuttonbn', ['course' => $newcourseid], '*', MUST_EXIST);
        $restoredbnx = $DB->get_record(
            'bbbext_bnx',
            ['bigbluebuttonbnid' => $restoredactivity->id],
            '*',
            MUST_EXIST
        );
        $restoredbnxsettings = $DB->get_record(
            'bbbext_bnx_settings',
            ['bnxid' => $restoredbnx->id],
            '*',
            MUST_EXIST
        );
        $this->assertEquals($restoredbnx->timecreated, $this->bnxdata->timecreated);
        $this->assertEquals($restoredbnx->timemodified, $this->bnxdata->timemodified);

        $this->assertEquals($restoredbnxsettings->name, $this->bnxsettings->name);
        $this->assertEquals($restoredbnxsettings->value, $this->bnxsettings->value);
    }

    /**
     * Create sample BN Experience data.
     */
    private function create_bnx_data() {
        global $DB;

        // Create BN Experience record.
        $this->bnxdata = (object) [
            'bigbluebuttonbnid' => $this->bbbactivity->id,
            'timecreated' => time(),
            'timemodified' => time(),
        ];
        // Check if record already exists.
        $record = $DB->get_record('bbbext_bnx', ['bigbluebuttonbnid' => $this->bbbactivity->id]);
        if ($record) {
            $this->bnxdata->id = $record->id;
            $DB->update_record('bbbext_bnx', $this->bnxdata);
        } else {
            $this->bnxdata->id = $DB->insert_record('bbbext_bnx', $this->bnxdata);
        }

        // Create BN Experience settings.
        $this->bnxsettings = (object) [
            'bnxid' => $this->bnxdata->id,
            'name' => 'feature_flag',
            'value' => '1',
            'timecreated' => time(),
            'timemodified' => time(),
        ];
        $DB->insert_record('bbbext_bnx_settings', $this->bnxsettings);
    }

    /**
     * Helper to create a BigBlueButton activity for tests.
     *
     * @return \stdClass
     */
    private function create_bigbluebutton_activity(): \stdClass {
        $this->course = $this->getDataGenerator()->create_course();
        return $this->getDataGenerator()->create_module('bigbluebuttonbn', ['course' => $this->course->id]);
    }
}
