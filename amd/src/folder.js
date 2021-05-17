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
 * Module to display files in a folder in modal
 *
 * @module     format_popups/folder
 * @package    format_popups
 * @copyright  2021 Daniel Thies <dethies@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import config from 'core/config';
import notification from 'core/notification';
import templates from 'core/templates';

var cmid, contextid;
/**
 * Initialise listeners to folder resource
 *
 * @param {int} id Course module context id
 */
export const init = (id, cm) => {
    'use strict';
    contextid = id;
    cmid = cm;
    document.querySelector('#format_popups_activity_content').removeEventListener('click', embedFiles);
    document.querySelector('#format_popups_activity_content').addEventListener('click', embedFiles);
};

/**
 * Display resource when selected
 *
 * @param {object} e event
 */
const embedFiles = (e) => {
    'use strict';
    let anchor = e.target.closest('div.foldertree a');

    if (anchor) {
        let url = new URL(anchor.getAttribute('href')),
            returnurl = new URL(config.wwwroot  + '/mod/folder/view.php'),
            path = url.pathname.split('/').slice(-5);
        if (!url.searchParams.get('forcedownload') && path[0] == contextid) {
            let isimage = path[4].search('.') !== -1 &&
                ['gif', 'jpg', 'jpeg', 'png', 'svg'].includes(path[4].split('.').pop());
            e.preventDefault();
            e.stopPropagation();
            returnurl.searchParams.append('id', cmid);
            templates.render(
                'format_popups/embedfile',
                {
                    heading: decodeURI(path[4]),
                    image: isimage,
                    returnurl: returnurl.toString(),
                    url: url.toString(),
                    params: [
                        {
                            name: 'forcedownload',
                            value: 1
                        }
                    ]
                }
            ).then(
                templates.replaceNodeContents.bind(templates, '#format_popups_activity_content')
            ).fail(notification.exception);
        }
    }
};
