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
 * Contains the default section course format output class.
 *
 * @package   format_multitopic
 * @copyright 2019 onwards James Calder and Otago Polytechnic
 * @copyright based on work by 2020 Ferran Recio <ferran@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_multitopic\output\courseformat\content;

use core_courseformat\output\local\content\section as section_base;

/**
 * Base class to render a course section.
 *
 * @package   format_multitopic
 * @copyright 2019 onwards James Calder and Otago Polytechnic
 * @copyright based on work by 2020 Ferran Recio <ferran@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class section extends section_base {

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output typically, the renderer that's calling this function
     * @return stdClass data context for a mustache template
     */
    public function export_for_template(\renderer_base $output): \stdClass {

        $format = $this->format;
        $course = $format->get_course();
        $thissection = $this->thissection;
        // REMOVED singlesection.

        $summary = new $this->summaryclass($format, $thissection);
        $availability = new $this->availabilityclass($format, $thissection);

        $pageid = ($thissection->levelsan < FORMAT_MULTITOPIC_SECTION_LEVEL_TOPIC) ? $thissection->id
                                                                                           : $thissection->parentid;

        $data = (object)[
            'num' => $thissection->section ?? '0',
            'id' => $thissection->id,
            'sectionreturnid' => $thissection->section,                         // CHANGED.
            'summary' => $summary->export_for_template($output),
            'availability' => $availability->export_for_template($output),
            'fmtonpage' => $pageid == $format->singlesectionid,                 // ADDED.
        ];

        // ADDED.
        $sectionstyle = '';
        // Determine the section type.
        if ($thissection->levelsan < FORMAT_MULTITOPIC_SECTION_LEVEL_TOPIC) {
            $sectionstyle .= ' section-page';
        } else if (format_multitopic_duration_as_days($thissection->periodduration) === 0) {
            $sectionstyle .= ' section-topic section-topic-untimed';
        } else {
            $sectionstyle .= ' section-topic section-topic-timed section-collapsed';
        }

        $sectionstyle .= " sectionid-{$thissection->id}";
        $data->fmtclasses = $sectionstyle;
        // END ADDED.

        // REMOVED stealth sections.

        if ($format->show_editor()) {
            if (empty($this->hidecontrols)) {
                $controlmenu = new $this->controlmenuclass($format, $thissection);
                $data->controlmenu = $controlmenu->export_for_template($output);
            }
                $data->cmcontrols = $output->course_section_add_cm_control($course, $thissection->section);
        }

        // REMOVED coursedisplay setting.

        if ($course->id == SITEID) {
            $data->sitehome = true;
        }

        // For now sections are always expanded. User preferences will be done in MDL-71211.
        $data->isactive = true;

        // REMOVED section 0 special case.

        // When a section is displayed alone the title goes over the section, not inside it.
        $header = new $this->headerclass($format, $thissection);

        // REMOVED singlesection code.

        if (empty($this->hidetitle)) {
            $data->header = $header->export_for_template($output);
        }

        // REMOVED index code.

        // Add the cm list.
        if ($thissection->uservisible) {
            $cmlist = new $this->cmlistclass($format, $thissection);
            $data->cmlist = $cmlist->export_for_template($output);
        }

        if (!$thissection->visible) {
            $data->ishidden = true;
        }
        if ($format->is_section_current($thissection)) {
            $data->iscurrent = true;
            $data->currentlink = get_accesshide(
                get_string('currentsection', 'format_'.$format->get_format())
            );
        }

        return $data;
    }
}
