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
 * @copyright  2021 Daniel Thies <dethies@gmail.com>
 *             adapted from Moodle mod_page
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_popups\local;

defined('MOODLE_INTERNAL') || die();

use stdClass;
use html_writer;
use mod_videotime\output\next_activity_button;
use mod_videotime\videotime_instance;

/**
 * Activity renderer Popups course format
 *
 * @copyright  2021 Daniel Thies <dethies@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_videotime {

    /** @var string page path if submittend */
    protected $path = null;

    /** @var object $cm course module */
    protected $cm = null;

    /** @var object $course course for course module */
    protected $course = null;

    /** @var object $context context for course module */
    protected $context = null;

    /** @var object $data data for course module */
    protected $data = null;

    /**
     * Constructor
     *
     * @param object $cm course module
     * @param object $context object course module context
     * @param object $course course record
     * @param object $data search params
     * @param string $path requested file path
     */
    public function __construct($cm, $context, $course, $data, $path) {

        $this->cm = $cm;

        $this->course = $course;

        $this->context = $context;

        $this->path = $path;

        $this->data = $data;
    }

    /**
     * Renders page contents
     *
     * @return string page contents
     */
    public function render() {
        global $DB, $OUTPUT, $PAGE;

        $moduleinstance = $DB->get_record('videotime', array('id' => $this->cm->instance), '*', MUST_EXIST);
        $moduleinstance = videotime_instance::instance_by_id($moduleinstance->id);
        require_capability('mod/videotime:view', $this->context);

        videotime_view($moduleinstance, $this->course, $this->cm, $moduleinstance->get_context());

        $renderer = $PAGE->get_renderer('mod_videotime');

        // Allow any subplugin to override video time instance output.
        foreach (\core_component::get_component_classes_in_namespace(null, 'videotime\\instance') as $fullclassname => $classpath) {
            if (is_subclass_of($fullclassname, videotime_instance::class)) {
                if ($override = $fullclassname::get_instance($moduleinstance->id)) {
                    $moduleinstance = $override;
                }
                if ($override = $fullclassname::get_renderer($moduleinstance->id)) {
                    $renderer = $override;
                }
            }
        }

        if (!$moduleinstance->vimeo_url) {
            \core\notification::error(get_string('vimeo_url_missing', 'videotime'));
        } else {
            return $OUTPUT->box($renderer->render($moduleinstance), 'modtype_videotime path-mod-videotime');
        }
    }
}
