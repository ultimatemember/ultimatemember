<?php
/**
 * Composer `copy-libs` script.
 *
 * Copies runtime libraries from vendor/ into includes/lib/ after `composer install` / `composer update`.
 *
 * Kept as a standalone file (instead of inline `@php -r "..."`) because on Linux Composer runs scripts through `sh`,
 * which expands `$variables` inside double quotes and breaks the PHP code. This file is not shipped with the plugin.
 */

$root = dirname( __DIR__ );

/**
 * Recursively copy a directory.
 *
 * @param string $src Source directory.
 * @param string $dst Destination directory.
 */
function um_copy_libs_rcopy( $src, $dst ) {
	if ( ! is_dir( $dst ) ) {
		mkdir( $dst, 0755, true );
	}

	foreach ( scandir( $src ) as $file ) {
		if ( '.' === $file || '..' === $file ) {
			continue;
		}

		$from = $src . '/' . $file;
		$to   = $dst . '/' . $file;

		if ( is_dir( $from ) ) {
			um_copy_libs_rcopy( $from, $to );
		} else {
			copy( $from, $to );
		}
	}
}

// Action Scheduler.
if ( is_dir( $root . '/vendor/woocommerce/action-scheduler' ) ) {
	um_copy_libs_rcopy( $root . '/vendor/woocommerce/action-scheduler', $root . '/includes/lib/action-scheduler' );
}

// WP Config Transformer.
$transformer = $root . '/vendor/wp-cli/wp-config-transformer/src/WPConfigTransformer.php';
if ( is_file( $transformer ) ) {
	if ( ! is_dir( $root . '/includes/lib/wp-config-transformer/src' ) ) {
		mkdir( $root . '/includes/lib/wp-config-transformer/src', 0755, true );
	}
	copy( $transformer, $root . '/includes/lib/wp-config-transformer/src/WPConfigTransformer.php' );
}
