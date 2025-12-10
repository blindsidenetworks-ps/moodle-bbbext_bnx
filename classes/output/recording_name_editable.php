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

namespace bbbext_bnx\output;

use mod_bigbluebuttonbn\instance;
use mod_bigbluebuttonbn\recording;

/**
 * Editable name field wrapper for recordings.
 *
 * @package    bbbext_bnx
 * @copyright  2025 onwards, Blindside Networks Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class recording_name_editable extends recording_editable {
    /**
     * Constructor with label and hint strings.
     *
     * @param recording $rec
     * @param instance $instance
     */
    public function __construct(recording $rec, instance $instance) {
        parent::__construct(
            $rec,
            $instance,
            get_string('view_recording_name_editlabel', 'mod_bigbluebuttonbn'),
            get_string('view_recording_name_edithint', 'mod_bigbluebuttonbn')
        );
    }

    /**
     * Get the value to display.
     *
     * @param recording $recording
     * @return string
     */
    public function get_recording_value(recording $recording): string {
        $metaname = $recording->get('name');
        return \html_writer::span($metaname, 'recording-name');
    }

    /**
     *  Get the type of editable.
     */
    protected static function get_type() {
        return 'name';
    }
}
