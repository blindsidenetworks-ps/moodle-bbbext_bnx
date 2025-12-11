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

use advanced_testcase;
use bbbext_bnx\local\services\bnx_service;

/**
 * Unit tests for BNX service.
 *
 * @package   bbbext_bnx
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Shamiso Jaravaza  (shamiso [dt] jaravaza [at] blindsidenetworks [dt] com)
 */
final class bnx_service_test extends advanced_testcase {
    /** @var bnx_service */
    private $service;

    /** @var int */
    private $bnxid;

    /** @var int */
    private $moduleid;

    /**
     * Setup test case.
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);

        $this->service = bnx_service::get_service();
        $this->bnxid = $this->create_bnx_record();
    }

    /**
     * Test delete_bnx removes record.
     * @covers \bbbext_bnx\local\services\bnx_service::delete_bnx
     */
    public function test_delete_bnx(): void {
        global $DB;
        $this->assertTrue($DB->record_exists('bbbext_bnx', ['bigbluebuttonbnid' => $this->moduleid]));
        $this->assertTrue($this->service->delete_bnx($this->moduleid));
        $this->assertFalse($DB->record_exists('bbbext_bnx', ['bigbluebuttonbnid' => $this->moduleid]));
    }

    /**
     * Create a bnx base record linked to a generated BigBlueButton activity.
     *
     * @return int bnx record id
     */
    private function create_bnx_record(): int {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module('bigbluebuttonbn', ['course' => $course->id]);
        $this->moduleid = (int)$module->id;

        $record = $DB->get_record('bbbext_bnx', ['bigbluebuttonbnid' => $module->id]);
        if ($record) {
            return (int)$record->id;
        }

        $now = time();
        $bnxid = $DB->insert_record('bbbext_bnx', (object) [
            'bigbluebuttonbnid' => $module->id,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);

        return (int)$bnxid;
    }
}
