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
        'KeyChecker' : {
            'actions' : {
                'index' : 'controller/KeyChecker/index'
            }
        },
        'TestRunner' : {
            'actions' : {
                'index' : 'controller/TestRunner/index',
                'timer' : 'controller/TestRunner/timer'
            }
        }
    };
});
