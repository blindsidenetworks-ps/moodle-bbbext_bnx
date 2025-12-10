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
use moodle_url;
use renderable;
use renderer_base;
use stdClass;
use templatable;

/**
 * Renderer for recording section.
 *
 * @package   bbbext_bnx
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class recordings_session implements renderable, templatable {
    /** @var instance $instance */
    protected $instance;

    /**
     * recordings_session constructor.
     *
     * @param instance $instance
     */
    public function __construct(instance $instance) {
        $this->instance = $instance;
    }

    /**
     * Export for template.
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output): stdClass {
        $isrecordedtype = $this->instance->is_type_room_and_recordings() || $this->instance->is_type_recordings_only();

        $templatedata = (object) [
            'bbbid' => $this->instance->get_instance_id(),
            'groupid' => $this->instance->get_group_id(),
            'has_recordings' => $this->instance->is_recorded() && $isrecordedtype,
            'searchbutton' => [
                'value' => '',
            ],
        ];

        if ($this->instance->can_import_recordings()) {
            global $PAGE;
            $urlpath = parse_url($PAGE->url->out_as_local_url(false), PHP_URL_PATH);
            $pagename = preg_replace('/\.php.*/', '', basename($urlpath));
            $pageparams = ['id' => $this->instance->get_cm()->id];
            $importurl = new moodle_url('/mod/bigbluebuttonbn/extension/bnx/import.php', [
                'destbn' => $this->instance->get_instance_id(),
                'originpage' => $pagename,
                'originparams' => http_build_query($pageparams),
            ]);
            $button = new \single_button(
                $importurl,
                get_string('view_recording_button_import', 'mod_bigbluebuttonbn')
            );
            $templatedata->import_button = $button->export_for_template($output);
        }
        return $templatedata;
    }
}
