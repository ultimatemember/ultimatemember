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

    gulp.src(['assets/js/*.js','!assets/js/*.min.js']) // path to your files
        .pipe( uglify() )
        .pipe( rename({ suffix: '.min' }) )
        .pipe( gulp.dest( 'assets/js/' ) );

	gulp.src(['assets/js/admin/*.js','!assets/js/admin/*.min.js']) // path to your files
        .pipe( uglify() )
        .pipe( rename({ suffix: '.min' }) )
        .pipe( gulp.dest( 'assets/js/admin/' ) );
	gulp.src(['assets/css/admin/*.css', '!assets/css/admin/*.min.css',])
		.pipe( cleanCSS() )
		.pipe( rename( { suffix: '.min' } ) )
		.pipe( gulp.dest( 'assets/css/admin/' ) );

	gulp.src(['assets/css/*.css', '!assets/css/*.min.css',])
		.pipe( cleanCSS() )
		.pipe( rename( { suffix: '.min' } ) )
		.pipe( gulp.dest( 'assets/css/' ) );

	// full CSS files
	gulp.src(['assets/css/admin/*.sass'])
		.pipe( sass().on( 'error', sass.logError ) )
		.pipe( gulp.dest( 'assets/css/admin/' ) );
	// min CSS files
	gulp.src(['assets/css/admin/*.sass'])
		.pipe( sass().on( 'error', sass.logError ) )
		.pipe( cleanCSS() )
		.pipe( rename( { suffix: '.min' } ) )
		.pipe( gulp.dest( 'assets/css/admin/' ) );

	// full CSS files
	gulp.src(['assets/css/*.sass'])
		.pipe( sass().on( 'error', sass.logError ) )
		.pipe( gulp.dest( 'assets/css/' ) );
	// min CSS files
	gulp.src(['assets/css/*.sass'])
		.pipe( sass().on( 'error', sass.logError ) )
		.pipe( cleanCSS() )
		.pipe( rename( { suffix: '.min' } ) )
		.pipe( gulp.dest( 'assets/css/' ) );

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

	// Pickadate lib
	gulp.src(['assets/libs/pickadate/*.css', '!assets/libs/pickadate/*.min.css',])
		.pipe( cleanCSS() )
		.pipe( rename( { suffix: '.min' } ) )
		.pipe( gulp.dest( 'assets/libs/pickadate/' ) );
	gulp.src(['assets/libs/pickadate/*.js', '!assets/libs/pickadate/*.min.js',])
		.pipe( uglify() )
		.pipe( rename({ suffix: '.min' }) )
		.pipe( gulp.dest( 'assets/libs/pickadate/' ) );
	gulp.src(['assets/libs/pickadate/translations/*.js', '!assets/libs/pickadate/translations/*.min.js',])
		.pipe( uglify() )
		.pipe( rename({ suffix: '.min' }) )
		.pipe( gulp.dest( 'assets/libs/pickadate/translations/' ) );

    done();
});
