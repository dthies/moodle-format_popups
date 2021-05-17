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
 * @copyright  2022 Daniel Thies <dethies@gmail.com>
 *             adapted from Moodle mod_folder
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_popups\local;

defined('MOODLE_INTERNAL') || die();

use stdClass;
use context_user;
use completion_info;

require_once("$CFG->dirroot/mod/folder/locallib.php");
require_once("$CFG->dirroot/repository/lib.php");
require_once($CFG->libdir . '/completionlib.php');

/**
 * Activity renderer Popups course format
 *
 * @copyright  2021 Daniel Thies <dethies@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_folder extends mod_page {

    /**
     * Renders page contents
     *
     * @return string page contents
     */
    public function render() {
        global $DB, $PAGE;

        $folder = $DB->get_record('folder', array('id' => $this->cm->instance), '*', MUST_EXIST);
        $course = $this->course;
        require_capability('mod/folder:view', $this->context);

        $params = array(
            'context' => $this->context,
            'objectid' => $folder->id
        );
        $event = \mod_folder\event\course_module_viewed::create($params);
        $event->add_record_snapshot('course_modules', $this->cm);
        $event->add_record_snapshot('course', $course);
        $event->add_record_snapshot('folder', $folder);
        $event->trigger();

        // Update 'viewed' state if required by completion system.
        $completion = new completion_info($course);
        $completion->set_module_viewed($this->cm);

        $output = $PAGE->get_renderer('mod_folder');
        $PAGE->requires->js_call_amd('format_popups/folder', 'init', array($this->context->id, $this->cm->id));

        return $output->display_folder($folder);
    }
}

