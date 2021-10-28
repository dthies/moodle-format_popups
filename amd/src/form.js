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
 * Module to add handlers for forms to modal content
 *
 * @module     format_popups/form
 * @copyright  2021 Daniel Thies <dethies@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import config from 'core/config';
import Fragment from 'core/fragment';
import notification from 'core/notification';
import templates from 'core/templates';
var contextid, modname;

/**
 * Listen for form submisson
 *
 * @param {int} id Course module context id
 * @param {string} name Modeule type
 */
export const init = (id, name) => {
    'use strict';
    contextid = id;
    modname = name;
    document.querySelector('#format_popups_activity_content').removeEventListener('submit',
        handleSubmit
    );
    document.querySelector('#format_popups_activity_content').removeEventListener('submit',
        handleSubmit,
        {capture: true}
    );
    document.querySelector('#format_popups_activity_content').addEventListener('submit',
        handleSubmit,
        {capture: true}
    );
};

/**
 * Submit form and load response
 *
 * @param {object} e event
 */
const handleSubmit = (e) => {
    'use strict';
    let form = e.target.closest('form');
    if (form) {
        let formdata = new FormData(form),
            params = new URLSearchParams(formdata),
            data = params.toString();
        if ((config.wwwroot + '/mod/' + modname + '/view.php' !== form.getAttribute('action'))
            && (config.wwwroot + '/mod/scorm/player.php' !== form.getAttribute('action'))) {
            return;
        }

        e.preventDefault();
        e.stopPropagation();

        Fragment.loadFragment(
            'format_popups',
            'mod',
            contextid,
            {
                jsondata: JSON.stringify(data),
                modname: modname
            }
        ).then(
            templates.replaceNodeContents.bind(templates, '#format_popups_activity_content')
        ).fail(notification.exception);
    }
};
