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
 * Repository to perform WS calls for mod_bigbluebuttonbn.
 *
 * @module      bbbext_bnx/repository
 */

import {call as fetchMany} from 'core/ajax';

export const updateRecording = args => fetchMany([
    {
        methodname: 'mod_bigbluebuttonbn_update_recording',
        args,
    }
])[0];

export const endMeeting = (bigbluebuttonbnid, groupid) => fetchMany([
    {
        methodname: 'mod_bigbluebuttonbn_end_meeting',
        args: {
            bigbluebuttonbnid,
            groupid
        },
    }
])[0];

export const completionValidate = (bigbluebuttonbnid) => fetchMany([
    {
        methodname: 'mod_bigbluebuttonbn_completion_validate',
        args: {
            bigbluebuttonbnid
        },
    }
])[0];

export const getMeetingInfo = (bigbluebuttonbnid, groupid, updatecache = false) => fetchMany([
    {
        methodname: 'mod_bigbluebuttonbn_meeting_info',
        args: {
            bigbluebuttonbnid,
            groupid,
            updatecache,
        },
    }
])[0];
