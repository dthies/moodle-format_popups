import config from 'core/config';
import Fragment from 'core/fragment';
import notification from 'core/notification';
import templates from 'core/templates';

var contextid;
/**
 * Initial listerners to handle book navigation
 *
 * @param int contextid Course module context id
 */
export const init = (id) => {
    'use strict';
    let content = document.querySelector('#format_popups_activity_content');
    contextid = id;
    content.removeEventListener('click', loadChapter);
    content.addEventListener('click', loadChapter);
    content.querySelectorAll('a').forEach(function(anchor) {
        let href = anchor.getAttribute('href');
        if (href && href.search('view.php') === 0) {
            anchor.setAttribute('href', config.wwwroot + '/mod/book/' + href);
        }
    });
    if (content.querySelector('#book_toc') && !document.querySelector('#toggletoc')) {
        templates.render('format_popups/booktoc', []).then(templates.prependNodeContents.bind(
            templates,
            content.closest('.modal-content').querySelector('.modal-title')
        ));
    }
};

/**
 * Load chapter from navigatioin
 *
 * @param object event
 */
export const loadChapter = (e) => {
    'use strict';
    let anchor = e.target.closest('a.bookprev, a.booknext, .book_toc a');

    if (e.target.closest('a.bookexit')) {
        e.preventDefault();
    }
    if (anchor) {
        let chapterid = anchor.getAttribute('data-chapterid') ||
            (new URLSearchParams(anchor.getAttribute('href'))).get('chapterid'),
            params = new URLSearchParams({
                chapterid: chapterid
            });
        e.preventDefault();
        e.stopPropagation();
        Fragment.loadFragment(
            'format_popups',
            'mod',
            contextid,
            {
                jsondata: JSON.stringify(params.toString()),
                modname: 'book'
            }
        ).then(function(html, js) {
            templates.replaceNodeContents('#format_popups_activity_content', html, js);
        }).fail(notification.exception);
    }
};
