<?php
namespace um\frontend\form\fields;

use um\frontend\form\Field;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Select extends Field {

	public function render() {
		return '<select></select>';
	}

	public function sanitize( $value ) {
		return sanitize_text_field( $value );
	}
}
