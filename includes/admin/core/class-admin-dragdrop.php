<?php
namespace um\admin\core;


if ( ! defined( 'ABSPATH' ) ) exit;


if ( ! class_exists( 'um\admin\core\Admin_DragDrop' ) ) {


	/**
	 * Class Admin_DragDrop
	 * @package um\admin\core
	 */
	class Admin_DragDrop {


		/**
		 * Admin_DragDrop constructor.
		 */
		function __construct() {
			add_action( 'admin_footer', array( &$this, 'load_field_order' ), 9 );
		}


		/**
		 * Update order of fields
		 */
		function update_order() {
			UM()->admin()->check_ajax_nonce();

			if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( __( 'Please login as administrator', 'ultimate-member' ) );
			}

			/**
			 * @var $form_id
			 */
			extract( $_POST );

			$fields = UM()->query()->get_attr( 'custom_fields', $form_id );

			$this->row_data = get_option( 'um_form_rowdata_' . $form_id, array() );
			$this->exist_rows = array();

			if ( ! empty( $fields ) ) {
				foreach ( $fields as $key => $array ) {
					if ( $array['type'] == 'row' ) {
						$this->row_data[ $key ] = $array;
						unset( $fields[ $key ] );
					}
				}
			} else {
				$fields = array();
			}

			foreach ( $_POST as $key => $value ) {

				// adding rows
				if ( 0 === strpos( $key, '_um_row_' ) ) {

					$update_args = null;

					$row_id = str_replace( '_um_row_', '', $key );

					$row_array = array(
						'type' => 'row',
						'id' => $value,
						'sub_rows' => $_POST[ '_um_rowsub_'.$row_id .'_rows' ],
						'cols' => $_POST[ '_um_rowcols_'.$row_id .'_cols' ],
						'origin' => $_POST[ '_um_roworigin_'.$row_id . '_val' ],
					);

					$row_args = $row_array;

					if ( isset( $this->row_data[ $row_array['origin'] ] ) ) {
						foreach ( $this->row_data[ $row_array['origin'] ] as $k => $v ){
							if ( $k != 'position' && $k != 'metakey' ) {
								$update_args[$k] = $v;
							}
						}
						if ( isset( $update_args ) ) {
							$row_args = array_merge( $update_args, $row_array );
						}
						$this->exist_rows[] = $key;
					}

					$fields[ $key ] = $row_args;

				}

				// change field position
				if ( 0 === strpos( $key, 'um_position_' ) ) {
					$field_key = str_replace( 'um_position_', '', $key );
					if ( isset( $fields[ $field_key ] ) ) {
						$fields[ $field_key ]['position'] = $value;
					}
				}

				// change field master row
				if ( 0 === strpos( $key, 'um_row_' ) ) {
					$field_key = str_replace( 'um_row_', '', $key );
					if ( isset( $fields[ $field_key ] ) ) {
						$fields[ $field_key ]['in_row'] = $value;
					}
				}

				// change field sub row
				if ( 0 === strpos( $key, 'um_subrow_' ) ) {
					$field_key = str_replace( 'um_subrow_', '', $key );
					if ( isset( $fields[ $field_key ] ) ) {
						$fields[ $field_key ]['in_sub_row'] = $value;
					}
				}

				// change field column
				if ( 0 === strpos( $key, 'um_col_' ) ) {
					$field_key = str_replace( 'um_col_', '', $key );
					if ( isset( $fields[ $field_key ] ) ) {
						$fields[ $field_key ]['in_column'] = $value;
					}
				}

				// add field to group
				if ( 0 === strpos( $key, 'um_group_' ) ) {
					$field_key = str_replace( 'um_group_', '', $key );
					if ( isset( $fields[ $field_key ] ) ) {
						$fields[ $field_key ]['in_group'] = $value;
					}
				}

			}

			foreach ( $this->row_data as $k => $v ) {
				if ( ! in_array( $k, $this->exist_rows ) ) {
					unset( $this->row_data[ $k ] );
				}
			}

			update_option( 'um_existing_rows_' . $form_id, $this->exist_rows );

			update_option( 'um_form_rowdata_' . $form_id , $this->row_data );

			UM()->query()->update_attr( 'custom_fields', $form_id, $fields );

		}


		/**
		 * Load form to maintain form order
		 */
		function load_field_order() {

			$screen = get_current_screen();

			if ( ! isset( $screen->id ) || $screen->id != 'um_form' ) {
				return;
			} ?>

			<div class="um-col-demon-settings" data-in_row="" data-in_sub_row="" data-in_column="" data-in_group=""></div>

			<div class="um-col-demon-row" style="display:none;">

				<div class="um-admin-drag-row-icons">
					<a href="javascript:void(0);" class="um-admin-drag-rowsub-add um-admin-tipsy-n" title="<?php esc_attr_e( 'Add Row', 'ultimate-member' ); ?>" data-row_action="add_subrow"><i class="um-icon-plus"></i></a>
					<a href="javascript:void(0);" class="um-admin-drag-row-edit um-admin-tipsy-n" title="<?php esc_attr_e( 'Edit Row', 'ultimate-member' ); ?>" data-modal="UM_edit_row" data-modal-size="normal" data-dynamic-content="um_admin_edit_field_popup" data-arg1="row" data-arg2="<?php echo esc_attr( get_the_ID() ); ?>"><i class="um-faicon-pencil"></i></a>
					<span class="um-admin-drag-row-start"><i class="um-icon-arrow-move"></i></span>
					<a href="javascript:void(0);" class="um-admin-tipsy-n" title="<?php esc_attr_e( 'Delete Row', 'ultimate-member' ); ?>" data-remove_element="um-admin-drag-row"><i class="um-faicon-trash-o"></i></a>
				</div>
				<div class="um-admin-clear"></div>

				<div class="um-admin-drag-rowsubs">
					<div class="um-admin-drag-rowsub">

						<div class="um-admin-drag-ctrls columns">
							<a href="javascript:void(0);" class="active" data-cols="1"></a>
							<a href="javascript:void(0);" data-cols="2"></a>
							<a href="javascript:void(0);" data-cols="3"></a>
						</div>

						<div class="um-admin-drag-rowsub-icons">
							<span class="um-admin-drag-rowsub-start"><i class="um-icon-arrow-move"></i></span>
							<a href="javascript:void(0);" class="um-admin-tipsy-n" title="<?php esc_attr_e( 'Delete Row', 'ultimate-member' ); ?>" data-remove_element="um-admin-drag-rowsub"><i class="um-faicon-trash-o"></i></a>
						</div><div class="um-admin-clear"></div>

						<div class="um-admin-drag-col">
						</div>

						<div class="um-admin-drag-col-dynamic"></div>

						<div class="um-admin-clear"></div>

					</div>
				</div>

			</div>

			<div class="um-col-demon-subrow" style="display:none;">

				<div class="um-admin-drag-ctrls columns">
					<a href="javascript:void(0);" class="active" data-cols="1"></a>
					<a href="javascript:void(0);" data-cols="2"></a>
					<a href="javascript:void(0);" data-cols="3"></a>
				</div>

				<div class="um-admin-drag-rowsub-icons">
					<span class="um-admin-drag-rowsub-start"><i class="um-icon-arrow-move"></i></span>
					<a href="javascript:void(0);" class="um-admin-tipsy-n" title="<?php esc_attr_e( 'Delete Row', 'ultimate-member' ); ?>" data-remove_element="um-admin-drag-rowsub"><i class="um-faicon-trash-o"></i></a>
				</div><div class="um-admin-clear"></div>

				<div class="um-admin-drag-col">
				</div>

				<div class="um-admin-drag-col-dynamic"></div>

				<div class="um-admin-clear"></div>

			</div>


			<form action="" method="post" class="um_update_order">

				<input type="hidden" name="form_id" id="form_id" value="<?php echo esc_attr( get_the_ID() ); ?>" />
				<input type="hidden" name="action" value="um_update_order" />
				<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'um-admin-nonce' ) ) ?>" />

				<div class="um_update_order_fields">

				</div>

			</form>

			<?php

		}

	}
}