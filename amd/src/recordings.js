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

/**
 * Enhance recording preview thumbnails with progressive disclosure behaviour.
 */
const initPreviewEnhancements = () => {
    const previewContainers = document.querySelectorAll('[data-bnx-preview]');

    previewContainers.forEach(async(container) => {
        if (container.dataset.previewReady === '1') {
            return;
        }

        container.querySelectorAll('.text-center.text-muted.small').forEach((help) => {
            const helpContainer = help.closest('.row');
            if (helpContainer) {
                helpContainer.remove();
            } else {
                help.remove();
            }
        });

        const thumbnails = container.querySelectorAll('img.recording-thumbnail');
        if (thumbnails.length <= 1) {
            container.dataset.previewReady = '1';
            return;
        }

        container.dataset.previewReady = '1';
        container.classList.add('d-inline-flex', 'flex-column');

        const baseId = container.querySelector('[id]')?.id || `bnx-preview-${Math.random().toString(36).slice(2)}`;
        const extraWrapper = document.createElement('div');
        extraWrapper.classList.add('bnx-preview-more', 'd-flex', 'flex-wrap', 'mt-2', 'd-none');
        extraWrapper.setAttribute('hidden', '');
        extraWrapper.setAttribute('aria-hidden', 'true');
        extraWrapper.id = `${baseId}-thumbnails`;

        const toggleButton = document.createElement('button');
        toggleButton.type = 'button';
        toggleButton.classList.add('btn', 'btn-link', 'btn-sm', 'p-0', 'ml-1', 'bnx-preview-toggle');

        const restCount = thumbnails.length - 1;
        toggleButton.textContent = `+${restCount}`;
        toggleButton.setAttribute('aria-expanded', 'false');
        toggleButton.setAttribute('aria-controls', extraWrapper.id);

        try {
            const labelKey = restCount === 1 ? 'preview_toggle_label_singular' : 'preview_toggle_label_plural';
            const [openLabel, closeLabel] = await Promise.all([
                getString(labelKey, 'bbbext_bnx', restCount),
                getString('preview_toggle_label_close', 'bbbext_bnx')
            ]);
            toggleButton.dataset.labelOpen = openLabel;
            toggleButton.dataset.labelClose = closeLabel;
            toggleButton.setAttribute('aria-label', openLabel);
            toggleButton.setAttribute('title', openLabel);
        } catch (error) {
            // Strings unavailable – continue with default attributes.
        }

        const thumbnailArray = Array.from(thumbnails);
        const primaryThumbnail = thumbnailArray[0];
        primaryThumbnail.dataset.previewIndex = '0';

        const parentRow = primaryThumbnail.closest('.row') ?? container;

        thumbnailArray.slice(1).forEach((thumb, index) => {
            thumb.dataset.previewIndex = String(index + 1);
            extraWrapper.appendChild(thumb);
        });

        parentRow.appendChild(extraWrapper);
        primaryThumbnail.insertAdjacentElement('afterend', toggleButton);

        const openPreviews = () => {
            if (!extraWrapper.classList.contains('d-none')) {
                return;
            }
            extraWrapper.classList.remove('d-none');
            extraWrapper.removeAttribute('hidden');
            extraWrapper.setAttribute('aria-hidden', 'false');
            toggleButton.setAttribute('aria-expanded', 'true');
            if (toggleButton.dataset.labelClose) {
                toggleButton.setAttribute('aria-label', toggleButton.dataset.labelClose);
                toggleButton.setAttribute('title', toggleButton.dataset.labelClose);
            }
        };

        const closePreviews = () => {
            if (extraWrapper.classList.contains('d-none')) {
                return;
            }
            extraWrapper.classList.add('d-none');
            extraWrapper.setAttribute('hidden', '');
            extraWrapper.setAttribute('aria-hidden', 'true');
            toggleButton.setAttribute('aria-expanded', 'false');
            if (toggleButton.dataset.labelOpen) {
                toggleButton.setAttribute('aria-label', toggleButton.dataset.labelOpen);
                toggleButton.setAttribute('title', toggleButton.dataset.labelOpen);
            }
        };

        toggleButton.addEventListener('click', (event) => {
            event.preventDefault();
            if (extraWrapper.classList.contains('d-none')) {
                openPreviews();
            } else {
                closePreviews();
            }
        });

        toggleButton.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closePreviews();
            }
        });

        [primaryThumbnail, toggleButton].forEach((element) => {
            element.addEventListener('mouseenter', openPreviews);
            element.addEventListener('focus', openPreviews);
        });

        container.addEventListener('mouseleave', closePreviews);
        container.addEventListener('focusout', (event) => {
            if (!container.contains(event.relatedTarget)) {
                closePreviews();
            }
        });
    });
};

setupTableInteractions();
setupPagination();
initPreviewEnhancements();
