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
 * Renderer for outputting the Multitopic course format.
 *
 * @package   format_multitopic
 * @copyright 2019 onwards James Calder and Otago Polytechnic
 * @copyright based on work by 2012 Dan Poltawski
 * @copyright based on work by 2020 Ferran Recio <ferran@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since     Moodle 2.3
 */

namespace format_multitopic\output;

use core_courseformat\output\section_renderer;
use moodle_page;

// ADDED.
defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/../../lib.php');
require_once(__DIR__ . '/../../classes/courseheader.php');
require_once(__DIR__ . '/../../classes/coursecontentheaderfooter.php');
// END ADDED.

/**
 * Basic renderer for Multitopic format.
 *
 * @copyright 2019 onwards James Calder and Otago Polytechnic
 * @copyright based on work by 2012 Dan Poltawski
 * @copyright based on work by 2020 Ferran Recio <ferran@moodle.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends section_renderer {

    /**
     * Constructor method, calls the parent constructor.
     *
     * @param moodle_page $page
     * @param string $target one of rendering target constants
     */
    public function __construct(\moodle_page $page, string $target) {
        parent::__construct($page, $target);

        // REMOVED: Marker stuff.

        // ADDED.
        // If we're on the view page, patch the URL to use the section ID instead of section number.
        if ($this->page->url->compare(new \moodle_url('/course/view.php'), URL_MATCH_BASE)
            && ($id = optional_param('id', null, PARAM_INT))) {
            $params = ['id' => $id];
            if ($sectionid = optional_param('sectionid', null, PARAM_INT)) {
                $params['sectionid'] = $sectionid;
            }
            $this->page->set_url('/course/view.php', $params);                  // TODO: Replace just section param, not whole url?
        }
        // END ADDED.

    }

    /**
     * Renders the provided widget and returns the HTML to display it.
     *
     * Course format templates uses a similar subfolder structure to the renderable classes.
     * This method find out the specific template for a course widget. That's the reason why
     * this render method is different from the normal plugin renderer one.
     *
     * course format templatables can be rendered using the core_course/local/* templates.
     * Format plugins are free to override the default template location using render_xxx methods as usual.
     *
     * @param renderable $widget instance with renderable interface
     * @return string the widget HTML
     */
    public function render(\renderable $widget) {
        global $CFG;
        $fullpath = str_replace('\\', '/', get_class($widget));
        $classparts = explode('/', $fullpath);
        // Strip namespaces.
        $classname = array_pop($classparts);
        // Remove _renderable suffixes.
        $classname = preg_replace('/_renderable$/', '', $classname);

        $rendermethod = 'render_' . $classname;
        if (method_exists($this, $rendermethod)) {
            return $this->$rendermethod($widget);
        }
        // Check for special course format templatables.
        if ($widget instanceof \templatable) {
            // Templatables from both core_courseformat\output\xxx_format\* and format_xxx\output\xxx_format\*
            // use core_crouseformat/local/xxx_format templates by default.
            $corepath = 'core_courseformat\/output\/local';
            $pluginpath = 'format_.+\/output\/courseformat';
            $specialrenderers = '/^(?<componentpath>' /*. $corepath . '|'*/ . $pluginpath . ')\/(?<template>.+)$/'; // CHANGED.
            $matches = null;

            if (preg_match($specialrenderers, $fullpath, $matches)) {
                $data = $widget->export_for_template($this);
                return $this->render_from_template('format_multitopic/courseformat/' . $matches['template'], $data);    // CHANGED.
            }
        }
        // If nothing works, let the parent class decide.
        return parent::render($widget);
    }

    /**
     * Generate the section title, wraps it in a link to the section if section is collapsible.
     *
     * @param section_info|stdClass $section The course_section entry from DB
     * @param stdClass $course The course entry from DB
     * @param bool $linkifneeded Whether to add link
     * @return string HTML to output.
     */
    public function section_title($section, $course, bool $linkifneeded = true) : string {
        // CHANGED LINE ABOVE.

        // ADDED.
        $section = course_get_format($course)->fmt_get_section($section);

        // Date range for the topic, to be placed under the title.
        $datestring = '';
        if (isset($section->dateend) && ($section->datestart < $section->dateend)) {

            $dateformat = get_string('strftimedateshort');
            $startday = userdate($section->datestart + 12 * 60 * 60, $dateformat);
            $endday = userdate($section->dateend - 12 * 60 * 60, $dateformat);

            if ($startday == $endday) {
                $datestring = "({$startday})";
            } else {
                $datestring = "({$startday}–{$endday})";
            }

        }
        // END ADDED.

        return $this->render(course_get_format($course)->inplace_editable_render_section_name($section, $linkifneeded))
                . \html_writer::empty_tag('br')
                . \html_writer::tag('span', $datestring, ['class' => 'section_subtitle']); // CHANGED.
    }

    /**
     * Generate the section title to be displayed on the section page, without a link.
     *
     * @param section_info|stdClass $section The course_section entry from DB
     * @param int|stdClass $course The course entry from DB
     * @return string HTML to output.
     */
    public function section_title_without_link($section, $course) : string {
        return $this->section_title($section, $course, false);                  // CHANGED.
    }

    // ADDED.
    /**
     * Generate HTML for course header: A banner with the course title and a slice of the course image.
     *
     * @param \format_multitopic\courseheader $header header to render
     * @return string HTML to output.
     */
    protected function render_courseheader(\format_multitopic\courseheader $header) : string {
        return $header->output();
    }

    /**
     * Generate HTML for course content header/footer: Back to course button.
     *
     * @param \format_multitopic\coursecontentheaderfooter $headerfooter header/footer to render
     * @return string HTML to output.
     */
    protected function render_coursecontentheaderfooter(
                            \format_multitopic\coursecontentheaderfooter $headerfooter) : string {
        return $headerfooter->output();
    }
    // END ADDED.

}
