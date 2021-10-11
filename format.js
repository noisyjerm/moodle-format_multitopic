// Javascript functions for Multitopic course format.

M.course = M.course || {};

M.course.format = M.course.format || {};

/**
 * Get sections config for this format.
 *
 * The section structure is:
 * <ul class="sections">
 *  <li class="section">...</li>
 *  <li class="section">...</li>
 *   ...
 * </ul>
 *
 * @return {object} section list configuration
 */
/* eslint-disable camelcase */
M.course.format.get_config = function() {
    return {
        container_node: 'ul',
        container_class: 'sections', // CHANGED.
        section_node: 'li',
        section_class: 'section'
    };
};
/* eslint-enable camelcase */

/**
 * Swap section.
 *
 * @param {YUI} Y YUI3 instance
 * @param {string} node1 node to swap to
 * @param {string} node2 node to swap with
 */
M.course.format.swap_sections = function(Y, node1, node2) {
    var CSS = {
        COURSECONTENT: 'course-content',
        SECTIONADDMENUS: 'section_add_menus'
    };

    var sectionlist = Y.Node.all('.' + CSS.COURSECONTENT + ' ' + M.course.format.get_section_selector(Y));
    // Swap the non-ajax menus, noting these are not always present (depends on theme and user prefs).
    if (sectionlist.item(node1).one('.' + CSS.SECTIONADDMENUS)) {
        sectionlist.item(node1).one('.' + CSS.SECTIONADDMENUS).swap(sectionlist.item(node2).one('.' + CSS.SECTIONADDMENUS));
    }
};

/**
 * Process sections after ajax response.
 *
 * @param {YUI} Y YUI3 instance
 * @param {NodeList} sectionlist of sections
 * @param {array} response ajax response
 * @param {string} sectionfrom first affected section
 * @param {string} sectionto last affected section
 */
/* eslint-disable camelcase */
M.course.format.process_sections = function(Y, sectionlist, response, sectionfrom, sectionto) {
    /* eslint-enable camelcase */
    var CSS = {
        SECTIONNAME: 'sectionname'
    },
    SELECTORS = {
        SECTIONLEFTSIDE: '.left .section-handle .icon'
    };

    if (response.action == 'move') {
        // If moving up swap around 'sectionfrom' and 'sectionto' so the that loop operates.
        if (sectionfrom > sectionto) {
            var temp = sectionto;
            sectionto = sectionfrom;
            sectionfrom = temp;
        }

        // Update titles and move icons in all affected sections.
        var ele, str, stridx, newstr;

        for (var i = sectionfrom; i <= sectionto; i++) {
            // Update section title.
            var content = Y.Node.create('<span>' + response.sectiontitles[i] + '</span>');
            sectionlist.item(i).all('.' + CSS.SECTIONNAME).setHTML(content);
            // Update the drag handle.
            ele = sectionlist.item(i).one(SELECTORS.SECTIONLEFTSIDE).ancestor('.section-handle');
            str = ele.getAttribute('title');
            stridx = str.lastIndexOf(' ');
            newstr = str.substr(0, stridx + 1) + i;
            ele.setAttribute('title', newstr);
            // Update the aria-label for the section.
            // REMOVED.

            // INCLUDED /course/format/weeks/format.js M.course.format.process_sections part.
            // Remove the current class as section has been moved.
            sectionlist.item(i).removeClass('current');
            // END INCLUDED.

        }
        // INCLUDED /course/format/weeks/format.js M.course.format.process_sections part.
        // If there is a current section, apply corresponding class in order to highlight it.
        if (response.current !== -1) {
            // Add current class to the required section.
            sectionlist.item(response.current).addClass('current');
        }
        // END INCLUDED.
    }
};

// REMAINDER ADDED.

/**
 * Expand, and scroll to, the section specified in the URL bar.
 *
 * @param {HashChangeEvent?} event The triggering event, if any
 */
 M.course.format.fmtCollapseOnHashChange = function(event) {

    // Find the specified section.
    var anchor = window.location.hash.substr(1);
    var selSectionDom = anchor ?
                    document.querySelector("body.format-multitopic .course-content ul.sections li.section.section-topic." + anchor)
                    : null;

    // Exit if there is no recognised section.
    if (!selSectionDom) {
        return;
    }

    // Expand, if appropriate.
    var sectionContentDom = selSectionDom.querySelector(":scope .content.course-content-item-content");
    if (sectionContentDom.classList.contains("collapse") && !sectionContentDom.classList.contains("show")) {
        sectionContentDom.classList.add("show");
        selSectionDom.querySelector(":scope .icons-collapse-expand").classList.remove("collapsed");
    }

    // Scroll to the specified section.
    if (selSectionDom) {
        selSectionDom.scrollIntoView();
    }

};

/**
 * Initialise: Set the initial state of collapsible sections, and watch for user input.
 */
 M.course.format.fmtCollapseInit = function() {

    // Set the initial state of collapsible sections.
    M.course.format.fmtCollapseOnHashChange();

    // Capture clicks on course section links.
    window.addEventListener("hashchange", M.course.format.fmtCollapseOnHashChange);

};

// Run initialisation when the page is loaded, or now, if the page is already loaded.
if (document.readyState == "loading") {
    document.addEventListener("DOMContentLoaded", M.course.format.fmtCollapseInit);
} else {
    M.course.format.fmtCollapseInit();
}
