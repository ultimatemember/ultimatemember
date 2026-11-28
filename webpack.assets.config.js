/**
 * Ultimate Member assets build (wp-admin + own libraries).
 *
 * Run via @wordpress/scripts:
 *   npm run build:assets   - one-off production build.
 *   npm run start:assets   - watch mode.
 *
 * Sources live in `src/`, built files are written to `assets/` with the same
 * paths and file names as before, so PHP enqueue code does not change:
 *
 *   src/admin/js/{name}.js       -> assets/js/admin/{name}.js + {name}.min.js
 *   src/admin/css/{name}.sass    -> assets/css/admin/{name}.css + {name}.min.css
 *   src/libs/{lib}/{lib}.js      -> assets/libs/{lib}/{lib}.js + {lib}.min.js
 *   src/libs/{lib}/{lib}.(s)css  -> assets/libs/{lib}/{lib}.css + {lib}.min.css
 *
 * Both the readable and the `.min` versions are built because PHP picks one of
 * them depending on SCRIPT_DEBUG / UM_SCRIPT_DEBUG (see um\common\Enqueue::get_suffix()).
 *
 * Legacy jQuery / vanilla JS is NOT bundled as webpack modules: these files expose
 * global functions (e.g. um_admin_init_users_select()) used by other scripts and
 * extensions, and webpack's module wrapper would hide them. They are copied as-is
 * and minified with Terser (top-level names are kept), like gulp-uglify did before.
 *
 * Third-party libraries in assets/libs/ (select2, pickadate, raty, tipsy, cropper, ...)
 * are intentionally excluded and stay untouched.
 */
const path = require( 'path' );
const fs = require( 'fs' );
const MiniCssExtractPlugin = require( 'mini-css-extract-plugin' );
const RemoveEmptyScriptsPlugin = require( 'webpack-remove-empty-scripts' );
const CopyWebpackPlugin = require( 'copy-webpack-plugin' );
const { minify } = require( 'terser' );

const SRC = path.resolve( __dirname, 'src' );
const ASSETS = path.resolve( __dirname, 'assets' );

/**
 * Own libraries that are built from src/libs/. Third-party libs are not listed here on purpose.
 */
const OWN_LIBS = [ 'um-confirm', 'um-notice' ];

/**
 * Collect style entries: every non-partial .sass/.scss/.css file in a directory.
 *
 * @param {string} srcDir  Absolute source directory.
 * @param {string} outDir  Output directory relative to assets/.
 * @return {Object} Entry map { 'css/admin/forms': '/abs/src/admin/css/forms.sass' }.
 */
function styleEntries( srcDir, outDir ) {
	const entries = {};
	fs.readdirSync( srcDir )
		.filter( ( file ) => /\.(sass|scss|css)$/.test( file ) && ! file.startsWith( '_' ) )
		.forEach( ( file ) => {
			entries[ `${ outDir }/${ file.replace( /\.(sass|scss|css)$/, '' ) }` ] = path.join( srcDir, file );
		} );
	return entries;
}

const entry = {
	...styleEntries( path.join( SRC, 'admin/css' ), 'css/admin' ),
	...OWN_LIBS.reduce(
		( acc, lib ) => ( { ...acc, ...styleEntries( path.join( SRC, 'libs', lib ), `libs/${ lib }` ) } ),
		{}
	),
};

/**
 * Legacy scripts copied 1:1 from src/ to assets/.
 */
const scriptPatterns = [
	{ from: 'admin/js/*.js', to: 'js/admin/' },
	...OWN_LIBS.map( ( lib ) => ( { from: `libs/${ lib }/*.js`, to: `libs/${ lib }/` } ) ),
];

/**
 * Loaders chain for styles.
 *
 * @param {boolean} minified Minify with cssnano (the same minifier @wordpress/scripts uses).
 * @param {boolean} sass     Compile Sass first.
 * @return {Array} Webpack loaders.
 */
function styleLoaders( minified, sass ) {
	return [
		MiniCssExtractPlugin.loader,
		{
			loader: require.resolve( 'css-loader' ),
			options: {
				// Keep url(../../img/...) as is, it is relative to the built CSS file in assets/.
				url: false,
				import: false,
			},
		},
		...( minified
			? [
				{
					loader: require.resolve( 'postcss-loader' ),
					options: {
						postcssOptions: {
							plugins: [ require( 'cssnano' )( { preset: [ 'default', { cssDeclarationSorter: false } ] } ) ],
						},
					},
				},
			]
			: [] ),
		...( sass
			? [
				{
					loader: require.resolve( 'sass-loader' ),
					options: {
						sassOptions: {
							style: 'expanded',
							// Sass `@import` deprecation warnings are known, migration to `@use` is a separate task.
							silenceDeprecations: [ 'import', 'global-builtin', 'color-functions' ],
						},
					},
				},
			]
			: [] ),
	];
}

/**
 * Build one webpack config: readable (`minified = false`) or `.min` (`minified = true`) files.
 *
 * @param {boolean} minified
 * @return {Object} Webpack config.
 */
function makeConfig( minified ) {
	const suffix = minified ? '.min' : '';

	return {
		name: minified ? 'minified' : 'readable',
		mode: minified ? 'production' : 'development',
		devtool: false,
		context: SRC,
		entry,
		output: {
			path: ASSETS,
			// Style-only entries produce empty JS chunks, removed by RemoveEmptyScriptsPlugin.
			filename: `[name]${ suffix }.js`,
			// Never clean assets/: it also contains images, third-party libs and frontend files.
			clean: false,
			// No "!*** css ... ***!" path comments in readable (development) builds.
			pathinfo: false,
		},
		module: {
			rules: [
				// Plain CSS (e.g. um-confirm.css) is taken as is, only minified for `.min`.
				{ test: /\.css$/, use: styleLoaders( minified, false ) },
				{ test: /\.s[ac]ss$/, use: styleLoaders( minified, true ) },
			],
		},
		plugins: [
			new RemoveEmptyScriptsPlugin(),
			new MiniCssExtractPlugin( { filename: `[name]${ suffix }.css` } ),
			new CopyWebpackPlugin( {
				patterns: scriptPatterns.map( ( pattern ) => ( {
					...pattern,
					to: `${ pattern.to }[name]${ suffix }[ext]`,
					...( minified
						? {
							transform: {
								transformer: async ( content ) => {
									const result = await minify( content.toString(), {
										compress: true,
										mangle: true,
										format: { comments: false },
									} );
									return result.code;
								},
								cache: true,
							},
						}
						: {} ),
				} ) ),
			} ),
		],
		optimization: {
			// CSS is minified by cssnano, JS by the Copy transform above.
			minimize: false,
		},
		performance: { hints: false },
		stats: 'errors-warnings',
	};
}

module.exports = [ makeConfig( false ), makeConfig( true ) ];
