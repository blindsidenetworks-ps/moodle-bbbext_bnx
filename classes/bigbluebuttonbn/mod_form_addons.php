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

defined('MOODLE_INTERNAL') || die();

use bbbext_bnx\local\service\bnx_settings_service;
use stdClass;

/**
 * BNX mod form integration.
 *
 * @package   bbbext_bnx
 */
class mod_form_addons extends \mod_bigbluebuttonbn\local\extension\mod_form_addons {
    private bnx_settings_service $service;

    public function __construct(\MoodleQuickForm &$mform, ?stdClass $bigbluebuttonbndata = null, ?string $suffix = null) {
        parent::__construct($mform, $bigbluebuttonbndata, $suffix);
        $this->service = new bnx_settings_service();
    }

    public function data_postprocessing(stdClass &$data): void {
        // BNX currently leaves post-processing unchanged.
    }

    public function data_preprocessing(?array &$defaultvalues): void {
        if (empty($defaultvalues['id'])) {
            return;
        }

        $bnxid = $this->get_bnx_id((int)$defaultvalues['id']);
        if ($bnxid === null) {
            return;
        }

        $settings = $this->service->get_settings($bnxid);
        foreach (mod_instance_helper::FEATURE_FIELD_MAP as $field => $setting) {
            if (!isset($settings[$setting])) {
                continue;
            }
            $defaultvalues[$field] = (int)$settings[$setting];
        }
    }

    public function add_completion_rules(): array {
        return [];
    }

    public function add_fields(): void {
        // Parent BNX plugin does not expose additional form controls yet.
    }

    public function validation(array $data, array $files): array {
        return [];
    }

    private function get_bnx_id(int $moduleid): ?int {
        global $DB;

        $record = $DB->get_record('bbbext_bnx', ['bigbluebuttonbnid' => $moduleid], 'id');
        return $record ? (int)$record->id : null;
    }
}
