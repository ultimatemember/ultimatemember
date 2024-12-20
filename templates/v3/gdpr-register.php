<?php
/**
 * Template for the GDPR checkbox in register form
 *
 * This template can be overridden by copying it to your-theme/ultimate-member/templates/gdpr-register.php
 *
 * Page: "Register"
 * Call: function display_option()
 *
 * @version 3.0.0
 *
 * @var array  $args
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="um-form-rows um-gdpr-row">
	<div class="um-form-row">
		<div class="um-form-cols um-form-cols-1">
			<div class="um-form-col um-form-col-1">
				<?php
				$fields = UM()->builtin()->get_specific_fields( 'gdpr_details,use_gdpr_agreement' );

				$output = null;
				foreach ( $fields as $key => $data ) {
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
								echo wp_kses( $button, UM()->get_allowed_html( 'templates' ) );
								?>
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
								$data['content'] = ob_get_clean();
							}
						}
					} elseif ( 'use_gdpr_agreement' === $key ) {
						$confirm = ! empty( $args['use_gdpr_agreement'] ) ? $args['use_gdpr_agreement'] : __( 'Please confirm that you agree to our privacy policy', 'ultimate-member' );

						$data['checkbox_label'] = $confirm;
					}
					$output .= UM()->fields()->edit_field( $key, $data );
				}
				echo $output;
				?>
			</div>
		</div>
	</div>
</div>
