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
 * Module to resize scorm activitiy to fit window
 *
 * @module     format_popups/scorm
 * @copyright  2021 Daniel Thies <dethies@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
var maxheight;

/**
 * Properly size scorm iframe
 */
export const init = () => {
    'use strict';

    let iframe = document.getElementById('format_popups_scorm_iframe');
    maxheight = iframe.getAttribute('height');

    window.removeEventListener('resize', resize);
    window.addEventListener('resize', resize);

    iframe.addEventListener('load', resize, {once: true});
};

/**
 * Resize iframe to window height
 *
 */
const resize = () => {
    'use strict';

    let modalbody = document.getElementById('format_popups_activity_content').closest('.modal-body'),
        iframe = document.getElementById('format_popups_scorm_iframe');

    iframe.setAttribute('height', maxheight);

    iframe.setAttribute('height', modalbody.offsetHeight - 40);
};
