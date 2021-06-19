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
 *             adapted from Moodle mod_resource
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_popups\local;

defined('MOODLE_INTERNAL') || die();

use stdClass;
use context_user;
use completion_info;
use core_media_manager;
use moodle_url;

require_once($CFG->dirroot.'/mod/resource/lib.php');
require_once($CFG->dirroot.'/mod/resource/locallib.php');
require_once($CFG->libdir.'/completionlib.php');

/**
 * Activity renderer Popups course format
 *
 * @copyright  2021 Daniel Thies <dethies@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_resource extends mod_url {

    /**
     * Renders page contents
     *
     * @return string page contents
     */
    public function render() {
        global $DB, $OUTPUT, $PAGE;

        $resource = $DB->get_record('resource', array('id' => $this->cm->instance), '*', MUST_EXIST);
        $course = $this->course;
        require_capability('mod/resource:view', $this->context);

        ob_start();

        // Completion and trigger events.
        resource_view($resource, $course, $this->cm, $this->context);

        if ($resource->tobemigrated) {
            resource_print_tobemigrated($resource, $this->cm, $course);
            die;
        }

        $fs = get_file_storage();
        $files = $fs->get_area_files($this->context->id, 'mod_resource',
            'content', 0, 'sortorder DESC, id ASC', false);
        if (count($files) < 1) {
            resource_print_filenotfound($resource, $this->cm, $course);
            die;
        } else {
            $file = reset($files);
            unset($files);
        }

        $resource->mainfile = $file->get_filename();

        $moodleurl = moodle_url::make_pluginfile_url($this->context->id, 'mod_resource', 'content', $resource->revision,
                $file->get_filepath(), $file->get_filename());

        $mimetype = $file->get_mimetype();
        $title    = $resource->name;

        $extension = resourcelib_get_extension($file->get_filename());

        $mediamanager = core_media_manager::instance($PAGE);
        $embedoptions = array(
            core_media_manager::OPTION_TRUSTED => true,
            core_media_manager::OPTION_BLOCK => true,
        );

        if (file_mimetype_in_typegroup($mimetype, 'web_image')) {
            // It's an image.
            $code = resourcelib_embed_image($moodleurl->out(), $title);
            $PAGE->requires->js_call_amd('format_popups/embed', 'init', array($this->context->id));

        } else if ($mimetype === 'application/pdf') {
            // PDF document.
            $code = resourcelib_embed_pdf($moodleurl->out(), $title, $clicktoopen);

        } else if ($mediamanager->can_embed_url($moodleurl, $embedoptions)) {
            // Media (audio/video) file.
            $code = $mediamanager->embed_url($moodleurl, $title, 0, 0, $embedoptions);

        } else if (in_array((int) $resource->display, array(
            RESOURCELIB_DISPLAY_EMBED,
            RESOURCELIB_DISPLAY_OPEN,
            RESOURCELIB_DISPLAY_POPUP,
        ))) {
            self::resource_display_embed($resource, $this->cm, $this->course, $file);
            $PAGE->requires->js_call_amd('format_popups/embed', 'init', array($this->context->id));
            $code = '';

        } else {
            // Anything else ask to download.
            $moodleurl->param('forcedownload', 1);
            $code = $OUTPUT->render_from_template('format_popups/filedownload', array(
                'filename' => $file->get_filename(),
                'url' => $moodleurl->out(),
            ));
        }

        echo $code;
        $contents = ob_get_contents();
        ob_end_clean();

        return $contents;
    }

    /**
     * Display embedded resource file.
     * @param object $resource
     * @param object $cm
     * @param object $course
     * @param stored_file $file main file
     * @return does not return
     */
    protected function resource_display_embed($resource, $cm, $course, $file) {
        global $PAGE;

        $clicktoopen = resource_get_clicktoopen($file, $resource->revision);

        $context = $this->context;
        $moodleurl = moodle_url::make_pluginfile_url($context->id, 'mod_resource', 'content', $resource->revision,
                $file->get_filepath(), $file->get_filename());

        $mimetype = $file->get_mimetype();
        $title    = $resource->name;

        $extension = resourcelib_get_extension($file->get_filename());

        $mediamanager = core_media_manager::instance($PAGE);
        $embedoptions = array(
            core_media_manager::OPTION_TRUSTED => true,
            core_media_manager::OPTION_BLOCK => true,
        );

        if (file_mimetype_in_typegroup($mimetype, 'web_image')) {  // It's an image.
            $code = resourcelib_embed_image($moodleurl->out(), $title);

        } else if ($mimetype === 'application/pdf') {
            // PDF document.
            $code = resourcelib_embed_pdf($moodleurl->out(), $title, $clicktoopen);

        } else if ($mediamanager->can_embed_url($moodleurl, $embedoptions)) {
            // Media (audio/video) file.
            $code = $mediamanager->embed_url($moodleurl, $title, 0, 0, $embedoptions);

        } else {
            // We need a way to discover if we are loading remote docs inside an iframe.
            $moodleurl->param('embed', 1);

            // Anything else - just try object tag enlarged as much as possible.
            $code = resourcelib_embed_general($moodleurl, $title, $clicktoopen, $mimetype);
        }

        resource_print_heading($resource, $cm, $course);

        echo $code;

        resource_print_intro($resource, $cm, $course);

    }
}
