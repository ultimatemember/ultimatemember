<?php
namespace um\common;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\common\Filesystem' ) ) {


	/**
	 * Class Filesystem
	 *
	 * @package um\common
	 */
	class Filesystem {


		/**
		 * Filesystem constructor.
		 */
		function __construct() {
		}


		/**
		 * Get a directory size
		 *
		 * @param $directory
		 *
		 * @return float|int
		 */
		function dir_size( $directory ) {
			if ( $directory == 'temp' ) {
				$directory = UM()->files()->upload_temp;
				$size = 0;

				foreach( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $directory ) ) as $file ) {
					$filename = $file->getFilename();
					if ( $filename == '.' || $filename == '..' ) {
						continue;
					}

					$size += $file->getSize();
				}
				return round ( $size / 1048576, 2 );
			}
			return 0;
		}
	}
}
