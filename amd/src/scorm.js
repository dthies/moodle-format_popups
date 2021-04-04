var maxheight;

/**
 * Properly size scorm iframe
 */
export const init = () => {
    'use strict';

    let iframe = document.getElementById('format_popups_scorm_iframe');
    maxheight = iframe.getAttribute('height');

    window.removeEventListener('resize', resize);
    window.addEventListener('resize', resize);

    iframe.addEventListener('load', resize, {once: true});
};

/**
 * Risize iframe to window height
 *
 * @param int maxheight Maximum height to use for iframe
 */
const resize = () => {
    'use strict';

    let modalbody = document.getElementById('format_popups_activity_content').closest('.modal-body'),
        iframe = document.getElementById('format_popups_scorm_iframe');

    iframe.setAttribute('height', maxheight);

    iframe.setAttribute('height', modalbody.offsetHeight - 40);
};
