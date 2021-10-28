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
 * Module to add navigation to book modal
 *
 * @module     format_popups/book
 * @copyright  2021 Daniel Thies <dethies@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
import config from 'core/config';
import Fragment from 'core/fragment';
import notification from 'core/notification';
import templates from 'core/templates';

var contextid;
/**
 * Initial listerners to handle book navigation
 *
 * @param {int} id Course module context id
 */
export const init = (id) => {
    'use strict';
    let content = document.querySelector('#format_popups_activity_content');
    contextid = id;
    content.removeEventListener('click', loadChapter);
    content.addEventListener('click', loadChapter);
    content.querySelectorAll('a').forEach(function(anchor) {
        let href = anchor.getAttribute('href');
        if (href && href.search('view.php') === 0) {
            anchor.setAttribute('href', config.wwwroot + '/mod/book/' + href);
        }
    });
    if (content.querySelector('#book_toc') && !document.querySelector('#toggletoc')) {
        templates.render('format_popups/booktoc', []).then(templates.prependNodeContents.bind(
            templates,
            content.closest('.modal-content').querySelector('.modal-title')
        )).fail(notification.exception);
    }
};

/**
 * Load chapter from navigatioin
 *
 * @param {object} e event
 */
export const loadChapter = (e) => {
    'use strict';
    let anchor = e.target.closest('a.bookprev, a.booknext, .book_toc a');

    if (e.target.closest('a.bookexit')) {
        e.preventDefault();
    }
    if (anchor) {
        let chapterid = anchor.getAttribute('data-chapterid') ||
            (new URLSearchParams(anchor.getAttribute('href'))).get('chapterid'),
            params = new URLSearchParams({
                chapterid: chapterid
            });
        if (!chapterid) {
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
                modname: 'book'
            }
        ).then(
            templates.replaceNodeContents.bind(templates, '#format_popups_activity_content')
        ).fail(notification.exception);
    }
};
