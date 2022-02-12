<?php if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


/**
 * Formats numbers nicely
 *
 * @param $count
 *
 * @return string
 */
function um_pretty_number_formatting( $count ) {
	$count = (int)$count;
	return number_format( $count );
}
add_filter( 'um_pretty_number_formatting', 'um_pretty_number_formatting' );