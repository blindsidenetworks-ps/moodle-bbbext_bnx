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
 * Pagination module for the recordings table.
 *
 * @module     bbbext_bnx/recordings_pagination
 */

import {getString} from 'core/str';

/**
 * Initializes pagination functionality for the recordings table.
 */
export const setupPagination = () => {
    const tableContainer = document.querySelector('.mod_bigbluebuttonbn_recordings_table');
    if (!tableContainer) {
        return;
    }

    const rows = Array.from(tableContainer.querySelectorAll('.row.mb-3.align-items-center'));

    const firstPageBtn = document.getElementById('firstPage');
    const prevPageBtn = document.getElementById('prevPage');
    const nextPageBtn = document.getElementById('nextPage');
    const lastPageBtn = document.getElementById('lastPage');
    const pageSelect = document.getElementById('pageSelect');

    if (!firstPageBtn || !prevPageBtn || !nextPageBtn || !lastPageBtn || !pageSelect) {
        return;
    }

    const itemsPerPage = 10;
    let currentPage = 1;
    let totalPages = Math.ceil(rows.length / itemsPerPage);

    /**
     * Show the current slice of rows based on the active page.
     *
     * @param {number} page One-based page index to render
     */
    function renderTable(page) {
        let visibleIndex = 0;

        rows.forEach(row => {
            if (row.dataset.filtered === 'false') {
                row.style.display = 'none';
                return;
            }

            const start = (page - 1) * itemsPerPage;
            const end = page * itemsPerPage;

            if (visibleIndex >= start && visibleIndex < end) {
                row.style.display = 'flex';
            } else {
                row.style.display = 'none';
            }

            visibleIndex++;
        });
    }

    /**
     * Recalculate pagination state and refresh control labels.
     * @returns {Promise<void>}
     */
    async function updatePaginationControls() {
        const filteredRows = rows.filter(row => row.dataset.filtered !== 'false');
        pageSelect.innerHTML = '';

        let pageString;
        try {
            pageString = await getString('view_recording_yui_page', 'bigbluebuttonbn');
        } catch (error) {
            pageString = 'Page';
        }

        totalPages = Math.max(1, Math.ceil(filteredRows.length / itemsPerPage));

        for (let i = 1; i <= totalPages; i++) {
            const option = document.createElement('option');
            option.value = i;
            option.textContent = `${pageString} ${i}`;
            if (i === currentPage) {
                option.selected = true;
            }
            pageSelect.appendChild(option);
        }

        firstPageBtn.disabled = (currentPage === 1);
        prevPageBtn.disabled = (currentPage === 1);
        nextPageBtn.disabled = (currentPage === totalPages);
        lastPageBtn.disabled = (currentPage === totalPages);
    }

    firstPageBtn.addEventListener('click', () => {
        currentPage = 1;
        renderTable(currentPage);
        updatePaginationControls();
    });

    prevPageBtn.addEventListener('click', () => {
        if (currentPage > 1) {
            currentPage--;
            renderTable(currentPage);
            updatePaginationControls();
        }
    });

    nextPageBtn.addEventListener('click', () => {
        if (currentPage < totalPages) {
            currentPage++;
            renderTable(currentPage);
            updatePaginationControls();
        }
    });

    lastPageBtn.addEventListener('click', () => {
        currentPage = totalPages;
        renderTable(currentPage);
        updatePaginationControls();
    });

    pageSelect.addEventListener('change', (e) => {
        currentPage = parseInt(e.target.value, 10);
        renderTable(currentPage);
        updatePaginationControls();
    });

    window.updatePagination = () => {
        currentPage = 1;
        renderTable(currentPage);
        updatePaginationControls();
    };

    rows.forEach(row => {
        row.dataset.filtered = 'true';
        row.style.display = 'flex';
    });

    renderTable(currentPage);
    updatePaginationControls();
};
