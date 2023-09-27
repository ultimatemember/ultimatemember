/*
  Ultimate Member dependencies
*/

var gulp = require('gulp')
, uglify = require('gulp-uglify'),
  sass = require('gulp-sass'),
  rename = require('gulp-rename'),
  cleanCSS   = require( 'gulp-clean-css' );

// task
gulp.task( 'default', function ( done ) {
	sass.compiler = require( 'node-sass' );

	gulp.src(['assets/sass/*.sass']).pipe( sass().on( 'error', sass.logError ) ).pipe( gulp.dest( 'assets/css' ) );

    gulp.src(['assets/js/*.js','!assets/js/um-fileupload.js', '!assets/js/*.min.js']) // path to your files
        .pipe( uglify() )
        .pipe( rename({ suffix: '.min' }) )
        .pipe( gulp.dest( 'assets/js/' ) );

	gulp.src(['assets/libs/legacy/fonticons/*.css', '!assets/libs/legacy/fonticons/*.min.css',])
		.pipe( cleanCSS() )
		.pipe( rename( { suffix: '.min' } ) )
		.pipe( gulp.dest( 'assets/libs/legacy/fonticons/' ) );

	// Raty lib
	gulp.src(['assets/libs/raty/*.css', '!assets/libs/raty/*.min.css',])
		.pipe( cleanCSS() )
		.pipe( rename( { suffix: '.min' } ) )
		.pipe( gulp.dest( 'assets/libs/raty/' ) );
	gulp.src(['assets/libs/raty/*.js', '!assets/libs/raty/*.min.js',])
		.pipe( uglify() )
		.pipe( rename({ suffix: '.min' }) )
		.pipe( gulp.dest( 'assets/libs/raty/' ) );

	// Tipsy lib
	gulp.src(['assets/libs/tipsy/*.css', '!assets/libs/tipsy/*.min.css',])
		.pipe( cleanCSS() )
		.pipe( rename( { suffix: '.min' } ) )
		.pipe( gulp.dest( 'assets/libs/tipsy/' ) );
	gulp.src(['assets/libs/tipsy/*.js', '!assets/libs/tipsy/*.min.js',])
		.pipe( uglify() )
		.pipe( rename({ suffix: '.min' }) )
		.pipe( gulp.dest( 'assets/libs/tipsy/' ) );

    done();
});
