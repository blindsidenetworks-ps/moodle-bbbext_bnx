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
 * Base BNX view-page override that mirrors the core BigBlueButtonBN output.
 *
 * @package   bbbext_bnx
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

namespace bbbext_bnx\bigbluebuttonbn;

defined('MOODLE_INTERNAL') || die();

use mod_bigbluebuttonbn\instance;
use renderer_base;
use stdClass;

/**
 * BNX view override scaffold delegating to the core implementation.
 *
 * This class exists purely to hook the BNX framework into the standard
 * BigBlueButtonBN view output without changing its behaviour.
 *
 * @package   bbbext_bnx
 */
class view_page_addons extends \mod_bigbluebuttonbn\local\extension\view_page_addons {
	/**
	 * Pass constructor through to the parent so core logic remains unchanged.
	 *
	 * @param instance $instance Activity instance being rendered
	 */
	public function __construct(instance $instance) {
		parent::__construct($instance);
	}

	/**
	 * Delegate template export to the parent implementation verbatim.
	 *
	 * @param renderer_base $output The renderer preparing the view page
	 * @return stdClass Template context from the core view
	 */
	public function export_for_template(renderer_base $output): stdClass {
		return parent::export_for_template($output);
	}
}
