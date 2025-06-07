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
 * Activity renderer Popups course format
 *
 * @package    format_popups
 * @copyright  2023 Daniel Thies <dethies@gmail.com>
 *             adapted from Moodle mod_assign
 *             base on work by Manuel Mejia <manimejia.me@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_popups\local\helper;

defined('MOODLE_INTERNAL') || die();

use assign_form;
use assign_plugin_header;
use mod_contentdesigner\content_display as content_display_base;
use context_user;
use core_user;
use core_tag_tag;
use html_writer;
use mod_assign_submission_form;
use moodle_exception;
use moodle_url;
use stdClass;

require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->dirroot . '/mod/assign/lib.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');
require_once($CFG->dirroot . '/mod/assign/renderable.php');

/**
 * Activity renderer Popups course format
 *
 * @copyright  2021 Daniel Thies <dethies@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class contentdesigner extends content_display_base {

    public function submitAttempt($mform, $notices) {
        global $USER;

        // Get the current attempt.
        $attempt = $this->get_attempt($USER->id);
        if (!$attempt) {
            return;
        }

        // Process the form data.
        $data = $mform->get_data();
        if ($data) {
            $this->process_finish_attempt($data, $attempt, $notices);
        }
    }
}