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

namespace bbbext_bnx\form;

use moodleform;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Unsubscribe confirmation form.
 *
 * @package   bbbext_bnx
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class unsubscribe extends moodleform {
    /**
     * Define the form.
     *
     * @return void
     */
    protected function definition() {
        $mform = $this->_form;
        $customdata = $this->_customdata;

        $mform->addElement(
            'static',
            'message',
            '',
            get_string('unsubscribe:label', 'bbbext_bnx')
        );

        $mform->addElement('hidden', 'cmid', $customdata['cmid']);
        $mform->setType('cmid', PARAM_INT);

        if (!empty($customdata['email'])) {
            $mform->addElement('hidden', 'email', $customdata['email']);
            $mform->setType('email', PARAM_EMAIL);
        }

        if (!empty($customdata['userid'])) {
            $mform->addElement('hidden', 'userid', $customdata['userid']);
            $mform->setType('userid', PARAM_INT);
        }

        $this->add_action_buttons(true, get_string('unsubscribe', 'bbbext_bnx'));
    }
}
