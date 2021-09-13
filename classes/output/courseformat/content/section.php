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
 * @package   core_courseformat
 * @copyright 2020 Ferran Recio <ferran@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_multitopic\output\courseformat\content;

use core_courseformat\output\local\content\section as section_base;

/**
 * Base class to render a course section.
 *
 * @package   core_courseformat
 * @copyright 2020 Ferran Recio <ferran@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class section extends section_base {

    /**
     * Hide the section title.
     *
     * This is used on blocks or in the home page where an isolated section is displayed.
     */
    public function hide_title(): void {
        $this->hidetitle = true;
    }

    /**
     * Hide the section controls.
     *
     * This is used on blocks or in the home page where an isolated section is displayed.
     */
    public function hide_controls(): void {
        $this->hidecontrols = true;
    }

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
        $singlesection = $format->get_section_number();

        $summary = new $this->summaryclass($format, $thissection);
        $availability = new $this->availabilityclass($format, $thissection);

        $data = (object)[
            'num' => $thissection->section ?? '0',
            'id' => $thissection->id,
            'sectionreturnid' => $singlesection,
            'summary' => $summary->export_for_template($output),
            'availability' => $availability->export_for_template($output),
            'fmtonpage' => $singlesection <= $thissection->section
                            && $thissection->section <= $format->fmtlastsectionnum,
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

        if ($format->show_editor()) {
            if (empty($this->hidecontrols)) {
                $controlmenu = new $this->controlmenuclass($format, $thissection);
                $data->controlmenu = $controlmenu->export_for_template($output);
            }
                $data->cmcontrols = $output->course_section_add_cm_control($course, $thissection->section, $singlesection);
        }

        // When a section is displayed alone the title goes over the section, not inside it.
        $header = new $this->headerclass($format, $thissection);

        if (empty($this->hidetitle)) {
            $data->header = $header->export_for_template($output);
        }

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

