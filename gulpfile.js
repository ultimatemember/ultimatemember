/*
  Ultimate Member dependencies
*/

const gulp       = require( 'gulp' );
const sass       = require( 'gulp-sass' );
const uglify     = require( 'gulp-uglify' );
const cleanCSS   = require( 'gulp-clean-css' );
const rename     = require( 'gulp-rename' );
const concat = require( 'gulp-concat' );
const md5        = require( 'v_to_md5' );


const modules = [
	'forumwp',
	'jobboardwp',
	'member-directory',
	'online',
	'recaptcha',
	'terms-conditions',
];

let matrix = [];

function permute(permutation) {
	var length = permutation.length,
		result = [permutation.slice()],
		c = new Array(length).fill(0),
		i = 1, k, p;

	while (i < length) {
		if (c[i] < i) {
			k = i % 2 && c[i];
			p = permutation[i];
			permutation[i] = permutation[k];
			permutation[k] = p;
			++c[i];
			i = 1;
			result.push(permutation.slice());
		} else {
			c[i] = 0;
			++i;
		}
	}
	return result;
}

for ( let pos = 0; pos < modules.length; pos++ ) {
	let submatrix = Array( modules.length ).fill( false ).fill( true, pos );

	if ( pos !== 0 ) {
		let sm = permute( submatrix );
		for ( let i = 0; i < sm.length; i++ ) {
			matrix.push( sm[ i ] );
		}
	} else {
		matrix.push( submatrix );
	}
}

let names = [];
for ( let i = 0; i < matrix.length; i++ ) {
	let names_row = [];
	for ( let j = 0; j < matrix[i].length; j++ ) {
		if ( matrix[i][j] ) {
			names_row.push( modules[j] );
		}
	}

	names[ names_row.join('') ] = names_row;
}


// task
gulp.task( 'default', function ( done ) {
	sass.compiler = require( 'node-sass' );

	// Password Reset
	gulp.src(['assets/css/password-reset/*.sass'])
		.pipe( sass().on( 'error', sass.logError ) )
		.pipe( gulp.dest( 'assets/css/password-reset/' ) );
	gulp.src(['assets/css/password-reset/*.sass'])
		.pipe( sass().on( 'error', sass.logError ) )
		.pipe( cleanCSS() )
		.pipe( rename( { suffix: '.min' } ) )
		.pipe( gulp.dest( 'assets/css/password-reset/' ) );

	// Login
	gulp.src(['assets/css/login/*.sass'])
		.pipe( sass().on( 'error', sass.logError ) )
		.pipe( gulp.dest( 'assets/css/login/' ) );
	gulp.src(['assets/css/login/*.sass'])
		.pipe( sass().on( 'error', sass.logError ) )
		.pipe( cleanCSS() )
		.pipe( rename( { suffix: '.min' } ) )
		.pipe( gulp.dest( 'assets/css/login/' ) );




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

	// min JS files
	gulp.src(['assets/js/*.js', '!assets/js/*.min.js', ])
		.pipe( uglify() )
		.pipe( rename({ suffix: '.min' }) )
		.pipe( gulp.dest( 'assets/js/' ) );

	// min wp-admin JS files
	gulp.src(['assets/js/admin/*.js', '!assets/js/admin/*.min.js', ])
		.pipe( uglify() )
		.pipe( rename({ suffix: '.min' }) )
		.pipe( gulp.dest( 'assets/js/admin/' ) );

	// min JS files
	gulp.src(['assets/libs/cropper/*.js', '!assets/libs/cropper/*.min.js', ])
		.pipe( uglify() )
		.pipe( rename({ suffix: '.min' }) )
		.pipe( gulp.dest( 'assets/libs/cropper/' ) );

	// min CSS files
	gulp.src(['assets/libs/cropper/*.css', '!assets/libs/cropper/*.min.css',])
		.pipe( cleanCSS() )
		.pipe( rename( { suffix: '.min' } ) )
		.pipe( gulp.dest( 'assets/libs/cropper/' ) );

	// min JS files
	gulp.src(['assets/libs/dropdown/*.js', '!assets/libs/dropdown/*.min.js', ])
		.pipe( uglify() )
		.pipe( rename({ suffix: '.min' }) )
		.pipe( gulp.dest( 'assets/libs/dropdown/' ) );

	// FA CSS
	gulp.src( 'assets/libs/fontawesome/scss/*.scss' )
		.pipe( sass().on( 'error', sass.logError ) )
		.pipe( gulp.dest( 'assets/libs/fontawesome/css/' ) );

	// FA CSS min
	gulp.src( 'assets/libs/fontawesome/scss/*.scss' )
		.pipe( sass().on( 'error', sass.logError ) )
		.pipe( cleanCSS() )
		.pipe( rename( { suffix: '.min' } ) )
		.pipe( gulp.dest( 'assets/libs/fontawesome/css/' ) );


	// ionicons CSS
	gulp.src( 'assets/libs/ionicons/*.sass' )
		.pipe( sass().on( 'error', sass.logError ) )
		.pipe( gulp.dest( 'assets/libs/ionicons/' ) );

	// ionicons CSS min
	gulp.src( 'assets/libs/ionicons/*.sass' )
		.pipe( sass().on( 'error', sass.logError ) )
		.pipe( cleanCSS() )
		.pipe( rename( { suffix: '.min' } ) )
		.pipe( gulp.dest( 'assets/libs/ionicons/' ) );


	// min CSS fonticons
	gulp.src(['assets/libs/fonticons/*.css', '!assets/libs/fonticons/*.min.css',])
		.pipe( cleanCSS() )
		.pipe( rename( { suffix: '.min' } ) )
		.pipe( gulp.dest( 'assets/libs/fonticons/' ) );


	// full CSS files
	gulp.src(['assets/libs/helptip/*.sass'])
		.pipe( sass().on( 'error', sass.logError ) )
		.pipe( gulp.dest( 'assets/libs/helptip/' ) );

	// min CSS files
	gulp.src(['assets/libs/helptip/*.sass'])
		.pipe( sass().on( 'error', sass.logError ) )
		.pipe( cleanCSS() )
		.pipe( rename( { suffix: '.min' } ) )
		.pipe( gulp.dest( 'assets/libs/helptip/' ) );

	// min JS files
	gulp.src(['assets/libs/helptip/*.js', '!assets/libs/helptip/*.min.js', ])
		.pipe( uglify() )
		.pipe( rename({ suffix: '.min' }) )
		.pipe( gulp.dest( 'assets/libs/helptip/' ) );

	// full CSS files
	gulp.src(['assets/libs/modal/*.sass'])
		.pipe( sass().on( 'error', sass.logError ) )
		.pipe( gulp.dest( 'assets/libs/modal/' ) );

	// min CSS files
	gulp.src(['assets/libs/modal/*.sass'])
		.pipe( sass().on( 'error', sass.logError ) )
		.pipe( cleanCSS() )
		.pipe( rename( { suffix: '.min' } ) )
		.pipe( gulp.dest( 'assets/libs/modal/' ) );

	// min JS files
	gulp.src(['assets/libs/modal/*.js', '!assets/libs/modal/*.min.js', ])
		.pipe( uglify() )
		.pipe( rename({ suffix: '.min' }) )
		.pipe( gulp.dest( 'assets/libs/modal/' ) );

	// Pickadate lib
	gulp.src(['assets/libs/pickadate/*.css', '!assets/libs/pickadate/*.min.css',])
		.pipe( cleanCSS() )
		.pipe( rename( { suffix: '.min' } ) )
		.pipe( gulp.dest( 'assets/libs/pickadate/' ) );

	// min JS files
	gulp.src(['assets/libs/pickadate/*.js', '!assets/libs/pickadate/*.min.js', ])
		.pipe( uglify() )
		.pipe( rename({ suffix: '.min' }) )
		.pipe( gulp.dest( 'assets/libs/pickadate/' ) );

	// Raty lib
	gulp.src(['assets/libs/raty/*.css', '!assets/libs/raty/*.min.css',])
		.pipe( cleanCSS() )
		.pipe( rename( { suffix: '.min' } ) )
		.pipe( gulp.dest( 'assets/libs/raty/' ) );

	// min JS files
	gulp.src(['assets/libs/raty/*.js', '!assets/libs/raty/*.min.js', ])
		.pipe( uglify() )
		.pipe( rename({ suffix: '.min' }) )
		.pipe( gulp.dest( 'assets/libs/raty/' ) );

	// Raty lib
	gulp.src(['assets/libs/tipsy/*.css', '!assets/libs/tipsy/*.min.css',])
		.pipe( cleanCSS() )
		.pipe( rename( { suffix: '.min' } ) )
		.pipe( gulp.dest( 'assets/libs/tipsy/' ) );

	// min JS files
	gulp.src(['assets/libs/tipsy/*.js', '!assets/libs/tipsy/*.min.js', ])
		.pipe( uglify() )
		.pipe( rename({ suffix: '.min' }) )
		.pipe( gulp.dest( 'assets/libs/tipsy/' ) );


	for ( let pos = 0; pos < modules.length; pos++ ) {

		let css_source = 'modules/' + modules[ pos ] + '/assets/css/*.sass';
		let css_dest   = 'modules/' + modules[ pos ] + '/assets/css/';

		let js_source =['modules/' + modules[ pos ] + '/assets/js/*.js', '!modules/' + modules[ pos ] + '/assets/js/*.min.js', ];
		let js_admin_source =['modules/' + modules[ pos ] + '/assets/js/admin/*.js', '!modules/' + modules[ pos ] + '/assets/js/admin/*.min.js', ];
		let js_dest = 'modules/' + modules[ pos ] + '/assets/js/';
		let js_admin_dest = 'modules/' + modules[ pos ] + '/assets/js/admin/';

		// full CSS files
		gulp.src( css_source )
			.pipe( sass().on( 'error', sass.logError ) )
			.pipe( gulp.dest( css_dest ) );

		// min CSS files
		gulp.src( css_source )
			.pipe( sass().on( 'error', sass.logError ) )
			.pipe( cleanCSS() )
			.pipe( rename( { suffix: '.min' } ) )
			.pipe( gulp.dest( css_dest ) );

		// min JS files
		gulp.src(js_source)
			.pipe( uglify() )
			.pipe( rename({ suffix: '.min' }) )
			.pipe( gulp.dest( js_dest ) );

		// min wp-admin JS files
		gulp.src( js_admin_source )
			.pipe( uglify() )
			.pipe( rename({ suffix: '.min' }) )
			.pipe( gulp.dest( js_admin_dest ) );
	}

	// modules
	for (const [index, currentValue] of Object.entries( names ) ) {
		let cv = currentValue.map( function ( val, indx, arr ) {
			return 'modules/' + val + '/assets/js/*.js'
		} );

		let css_cv = currentValue.map( function ( val, indx, arr ) {
			return 'modules/' + val + '/assets/css/*.sass'
		} );

		Promise.resolve( md5( index ) ).then( function( value ) {
			gulp.src( cv )
				.pipe( concat( value + '.js' ) )
				.pipe( gulp.dest('assets/modules/') );

			gulp.src( cv )
				.pipe( concat( value + '.min.js' ) )
				.pipe( uglify() )
				.pipe( gulp.dest( 'assets/modules/' ) );

			// full CSS files
			gulp.src( css_cv )
				.pipe( sass().on( 'error', sass.logError ) )
				.pipe( concat( value + '.css' ) )
				.pipe( gulp.dest( 'assets/modules/' ) );

			// min CSS files
			gulp.src( css_cv )
				.pipe( sass().on( 'error', sass.logError ) )
				.pipe( cleanCSS() )
				.pipe( concat( value + '.min.css' ) )
				.pipe( gulp.dest( 'assets/modules/' ) );
		});
	}

	done();
});
