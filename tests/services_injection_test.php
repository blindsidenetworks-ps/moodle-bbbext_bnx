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
 * Unit tests for services injection via set_service()/get_service().
 *
 * @package   bbbext_bnx
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

namespace bbbext_bnx;

use advanced_testcase;
use bbbext_bnx\local\services\bnx_settings_service;
use bbbext_bnx\local\services\bnx_settings_service_interface;

/**
 * Tests for services injection via set_service()/get_service().
 *
 * @package   bbbext_bnx
 */
final class services_injection_test extends advanced_testcase {
    /**
     * Setup test case.
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();
        // Ensure default singletons are cleared for test isolation.
        bnx_settings_service::set_service(null);
    }

    /**
     * Test bnx_settings_service set_service() and get_service().
     *
     * @covers \bbbext_bnx\local\services\bnx_settings_service::set_service
     * @covers \bbbext_bnx\local\services\bnx_settings_service::get_service
     * @return void
     */
    public function test_settings_service_set_and_get_service(): void {
        $this->resetAfterTest(true);

        $mock = new class implements bnx_settings_service_interface {
            /** @var array<int, array> */
            private array $persisted = [];

            /**
             * Return mocked settings payload for the supplied BNX id.
             *
             * @param int $bnxid BNX identifier
             * @return array mocked settings collection
             */
            public function get_settings(int $bnxid): array {
                return $this->persisted[$bnxid] ?? ['mocked' => (string)$bnxid];
            }

            /**
             * Return a single mocked setting value.
             *
             * @param int $bnxid BNX identifier
             * @param string $name Setting name
             * @return string|null mocked setting value
             */
            public function get_setting(int $bnxid, string $name): ?string {
                return $this->persisted[$bnxid][$name] ?? null;
            }

            /**
             * Return a mocked setting value when addressed by module id.
             *
             * @param int $moduleid Module identifier
             * @param string $name Setting name
             * @return string|null mocked setting value
             */
            public function get_setting_for_module(int $moduleid, string $name): ?string {
                return $this->persisted[$moduleid][$name] ?? null;
            }

            /**
             * Record provided settings; no-op for mock implementation.
             *
             * @param int $bnxid BNX identifier
             * @param array $values Settings to persist
             * @return void
             */
            public function set_settings(int $bnxid, array $values): void {
                $this->persisted[$bnxid] = $values;
            }

            /**
             * Delete all settings for the mocked BNX id.
             *
             * @param int $bnxid BNX identifier
             * @return void
             */
            public function delete_settings(int $bnxid): void {
                unset($this->persisted[$bnxid]);
            }

            /**
             * Delete a single mocked setting entry.
             *
             * @param int $bnxid BNX identifier
             * @param string $name Setting name
             * @return void
             */
            public function delete_setting(int $bnxid, string $name): void {
                unset($this->persisted[$bnxid][$name]);
            }
        };

        bnx_settings_service::set_service($mock);
        $svc = bnx_settings_service::get_service();
        $this->assertInstanceOf(bnx_settings_service_interface::class, $svc);
        $this->assertSame(['mocked' => '123'], $svc->get_settings(123));

        // Clean up.
        bnx_settings_service::set_service(null);
    }
}
