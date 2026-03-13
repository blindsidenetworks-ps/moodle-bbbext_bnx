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

use core\plugininfo\mod;

/**
 * Conformance tests for BigBlueButtonBN auto-enable behaviour in BNX.
 *
 * @package   bbbext_bnx
 * @copyright 2026 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */
final class module_enablement_test extends \advanced_testcase {
    /**
     * BNX install must ensure the BigBlueButtonBN module is enabled.
     *
     * @covers ::xmldb_bbbext_bnx_install
     * @return void
     */
    public function test_install_enables_bigbluebuttonbn_module(): void {
        global $CFG;

        $this->resetAfterTest(true);
        $this->skip_if_missing_bigbluebutton_module();

        mod::enable_plugin('bigbluebuttonbn', 0);
        $this->assert_bigbluebuttonbn_enabled(false);

        require_once($CFG->dirroot . '/mod/bigbluebuttonbn/extension/bnx/db/install.php');
        xmldb_bbbext_bnx_install();

        $this->assert_bigbluebuttonbn_enabled(true);
    }

    /**
     * Enabling BNX must ensure the BigBlueButtonBN module is enabled.
     *
     * @covers \bbbext_bnx\observer::config_log_created
     * @return void
     */
    public function test_enabling_bnx_enables_bigbluebuttonbn_module(): void {
        $this->resetAfterTest(true);
        $this->skip_if_missing_bigbluebutton_module();

        mod::enable_plugin('bigbluebuttonbn', 0);
        $this->assert_bigbluebuttonbn_enabled(false);

        $event = \core\event\config_log_created::create([
            'context' => \context_system::instance(),
            'other' => [
                'name' => 'disabled',
                'plugin' => 'bbbext_bnx',
                'oldvalue' => '1',
                'value' => '0',
            ],
        ]);
        observer::config_log_created($event);

        $this->assert_bigbluebuttonbn_enabled(true);
    }

    /**
     * BNX upgrade must backfill BigBlueButtonBN enablement for already-installed sites.
     *
     * @covers ::xmldb_bbbext_bnx_upgrade
     * @return void
     */
    public function test_upgrade_enables_bigbluebuttonbn_module(): void {
        global $CFG;

        $this->resetAfterTest(true);
        $this->skip_if_missing_bigbluebutton_module();

        mod::enable_plugin('bigbluebuttonbn', 0);
        $this->assert_bigbluebuttonbn_enabled(false);

        set_config('version', 2026031300, 'bbbext_bnx');

        require_once($CFG->libdir . '/upgradelib.php');
        require_once($CFG->dirroot . '/mod/bigbluebuttonbn/extension/bnx/db/upgrade.php');
        xmldb_bbbext_bnx_upgrade(2026031300);

        $this->assert_bigbluebuttonbn_enabled(true);
    }

    /**
     * Assert BigBlueButtonBN module visibility state.
     *
     * @param bool $expectedenabled
     * @return void
     */
    private function assert_bigbluebuttonbn_enabled(bool $expectedenabled): void {
        global $DB;

        $module = $DB->get_record('modules', ['name' => 'bigbluebuttonbn'], 'id, visible', MUST_EXIST);
        $this->assertSame((int)$expectedenabled, (int)$module->visible);
    }

    /**
     * Skip test if BigBlueButtonBN module is not installed in current environment.
     *
     * @return void
     */
    private function skip_if_missing_bigbluebutton_module(): void {
        global $DB;

        if (!$DB->record_exists('modules', ['name' => 'bigbluebuttonbn'])) {
            $this->markTestSkipped('Missing required mod_bigbluebuttonbn module.');
        }
    }
}
