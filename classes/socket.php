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

/**
 * Web socket manager
 *
 * @package    format_popups
 * @copyright  2023 Daniel Thies
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class socket extends \block_deft\socket {
    /**
     * @var Component
     */
    protected const COMPONENT = 'format_popups';

    /**
     * Validate context and availabilty
     */
    public function validate() {
        if (
            $this->context->contextlevel != CONTEXT_COURSE
            || !get_config('format_' . get_course($this->context->instanceid)->format, 'enabledeftresponse')
        ) {
            throw new moodle_exception('invalidcontext');
        }
    }
}
