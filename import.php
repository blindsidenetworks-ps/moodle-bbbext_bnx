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
 * View for importing BigBlueButtonBN recordings.
 *
 * @package   bbbext_bnx
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

use bbbext_bnx\output\import;
use core\notification;
use mod_bigbluebuttonbn\instance;
use mod_bigbluebuttonbn\local\config;
use mod_bigbluebuttonbn\local\exceptions\server_not_available_exception;
use mod_bigbluebuttonbn\local\proxy\bigbluebutton_proxy;
use mod_bigbluebuttonbn\plugin;
use moodle_url;

require_once(__DIR__ . '/../../../../config.php');

$destbn = required_param('destbn', PARAM_INT);
$sourcebn = optional_param('sourcebn', -1, PARAM_INT);
$sourcecourseid = optional_param('sourcecourseid', -1, PARAM_INT);
$originpage = optional_param('originpage', '', PARAM_TEXT);
parse_str(optional_param('originparams', '', PARAM_TEXT), $originparams);

$destinationinstance = instance::get_from_instanceid($destbn);
if (!$destinationinstance) {
    throw new moodle_exception('view_error_url_missing_parameters', plugin::COMPONENT);
}

$cm = $destinationinstance->get_cm();
$course = $destinationinstance->get_course();

require_login($course, true, $cm);

$originurl = new moodle_url('/mod/bigbluebuttonbn/extension/bnx/import.php', [
    'destbn' => $destinationinstance->get_instance_id(),
    'originpage' => $originpage,
    'originparams' => http_build_query($originparams),
]);

if (!(bool) config::importrecordings_enabled()) {
    notification::add(
        get_string('view_message_importrecordings_disabled', plugin::COMPONENT),
        notification::ERROR
    );
    redirect($originurl);
}

$sourceinstanceid = is_numeric($sourcebn) ? (int) $sourcebn : -1;

$PAGE->set_url($originurl);
$PAGE->set_title($destinationinstance->get_meeting_name());
$PAGE->set_cacheable(false);
$PAGE->set_heading($course->fullname);

$renderer = $PAGE->get_renderer('mod_bigbluebuttonbn');

try {
    $renderedinfo = $renderer->render(
        new import($destinationinstance, $sourcecourseid, $sourceinstanceid, $originpage, $originparams)
    );
} catch (server_not_available_exception $e) {
    bigbluebutton_proxy::handle_server_not_available($instance);
}

echo $OUTPUT->header();

echo $renderedinfo;

echo $OUTPUT->footer();
