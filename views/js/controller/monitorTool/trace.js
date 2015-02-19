define(['jquery', 'helpers', 'ui/feedback','taoDevTools/lib/run_prettify'], function($, helpers, feedback, prettify){


    //Controller

    return {

        start : function(){
            window.PR.prettyPrint();
            $('[data-toggle="collapse"]').on('click',function(){
                $(this).next('div').toggle('100');
            });
        }


    };

});
