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
 * Settings for bbbext_bnx extension.
 *
 * Configures BN Experience features and conditionally hides the setup instructions
 * when BigBlueButton is pre-configured via config.php.
 *
 * @package    bbbext_bnx
 * @copyright  2025 onwards, Blindside Networks Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Shamiso Jaravaza (shamiso [dt] jaravaza [at] blindsidenetworks [dt] com)
 */

defined('MOODLE_INTERNAL') || die();

// Hide the setup description if BigBlueButton is already configured via config.php.
// Check if BigBlueButton is pre-configured via $CFG->bigbluebuttonbn array.
global $CFG;
$isconfigured = !empty($CFG->bigbluebuttonbn['server_url'] ?? null)
    && !empty($CFG->bigbluebuttonbn['shared_secret'] ?? null);

// Locate the BigBlueButton General settings page and replace the setup description if configured.
$bbbgeneralpage = $ADMIN->locate('modsettingbigbluebuttonbn');
if (
    $isconfigured
    && ($bbbgeneralpage instanceof admin_settingpage)
    && isset($bbbgeneralpage->settings->bigbluebuttonbn_config_general)
) {
    // Replace the setup description with a message indicating credentials are configured in config.php.
    $message = get_string('config_general_description_credentials_preconfigured', 'bbbext_bnx');
    $bbbgeneralpage->settings->bigbluebuttonbn_config_general = new admin_setting_heading(
        'bigbluebuttonbn_config_general',
        '',
        $message
    );
}

if ($ADMIN->fulltree) {
    $featuresbysection = [
        'waitingroom' => [
            'approvalbeforejoin',
        ],
        'reminders' => [
            'reminder',
        ],
    ];

    $options = [
        '1' => get_string('options_enabled', 'bbbext_bnx'),
        '0' => get_string('options_disabled', 'bbbext_bnx'),
    ];

    foreach ($featuresbysection as $section => $features) {
        $settings->add(new admin_setting_heading(
            "bbbext_bnx/section_{$section}",
            get_string("section_{$section}_heading", 'bbbext_bnx'),
            get_string("section_{$section}_desc", 'bbbext_bnx')
        ));

        foreach ($features as $feature) {
            $settings->add(new admin_setting_configselect(
                "bbbext_bnx/{$feature}_default",
                get_string("{$feature}_default", 'bbbext_bnx'),
                get_string("{$feature}_default_desc", 'bbbext_bnx'),
                '1',
                $options
            ));

            $settings->add(new admin_setting_configcheckbox(
                "bbbext_bnx/{$feature}_editable",
                get_string("{$feature}_editable", 'bbbext_bnx'),
                get_string("{$feature}_editable_desc", 'bbbext_bnx'),
                1
            ));
        }
    }

    // Email customisation settings for reminders.
    $settings->add(new admin_setting_heading(
        'bbbext_bnx/emailcontent',
        get_string('emailcontent', 'bbbext_bnx'),
        get_string('emailcontent:desc', 'bbbext_bnx')
    ));

    $settings->add(new admin_setting_configtext(
        'bbbext_bnx/emailsubject',
        get_string('emailsubject', 'bbbext_bnx'),
        get_string('emailsubject:desc', 'bbbext_bnx'),
        get_string('emailsubject:default', 'bbbext_bnx')
    ));

    $settings->add(new admin_setting_confightmleditor(
        'bbbext_bnx/emailtemplate',
        get_string('emailtemplate', 'bbbext_bnx'),
        get_string('emailtemplate:desc', 'bbbext_bnx'),
        get_string('emailtemplate:default', 'bbbext_bnx')
    ));

    $settings->add(new admin_setting_confightmleditor(
        'bbbext_bnx/emailfooter',
        get_string('emailfooter', 'bbbext_bnx'),
        get_string('emailfooter:desc', 'bbbext_bnx'),
        ''
    ));
}
