var iframe;

/**
 * Iniatialie listener to resize iframe in modal
 */
export const init = () => {
    'use strict';

    window.removeEventListener('resize', resize);

    iframe = document.querySelector(
        '#format_popups_activity_content img, #format_popups_activity_content iframe'
    );

    window.removeEventListener('resize', resize);

    if (iframe) {
        iframe.addEventListener('load', resize, {once: true});
        window.addEventListener('resize', resize);

        resize();
    }
};

/*
 * Resize the iframe to fit window size
 */
export const resize = () => {
    'use strict';

    if (!iframe) {
        window.removeEventListener('resize', resize);
        return;
    }

    let modalbody = document.getElementById('format_popups_activity_content').closest('.modal-body'),
        maxheight = window.innerHeight;

    iframe.setAttribute('height', maxheight);

    iframe.setAttribute('height', modalbody.offsetHeight + maxheight - modalbody.firstChild.offsetHeight - 40);
};
