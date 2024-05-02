/*
  Ultimate Member dependencies
*/

// var gulp = require('gulp')
// , uglify = require('gulp-uglify'),
//   sass = require('gulp-sass'),
//   rename = require('gulp-rename'),
//   cleanCSS   = require( 'gulp-clean-css' );

const { src, dest, parallel } = require( 'gulp' );
const sass        = require( 'gulp-sass' )( require( 'sass' ) );
const uglify      = require( 'gulp-uglify' );
const cleanCSS    = require( 'gulp-clean-css' );
const rename      = require( 'gulp-rename' );

// task
// gulp.task( 'default', function ( done ) {
// 	sass.compiler = require( 'node-sass' );
//
// 	gulp.src(['assets/sass/*.sass']).pipe( sass().on( 'error', sass.logError ) ).pipe( gulp.dest( 'assets/css' ) );
//
//     gulp.src(['assets/js/*.js','!assets/js/*.min.js']) // path to your files
//         .pipe( uglify() )
//         .pipe( rename({ suffix: '.min' }) )
//         .pipe( gulp.dest( 'assets/js/' ) );
//
// 	gulp.src(['assets/js/admin/*.js','!assets/js/admin/*.min.js']) // path to your files
//         .pipe( uglify() )
//         .pipe( rename({ suffix: '.min' }) )
//         .pipe( gulp.dest( 'assets/js/admin/' ) );
// 	gulp.src(['assets/css/admin/*.css', '!assets/css/admin/*.min.css',])
// 		.pipe( cleanCSS() )
// 		.pipe( rename( { suffix: '.min' } ) )
// 		.pipe( gulp.dest( 'assets/css/admin/' ) );
//
// 	gulp.src(['assets/css/*.css', '!assets/css/*.min.css',])
// 		.pipe( cleanCSS() )
// 		.pipe( rename( { suffix: '.min' } ) )
// 		.pipe( gulp.dest( 'assets/css/' ) );
//
// 	// full CSS files
// 	gulp.src(['assets/css/admin/*.sass'])
// 		.pipe( sass().on( 'error', sass.logError ) )
// 		.pipe( gulp.dest( 'assets/css/admin/' ) );
// 	// min CSS files
// 	gulp.src(['assets/css/admin/*.sass'])
// 		.pipe( sass().on( 'error', sass.logError ) )
// 		.pipe( cleanCSS() )
// 		.pipe( rename( { suffix: '.min' } ) )
// 		.pipe( gulp.dest( 'assets/css/admin/' ) );
//
// 	// full CSS files
// 	gulp.src(['assets/css/*.sass'])
// 		.pipe( sass().on( 'error', sass.logError ) )
// 		.pipe( gulp.dest( 'assets/css/' ) );
// 	// min CSS files
// 	gulp.src(['assets/css/*.sass'])
// 		.pipe( sass().on( 'error', sass.logError ) )
// 		.pipe( cleanCSS() )
// 		.pipe( rename( { suffix: '.min' } ) )
// 		.pipe( gulp.dest( 'assets/css/' ) );
//
// 	gulp.src(['assets/libs/legacy/fonticons/*.css', '!assets/libs/legacy/fonticons/*.min.css',])
// 		.pipe( cleanCSS() )
// 		.pipe( rename( { suffix: '.min' } ) )
// 		.pipe( gulp.dest( 'assets/libs/legacy/fonticons/' ) );
//
// 	// Dropdown lib
// 	// full CSS files
// 	gulp.src(['assets/libs/dropdown/*.sass'])
// 		.pipe( sass().on( 'error', sass.logError ) )
// 		.pipe( gulp.dest( 'assets/libs/dropdown/' ) );
// 	// min CSS files
// 	gulp.src(['assets/libs/dropdown/*.sass'])
// 		.pipe( sass().on( 'error', sass.logError ) )
// 		.pipe( cleanCSS() )
// 		.pipe( rename( { suffix: '.min' } ) )
// 		.pipe( gulp.dest( 'assets/libs/dropdown/' ) );
// 	gulp.src(['assets/libs/dropdown/*.js', '!assets/libs/dropdown/*.min.js',])
// 		.pipe( uglify() )
// 		.pipe( rename({ suffix: '.min' }) )
// 		.pipe( gulp.dest( 'assets/libs/dropdown/' ) );
//
// 	// Modal lib
// 	// full CSS files
// 	gulp.src(['assets/libs/modal/*.sass'])
// 		.pipe( sass().on( 'error', sass.logError ) )
// 		.pipe( gulp.dest( 'assets/libs/modal/' ) );
// 	// min CSS files
// 	gulp.src(['assets/libs/modal/*.sass'])
// 		.pipe( sass().on( 'error', sass.logError ) )
// 		.pipe( cleanCSS() )
// 		.pipe( rename( { suffix: '.min' } ) )
// 		.pipe( gulp.dest( 'assets/libs/modal/' ) );
// 	gulp.src(['assets/libs/modal/*.js', '!assets/libs/modal/*.min.js',])
// 		.pipe( uglify() )
// 		.pipe( rename({ suffix: '.min' }) )
// 		.pipe( gulp.dest( 'assets/libs/modal/' ) );
//
// 	// Raty lib
// 	gulp.src(['assets/libs/raty/*.css', '!assets/libs/raty/*.min.css',])
// 		.pipe( cleanCSS() )
// 		.pipe( rename( { suffix: '.min' } ) )
// 		.pipe( gulp.dest( 'assets/libs/raty/' ) );
// 	gulp.src(['assets/libs/raty/*.js', '!assets/libs/raty/*.min.js',])
// 		.pipe( uglify() )
// 		.pipe( rename({ suffix: '.min' }) )
// 		.pipe( gulp.dest( 'assets/libs/raty/' ) );
//
// 	// Tipsy lib
// 	gulp.src(['assets/libs/tipsy/*.css', '!assets/libs/tipsy/*.min.css',])
// 		.pipe( cleanCSS() )
// 		.pipe( rename( { suffix: '.min' } ) )
// 		.pipe( gulp.dest( 'assets/libs/tipsy/' ) );
// 	gulp.src(['assets/libs/tipsy/*.js', '!assets/libs/tipsy/*.min.js',])
// 		.pipe( uglify() )
// 		.pipe( rename({ suffix: '.min' }) )
// 		.pipe( gulp.dest( 'assets/libs/tipsy/' ) );
//
// 	// Pickadate lib
// 	gulp.src(['assets/libs/pickadate/*.css', '!assets/libs/pickadate/*.min.css',])
// 		.pipe( cleanCSS() )
// 		.pipe( rename( { suffix: '.min' } ) )
// 		.pipe( gulp.dest( 'assets/libs/pickadate/' ) );
// 	gulp.src(['assets/libs/pickadate/*.js', '!assets/libs/pickadate/*.min.js',])
// 		.pipe( uglify() )
// 		.pipe( rename({ suffix: '.min' }) )
// 		.pipe( gulp.dest( 'assets/libs/pickadate/' ) );
// 	gulp.src(['assets/libs/pickadate/translations/*.js', '!assets/libs/pickadate/translations/*.min.js',])
// 		.pipe( uglify() )
// 		.pipe( rename({ suffix: '.min' }) )
// 		.pipe( gulp.dest( 'assets/libs/pickadate/translations/' ) );
//
//     done();
// });
//
// function js( path ) {
// 	return src( ['assets/' + path + '/js/*.js', '!assets/' + path + '/js/*.min.js'] )
// 		.pipe( uglify() )
// 		.pipe( rename( { suffix: '.min' } ) )
// 		.pipe( dest( 'assets/' + path + '/js' ) );
// }
//
// function css( path ) {
// 	sass.compiler = require( 'node-sass' );
//
// 	var src_array = exclude_css( path );
// 	return src( src_array )
// 		.pipe( sass().on( 'error', sass.logError ) )
// 		.pipe( dest( 'assets/' + path + '/css' ) );
// }
//
// function min_css( path ) {
// 	sass.compiler = require( 'node-sass' );
//
// 	var src_array = exclude_css( path );
// 	return src( src_array )
// 		.pipe( sass().on( 'error', sass.logError ) )
// 		.pipe( cleanCSS() )
// 		.pipe( rename( { suffix: '.min' } ) )
// 		.pipe( dest( 'assets/' + path + '/css' ) );
// }

function defaultTask( done ) {
	// sass.compiler = require( 'node-sass' );

	src(['assets/sass/*.sass']).pipe( sass().on( 'error', sass.logError ) ).pipe( dest( 'assets/css' ) );

	src(['assets/js/*.js','!assets/js/*.min.js']) // path to your files
		.pipe( uglify() )
		.pipe( rename({ suffix: '.min' }) )
		.pipe( dest( 'assets/js/' ) );

	src(['assets/js/admin/*.js','!assets/js/admin/*.min.js']) // path to your files
		.pipe( uglify() )
		.pipe( rename({ suffix: '.min' }) )
		.pipe( dest( 'assets/js/admin/' ) );
	src(['assets/css/admin/*.css', '!assets/css/admin/*.min.css',])
		.pipe( cleanCSS() )
		.pipe( rename( { suffix: '.min' } ) )
		.pipe( dest( 'assets/css/admin/' ) );

	src(['assets/css/*.css', '!assets/css/*.min.css',])
		.pipe( cleanCSS() )
		.pipe( rename( { suffix: '.min' } ) )
		.pipe( dest( 'assets/css/' ) );

	// full CSS files
	src(['assets/css/admin/*.sass'])
		.pipe( sass().on( 'error', sass.logError ) )
		.pipe( dest( 'assets/css/admin/' ) );
	// min CSS files
	src(['assets/css/admin/*.sass'])
		.pipe( sass().on( 'error', sass.logError ) )
		.pipe( cleanCSS() )
		.pipe( rename( { suffix: '.min' } ) )
		.pipe( dest( 'assets/css/admin/' ) );

	// full CSS files
	src(['assets/css/*.sass'])
		.pipe( sass().on( 'error', sass.logError ) )
		.pipe( dest( 'assets/css/' ) );
	// min CSS files
	src(['assets/css/*.sass'])
		.pipe( sass().on( 'error', sass.logError ) )
		.pipe( cleanCSS() )
		.pipe( rename( { suffix: '.min' } ) )
		.pipe( dest( 'assets/css/' ) );

	src(['assets/libs/legacy/fonticons/*.css', '!assets/libs/legacy/fonticons/*.min.css',])
		.pipe( cleanCSS() )
		.pipe( rename( { suffix: '.min' } ) )
		.pipe( dest( 'assets/libs/legacy/fonticons/' ) );

	// Dropdown lib
	// full CSS files
	src(['assets/libs/dropdown/*.sass'])
		.pipe( sass().on( 'error', sass.logError ) )
		.pipe( dest( 'assets/libs/dropdown/' ) );
	// min CSS files
	src(['assets/libs/dropdown/*.sass'])
		.pipe( sass().on( 'error', sass.logError ) )
		.pipe( cleanCSS() )
		.pipe( rename( { suffix: '.min' } ) )
		.pipe( dest( 'assets/libs/dropdown/' ) );
	src(['assets/libs/dropdown/*.js', '!assets/libs/dropdown/*.min.js',])
		.pipe( uglify() )
		.pipe( rename({ suffix: '.min' }) )
		.pipe( dest( 'assets/libs/dropdown/' ) );

	// Modal lib
	// full CSS files
	src(['assets/libs/modal/*.sass'])
		.pipe( sass().on( 'error', sass.logError ) )
		.pipe( dest( 'assets/libs/modal/' ) );
	// min CSS files
	src(['assets/libs/modal/*.sass'])
		.pipe( sass().on( 'error', sass.logError ) )
		.pipe( cleanCSS() )
		.pipe( rename( { suffix: '.min' } ) )
		.pipe( dest( 'assets/libs/modal/' ) );
	src(['assets/libs/modal/*.js', '!assets/libs/modal/*.min.js',])
		.pipe( uglify() )
		.pipe( rename({ suffix: '.min' }) )
		.pipe( dest( 'assets/libs/modal/' ) );

	// Raty lib
	src(['assets/libs/raty/*.css', '!assets/libs/raty/*.min.css',])
		.pipe( cleanCSS() )
		.pipe( rename( { suffix: '.min' } ) )
		.pipe( dest( 'assets/libs/raty/' ) );
	src(['assets/libs/raty/*.js', '!assets/libs/raty/*.min.js',])
		.pipe( uglify() )
		.pipe( rename({ suffix: '.min' }) )
		.pipe( dest( 'assets/libs/raty/' ) );

	// Tipsy lib
	src(['assets/libs/tipsy/*.css', '!assets/libs/tipsy/*.min.css',])
		.pipe( cleanCSS() )
		.pipe( rename( { suffix: '.min' } ) )
		.pipe( dest( 'assets/libs/tipsy/' ) );
	src(['assets/libs/tipsy/*.js', '!assets/libs/tipsy/*.min.js',])
		.pipe( uglify() )
		.pipe( rename({ suffix: '.min' }) )
		.pipe( dest( 'assets/libs/tipsy/' ) );

	// UM Confirm lib
	src(['assets/libs/um-confirm/*.css', '!assets/libs/um-confirm/*.min.css',])
		.pipe( cleanCSS() )
		.pipe( rename( { suffix: '.min' } ) )
		.pipe( dest( 'assets/libs/um-confirm/' ) );
	src(['assets/libs/um-confirm/*.js', '!assets/libs/um-confirm/*.min.js',])
		.pipe( uglify() )
		.pipe( rename({ suffix: '.min' }) )
		.pipe( dest( 'assets/libs/um-confirm/' ) );

	// Pickadate lib
	src(['assets/libs/pickadate/*.css', '!assets/libs/pickadate/*.min.css',])
		.pipe( cleanCSS() )
		.pipe( rename( { suffix: '.min' } ) )
		.pipe( dest( 'assets/libs/pickadate/' ) );
	src(['assets/libs/pickadate/*.js', '!assets/libs/pickadate/*.min.js',])
		.pipe( uglify() )
		.pipe( rename({ suffix: '.min' }) )
		.pipe( dest( 'assets/libs/pickadate/' ) );
	src(['assets/libs/pickadate/translations/*.js', '!assets/libs/pickadate/translations/*.min.js',])
		.pipe( uglify() )
		.pipe( rename({ suffix: '.min' }) )
		.pipe( dest( 'assets/libs/pickadate/translations/' ) );

	done();
}
exports.default = defaultTask;
