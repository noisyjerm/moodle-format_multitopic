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

namespace format_multitopic\output\courseformat\state;

global $CFG;
require_once($CFG->dirroot . '/course/format/multitopic/lib.php');

use core_courseformat\output\local\state\course as base_course;

use core_courseformat\base as course_format;
use course_modinfo;
use moodle_url;
use renderable;
use stdClass;

/**
 * Contains the ajax update course structure.
 *
 * @package   core_course
 * @copyright 2021 Ferran Recio <ferran@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course extends base_course {

    /** @var course_format the course format class */
    protected $format;

    /**
     * Constructor.
     *
     * @param course_format $format the course format
     */
    public function __construct(course_format $format) {
        $this->format = $format;
    }

    /**
     * Export this data so it can be used as state object in the course editor.
     *
     * @param renderer_base $output typically, the renderer that's calling this function
     * @return stdClass data context for a mustache template
     */
    public function export_for_template(\renderer_base $output): stdClass {
        $format = $this->format;
        $course = $format->get_course();
        // State must represent always the most updated version of the course.
        $modinfo = course_modinfo::instance($course);

        $url = new moodle_url('/course/view.php', ['id' => $course->id]);

        $data = (object)[
            'id' => $course->id,
            'numsections' => $format->get_last_section_number(),
            'sectionlist' => [],
            'firstsectionlist' => [],
            'secondsectionlist' => [],
            'editmode' => $format->show_editor(),
            'highlighted' => $format->get_section_highlighted_name(),
            'maxsections' => $format->get_max_sections(),
            'baseurl' => $url->out(),
            'statekey' => course_format::session_cache($course),
        ];

        $subtabs = [];

       // $sections = $modinfo->get_section_info_all();
        $format = course_get_format($course->id);
        $sections = $format->fmt_get_sections();
        $currentsectionid = 615; // Todo: how do i find the current tab??
        $parentindex = -1;

        foreach ($sections as $section) {
            if ($section->uservisible) {
                $data->sectionlist[] = $section->id;

                if ($section->level === 0) {
                    $data->secondsectionlist[] = [$section->id]; // Tabs uses first item as parent, Course index might not.
                    $parentindex ++;
                    $data->firstsectionlist[] = $section->id;
                }
                if ($section->level == 1) {
                    $data->secondsectionlist[$parentindex][] = $section->id;
                }
            }
        }

        //$data->secondsectionlist = $subtabs[$current->parentid];
        //$data->current = $currentsectionid;

        return $data;
    }
}
