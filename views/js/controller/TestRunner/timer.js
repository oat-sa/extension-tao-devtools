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
    'moment',
    'util/url',
    'core/promise',
    'core/logger',
    'core/store',
    'core/dataProvider/request',
    'core/polling',
    'core/encoder/time',
    'layout/loading-bar',
    'tpl!taoDevTools/controller/TestRunner/durations',
    'ui/button',
    'ui/datatable'
], function ($,
             _,
             __,
             moment,
             urlHelper,
             Promise,
             loggerFactory,
             storeFactory,
             request,
             pollingFactory,
             timeEncoder,
             loadingBar,
             durationTpl,
             buttonFactory) {
    'use strict';

    function formatDurations(durations) {
        var dataDurations = [];

        _.forEach(durations, function (value, key) {
            dataDurations.push({
                label: key,
                value: value && timeEncoder.encode(value + 's') || '-'
            });
        });

        dataDurations.sort(function (a, b) {
            return a.label.localeCompare(b.label);
        });

        return durationTpl(dataDurations);
    }

    return {
        start: function start() {
            var $container = $('.container');
            var $toolbar = $('.toolbar', $container);
            var $content = $('.content', $container);

            var currentRoute = urlHelper.parse(window.location.href);
            var sessionId = currentRoute.query.deliveryExecution && decodeURIComponent(currentRoute.query.deliveryExecution);
            var dataUrl = urlHelper.route('deliveryExecutionData', 'TestRunner', 'taoDevTools', {deliveryExecution: sessionId});

            var history = [];
            var last, lastId;
            var buttons = {};
            var timerStore, durationStore;

            var logger = loggerFactory('taoDevTools/TestRunner/timer');

            var polling = pollingFactory({
                interval: 1000,
                action: function action(p) {
                    var promise = p.async();
                    update().then(function () {
                        promise.resolve();
                    });
                }
            });

            function update() {
                return request(dataUrl)
                    .then(function (data) {
                        var isNew = !last || lastId !== data.id || last.state !== data.state;
                        var itemAttemptId;
                        var isDiff;
                        var promises = [];
                        var localDurations = {};
                        var localTimers = {};

                        data.timestamp = Date.now();
                        data.timestampLabel = moment(data.timestamp).format('LLL');
                        data.remainingLabel = _.isEmpty(data.remaining) ? '-' : timeEncoder.encode(data.remaining);
                        data.serverDuration = data.durations && formatDurations(data.durations.server);
                        data.clientDuration = data.durations && formatDurations(data.durations.client);
                        data.extraDuration = data.extraTime && formatDurations(data.extraTime);

                        if (!data.running) {
                            stopPolling();
                        }

                        if (data.identifiers) {
                            itemAttemptId = data.identifiers.item + '#' + data.identifiers.attempt;
                            promises.push(durationStore.getItem(itemAttemptId).then(function (duration) {
                                localDurations.duration = _.isNumber(duration) ? duration : 0;
                            }));
                        }

                        if (data.timers) {
                            _.forEach(data.timers, function (timer) {
                                promises.push(timerStore.getItem(timer.id).then(function (remaining) {
                                    localTimers[timer.label] = _.isNumber(remaining) ? remaining / 1000 : 0;
                                }));
                            });
                        }

                        return Promise.all(promises).then(function () {
                            data.localDuration = formatDurations(localDurations);
                            data.localTimers = formatDurations(localTimers);
                            if (!last || isNew) {
                                data.startTimers = data.localTimers;
                            } else if (last.startTimers) {
                                data.startTimers = last.startTimers;
                            }

                            isDiff = !_.isEqual(last, data);
                            last = data;
                            lastId = data.id;

                            if (isNew) {
                                history.unshift(data);
                            } else if (isDiff) {
                                history.shift();
                                history.unshift(data);
                            }

                            if (isNew || isDiff) {
                                $content.datatable('refresh', {
                                    data: history
                                });
                            }
                        });
                    })
                    .catch(function (err) {
                        logger.error(err);
                    });
            }

            function reload() {
                stopPolling();
                loadingBar.start();
                window.location.reload();
            }

            function index() {
                stopPolling();
                loadingBar.start();
                window.location.href = urlHelper.route('index', 'TestRunner', 'taoDevTools');
            }

            function startPolling() {
                polling.start();
                buttons.stop.show();
                buttons.start.hide();
                buttons.ping.hide();
            }

            function stopPolling() {
                polling.stop();
                buttons.stop.hide();
                buttons.start.show();
                buttons.ping.show();
            }

            buttons.back = buttonFactory({
                id: 'back',
                label: __('Index'),
                type: 'info',
                icon: 'left',
                renderTo: $toolbar
            }).on('click', index);

            buttons.reload = buttonFactory({
                id: 'reload',
                label: __('Reload'),
                type: 'info',
                icon: 'reload',
                renderTo: $toolbar
            }).on('click', reload);

            buttons.start = buttonFactory({
                id: 'start',
                label: __('Start watching'),
                type: 'info',
                icon: 'play',
                renderTo: $toolbar
            }).on('click', startPolling).hide();

            buttons.stop = buttonFactory({
                id: 'stop',
                label: __('Stop watching'),
                type: 'info',
                icon: 'stop',
                renderTo: $toolbar
            }).on('click', stopPolling).hide();

            buttons.ping = buttonFactory({
                id: 'ping',
                label: __('Ping'),
                type: 'info',
                icon: 'target',
                renderTo: $toolbar
            }).on('click', function() {
                buttons.ping.disable();
                update().then(function() {
                    buttons.ping.enable();
                });
            }).hide();

            $content.datatable({
                paginationStrategyTop: 'none',
                paginationStrategyBottom: 'none',
                model: [{
                    id: 'timestampLabel',
                    label: __('Timestamp')
                }, {
                    id: 'state',
                    label: __('State')
                }, {
                    id: 'position',
                    label: __('Position')
                }, {
                    id: 'remainingLabel',
                    label: __('Server Timer')
                }, {
                    id: 'serverDuration',
                    label: __('Server durations')
                }, {
                    id: 'clientDuration',
                    label: __('Client durations')
                }, {
                    id: 'extraDuration',
                    label: __('Extra time')
                }, {
                    id: 'localDuration',
                    label: __('Browser durations')
                }, {
                    id: 'localTimers',
                    label: __('Client Timers')
                }, {
                    id: 'startTimers',
                    label: __('From timers')
                }]
            });

            Promise.all([
                storeFactory('timer-' + sessionId).then(function (store) {
                    timerStore = store;
                }),
                storeFactory('duration-' + sessionId).then(function (store) {
                    durationStore = store;
                })
            ]).then(function () {
                startPolling();
            }).catch(function (err) {
                logger.error(err);
            });
        }
    };
});
