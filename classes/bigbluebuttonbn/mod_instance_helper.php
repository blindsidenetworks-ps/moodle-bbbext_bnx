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
 * Definitions for the bnx module instance helper.
 *
 * @package   bbbext_bnx
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

namespace bbbext_bnx\bigbluebuttonbn;

use bbbext_bnx\local\services\bnx_service;
use bbbext_bnx\local\services\bnx_service_interface;
use bbbext_bnx\local\services\bnx_settings_service;
use bbbext_bnx\local\services\bnx_settings_service_interface;
use stdClass;

/**
 * BNX lifecycle helper.
 *
 * @package   bbbext_bnx
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */
class mod_instance_helper extends \mod_bigbluebuttonbn\local\extension\mod_instance_helper {
    /**
     * Table storing base bnx records.
     * @var string
     */
    private const BNX_TABLE = 'bbbext_bnx';

    /** Table storing individual reminder timespans. */
    public const REMINDERS_TABLE = 'bbbext_bnx_reminders';

    /** Table storing guest emails for reminders. */
    public const REMINDERS_GUESTS_TABLE = 'bbbext_bnx_reminders_guests';

    /**
     * Mapping between form fields and stored setting names.
     */
    public const FEATURE_FIELD_MAP = [
        'approvalbeforejoin' => 'approvalbeforejoin',
    ];

    /**
     * Service handling persistence of bnx record.
     * @var bnx_service_interface
     */
    private bnx_service_interface $bnxservice;

    /**
     * Service handling persistence of bnx settings.
     * @var bnx_settings_service_interface
     */
    private bnx_settings_service_interface $service;

    /**
     * Initialise the helper with service instances.
     *
     * @param bnx_service_interface|null $bnxservice optional bnx service override for testing
     * @param bnx_settings_service_interface|null $service optional settings service override for testing
     */
    public function __construct(
        ?bnx_service_interface $bnxservice = null,
        ?bnx_settings_service_interface $service = null
    ) {
        $this->bnxservice = $bnxservice ?? bnx_service::get_service();
        $this->service = $service ?? bnx_settings_service::get_service();
    }

    /**
     * Persist bnx details when an instance is created.
     *
     * @param stdClass $bigbluebuttonbn module data payload
     * @return void
     */
    public function add_instance(stdClass $bigbluebuttonbn) {
        $bnxid = $this->persist_bnx_record($bigbluebuttonbn);
        if ($bnxid !== null) {
            $this->persist_settings($bnxid, $bigbluebuttonbn);
        }
        $this->sync_reminder_data($bigbluebuttonbn);
    }

    /**
     * Sync bnx details when a module is updated.
     *
     * @param stdClass $bigbluebuttonbn module data payload
     * @return void
     */
    public function update_instance(stdClass $bigbluebuttonbn): void {
        $bnxid = $this->persist_bnx_record($bigbluebuttonbn);
        if ($bnxid !== null) {
            $this->persist_settings($bnxid, $bigbluebuttonbn);
        }
        $this->sync_reminder_data($bigbluebuttonbn);
    }

    /**
     * Cleanup persisted settings when a module is deleted.
     *
     * @param int $moduleid module identifier
     * @return void
     */
    public function delete_instance(int $moduleid): void {
        global $DB;

        $bnxid = $this->get_bnx_id($moduleid);
        if ($bnxid === null) {
            return;
        }

        $this->bnxservice->delete_bnx($moduleid);
        $this->service->delete_settings($bnxid);

        // Clean up reminder tables.
        $DB->delete_records(self::REMINDERS_TABLE, ['bigbluebuttonbnid' => $moduleid]);
        $DB->delete_records(self::REMINDERS_GUESTS_TABLE, ['bigbluebuttonbnid' => $moduleid]);
    }

    /**
     * Report extension tables used to store related data.
     *
     * @return string[]
     */
    public function get_join_tables(): array {
        return [
            self::BNX_TABLE,
        ];
    }

    /**
     * Sync reminder data (enabled flags and timespan rows) from form submission.
     *
     * @param stdClass $data module data payload
     * @return void
     */
    private function sync_reminder_data(stdClass $data): void {
        global $DB;

        if (!\bbbext_bnx\reminders_utils::is_reminders_enabled()) {
            return;
        }

        $moduleid = $this->resolve_module_id($data);
        if ($moduleid === null) {
            return;
        }

        // Sync the reminder enabled flags via bnx_settings.
        $bnxid = $this->get_bnx_id($moduleid);
        if ($bnxid !== null) {
            $this->service->set_settings($bnxid, [
                'reminderenabled' => !empty($data->bnx_reminderenabled) ? '1' : '0',
                'remindertoguestsenabled' => !empty($data->bnx_remindertoguestsenabled) ? '1' : '0',
            ]);
        }

        // Sync timespan rows.
        if (!isset($data->bnx_paramcount)) {
            return;
        }

        $submittedtimespans = [];
        if (!empty($data->bnx_timespan)) {
            $timespans = is_array($data->bnx_timespan) ? $data->bnx_timespan : [$data->bnx_timespan];
            for ($i = 0; $i < (int)$data->bnx_paramcount; $i++) {
                if (isset($timespans[$i]) && !empty($timespans[$i])) {
                    $submittedtimespans[] = $timespans[$i];
                }
            }
        }

        $existingreminders = $DB->get_records(
            self::REMINDERS_TABLE,
            ['bigbluebuttonbnid' => $moduleid]
        );
        $existingtimespans = array_map(fn($r) => $r->timespan, $existingreminders);

        // Delete removed timespans.
        foreach ($existingreminders as $existing) {
            if (!in_array($existing->timespan, $submittedtimespans)) {
                $DB->delete_records(self::REMINDERS_TABLE, ['id' => $existing->id]);
            }
        }

        // Add new timespans.
        foreach ($submittedtimespans as $timespan) {
            if (!in_array($timespan, $existingtimespans)) {
                $DB->insert_record(self::REMINDERS_TABLE, (object) [
                    'bigbluebuttonbnid' => $moduleid,
                    'timespan' => $timespan,
                    'lastsent' => 0,
                ]);
            }
        }
    }

    /**
     * Ensure the bnx base record exists for supplied payload.
     *
     * @param stdClass $data module data payload
     * @return int|null bnx identifier when available
     */
    private function persist_bnx_record(stdClass $data): ?int {
        $moduleid = $this->resolve_module_id($data);
        if ($moduleid === null) {
            return null;
        }

        return $this->upsert_bnx_record($moduleid);
    }

    /**
     * Persist feature settings for the given bnx record.
     *
     * @param int $bnxid bnx identifier
     * @param stdClass $data module data payload
     * @return void
     */
    private function persist_settings(int $bnxid, stdClass $data): void {
        $values = $this->collect_feature_values($data);
        if (!empty($values)) {
            $this->service->set_settings($bnxid, $values);
        }
    }

    /**
     * Collect feature toggles from the submitted payload.
     *
     * @param stdClass $data module data payload
     * @return array<string, int> normalised field values keyed by setting name
     */
    private function collect_feature_values(stdClass $data): array {
        $values = [];
        foreach (self::FEATURE_FIELD_MAP as $field => $setting) {
            if (!property_exists($data, $field)) {
                continue;
            }

            $values[$setting] = $this->normalise_value($data->{$field});
        }

        return $values;
    }

    /**
     * Normalise input into a persisted integer value.
     *
     * @param mixed $value raw form value
     * @return int
     */
    private function normalise_value($value): int {
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }
        if (is_numeric($value)) {
            return (int)$value;
        }

        return empty($value) ? 0 : 1;
    }

    /**
     * Resolve the bnx base record identifier.
     *
     * @param int $moduleid module identifier
     * @return int|null
     */
    private function get_bnx_id(int $moduleid): ?int {
        global $DB;

        $record = $DB->get_record(self::BNX_TABLE, ['bigbluebuttonbnid' => $moduleid], 'id');
        return $record ? (int)$record->id : null;
    }

    /**
     * Extract a module id from supported payload shapes.
     *
     * @param stdClass $data module data payload
     * @return int|null
     */
    private function resolve_module_id(stdClass $data): ?int {
        return match (true) {
            !empty($data->id) => (int)$data->id,
            !empty($data->instance) => (int)$data->instance,
            !empty($data->bigbluebuttonbnid) => (int)$data->bigbluebuttonbnid,
            default => null,
        };
    }

    /**
     * Ensure a bnx base record exists for the module id.
     *
     * @param int $moduleid module identifier
     * @return int bnx identifier
     */
    private function upsert_bnx_record(int $moduleid): int {
        global $DB;

        $record = $DB->get_record(self::BNX_TABLE, ['bigbluebuttonbnid' => $moduleid]);
        $now = time();

        if ($record) {
            $record->timemodified = $now;
            $DB->update_record(self::BNX_TABLE, $record);
            return (int)$record->id;
        }

        return (int)$DB->insert_record(self::BNX_TABLE, (object) [
            'bigbluebuttonbnid' => $moduleid,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
    }
}
