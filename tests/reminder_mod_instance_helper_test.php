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

namespace bbbext_bnx\bigbluebuttonbn;

use core_date;
use DateInterval;
use DateTime;
use mod_bigbluebuttonbn\instance;
use ReflectionClass;

/**
 * Tests for reminder functionality in mod_instance_helper.
 *
 * @package   bbbext_bnx
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \bbbext_bnx\bigbluebuttonbn\mod_instance_helper
 */
final class reminder_mod_instance_helper_test extends \advanced_testcase {
    /**
     * Test sync of reminder data (create/update/delete timespans).
     *
     * @return void
     * @covers ::add_instance
     * @covers ::update_instance
     */
    public function test_sync_reminder_data(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $bbbgenerator = $generator->get_plugin_generator('mod_bigbluebuttonbn');
        $bbbinstance = instance::get_from_instanceid(
            $bbbgenerator->create_instance(['course' => $course])->id
        );

        $bnxgenerator = $generator->get_plugin_generator('bbbext_bnx');
        $bnxgenerator->enable_reminder($bbbinstance->get_instance_id());
        $bnxgenerator->add_reminder([
            'bigbluebuttonbnid' => $bbbinstance->get_instance_id(),
            'timespan' => 'PT1H',
        ]);
        $bnxgenerator->add_reminder([
            'bigbluebuttonbnid' => $bbbinstance->get_instance_id(),
            'timespan' => 'PT2H',
            'lastsent' => time(),
        ]);

        $modinstancehelper = new mod_instance_helper();
        $modinstancehelperref = new ReflectionClass($modinstancehelper);
        $syncmethod = $modinstancehelperref->getMethod('sync_reminder_data');
        $syncmethod->setAccessible(true);

        // Simulate form sent with 3 timespans.
        $data = $bbbinstance->get_instance_data();
        $data->bnx_reminderenabled = true;
        $data->bnx_remindertoguestsenabled = true;
        $data->bnx_paramcount = 3;
        $data->bnx_timespan = ['PT1H', 'PT2H', 'P1D'];
        $syncmethod->invokeArgs($modinstancehelper, [$data]);

        $existingreminders = $DB->get_records(
            mod_instance_helper::REMINDERS_TABLE,
            ['bigbluebuttonbnid' => $bbbinstance->get_instance_id()]
        );
        $this->assertCount(3, $existingreminders);
        $timespans = array_values(
            array_map(fn($r) => $r->timespan, $existingreminders)
        );
        sort($timespans);
        $this->assertEquals(['P1D', 'PT1H', 'PT2H'], $timespans);

        // Now update: remove PT2H, add P2D.
        $data->bnx_paramcount = 2;
        $data->bnx_timespan = ['PT1H', 'P2D'];
        $syncmethod->invokeArgs($modinstancehelper, [$data]);

        $existingreminders = $DB->get_records(
            mod_instance_helper::REMINDERS_TABLE,
            ['bigbluebuttonbnid' => $bbbinstance->get_instance_id()]
        );
        $this->assertCount(2, $existingreminders);
        $timespans = array_values(array_map(fn($r) => $r->timespan, $existingreminders));
        sort($timespans);
        $this->assertEquals(['P2D', 'PT1H'], $timespans);
    }

    /**
     * Test that reminder data is not processed when plugin is disabled.
     *
     * @return void
     * @covers ::add_instance
     */
    public function test_sync_reminder_data_disabled(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        // Disable the plugin.
        set_config('disabled', 1, 'bbbext_bnx');
        \core_plugin_manager::reset_caches();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $bbbgenerator = $generator->get_plugin_generator('mod_bigbluebuttonbn');
        $bbbinstance = instance::get_from_instanceid(
            $bbbgenerator->create_instance(['course' => $course])->id
        );

        $modinstancehelper = new mod_instance_helper();
        $modinstancehelperref = new ReflectionClass($modinstancehelper);
        $syncmethod = $modinstancehelperref->getMethod('sync_reminder_data');
        $syncmethod->setAccessible(true);

        $data = $bbbinstance->get_instance_data();
        $data->bnx_reminderenabled = true;
        $data->bnx_paramcount = 1;
        $data->bnx_timespan = ['PT1H'];
        $syncmethod->invokeArgs($modinstancehelper, [$data]);

        // No reminder timespan records should have been created.
        $count = $DB->count_records(
            mod_instance_helper::REMINDERS_TABLE,
            ['bigbluebuttonbnid' => $bbbinstance->get_instance_id()]
        );
        $this->assertEquals(0, $count);

        // Re-enable for cleanup.
        unset_config('disabled', 'bbbext_bnx');
        \core_plugin_manager::reset_caches();
    }

    /**
     * Test existing reminders reset lastsent when opening time changes.
     *
     * @return void
     * @covers ::update_instance
     */
    public function test_sync_reminder_data_resets_lastsent_on_openingtime_change(): void {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $bbbgenerator = $generator->get_plugin_generator('mod_bigbluebuttonbn');
        $bbbinstance = instance::get_from_instanceid(
            $bbbgenerator->create_instance(['course' => $course])->id
        );

        $bnxgenerator = $generator->get_plugin_generator('bbbext_bnx');
        $bnxgenerator->enable_reminder($bbbinstance->get_instance_id());
        $bnxgenerator->add_reminder([
            'bigbluebuttonbnid' => $bbbinstance->get_instance_id(),
            'timespan' => 'PT1H',
            'lastsent' => time(),
        ]);
        $bnxgenerator->add_reminder([
            'bigbluebuttonbnid' => $bbbinstance->get_instance_id(),
            'timespan' => 'PT2H',
            'lastsent' => time(),
        ]);

        $modinstancehelper = new mod_instance_helper();
        $modinstancehelperref = new ReflectionClass($modinstancehelper);
        $syncmethod = $modinstancehelperref->getMethod('sync_reminder_data');
        $syncmethod->setAccessible(true);

        $data = $bbbinstance->get_instance_data();
        $data->bnx_reminderenabled = true;
        $data->bnx_paramcount = 2;
        $data->bnx_timespan = ['PT1H', 'PT2H'];
        $data->bnx_openingtime = (int)$data->openingtime;
        $data->openingtime = (int)$data->openingtime + 60;
        $syncmethod->invokeArgs($modinstancehelper, [$data]);

        $records = $DB->get_records(
            mod_instance_helper::REMINDERS_TABLE,
            ['bigbluebuttonbnid' => $bbbinstance->get_instance_id()]
        );

        $this->assertCount(2, $records);
        foreach ($records as $record) {
            $this->assertEquals(0, (int)$record->lastsent);
        }
    }
}
