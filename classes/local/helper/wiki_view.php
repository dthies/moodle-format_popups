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

namespace format_popups\local\helper;

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
class wiki_view extends page_wiki_view {
    /**
     * This method prints the top of the page.
     */
    public function print_header() {
        global $PAGE;
        if (!empty($this->page)) {
            echo $this->action_bar($this->page->id, $PAGE->url);
        }

        $this->wikioutput->wiki_print_subwiki_selector($PAGE->activityrecord, $this->subwiki, $this->page, 'view');

        $this->print_pagetitle();
    }

    /**
     * The page_wiki constructor.
     *
     * @param stdClass $wiki Current wiki
     * @param stdClass $subwiki Current subwiki.
     * @param stdClass $cm Current course_module.
     * @param string|null $activesecondarytab Secondary navigation node to be activated on the page, if required
     */
    public function __construct($wiki, $subwiki, $cm, ?string $activesecondarytab = null) {
        global $PAGE, $CFG;
        $this->subwiki = $subwiki;
        $this->cm = $cm;
        $this->modcontext = context_module::instance($this->cm->id);

        // Initialise wiki renderer.
        $this->wikioutput = $PAGE->get_renderer('mod_wiki');
        $PAGE->set_cacheable(true);
        $PAGE->set_cm($cm);
        $PAGE->set_activity_record($wiki);
        if ($activesecondarytab) {
            $PAGE->set_secondary_active_tab($activesecondarytab);
        }
        // Add the search box.
        if (!empty($subwiki->id)) {
            $search = optional_param('searchstring', null, PARAM_TEXT);
            $PAGE->set_button(wiki_search_form($cm, $search, $subwiki));
        }
    }
}
