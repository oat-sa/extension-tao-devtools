define(['jquery', 'helpers', 'ui/feedback','taoDevTools/lib/run_prettify', 'layout/actions/binder', 'layout/actions'], function($, helpers, feedback, prettify, binder, actions){

    var _initialized = false;

    /**
     * Update the start and stop action's state in regards of the Monitor status
     *
     * @param status bool
     * @private
     */
    var _setToolbarState = function(status) {
        actions.getBy('monitor-start').state.disabled = status;
        actions.getBy('monitor-stop').state.disabled = !status;
        actions.updateContext();
    }

    /**
     * Call an action on the MonitorTool Controller
     * @param {string} action
     * @param {function} success
     * @private
     */
    var _action = function(action, success) {
        $.ajax({
            type: "POST",
            url: helpers._url(action, 'MonitorTool', 'taoDevTools'),
            dataType: 'json',
            success: success
        });
    }

    /**
     * Collect the monitor status from the server and update the action
     *
     * @private
     */
    var _updateMonitorToolbar = function() {
        _action('getMonitorStatus', function(data) {
            if (data.success) {
                _setToolbarState(data.status);
            } else {
                _setToolbarState(false);
                feedback().error(data.message);
            }
        })
    }

    //Actions

    /**
     * Stop the monitor
     */
    var stopMonitor = function () {

        actions.getBy('monitor-stop').state.disabled = true;
        actions.updateContext();

        _action('stopMonitor', function(data) {
                if (data.success) {
                    _updateMonitorToolbar();
                } else {
                    feedback().error(data.message);
                }
            }
        );
    }

    /**
     * Start the monitor
     */
    var startMonitor = function () {
        actions.getBy('monitor-start').state.disabled = true;
        actions.updateContext();

        _action('startMonitor', function(data) {
                if (data.success) {
                    _updateMonitorToolbar();
                } else {
                    feedback().error(data.message);
                }
            }
        );
    }

    /**
     * Clear the data store
     */
    var clearMonitor = function () {

        if(!window.confirm('Definitely delete monitor data ?')) {
            return;
        }
        actions.getBy('monitor-clear').state.disabled = true;
        actions.updateContext();

        _action('clearMonitor', function(data) {
                if (!data.success) {
                    feedback().error(data.message);
                }
                refreshTree();
                actions.getBy('monitor-clear').state.disabled = false;
                actions.updateContext();

            }
        );
    }

    /**
     * Refresh the tree and reload data from the server
     */
    var refreshTree = function () {
        actions.getBy('monitor-refresh').state.disabled = true;
        actions.updateContext();

        $('.tree').trigger('refresh.taotree');

        actions.getBy('monitor-refresh').state.disabled = false;
        actions.updateContext();

    }

    //Register all the actions above
    binder.register('monitorRefreshTree', refreshTree );
    binder.register('monitorStart', startMonitor );
    binder.register('monitorStop', stopMonitor );
    binder.register('monitorClear', clearMonitor );

    //Controller

    return {

        start : function(){
            if(!_initialized) {
                _updateMonitorToolbar();
                _initialized = true;

            }
            $('[data-toggle="collapse"]').on('click',function(){
                console.log(this,$('this').next('div'));
                $(this).next('div').toggle('200');
            });
        }


    };

});
