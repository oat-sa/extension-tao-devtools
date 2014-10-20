define(['jquery', 'ui', 'ui/uploader', 'ui/feedback'], function($, ui, uploader, feedback){

    var container = $('#upload-container');

    container.uploader({
        uploadUrl   : container.data('url')
    });

    container.on('upload.uploader', function(e, file, interactionHook){
        if(interactionHook.warning != ''){
            feedback().warning(interactionHook.warning);
        }
        $('.data-container-wrapper').html('<pre>'+interactionHook.success+'</pre>');
    });

    container.on('fail.uploader', function(e, file, interactionHook){
        feedback().error(interactionHook.message);
    });
});
