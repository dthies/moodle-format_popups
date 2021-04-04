import config from 'core/config';
import Fragment from 'core/fragment';
import notification from 'core/notification';
import templates from 'core/templates';
var contextid, modname;

/**
 * Listen for form submisson
 *
 * @param int contextid Course module context id
 * @param string modnameModelue type
 */
export const init = (id, name) => {
    'use strict';
    contextid = id;
    modname = name;
    document.querySelector('#format_popups_activity_content').removeEventListener('submit',
        handleSubmit
    );
    document.querySelector('#format_popups_activity_content').removeEventListener('submit',
        handleSubmit,
        {capture: true}
    );
    document.querySelector('#format_popups_activity_content').addEventListener('submit',
        handleSubmit,
        {capture: true}
    );
};

/**
 * Submit form and load response
 *
 * @param object event
 */
const handleSubmit = (e) => {
    'use strict';
    let form = e.target.closest('form');
    if (form) {
        let formdata = new FormData(form),
            params = new URLSearchParams(formdata),
            data = params.toString();
        if ((config.wwwroot + '/mod/' + modname + '/view.php' !== form.getAttribute('action'))
            && (config.wwwroot + '/mod/scorm/player.php' !== form.getAttribute('action'))) {
            return;
        }

        e.preventDefault();
        e.stopPropagation();

        Fragment.loadFragment(
            'format_popups',
            'mod',
            contextid,
            {
                jsondata: JSON.stringify(data),
                modname: modname
            }
        ).then(function(html, js) {
            templates.replaceNodeContents('#format_popups_activity_content', html, js);
        }).fail(notification.exception);
    }
};
