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

/**
 * Tests for reminders_utils.
 *
 * @package   bbbext_bnx
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \bbbext_bnx\reminders_utils
 */
final class reminders_utils_test extends \advanced_testcase {
    /**
     * Test timespan options are returned correctly.
     *
     * @return void
     * @covers ::get_timespan_options
     */
    public function test_get_timespan_options(): void {
        $result = reminders_utils::get_timespan_options();
        $this->assertEquals([
            reminders_utils::ONE_HOUR => get_string('timespan:pt1h', 'bbbext_bnx'),
            reminders_utils::TWO_HOURS => get_string('timespan:pt2h', 'bbbext_bnx'),
            reminders_utils::ONE_DAY => get_string('timespan:p1d', 'bbbext_bnx'),
            reminders_utils::TWO_DAYS => get_string('timespan:p2d', 'bbbext_bnx'),
            reminders_utils::ONE_WEEK => get_string('timespan:p1w', 'bbbext_bnx'),
        ], $result);
    }

    /**
     * Test replace_vars_in_text replaces placeholders.
     *
     * @return void
     * @covers ::replace_vars_in_text
     */
    public function test_replace_vars_in_text(): void {
        $text = 'Hello {$name}, your meeting is at {$date}.';
        $vars = ['name' => 'Alice', 'date' => '2025-01-01'];
        $result = reminders_utils::replace_vars_in_text($vars, $text);
        $this->assertEquals('Hello Alice, your meeting is at 2025-01-01.', $result);
    }
}
