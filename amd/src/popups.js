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
 * Module to initialise modal with listeners
 *
 * @module     format_popups/popups
 * @copyright  2021 Daniel Thies <dethies@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import Ajax from 'core/ajax';
import config from 'core/config';
import Fragment from 'core/fragment';
import ModalEvents from 'core/modal_events';
import ModalFactory from 'core/modal_factory';
import notification from 'core/notification';
import templates from 'core/templates';
import loadChapter from 'format_popups/book';
import resize from 'format_popups/embed';

/**
 * Initialize modal and listeners
 *
 * @param {int} contextid Course context id
 * @param {int} courseid Course id
 * @param {int} displaysection Single section to display
 */
export const init = (contextid, courseid, displaysection) => {
    'use strict';

    ModalFactory.create({
        large: true,
        title: 'title',
        body: '<div id="format_popups_activity_content"></div>'
    }).then(function(modal) {
        modal.contextid = contextid;
        modal.courseid = courseid;
        modal.displaysection = displaysection;
        modal.modules = [];
        modal.modulecount = document.querySelectorAll('.activity').length;
        registerListeners.bind(modal)();

        return Ajax.call([{
            methodname: 'format_popups_get_available_mods',
            args: {
                contextid: modal.contextid
            },
            done: function(modules) {
                this.modules = modules;
            }.bind(modal),
            fail: notification.exception
        }]);
    }).fail(notification.exception);
};

/**
 * Update activities on course page and optionally manual completion
 */
function updatePage() {
    'use strict';

    Fragment.loadFragment(
        'format_popups',
        'page',
        this.contextid,
        {
            displaysection: this.displaysection
        }
    ).then(function(html, js) {
        if (!html.length) {
            return;
        }
        templates.replaceNodeContents('div.course-content', html, js);
        document.querySelectorAll('form#sectionmenu select').forEach(function(selector) {
            let form = selector.closest('form'),
                html;
            selector.removeAttribute('id');
            html = form.innerHTML;
            templates.replaceNodeContents(form, html, '');
        });
        return;
    }).fail(notification.exception);

    Ajax.call([{
        methodname: 'format_popups_get_available_mods',
        args: {
            contextid: this.contextid
        },
        done: function(modules) {
            this.modules = modules;
        }.bind(this),
        fail: notification.exception
    }]);
}

/**
 * Register listeners for modal
 *
 */
function registerListeners() {
    'use strict';

    // Open Modal when view is clicked.
    document.querySelector('body').addEventListener('click', function(e) {
        let anchor = e.target.closest('a');
        if (anchor && anchor.getAttribute('href')
            && !anchor.getAttribute('onclick')
            && anchor.getAttribute('href').match('http')
            && anchor.closest('div.course-content, #format_popups_activity_content, #courseindex-content')
        ) {
            let url = new URL(anchor.getAttribute('href')),
                id = url.searchParams.get('id');
            this.modules.forEach(function(module) {
                if ((id == module.id &&
                        !url.searchParams.get('downloadown') &&
                        config.wwwroot + '/mod/' + module.modname + '/view.php' === url.origin + url.pathname) ||
                    (url.searchParams.get('a') == module.instance &&
                        config.wwwroot + '/mod/' + module.modname + '/report.php' === url.origin + url.pathname
                            && module.modname === 'h5pactivity')
                ) {
                        e.preventDefault();
                        e.stopPropagation();
                        this.setTitle(module.title);
                        Fragment.loadFragment(
                            'format_popups',
                            'mod',
                            module.contextid,
                            {
                                jsondata: JSON.stringify(url.searchParams.toString()),
                                modname: module.modname,
                                path: url.pathname
                            }
                        ).then(
                            templates.replaceNodeContents.bind(templates, '#format_popups_activity_content')
                        ).fail(notification.exception);
                        this.show();
                }
            }.bind(this));
        }
    }.bind(this));

    // Update the page so new completion and conditions show.
    this.getRoot().on(ModalEvents.hidden, updatePage.bind(this));

    // Add event listener for file upload complete.
    document.addEventListener('focusin', function() {
        'use strict';

        // Check whether number of modules changed. if so we need to update.
        if (this.modulecount === document.querySelectorAll('.activity').length) {
            return;
        }
        this.modulecount = document.querySelectorAll('.activity').length;

        Ajax.call([{
            methodname: 'format_popups_get_available_mods',
            args: {
                contextid: this.contextid
            },
            done: function(modules) {
                this.modules = modules;
            }.bind(this),
            fail: notification.exception
        }]);
    }.bind(this));

    // Listen for manual completion update.
    document.querySelector('div.course-content').addEventListener('submit', handleCompletion.bind(this));

    // Remove module listener.
    this.getRoot().on(ModalEvents.hidden, function() {
        let content = document.querySelector('#format_popups_activity_content');
        if (content) {
            content.removeEventListener('click', loadChapter);
            content.removeEventListener('resize', resize);
        }
    });

    // Navigation links within the course page.
    document.querySelectorAll('#page-navbar, #nav-drawer, div.course-content').forEach(function(container) {
        container.addEventListener('click', function(e) {
            let anchor = e.target.closest('a') || e.target;
            if (anchor && anchor.getAttribute('href')) {
                let href = anchor.getAttribute('href');
                if (href.search(config.wwwroot + '/course/view.php') === 0) {
                    let url = new URL(href),
                        params = url.searchParams;
                    if (!params.has('sesskey') && !href.includes('#') && params.get('id') === this.courseid) {
                        this.displaysection = params.get('section');
                        updatePage.bind(this)();
                        e.preventDefault();
                        e.stopPropagation();
                    }
                }
            }
        }.bind(this));
    }.bind(this));

    // Handle form navigation on the course page.
    document.querySelector('div.course-content').addEventListener('change', function(e) {
        let form = e.target.closest('form#sectionmenu');
        if (form) {
            let formdata = new FormData(form),
                url = new URL(config.wwwroot + formdata.get('jump')),
                params = url.searchParams;
            if (params.get('id') === this.courseid) {
                this.displaysection = params.get('section');
                updatePage.bind(this)();
                e.preventDefault();
                e.stopPropagation();
            }
        }
    }.bind(this));

    // Remove original form listeners.
    document.querySelectorAll('form#sectionmenu select, form.togglecompletion').forEach(function(selector) {
        let form = selector.closest('form'),
            html;
        selector.removeAttribute('id');
        html = form.innerHTML;
        templates.replaceNodeContents(form, html, '');
    });

    // If window looses focus and and modal is empty, then close the modal. This
    // happens when a SCORM package is opened in external window.
    window.addEventListener('blur', function() {
        if (
            document.getElementById('format_popups_activity_content') &&
            window.getComputedStyle(document.getElementById('format_popups_activity_content')).height == '0px'
        ) {
           this.hide();
        }
    }.bind(this));

    this.getRoot().on(ModalEvents.hidden, templates.replaceNodeContents.bind(
        templates,
        '#format_popups_activity_content',
        '<div style="height: 275px;"></div>',
        ''
    ));
}

/**
 * Submit form and load response
 *
 * @param {object} e event
 */
function handleCompletion(e) {
    'use strict';
    let form = e.target.closest('form.togglecompletion');
    if (form) {
        let formdata = new FormData(form),
            url = new URL(form.getAttribute('action')),
            params = new URLSearchParams(formdata);

        if (config.wwwroot + '/course/togglecompletion.php' === url.origin + url.pathname) {
            let xhttp = new XMLHttpRequest(),
                spinner = document.createElement('div');
            e.preventDefault();
            e.stopPropagation();
            params.append('fromajax', 1);
            spinner.setAttribute('class', 'ajaxworking');
            form.appendChild(spinner);

            // This is not the fasted. but best code reuse.  First complete ajax request
            // for completion update ignoring result, then reload the page.
            xhttp.onreadystatechange = function(modal) {
                if (this.readyState == 4 && this.status == 200) {
                    updatePage.bind(modal)();
                }
            }.bind(xhttp, this);
            xhttp.open('POST', form.getAttribute('action'), true);
            xhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhttp.send(params.toString());
        }
    }
}
