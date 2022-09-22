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
 * AMD Module to add AJAX navigation to assign modal content
 *             adapted from format_popups/choice.js
 *
 * @package    format_popups
 * @copyright  2022 Manuel Mejia <manimejia.me@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([
    'core/config',
    'core/fragment',
    'core/notification',
    'core/templates'
], function(config,Fragment,notification,templates) {
    var contextid, modname;

    /**
     * 
     * @param {DOM Object} form 
     * @param {boolean} asParams 
     * @returns object or string
     */
    var extractFormData = function(form, asParams){
        let formdata = new FormData(form);
        let data = {};
        let params = '';
        formdata.forEach(function(value, key){
            data[key] = value;
            params += key + "=" + value + "&";
        });
        // data = {},
        // params = '';
        // formdata.forEach((value, key) => {
        //     // Reflect.has in favor of: object.hasOwnProperty(key)
        //     if(!Reflect.has(data, key)){
        //         data[key] = value;
        //         params += key + "=" + data + "&";
        //         return;
        //     }
        //     if(!Array.isArray(data[key])){
        //         data[key] = [data[key]];
        //         params += key + "=" + data;
        //     }
        //     data[key].push(value);
        //     params += key + "=" + data;
        // });
        if(asParams){
            return params;
            //return json_encode($data);
        }else{
            return data;
        }
    }
    /**
     * Callback to submit form and load response
     *
     * @param {object} e event
     */
    var handleSubmit = function(e){
        'use strict';
        let form = e.target.closest('form');
        if (form) {
            e.preventDefault();
            e.stopPropagation();

            if (e.submitter.getAttribute('name') == 'cancel'){
                // TODO close modal
                return;
            }
            var jsondata = JSON.stringify(extractFormData(form,true));

            Fragment.loadFragment(
                'format_popups',
                'mod',
                contextid,
                {
                    'jsondata': jsondata,
                    'modname': modname
                }
            ).then(
                templates.replaceNodeContents.bind(templates, '#format_popups_activity_content')
            ).fail(notification.exception);
        }
    };

    /**
     * Callback to load content for internal course links
     *
     * @param {object} e event
     */
    var handleLink = function(e) {
        'use strict';
        let anchor = e.target.closest('a');
        if (anchor) {

            let url = new URL(anchor.getAttribute('href')),
                params = url.searchParams;

            if (url.toString() && url.pathname.search('mod/assign/report.php') > 0) {
                let xhttp = new XMLHttpRequest();
                e.preventDefault();
                e.stopPropagation();
                xhttp.onreadystatechange = function() {
                    if (this.readyState == 4 && this.status == 200) {
                        showReport(xhttp.responseText);
                    }
                };
                xhttp.open('GET', url.toString(), true);
                xhttp.send();
            }

            if (url.toString() && url.pathname.search('mod/assign/view.php') > 0) {
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
                ).then(
                    templates.replaceNodeContents.bind(templates, '#format_popups_activity_content')
                ).fail(notification.exception);
            }
        }
    }
    /**
     * Callback to handle change group selector
     *
     * @param {object} e event
     */
    var changeGroup = function(e){
        let form = e.target.closest('form');
        if (form && e.target.closest('select.custom-select')) {
            let formdata = new FormData(form),
                url = new URL(form.getAttribute('action')),
                params = new URLSearchParams(formdata);
            e.stopPropagation();
            e.preventDefault();
            if (config.wwwroot + '/mod/assign/view.php' === form.getAttribute('action')) {
                Fragment.loadFragment(
                    'format_popups',
                    'mod',
                    contextid,
                    {
                        jsondata: JSON.stringify(params.toString()),
                        modname: modname
                    }
                ).then(
                    templates.replaceNodeContents.bind(templates, '#format_popups_activity_content')
                ).fail(notification.exception);
            } else if (config.wwwroot + '/mod/assign/report.php' === url.origin + url.pathname) {
                let xhttp = new XMLHttpRequest();
                xhttp.onreadystatechange = function() {
                    if (this.readyState == 4 && this.status == 200) {
                        showReport(xhttp.responseText);
                    }
                };
                xhttp.open('POST', form.getAttribute('action'), true);
                xhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhttp.send(params.toString());
            } else {
                return;
            }
        }
    }

    return {
        /**
         * Initialize assign mod actions
         *
         * @param {int} id Course module context id
         * @param {string} name Activity type
         */
        init : function(id, name){
            'use strict';
            contextid = id;
            modname = name;
            
            var popup = document.querySelector('#format_popups_activity_content');
            // add submit event handlers
            popup.removeEventListener('submit', handleSubmit);
            popup.removeEventListener('submit', handleSubmit, {capture: true} );
            popup.addEventListener('submit', handleSubmit, {capture: true} );

            // add click event listeners
            popup.removeEventListener('click', handleLink);
            popup.addEventListener('click', handleLink);
        
            // Disable handler for  group selection form.
            document.querySelectorAll('form#selectgroup select').forEach(function(select) {
                let id = select.getAttribute('id'),
                    form = select.closest('form'),
                    html;
                if (id) {
                    let label = document.querySelector('label[for="' + id + '"]');
                    select.setAttribute(id, id + '_popup');
                    if (label) {
                        label.setAttribute('for', id + '_popup');
                    }
                }
                html = form.innerHTML;
                templates.replaceNodeContents('form#selectgroup', html, '');
            });
            popup.removeEventListener('change', changeGroup, {capture: true});
            popup.addEventListener('change', changeGroup, {capture: true});
        
            // Fix broken relative links.
            document.querySelectorAll('a').forEach(function(anchor) {
                if (anchor.getAttribute('href') && (anchor.getAttribute('href').search('http') !== 0)) {
                    anchor.setAttribute('href', config.wwwroot + '/mod/assign/' + anchor.getAttribute('href'));
                }
            });
        },
    /** TODO make sure the methods bellow actually work for assign module (imported from format_popups/choice) */
        /**
         * Show report page
         *
         * @param {string} response text
         */
        showReport : function(response){
            'use strict';
            let container = document.createElement('div');
            container.innerHTML = response;
            container.querySelectorAll('div.downloadreport form').forEach(function(form) {
                form.setAttribute('action', config.wwwroot + '/mod/assign/' + form.getAttribute('action'));
            });
            templates.replaceNodeContents(
                '#format_popups_activity_content',
                container.querySelector('#page-content div[role="main"]').innerHTML,
                "require(['core/checkbox-toggleall'], function(ToggleAll) { ToggleAll.init(); });"
            );
        }
    }
});
