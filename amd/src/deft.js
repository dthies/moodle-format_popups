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
 * Add Deft response bleck to update course page remotely
 *
 * @module     format_popups/deft
 * @copyright  2023 Daniel Thies <dethies@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from "core/ajax";
import {debounce} from 'core/utils';
import Log from "core/log";
import Notification from "core/notification";
import Popups from 'format_popups/popups';
import SocketBase from 'block_deft/socket';

/**
 * Initialize modal and listeners
 *
 * @param {int} contextid Course context id
 * @param {int} courseid Course id
 * @param {int} displaysection Single section to display
 * @param {string} token Deft authentication
 * @param {int} throttle Throttle dely in ms
 */
export const init = (contextid, courseid, displaysection, token, throttle) => {
    'use strict';

    const socket = new Socket(contextid, token);
    Popups.init(contextid, courseid, displaysection, 'format_popups');

    socket.subscribe(
        debounce(Popups.updatePage.bind(Popups), throttle)
    );
};

class Socket extends SocketBase {
    /**
     * Renew token
     *
     * @param {int} contextid Context id of block
     */
    renewToken(contextid) {
        Ajax.call([{
            methodname: 'format_popups_renew_token',
            args: {contextid: contextid},
            done: (replacement) => {
                Log.debug('Reconnecting');
                this.connect(contextid, replacement.token);
            },
            fail: Notification.exception
        }]);
    }
}
