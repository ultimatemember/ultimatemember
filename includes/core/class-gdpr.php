<?php
namespace um\core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\core\GDPR' ) ) {

	/**
	 * Class Admin_GDPR
	 * @package um\core
	 */
	class GDPR {

		/**
		 * Admin_GDPR constructor.
		 */
		public function __construct() {
			add_action( 'um_submit_form_register', array( &$this, 'agreement_validation' ), 9, 2 );

			add_filter( 'um_whitelisted_metakeys', array( &$this, 'extend_whitelisted' ), 10, 2 );

			add_filter( 'um_before_save_filter_submitted', array( &$this, 'add_agreement_date' ) );
			add_action( 'um_after_form_fields', array( &$this, 'display_option' ) );

			add_filter( 'um_get_form_fields', array( &$this, 'extends_fields' ), 100, 2 );
		}

		/**
		 * Extends fields on the registration form
		 *
		 * @param array $fields
		 * @param int   $form_id
		 *
		 * @return array
		 */
		public function extends_fields( $fields, $form_id ) {
			if ( ! UM()->is_new_ui() ) {
				return $fields;
			}

			$mode = UM()->query()->get_attr( 'mode', $form_id );
			if ( 'register' === $mode ) {
				$args = UM()->query()->post_data( $form_id );
				if ( ! empty( $args['use_gdpr'] ) ) {
					$gdpr_row_key = 'um-gdpr-row';

					$fields[ $gdpr_row_key ] = array(
						'type'     => 'row',
						'id'       => $gdpr_row_key,
						'sub_rows' => 1,
						'cols'     => 1,
						'origin'   => $gdpr_row_key,
					);

					$gdpr_fields = UM()->builtin()->get_specific_fields( 'gdpr_details,use_gdpr_agreement' );
					foreach ( $gdpr_fields as $key => $data ) {
						if ( 'gdpr_details' === $key ) {
							if ( ! empty( $args['use_gdpr_content_id'] ) ) {
								$um_content_query = get_post( $args['use_gdpr_content_id'] );
								if ( ! empty( $um_content_query ) && ! is_wp_error( $um_content_query ) ) {
									$toggle_show = ! empty( $args['use_gdpr_toggle_show'] ) ? $args['use_gdpr_toggle_show'] : __( 'Show privacy policy', 'ultimate-member' );
									$toggle_hide = ! empty( $args['use_gdpr_toggle_hide'] ) ? $args['use_gdpr_toggle_hide'] : __( 'Hide privacy policy', 'ultimate-member' );

									$button = UM()->frontend()::layouts()::link(
										$toggle_show,
										array(
											'type'    => 'raw',
											'size'    => 's',
											'design'  => 'primary',
											'title'   => $toggle_show,
											'classes' => array(
												'um-hide-gdpr',
											),
											'data'    => array(
												'toggle-text' => $toggle_hide,
												'um-toggle'   => '.um-gdpr-post-content-wrap',
											),
										)
									);
									ob_start();
									?>
									<span class="um-gdpr-toggle-link-wrapper"><?php echo wp_kses( $button, UM()->get_allowed_html( 'templates' ) ); ?></span>
									<div class="um-gdpr-post-content-wrap um-toggle-block um-toggle-block-collapsed">
										<div class="um-gdpr-post-content-inner um-toggle-block-inner">
											<div class="um-gdpr-post-content">
												<?php
												$content = apply_filters( 'um_gdpr_policies_page_content', $um_content_query->post_content, $args );
												echo wp_kses( apply_filters( 'the_content', $content, $um_content_query->ID ), UM()->get_allowed_html( 'templates' ) );
												?>
											</div>
											<?php echo wp_kses( $button, UM()->get_allowed_html( 'templates' ) ); ?>
										</div>
									</div>
									<?php
									$data['content']    = ob_get_clean();
									$data['in_row']     = $gdpr_row_key;
									$data['in_sub_row'] = '0';
									$data['in_column']  = '1';
									$data['in_group']   = '';
									$data['position']   = 1;

									$fields[ $key ] = $data;
								}
							}
						} elseif ( 'use_gdpr_agreement' === $key ) {
							$confirm = ! empty( $args['use_gdpr_agreement'] ) ? $args['use_gdpr_agreement'] : __( 'Please confirm that you agree to our privacy policy', 'ultimate-member' );

							$data['checkbox_label'] = $confirm;
							$data['in_row']         = $gdpr_row_key;
							$data['in_sub_row']     = '0';
							$data['in_column']      = '1';
							$data['in_group']       = '';
							$data['position']       = 2;

							$fields[ $key ] = $data;
						}
					}
				}
			}

			return $fields;
		}

		/**
		 * @todo Deprecate since new UI is live
		 * @param $args
		 */
		public function display_option( $args ) {
			if ( UM()->is_new_ui() ) {
				return;
			}

			if ( ! empty( $args['use_gdpr'] ) ) {
				UM()->get_template( 'gdpr-register.php', '', array( 'args' => $args ), true );
			}
		}

		/**
		 * @param array $submitted_data
		 * @param array $form_data
		 */
		public function agreement_validation( $submitted_data, $form_data ) {
			$gdpr_enabled        = get_post_meta( $form_data['form_id'], '_um_register_use_gdpr', true );
			$use_gdpr_error_text = get_post_meta( $form_data['form_id'], '_um_register_use_gdpr_error_text', true );
			$use_gdpr_error_text = ! empty( $use_gdpr_error_text ) ? $use_gdpr_error_text : __( 'Please confirm your acceptance of our privacy policy', 'ultimate-member' );

			if ( $gdpr_enabled && empty( $submitted_data['submitted']['use_gdpr_agreement'] ) ) {
				UM()->form()->add_error( 'use_gdpr_agreement', $use_gdpr_error_text );
			}
		}

		/**
		 * @param array $metakeys
		 * @param array $form_data
		 */
		public function extend_whitelisted( $metakeys, $form_data ) {
			$gdpr_enabled = get_post_meta( $form_data['form_id'], '_um_register_use_gdpr', true );
			if ( ! empty( $gdpr_enabled ) ) {
				$metakeys[] = 'use_gdpr_agreement';
			}
			return $metakeys;
		}

		/**
		 * @param $submitted
		 *
		 * @return mixed
		 */
		public function add_agreement_date( $submitted ) {
			if ( isset( $submitted['use_gdpr_agreement'] ) ) {
				$submitted['use_gdpr_agreement'] = current_time( 'mysql', true );
			}

			return $submitted;
		}
	}
}
