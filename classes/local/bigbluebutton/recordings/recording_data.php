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
        $coursecanmanage = self::can_manage_at_course_level($instance, $courseid);
        $tools = self::filter_tools_for_context($tools, $instance, $rec, $coursecanmanage);

        if (!self::include_recording_table_row($instance, $rec)) {
            return null;
        }

        $renderer = $PAGE->get_renderer('mod_bigbluebuttonbn');
        $row = new stdClass();

        $row->playback = self::render_playback_cell($rec, $instance, $renderer);
        [$row->recording, $row->description] = self::render_recording_cells($rec, $instance, $renderer);
        $preview = self::render_preview_cell($rec, $instance, $coursecanmanage, $renderer);
        if ($preview !== null) {
            $row->preview = $preview;
        }
        $row->date = self::normalise_recording_date($rec);
        $row->duration = self::row_duration($rec);

        $actionbar = self::render_actionbar_cell($rec, $instance, $coursecanmanage, $tools, $renderer);
        if ($actionbar !== null) {
            $row->actionbar = $actionbar;
        }

        return $row;
    }

    /**
     * Determine if the user can manage recordings from the wider course context.
     *
     * @param instance|null $instance
     * @param int|null $courseid
     * @return bool
     */
    private static function can_manage_at_course_level(?instance $instance, ?int $courseid): bool {
        if (!empty($instance)) {
            return false;
        }

        return roles::has_capability_in_course($courseid, 'mod/bigbluebuttonbn:managerecordings');
    }

    /**
     * Restrict tools based on permissions and recording state.
     *
     * @param array $tools
     * @param instance|null $instance
     * @param recording $rec
     * @param bool $coursecanmanage
     * @return array
     */
    private static function filter_tools_for_context(
        array $tools,
        ?instance $instance,
        recording $rec,
        bool $coursecanmanage
    ): array {
        if (!(bool) config::get('recording_protect_editable')) {
            $tools = array_diff($tools, ['protect', 'unprotect']);
        }

        if (in_array('protect', $tools, true) && $rec->get('protected') === null) {
            $tools = array_diff($tools, ['protect', 'unprotect']);
        }

        foreach ($tools as $key => $tool) {
            $allowed = !empty($instance)
                ? $instance->can_perform_on_recordings($tool)
                : $coursecanmanage;

            if (!$allowed) {
                unset($tools[$key]);
            }
        }

        return $tools;
    }

    /**
     * Render the playback column content.
     *
     * @param recording $rec
     * @param instance|null $instance
     * @param \renderer_base $renderer
     * @return string
     */
    private static function render_playback_cell(
        recording $rec,
        ?instance $instance,
        \renderer_base $renderer
    ): string {
        $recordingplayback = new recording_row_playback($rec, $instance);
        return $renderer->render($recordingplayback);
    }

    /**
     * Render the recording name and description cells.
     *
     * @param recording $rec
     * @param instance|null $instance
     * @param \renderer_base $renderer
     * @return array{0:string,1:string}
     */
    private static function render_recording_cells(
        recording $rec,
        ?instance $instance,
        \renderer_base $renderer
    ): array {
        if (empty($instance)) {
            return [$rec->get('name'), $rec->get('description')];
        }

        $recordingname = new recording_name_editable($rec, $instance);
        $recordingdescription = new recording_description_editable($rec, $instance);

        return [
            $renderer->render_inplace_editable($recordingname),
            $renderer->render_inplace_editable($recordingdescription),
        ];
    }

    /**
     * Render the preview column when available.
     *
     * @param recording $rec
     * @param instance|null $instance
     * @param bool $coursecanmanage
     * @param \renderer_base $renderer
     * @return string|null
     */
    private static function render_preview_cell(
        recording $rec,
        ?instance $instance,
        bool $coursecanmanage,
        \renderer_base $renderer
    ): ?string {
        $previewenabled = (!empty($instance) && self::preview_enabled($instance)) || $coursecanmanage;
        if (!$previewenabled) {
            return null;
        }

        if (!$rec->get('playbacks')) {
            return null;
        }

        $rowpreview = new recording_row_preview($rec);
        return $renderer->render($rowpreview);
    }

    /**
     * Convert a recording start time into the table value.
     *
     * @param recording $rec
     * @return float
     */
    private static function normalise_recording_date(recording $rec): float {
        $starttime = $rec->get('starttime');
        return $starttime !== null ? (float) $starttime : 0.0;
    }

    /**
     * Render the actionbar cell if the user can manage recordings.
     *
     * @param recording $rec
     * @param instance|null $instance
     * @param bool $coursecanmanage
     * @param array $tools
     * @param \renderer_base $renderer
     * @return string|null
     */
    private static function render_actionbar_cell(
        recording $rec,
        ?instance $instance,
        bool $coursecanmanage,
        array $tools,
        \renderer_base $renderer
    ): ?string {
        $canmanage = (!empty($instance) && $instance->can_manage_recordings()) || $coursecanmanage;
        if (!$canmanage || empty($tools)) {
            return null;
        }

        $actionbar = new recording_row_actionbar($rec, $tools);
        return $renderer->render($actionbar);
    }
}
