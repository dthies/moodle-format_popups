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

    document.querySelector('#format_popups_activity_content').removeEventListener('click', handleLink);
    document.querySelector('#format_popups_activity_content').addEventListener('click', handleLink);

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

    // Fix broken relative links.
    document.querySelectorAll('a').forEach(function(anchor) {
        if (anchor.getAttribute('href') && (anchor.getAttribute('href').search('http') !== 0)) {
            anchor.setAttribute('href', config.wwwroot + '/mod/choice/' + anchor.getAttribute('href'));
        }
    });
};

/**
 * Load content for internal course links
 *
 * @param {object} e event
 */
function handleLink(e) {
    'use strict';
    let anchor = e.target.closest('a');
    if (anchor) {

        let url = new URL(anchor.getAttribute('href')),
            params = url.searchParams;

        if (url.toString() && url.pathname.search('mod/choice/report.php') > 0) {
            let xhttp = new XMLHttpRequest();
            e.preventDefault();
            e.stopPropagation();
            xhttp.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    showReport(xhttp.responseText);
                }
            };
            xhttp.open('GET', url.toString(), true);
            xhttp.send();
        }

        if (url.toString() && url.pathname.search('mod/choice/view.php') > 0) {
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
}

/**
 * Show report page
 *
 * @param {string} response text
 */
const showReport = (response) => {
    'use strict';
    let container = document.createElement('div');
    container.innerHTML = response;
    container.querySelectorAll('div.downloadreport form').forEach(function(form) {
        form.setAttribute('action', config.wwwroot + '/mod/choice/' + form.getAttribute('action'));
    });
    templates.replaceNodeContents(
        '#format_popups_activity_content',
        container.querySelector('#page-content div[role="main"]').innerHTML,
        "require(['core/checkbox-toggleall'], function(ToggleAll) { ToggleAll.init(); });"
    );
};

/**
 * Handle change group selector
 *
 * @param {object} e event
 */
const changeGroup = (e) => {
    let form = e.target.closest('form');
    if (form && e.target.closest('select.custom-select')) {
        let formdata = new FormData(form),
            url = new URL(form.getAttribute('action')),
            params = new URLSearchParams(formdata);
        e.stopPropagation();
        e.preventDefault();
        if (config.wwwroot + '/mod/choice/view.php' === form.getAttribute('action')) {
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
        } else if (config.wwwroot + '/mod/choice/report.php' === url.origin + url.pathname) {
            let xhttp = new XMLHttpRequest();
            xhttp.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    showReport(xhttp.responseText);
                }
            };
            xhttp.open('POST', form.getAttribute('action'), true);
            xhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhttp.send(params.toString());
        } else {
            return;
        }
    }
};
