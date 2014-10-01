define(['jquery', 'helpers'], function($, helpers){
	return {
        start : function(){
            
            $('.script').click(function(event) {
            	var action = $(event.target).data('action');
            	$.ajax({
                    type: "POST",
                    url: helpers._url(action, 'ScriptRunner', 'taoDevTools'),
                    dataType: 'json',
                    success: function(data) {
                        helpers.loaded();
                        if (data.success) {
                            helpers.createInfoMessage(data.message);
                        } else {
                        	helpers.createErrorMessage(data.message);
                        }
                    }
            	});
            });
            
        }
    };

});