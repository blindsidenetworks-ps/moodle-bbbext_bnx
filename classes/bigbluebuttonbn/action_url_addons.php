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

namespace bbbext_bnx\bigbluebuttonbn;
use bbbext_bnx\local\bigbluebutton\action_url_parameters;

/**
 * Class action_url_addons
 *
 * @package    bbbext_bnx
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class action_url_addons extends \mod_bigbluebuttonbn\local\extension\action_url_addons {
    /**
     * Execute action URL addons.
     *
     * @param string $action
     * @param array $data
     * @param array $metadata
     * @param int|null $instanceid
     * @return array associative array with the additional data and metadata (indexed by 'data' and
     * 'metadata' keys)
     */
    public function execute(string $action = '', array $data = [], array $metadata = [], ?int $instanceid = null): array {
        unset($data, $metadata);

        // Per extension contract: return ONLY the parameters this addon adds, not the full input.
        // This prevents later addons from overwriting our additions when core merges results.
        if (!$instanceid) {
            return ['data' => [], 'metadata' => []];
        }
        $additionaldata = action_url_parameters::get_parameters($action, $instanceid);
        return ['data' => $additionaldata, 'metadata' => []];
    }
}
