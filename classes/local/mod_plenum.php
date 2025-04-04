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

namespace format_popups\local;

use stdClass;
use context_user;
use core_tag_tag;
use html_writer;
use moodle_exception;
use moodle_url;

/**
 * Activity renderer Popups course format
 *
 * @copyright  2025 Daniel Thies <dethies@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package format_popups
 */
class mod_plenum extends mod_page {
    /**
     * Renders page contents
     *
     * @return string page contents
     */
    public function render() {
        global $CFG, $SESSION, $OUTPUT, $PAGE;
        $cm = $this->cm;
        $modulecontext = $this->context;
        $course = get_course($cm->course);

        $plenum = \core\di::get(\mod_plenum\manager::class)->get_plenum($modulecontext, $cm, $course);

        // Check to see if groups are being used in this meeting.
        $groupmode = groups_get_activity_groupmode($this->cm);

        $content = '<div class="clearer"></div>';
        ob_start();
        if (isset($this->data->group)) {
            if (has_capability('moodle/site:accessallgroups', $this->context)) {
                $SESSION->activegroup[$this->course->id]['aag'][0] = $this->data->group;
            } else {
                $SESSION->activegroup[$this->course->id][$groupmode][0] = $this->data->group;
            }
        }

        if ($groupmode) {
            groups_get_activity_group($this->cm, true);
            groups_print_activity_menu($this->cm, $CFG->wwwroot . '/mod/plenum/view.php?id=' . $this->context->instanceid);
        }
        $content .= ob_get_contents();
        ob_end_clean();

        $main = $plenum->get_mainpage();

        $PAGE->requires->js_call_amd('format_popups/form', 'init', [$this->context->id, $this->cm->modname]);
        $PAGE->requires->js_call_amd('format_popups/plenum', 'init', [$this->context->id, $this->cm->modname]);

        return $content . $OUTPUT->render($main);
    }
}
