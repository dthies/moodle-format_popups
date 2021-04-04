import notification from 'core/notification';
import templates from 'core/templates';

var contextid;
/**
 * Initialise listeners to folder resource
 *
 * @param int contextid Course module context id
 */
export const init = (id) => {
    'use strict';
    contextid = id;
    document.querySelector('#format_popups_activity_content').removeEventListener('click', embedFiles);
    document.querySelector('#format_popups_activity_content').addEventListener('click', embedFiles);
};

/**
 * Display resource when selected
 *
 * @param object event
 */
const embedFiles = (e) => {
    'use strict';
    let anchor = e.target.closest('div.foldertree a');

    if (anchor) {
        let url = new URL(anchor.getAttribute('href')),
            path = url.pathname.split('/').slice(-5);
        if (!url.searchParams.get('forcedownload') && path[0] == contextid) {
            let isimage = path[4].search('.') !== -1 &&
                ['gif', 'jpg',  'jpeg', 'png', 'svg',].includes(path[4].split('.').pop());
            e.preventDefault();
            e.stopPropagation();
            templates.render(
                'format_popups/embedfile',
                {
                    heading: decodeURI(path[4]),
                    image: isimage,
                    url: url.toString(),
                    params: [
                        {
                            name: 'forcedownload',
                            value: 1
                        }
                    ]
                }
            ).then(
                templates.replaceNodeContents.bind(templates, '#format_popups_activity_content')
            ).fail(notification.exception);
        }
    }
};
