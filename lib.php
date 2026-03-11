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
 * Callback implementations for the BN Experience extension.
 *
 * @package    bbbext_bnx
 * @copyright  2025 onwards, Blindside Networks Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */
/**
 * In place editable callback for BNX-managed recording fields.
 *
 * @param string $itemtype editable item type
 * @param string $itemid identifier for the editable item
 * @param mixed $newvalue new value to persist
 * @return \core\output\inplace_editable|null
 */
function bbbext_bnx_inplace_editable(string $itemtype, string $itemid, $newvalue) {
    $editableclass = "\\bbbext_bnx\\output\\recording_{$itemtype}_editable";
    if (class_exists($editableclass) && method_exists($editableclass, 'update')) {
        return $editableclass::update($itemid, $newvalue);
    }

    return null; // Let core throw the standard exception for unknown editables.
}

/**
 * Add FontAwesome icon mapping.
 *
 * @return string[]
 */
function bbbext_bnx_get_fontawesome_icon_map() {
    return [
        'bbbext_bnx:i/bell' => 'fa-bell-o',
    ];
}

/**
 * Serves attached files for email reminders.
 *
 * @param mixed $course course or id of the course
 * @param mixed $cm course module or id of the course module
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options additional options affecting the file serving
 * @return bool false if file not found, does not return if found
 */
function bbbext_bnx_pluginfile(
    $course,
    $cm,
    context $context,
    $filearea,
    $args,
    $forcedownload,
    array $options = []
) {
    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_login($course, false, $cm);
    $canview = has_capability('mod/bigbluebuttonbn:view', $context);

    if ($filearea === \bbbext_bnx\reminders_utils::EMAIL_REMINDER_FILEAREA) {
        $canview = true; // External users can see the image.
    }

    if (!$canview) {
        return false;
    }

    $itemid = (int) array_shift($args);
    if ($itemid != 0) {
        return false;
    }

    $relativepath = implode('/', $args);
    $fullpath = "/{$context->id}/bbbext_bnx/$filearea/$itemid/$relativepath";

    $fs = get_file_storage();
    $file = $fs->get_file_by_hash(sha1($fullpath));
    if (!$file || $file->is_directory()) {
        return false;
    }
    send_stored_file($file, 0, 0, $forcedownload, $options);
}

/**
 * Add a reminder preferences link to user settings navigation.
 *
 * @param navigation_node $useraccount
 * @param stdClass $user
 * @param context_user $context
 * @param stdClass $course
 * @param context_course $coursecontext
 * @return void
 */
function bbbext_bnx_extend_navigation_user_settings(
    navigation_node $useraccount,
    stdClass $user,
    context_user $context,
    stdClass $course,
    context_course $coursecontext
) {
    unset($user, $context, $course, $coursecontext);

    if (!\bbbext_bnx\reminders_utils::is_reminders_enabled()) {
        return;
    }
    $parent = $useraccount->parent->find('useraccount', navigation_node::TYPE_CONTAINER);
    if ($parent) {
        $parent->add(
            get_string('reminders:preferences', 'bbbext_bnx'),
            new moodle_url('/mod/bigbluebuttonbn/extension/bnx/managesubscriptions.php')
        );
    }
}

/**
 * Callback when guests are added to a meeting — store their emails for reminders.
 *
 * @param array $emails
 * @param int $instanceid
 * @return void
 */
function bbbext_bnx_meeting_add_guests(array $emails, int $instanceid): void {
    global $USER;
    foreach ($emails as $email) {
        \bbbext_bnx\local\persistent\guest_email::create_guest_mail_record($email, $instanceid, $USER->id);
    }
}
