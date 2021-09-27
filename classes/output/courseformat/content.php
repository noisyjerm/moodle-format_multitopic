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
 * Contains the main course format out class.
 *
 * @package   format_multitopic
 * @copyright 2019 onwards James Calder and Otago Polytechnic
 * @copyright based on work by 2020 Ferran Recio <ferran@moodle.com>
 * @copyright based on work by 2012 David Herney Bernal - cirano
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace format_multitopic\output\courseformat;

use core_courseformat\output\local\content as content_base;

/**
 * Base class to render a course format.
 *
 * @package   format_multitopic
 * @copyright 2019 onwards James Calder and Otago Polytechnic
 * @copyright based on work by 2020 Ferran Recio <ferran@moodle.com>
 * @copyright based on work by 2012 David Herney Bernal - cirano
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class content extends content_base {

    /**
     * Export this data so it can be used as the context for a mustache template (core/inplace_editable).
     *
     * @param renderer_base $output typically, the renderer that's calling this function
     * @return stdClass data context for a mustache template
     */
    public function export_for_template(\renderer_base $output) {
        global $USER;                               // INCLUDED from course/format/classes/output/local/content/section/cmlist.php .

        $format = $this->format;

        // ADDED.
        $course = $format->get_course();
        $sections = $this->format->fmt_get_sections();
        $this->fmtsections = $sections;
        $displaysection = $sections[$this->format->singlesectionid];
        // END ADDED.

        $user = $USER;                              // INCLUDED from course/format/classes/output/local/content/section/cmlist.php .

        // INCLUDED from course/format/classes/output/section_renderer.php print_single_section_page() .
        // Can we view the section in question?
        if (!($sectioninfo = $displaysection) || !$sectioninfo->uservisiblesan) { // CHANGED: Already have section info.
            // This section doesn't exist or is not available for the user.
            // We actually already check this in course/view.php but just in case exit from this function as well.
            throw new \moodle_exception(
                'unknowncoursesection',
                'error',
                course_get_url($course),
                format_string($course->fullname)
            );
        }
        // END INCLUDED.

        // INCLUDED list of sections parts
        // and /course/format/onetopic/renderer.php function print_single_section_page tabs parts CHANGED.

        // Init custom tabs.
        $tabs = array();
        $inactivetabs = array();

        $tabln = array_fill(FORMAT_MULTITOPIC_SECTION_LEVEL_ROOT + 1,
                            FORMAT_MULTITOPIC_SECTION_LEVEL_TOPIC - FORMAT_MULTITOPIC_SECTION_LEVEL_ROOT - 1, null);
        $sectionatlevel = array_fill(FORMAT_MULTITOPIC_SECTION_LEVEL_ROOT,
                                     FORMAT_MULTITOPIC_SECTION_LEVEL_TOPIC - FORMAT_MULTITOPIC_SECTION_LEVEL_ROOT, null);

        foreach ($sections as $thissection) {

            for ($level = $thissection->levelsan; $level < FORMAT_MULTITOPIC_SECTION_LEVEL_TOPIC; $level++) {
                $sectionatlevel[$level] = $thissection;
            }

            // Show the section if the user is permitted to access it, OR if it's not available
            // but there is some available info text which explains the reason & should display,
            // OR it is hidden but the course has a setting to display hidden sections as unavilable.
            $showsection = $thissection->uservisible ||
                    ($thissection->visible || !$course->hiddensections)
                    && ($thissection->available || !empty($thissection->availableinfo));

            // Make and add tabs for visible pages.
            if ($thissection->levelsan <= FORMAT_MULTITOPIC_SECTION_LEVEL_ROOT
                || $thissection->levelsan < FORMAT_MULTITOPIC_SECTION_LEVEL_TOPIC
                    && $sectionatlevel[$thissection->levelsan - 1]->uservisiblesan && $showsection) {

                $sectionname = get_section_name($course, $thissection);

                $url = course_get_url($course, $thissection);

                // REMOVED: marker.

                // Include main tab, and index tabs for pages with sub-pages.
                for ($level = max(FORMAT_MULTITOPIC_SECTION_LEVEL_ROOT + 1, $thissection->levelsan); /* ... */
                     $level <= $thissection->pagedepthdirect
                                + ($format->show_editor()
                                    && $thissection->pagedepthdirect < FORMAT_MULTITOPIC_SECTION_LEVEL_PAGE_USE ? 1 : 0); /* ... */
                     $level++) {

                    // Make tab.
                    $newtab = new \tabobject("tab_id_{$thissection->id}_l{$level}", $url,
                        \html_writer::tag('div', $sectionname, ['class' =>
                            'tab_content'
                            . ($thissection->currentnestedlevel >= $level ? ' marker' : '')
                            . (!$thissection->visible || !$thissection->available
                               || $level > $thissection->pagedepthdirect ? ' dimmed' : '')
                        ]),
                        $sectionname);
                    $newtab->level = $level - FORMAT_MULTITOPIC_SECTION_LEVEL_ROOT;

                    if ($thissection->id == $displaysection->id) {
                        $newtab->selected = true;
                    }

                    // Add tab.
                    if ($level <= FORMAT_MULTITOPIC_SECTION_LEVEL_ROOT + 1) {
                        $tabs[] = $newtab;
                    } else {
                        $tabln[$level - 1]->subtree[] = $newtab;
                    }
                    $tabln[$level] = $newtab;

                }

                // Disable tabs for hidden sections.
                if (!$thissection->uservisiblesan) {
                    $inactivetabs[] = "tab_id_{$thissection->id}_l{$thissection->levelsan}";
                }

            }

            // Include "add" sub-tabs if editing.
            if ($thissection->nextanyid == $thissection->nextpageid
                && $format->show_editor()) {

                // Include "add" sub-tabs for each level of page finished.
                $nextsectionlevel = $thissection->nextpageid ? $sections[$thissection->nextpageid]->levelsan
                                                            : FORMAT_MULTITOPIC_SECTION_LEVEL_ROOT;
                for ($level = min($sectionatlevel[FORMAT_MULTITOPIC_SECTION_LEVEL_TOPIC - 1]->pagedepthdirect + 1,
                                    FORMAT_MULTITOPIC_SECTION_LEVEL_PAGE_USE); /* ... */
                        $level >= $nextsectionlevel + 1; /* ... */
                        $level--) {

                    // Make "add" tab.
                    $straddsection = get_string_manager()->string_exists('addsectionpage', 'format_' . $course->format) ?
                                        get_string('addsectionpage', 'format_' . $course->format) : get_string('addsections');
                    $url = new \moodle_url('/course/format/multitopic/_course_changenumsections.php',
                        ['courseid' => $course->id,
                            'increase' => true,
                            'sesskey' => sesskey(),
                            'insertparentid' => $sectionatlevel[$level - 1]->id,
                            'insertlevel' => $level,                            // ADDED.
                        ]);
                    $icon = $output->pix_icon('t/switch_plus', $straddsection);
                    $newtab = new \tabobject("tab_id_{$sectionatlevel[$level - 1]->id}_l{($level - 1)}_add",
                        $url,
                        $icon,
                        s($straddsection));

                    // Add "add" tab.
                    if ($level <= FORMAT_MULTITOPIC_SECTION_LEVEL_ROOT + 1) {
                        $tabs[] = $newtab;
                    } else {
                        $tabln[$level - 1]->subtree[] = $newtab;
                    }
                    $tabln[$level] = null;

                }

            }

        }

        // Display tabs.
        $tabseft = (new \tabtree($tabs,
            "tab_id_{$displaysection->id}_l{$displaysection->pagedepthdirect}",
            $inactivetabs))->export_for_template($output);

        // END INCLUDED.

        // ADDED: Expand/collapse all sections.
        $collapsiblenum = 0;
        $thissection = $displaysection->nextanyid ? $sections[$displaysection->nextanyid] : null;
        while ($thissection && ($thissection->levelsan >= FORMAT_MULTITOPIC_SECTION_LEVEL_TOPIC)) {
            if ((format_multitopic_duration_as_days($thissection->periodduration) !== 0) && $thissection->uservisiblesan) {
                $collapsiblenum++;
            }
            $thissection = $thissection->nextanyid ? $sections[$thissection->nextanyid] : null;
        }
        $collapseexpandall = '';
        $collapseexpandall .= \html_writer::start_tag('div',
                array('class' => 'collapsible-actions', 'style' => $collapsiblenum ? '' : 'display: none;'));
        $collapseexpandall .= \html_writer::tag('a', get_string('expandall'),
                array('href' => '#', 'class' => 'collapseexpand expand-all', 'role' => 'button'));
        $collapseexpandall .= \html_writer::tag('a', get_string('collapseall'),
                array('href' => '#', 'class' => 'collapseexpand collapse-all', 'role' => 'button', 'style' => 'display: none;'));
        $collapseexpandall .= \html_writer::end_tag('div');
        // END ADDED.

        $addsection = new $this->addsectionclass($format);

        // Most formats uses section 0 as a separate section so we remove from the list.
        $sectionseft = $this->export_sections($output);
        $initialsection = '';
        if (!empty($sectionseft)) {
            $initialsection = array_shift($sectionseft);
        }

        $data = (object)[
            'title' => $format->page_title(), // This method should be in the course_format class.
            'tabs' => $tabseft,                                                 // ADDED.
            'collapseexpandall' => $collapseexpandall,                          // ADDED.
            'initialsection' => $initialsection,
            'sections' => $sectionseft,
            'numsections' => $addsection->export_for_template($output),
            'format' => $format->get_format(),
        ];

        // INCLUDED /course/format/onetopic/renderer.php function print_single_section_page utilities (parts).
        // Output the enable / disable button.
        $disableajax = false;
        if ($format->show_editor()) {

            $url = course_get_url($course, $displaysection, ['fmtedit' => true]);
            $url->param('sesskey', sesskey());

            if ($USER->onetopic_da[$course->id] ?? false) {
                $disableajax = true;
                $url->param('onetopic_da', 0);
                $buttontext = get_string_manager()->string_exists('activityclipboard_disable', 'format_multitopic') ?
                                get_string('activityclipboard_disable', 'format_multitopic') : get_string('disable');
            } else {
                $url->param('onetopic_da', 1);
                $buttontext = get_string_manager()->string_exists('activityclipboard_enable', 'format_multitopic') ?
                                get_string('activityclipboard_enable', 'format_multitopic') : get_string('enable');
            }

            // ADDED.
            $button = new \single_button($url, $buttontext, 'get');
            $button->disabled = $disableajax && ismoving($course->id);
            $data->movebutton = $button->export_for_template($output);
            // END ADDED.
        }
        // END INCLUDED.

        // REMOVED navigation.

        // INCLUDED from course/format/classes/output/local/content/section/cmlist.php export_for_template() .
        $data->showclipboard = $disableajax || ismoving($course->id);

        $showmovehere = ismoving($course->id);

        if ($showmovehere) {
            $data->showmovehere = true;
            $data->movingstr = strip_tags(get_string('activityclipboard', '', $user->activitycopyname));
            $data->cancelcopyurl = new \moodle_url('/course/mod.php', ['cancelcopy' => 'true', 'sesskey' => sesskey()]);
        }
        // END INCLUDED.

        return $data;
    }

    /**
     * Export sections array data.
     *
     * @param renderer_base $output typically, the renderer that's calling this function
     * @return array data context for a mustache template
     */
    protected function export_sections(\renderer_base $output): array {

        $format = $this->format;
        $course = $format->get_course();
        $modinfo = $this->format->get_modinfo();

        $sectionatlevel = array_fill(FORMAT_MULTITOPIC_SECTION_LEVEL_ROOT,
                                     FORMAT_MULTITOPIC_SECTION_LEVEL_TOPIC - FORMAT_MULTITOPIC_SECTION_LEVEL_ROOT, null);

        // Generate section list.
        $sectionseft = [];
        foreach ($this->get_sections_to_display($modinfo) as $thissection) {
            // The course/view.php check the section existence but the output can be called
            // from other parts so we need to check it.
            if (!$thissection) {
                throw new \moodle_exception('unknowncoursesection', 'error', course_get_url($course),
                    format_string($course->fullname));
            }

            $section = new $this->sectionclass($format, $thissection);

            for ($level = $thissection->levelsan; $level < FORMAT_MULTITOPIC_SECTION_LEVEL_TOPIC; $level++) {
                $sectionatlevel[$level] = $thissection;
            }

            // REMOVED: Section 0 differentiation and numsections.

            // Show the section if the user is permitted to access it, OR if it's not available
            // but there is some available info text which explains the reason & should display,
            // OR it is hidden but the course has a setting to display hidden sections as unavilable.
            $showsection = $thissection->uservisible ||
                    ($thissection->visible || !$course->hiddensections)
                    && ($thissection->available || !empty($thissection->availableinfo));
            // REMOVED: return if section hidden (we may have more to do), and coursedisplay.

            if ($thissection->levelsan <= FORMAT_MULTITOPIC_SECTION_LEVEL_ROOT
                || $sectionatlevel[$level - 1]->uservisiblesan && $showsection) {   // ADDED.
                $pageid = ($thissection->levelsan < FORMAT_MULTITOPIC_SECTION_LEVEL_TOPIC) ? $thissection->id
                                                                                           : $thissection->parentid;
                $onpage = ($pageid == $format->singlesectionid);
                if ($thissection->uservisible && ($onpage || $format->show_editor())) {
                    $sectionseft[] = $section->export_for_template($output);
                }

            }

        }
        return $sectionseft;
    }

    /**
     * Return an array of sections to display.
     *
     * This method is used to differentiate between display a specific section
     * or a list of them.
     *
     * @param course_modinfo $modinfo the current course modinfo object
     * @return section_info[] an array of section_info to display
     */
    private function get_sections_to_display(\course_modinfo $modinfo): array {
        return $this->fmtsections;
    }

}
