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
 * Module to show h5pactivity report in modal
 *
 * @module     format_popups/h5pactivity
 * @copyright  2021 Daniel Thies <dethies@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import config from 'core/config';
import Fragment from 'core/fragment';
import notification from 'core/notification';
import templates from 'core/templates';

var contextid, modname, instance;
/**
 * Initialize Choice mod actions
 *
 * @param {int} id Course module context id
 * @param {string} name Activity type
 * @param {int} instanceid H5P activity instance id
 */
export const init = (id, name, instanceid) => {
    'use strict';
    contextid = id;
    modname = name;
    instance = instanceid;

    document.querySelector('#format_popups_activity_content').removeEventListener('click', showReport);
    document.querySelector('#format_popups_activity_content').addEventListener('click', showReport);
};

/**
 * Show report page
 *
 * @param {object} e Event
 */
const showReport = (e) => {
    let anchor = e.target.closest('a');
    if (anchor) {
        let url = new URL(anchor.getAttribute('href')),
            params = url.searchParams;

        if (url.origin + url.pathname === config.wwwroot + '/mod/h5pactivity/report.php' && params.get('a') === instance) {
            e.preventDefault();
            e.stopPropagation();
            Fragment.loadFragment(
                'format_popups',
                'mod',
                contextid,
                {
                    jsondata: JSON.stringify(params.toString()),
                    modname: modname
                }
            ).then(
                templates.replaceNodeContents.bind(templates, '#format_popups_activity_content')
            ).fail(notification.exception);
        }
    }
};
