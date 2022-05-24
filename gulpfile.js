/*
  Ultimate Member dependencies
*/

var gulp = require('gulp')
, uglify = require('gulp-uglify'),
  sass = require('gulp-sass'),
  rename = require("gulp-rename");

// task
gulp.task( 'default', function ( done ) {
	sass.compiler = require( 'node-sass' );

	gulp.src(['assets/sass/*.sass']).pipe( sass().on( 'error', sass.logError ) ).pipe( gulp.dest( 'assets/css' ) );

    gulp.src(['assets/js/*.js', '!assets/js/*.min.js']) // path to your files
        .pipe( uglify() )
        .pipe( rename({ suffix: '.min' }) )
        .pipe( gulp.dest( 'assets/js/' ) );

    done();
});
