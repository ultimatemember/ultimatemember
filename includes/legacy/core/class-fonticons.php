<?php
namespace um\core;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'um\core\FontIcons' ) ) {


	/**
	 * Class FontIcons
	 * @package um\core
	 */
	class FontIcons {


		/**
		 * FontIcons constructor.
		 */
		function __construct() {

			if ( ! get_option( 'um_cache_fonticons' ) ) {

				$files['ii'] = um_path . 'assets/css/um-fonticons-ii.css';
				$files['fa'] = um_path . 'assets/css/um-fonticons-fa.css';

				$array = array();
				foreach ( $files as $c => $file ) {

					$css = file_get_contents( $file );

					if ( $c == 'fa' ) {
						preg_match_all('/\.(um-faicon-.*?):before/', $css, $matches);
					} else {
						preg_match_all('/\.(um-icon-.*?):before/', $css, $matches);
					}

					foreach ( $matches[1] as $match ) {
						$icon = str_replace( ':before', '', $match );
						$array[] = $icon;
					}
					$array = array_unique( $array );
				}

				update_option( 'um_cache_fonticons', $array );
			}

			$this->all = get_option( 'um_cache_fonticons' );

		}

	}
}