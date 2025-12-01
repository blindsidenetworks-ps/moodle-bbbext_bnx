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
 * Definitions for the bnx settings persistence service.
 *
 * @package   bbbext_bnx
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

namespace bbbext_bnx\local\services;

/**
 * Service wrapper for BNX settings persistence.
 *
 * @package   bbbext_bnx
 */
class bnx_settings_service implements bnx_settings_service_interface {
    /**
     * Cached instance for factory.
     *
     * @var bnx_settings_service_interface|null
     */
    protected static ?bnx_settings_service_interface $service = null;

    /**
     * Get the shared service instance (factory).
     *
     * @return bnx_settings_service_interface
     */
    public static function get_service(): bnx_settings_service_interface {
        if (self::$service === null) {
            self::$service = new self();
        }
        return self::$service;
    }

    /**
     * Test hook to override the service instance.
     *
     * @param bnx_settings_service_interface|null $service
     * @return void
     */
    public static function set_service(?bnx_settings_service_interface $service = null): void {
        self::$service = $service;
    }

    /**
     * Table storing bnx extension settings.
     */
    public const BNX_SETTINGS_TABLE = 'bbbext_bnx_settings';

    /**
     * Table storing base bnx records.
     */
    private const BNX_TABLE = 'bbbext_bnx';

    /**
     * Fetch settings for a BNX record.
     *
     * @param int $bnxid bnx parent record identifier
     * @return array<string, string>
     */
    public function get_settings(int $bnxid): array {
        global $DB;
        $records = $DB->get_records(self::BNX_SETTINGS_TABLE, ['bnxid' => $bnxid], 'name ASC', 'name, value');
        $result = [];
        foreach ($records as $record) {
            $result[$record->name] = (string)$record->value;
        }
        return $result;
    }

    /**
     * Fetch a single setting value for the BNX record.
     *
     * @param int $bnxid bnx parent record identifier
     * @param string $name setting name
     * @return string|null
     */
    public function get_setting(int $bnxid, string $name): ?string {
        global $DB;
        $record = $DB->get_record(self::BNX_SETTINGS_TABLE, [
            'bnxid' => $bnxid,
            'name' => $name,
        ], 'value', IGNORE_MISSING);

        return $record ? (string)$record->value : null;
    }

    /**
     * Fetch a single setting value using the module identifier.
     *
     * @param int $moduleid module identifier
     * @param string $name setting name
     * @return string|null
     */
    public function get_setting_for_module(int $moduleid, string $name): ?string {
        $bnxid = $this->get_bnx_id_for_module($moduleid);
        if ($bnxid === null) {
            return null;
        }

        return $this->get_setting($bnxid, $name);
    }

    /**
     * Upsert multiple settings for a BNX record.
     *
     * @param int $bnxid bnx parent record identifier
     * @param array $values setting values keyed by name
     */
    public function set_settings(int $bnxid, array $values): void {
        foreach ($values as $name => $value) {
            $this->set_setting($bnxid, (string)$name, $value);
        }
    }

    /**
     * Remove all settings for a BNX record.
     *
     * @param int $bnxid bnx parent record identifier
     */
    public function delete_settings(int $bnxid): void {
        global $DB;
        $DB->delete_records(self::BNX_SETTINGS_TABLE, ['bnxid' => $bnxid]);
    }

    /**
     * Remove a specific setting entry for a BNX record.
     *
     * @param int $bnxid bnx parent record identifier
     * @param string $name setting name
     */
    public function delete_setting(int $bnxid, string $name): void {
        global $DB;
        $DB->delete_records(self::BNX_SETTINGS_TABLE, [
            'bnxid' => $bnxid,
            'name' => $name,
        ]);
    }

    /**
     * Internal helper to upsert a single setting row.
     *
     * @param int $bnxid bnx parent record identifier
     * @param string $name setting name
     * @param mixed $value raw value to persist
     */
    private function set_setting(int $bnxid, string $name, $value): void {
        global $DB;
        // Normalize to a string so we can store arbitrary data.
        $normalised = $this->normalise_value($value);
        $now = time();

        $record = $DB->get_record(self::BNX_SETTINGS_TABLE, [
            'bnxid' => $bnxid,
            'name' => $name,
        ]);

        if ($record) {
            if ((string)$record->value === (string)$normalised) {
                return;
            }
            $record->value = (string)$normalised;
            $record->timemodified = $now;
            $DB->update_record(self::BNX_SETTINGS_TABLE, $record);
            return;
        }

        $DB->insert_record(self::BNX_SETTINGS_TABLE, (object) [
            'bnxid' => $bnxid,
            'name' => $name,
            'value' => (string)$normalised,
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
    }

    /**
     * Resolve the bnx identifier for a module.
     *
     * @param int $moduleid module identifier
     * @return int|null
     */
    private function get_bnx_id_for_module(int $moduleid): ?int {
        global $DB;

        $record = $DB->get_record(self::BNX_TABLE, ['bigbluebuttonbnid' => $moduleid], 'id');
        return $record ? (int)$record->id : null;
    }

    /**
     * Normalise incoming values so they match the schema. Returns string.
     *
     * @param mixed $value raw value
     * @return string
     */
    private function normalise_value($value): string {
        if (is_string($value)) {
            return $value;
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_numeric($value)) {
            return (string)((int)$value);
        }
        // Fallback: JSON-encode arrays/objects, or cast to string.
        if (is_array($value) || is_object($value)) {
            return json_encode($value);
        }
        return (string)$value;
    }
}
