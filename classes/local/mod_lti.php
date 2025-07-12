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
 *             adapted from Moodle mod_lti
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_popups\local;

defined('MOODLE_INTERNAL') || die();

use stdClass;
use context_user;
use html_writer;
use js_writer;
use moodle_url;

require_once($CFG->dirroot . '/mod/lti/lib.php');
require_once($CFG->dirroot . '/mod/lti/locallib.php');
require_once($CFG->dirroot . '/course/lib.php');

/**
 * Activity renderer Popups course format
 *
 * @copyright  2021 Daniel Thies <dethies@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_lti extends mod_page {
    /**
     * Renders page contents
     *
     * @return string page contents
     */
    public function render() {
        global $DB, $OUTPUT, $PAGE, $SESSION, $USER;
        $cm = $this->cm;
        $course = $this->course;
        $contextmodule = $this->context;
        $lti = $DB->get_record('lti', ['id' => $this->cm->instance], '*', MUST_EXIST);

        require_capability('mod/lti:view', $this->context);
        $typeid = $lti->typeid;
        if (empty($typeid) && ($tool = lti_get_tool_by_url_match($lti->toolurl))) {
            $typeid = $tool->id;
        }
        if ($typeid) {
            $toolconfig = lti_get_type_config($typeid);
            $missingtooltype = empty($toolconfig);
            if (!$missingtooltype) {
                $toolurl = $toolconfig['toolurl'];
            }
        } else {
            $toolconfig = [];
            $toolurl = $lti->toolurl;
        }

        if (!empty($missingtooltype)) {
            throw new moodle_exception('tooltypenotfounderror', 'mod_lti');
        }

        $launchcontainer = lti_get_launch_container($lti, $toolconfig);

        lti_view($lti, $this->course, $this->cm, $this->context);

        $content = '';
        // Build the allowed URL, since we know what it will be from $lti->toolurl,
        // If the specified toolurl is invalid the iframe won't load, but we still want to avoid parse related errors here.
        // So we set an empty default allowed url, and only build a real one if the parse is successful.
        $ltiallow = '';
        $urlparts = parse_url($toolurl);
        $launchurl = new moodle_url('/mod/lti/launch.php', ['id' => $this->cm->id, 'triggerview' => 0]);
        if ($urlparts && array_key_exists('scheme', $urlparts) && array_key_exists('host', $urlparts)) {
            $ltiallow = $urlparts['scheme'] . '://' . $urlparts['host'];
            // If a port has been specified we append that too.
            if (array_key_exists('port', $urlparts)) {
                $ltiallow .= ':' . $urlparts['port'];
            }
        }

        // Request the launch content with an iframe tag.
        $attributes = [];
        $attributes['id'] = "contentframe";
        $attributes['height'] = '600px';
        $attributes['width'] = '100%';
        $attributes['src'] = $launchurl;
        $attributes['allow'] = "microphone $ltiallow; " .
            "camera $ltiallow; " .
            "geolocation $ltiallow; " .
            "midi $ltiallow; " .
            "encrypted-media $ltiallow; " .
            "autoplay $ltiallow";
        $attributes['allowfullscreen'] = 1;
        $iframehtml = html_writer::tag('iframe', $content, $attributes);

        return $iframehtml;
    }
}
