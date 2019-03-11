module.exports = function(grunt) {

//Initializing the configuration object
grunt.initConfig({

    // Paths variables
    paths: {
      // Development where put SASS files, etc
      assets: {
        css: '/www/site/css',
        js: '/www/site/js',
        vendor: '/www/_platform/bower_components'
      },
      // Production where Grunt output the files
      css: '/www/site/public/css',
      js: '/www/site/public/js'

    },

    // Task configuration
    concat: {
        options: {
            separator: ';',
        },
        js_header: {
            src: [
            ],
            dest: '<%= paths.js %>/scripts_header.js',
        },
        js_footer: {
            src: [
                '<%= paths.assets.vendor %>/jquery/dist/jquery.min.js',
                '<%= paths.assets.vendor %>/what-input/what-input.min.js',
                '<%= paths.assets.vendor %>/foundation-sites/dist/js/foundation.min.js',
                '<%= paths.assets.vendor %>/chart.js/dist/Chart.bundle.min.js',
                '<%= paths.assets.vendor %>/motion-ui/motion-ui.js',
                '<%= paths.assets.vendor %>/scroll-depth/jquery.scrolldepth.min.js',
                '<%= paths.assets.js %>/jquery.stickybits.min.js',
                '<%= paths.assets.js %>/functions.js',
                '<%= paths.assets.js %>/custom.js',
            ],
            dest: '<%= paths.js %>/scripts_footer.js',
        }
    },
    sass:  {
        css: {
            options: {
                style: 'compressed',
                compass: true
            },
            files: [{
                expand: true,
                cwd: '<%= paths.assets.css %>',
                src: '**/*.scss',
                dest: '<%= paths.css %>',
                ext: '.css',
            }],
        },
    },
    uglify: {
        options: {
            mangle: false
        },
        js: {
            files: {
                '<%= paths.js %>/min/scripts_footer.min.js': '<%= paths.js %>/scripts_footer.js',
                '<%= paths.js %>/min/scripts_header.min.js': '<%= paths.js %>/scripts_header.js'
            },
        }
    },
    phpunit: {
        classes: {
            dir: '/www/site/tests/'
        },
        options: {
            bin: '/www/_platform/vendor/bin/phpunit',
            bootstrap: '/www/site/tests/TestHelper.php',
            colors: true
        }
    },
    watch: {
        css: {
            files: '/www/site/css/*.scss',
            tasks: ['rebuildcss']
        },
        js: {
            files: '/www/site/js/*.js',
            tasks: ['rebuildjs']
        },
    },
    exec: {
        codeception: {
            cmd: 'php /www/_platform/vendor/bin/codeceptionf run'
        }
    },
    replace: {
        build_replace: {
            options: {
                variables: {
                    'hash': '<%= ((new Date()).valueOf().toString()) + (Math.floor((Math.random()*1000000)+1).toString()) %>'
                }
            },
            // Source and destination files
            files: [{
                src: ['/www/site/views/index-src.volt'],
                dest: '/www/site/views/index.volt'
            }]
        }
    },
    /*cacheBust: {
        taskName: {
            options: {
                assets: ['css/**', 'js/**'],
                queryString: true,
                baseDir: '/public/',
                length: 16
            },
            src: ['/www/app/views/index.volt']
        }
    }
    */
});

// Plugin loading
grunt.loadNpmTasks('grunt-contrib-compass');
grunt.loadNpmTasks('grunt-contrib-sass');
grunt.loadNpmTasks('grunt-contrib-concat');
grunt.loadNpmTasks('grunt-contrib-uglify');
grunt.loadNpmTasks('grunt-contrib-watch');
grunt.loadNpmTasks('grunt-phpunit');
grunt.loadNpmTasks('grunt-exec');
grunt.loadNpmTasks('grunt-replace');
//grunt.loadNpmTasks('grunt-cache-bust');

// Task definition
grunt.registerTask('default', ['sass', 'concat', 'uglify', 'watch', 'replace']);
grunt.registerTask('rebuildjs', ['concat', 'uglify', 'replace']);
grunt.registerTask('rebuildcss', ['sass', 'replace']);
};
