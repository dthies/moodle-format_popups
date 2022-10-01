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
 *             adapted from Moodle mod_book
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
class mod_book extends mod_page {

    /**
     * Renders page contents
     *
     * @return string page contents
     */
    public function render() {
        global $DB, $OUTPUT, $PAGE;
        $cm = $this->cm;
        $course = $this->course;
        $context = $this->context;
        $book = $DB->get_record('book', array('id' => $this->cm->instance), '*', MUST_EXIST);
        $chapterid = empty($this->data->chapterid) ? 0 : $this->data->chapterid;

        ob_start();

        require_capability('mod/book:read', $context);

        $allowedit  = has_capability('mod/book:edit', $context);
        $viewhidden = has_capability('mod/book:viewhiddenchapters', $context);

        // Read chapters.
        $chapters = book_preload_chapters($book);

        if ($allowedit && !$chapters) {
            redirect('edit.php?cmid='.$cm->id); // No chapters - add new one.
        }
        // Check chapterid and read chapter data.
        if ($chapterid == '0') { // Go to first chapter if no given.
            // Trigger course module viewed event.
            book_view($book, null, false, $course, $cm, $context);

            foreach ($chapters as $ch) {
                if ($ch->hidden && $viewhidden) {
                    $chapterid = $ch->id;
                    break;
                }
                if (!$ch->hidden) {
                    $chapterid = $ch->id;
                    break;
                }
            }
        }

        $courseurl = new moodle_url('/course/view.php', array('id' => $course->id));

        // Chapter doesnt exist or it is hidden for students.
        if ((!$chapter = $DB->get_record('book_chapters', array(
            'id' => $chapterid,
            'bookid' => $book->id,
        ))) || ($chapter->hidden && !$viewhidden)) {
            throw new moodle_exception('errorchapter', 'book', $courseurl);
        }

        // Unset all page parameters.
        unset($chapterid);

        // Prepare chapter navigation icons.
        $previd = null;
        $prevtitle = null;
        $navprevtitle = null;
        $nextid = null;
        $nexttitle = null;
        $navnexttitle = null;
        $last = null;
        foreach ($chapters as $ch) {
            if ($ch->hidden && !$viewhidden) {
                continue;
            }
            if ($last == $chapter->id) {
                $nextid = $ch->id;
                $nexttitle = book_get_chapter_title($ch->id, $chapters, $book, $context);
                $navnexttitle = get_string('navnexttitle', 'mod_book', $nexttitle);
                break;
            }
            if ($ch->id != $chapter->id) {
                $previd = $ch->id;
                $prevtitle = book_get_chapter_title($ch->id, $chapters, $book, $context);
                $navprevtitle = get_string('navprevtitle', 'mod_book', $prevtitle);
            }
            $last = $ch->id;
        }

        if ($book->navstyle) {
            $navprevicon = right_to_left() ? 'nav_next' : 'nav_prev';
            $navnexticon = right_to_left() ? 'nav_prev' : 'nav_next';

            $chnavigation = '';
            if ($previd) {
                $navprev = get_string('navprev', 'book');
                if ($book->navstyle == 1) {
                    $chnavigation .= '<a title="' . $navprevtitle . '" class="bookprev" href="#" data-chapterid="' .
                        $previd .  '">' .
                        $OUTPUT->pix_icon($navprevicon, $navprevtitle, 'mod_book') . '</a>';
                } else {
                    $chnavigation .= '<a title="' . $navprev . '" class="bookprev" href="#" data-chapterid="' .
                        $previd . '">' .
                        '<span class="chaptername"><span class="arrow">' . $OUTPUT->larrow() . '&nbsp;</span></span>' .
                        $navprev . ':&nbsp;<span class="chaptername">' . $prevtitle . '</span></a>';
                }
            }
            if ($nextid) {
                $navnext = get_string('navnext', 'book');
                if ($book->navstyle == 1) {
                    $chnavigation .= '<a title="' . $navnexttitle . '" class="booknext" href="#" data-chapterid="'.
                        $nextid.' ">' .
                        $OUTPUT->pix_icon($navnexticon, $navnexttitle, 'mod_book') . '</a>';
                } else {
                    $chnavigation .= ' <a title="' . $navnext . '" class="booknext" href="#" data-chapterid="'.
                        $nextid. '">' .
                        $navnext . ':<span class="chaptername">&nbsp;' . $nexttitle.
                        '<span class="arrow">&nbsp;' . $OUTPUT->rarrow() . '</span></span></a>';
                }
            } else {
                $navexit = get_string('navexit', 'book');
                $sec = $DB->get_field('course_sections', 'section', array('id' => $cm->section));
                $returnurl = course_get_url($course, $sec);
                if ($book->navstyle == 1) {
                    $chnavigation .= '<a title="' . $navexit . '" class="bookexit"  data-action="hide" href="'.$returnurl.'">' .
                        $OUTPUT->pix_icon('nav_exit', $navexit, 'mod_book') . '</a>';
                } else {
                    $chnavigation .= ' <a title="' . $navexit . '" class="bookexit"  data-action="hide" href="'.$returnurl.'">' .
                        '<span class="chaptername">' . $navexit . '&nbsp;' . $OUTPUT->uarrow() . '</span></a>';
                }
            }
        }

        // We need to discover if this is the last chapter to mark activity as completed.
        $islastchapter = false;
        if (!$nextid) {
            $islastchapter = true;
        }

        book_view($book, $chapter, $islastchapter, $course, $cm, $context);

        // Book display HTML code.

        if ($toc = book_get_toc($chapters, $chapter, $book, $cm, 0)) {
            $toc = $OUTPUT->box($toc, 'collapse', 'book_toc');
            echo $toc;
        }
        // Info box.
        if ($book->intro) {
            echo $OUTPUT->box(format_module_intro('book', $book, $cm->id), 'generalbox', 'intro');
        }

        $navclasses = book_get_nav_classes();

        if ($book->navstyle) {
            // Upper navigation.
            echo '<div class="navtop border-top py-3 clearfix collapse show ' .
                $navclasses[$book->navstyle] . '">' . $chnavigation . '</div>';
        }

        // The chapter itself.
        $hidden = $chapter->hidden ? ' dimmed_text' : null;
        echo $OUTPUT->box_start('generalbox book_content collapse show' . $hidden);

        if (!$book->customtitles) {
            if (!$chapter->subchapter) {
                $currtitle = book_get_chapter_title($chapter->id, $chapters, $book, $context);
                echo $OUTPUT->heading($currtitle, 3);
            } else {
                $currtitle = book_get_chapter_title($chapters[$chapter->id]->parent, $chapters, $book, $context);
                $currsubtitle = book_get_chapter_title($chapter->id, $chapters, $book, $context);
                echo $OUTPUT->heading($currtitle, 3);
                echo $OUTPUT->heading($currsubtitle, 4);
            }
        }
        $chaptertext = file_rewrite_pluginfile_urls(
            $chapter->content,
            'pluginfile.php',
            $context->id, 'mod_book',
            'chapter',
            $chapter->id
        );
        echo format_text($chaptertext, $chapter->contentformat, array(
            'noclean' => true,
            'overflowdiv' => true,
            'context' => $context,
        ));

        echo $OUTPUT->box_end();

        if (core_tag_tag::is_enabled('mod_book', 'book_chapters')) {
            echo $OUTPUT->tag_list(core_tag_tag::get_item_tags('mod_book', 'book_chapters', $chapter->id), null, 'book-tags');
        }

        if ($book->navstyle) {
            // Lower navigation.
            echo '<div class="navbottom py-3 border-bottom clearfix ' . $navclasses[$book->navstyle] . '">' .
                $chnavigation . '</div>';
        }

        $contents = ob_get_contents();
        ob_end_clean();

        $PAGE->requires->js_call_amd('format_popups/book', 'init', array($context->id));

        return $contents;
    }
}
