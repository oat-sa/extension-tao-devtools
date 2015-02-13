define(['jquery', 'helpers', 'ui/feedback'], function($, helpers, feedback){

    return {



        start : function(){
            var self = this;
            $('.monitor-tool-bar').click(function(event) {

                var action = $(event.target).data('action');
                $(this).prop('disable', true);
                $.ajax({
                    type: "POST",
                    url: helpers._url(action, 'MonitorTool', 'taoDevTools'),
                    dataType: 'json',
                    success: function(data) {
                        if (!data.success) {
                            feedback().error(data.message);
                        }
                    }
                });
                self.updateMonitorToolbar();
            });

            self.updateMonitorToolbar();

        },

       updateMonitorToolbar : function() {
           $.ajax({
               type: "POST",
               url: helpers._url('getMonitorStatus', 'MonitorTool', 'taoDevTools'),
               dataType: 'json',
               success: function(data) {
                   if (data.success) {
                       $('.monitor-start').prop('disabled', data.status);
                       $('.monitor-stop').prop('disabled', !data.status);

                   } else {
                       $('.monitor-start').prop('disabled', false);
                       $('.monitor-stop').prop('disabled', true);
                       feedback().error(data.message);
                   }
               }
           });
       }
    };

});
