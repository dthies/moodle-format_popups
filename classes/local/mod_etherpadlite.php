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
 *             adapted from Moodle mod_etherpadlite
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_popups\local;

defined('MOODLE_INTERNAL') || die();

use stdClass;
use context_user;
use core_tag_tag;
use html_writer;
use moodle_exception;
use moodle_url;

require_once($CFG->dirroot.'/mod/book/lib.php');
require_once($CFG->dirroot.'/mod/book/locallib.php');
require_once($CFG->libdir.'/completionlib.php');

/**
 * Activity renderer Popups course format
 *
 * @copyright  2021 Daniel Thies <dethies@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_etherpadlite extends mod_page {

    /**
     * Renders page contents
     *
     * @return string page contents
     */
    public function render() {
        global $DB, $OUTPUT, $PAGE, $USER;
        $cm = $this->cm;
        $context = $this->context;
        list($course, $cm, $etherpadlite) = \mod_etherpadlite\util::get_coursemodule($cm->id, $cm->instance);

        $config = get_config('etherpadlite');
        // START of Initialise the session for the Author.
        // Set vars.
        $padid = $etherpadlite->uri;

        // Make a new intance from the etherpadlite client. It might throw an exception.
        $client = \mod_etherpadlite\api\client::get_instance($config->apikey, $config->url);

        // Get group mode.
        $groupmode = groups_get_activity_groupmode($cm);
        $canaddinstance = has_capability('mod/etherpadlite:addinstance', $context);
        $isgroupmember = true;
        $urlpadid = $padid;

        if ($groupmode) {
            $activegroup = groups_get_activity_group($cm, true);
            if ($activegroup != 0) {
                $urlpadid = $urlpadid . $activegroup;
                $isgroupmember = groups_is_member($activegroup);
            }
        }

        // Check if Activity is in the open timeframe.
        $time = time();
        $openrestricted = !empty($etherpadlite->timeopen) && ($etherpadlite->timeopen >= $time);
        $closerestricted = !empty($etherpadlite->timeclose) && ($etherpadlite->timeclose <= $time);
        $timerestricted = ($openrestricted || $closerestricted) && !$canaddinstance;

        // Are there some guest restrictions?
        $guestrestricted = isguestuser() && !etherpadlite_guestsallowed($etherpadlite);

        // Are there some groups restrictions?
        $grouprestricted = !$isgroupmember && !$canaddinstance;

        // Fullurl generation depending on the restrictions.
        if ($guestrestricted || $grouprestricted || $timerestricted) {
            if (!$readonlyid = $client->get_readonly_id($urlpadid)) {
                throw new \moodle_exception('could not get readonly id');
            }
            $fullurl = $config->url . 'p/' . $readonlyid;
        } else {
            $fullurl = $config->url . 'p/' . $urlpadid;
        }

        // Get the groupID.
        $epgroupid = explode('$', $padid);
        $epgroupid = $epgroupid[0];

        // Create author if not exists for logged in user (with full name as it is obtained from Moodle core library).
        if ((isguestuser() && etherpadlite_guestsallowed($etherpadlite)) || !$isgroupmember) {
            $authorid = $client->create_author('Guest-'.etherpadlite_gen_random_string());
        } else {
            $authorid = $client->create_author_if_not_exists_for($USER->id, fullname($USER));
        }
        if (!$authorid) {
            throw new \moodle_exception('could not create etherpad author');
        }

        // Create a browser session to the etherpad lite server.
        if (!$client->create_session($epgroupid, $authorid)) {
            throw new \moodle_exception('could not create etherpad session');
        }

        // END of Etherpad Lite init.
        // Display the etherpadlite and possibly results.
        $eventparams = [
            'context' => $context,
            'objectid' => $etherpadlite->id
        ];
        $event = \mod_etherpadlite\event\course_module_viewed::create($eventparams);
        $event->add_record_snapshot('course_modules', $cm);
        $event->add_record_snapshot('course', $course);
        $event->add_record_snapshot('etherpadlite', $etherpadlite);
        $event->trigger();

        $PAGE->set_title(get_string('modulename', 'mod_etherpadlite').': '.format_string($etherpadlite->name));
        $PAGE->set_heading(format_string($course->fullname));
        $PAGE->set_context($context);

        // Add the keepalive system to keep checking for a connection.
        \core\session\manager::keepalive();

        $renderer = $PAGE->get_renderer('mod_etherpadlite');

        // Print the etherpad content.
        return $renderer->render_etherpad($etherpadlite, $cm, $fullurl);

    }
}
