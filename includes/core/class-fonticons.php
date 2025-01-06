<?php
namespace um\core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\core\FontIcons' ) ) {

	/**
	 * Class FontIcons
	 * @package um\core
	 */
	class FontIcons {

		/**
		 * The list of the FontIcons.
		 *
		 * @var array
		 */
		public $all = array();

		/**
		 * @var string
		 */
		private $default_file_fonticon = 'um-faicon-file-o';

		/**
		 * @var array|array[]
		 */
		private $file_fonticons;

		/**
		 * FontIcons constructor.
		 */
		public function __construct() {
			$cached_option = get_option( 'um_cache_fonticons', array() );

			if ( empty( $cached_option ) ) {
				$files['ii'] = UM_PATH . 'assets/libs/legacy/fonticons/fonticons-ii.css';
				$files['fa'] = UM_PATH . 'assets/libs/legacy/fonticons/fonticons-fa.css';

				$array = array();
				foreach ( $files as $c => $file ) {
					$css = file_get_contents( $file );

					if ( 'fa' === $c ) {
						preg_match_all( '/\.(um-faicon-.*?):before/', $css, $matches );
					} else {
						preg_match_all( '/\.(um-icon-.*?):before/', $css, $matches );
					}

					foreach ( $matches[1] as $match ) {
						$icon    = str_replace( ':before', '', $match );
						$array[] = $icon;
					}
					$array = array_unique( $array );
				}

				update_option( 'um_cache_fonticons', $array );
			}

			$this->all = $cached_option;

			$this->file_fonticons = array(
				'pdf'  => array(
					'icon'  => 'um-faicon-file-pdf-o',
					'color' => '#D24D4D',
				),
				'txt'  => array( 'icon' => 'um-faicon-file-text-o' ),
				'csv'  => array( 'icon' => 'um-faicon-file-text-o' ),
				'doc'  => array(
					'icon'  => 'um-faicon-file-text-o',
					'color' => '#2C95D5',
				),
				'docx' => array(
					'icon'  => 'um-faicon-file-text-o',
					'color' => '#2C95D5',
				),
				'odt'  => array(
					'icon'  => 'um-faicon-file-text-o',
					'color' => '#2C95D5',
				),
				'ods'  => array(
					'icon'  => 'um-faicon-file-excel-o',
					'color' => '#51BA6A',
				),
				'xls'  => array(
					'icon'  => 'um-faicon-file-excel-o',
					'color' => '#51BA6A',
				),
				'xlsx' => array(
					'icon'  => 'um-faicon-file-excel-o',
					'color' => '#51BA6A',
				),
				'zip'  => array( 'icon' => 'um-faicon-file-zip-o' ),
				'rar'  => array( 'icon' => 'um-faicon-file-zip-o' ),
				'mp3'  => array( 'icon' => 'um-faicon-file-audio-o' ),
				'jpg'  => array( 'icon' => 'um-faicon-picture-o' ),
				'jpeg' => array( 'icon' => 'um-faicon-picture-o' ),
				'png'  => array( 'icon' => 'um-icon-image' ),
				'gif'  => array( 'icon' => 'um-icon-images' ),
				'eps'  => array( 'icon' => 'um-icon-images' ),
				'psd'  => array( 'icon' => 'um-icon-images' ),
				'tif'  => array( 'icon' => 'um-icon-image' ),
				'tiff' => array( 'icon' => 'um-icon-image' ),
			);
		}

		/**
		 * Get extension icon
		 *
		 * @since 3.0.0
		 *
		 * @param string $extension
		 *
		 * @return string
		 */
		public function get_file_fonticon( $extension ) {
			if ( isset( $this->file_fonticons[ $extension ]['icon'] ) ) {
				return $this->file_fonticons[ $extension ]['icon'];
			}
			return $this->default_file_fonticon;
		}

		/**
		 * Get extension icon background
		 *
		 * @since 3.0.0
		 *
		 * @param string $extension
		 *
		 * @return string
		 */
		public function get_file_fonticon_bg( $extension ) {
			if ( isset( $this->file_fonticons[ $extension ]['color'] ) ) {
				return $this->file_fonticons[ $extension ]['color'];
			}

			return '#666';
		}
	}
}
