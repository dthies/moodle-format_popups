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
 *             adapted from Moodle mod_url
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_popups\local;

defined('MOODLE_INTERNAL') || die();

use stdClass;
use context_user;
use completion_info;
use core_media_manager;
use html_writer;
use moodle_url;

require_once("$CFG->dirroot/mod/url/lib.php");
require_once("$CFG->dirroot/mod/url/locallib.php");
require_once($CFG->libdir . '/completionlib.php');

/**
 * Activity renderer Popups course format
 *
 * @copyright  2021 Daniel Thies <dethies@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_url extends mod_page {

    /**
     * Renders page contents
     *
     * @return string page contents
     */
    public function render() {
        global $DB, $OUTPUT, $PAGE;

        $url = $DB->get_record('url', array('id' => $this->cm->instance), '*', MUST_EXIST);
        $course = $this->course;
        $cm = $this->cm;
        $context = $this->context;

        require_capability('mod/resource:view', $this->context);

        ob_start();

        require_capability('mod/url:view', $context);

        // Completion and trigger events.
        url_view($url, $course, $cm, $context);

        // Make sure URL exists before generating output - some older sites may contain empty urls
        // Do not use PARAM_URL here, it is too strict and does not support general URIs!
        $exturl = trim($url->externalurl);
        if (empty($exturl) || $exturl === 'http://') {
            url_print_header($url, $cm, $course);
            if ($intro = url_get_intro($url, $cm, false)) {
                echo $OUTPUT->box_start('mod_introbox', 'urlintro');
                echo $intro;
                echo $OUTPUT->box_end();
            }
            notice(get_string('invalidstoredurl', 'url'), new moodle_url('/course/view.php', array(
                'id' => $cm->course
            )));
            die;
        }
        unset($exturl);

        $displaytype = url_get_final_display_type($url);

        switch ($displaytype) {
            case RESOURCELIB_DISPLAY_EMBED:
                self::url_display_embed($url, $this->cm, $this->course);
                $PAGE->requires->js_call_amd('format_popups/embed', 'init', array($this->context->id));
                break;
            case RESOURCELIB_DISPLAY_FRAME:
                echo $OUTPUT->render_from_template('format_popups/embedfile', array(
                    'filename' => $url->name,
                    'url' => $url->externalurl,
                    'params' => array(array('name' => 'download', 'value' => 1)),
                ));
                break;
            default:
                break;
        }

        $contents = ob_get_contents();
        ob_end_clean();

        return $contents;
    }

    /**
     * Display embedded url file.
     * @param object $url
     * @param object $cm
     * @param object $course
     * @return does not return
     */
    protected static function url_display_embed($url, $cm, $course) {
        global $CFG, $PAGE, $OUTPUT;

        $mimetype = resourcelib_guess_url_mimetype($url->externalurl);
        $fullurl  = url_get_full_url($url, $cm, $course);
        $title    = $url->name;

        $link = html_writer::tag('a', $fullurl, array('href' => str_replace('&amp;', '&', $fullurl)));
        $clicktoopen = get_string('clicktoopen', 'url', $link);
        $moodleurl = new moodle_url($fullurl);

        $extension = resourcelib_get_extension($url->externalurl);

        $mediamanager = core_media_manager::instance($PAGE);
        $embedoptions = array(
            core_media_manager::OPTION_TRUSTED => true,
            core_media_manager::OPTION_BLOCK => true
        );

        if (in_array($mimetype, array('image/gif', 'image/jpeg', 'image/png'))) { // It's an image.
            $code = resourcelib_embed_image($fullurl, $title);

        } else if ($mediamanager->can_embed_url($moodleurl, $embedoptions)) {
            // Media (audio/video) file.
            $code = $mediamanager->embed_url($moodleurl, $title, 0, 0, $embedoptions);

        } else {
            // Anything else - just try object tag enlarged as much as possible.
            $code = self::resourcelib_embed_general($fullurl, $title, $clicktoopen, $mimetype);
        }

        echo $code;

        if ($intro = url_get_intro($url, $cm, false)) {
            echo $OUTPUT->box_start('mod_introbox', 'urlintro');
            echo $intro;
            echo $OUTPUT->box_end();
        }
    }

    /**
     * Returns general link or file embedding html.
     * @param string $fullurl
     * @param string $title
     * @param string $clicktoopen
     * @param string $mimetype
     * @return string html
     */
    protected static function resourcelib_embed_general($fullurl, $title, $clicktoopen, $mimetype) {

        if ($fullurl instanceof moodle_url) {
            $fullurl = $fullurl->out();
        }

        // Always use iframe embedding because object tag does not work much,
        // this is ok in HTML5.
        $code = <<<EOT
    <div class="resourcecontent resourcegeneral">
      <iframe id="resourceobject" src="$fullurl">
        $clicktoopen
      </iframe>
    </div>
EOT;

        return $code;
    }
}
