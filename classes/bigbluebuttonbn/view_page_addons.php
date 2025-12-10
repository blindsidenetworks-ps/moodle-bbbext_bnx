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
 * View Page template renderable.
 *
 * @package   bbbext_bnx
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

namespace bbbext_bnx\bigbluebuttonbn;

use core\check\result;
use core\output\notification;
use mod_bigbluebuttonbn\instance;
use mod_bigbluebuttonbn\local\config;
use mod_bigbluebuttonbn\local\proxy\bigbluebutton_proxy;
use mod_bigbluebuttonbn\meeting;
use renderer_base;
use stdClass;
use tool_task\check\cronrunning;
use bbbext_bnx\external\get_recordings;
use bbbext_bnx\output\recordings_session;

/**
 * BNX view override that embeds the enhanced recordings experience.
 *
 * @package   bbbext_bnx
 */
class view_page_addons extends \mod_bigbluebuttonbn\local\extension\view_page_addons {
    /** @var instance */
    protected $instance;

    /**
     * Construct the renderable for a specific instance.
     *
     * @param instance $instance BigBlueButton instance being rendered.
     */
    public function __construct(instance $instance) {
        $this->instance = $instance;
    }

    /**
     * Build the template context for the BNX view.
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output): stdClass {
        global $PAGE;

        $pollinterval = bigbluebutton_proxy::get_poll_interval();
        $renderer = $PAGE->get_renderer('mod_bigbluebuttonbn');
        $groupselector = $renderer->render_groups_selector($this->instance);

        $templatedata = (object) [
            'instanceid' => $this->instance->get_instance_id(),
            'pollinterval' => $pollinterval * 1000,
            'groupselector' => $groupselector,
            'meetingname' => $this->instance->get_meeting_name(),
            'meetingdescription' => $this->instance->get_meeting_description(true),
            'description' => $this->instance->get_meeting_description(true),
            'joinurl' => $this->instance->get_join_url(),
            'recordings' => (object) [
                'session' => (object) [],
                'output' => [],
                'search' => true,
            ],
        ];

        $viewwarningmessage = config::get('general_warning_message');
        if ($this->show_view_warning() && !empty($viewwarningmessage)) {
            $templatedata->sitenotification = (object) [
                'message' => $viewwarningmessage,
                'type' => config::get('general_warning_box_type'),
                'icon' => [
                    'pix' => 'i/bullhorn',
                    'component' => 'core',
                ],
            ];

            if ($url = config::get('general_warning_button_href')) {
                $templatedata->sitenotification->actions = [[
                    'url' => $url,
                    'title' => config::get('general_warning_button_text'),
                ]];
            }
        }

        if ($this->instance->is_feature_enabled('showroom')) {
            $showpresentation = $this->instance->should_show_presentation();
            $roomdata = meeting::get_meeting_info_for_instance($this->instance);
            $roomdata->haspresentations = !empty($roomdata->presentations);
            $roomdata->showpresentations = $showpresentation;
            $templatedata->room = $roomdata;
        }

        $templatedata->showactionbar = $this->instance->can_manage_recordings();
        $templatedata->refreshurl = $this->instance->get_view_url()->out();
        $templatedata->recordingwarnings = [];

        $check = new cronrunning();
        $result = $check->get_result();
        if ($result->get_status() != result::OK && $this->instance->is_moderator()) {
            $templatedata->recordingwarnings[] = (new notification(
                get_string('view_message_cron_disabled', 'mod_bigbluebuttonbn', $result->get_summary()),
                notification::NOTIFY_ERROR,
                false
            ))->export_for_template($output);
        }

        if ($this->instance->is_feature_enabled('showrecordings') && $this->instance->is_recorded()) {
            $recordingssession = new recordings_session($this->instance);
            $templatedata->recordings->session = $recordingssession->export_for_template($output);

            try {
                $recordings = get_recordings::execute(
                    $this->instance->get_instance_id(),
                    'protect,unprotect,publish,unpublish,delete',
                    $this->instance->get_group_id()
                );

                if (!empty($recordings['tabledata']['data'])) {
                    $recordingsoutput = json_decode($recordings['tabledata']['data'], true) ?? [];
                    if (!empty($recordingsoutput)) {
                        $recordingsoutput[0]['first'] = true;
                    }
                    foreach ($recordingsoutput as &$recording) {
                        if (!empty($recording['date'])) {
                            $recording['date'] = userdate($recording['date'] / 1000, '%B %d, %Y, %I:%M %p');
                        }
                    }
                    unset($recording);
                    $templatedata->recordings->output = $recordingsoutput;
                }
            } catch (\moodle_exception $e) {
                debugging('BNX recordings fetch error: ' . $e->getMessage());
            }
        } else if ($this->instance->is_type_recordings_only()) {
            $templatedata->recordingwarnings[] = (new notification(
                get_string('view_message_recordings_disabled', 'mod_bigbluebuttonbn'),
                notification::NOTIFY_WARNING,
                false
            ))->export_for_template($output);
        }

        return $templatedata;
    }

    /**
     * Determine if the view warning should be displayed.
     */
    protected function show_view_warning(): bool {
        if ($this->instance->is_admin()) {
            return true;
        }
        $generalwarningroles = explode(',', config::get('general_warning_roles'));
        $userroles = \mod_bigbluebuttonbn\local\helpers\roles::get_user_roles(
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
}
