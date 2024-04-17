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
 * Contains the default content output class.
 *
 * @package   format_popups
 * @copyright 2022 Daniel Thies <dethies@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_popups\output\courseformat;

use context_course;
use core_courseformat\output\local\content as content_base;
use format_popups\socket;
use renderer_base;

/**
 * Base class to render a course content.
 *
 * @package   format_popups
 * @copyright 2022 Daniel Thies <dethies@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class content extends \format_topics\output\courseformat\content {
    /**
     * Export this data so it can be used as the context for a mustache template (core/inplace_editable).
     *
     * @param renderer_base $output typically, the renderer that's calling this function
     * @return stdClass data context for a mustache template
     */
    public function export_for_template(renderer_base $output) {
        global $PAGE;
        $course = $this->format->get_course();
        $context = context_course::instance($course->id);
        $displaysection = $this->format->get_sectionid();

        if (get_config('format_popups', 'enabledeftresponse')) {
            $socket = new socket($context);
            $token = $socket->get_token();
            $PAGE->requires->js_call_amd('format_popups/deft', 'init', [
                $context->id, $course->id, $displaysection, $token, get_config('block_deft', 'throttle'),
            ]);
        } else {
            $PAGE->requires->js_call_amd('format_popups/popups', 'init', [
                $context->id, $course->id, $displaysection,
            ]);
        }

        return parent::export_for_template($output);
    }
}
