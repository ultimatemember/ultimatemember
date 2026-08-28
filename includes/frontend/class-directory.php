<?php
namespace um\frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Directory
 *
 * @package um\frontend
 */
class Directory extends \um\common\Directory {

	/**
	 * @param $filter
	 * @param $directory_data
	 * @param $default_value
	 *
	 * @return void
	 *
	 * @todo maybe remove because unused
	 */
	public function get_filter_data( $filter, $directory_data, $default_value = false ) {
		$filter_content = $this->show_filter( $filter, $directory_data );
		$type           = $this->filter_types[ $filter ];
		$unique_hash    = $this->get_directory_hash( $directory_data['form_id'] );

		$filter_from_url = ! empty( $_GET[ 'filter_' . $filter . '_' . $unique_hash ] ) ? sanitize_text_field( $_GET[ 'filter_' . $filter . '_' . $unique_hash ] ) : $default_value;
	}

	/**
	 * @todo maybe re-write for using 32-bit hash string in $_GET attribute for all filters params
	 *
	 * @param $search_filters
	 * @param $args
	 *
	 * @return false|string
	 */
	public function get_filters_hash( $search_filters, $args ) {
		$hash_entities = array();
		foreach ( $search_filters as $filter => $filter_data ) {
			switch ( $filter_data['type'] ) {
				case 'text':
					$filter_from_url = ! empty( $_GET[ 'filter_' . $filter . '_' . $args['unique_hash'] ] ) ? sanitize_text_field( $_GET[ 'filter_' . $filter . '_' . $args['unique_hash'] ] ) : '';
					if ( empty( $filter_from_url ) ) {
						continue 2;
					}
					$hash_entities[ $filter ] = $filter_from_url;
					break;

				case 'select':
					// getting value from GET line
					$filter_from_url = ! empty( $_GET[ 'filter_' . $filter . '_' . $args['unique_hash'] ] ) ? explode( '||', sanitize_text_field( $_GET[ 'filter_' . $filter . '_' . $args['unique_hash'] ] ) ) : array();
					if ( empty( $filter_from_url ) ) {
						continue 2;
					}
					$hash_entities[ $filter ] = $filter_from_url;
					break;

				case 'slider':
					$range                = $this->slider_filters_range( $filter, $args );
					$filter_from_url_from = ! empty( $_GET[ 'filter_' . $filter . '_from_' . $args['unique_hash'] ] ) ? sanitize_text_field( $_GET[ 'filter_' . $filter . '_from_' . $args['unique_hash'] ] ) : '';
					$filter_from_url_to   = ! empty( $_GET[ 'filter_' . $filter . '_to_' . $args['unique_hash'] ] ) ? sanitize_text_field( $_GET[ 'filter_' . $filter . '_to_' . $args['unique_hash'] ] ) : '';

					$filter_from_url_from = $filter_from_url_from !== $range[0] ? $filter_from_url_from : '';
					$filter_from_url_to   = $filter_from_url_to !== $range[1] ? $filter_from_url_to : '';

					if ( empty( $filter_from_url_from ) && empty( $filter_from_url_to ) ) {
						continue 2;
					}
					$hash_entities[ $filter ] = array( $filter_from_url_from, $filter_from_url_to );
					break;
				case 'datepicker':
				case 'timepicker':
					$filter_from_url_from = ! empty( $_GET[ 'filter_' . $filter . '_from_' . $args['unique_hash'] ] ) ? sanitize_text_field( $_GET[ 'filter_' . $filter . '_from_' . $args['unique_hash'] ] ) : '';
					$filter_from_url_to   = ! empty( $_GET[ 'filter_' . $filter . '_to_' . $args['unique_hash'] ] ) ? sanitize_text_field( $_GET[ 'filter_' . $filter . '_to_' . $args['unique_hash'] ] ) : '';
					if ( empty( $filter_from_url_from ) && empty( $filter_from_url_to ) ) {
						continue 2;
					}
					$hash_entities[ $filter ] = array( $filter_from_url_from, $filter_from_url_to );
					break;
			}
		}

		if ( empty( $hash_entities ) ) {
			return '';
		}

		$json_hash = wp_json_encode( $hash_entities );
		/*$json_hash_length = mb_strlen( $json_hash );
		for ( $i = 0; $i < $json_hash_length; $i++ ) {
			$utf8Character = 'Ą';
			list(, $ord) = unpack('N', mb_convert_encoding($utf8Character, 'UCS-4BE', 'UTF-8'));
			echo $ord; # 260
		}

		for (let i = 0; i < $json_hash.length; i++) {
			const char = str.charCodeAt(i);
			hash = ((hash << 5) - hash) + char;
			hash |= 0; // Convert to 32-bit integer
		}*/

		return $json_hash;
	}
}
