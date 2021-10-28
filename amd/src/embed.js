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
 * Module to resize content embedded in modal
 *
 * @module     format_popups/embed
 * @copyright  2021 Daniel Thies <dethies@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
var iframe;

/**
 * Iniatialie listener to resize iframe in modal
 */
export const init = () => {
    'use strict';

    window.removeEventListener('resize', resize);

    iframe = document.querySelector(
        '#format_popups_activity_content iframe'
    );

    window.removeEventListener('resize', resize);

    if (iframe) {
        iframe.addEventListener('load', resize, {once: true});
        window.addEventListener('resize', resize);

        resize();
    }
};

/*
 * Resize the iframe to fit window size
 */
export const resize = () => {
    'use strict';

    if (!iframe) {
        window.removeEventListener('resize', resize);
        return;
    }

    let modalbody = document.getElementById('format_popups_activity_content').closest('.modal-body'),
        maxheight = window.innerHeight;

    iframe.setAttribute('height', maxheight);

    iframe.setAttribute('height', modalbody.offsetHeight + maxheight - modalbody.firstChild.offsetHeight - 40);
};
