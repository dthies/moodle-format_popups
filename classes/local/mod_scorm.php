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
 *             adapted from Moodle mod_scorm
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_popups\local;

defined('MOODLE_INTERNAL') || die();

use stdClass;
use context_user;
use html_writer;
use js_writer;
use moodle_url;

require_once($CFG->dirroot.'/mod/scorm/lib.php');
require_once($CFG->dirroot.'/mod/scorm/locallib.php');
require_once($CFG->dirroot.'/course/lib.php');

/**
 * Activity renderer Popups course format
 *
 * @copyright  2021 Daniel Thies <dethies@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_scorm extends mod_page {

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
        $scorm = $DB->get_record('scorm', array('id' => $this->cm->instance), '*', MUST_EXIST);

        ob_start();

        if (!empty($this->data) && !empty($this->data->scoid)) {
            if ($scorm->popup != 0 && $displaymode !== 'popup') {
                // Clean the name for the window as IE is fussy.
                $name = preg_replace("/[^A-Za-z0-9]/", "", $scorm->name);
                if (!$name) {
                    $name = 'DefaultPlayerWindow';
                }
                $name = 'scorm_'.$name;
                echo html_writer::script('', $CFG->wwwroot.'/mod/scorm/player.js');
                $url = new moodle_url('/mod/scorm/player.php', array(
                    'cm' => $this->cm->id,
                    'scoid' => $this->data->scoid,
                    'display' => 'popup',
                    'mode' => $this->data->mode ?? 'normal',
                ));
                echo html_writer::script(
                    js_writer::function_call('scorm_openpopup', Array($url->out(false),
                                                       $name, $scorm->options,
                                                       $scorm->width, $scorm->height)));
                    $contents = ob_get_contents();
                    ob_end_clean();

                    $PAGE->requires->jquery('
                        $("#popup").closest(".modal").hide();
                    ');
                    return $contents;
            }
            $url = new moodle_url("/mod/scorm/player.php", array(
                'a' => $this->cm->instance,
                'currentorg' => $this->data->currentorg,
                'scoid' => $this->data->sco,
                'sesskey' => sesskey(),
                'display' => 'popup',
            ));
            echo '<div><iframe id="format_popups_scorm_iframe" width="100%" height="' .  $scorm->height .
                '" src="' . $url->out(false) . '"></iframe></div>';

            $contents = ob_get_contents();
            ob_end_clean();

            $PAGE->requires->js_call_amd('format_popups/scorm', 'init', array());

            return $contents;
        }

        $launch = false; // Does this automatically trigger a launch based on skipview.

        if (!empty($scorm->popup)) {
            $scoid = 0;
            $orgidentifier = '';

            $result = scorm_get_toc($USER, $scorm, $cm->id, TOCFULLURL);
            // Set last incomplete sco to launch first.
            if (!empty($result->sco->id)) {
                $sco = $result->sco;
            } else {
                $sco = scorm_get_sco($scorm->launch, SCO_ONLY);
            }
            if (!empty($sco)) {
                $scoid = $sco->id;
                if (($sco->organization == '') && ($sco->launch == '')) {
                    $orgidentifier = $sco->identifier;
                } else {
                    $orgidentifier = $sco->organization;
                }
            }

            if (empty($preventskip) && $scorm->skipview >= SCORM_SKIPVIEW_FIRST &&
                has_capability('mod/scorm:skipview', $contextmodule) &&
                !has_capability('mod/scorm:viewreport', $contextmodule)) { // Don't skip users with the capability to view reports.

                // Do we launch immediately and redirect the parent back ?
                if ($scorm->skipview == SCORM_SKIPVIEW_ALWAYS || !scorm_has_tracks($scorm->id, $USER->id)) {
                    $launch = true;
                }
            }
        }

        if (isset($SESSION->scorm)) {
            unset($SESSION->scorm);
        }

        // Trigger module viewed event.
        scorm_view($scorm, $course, $cm, $contextmodule);

        if (empty($preventskip) && empty($launch) && (has_capability('mod/scorm:skipview', $contextmodule))) {
            scorm_simple_play($scorm, $USER, $contextmodule, $cm->id);
        }

        // Print the main part of the page.
        $attemptstatus = '';
        if (empty($launch) && ($scorm->displayattemptstatus == SCORM_DISPLAY_ATTEMPTSTATUS_ALL ||
                 $scorm->displayattemptstatus == SCORM_DISPLAY_ATTEMPTSTATUS_ENTRY)) {
            $attemptstatus = scorm_get_attempt_status($USER, $scorm, $cm);
        }
        echo $OUTPUT->box(format_module_intro('scorm', $scorm, $cm->id).$attemptstatus, 'container', 'intro');

        // Check if SCORM available.
        list($available, $warnings) = scorm_get_availability_status($scorm);
        if (!$available) {
            $reason = current(array_keys($warnings));
            echo $OUTPUT->box(get_string($reason, "scorm", $warnings[$reason]), "container");
        }

        if ($available && empty($launch)) {
            scorm_print_launch($USER, $scorm, 'view.php?id='.$cm->id, $cm);
        }

        $contents = ob_get_contents();
        ob_end_clean();

        $PAGE->requires->js_call_amd('format_popups/form', 'init', array($this->context->id, $this->cm->modname));

        return $contents;
    }
}
