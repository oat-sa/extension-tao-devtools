define(function(){
    return {
        'ExtensionsManager' : {
            'actions' : {
                'index' : 'controller/settings/extensionManager'
            }
        },
        'ScriptRunner' : {
            'actions' : {
                'index' : 'controller/ScriptRunner/index'
            }
        },
        'FontConversion' : {
            'actions' : {
                'index' : 'controller/fontConversion/fileUpload'
            }
        },
        'StudentTollGenerator' : {
            'actions' : {
                'index' : 'controller/fontConversion/fileUpload'
            }
        },
        'MonitorTool' : {
            'actions' : {
                'index' : 'controller/monitorTool/monitor',
                'showCallGroupChunk' : 'controller/monitorTool/trace'

            }
        }
    };
});
