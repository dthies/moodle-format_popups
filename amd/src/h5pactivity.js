import config from 'core/config';
import Fragment from 'core/fragment';
import notification from 'core/notification';
import templates from 'core/templates';

var contextid, modname, instance;
/**
 * Initialize Choice mod actions
 *
 * @param int contextid Course module context id
 * @param string modname Activity type
 */
export const init = (id, name, instanceid) => {
    'use strict';
    contextid = id;
    modname = name;
    instance = instanceid;

    document.querySelector('#format_popups_activity_content').removeEventListener('click', showReport);
    document.querySelector('#format_popups_activity_content').addEventListener('click', showReport);
};

/**
 * Show report page
 *
 * @param string response text
 */
const showReport = (e) => {
    let anchor = e.target.closest('a');
    if (anchor) {
        let url = new URL(anchor.getAttribute('href')),
            params = url.searchParams;

        if (url.origin + url.pathname === config.wwwroot + '/mod/h5pactivity/report.php' && params.get('a') === instance) {
            e.preventDefault();
            e.stopPropagation();
            Fragment.loadFragment(
                'format_popups',
                'mod',
                contextid,
                {
                    jsondata: JSON.stringify(params.toString()),
                    modname: modname
                }
            ).then(function(html, js) {
                templates.replaceNodeContents('#format_popups_activity_content', html, js);
            }).fail(notification.exception);
        }
    }
};
