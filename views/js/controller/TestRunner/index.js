/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA ;
 */
/**
 * @author Jean-Sébastien Conan <jean-sebastien@taotesting.com>
 */
define([
    'jquery',
    'lodash',
    'i18n',
    'layout/loading-bar',
    'ui/button'
], function ($, _, __, loadingBar, buttonFactory) {
    'use strict';

    return {
        start: function start() {
            var $container = $('.container');
            var $toolbar = $('.toolbar', $container);
            var $content = $('.content', $container);
            var buttons = {};

            function reload() {
                loadingBar.start();
                window.location.reload();
            }

            buttons.reload = buttonFactory({
                id: 'reload',
                label: __('Reload'),
                type: 'info',
                icon: 'reload',
                renderTo: $toolbar
            }).on('click', reload);
        }
    };
});
