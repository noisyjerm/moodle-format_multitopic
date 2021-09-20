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
 * Contains the default section header format output class.
 *
 * @package   format_multitopic
 * @copyright 2019 onwards James Calder and Otago Polytechnic
 * @copyright based on work by 2020 Ferran Recio <ferran@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_multitopic\output\courseformat\content\section;

require_once(__DIR__.'/../../../../../../../../config.php');
require_login();

use core_courseformat\output\local\content\section\header as header_base;

/**
 * Base class to render a section header.
 *
 * @package   format_multitopic
 * @copyright 2019 onwards James Calder and Otago Polytechnic
 * @copyright based on work by 2020 Ferran Recio <ferran@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class header extends header_base {

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output typically, the renderer that's calling this function
     * @return array data context for a mustache template
     */
    public function export_for_template(\renderer_base $output): \stdClass {
        global $CFG;

        $format = $this->format;
        $section = $this->section;
        $course = $format->get_course();

        $data = (object)[
            'num' => $section->section,
            'id' => $section->id,
        ];

        if (!$CFG->linkcoursesections
                && ($section->section == $format->get_section_number() || $section->periodduration == '0 day')) {
            // Regular section title.
            $data->title = $output->section_title_without_link($section, $course);
            $data->issinglesection = true;
        } else {
            // Regular section title.
            $data->title = $output->section_title($section, $course);
        }

        if (!$section->visible) {
            $data->ishidden = true;
        }

        $coursedisplay = $course->coursedisplay ?? COURSE_DISPLAY_SINGLEPAGE;

        if (!$format->show_editor() && $coursedisplay == COURSE_DISPLAY_MULTIPAGE && empty($data->issinglesection)) {
            $data->url = course_get_url($course, $section->section);
            $data->name = get_section_name($course, $section);
        }

        return $data;
    }
}
