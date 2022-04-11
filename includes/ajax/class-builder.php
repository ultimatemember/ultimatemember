<?php
namespace um\ajax;


if ( ! defined( 'ABSPATH' ) ) exit;


if ( ! class_exists( 'um\ajax\Builder' ) ) {


	/**
	 * Class Builder
	 *
	 * @package um\ajax
	 */
	class Builder {


		/**
		 * Builder constructor.
		 */
		function __construct() {
			add_action( 'wp_ajax_um_admin_fields_list', array( &$this, 'admin_fields_list' ) );
			add_action( 'wp_ajax_um_admin_remove_field_global', array( &$this, 'remove_field_global' ) );

			add_action( 'wp_ajax_um_admin_new_field_popup', array( &$this, 'new_field_popup' ) );
			add_action( 'wp_ajax_um_admin_add_field_from_list', array( &$this, 'add_field_from_list' ) );
			add_action( 'wp_ajax_um_admin_add_field_from_predefined', array( &$this, 'add_field_from_predefined' ) );

			add_action( 'wp_ajax_um_admin_edit_field_popup', array( &$this, 'edit_field_popup' ) );
			add_action( 'wp_ajax_um_admin_preview_form', array( &$this, 'preview_form' ) );

			add_action( 'wp_ajax_um_admin_duplicate_field', array( &$this, 'duplicate_field' ) );
			add_action( 'wp_ajax_um_admin_remove_field', array( &$this, 'remove_field' ) );

			add_action( 'wp_ajax_um_update_builder', array( &$this, 'update_builder' ) );
			add_action( 'wp_ajax_um_update_field', array( &$this, 'update_field' ) );

			add_action( 'wp_ajax_um_get_icons', array( $this, 'get_icons' ) );

			add_action( 'wp_ajax_um_update_order', array( $this, 'update_order' ) );

			add_action( 'wp_ajax_um_populate_dropdown_options', array( $this, 'populate_dropdown_options' ) );
		}


		/**
		 *  Retrieves dropdown/multi-select options from a callback function
		 */
		function populate_dropdown_options() {
			UM()->ajax()->check_nonce( 'um-admin-nonce' );

			if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( __( 'This is not possible for security reasons.', 'ultimate-member' ) );
			}

			$arr_options = array();

			$um_callback_func = sanitize_key( $_POST['um_option_callback'] );
			if ( empty( $um_callback_func ) ) {
				$arr_options['status'] = 'empty';
				$arr_options['function_name'] = $um_callback_func;
				$arr_options['function_exists'] = function_exists( $um_callback_func );
			}

			$arr_options['data'] = array();
			if ( function_exists( $um_callback_func ) ) {
				$arr_options['data'] = call_user_func( $um_callback_func );
			}

			wp_send_json( $arr_options );
		}


		/**
		 * Update order of fields
		 */
		public function update_order() {
			UM()->ajax()->check_nonce( 'um-admin-nonce' );

			if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( __( 'Please login as administrator', 'ultimate-member' ) );
			}

			/**
			 * @var $form_id
			 */
			extract( $_POST );

			if ( isset( $form_id ) ) {
				$form_id = absint( $form_id );
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

					if ( strstr( $_POST[ '_um_rowcols_' . $row_id . '_cols' ], ':' ) ) {
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
		}


		function get_icons() {
			UM()->ajax()->check_nonce( 'um-admin-nonce' );

			$search_request = ! empty( $_REQUEST['search'] ) ? sanitize_text_field( $_REQUEST['search'] ) : '';
			$page           = ! empty( $_REQUEST['page'] ) ? absint( $_REQUEST['page'] ) : 1;
			$per_page       = 50;

			UM()->install()->set_icons_options();

			$um_icons_list = get_option( 'um_icons_list' );
			if ( ! empty( $search_request ) ) {
				$um_icons_list = array_filter( $um_icons_list, function( $item ) use ( $search_request ) {
					$result = array_filter( $item['search'], function( $search_item ) use ( $search_request ) {
						return stripos( $search_item, $search_request ) !== false;
					});
					return count( $result ) > 0;
				});
			}

			$total_count = count( $um_icons_list );

			$um_icons_list = array_slice( $um_icons_list, $per_page * ( $page - 1 ), $per_page );

			wp_send_json_success(
				array(
					'icons'       => $um_icons_list,
					'total_count' => $total_count,
				)
			);
		}



		function edit_field_popup() {
			UM()->ajax()->check_nonce( 'um-admin-nonce' );

			if ( empty( $_REQUEST['field_type'] ) ) {
				wp_send_json_error( __( 'Invalid field type.', 'ultimate-member' ) );
			}

			if ( empty( $_REQUEST['form_id'] ) ) {
				wp_send_json_error( __( 'Invalid form ID.', 'ultimate-member' ) );
			}

			if ( empty( $_REQUEST['field_key'] ) ) {
				wp_send_json_error( __( 'Invalid field key.', 'ultimate-member' ) );
			}

			$field_type = sanitize_key( $_REQUEST['field_type'] );
			$form_id    = absint( $_REQUEST['form_id'] );
			$field_key  = sanitize_text_field( $_REQUEST['field_key'] );

			$metabox = UM()->admin()->metabox();

			$form_fields = UM()->query()->get_attr( 'custom_fields', $form_id );

			$metabox->set_field_type = $field_type;
			$metabox->in_edit        = true;
			$metabox->edit_array     = $form_fields[ $field_key ];

			if ( ! isset( $metabox->edit_array['metakey'] ) ) {
				$metabox->edit_array['metakey'] = $metabox->edit_array['id'];
			}

			if ( ! isset( $metabox->edit_array['position'] ) ) {
				$metabox->edit_array['position'] = $metabox->edit_array['id'];
			}

			$args = UM()->builtin()->get_core_field_attrs( $field_type );

			/**
			 * @var array $col1
			 * @var array $col2
			 * @var array $col3
			 * @var array $col_full
			 */
			extract( $args );

			ob_start();

			if ( ! isset( $col1 ) ) {

				echo '<p>' . esc_html__( 'This field type is not setup correcty.', 'ultimate-member' ) . '</p>';

			} else {

				?>

				<form action="" method="post" class="um_add_field um_edit_field um-admin-metabox">
					<div class="um-admin-modal-form-inner">
					<input type="hidden" name="_in_row" id="_in_row" value="<?php echo $metabox->edit_array['in_row']; ?>" />
					<input type="hidden" name="_in_sub_row" id="_in_sub_row" value="<?php echo $metabox->edit_array['in_sub_row']; ?>" />
					<input type="hidden" name="_in_column" id="_in_column" value="<?php echo $metabox->edit_array['in_column']; ?>" />
					<input type="hidden" name="_in_group" id="_in_group" value="<?php echo $metabox->edit_array['in_group']; ?>" />

					<input type="hidden" name="_type" id="_type" value="<?php echo esc_attr( $field_type ); ?>" />

					<input type="hidden" name="post_id" id="post_id" value="<?php echo esc_attr( $form_id ); ?>" />

					<input type="hidden" name="edit_mode" id="edit_mode" value="true" />

					<input type="hidden" name="_metakey" id="_metakey" value="<?php echo $metabox->edit_array['metakey']; ?>" />

					<input type="hidden" name="_position" id="_position" value="<?php echo $metabox->edit_array['position']; ?>" />

					<?php if ( isset( $args['mce_content'] ) ) { ?>
						<div class="dynamic-mce-content"><?php echo ! empty( $metabox->edit_array['content'] ) ? $metabox->edit_array['content'] : ''; ?></div>
					<?php } ?>

					<?php UM()->builder()->modal_header(); ?>

					<div class="um-admin-half">

						<?php if ( isset( $col1 ) ) {
							foreach ( $col1 as $opt ) {
								$metabox->field_input( $opt, null, $metabox->edit_array );
							}
						} ?>

					</div>

					<div class="um-admin-half um-admin-right">

						<?php if ( isset( $col2 ) ) {
							foreach ( $col2 as $opt ) {
								$metabox->field_input( $opt, null, $metabox->edit_array );
							}
						} ?>

					</div>

					<div class="um-admin-clear"></div>

					<?php if ( isset( $col3 ) ) {
						foreach ( $col3 as $opt ) {
							$metabox->field_input( $opt, null, $metabox->edit_array );
						}
					} ?>

					<div class="um-admin-clear"></div>

					<?php if ( isset( $col_full ) ) {
						foreach ( $col_full as $opt ) {
							$metabox->field_input( $opt, null, $metabox->edit_array );
						}
					} ?>

					<?php UM()->builder()->modal_footer( $form_id, $args, $metabox ); ?>

					</div>

					<div class="um-admin-modal-foot">
						<input type="submit" value="<?php esc_attr_e( 'Update', 'ultimate-member' ); ?>" class="button-primary" />
						<input type="hidden" name="action" value="um_update_field" />
						<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'um-admin-nonce' ) ) ?>" />
						<a href="javascript:void(0);" class="button um-admin-modal-close">
							<?php esc_html_e( 'Cancel', 'ultimate-member' ); ?>
						</a>
					</div>

				</form>

				<?php
			}

			$output = ob_get_clean();

			wp_send_json_success( $output );
		}


		function preview_form() {
			UM()->ajax()->check_nonce( 'um-admin-nonce' );

			if ( empty( $_REQUEST['form_id'] ) ) {
				wp_send_json_error( __( 'Invalid form ID.', 'ultimate-member' ) );
			}

			$form_id = absint( $_REQUEST['form_id'] );

			UM()->user()->preview = true;

			$mode = UM()->query()->get_attr('mode', $form_id );

			if ( 'profile' === $mode ) {
				UM()->fields()->editing = true;
			}

			ob_start();
			?>

			<div class="um-admin-preview-inner">

			<div class="um-admin-preview-overlay"></div>

			<?php if ( version_compare( get_bloginfo('version'),'5.4', '<' ) ) {
				echo do_shortcode('[ultimatemember form_id="' . esc_attr( $form_id ) . '" /]');
			} else {
				echo apply_shortcodes('[ultimatemember form_id="' . esc_attr( $form_id ) . '" /]');
			} ?>

			</div>

			<div class="um-admin-modal-foot">
				<a href="javascript:void(0);" class="button-primary um-admin-modal-close">
					<?php esc_html_e( 'Continue editing', 'ultimate-member' ); ?>
				</a>
			</div>

			<?php
			$output = ob_get_clean();

			wp_send_json_success( $output );
		}


		function new_field_popup() {
			UM()->ajax()->check_nonce( 'um-admin-nonce' );

			if ( empty( $_REQUEST['field_type'] ) ) {
				wp_send_json_error( __( 'Invalid field type.', 'ultimate-member' ) );
			}

			if ( empty( $_REQUEST['form_id'] ) ) {
				wp_send_json_error( __( 'Invalid form ID.', 'ultimate-member' ) );
			}

			$field_type = sanitize_key( $_REQUEST['field_type'] );
			$form_id    = absint( $_REQUEST['form_id'] );

			$in_row     = ! empty( $_REQUEST['in_row'] ) ? absint( $_REQUEST['in_row'] ) : 0;
			$in_sub_row = ! empty( $_REQUEST['in_sub_row'] ) ? absint( $_REQUEST['in_sub_row'] ) : 0;
			$in_column  = ! empty( $_REQUEST['in_column'] ) ? absint( $_REQUEST['in_column'] ) : 1;
			$in_group   = ! empty( $_REQUEST['in_group'] ) ? sanitize_key( $_REQUEST['in_group'] ) : '';

			$metabox                 = UM()->admin()->metabox();
			$metabox->set_field_type = $field_type;

			$args = UM()->builtin()->get_core_field_attrs( $field_type );

			/**
			 * @var array $col1
			 * @var array $col2
			 * @var array $col3
			 * @var array $col_full
			 */
			extract( $args );

			ob_start();

			if ( ! isset( $col1 ) ) {

				echo '<p>' . esc_html__( 'This field type is not setup correctly.', 'ultimate-member' ) . '</p>';

			} else {
				?>

				<form action="" method="post" class="um_add_field um-admin-metabox">

					<div class="um-admin-modal-form-inner">
					<input type="hidden" name="_in_row" id="_in_row" value="_um_row_<?php echo esc_attr( $in_row + 1 ); ?>" />
					<input type="hidden" name="_in_sub_row" id="_in_sub_row" value="<?php echo esc_attr( $in_sub_row ); ?>" />
					<input type="hidden" name="_in_column" id="_in_column" value="<?php echo esc_attr( $in_column ); ?>" />
					<input type="hidden" name="_in_group" id="_in_group" value="<?php echo esc_attr( $in_group ); ?>" />

					<input type="hidden" name="_type" id="_type" value="<?php echo esc_attr( $field_type ); ?>" />
					<input type="hidden" name="post_id" id="post_id" value="<?php echo esc_attr( $form_id ); ?>" />

					<?php UM()->builder()->modal_header(); ?>

					<?php
					// future feature
					if ( isset( $tabs ) ) {
						?>
						<ul class="um-modal-tabs">
							<?php
							$active = false;
							$i      = 1;
							foreach ( $tabs as $key => $tab ) { ?>
								<li class="um-modal-tab<?php echo ! $active ? ' active' : '' ?>">
									<a href="javascript:void(0);" data-key="<?php echo esc_attr( $key ); ?>"><?php echo $metabox->tab_label( $tab ); ?></a>
									<?php echo ( $i < count( $tabs ) ) ? ' | ' : ''; ?>
								</li>

								<?php $active = ! $active ? $key : $active;
								$i++;
							}
							?>
						</ul>
						<div class="um-modal-tabs-content-wrapper">
							<?php
							foreach ( $tabs as $key => $tab ) {
								$classes = [
									'um-modal-tab-content',
									'um-modal-tab-' . $key
								];

								if ( $active == $key ) {
									$classes[] = 'active';
								}
								?>

								<div class="<?php echo esc_attr( implode( ' ', $classes ) ) ?>">
									<?php echo $metabox->tab_content( $tab ); ?>
								</div>

							<?php } ?>
						</div>
					<?php } ?>

					<div class="um-admin-half">
						<?php
						if ( isset( $col1 ) ) {
							foreach ( $col1 as $opt ) {
								$metabox->field_input( $opt );
							}
						}
						?>
					</div>

					<div class="um-admin-half um-admin-right">
						<?php
						if ( isset( $col2 ) ) {
							foreach ( $col2 as $opt ) {
								$metabox->field_input( $opt );
							}
						}
						?>
					</div>

					<div class="um-admin-clear"></div>

					<?php
					if ( isset( $col3 ) ) {
						foreach ( $col3 as $opt ) {
							$metabox->field_input( $opt );
						}
					}
					?>

					<div class="um-admin-clear"></div>

					<?php
					if ( isset( $col_full ) ) {
						foreach ( $col_full as $opt ) {
							$metabox->field_input( $opt );
						}
					}

					UM()->builder()->modal_footer( $form_id, $args, $metabox );
					?>

					</div>
					<div class="um-admin-modal-foot">
						<input type="submit" value="<?php esc_attr_e( 'Add', 'ultimate-member' ); ?>" class="button-primary" />
						<input type="hidden" name="action" value="um_update_field" />
						<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'um-admin-nonce' ) ) ?>" />
						<a href="javascript:void(0);" class="button um-admin-modal-close">
							<?php esc_html_e( 'Cancel', 'ultimate-member' ); ?>
						</a>
					</div>

				</form>


				<?php
			}

			$output = ob_get_clean();

			wp_send_json_success( $output );
		}


		function add_field_from_list() {
			UM()->ajax()->check_nonce( 'um-admin-nonce' );

			if ( empty( $_REQUEST['field_key'] ) ) {
				wp_send_json_error( __( 'Invalid field key.', 'ultimate-member' ) );
			}

			if ( empty( $_REQUEST['form_id'] ) ) {
				wp_send_json_error( __( 'Invalid form ID.', 'ultimate-member' ) );
			}

			$key     = sanitize_text_field( $_REQUEST['field_key'] );
			$form_id = absint( $_REQUEST['form_id'] );

			$in_row   = ! empty( $_REQUEST['in_row'] ) ? absint( $_REQUEST['in_row'] ) : 0;
			$position = array(
				'in_row'     => '_um_row_' . ( $in_row + 1 ),
				'in_sub_row' => ! empty( $_REQUEST['in_sub_row'] ) ? absint( $_REQUEST['in_sub_row'] ) : 0,
				'in_column'  => ! empty( $_REQUEST['in_column'] ) ? absint( $_REQUEST['in_column'] ) : 1,
				'in_group'   => ! empty( $_REQUEST['in_group'] ) ? sanitize_key( $_REQUEST['in_group'] ) : '',
			);

			$result = $this->add_from_list( $key, $form_id, $position );

			if ( $result ) {
				wp_send_json_success();
			} else {
				wp_send_json_error( __( 'Cannot add a field.', 'ultimate-member' ) );
			}
		}


		function add_field_from_predefined() {
			UM()->ajax()->check_nonce( 'um-admin-nonce' );

			if ( empty( $_REQUEST['field_key'] ) ) {
				wp_send_json_error( __( 'Invalid field key.', 'ultimate-member' ) );
			}

			if ( empty( $_REQUEST['form_id'] ) ) {
				wp_send_json_error( __( 'Invalid form ID.', 'ultimate-member' ) );
			}

			$key     = sanitize_text_field( $_REQUEST['field_key'] );
			$form_id = absint( $_REQUEST['form_id'] );

			$in_row   = ! empty( $_REQUEST['in_row'] ) ? absint( $_REQUEST['in_row'] ) : 0;
			$position = array(
				'in_row'     => '_um_row_' . ( $in_row + 1 ),
				'in_sub_row' => ! empty( $_REQUEST['in_sub_row'] ) ? absint( $_REQUEST['in_sub_row'] ) : 0,
				'in_column'  => ! empty( $_REQUEST['in_column'] ) ? absint( $_REQUEST['in_column'] ) : 1,
				'in_group'   => ! empty( $_REQUEST['in_group'] ) ? sanitize_key( $_REQUEST['in_group'] ) : '',
			);

			$result = $this->add_from_predefined( $key, $form_id, $position );
			if ( $result ) {
				wp_send_json_success();
			} else {
				wp_send_json_error( __( 'Cannot add a field.', 'ultimate-member' ) );
			}
		}


		/**
		 * AJAX handler for field removing from the form
		 */
		function remove_field() {
			UM()->ajax()->check_nonce( 'um-admin-nonce' );

			if ( empty( $_REQUEST['field_key'] ) ) {
				wp_send_json_error( __( 'Invalid field key.', 'ultimate-member' ) );
			}

			if ( empty( $_REQUEST['form_id'] ) ) {
				wp_send_json_error( __( 'Invalid form ID.', 'ultimate-member' ) );
			}

			$key     = sanitize_text_field( $_REQUEST['field_key'] );
			$form_id = absint( $_REQUEST['form_id'] );

			$result = UM()->common()->field()->delete_from_form( $key, $form_id );
			if ( $result ) {
				wp_send_json_success();
			} else {
				wp_send_json_error( __( 'Cannot delete custom field from a form.', 'ultimate-member' ) );
			}
		}


		/**
		 * AJAX handler for duplicate field process
		 */
		function duplicate_field() {
			UM()->ajax()->check_nonce( 'um-admin-nonce' );

			if ( empty( $_REQUEST['field_key'] ) ) {
				wp_send_json_error( __( 'Invalid field key.', 'ultimate-member' ) );
			}

			if ( empty( $_REQUEST['form_id'] ) ) {
				wp_send_json_error( __( 'Invalid form ID.', 'ultimate-member' ) );
			}

			$key     = sanitize_text_field( $_REQUEST['field_key'] );
			$form_id = absint( $_REQUEST['form_id'] );

			UM()->common()->field()->duplicate( $key, $form_id );
		}


		/**
		 * AJAX handler for delete custom field process
		 */
		function remove_field_global() {
			UM()->ajax()->check_nonce( 'um-admin-nonce' );

			if ( empty( $_REQUEST['field_key'] ) ) {
				wp_send_json_error( __( 'Invalid field key.', 'ultimate-member' ) );
			}

			$key = sanitize_text_field( $_REQUEST['field_key'] );

			$result = UM()->common()->field()->delete_permanently( $key );
			if ( $result ) {
				wp_send_json_success();
			} else {
				wp_send_json_error( __( 'Cannot delete custom field.', 'ultimate-member' ) );
			}
		}


		/**
		 *
		 */
		function admin_fields_list() {
			UM()->ajax()->check_nonce( 'um-admin-nonce' );

			if ( empty( $_REQUEST['form_id'] ) ) {
				wp_send_json_error( __( 'Invalid form ID.', 'ultimate-member' ) );
			}

			$form_id = absint( $_REQUEST['form_id'] );

			ob_start();
			$form_fields = UM()->query()->get_attr( 'custom_fields', $form_id );
			$form_fields = array_values( array_filter( array_keys( $form_fields ) ) );
			?>

			<h4><?php esc_html_e( 'Setup New Field', 'ultimate-member' ); ?></h4>

			<div class="um-admin-btns">
				<?php
				if ( UM()->builtin()->core_fields ) {
					foreach ( UM()->builtin()->core_fields as $field_type => $array ) {
						if ( isset( $array['in_fields'] ) && $array['in_fields'] == false ) {
							continue;
						}
						?>

						<a href="javascript:void(0);" class="button um_admin_new_field_popup" data-field_type="<?php echo esc_attr( $field_type ); ?>" data-form_id="<?php echo esc_attr( $form_id ); ?>">
							<?php echo esc_html( $array['name'] ); ?>
						</a>

						<?php
					}
				}
				?>
			</div>

			<h4><?php esc_html_e('Predefined Fields','ultimate-member'); ?></h4>

			<div class="um-admin-btns">

				<?php
				if ( UM()->builtin()->predefined_fields ) {
					foreach ( UM()->builtin()->predefined_fields as $field_key => $array ) {
						if ( isset( $array['account_only'] ) || isset( $array['private_use'] ) ) {
							continue;
						}
						?>

						<a href="javascript:void(0);" class="button um_admin_add_field_from_predefined" <?php disabled( in_array( $field_key, $form_fields, true ) ) ?> data-field_key="<?php echo esc_attr( $field_key ); ?>" data-form_id="<?php echo esc_attr( $form_id ); ?>">
							<?php echo um_trim_string( stripslashes( $array['title'] ), 20 ); ?>
						</a>

						<?php
					}
				} else {
					echo '<p>' . esc_html__( 'None', 'ultimate-member' ) . '</p>';
				}
				?>

			</div>

			<h4><?php esc_html_e( 'Custom Fields', 'ultimate-member' ); ?></h4>

			<div class="um-admin-btns">

				<?php
				if ( UM()->builtin()->custom_fields ) {
					foreach ( UM()->builtin()->custom_fields as $field_key => $array ) {
						if ( empty( $array['title'] ) || empty( $array['type'] ) ) {
							continue;
						}
						?>

						<a href="javascript:void(0);" class="button with-icon um_admin_add_field_from_list" <?php disabled( in_array( $field_key, $form_fields, true ) ) ?> data-field_key="<?php echo esc_attr( $field_key ); ?>" data-form_id="<?php echo esc_attr( $form_id ); ?>" title="<?php echo esc_attr( sprintf( __( 'Meta Key - %s', 'ultimate-member' ), $field_key ) ); ?>">
							<?php echo um_trim_string( stripslashes( $array['title'] ), 20 ); ?> (<?php echo ucfirst( $array['type'] ); ?>)
							<span class="remove dashicons dashicons-dismiss"></span>
						</a>

						<?php
					}
				}
				?>

				<p class="um-no-custom-fields"<?php if ( UM()->builtin()->custom_fields ) { ?> style="display: none;"<?php } ?>>
					<?php esc_html_e( 'You did not create any custom fields.', 'ultimate-member' ); ?>
				</p>
			</div>

			<?php $output = ob_get_clean();
			wp_send_json_success( $output );
		}


		/**
		 * Quickly adds a field from custom fields
		 *
		 * @param string $field_key
		 * @param int    $form_id
		 * @param array  $position_data
		 *
		 * @return bool
		 */
		function add_from_list( $field_key, $form_id, $position_data = array() ) {
			$fields = UM()->query()->get_attr( 'custom_fields', $form_id );
			$field_scope = UM()->builtin()->saved_fields;

			if ( array_key_exists( $field_key, $fields ) ) {
				return false;
			}

			$fields[ $field_key ] = $field_scope[ $field_key ];

			$position = 1;
			if ( ! empty( $fields ) ) {
				$position = count( $fields ) + 1;
			}
			$fields[ $field_key ]['position'] = $position;

			// set position
			if ( ! empty( $position_data ) ) {
				foreach ( $position_data as $key => $val ) {
					$fields[ $field_key ][ $key ] = $val;
				}
			}

			// add field to form
			UM()->query()->update_attr( 'custom_fields', $form_id, $fields );

			return true;
		}


		/**
		 * Quickly adds a field from pre-defined fields
		 *
		 * @param string $field_key
		 * @param int    $form_id
		 * @param array  $position_data
		 *
		 * @return bool
		 */
		function add_from_predefined( $field_key, $form_id, $position_data = array() ) {
			$fields = UM()->query()->get_attr( 'custom_fields', $form_id );
			$field_scope = UM()->builtin()->predefined_fields;

			if ( array_key_exists( $field_key, $fields ) ) {
				return false;
			}

			$fields[ $field_key ] = $field_scope[ $field_key ];

			$position = 1;
			if ( ! empty( $fields ) ) {
				$position = count( $fields ) + 1;
			}
			$fields[ $field_key ]['position'] = $position;

			// set position
			if ( ! empty( $position_data ) ) {
				foreach ( $position_data as $key => $val ) {
					$fields[ $field_key ][ $key ] = $val;
				}
			}

			// add field to form
			UM()->query()->update_attr( 'custom_fields', $form_id, $fields );

			return true;
		}


		/**
		 * Update the builder area
		 */
		function update_builder() {
			UM()->ajax()->check_nonce( 'um-admin-nonce' );

			if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( __( 'Please login as administrator', 'ultimate-member' ) );
			}

			ob_start();

			UM()->builder()->form_id = absint( $_POST['form_id'] );

			UM()->builder()->show_builder();

			$output = ob_get_clean();

			if ( is_array( $output ) ) {
				print_r( $output );
			} else {
				echo $output;
			}
			die;
		}


		/**
		 *
		 */
		function update_field() {
			UM()->ajax()->check_nonce( 'um-admin-nonce' );

			if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( __( 'Please login as administrator', 'ultimate-member' ) );
			}

			$output['error'] = null;

			$array = array(
				'field_type' => sanitize_key( $_POST['_type'] ),
				'form_id'    => absint( $_POST['post_id'] ),
				'args'       => UM()->builtin()->get_core_field_attrs( sanitize_key( $_POST['_type'] ) ),
				'post'       => UM()->admin()->sanitize_builder_field_meta( $_POST ),
			);

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_admin_pre_save_fields_hook
			 * @description Filter field data before save
			 * @input_vars
			 * [{"var":"$array","type":"array","desc":"Save Field data"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_filter( 'um_admin_pre_save_fields_hook', 'function_name', 10, 1 );
			 * @example
			 * <?php
			 * add_filter( 'um_admin_pre_save_fields_hook', 'my_admin_pre_save_fields', 10, 1 );
			 * function my_admin_pre_save_fields( $array ) {
			 *     // your code here
			 *     return $array;
			 * }
			 * ?>
			 */
			$array = apply_filters( 'um_admin_pre_save_fields_hook', $array );

			/**
			 * UM hook
			 *
			 * @type filter
			 * @title um_admin_field_update_error_handling
			 * @description Change error string on save field
			 * @input_vars
			 * [{"var":"$error","type":"string","desc":"Error String"},
			 * {"var":"$array","type":"array","desc":"Save Field data"}]
			 * @change_log
			 * ["Since: 2.0"]
			 * @usage add_filter( 'um_admin_field_update_error_handling', 'function_name', 10, 2 );
			 * @example
			 * <?php
			 * add_filter( 'um_admin_field_update_error_handling', 'my_admin_field_update_error', 10, 2 );
			 * function my_admin_field_update_error( $error, $array ) {
			 *     // your code here
			 *     return $error;
			 * }
			 * ?>
			 */
			$output['error'] = apply_filters( 'um_admin_field_update_error_handling', $output['error'], $array );

			/**
			 * @var $_metakey
			 * @var $post_id
			 */
			extract( $array['post'] );

			if ( empty( $output['error'] ) ) {

				$save = array();
				$save[ $_metakey ] = null;
				foreach ( $array['post'] as $key => $val ) {

					if ( substr( $key, 0, 1 ) === '_' && $val !== '' ) { // field attribute
						$new_key = ltrim ( $key, '_' );

						if ( $new_key == 'options' ) {
							//$save[ $_metakey ][$new_key] = explode(PHP_EOL, $val);
							$save[ $_metakey ][ $new_key ] = preg_split( '/[\r\n]+/', $val, -1, PREG_SPLIT_NO_EMPTY );
						} else {
							$save[ $_metakey ][ $new_key ] = $val;
						}

					} elseif ( strstr( $key, 'um_editor' ) ) {

						if ( 'block' === $array['post']['_type'] ) {
							$save[ $_metakey ]['content'] = wp_kses_post( $val );
						} else {
							$save[ $_metakey ]['content'] = sanitize_textarea_field( $val );
						}
					}

				}

				$field_ID = $_metakey;
				$field_args = $save[ $_metakey ];

				/**
				 * UM hook
				 *
				 * @type filter
				 * @title um_admin_pre_save_field_to_form
				 * @description Change field options before save to form
				 * @input_vars
				 * [{"var":"$field_args","type":"array","desc":"Field Options"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage add_filter( 'um_admin_pre_save_field_to_form', 'function_name', 10, 1 );
				 * @example
				 * <?php
				 * add_filter( 'um_admin_pre_save_field_to_form', 'my_admin_pre_save_field_to_form', 10, 1 );
				 * function my_admin_pre_save_field_to_form( $field_args ) {
				 *     // your code here
				 *     return $field_args;
				 * }
				 * ?>
				 */
				$field_args = apply_filters( 'um_admin_pre_save_field_to_form', $field_args );

				UM()->fields()->update_field( $field_ID, $field_args, $post_id );

				/**
				 * UM hook
				 *
				 * @type filter
				 * @title um_admin_pre_save_field_to_db
				 * @description Change field options before save to DB
				 * @input_vars
				 * [{"var":"$field_args","type":"array","desc":"Field Options"}]
				 * @change_log
				 * ["Since: 2.0"]
				 * @usage add_filter( 'um_admin_pre_save_field_to_db', 'function_name', 10, 1 );
				 * @example
				 * <?php
				 * add_filter( 'um_admin_pre_save_field_to_db', 'my_admin_pre_save_field_to_db', 10, 1 );
				 * function my_admin_pre_save_field_to_form( $field_args ) {
				 *     // your code here
				 *     return $field_args;
				 * }
				 * ?>
				 */
				$field_args = apply_filters( 'um_admin_pre_save_field_to_db', $field_args );

				if ( ! isset( $array['args']['form_only'] ) ) {
					if ( ! isset( UM()->builtin()->predefined_fields[ $field_ID ] ) ) {
						UM()->fields()->globally_update_field( $field_ID, $field_args );
					}
				}

			}

			$output = json_encode( $output );
			if ( is_array( $output ) ) {
				print_r( $output );
			} else {
				echo $output;
			}
			die;
		}
	}
}
