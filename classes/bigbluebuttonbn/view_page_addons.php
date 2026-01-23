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

use mod_bigbluebuttonbn\instance;
use renderer_base;
use stdClass;
use bbbext_bnx\local\bigbluebutton\view\page_context_builder;

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
        $builder = new page_context_builder($this->instance, $output);

        return $builder->build();
    }
}
