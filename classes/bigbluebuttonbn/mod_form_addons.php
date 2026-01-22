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
 * Definitions for the bnx mod form addons.
 *
 * @package   bbbext_bnx
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

namespace bbbext_bnx\bigbluebuttonbn;

use bbbext_bnx\local\services\bnx_settings_service;
use bbbext_bnx\local\services\bnx_settings_service_interface;
use stdClass;

/**
 * BNX mod form integration.
 *
 * @package   bbbext_bnx
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */
class mod_form_addons extends \mod_bigbluebuttonbn\local\extension\mod_form_addons {
    /**
     * Service used to fetch and persist settings.
     * @var bnx_settings_service_interface
     */
    private bnx_settings_service_interface $service;

    /**
     * Construct the addon wrapper around the Moodle form.
     *
     * @param \MoodleQuickForm $mform form instance
     * @param stdClass|null $bigbluebuttonbndata existing module data
     * @param string|null $suffix suffix used when the form fieldset is duplicated
     * @param bnx_settings_service_interface|null $service optional service override for testing
     */
    public function __construct(
        \MoodleQuickForm &$mform,
        ?stdClass $bigbluebuttonbndata = null,
        ?string $suffix = null,
        ?bnx_settings_service_interface $service = null
    ) {
        parent::__construct($mform, $bigbluebuttonbndata, $suffix);
        $this->service = $service ?? bnx_settings_service::get_service();
    }

    /**
     * Apply post-processing adjustments to submitted data.
     *
     * @param stdClass $data form submission
     * @return void
     */
    public function data_postprocessing(stdClass &$data): void {
        foreach (array_keys(mod_instance_helper::FEATURE_FIELD_MAP) as $field) {
            if (!property_exists($data, $field)) {
                continue;
            }

            $data->{$field} = (int)!empty($data->{$field});
        }
    }

    /**
     * Preload form defaults from stored settings.
     *
     * @param array|null $defaultvalues form defaults
     * @return void
     */
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

    /**
     * Declare additional completion rules added by the addon.
     *
     * @return array
     */
    public function add_completion_rules(): array {
        return [];
    }

    /**
     * Add supplementary form fields contributed by the addon.
     *
     * @return void
     */
    public function add_fields(): void {
        // A nav label override is handled globally via hook_callbacks::before_footer().
    }

    /**
     * Validate addon-specific form data.
     *
     * @param array $data submitted values
     * @param array $files uploaded files
     * @return array
     */
    public function validation(array $data, array $files): array {
        $errors = [];

        foreach (array_keys(mod_instance_helper::FEATURE_FIELD_MAP) as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }

            $value = $data[$field];
            if (!is_bool($value) && !is_numeric($value)) {
                $errors[$field] = get_string('err_numeric', 'form');
            }
        }

        unset($files);

        return $errors;
    }

    /**
     * Resolve the bnx identifier for a module.
     *
     * @param int $moduleid module identifier
     * @return int|null
     */
    private function get_bnx_id(int $moduleid): ?int {
        global $DB;

        $record = $DB->get_record('bbbext_bnx', ['bigbluebuttonbnid' => $moduleid], 'id');
        return $record ? (int)$record->id : null;
    }
}
