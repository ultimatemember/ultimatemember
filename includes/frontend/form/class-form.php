<?php
namespace um\frontend\form;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Form {

	/**
	 * @var array
	 */
	protected $fields = array();

	/**
	 * @var array
	 */
	protected $layout = array();

//	public function add_field( $data ) {
//		$this->fields[] = $data;
//	}

	public function add_rows_group( $title = '' ) {
		$this->layout[] = new Rows_Group( array( 'title' => 'Title 1' ) );
	}

	public function add_row(  ) {

	}

	public function add_column( $row_id ) {

	}

	/**
	 * @return void
	 */
	public function display() {
		ob_start();
		?>
		<form>
			<?php foreach ( $this->fields as $field ) { ?>
			<?php } ?>
		</form>
		<?php
		ob_get_flush();
	}

	/**
	 * @return bool
	 */
	public function validate() {
		return true;
	}
}
