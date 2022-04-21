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
 * Additional scripts for Multitopic course format.
 *
 * @module     format/multitopic
 * @copyright  Te Wānanga o Aotearoa
 * @author     Jeremy FitzPatrick
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import * as ModalFactory from 'core/modal_factory';
import * as Str from 'core/str';

/**
 * Set up the Multitopic course page with eventlistener
 *  for clicking add section controls.
 *
 * @param {number} maxsections maximum number of sections allowed.
 */
export const init = (maxsections) => {
    let tabcontent = document.getElementsByClassName("course-content");

    tabcontent[0].addEventListener('click', e => {
        let cantaddlink = e.target.matches('.cantadd.dimmed');
        if (cantaddlink === false) {
            // Maybe we clicked on a tab.
            cantaddlink = e.target.firstElementChild.matches('.cantadd.dimmed');
        }
        if (cantaddlink) {
            e.preventDefault();
            return ModalFactory.create({
                type: ModalFactory.types.ALERT,
                body: Str.get_string('maxsectionslimit', 'core', maxsections),
                title: Str.get_string('notice'),
                removeOnClose: false,
            })
                .then(modal => {
                    modal.show();
                    return modal;
                });
        }
    });
};
