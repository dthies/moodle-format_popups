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
 * Module to add navigation to choice modal
 *
 * @module     format_popups/choice
 * @copyright  2021 Daniel Thies <dethies@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import config from 'core/config';
import Fragment from 'core/fragment';
import notification from 'core/notification';
import templates from 'core/templates';

var contextid, modname;

/**
 * Initialize Choice mod actions
 *
 * @param {int} id Course module context id
 * @param {string} name Activity type
 */
export const init = (id, name) => {
    'use strict';
    contextid = id;
    modname = name;

    // Disable handler for  group selection form.
    document.querySelectorAll('form#selectgroup select').forEach(function(select) {
        let id = select.getAttribute('id'),
            form = select.closest('form'),
            html;
        if (id) {
            let label = document.querySelector('label[for="' + id + '"]');
            select.setAttribute(id, id + '_popup');
            if (label) {
                label.setAttribute('for', id + '_popup');
            }
        }
        html = form.innerHTML;
        templates.replaceNodeContents('form#selectgroup', html, '');
    });
    document.querySelector('#format_popups_activity_content').removeEventListener('change', changeGroup, {capture: true});
    document.querySelector('#format_popups_activity_content').addEventListener('change', changeGroup, {capture: true});
};

/**
 * Handle change group selector
 *
 * @param {object} e event
 */
const changeGroup = async(e) => {
    let form = e.target.closest('form');
    if (form && e.target.closest('select.custom-select')) {
        let formdata = new FormData(form),
            params = new URLSearchParams(formdata);
        e.stopPropagation();
        e.preventDefault();
        if (config.wwwroot + '/mod/plenum/view.php' !== form.getAttribute('action')) {
            return;
        }
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
};
