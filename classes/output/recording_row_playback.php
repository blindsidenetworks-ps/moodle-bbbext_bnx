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

use bbbext_bnx\local\bigbluebutton\recordings\recording_data;
use mod_bigbluebuttonbn\output\recording_row_playback as base_recording_row_playback;
use renderer_base;
use stdClass;

/**
 * Playback renderer with BNX adjustments.
 *
 * @package   bbbext_bnx
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class recording_row_playback extends base_recording_row_playback {
    /**
     * Export for template.
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output): stdClass {
        $ispublished = $this->recording->get('published');
        $recordingid = $this->recording->get('id');
        $context = (object) [
            'dataimported' => $this->recording->get('imported'),
            'id' => 'playbacks-' . $recordingid,
            'recordingid' => $recordingid,
            'additionaloptions' => '',
            'playbacks' => [],
        ];

        $playbacks = $this->recording->get('playbacks');
        if ($ispublished && $playbacks) {
            foreach ($playbacks as $playback) {
                if ($this->should_be_included($playback)) {
                    $linkattributes = [
                        'id' => "recording-play-{$playback['type']}-{$recordingid}",
                        'class' => 'btn btn-sm btn-default',
                        'data-action' => 'play',
                        'data-target' => $playback['type'],
                        'target' => '_blank',
                        'rel' => 'noopener noreferrer',
                    ];
                    $actionlink = new \action_link(
                        $playback['url'],
                        recording_data::type_text($playback['type']),
                        null,
                        $linkattributes
                    );
                    $context->playbacks[] = $actionlink->export_for_template($output);
                }
            }
        }
        return $context;
    }
}
