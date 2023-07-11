<?php
namespace um\admin\core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\admin\core\Admin_DragDrop' ) ) {

	/**
	 * Class Admin_DragDrop
	 * @package um\admin\core
	 */
	class Admin_DragDrop {

		/**
		 * @var array
		 */
		public $row_data = array();

		/**
		 * @var array
		 */
		public $exist_rows = array();

		/**
		 * Admin_DragDrop constructor.
		 */
		public function __construct() {
			add_action( 'admin_footer', array( &$this, 'load_field_order' ), 9 );
		}

		/**
		 * Update order of fields.
		 */
		public function update_order() {
			UM()->admin()->check_ajax_nonce();
			// phpcs:disable WordPress.Security.NonceVerification -- already verified here

			if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( __( 'Please login as administrator', 'ultimate-member' ) );
			}

			if ( empty( $_POST['form_id'] ) ) {
				wp_send_json_error( __( 'Invalid form ID.', 'ultimate-member' ) );
			}

			$form_id = absint( $_POST['form_id'] );
			if ( empty( $form_id ) ) {
				wp_send_json_error( __( 'Invalid form ID.', 'ultimate-member' ) );
			}

			$fields = UM()->query()->get_attr( 'custom_fields', $form_id );

			$this->row_data   = get_option( 'um_form_rowdata_' . $form_id, array() );
			$this->exist_rows = array();

			if ( ! empty( $fields ) ) {
				foreach ( $fields as $key => $array ) {
					if ( 'row' === $array['type'] ) {
						$this->row_data[ $key ] = $array;
						unset( $fields[ $key ] );
					}
				}
			} else {
				$fields = array();
			}

			foreach ( $_POST as $key => $value ) {
				// don't use sanitize_key here because of a key can be in Uppercase
				$key = sanitize_text_field( $key );

				// adding rows
				if ( 0 === strpos( $key, '_um_row_' ) ) {
					$update_args = null;

					$row_id = str_replace( '_um_row_', '', $key );

					if ( false !== strpos( $_POST[ '_um_rowcols_' . $row_id . '_cols' ], ':' ) ) {
						$cols = sanitize_text_field( $_POST[ '_um_rowcols_' . $row_id . '_cols' ] );
					} else {
						$cols = absint( $_POST[ '_um_rowcols_' . $row_id . '_cols' ] );
					}

					$row_array = array(
						'type'     => 'row',
						'id'       => sanitize_key( $value ),
						'sub_rows' => absint( $_POST[ '_um_rowsub_' . $row_id . '_rows' ] ),
						'cols'     => $cols,
						'origin'   => sanitize_key( $_POST[ '_um_roworigin_' . $row_id . '_val' ] ),
					);

					$row_args = $row_array;

					if ( isset( $this->row_data[ $row_array['origin'] ] ) ) {
						foreach ( $this->row_data[ $row_array['origin'] ] as $k => $v ) {
							if ( 'position' !== $k && 'metakey' !== $k ) {
								$update_args[ $k ] = $v;
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
						$fields[ $field_key ]['position'] = absint( $value );
					}
				}

				// change field master row
				if ( 0 === strpos( $key, 'um_row_' ) ) {
					$field_key = str_replace( 'um_row_', '', $key );
					if ( isset( $fields[ $field_key ] ) ) {
						$fields[ $field_key ]['in_row'] = sanitize_key( $value );
					}
				}

				// change field sub row
				if ( 0 === strpos( $key, 'um_subrow_' ) ) {
					$field_key = str_replace( 'um_subrow_', '', $key );
					if ( isset( $fields[ $field_key ] ) ) {
						$fields[ $field_key ]['in_sub_row'] = sanitize_key( $value );
					}
				}

				// change field column
				if ( 0 === strpos( $key, 'um_col_' ) ) {
					$field_key = str_replace( 'um_col_', '', $key );
					if ( isset( $fields[ $field_key ] ) ) {
						$fields[ $field_key ]['in_column'] = absint( $value );
					}
				}

				// add field to group
				if ( 0 === strpos( $key, 'um_group_' ) ) {
					$field_key = str_replace( 'um_group_', '', $key );
					if ( isset( $fields[ $field_key ] ) ) {
						$fields[ $field_key ]['in_group'] = ! empty( $value ) ? absint( $value ) : '';
					}
				}
			}

			foreach ( $this->row_data as $k => $v ) {
				if ( ! in_array( $k, $this->exist_rows, true ) ) {
					unset( $this->row_data[ $k ] );
				}
			}

			update_option( 'um_existing_rows_' . $form_id, $this->exist_rows );

			update_option( 'um_form_rowdata_' . $form_id, $this->row_data );

			UM()->query()->update_attr( 'custom_fields', $form_id, $fields );
			// phpcs:enable WordPress.Security.NonceVerification -- already verified here
		}

		/**
		 * Load form to maintain form order.
		 */
		public function load_field_order() {
			$screen = get_current_screen();

			if ( ! isset( $screen, $screen->id ) || 'um_form' !== $screen->id ) {
				return;
			} ?>

			<div class="um-col-demon-settings" data-in_row="" data-in_sub_row="" data-in_column="" data-in_group=""></div>

			<div class="um-col-demon-row" style="display:none;">
				<span class="um-admin-row-loading"><span></span></span>

				<div class="um-admin-drag-row-icons">
					<a href="javascript:void(0);" class="um-admin-drag-rowsub-add um-admin-tipsy-n" title="<?php esc_attr_e( 'Add Row', 'ultimate-member' ); ?>" data-row_action="add_subrow"><i class="um-icon-plus"></i></a>
					<a href="javascript:void(0);" class="um-admin-drag-row-edit um-admin-tipsy-n" title="<?php esc_attr_e( 'Edit Row', 'ultimate-member' ); ?>" data-modal="UM_edit_row" data-modal-size="normal" data-dynamic-content="um_admin_edit_field_popup" data-arg1="row" data-arg2="<?php echo esc_attr( get_the_ID() ); ?>"><i class="um-faicon-pencil"></i></a>
					<span class="um-admin-drag-row-start"><i class="um-icon-arrow-move"></i></span>
					<a href="javascript:void(0);" class="um-admin-tipsy-n" title="<?php esc_attr_e( 'Delete Row', 'ultimate-member' ); ?>" data-remove_element="um-admin-drag-row"><i class="um-faicon-trash-o"></i></a>
				</div>
				<div class="um-admin-clear"></div>

				<div class="um-admin-drag-rowsubs">
					<div class="um-admin-drag-rowsub">
						<span class="um-admin-row-loading"><span></span></span>

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
				<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'um-admin-nonce' ) ); ?>" />

				<div class="um_update_order_fields">

				</div>

			</form>

			<?php

		}
	}
}
