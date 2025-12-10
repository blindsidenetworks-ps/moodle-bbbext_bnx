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
 * Search filter for recordings table.
 *
 * @module     bbbext_bnx/recordings_search
 */

export const setupSearch = () => {
    const searchInput = document.getElementById('recordings-search-input');
    const searchButton = document.getElementById('recordings-search-button');
    const tableContainer = document.querySelector('.mod_bigbluebuttonbn_recordings_table');

    if (!searchInput || !searchButton || !tableContainer) {
        return;
    }

    const rows = Array.from(tableContainer.querySelectorAll('.row.mb-3.align-items-center'));

    const filterRows = () => {
        const query = searchInput.value.trim().toLowerCase();

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            const match = query === '' || text.includes(query);
            row.dataset.filtered = match ? 'true' : 'false';
        });

        window.currentPage = 1;
        if (typeof window.updatePagination === 'function') {
            window.updatePagination();
        }
    };

    searchButton.addEventListener('click', () => {
        filterRows();
    });

    searchInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            filterRows();
        }
    });

    rows.forEach(row => {
        row.dataset.filtered = 'true';
    });
};
