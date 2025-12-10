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
 * JS for handling actions in the overridden recordings table.
 *
 * @module     bbbext_bnx/recordings
 */

import * as repository from './repository';
import {exception as displayException, saveCancelPromise} from 'core/notification';
import {getString} from 'core/str';
import {sortTable} from './recordings_sorting';
import {setupPagination} from './recordings_pagination';

/**
 * Handles an action (e.g., delete, publish, unpublish, lock, etc.) for a recording.
 *
 * @param {HTMLElement} element - The clicked action button.
 * @returns {Promise}
 */
const requestPlainAction = async(element) => {
    const getDataFromAction = (element, dataType) => {
        const dataElement = element.closest(`[data-${dataType}]`);
        return dataElement ? dataElement.dataset[dataType] : null;
    };

    const elementData = element.dataset;
    const payload = {
        bigbluebuttonbnid: getDataFromAction(element, 'bbbid'),
        recordingid: getDataFromAction(element, 'recordingid'),
        additionaloptions: getDataFromAction(element, 'additionaloptions'),
        action: elementData.action,
    };

    if (!payload.additionaloptions) {
        payload.additionaloptions = {};
    }
    if (elementData.action === 'import') {
        payload.additionaloptions.sourceid = getDataFromAction(element, 'source-instance-id') || 0;
        payload.additionaloptions.bbbcourseid = getDataFromAction(element, 'source-course-id') || 0;
    }
    payload.additionaloptions = JSON.stringify(payload.additionaloptions);

    if (element.dataset.requireConfirmation === "1") {
        try {
            await saveCancelPromise(
                getString('confirm'),
                await getRecordingConfirmationMessage(payload),
                getString('ok', 'moodle'),
            );
        } catch {
            return Promise.resolve();
        }
    }

    return repository.updateRecording(payload)
        .then(() => refreshPlainTable())
        .catch(displayException);
};

/**
 * Generates a confirmation message for recording actions.
 *
 * @param {Object} data - The recording action data.
 * @returns {Promise<string>}
 */
const getRecordingConfirmationMessage = async(data) => {
    const playbackElement = document.querySelector(`#playbacks-${data.recordingid}`);
    if (!playbackElement) {
        return getString(`view_recording_${data.action}_confirmation`, 'bigbluebuttonbn');
    }

    const recordingType = await getString(
        playbackElement.dataset.imported === 'true' ? 'view_recording_link' : 'view_recording',
        'bigbluebuttonbn'
    );

    const confirmation = await getString(
        `view_recording_${data.action}_confirmation`,
        'bigbluebuttonbn',
        recordingType
    );

    if (data.action === 'import') {
        return confirmation;
    }

    const associatedLinkCount = document.querySelector(`a#recording-${data.action}-${data.recordingid}`)?.dataset?.links;
    if (!associatedLinkCount || associatedLinkCount === "0") {
        return confirmation;
    }

    const confirmationWarning = await getString(
        associatedLinkCount === "1"
            ? `view_recording_${data.action}_confirmation_warning_p`
            : `view_recording_${data.action}_confirmation_warning_s`,
        'bigbluebuttonbn',
        associatedLinkCount
    );

    return `${confirmationWarning}\n\n${confirmation}`;
};

/**
 * Refreshes the plain recordings table by reloading the page.
 */
const refreshPlainTable = () => {
    const refreshUrl = document.getElementById('bigbluebuttonbn_recordings_table')?.dataset?.refreshUrl;
    if (refreshUrl) {
        window.location.href = refreshUrl;
    } else {
        window.location.href = window.location.origin + window.location.pathname + window.location.search;
    }
};

/**
 * Registers event listeners for table interactions.
 */
const setupTableInteractions = () => {
    document.addEventListener('click', (e) => {
        const actionButton = e.target.closest('.action-icon');
        if (actionButton) {
            e.preventDefault();
            requestPlainAction(actionButton);
            return;
        }

        const sortableHeader = e.target.closest('.sortable-header');
        if (sortableHeader) {
            e.preventDefault();
            sortTable(sortableHeader.dataset.sort);
        }
    });
};

setupTableInteractions();
setupPagination();
