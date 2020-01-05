// These changes would need to be made to Moodle to support the experimental changes in this branch.

// course/yui/src/dragdrop/js/section.js
// course/yui/build/moodle-course-dragdrop/moodle-course-dragdrop-debug.js

// ...

Y.extend(DRAGSECTION, M.core.dragdrop, {

    // ...

    get_section_index: function(node) {
        var sectionlistselector = '.' + CSS.COURSECONTENT + ' ' + M.course.format.get_section_selector(Y),
            sectionList = Y.all(sectionlistselector),
            nodeIndex = sectionList.indexOf(node),
            firstIndex = sectionList.indexOf(Y.one('[id^=section-]')),
            firstNum = parseInt(sectionList.item(firstIndex).get("id").match(/section-(\d+)/)[1]);

        return (nodeIndex - firstIndex + firstNum);
    },


    drop_hit: function(e) {

    // ...

        Y.io(uri, {

            // ...

            on: {

            // ...

                success: function(tid, response) {
                    var firstIndex = sectionlist.indexOf(Y.one('[id^=section-]')),
                        firstNum = parseInt(sectionlist.item(firstIndex).get("id").match(/section-(\d+)/)[1]);

            // ...

                    do {
                        // ...
                        for (index = loopstart - firstNum; index <= loopend - firstNum; index++) {
                            // ...
                        }
                        // ...
                    } while (swapped);

                    // ...

                },

                // ...
            },
            // ...
        });
    }

// ...
});

// ...