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
 * External service to fetch a list of recordings from the BBB service.
 *
 * @package   bbbext_bnx
 * @category  external
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */

namespace bbbext_bnx\external;

use bbbext_bnx\local\bigbluebutton\recordings\recording_data;
use bbbext_bnx\recording;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use core_external\external_warnings;
use mod_bigbluebuttonbn\instance;

/**
 * External service to fetch recordings available for import.
 *
 * @package   bbbext_bnx
 * @category  external
 */
class get_recordings_to_import extends external_api {
    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'destinationinstanceid' => new external_value(
                PARAM_INT,
                'Target BBB activity ID for import operations.',
                VALUE_REQUIRED
            ),
            'sourcebigbluebuttonbnid' => new external_value(
                PARAM_INT,
                'Source BBB activity ID to filter by.',
                VALUE_DEFAULT,
                0
            ),
            'sourcecourseid' => new external_value(
                PARAM_INT,
                'Source course ID to filter by.',
                VALUE_DEFAULT,
                0
            ),
            'tools' => new external_value(
                PARAM_RAW,
                'Enabled tools list.',
                VALUE_DEFAULT,
                'protect,unprotect,publish,unpublish,delete'
            ),
            'groupid' => new external_value(PARAM_INT, 'Group ID', VALUE_DEFAULT, null),
        ]);
    }

    /**
     * Build the recordings listing for the import modal.
     *
     * @param int $destinstanceid destination activity identifier
     * @param int|null $sourceinstanceid source activity identifier to filter
     * @param int|null $sourcecourseid source course identifier to filter
     * @param string|null $tools comma separated tool list
     * @param int|null $groupid optional group identifier
     * @return array
     */
    public static function execute(
        int $destinstanceid,
        ?int $sourceinstanceid = 0,
        ?int $sourcecourseid = 0,
        ?string $tools = 'protect,unprotect,publish,unpublish,delete',
        ?int $groupid = null
    ): array {
        global $USER;

        $params = self::normalise_parameters(
            $destinstanceid,
            $sourceinstanceid,
            $sourcecourseid,
            $tools,
            $groupid
        );

        $toolnames = self::parse_tools($params['tools']);
        $sourceinstance = self::resolve_source_instance($params['sourcebigbluebuttonbnid']);
        $destinstance = self::resolve_destination_instance($params['destinationinstanceid']);

        self::assert_group_access($destinstance, $USER, $params['groupid']);
        if (!empty($params['groupid'])) {
            $destinstance->set_group_id($params['groupid']);
        }

        $recordings = self::fetch_candidate_recordings(
            $sourceinstance,
            (int) $params['sourcecourseid'],
            $destinstance,
            $params['sourcebigbluebuttonbnid']
        );

        $recordings = self::filter_imported_recordings($recordings, $destinstance);

        return [
            'status' => true,
            'warnings' => [],
            'tabledata' => recording_data::get_recording_table(
                $recordings,
                $toolnames,
                $sourceinstance,
                (int) $params['sourcecourseid']
            ),
        ];
    }

    /**
     * Describe the return structure of the external service.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'status' => new external_value(PARAM_BOOL, 'Whether the fetch was successful'),
            'tabledata' => new external_single_structure([
                'activity' => new external_value(PARAM_ALPHANUMEXT),
                'ping_interval' => new external_value(PARAM_INT),
                'locale' => new external_value(PARAM_TEXT),
                'profile_features' => new external_multiple_structure(new external_value(PARAM_TEXT)),
                'columns' => new external_multiple_structure(new external_single_structure([
                    'key' => new external_value(PARAM_ALPHA),
                    'label' => new external_value(PARAM_TEXT),
                    'width' => new external_value(PARAM_ALPHANUMEXT),
                    'type' => new external_value(PARAM_ALPHANUMEXT, 'Column type', VALUE_OPTIONAL),
                    'sortable' => new external_value(PARAM_BOOL, 'Whether this column is sortable', VALUE_OPTIONAL, false),
                    'allowHTML' => new external_value(PARAM_BOOL, 'Whether this column contains HTML', VALUE_OPTIONAL, false),
                    'formatter' => new external_value(PARAM_ALPHANUMEXT, 'Formatter name', VALUE_OPTIONAL),
                ])),
                'data' => new external_value(PARAM_RAW),
            ], '', VALUE_OPTIONAL),
            'warnings' => new external_warnings(),
        ]);
    }

    /**
     * Validate and normalise incoming parameters.
     *
     * @param int $destinstanceid
     * @param int|null $sourceinstanceid
     * @param int|null $sourcecourseid
     * @param string|null $tools
     * @param int|null $groupid
     * @return array
     */
    private static function normalise_parameters(
        int $destinstanceid,
        ?int $sourceinstanceid,
        ?int $sourcecourseid,
        ?string $tools,
        ?int $groupid
    ): array {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), [
            'destinationinstanceid' => $destinstanceid,
            'sourcebigbluebuttonbnid' => $sourceinstanceid,
            'sourcecourseid' => $sourcecourseid,
            'tools' => $tools,
            'groupid' => $groupid,
        ]);

        if (!empty($params['sourcecourseid'])) {
            $DB->get_record('course', ['id' => $params['sourcecourseid']], 'id', MUST_EXIST);
        }

        return $params;
    }

    /**
     * Turn the provided tools string into an array list.
     *
     * @param string|null $tools
     * @return array
     */
    private static function parse_tools(?string $tools): array {
        $toolstring = $tools ?? 'protect,unprotect,publish,unpublish,delete';
        return array_filter(array_map('trim', explode(',', $toolstring)));
    }

    /**
     * Fetch the source instance if provided.
     *
     * @param int|null $sourceinstanceid Source instance identifier
     * @return instance|null
     * @throws \invalid_parameter_exception
     */
    private static function resolve_source_instance(?int $sourceinstanceid): ?instance {
        if (empty($sourceinstanceid)) {
            return null;
        }

        $sourceinstance = instance::get_from_instanceid($sourceinstanceid);
        if (!$sourceinstance) {
            throw new \invalid_parameter_exception('Source BigBlueButton ID is invalid');
        }

        self::validate_context($sourceinstance->get_context());

        return $sourceinstance;
    }

    /**
     * Resolve the destination instance for the import workflow.
     *
     * @param int $destinstanceid Destination instance identifier
     * @return instance
     */
    private static function resolve_destination_instance(int $destinstanceid): instance {
        $destinstance = instance::get_from_instanceid($destinstanceid);
        self::validate_context($destinstance->get_context());
        return $destinstance;
    }

    /**
     * Ensure the current user can access the provided group.
     *
     * @param instance $destinstance Destination instance
     * @param \stdClass $user User object
     * @param int|null $groupid Group ID
     * @throws \invalid_parameter_exception
     */
    private static function assert_group_access(instance $destinstance, \stdClass $user, ?int $groupid): void {
        if (!$destinstance->user_has_group_access($user, $groupid)) {
            throw new \invalid_parameter_exception('Invalid group for this user ' . $groupid);
        }
    }

    /**
     * Gather the initial list of recordings candidates.
     *
     * @param instance|null $sourceinstance Source instance to filter by
     * @param int $sourcecourseid Source course to filter by
     * @param instance $destinstance Destination instance
     * @param int|null $sourceinstanceid Source instance identifier
     * @return recording[]
     */
    private static function fetch_candidate_recordings(
        ?instance $sourceinstance,
        int $sourcecourseid,
        instance $destinstance,
        ?int $sourceinstanceid
    ): array {
        $excludedids = [$destinstance->get_instance_id()];

        if ($sourceinstance) {
            return recording::get_recordings($sourceinstance, $excludedids);
        }

        $includeall = ($sourcecourseid === 0 || $sourceinstanceid === 0);

        return recording::get_recordings_for_course(
            $sourcecourseid,
            $excludedids,
            true,
            false,
            $includeall,
            $includeall
        );
    }

    /**
     * Remove recordings already imported into the destination instance.
     *
     * @param recording[] $recordings
     * @param instance $destinstance
     * @return recording[]
     */
    private static function filter_imported_recordings(array $recordings, instance $destinstance): array {
        $importedrecordings = recording::get_recordings_for_instance($destinstance, true, true);
        if (empty($importedrecordings)) {
            return $recordings;
        }

        $importedids = [];
        foreach ($importedrecordings as $importedrecording) {
            $importedids[$importedrecording->get('recordingid')] = true;
        }

        return array_values(array_filter($recordings, static function ($recording) use ($importedids) {
            return empty($importedids[$recording->get('recordingid')]);
        }));
    }
}
