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

use stdClass;
use mod_bigbluebuttonbn\instance;
use mod_bigbluebuttonbn\local\exceptions\bigbluebutton_exception;
use mod_bigbluebuttonbn\recording;

/**
 * Class to describe a BBB Meeting with BNX extensions support.
 *
 * This class extends the core meeting to support features provided by BNX sidecar plugins.
 *
 * @package   bbbext_bnx
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */
class meeting extends \mod_bigbluebuttonbn\meeting {
    /**
     * Helper to join a meeting.
     *
     * It will create the meeting if not already created.
     *
     * @param instance $instance
     * @param int $origin
     * @return string
     * @throws meeting_join_exception this is sent if we cannot join (meeting full, user needs to wait...)
     */
    public static function join_meeting(instance $instance, $origin = logger::ORIGIN_BASE): string {
        $meeting = new meeting($instance);

        // Force-fetch meeting info from BBB (bypass cached meetinginfo property).
        $meeting->update_cache();

        if (empty($meeting->get_meeting_info()->createtime)) {
            try {
                $meeting->create_meeting();
            } catch (bigbluebutton_exception $e) {
                // The create request may have been processed by BBB even though
                // the response was malformed (e.g. proxied through a gateway).
                // Refresh meeting info and proceed if the meeting now exists.
                $meeting->update_cache();
                if (empty($meeting->get_meeting_info()->createtime)) {
                    throw $e;
                }
            }
            // Refresh cached info so join() sees the new createtime.
            $meeting->update_cache();
        }

        return $meeting->join($origin);
    }

    /**
     * Return meeting information for the specified instance.
     *
     * @param instance $instance
     * @param bool|null $updatecache Whether to update the cache when fetching the information
     * @return stdClass
     */
    public static function get_meeting_info_for_instance(instance $instance, $updatecache = null): stdClass {
        $meeting = new self($instance);
        return $meeting->do_get_meeting_info((bool) $updatecache);
    }

    /**
     * Creates a bigbluebutton meeting, send the message to BBB and returns the response in an array.
     *
     * @return array
     */
    public function create_meeting() {
        // Get presentations from sidecar plugins (e.g., bnx_preuploads).
        $presentations = $this->get_presentations_for_ws();
        if (empty($presentations)) {
            return parent::create_meeting();
        }

        // Multi-presentation flow: build data/metadata and call local proxy.
        $data = $this->create_meeting_data();
        $metadata = $this->create_meeting_metadata();
        $response = \bbbext_bnx\local\proxy\bigbluebutton_proxy::create_meeting_with_presentations(
            $data,
            $metadata,
            $presentations,
            $this->instance->get_instance_id()
        );

        // Preserve recording behavior from parent.
        if ($this->instance->is_recorded()) {
            $recording = new recording(0, (object) [
                'courseid' => $this->instance->get_course_id(),
                'bigbluebuttonbnid' => $this->instance->get_instance_id(),
                'recordingid' => $response['internalMeetingID'],
                'groupid' => $this->instance->get_group_id(),
            ]);
            $recording->create();
        }

        return $response;
    }

    /**
     * Return meeting information for this meeting.
     *
     * @param bool|null $updatecache Whether to update the cache when fetching the information
     * @return stdClass
     */
    protected function do_get_meeting_info($updatecache = null): stdClass {
        // Delegate most of the work to parent and then adjust the few fields we need to change.
        $meetinginfo = parent::do_get_meeting_info((bool) $updatecache);

        // Replace the join URL with our custom join URL builder.
        $meetinginfo->joinurl = \bbbext_bnx\local\helpers\joinurl_helper::build_join_url($this->instance)->out(false);

        // Get presentations from sidecar plugins.
        $presentations = $this->get_presentations();
        if (!empty($presentations)) {
            $meetinginfo->presentations = $presentations;
            $meetinginfo->showpresentations = $this->instance->should_show_presentation();
        }
        // Apply room adjustments from sidecars.
        $meetinginfo = local\helpers\sidecar_helper::apply_room_adjustments($this->instance, $meetinginfo);

        return $meetinginfo;
    }

    /**
     * Get presentations for webservice consumption from sidecar plugins.
     *
     * @return array
     */
    protected function get_presentations_for_ws(): array {
        // Check if bnx_preuploads is installed, enabled, and has the helper class.
        $helperclass = '\bbbext_bnx_preuploads\local\helpers\presentation_helper';
        if (!local\helpers\sidecar_helper::is_available('bnx_preuploads', $helperclass)) {
            return [];
        }
        return \bbbext_bnx_preuploads\local\helpers\presentation_helper::get_presentations_for_ws(
            $this->instance->get_instance_id()
        );
    }

    /**
     * Get presentations from sidecar plugins for display.
     *
     * @return array
     */
    protected function get_presentations(): array {
        // Check if bnx_preuploads is installed, enabled, and has the helper class.
        $helperclass = '\bbbext_bnx_preuploads\local\helpers\presentation_helper';
        if (!local\helpers\sidecar_helper::is_available('bnx_preuploads', $helperclass)) {
            return [];
        }
        return \bbbext_bnx_preuploads\local\helpers\presentation_helper::get_presentations(
            $this->instance->get_instance_id()
        );
    }
}
