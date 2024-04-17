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
 *             adapted from mod_poster 2015 David Mudrak <david@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_popups\local\helper;

defined('MOODLE_INTERNAL') || die();

use stdClass;
use completion_info;
use moodle_page;

require_once($CFG->libdir . '/completionlib.php');
require_once($CFG->dirroot . '/mod/poster/renderer.php');

/**
 * Activity renderer Popups course format
 *
 * @copyright  2021 Daniel Thies <dethies@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_poster_renderer extends \mod_poster_renderer {
    /**
     * Render the poster main view page (view.php)
     *
     * @param stdClass $poster The poster instance record
     * @return string
     */
    public function view_page($poster) {

        if ($this->page->user_allowed_editing()) {
            $this->page->set_button($this->edit_button($this->page->url));
            $this->page->blocks->set_default_region('mod_poster-pre');
            $this->page->theme->addblockposition = BLOCK_ADDBLOCK_POSITION_DEFAULT;
        }

        // We do not want the header, but need to set state to ready blocks.
        $this->page->set_state(moodle_page::STATE_PRINTING_HEADER);

        if ($poster->shownameview) {
            $out .= $this->view_page_heading($poster);
        }

        if ($poster->showdescriptionview) {
            $out .= $this->view_page_description($poster);
        }

        $out .= $this->view_page_content($poster);

        return $out;
    }
}
