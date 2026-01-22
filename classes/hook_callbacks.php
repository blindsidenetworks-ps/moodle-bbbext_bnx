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

use context_module;
use core\hook\output\before_standard_footer_html_generation;
use html_writer;

/**
 * Hook callbacks for BN Experience extension.
 *
 * @package    bbbext_bnx
 * @copyright  2025 onwards, Blindside Networks Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */
class hook_callbacks {
    /**
     * Inject the secondary navigation label override for BigBlueButton module pages.
     *
     * This hook callback runs on every page before the footer is rendered.
     * If the current page is within a BigBlueButton module context, it outputs
     * a hidden span with the custom label and initializes the overridenav JS module.
     *
     * @param before_standard_footer_html_generation $hook The hook instance.
     * @return void
     */
    public static function before_footer(before_standard_footer_html_generation $hook): void {
        global $PAGE;

        // Check if we're in a module context.
        $context = $PAGE->context;
        if ($context->contextlevel !== CONTEXT_MODULE) {
            return;
        }

        // Get the course module to check if it's a BigBlueButton activity.
        try {
            $cm = get_coursemodule_from_id('', $context->instanceid, 0, false, MUST_EXIST);
        } catch (\Exception $e) {
            return;
        }

        // Only proceed for BigBlueButton activities.
        if ($cm->modname !== 'bigbluebuttonbn') {
            return;
        }

        // Build the custom label.
        $label = get_string('navlabel', 'bbbext_bnx');

        // Output hidden span with the label data.
        $hook->add_html(
            html_writer::span('', 'bbbext-bnx-navlabel', [
                'data-label' => $label,
                'hidden' => 'hidden',
            ])
        );

        // Initialize the JavaScript module to override the nav label.
        $PAGE->requires->js_call_amd('bbbext_bnx/overridenav', 'init');
    }
}
