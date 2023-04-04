<?php
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
 * WebSocket manager
 *
 * @package    format_popups
 * @copyright  2023 Daniel Thies
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_popups;

use context;
use moodle_exception;
use stdClass;
use format_popups\socket;

/**
 * Web socket manager
 *
 * @package    format_popups
 * @copyright  2023 Daniel Thies
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {

    /**
     * Handle an event
     *
     * @param \core\event\base $event
     */
    public static function observe(\core\event\base $event) {
        global $USER;

        if (
            !get_config('format_popups', 'enabledeftresponse')
            || !class_exists('\\block_deft\\socket')
            || !$context = $event->get_context()->get_course_context()
        ) {
            return;
        }

        $eventdata = $event->get_data();
        if (!empty($eventdata['relateduserid']) && ($USER->id != $eventdata['relateduserid'])) {
            return;
        }

        try {
            $socket = new socket($context);
            $socket->validate();
            $socket->dispatch();
        } catch (moodle_exception $e) {
            return;
        }

    }
}
