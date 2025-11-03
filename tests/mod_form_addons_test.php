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

use bbbext_bnx\bigbluebuttonbn\mod_form_addons;

/**
 * Tests for the BNX mod_form_addons hooks.
 *
 * @package    bbbext_bnx
 * @covers     \bbbext_bnx\bigbluebuttonbn\mod_form_addons
 */
final class mod_form_addons_test extends \advanced_testcase {
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);
    }

    public function test_hooks_are_noops(): void {
        global $CFG;

        require_once($CFG->libdir . '/formslib.php');
        $form = new \MoodleQuickForm('bnxform', 'post', '');
        $addons = new mod_form_addons($form);

        $data = (object) ['existing' => 'value'];
        $addons->data_postprocessing($data);
        $this->assertSame('value', $data->existing);

        $defaults = ['preset' => 123];
        $addons->data_preprocessing($defaults);
        $this->assertSame(['preset' => 123], $defaults);

        $this->assertSame([], $addons->add_completion_rules());
        $this->assertSame([], $addons->validation([], []));

        // Should not throw.
        $addons->add_fields();
    }
}
