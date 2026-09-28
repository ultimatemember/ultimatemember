<?php
namespace um\common;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Uploader
 *
 * @package um\common
 */
class Uploader {

	// Adding Constants for the Handlers
	protected const HANDLER_UPLOAD = 'common-upload'; // @todo maybe remove it before release

	public const HANDLER_FIELD_FILE = 'field-file';

	public const HANDLER_FIELD_IMAGE = 'field-image';


}
