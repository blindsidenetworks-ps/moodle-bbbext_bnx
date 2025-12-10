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
 * The recordings_data.
 *
 * @package    bbbext_bnx
 * @copyright  2025 onwards, Blindside Networks Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Shamiso Jaravaza  (shamiso [dt] jaravaza [at] blindsidenetworks [dt] com)
 */

namespace bbbext_bnx\local\bigbluebutton\recordings;

use mod_bigbluebuttonbn\instance;
use mod_bigbluebuttonbn\local\config;
use mod_bigbluebuttonbn\local\helpers\roles;
use mod_bigbluebuttonbn\output\recording_row_preview;
use mod_bigbluebuttonbn\recording;
use bbbext_bnx\output\recording_description_editable;
use bbbext_bnx\output\recording_name_editable;
use bbbext_bnx\output\recording_row_actionbar;
use bbbext_bnx\output\recording_row_playback;
use mod_bigbluebuttonbn\local\bigbluebutton\recordings\recording_data as base_recording_data;
use stdClass;

/**
 * Build table content for the BNX recordings table.
 *
 * @package    bbbext_bnx
 */
class recording_data extends base_recording_data {
    /**
     * Get the full recording table.
     *
     * @param array $recordings
     * @param array $tools
     * @param instance|null $instance
     * @param int $courseid
     * @return array
     */
    public static function get_recording_table(
        array $recordings,
        array $tools,
        ?instance $instance = null,
        int $courseid = 0
    ): array {
        $table = parent::get_recording_table([], $tools, $instance, $courseid);

        $rows = [];
        foreach ($recordings as $rec) {
            $rowtools = $tools;
            if (!(bool) config::get('recording_protect_editable')) {
                $rowtools = array_diff($rowtools, ['protect', 'unprotect']);
            }
            if (in_array('protect', $rowtools, true) && $rec->get('protected') === null) {
                $rowtools = array_diff($rowtools, ['protect', 'unprotect']);
            }

            $row = self::row($instance, $rec, $rowtools, $courseid);
            if (!empty($row)) {
                $rows[] = $row;
            }
        }
        $table['data'] = json_encode($rows);
        return $table;
    }

    // phpcs:ignore moodle.Commenting.DocblockTagSniff.InvalidTag
    /**
     * Build a single row entry for the recordings table output.
     *
     * @param instance|null $instance BigBlueButton instance context
     * @param recording $rec Recording being rendered
     * @param array|null $tools Tools available for this recording
     * @param int|null $courseid Course id when no instance is provided
     * @return stdClass|null
     */
    public static function row(
        ?instance $instance,
        recording $rec,
        ?array $tools = null,
        ?int $courseid = 0
    ): ?stdClass {
        global $PAGE;
        $tools = $tools ?? [];
        $hascapabilityincourse = empty($instance)
            && roles::has_capability_in_course($courseid, 'mod/bigbluebuttonbn:managerecordings');
        $renderer = $PAGE->get_renderer('mod_bigbluebuttonbn');
        foreach ($tools as $key => $tool) {
            $allowed = !empty($instance)
                ? $instance->can_perform_on_recordings($tool)
                : $hascapabilityincourse;

            if (!$allowed) {
                unset($tools[$key]);
            }
        }
        if (!self::include_recording_table_row($instance, $rec)) {
            return null;
        }
        $row = new stdClass();

        $recordingplayback = new recording_row_playback($rec, $instance);
        $row->playback = $renderer->render($recordingplayback);

        if (empty($instance)) {
            $row->recording = $rec->get('name');
            $row->description = $rec->get('description');
        } else {
            $recordingname = new recording_name_editable($rec, $instance);
            $row->recording = $renderer->render_inplace_editable($recordingname);
            $recordingdescription = new recording_description_editable($rec, $instance);
            $row->description = $renderer->render_inplace_editable($recordingdescription);
        }

        if ((!empty($instance) && self::preview_enabled($instance)) || $hascapabilityincourse) {
            $row->preview = '';
            if ($rec->get('playbacks')) {
                $rowpreview = new recording_row_preview($rec);
                $row->preview = $renderer->render($rowpreview);
            }
        }
        $starttime = $rec->get('starttime');
        $row->date = !is_null($starttime) ? floatval($starttime) : 0;
        $row->duration = self::row_duration($rec);
        if ((!empty($instance) && $instance->can_manage_recordings()) || $hascapabilityincourse) {
            $actionbar = new recording_row_actionbar($rec, $tools);
            $row->actionbar = $renderer->render($actionbar);
        }
        return $row;
    }
}
