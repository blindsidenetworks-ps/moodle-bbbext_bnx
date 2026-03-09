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
 * Builder for BNX view page template data.
 *
 * @package   bbbext_bnx
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace bbbext_bnx\local\bigbluebutton\view;

use bbbext_bnx\local\helpers\sidecar_helper;
use bbbext_bnx\local\helpers\ui_string_helper;
use core\check\result;
use core\output\notification;
use mod_bigbluebuttonbn\instance;
use mod_bigbluebuttonbn\local\config;
use mod_bigbluebuttonbn\local\helpers\roles;
use mod_bigbluebuttonbn\local\proxy\bigbluebutton_proxy;
use mod_bigbluebuttonbn\meeting;
use renderer_base;
use stdClass;
use tool_task\check\cronrunning;
use bbbext_bnx\external\get_recordings;
use bbbext_bnx\output\recordings_session;

/**
 * Generates the template context for the BNX view page.
 */
class page_context_builder {
    /** @var instance */
    private $instance;
    /** @var renderer_base */
    private $output;

    /**
     * Constructor.
     *
     * @param instance $instance BigBlueButton instance being rendered.
     * @param renderer_base $output The renderer used for template exporting.
     */
    public function __construct(
        instance $instance,
        renderer_base $output
    ) {
        $this->instance = $instance;
        $this->output = $output;
    }

    /**
     * Build the complete template context.
     *
     * @return stdClass
     */
    public function build(): stdClass {
        $context = $this->create_base_context();

        $this->apply_site_notification($context);
        $this->apply_room_context($context);

        $context->showactionbar = $this->instance->can_manage_recordings();
        $context->refreshurl = $this->instance->get_view_url()->out();
        $context->recordingwarnings = $this->collect_recording_warnings();

        if ($this->should_display_recordings()) {
            $context->recordings->session = $this->build_recordings_session();
            $context->recordings->output = $this->fetch_recordings_output();
        } else if ($this->instance->is_type_recordings_only()) {
            $context->recordingwarnings[] = $this->render_notification(
                get_string('view_message_recordings_disabled', 'mod_bigbluebuttonbn'),
                notification::NOTIFY_WARNING
            );
        }

        return $context;
    }

    /**
     * Create the base context scaffold.
     *
     * @return stdClass
     */
    private function create_base_context(): stdClass {
        $pollinterval = bigbluebutton_proxy::get_poll_interval();

        return (object) [
            'instanceid' => $this->instance->get_instance_id(),
            'pollinterval' => $pollinterval * 1000,
            'groupselector' => $this->render_groups_selector(),
            'meetingname' => $this->instance->get_meeting_name(),
            'meetingdescription' => $this->instance->get_meeting_description(true),
            'description' => $this->instance->get_meeting_description(true),
            'joinurl' => \bbbext_bnx\local\helpers\joinurl_helper::build_join_url($this->instance)->out(false),
            'recordings' => (object) [
                'session' => (object) [],
                'output' => [],
                'search' => true,
            ],
        ];
    }

    /**
     * Render the groups selector with BNX-specific behavior.
     *
     * For Separate Groups mode, "All Participants" is hidden for all users
     * including teachers and admins. For Visible Groups, default behavior applies.
     *
     * @return string The rendered groups selector HTML.
     */
    private function render_groups_selector(): string {
        $cm = $this->instance->get_cm();
        $groupmode = groups_get_activity_groupmode($cm);

        if ($groupmode == NOGROUPS) {
            return '';
        }

        // Get allowed groups for the current user.
        $groups = groups_get_activity_allowed_groups($cm);
        if (empty($groups)) {
            \core\notification::add(
                get_string('view_groups_nogroups_warning', 'bigbluebuttonbn'),
                \core\output\notification::NOTIFY_INFO
            );
            return '';
        }

        if (count($groups) > 1) {
            \core\notification::add(
                get_string('view_groups_selection_warning', 'bigbluebuttonbn'),
                \core\output\notification::NOTIFY_INFO
            );
        }

        // For Separate Groups mode, hide "All Participants" for everyone (including teachers/admins).
        // For Visible Groups mode, show "All Participants" to everyone.
        $hideallparticipants = ($groupmode == SEPARATEGROUPS);

        $groupsmenu = groups_print_activity_menu(
            $cm,
            $this->instance->get_view_url(),
            true,
            $hideallparticipants
        );

        return $groupsmenu . '<br><br>';
    }

    /**
     * Add site wide notifications when applicable.
     *
     * @param stdClass $context
     * @return void
     */
    private function apply_site_notification(stdClass $context): void {
        $message = config::get('general_warning_message');
        if (!$this->should_show_site_notification($message)) {
            return;
        }

        $context->sitenotification = (object) [
            'message' => $message,
            'type' => config::get('general_warning_box_type'),
            'icon' => [
                'pix' => 'i/bullhorn',
                'component' => 'core',
            ],
        ];

        if ($url = config::get('general_warning_button_href')) {
            $context->sitenotification->actions = [[
                'url' => $url,
                'title' => config::get('general_warning_button_text'),
            ]];
        }
    }

    /**
     * Flag whether the notification banner should be shown.
     *
     * @param string|null $message
     * @return bool
     */
    private function should_show_site_notification(?string $message): bool {
        if (empty($message)) {
            return false;
        }

        if ($this->instance->is_admin()) {
            return true;
        }

        $generalwarningroles = explode(',', config::get('general_warning_roles'));
        $userroles = roles::get_user_roles(
            $this->instance->get_context(),
            $this->instance->get_user_id()
        );

        foreach ($userroles as $userrole) {
            if (in_array($userrole->shortname, $generalwarningroles)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Attach the current room data when the feature is enabled.
     *
     * @param stdClass $context
     * @return void
     */
    private function apply_room_context(stdClass $context): void {
        if (!$this->instance->is_feature_enabled('showroom')) {
            return;
        }

        $roomdata = meeting::get_meeting_info_for_instance($this->instance);
        $roomdata->haspresentations = !empty($roomdata->presentations);
        $roomdata->showpresentations = $this->instance->should_show_presentation();

        // Allow sidecars to adjust room data.
        $roomdata = $this->apply_sidecar_room_adjustments($roomdata);
        $roomdata->presentationtitle = ui_string_helper::get('view_section_title_presentation');

        $context->room = $roomdata;
    }

    /**
     * Allow sidecar plugins to adjust room data.
     *
     *
     * @param stdClass $roomdata The room data to adjust.
     * @return stdClass The adjusted room data.
     */
    private function apply_sidecar_room_adjustments(stdClass $roomdata): stdClass {
        return sidecar_helper::apply_room_adjustments($this->instance, $roomdata);
    }

    /**
     * Collate any warnings that should be displayed above the recordings table.
     *
     * @return array
     */
    private function collect_recording_warnings(): array {
        $warnings = [];

        if ($cronwarning = $this->build_cron_warning()) {
            $warnings[] = $cronwarning;
        }

        return $warnings;
    }

    /**
     * Produce the cron warning when the scheduled tasks are not running.
     *
     * @return array|null
     */
    private function build_cron_warning(): ?array {
        if (!$this->instance->is_moderator()) {
            return null;
        }

        $check = new cronrunning();
        $result = $check->get_result();

        if ($result->get_status() === result::OK) {
            return null;
        }

        return $this->render_notification(
            get_string('view_message_cron_disabled', 'mod_bigbluebuttonbn', $result->get_summary()),
            notification::NOTIFY_ERROR
        );
    }

    /**
     * Determine if the recordings section should be rendered with data.
     *
     * @return bool
     */
    private function should_display_recordings(): bool {
        return $this->instance->is_feature_enabled('showrecordings') && $this->instance->is_recorded();
    }

    /**
     * Export the recordings session metadata for the template.
     *
     * @return stdClass
     */
    private function build_recordings_session(): stdClass {
        $recordingssession = new recordings_session($this->instance);
        return $recordingssession->export_for_template($this->output);
    }

    /**
     * Fetch recordings and normalise the data for the template.
     *
     * @return array
     */
    private function fetch_recordings_output(): array {
        try {
            $recordings = get_recordings::execute(
                $this->instance->get_instance_id(),
                'protect,unprotect,publish,unpublish,delete',
                $this->instance->get_group_id()
            );
        } catch (\moodle_exception $e) {
            debugging('BNX recordings fetch error: ' . $e->getMessage());
            return [];
        }

        if (empty($recordings['tabledata']['data'])) {
            return [];
        }

        $recordingsoutput = json_decode($recordings['tabledata']['data'], true) ?? [];
        if (empty($recordingsoutput)) {
            return [];
        }

        $recordingsoutput[0]['first'] = true;
        foreach ($recordingsoutput as &$recording) {
            if (!empty($recording['date'])) {
                $recording['date'] = userdate($recording['date'] / 1000, '%B %d, %Y, %I:%M %p');
            }
        }
        unset($recording);

        return $recordingsoutput;
    }

    /**
     * Render a notification structure expected by the Mustache templates.
     *
     * @param string $message
     * @param string $type
     * @return array
     */
    private function render_notification(string $message, string $type): array {
        return (new notification($message, $type, false))->export_for_template($this->output);
    }
}
