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

namespace format_multitopic\courseformat;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/course/format/multitopic/lib.php');

use core_courseformat\base as course_format;
use core_courseformat\stateupdates;
use cm_info;
use section_info;
use stdClass;
use course_modinfo;
use moodle_exception;
use context_module;
use context_course;
use cache;


/**
 * Contains the Multitopic course state actions.
 *
 * The methods from this class should be executed via "core_courseformat_edit" web service.
 *
 * @package   format_multitopic
 * @copyright 2019 onwards James Calder and Otago Polytechnic
 * @copyright based on work by 2020 Ferran Recio <ferran@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class stateactions extends \core_courseformat\stateactions {

    /**
     * Move course sections to another location in the same course.
     *
     * @param stateupdates $updates the affected course elements track
     * @param stdClass $course the course object
     * @param int[] $ids the list of affected course module ids
     * @param int $targetsectionid optional target section id
     * @param int $targetcmid optional target cm id
     */
    public function section_move(
        stateupdates $updates,
        stdClass $course,
        array $ids,
        ?int $targetsectionid = null,
        ?int $targetcmid = null
    ): void {
        // Validate target elements.
        if (!$targetsectionid) {
            throw new moodle_exception("Action cm_move requires targetsectionid");
        }

        $this->validate_sections($course, $ids, __FUNCTION__);

        $coursecontext = context_course::instance($course->id);
        require_capability('moodle/course:movesections', $coursecontext);

        $modinfo = get_fast_modinfo($course);

        // Target section.
        $this->validate_sections($course, [$targetsectionid], __FUNCTION__);
        $targetsection = $modinfo->get_section_info_by_id($targetsectionid, MUST_EXIST);

        $format = course_get_format($course->id);
        // $targetsection = $format->fmt_get_section($targetsectionid); // Not sure why this won't work.

        // Todo: Find another way to get this info.
        global $DB;
        $fmtoption1 = $DB->get_record('course_format_options', [
            'courseid' => $course->id,
            'format' => 'multitopic',
            'name' => 'level',
            'sectionid' => $targetsectionid]);
        $fmtoption2 = $DB->get_record('course_format_options', [
            'courseid' => $course->id,
            'format' => 'multitopic',
            'name' => 'level',
            'sectionid' => $ids[0]]);

        if ($fmtoption1->value !== $fmtoption2->value) {
            // Prevent moving to a different level because it's just too messy otherwise.
            return;
        }

        $affectedsections = [$targetsection->section => true];

        $sections = $this->get_section_info($modinfo, $ids);
        foreach ($sections as $section) {
            $affectedsections[$section->section] = true;
            move_section_to($course, $section->section, $targetsection->section);
        }

        // Use section_state to return the section and activities updated state.
        $this->section_state($updates, $course, $ids, $targetsectionid);

        // All course sections can be renamed because of the resort.
        $allsections = $modinfo->get_section_info_all();
        foreach ($allsections as $section) {
            // Ignore the affected sections because they are already in the updates.
            if (isset($affectedsections[$section->section])) {
                continue;
            }
            $updates->add_section_put($section->id);
        }
        // The section order is at a course level.
        $updates->add_course_put();


    }

}
