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
        global $CFG;                                                            // ADDED.

        $format = $this->format;
        $section = $this->section;
        $course = $format->get_course();

        $data = (object)[
            'num' => $section->section,
            'id' => $section->id,
        ];

        // REMOVED stealth sections.
        if (!$CFG->linkcoursesections                                           // CHANGED link condition.
                && ($section->levelsan < FORMAT_MULTITOPIC_SECTION_LEVEL_TOPIC
                    || ((($section->collapsible != '') ? $section->collapsible : $course->collapsible) == '0') )) {
            // Regular section title.
            $data->title = $output->section_title_without_link($section, $course);
        } else if ($section->uservisible || $section->section == 0) {
            // Regular section title.
            $data->title = $output->section_title($section, $course);
        } else {
            // Regular section title without link.
            $data->title = $output->section_title_without_link($section, $course);
        }

        // ADDED.
        $data->fmticon = $section->levelsan < FORMAT_MULTITOPIC_SECTION_LEVEL_TOPIC ?
                            'icon fa fa-folder-o fa-fw' : 'icon fa fa-list fa-fw';
        // END ADDED.

        if (!$section->visible) {
            $data->ishidden = true;
        }

        if ($course->id == SITEID) {
            $data->sitehome = true;
        }

        // REMOVED index page.

        $data->name = get_section_name($course, $section);

        return $data;
    }
}
