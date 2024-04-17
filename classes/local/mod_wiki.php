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
 * @copyright  2024 Daniel Thies <dethies@gmail.com>
 *             adapted from Moodle mod_wiki
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_popups\local;

defined('MOODLE_INTERNAL') || die();

use stdClass;
use context_module;
use context_user;
use core_tag_tag;
use html_writer;
use moodle_exception;
use moodle_url;
use page_wiki_view;

require_once($CFG->dirroot . '/mod/wiki/lib.php');
require_once($CFG->dirroot . '/mod/wiki/locallib.php');
require_once($CFG->dirroot . '/mod/wiki/pagelib.php');

/**
 * Activity renderer Popups course format
 *
 * @copyright  2024 Daniel Thies <dethies@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_wiki extends mod_page {
    /**
     * Renders page contents
     *
     * @return string page contents
     */
    public function render() {
        global $DB, $OUTPUT, $PAGE, $USER;
        $cm = $this->cm;
        $context = $this->context;

        $config = get_config('wiki');
        $groupmode = groups_get_activity_groupmode($cm);

        if ($groupmode) {
            $currentgroup = groups_get_activity_group($cm, true);
        }

        // Checking course instance.
        $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
        $pageid = empty($this->data->pageid) ? 0 : $this->data->pageid;
        if (!empty($pageid)) {
            // Checking page instance.
            if (!$page = wiki_get_page($pageid)) {
                throw new \moodle_exception('incorrectpageid', 'wiki');
            }

            // Checking subwiki.
            if (!$subwiki = wiki_get_subwiki($page->subwikiid)) {
                throw new \moodle_exception('incorrectsubwikiid', 'wiki');
            }

            // Checking wiki instance of that subwiki.
            if (!$wiki = wiki_get_wiki($subwiki->wikiid)) {
                throw new \moodle_exception('incorrectwikiid', 'wiki');
            }

            // Checking course module instance.
            if (!$cm = get_coursemodule_from_instance("wiki", $subwiki->wikiid)) {
                throw new \moodle_exception('invalidcoursemodule');
            }

            $currentgroup = $subwiki->groupid;

            // Checking course instance.
            $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);

            require_course_login($course, true, $cm);
        } else {
            require_course_login($course, true, $cm);

            // Checking wiki instance.
            if (!$wiki = wiki_get_wiki($cm->instance)) {
                throw new \moodle_exception('incorrectwikiid', 'wiki');
            }

            // Getting current group id.
            $currentgroup = groups_get_activity_group($cm);

            // Getting current user id.
            if ($wiki->wikimode == 'individual') {
                $userid = $USER->id;
            } else {
                $userid = 0;
            }

            // Getting subwiki. If it does not exists, redirecting to create page.
            if (!$subwiki = wiki_get_subwiki_by_group($wiki->id, $currentgroup, $userid)) {
                $params = ['wid' => $wiki->id, 'group' => $currentgroup, 'uid' => $userid, 'title' => $wiki->firstpagetitle];
                $url = new moodle_url('/mod/wiki/create.php', $params);
                redirect($url);
            }

            // Getting first page. If it does not exists, redirecting to create page.
            if (!$page = wiki_get_first_page($subwiki->id, $wiki)) {
                $params = ['swid' => $subwiki->id, 'title' => $wiki->firstpagetitle];
                $url = new moodle_url('/mod/wiki/create.php', $params);
                redirect($url);
            }
        }

        $wikipage = new helper\wiki_view($wiki, $subwiki, $cm);

        $wikipage->set_gid($currentgroup);
        $wikipage->set_page($page);

        ob_start();

        $wikipage->print_header();
        $wikipage->print_content();

        $contents = ob_get_contents();
        ob_end_clean();

        $PAGE->requires->js_call_amd('format_popups/wiki', 'init', [$context->id]);

        return  $contents;
    }
}
