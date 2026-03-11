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

namespace bbbext_bnx\local\proxy;

use mod_bigbluebuttonbn\local\proxy\curl;

/**
 * Extension-local proxy that allows bnx to customize proxy behavior.
 *
 * By extending the core proxy we can override individual static methods such
 * as create_meeting without changing core code.
 *
 * @package   bbbext_bnx
 * @copyright 2025 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Jesus Federico  (jesus [at] blindsidenetworks [dt] com)
 */
class bigbluebutton_proxy extends \mod_bigbluebuttonbn\local\proxy\bigbluebutton_proxy {
    /**
     * Extension method that accepts an array of presentations and posts a multi-document payload.
     *
     * @param array $data
     * @param array $metadata
     * @param array|null $presentations
     * @param int|null $instanceid
     * @return array
     * @throws \mod_bigbluebuttonbn\local\exceptions\bigbluebutton_exception
     */
    public static function create_meeting_with_presentations(
        array $data,
        array $metadata,
        ?array $presentations = null,
        ?int $instanceid = null
    ): array {
        $createmeetingurl = self::action_url('create', $data, $metadata, $instanceid);

        $xml = self::request_create_meeting_xml($createmeetingurl, $presentations);

        self::assert_returned_xml($xml);

        if (empty($xml->meetingID)) {
            throw new \mod_bigbluebuttonbn\local\exceptions\bigbluebutton_exception('general_error_cannot_create_meeting');
        }

        if ((string) ($xml->hasBeenForciblyEnded ?? '') === 'true') {
            throw new \mod_bigbluebuttonbn\local\exceptions\bigbluebutton_exception('index_error_forciblyended');
        }

        return [
            'meetingID' => (string) $xml->meetingID,
            'internalMeetingID' => (string) $xml->internalMeetingID,
            'attendeePW' => (string) $xml->attendeePW,
            'moderatorPW' => (string) $xml->moderatorPW,
        ];
    }

    /**
     * Request the create-meeting XML response.
     *
     * @param string $createmeetingurl
     * @param array|null $presentations
     * @return mixed
     */
    private static function request_create_meeting_xml(string $createmeetingurl, ?array $presentations) {
        $curl = new curl();

        if (empty($presentations)) {
            return self::parse_xml_if_string($curl->get($createmeetingurl));
        }

        $payload = self::build_presentations_payload($presentations);
        return self::parse_xml_if_string($curl->post($createmeetingurl, $payload));
    }

    /**
     * Build XML payload for multiple presentations.
     *
     * @param array $presentations
     * @return string
     */
    private static function build_presentations_payload(array $presentations): string {
        $payload = "<?xml version='1.0' encoding='UTF-8'?><modules><module name='presentation'>";

        foreach ($presentations as $presentation) {
            $url = is_array($presentation) ? ($presentation['url'] ?? '') : ($presentation->url ?? '');
            if (empty($url)) {
                continue;
            }

            $name = is_array($presentation) ? ($presentation['name'] ?? '') : ($presentation->name ?? '');
            $escapedurl = htmlspecialchars($url, ENT_QUOTES | ENT_XML1, 'UTF-8');
            $escapedname = htmlspecialchars($name, ENT_QUOTES | ENT_XML1, 'UTF-8');
            $payload .= "<document url=\"{$escapedurl}\" filename=\"{$escapedname}\" />";
        }

        return $payload . "</module></modules>";
    }

    /**
     * Parse XML only when the response is a string.
     *
     * @param mixed $xml
     * @return mixed
     */
    private static function parse_xml_if_string($xml) {
        if (!is_string($xml)) {
            return $xml;
        }

        $parsed = @simplexml_load_string($xml);
        return $parsed !== false ? $parsed : $xml;
    }
}
