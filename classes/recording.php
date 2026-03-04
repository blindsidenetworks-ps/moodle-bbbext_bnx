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

use context_course;
use mod_bigbluebuttonbn\instance;
use mod_bigbluebuttonbn\local\proxy\recording_proxy;
use mod_bigbluebuttonbn\recording as base_recording;

/**
 * Recording helper wrapper for BNX.
 *
 * @package    bbbext_bnx
 * @copyright  2025 onwards, Blindside Networks Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class recording extends base_recording {
    /**
     * Description is stored in the metadata, so we sometimes need to do some conversion.
     */
    protected function get_description() {
        return trim((string) $this->metadata_get('description'));
    }

    /**
     * Name is stored in the metadata.
     */
    protected function get_name() {
        return trim((string) $this->metadata_get('name'));
    }

    /**
     * Get recordings for this instance.
     *
     * @param instance $instance
     * @param string[] $excludedid
     * @param bool $viewdeleted view deleted recordings?
     * @return recording[]
     */
    // phpcs:ignore moodle.Commenting.DocblockTagSniff.InvalidTag
    /**
     * Retrieve recordings for a specific instance or its course scope.
     *
     * @param instance $instance BigBlueButton instance
     * @param array $excludedid Instance ids excluded from results
     * @param bool $viewdeleted Include deleted recordings flag
     * @return recording[]
     */
    public static function get_recordings(instance $instance, array $excludedid = [], bool $viewdeleted = false): array {
        if ($instance->is_feature_enabled('showroom')) {
            return self::get_recordings_for_instance(
                $instance,
                $instance->is_feature_enabled('importrecordings'),
                $instance->get_instance_var('recordings_imported')
            );
        }

        return self::get_recordings_for_course(
            $instance->get_course_id(),
            $excludedid,
            $instance->is_feature_enabled('importrecordings'),
            false,
            $viewdeleted
        );
    }

    /**
     * Helper function to retrieve recordings from the BigBlueButton.
     *
     * @param instance $instance
     * @param bool $includeimported
     * @param bool $onlyimported
     * @param bool $filterbygroups
     * @return recording[]
     */
    // phpcs:ignore moodle.Commenting.DocblockTagSniff.InvalidTag
    /**
     * Retrieve recordings belonging to a single activity instance.
     *
     * @param instance $instance BigBlueButton instance
     * @param bool $includeimported Include imported recordings flag
     * @param bool $onlyimported Restrict to imported recordings
     * @param bool $filterbygroups Restrict results by group membership
     * @return recording[]
     */
    public static function get_recordings_for_instance(
        instance $instance,
        bool $includeimported = false,
        bool $onlyimported = false,
        bool $filterbygroups = true
    ): array {
        [$selects, $params] = static::get_basic_select_from_parameters(false, $includeimported, $onlyimported);
        $selects[] = "bigbluebuttonbnid = :bbbid";
        $params['bbbid'] = $instance->get_instance_id();
        $groupmode = groups_get_activity_groupmode($instance->get_cm());
        $context = $instance->get_context();
        if ($groupmode && $filterbygroups) {
            [$groupselects, $groupparams] = static::get_select_for_group(
                $groupmode,
                $context,
                $instance->get_course_id(),
                $instance->get_group_id(),
                $instance->get_cm()->groupingid
            );
            if ($groupselects) {
                $selects[] = $groupselects;
                $params = array_merge_recursive($params, $groupparams);
            }
        }

        $recordings = static::fetch_records($selects, $params);
        foreach ($recordings as $recording) {
            $recording->instance = $instance;
        }

        return $recordings;
    }

    /**
     * Helper function to retrieve recordings from a given course.
     *
     * @param int $courseid id for a course record or null
     * @param array $excludedinstanceid exclude recordings from instance ids
     * @param bool $includeimported
     * @param bool $onlyimported
     * @param bool $includedeleted
     * @param bool $onlydeleted
     * @return recording[]
     */
    public static function get_recordings_for_course(
        int $courseid,
        array $excludedinstanceid = [],
        bool $includeimported = false,
        bool $onlyimported = false,
        bool $includedeleted = false,
        bool $onlydeleted = false
    ): array {
        global $DB;

        [$selects, $params] = static::get_basic_select_from_parameters(
            $includedeleted,
            $includeimported,
            $onlyimported,
            $onlydeleted
        );
        if ($courseid) {
            $selects[] = "courseid = :courseid";
            $params['courseid'] = $courseid;
            $course = $DB->get_record('course', ['id' => $courseid]);
            $groupmode = groups_get_course_groupmode($course);
            $context = context_course::instance($courseid);
        } else {
            $context = \context_system::instance();
            $groupmode = NOGROUPS;
        }

        if ($groupmode) {
            [$groupselects, $groupparams] = static::get_select_for_group($groupmode, $context, $course->id ?? 0);
            if ($groupselects) {
                $selects[] = $groupselects;
                $params = array_merge($params, $groupparams);
            }
        }

        if ($excludedinstanceid) {
            [$sqlexcluded, $paramexcluded] = $DB->get_in_or_equal($excludedinstanceid, SQL_PARAMS_NAMED, 'param', false);
            $selects[] = "bigbluebuttonbnid {$sqlexcluded}";
            $params = array_merge($params, $paramexcluded);
        }

        return static::fetch_records($selects, $params);
    }

    /**
     * Fetch all records which match the specified parameters, including all metadata that relates to them.
     *
     * @param array $selects
     * @param array $params
     * @return recording[]
     */
    protected static function fetch_records(array $selects, array $params): array {
        global $DB, $CFG;

        $withindays = time() - (static::RECORDING_TIME_LIMIT_DAYS * DAYSECS);
        $recordingsort = $CFG->bigbluebuttonbn_recordings_asc_sort ? 'timecreated ASC' : 'timecreated DESC';

        $recordings = $DB->get_records_select(
            static::TABLE,
            implode(' AND ', $selects),
            $params,
            $recordingsort
        );

        $imported = array_filter($recordings, function ($recording) {
            return isset($recording->imported) && (int) $recording->imported === 1;
        });

        $nonimported = array_filter($recordings, function ($recording) {
            return isset($recording->imported) && (int) $recording->imported === 0;
        });

        $recordingids = array_values(array_map(function ($recording) {
            return $recording->recordingid;
        }, $nonimported));

        $metadatas = recording_proxy::fetch_recordings($recordingids);
        $failedids = recording_proxy::fetch_missing_recordings($recordingids);

        foreach ($imported as $recording) {
            if (isset($recording->recordingid, $recording->importeddata)) {
                $decoded = json_decode($recording->importeddata, true);
                if (is_array($decoded)) {
                    $metadatas[$recording->recordingid] = $decoded;
                }
            }
        }

        return array_filter(array_map(function ($recording) use ($metadatas, $withindays, $failedids) {
            if (!array_key_exists($recording->recordingid, $metadatas)) {
                if (!in_array($recording->recordingid, $failedids) && $withindays > $recording->timecreated) {
                    $rec = new static(0, $recording, null);
                    $rec->set_status(static::RECORDING_STATUS_DISMISSED);
                }
                return false;
            }
            $metadata = $metadatas[$recording->recordingid];
            if (($metadata['state'] ?? null) === 'deleted') {
                $rec = new static(0, $recording, null);
                $rec->set_status(static::RECORDING_STATUS_DELETED);
                return false;
            }
            return new static(0, $recording, $metadata);
        }, $recordings));
    }

    /**
     * Before doing the database update, let's check if we need to update metadata.
     */
    protected function before_update() {
        if (!$this->metadatachanged) {
            return;
        }

        $recordingid = $this->get('recordingid');

        if (!$this->get('imported')) {
            $metadata = $this->fetch_metadata();
            if ($metadata) {
                recording_proxy::update_recording($recordingid, $metadata);
            }
        } else {
            $metadata = $this->metadata;
            if ($metadata) {
                $this->set('importeddata', json_encode($metadata, JSON_UNESCAPED_SLASHES));
            }
        }

        $this->metadatachanged = false;
    }

    /**
     * Set locally stored metadata from this instance.
     *
     * @param string $fieldname
     * @param mixed $value
     */
    protected function metadata_set($fieldname, $value) {
        $this->metadatachanged = true;
        $metadata = $this->fetch_metadata();
        $possiblesourcename = $this->get_possible_meta_name_for_source($fieldname, $metadata);
        $metadata[$possiblesourcename] = $value;
        $this->metadata = $metadata;
    }

    /**
     * Get select for given group mode and context.
     *
     * @param int $groupmode
     * @param context $context
     * @param int $courseid
     * @param int $groupid
     * @param int $groupingid
     * @return array
     */
    protected static function get_select_for_group($groupmode, $context, $courseid, $groupid = 0, $groupingid = 0): array {
        global $DB, $USER;

        $selects = [];
        $params = [];
        if ($groupmode) {
            if ($groupid === 0) {
                $selects[] = 'groupid = :groupid';
                $params['groupid'] = 0;
                return [implode(' AND ', $selects), $params];
            }
            $accessallgroups = has_capability('moodle/site:accessallgroups', $context) || $groupmode == VISIBLEGROUPS;
            if ($accessallgroups) {
                if ($context instanceof \context_module) {
                    $allowedgroups = groups_get_all_groups($courseid, 0, $groupingid);
                } else {
                    $allowedgroups = groups_get_all_groups($courseid);
                }
            } else {
                if ($context instanceof \context_module) {
                    $allowedgroups = groups_get_all_groups($courseid, $USER->id, $groupingid);
                } else {
                    $allowedgroups = groups_get_all_groups($courseid, $USER->id);
                }
            }
            $allowedgroupsid = array_map(function ($g) {
                return $g->id;
            }, $allowedgroups);
            if ($groupid || empty($allowedgroups)) {
                $selects[] = 'groupid = :groupid';
                $params['groupid'] = ($groupid && in_array($groupid, $allowedgroupsid)) ? $groupid : 0;
            } else {
                if ($accessallgroups) {
                    $allowedgroupsid[] = 0;
                }
                [$groupselects, $groupparams] = $DB->get_in_or_equal($allowedgroupsid, SQL_PARAMS_NAMED);
                $selects[] = 'groupid ' . $groupselects;
                $params = array_merge_recursive($params, $groupparams);
            }
        }
        return [
            implode(' AND ', $selects),
            $params,
        ];
    }
}
