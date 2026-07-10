<?php
namespace um\frontend\form;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Row {

	/**
	 * @var Column[]
	 */
	public $columns = array();

	public function render() {
		ob_start();
		foreach ( $this->columns as $column ) {
			$column->render();
		}
		ob_get_flush();
	}

	public function add_column( $data ) {
		$this->columns[] = $data;
	}
}
