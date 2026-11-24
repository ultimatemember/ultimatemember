<?php
/**
 * PHPUnit bootstrap for Ultimate Member integration tests.
 *
 * Runs on a real WordPress test install (WP_UnitTestCase). In wp-env, WP_TESTS_DIR
 * is already set to the WordPress PHPUnit library that matches the installed core.
 *
 * @package UM\Tests
 */

$um_tests_wp_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $um_tests_wp_dir ) {
	$um_tests_wp_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $um_tests_wp_dir . '/includes/functions.php' ) ) {
	fwrite( STDERR, "Could not find the WordPress test library in {$um_tests_wp_dir}.\nRun the tests through wp-env: npm run test:php (see CLAUDE.md).\n" );
	exit( 1 );
}

$um_tests_polyfills = __DIR__ . '/vendor/yoast/phpunit-polyfills';
if ( ! file_exists( $um_tests_polyfills . '/phpunitpolyfills-autoload.php' ) ) {
	fwrite( STDERR, "Yoast PHPUnit Polyfills are missing. Run: npm run test:php:setup\n" );
	exit( 1 );
}
define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', $um_tests_polyfills );

require_once $um_tests_wp_dir . '/includes/functions.php';

tests_add_filter(
	'muplugins_loaded',
	static function () {
		require dirname( __DIR__, 2 ) . '/ultimate-member.php';
	}
);

require $um_tests_wp_dir . '/includes/bootstrap.php';

/*
 * Emulate plugin activation once per run: custom tables, default settings and
 * default UM meta for the built-in WP roles. Written outside of test transactions,
 * so this is the baseline state every test starts from.
 */
UM()->setup()->run_setup();
// Options are read once in the constructor; reload them after setup wrote the defaults.
UM()->classes['options'] = new um\core\Options();

require_once __DIR__ . '/includes/class-um-unittestcase.php';
