module.exports = function (grunt) {

    require('load-grunt-tasks')(grunt);

    var production = !!grunt.option('production');
    var pkg = grunt.file.readJSON('package.json');

    // setting browser compatibility
    if (typeof (pkg.supportedBrowsers) == 'undefined') {
        pkg.supportedBrowsers = ['> 5% in DE', 'ie 10'];
    }

    // 1. All configuration goes here
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),

        less: {
            default: {
                options: {
                    compress: production,
                    yuicompress: production,
                    optimization: 2,
                    sourceMap: !production,
                    sourceMapURL: 'styles.css.map',
                    plugins: [
                        new (require('less-plugin-autoprefix'))({browsers: pkg.supportedBrowsers})
                    ]
                },
                files: [{
                    expand: true,     // Enable dynamic expansion.
                    cwd: './',      // Src matches are relative to this path.
                    src: ['**/less/be.less', '**/less/fe.less'], // Actual pattern(s) to match.
                    dest: './',   // Destination path prefix.
                    ext: '.css',   // Dest filepaths will have this extension.
                    extDot: 'last',   // Extensions in filenames begin after the first dot
                    rename: function (dest, src) {
                        src = src.replace(/\/less\//, '/../assets/css/');
                        return src;
                    }
                }],
            }
        },

        concat: {
            options: {
                separator: ';\n',
                stripBanners: production,
                sourceMap: !production
            }
        },

        uglify: {
            options: {
                sourceMap: !production,
                sourceMapIncludeSources: !production,
                sourceMapIn: function (path) {
                    return path.replace(/\.js/, "\.js\.map")
                },
                compress: {
                    drop_console: production
                }
            }
        },

        shell: {
            default: {
                command: './rsync.sh'
            }
        },

        watch: {
            css: {
                files: ['**/less/**/*.less'], // which files to watch
                tasks: ['less:default', 'shell:default']
            },
            rsync: {
                files: ['**/assets/**/*'],
                tasks: ['shell:default']
            }
        }
    });

    // get all module directories
    grunt.file.expand('**/assets_src/js/be').forEach(function (dir) {
        // get the module name from the directory name
        var dirName = dir.substr(dir.lastIndexOf('/') + 1),
            taskLabel = dir.replace(/[^a-zA-Z0-9\_]/g, '_');

        // get the current concat object from initConfig
        var concat = grunt.config.get('concat') || {};
        concat[taskLabel] = {
            src: [dir + '/**/*.js'],
            dest: dir + '/../../../assets/js/be.js'
        };
        grunt.config.set('concat', concat);

        var uglify = grunt.config.get('uglify') || {};
        uglify[taskLabel] = {
            src: ['<%= concat.' + taskLabel + '.dest %>'],
            dest: '<%= concat.' + taskLabel + '.dest %>'
        };
        grunt.config.set('uglify', uglify);

        var watch = grunt.config.get('watch') || {};
        watch[taskLabel] = {
            files: ['<%= concat.' + taskLabel + '.src %>'],
            tasks: ['concat:' + taskLabel, 'uglify:' + taskLabel, 'shell:default']
        };
        grunt.config.set('watch', watch);
    });

    // get all module directories
    grunt.file.expand('**/assets_src/js/fe').forEach(function (dir) {
        // get the module name from the directory name
        var dirName = dir.substr(dir.lastIndexOf('/') + 1),
            taskLabel = dir.replace(/[^a-zA-Z0-9\_]/g, '_');

        // get the current concat object from initConfig
        var concat = grunt.config.get('concat') || {};
        concat[taskLabel] = {
            src: [dir + '/**/*.js'],
            dest: dir + '/../../../assets/js/fe.js'
        };
        grunt.config.set('concat', concat);

        var uglify = grunt.config.get('uglify') || {};
        uglify[taskLabel] = {
            src: ['<%= concat.' + taskLabel + '.dest %>'],
            dest: '<%= concat.' + taskLabel + '.dest %>'
        };
        grunt.config.set('uglify', uglify);

        var watch = grunt.config.get('watch') || {};
        watch[taskLabel] = {
            files: ['<%= concat.' + taskLabel + '.src %>'],
            tasks: ['concat:' + taskLabel, 'uglify:' + taskLabel, 'shell:default']
        };
        grunt.config.set('watch', watch);
    });

    if (production) {
        grunt.registerTask('default', ['concat', 'uglify', 'less', 'shell']);
    } else {
        grunt.registerTask('default', ['concat', 'less', 'shell', 'watch']);
    }
};
