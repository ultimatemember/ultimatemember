<?php
namespace um\frontend\form;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Rows_Group {

	private $title = '';

	/**
	 * @var Row[]
	 */
	public $rows = array();

	public function __construct( $data ) {
		$this->title = $data['title'];
	}

	public function render() {
		ob_start();
		echo esc_html( $this->title );
		foreach ( $this->rows as $row ) {
			$row->render();
		}
		ob_get_flush();
	}

	public function add_row( $data ) {
		$this->rows[] = $data;
	}
}
