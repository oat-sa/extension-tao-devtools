module.exports = function(grunt) {
    'use strict';

    var sass    = grunt.config('sass') || {};
    var watch   = grunt.config('watch') || {};
    var notify  = grunt.config('notify') || {};
    var root    = grunt.option('root') + '/taoDevTools/views/';

    sass.taodevtools = { };
    sass.taodevtools.files = { };
    sass.taodevtools.files[root + 'css/devtools.css'] = root + 'scss/devtools.scss';
    sass.taodevtools.files[root + 'css/keychecker.css'] = root + 'scss/keychecker.scss';
    sass.taodevtools.files[root + 'css/testrunner.css'] = root + 'scss/testrunner.scss';

    watch.taodevtoolssass = {
        files : [root + 'scss/**/*.scss'],
        tasks : ['sass:taodevtools', 'notify:taodevtoolssass'],
        options : {
            debounceDelay : 1000
        }
    };

    notify.taodevtoolssass = {
        options: {
            title: 'Grunt SASS',
            message: 'SASS files compiled to CSS'
        }
    };

    grunt.config('sass', sass);
    grunt.config('watch', watch);
    grunt.config('notify', notify);

    //register an alias for main build
    grunt.registerTask('taodevtoolssass', ['sass:taodevtools']);
};
