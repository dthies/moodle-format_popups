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
 * Module to add navigation to wiki modal
 *
 * @module     format_popups/wiki
 * @copyright  2021 Daniel Thies <dethies@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import Fragment from 'core/fragment';
import notification from 'core/notification';
import templates from 'core/templates';

var contextid;
/**
 * Initial listerners to handle wiki navigation
 *
 * @param {int} id Course module context id
 */
export const init = (id) => {
    'use strict';
    const content = document.querySelector('#format_popups_activity_content');
    contextid = id;
    content.removeEventListener('click', loadPage);
    content.addEventListener('click', loadPage);
};

/**
 * Load page from navigatoin
 *
 * @param {object} e event
 */
export const loadPage = (e) => {
    'use strict';
    const anchor = e.target.closest('a');

    if (anchor && anchor.href) {
        const url = new URL(anchor.href);
        const pageid = url.searchParams.get('pageid'),
            params = new URLSearchParams({
                pageid: pageid
            });
        if (!pageid || (url.pathname.substr(-18) != '/mod/wiki/view.php')) {
            return;
        }
        e.preventDefault();
        e.stopPropagation();
        Fragment.loadFragment(
            'format_popups',
            'mod',
            contextid,
            {
                jsondata: JSON.stringify(params.toString()),
                modname: 'wiki'
            }
        ).then(
            templates.replaceNodeContents.bind(templates, '#format_popups_activity_content')
        ).fail(notification.exception);
    }
};
