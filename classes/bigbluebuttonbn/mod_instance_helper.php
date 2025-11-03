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
 * BNX lifecycle helper.
 *
 * @package   bbbext_bnx
 */
class mod_instance_helper extends \mod_bigbluebuttonbn\local\extension\mod_instance_helper {
    private const BNX_TABLE = 'bbbext_bnx';

    public const FEATURE_FIELD_MAP = [
        'enablecam' => 'enablecam',
        'enablemic' => 'enablemic',
        'enableprivatechat' => 'enableprivatechat',
        'enablepublicchat' => 'enablepublicchat',
        'enableuserlist' => 'enableuserlist',
        'enablenote' => 'enablenotes',
    ];

    private bnx_settings_service $service;

    public function __construct() {
        $this->service = new bnx_settings_service();
    }

    public function add_instance(stdClass $bigbluebuttonbn) {
        $bnxid = $this->persist_bnx_record($bigbluebuttonbn);
        if ($bnxid !== null) {
            $this->persist_settings($bnxid, $bigbluebuttonbn);
        }
    }

    public function update_instance(stdClass $bigbluebuttonbn): void {
        $bnxid = $this->persist_bnx_record($bigbluebuttonbn);
        if ($bnxid !== null) {
            $this->persist_settings($bnxid, $bigbluebuttonbn);
        }
    }

    public function delete_instance(int $moduleid): void {
        $bnxid = $this->get_bnx_id($moduleid);
        if ($bnxid === null) {
            return;
        }

        $this->service->delete_settings($bnxid);
    }

    public function get_join_tables(): array {
        return [bnx_settings_service::BNX_SETTINGS_TABLE];
    }

    private function persist_bnx_record(stdClass $data): ?int {
        $moduleid = $this->resolve_module_id($data);
        if ($moduleid === null) {
            return null;
        }

        return $this->upsert_bnx_record($moduleid);
    }

    private function persist_settings(int $bnxid, stdClass $data): void {
        $values = $this->collect_feature_values($data);
        if (!empty($values)) {
            $this->service->set_settings($bnxid, $values);
        }
    }

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

    private function normalise_value($value): int {
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }
        if (is_numeric($value)) {
            return (int)$value;
        }

        return empty($value) ? 0 : 1;
    }

    private function get_bnx_id(int $moduleid): ?int {
        global $DB;

        $record = $DB->get_record(self::BNX_TABLE, ['bigbluebuttonbnid' => $moduleid], 'id');
        return $record ? (int)$record->id : null;
    }

    private function resolve_module_id(stdClass $data): ?int {
        return match (true) {
            !empty($data->id) => (int)$data->id,
            !empty($data->instance) => (int)$data->instance,
            !empty($data->bigbluebuttonbnid) => (int)$data->bigbluebuttonbnid,
            default => null,
        };
    }

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
