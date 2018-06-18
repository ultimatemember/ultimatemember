/*
  Ultimate Member dependencies
*/

var gulp = require('gulp')
, uglify = require('gulp-uglify'),
  rename = require("gulp-rename");

// task
gulp.task( 'jsminify', function() {
    gulp.src(['assets/js/*.js', '!assets/js/*.min.js'])
        .pipe( uglify() )
        .pipe( rename({ suffix: '.min' }) )
        .pipe( gulp.dest( 'assets/js/' ) );
});

// The default task (called when you run `gulp`)
gulp.task('default', function() {
    gulp.start( 'jsminify' );
});