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

$toggle_show  = ! empty( $args['use_gdpr_toggle_show'] ) ? $args['use_gdpr_toggle_show'] : __( 'Show privacy policy', 'ultimate-member' );
$toggle_hide  = ! empty( $args['use_gdpr_toggle_hide'] ) ? $args['use_gdpr_toggle_hide'] : __( 'Hide privacy policy', 'ultimate-member' );
$toggle_title = ! empty( $args['use_gdpr_toggle_show'] ) ? $args['use_gdpr_toggle_show'] : __( 'Show privacy policy', 'ultimate-member' );
$confirm      = ! empty( $args['use_gdpr_agreement'] ) ? $args['use_gdpr_agreement'] : __( 'Please confirm that you agree to our privacy policy', 'ultimate-member' );

$error_message = ! empty( $args['use_gdpr_error_text'] ) ? $args['use_gdpr_error_text'] : __( 'Please confirm your acceptance of our privacy policy', 'ultimate-member' );
?>
<div class="um-form-rows um-gdpr-row">
	<div class="um-form-row">
		<div class="um-form-cols um-form-cols-1">
			<div class="um-form-col um-form-col-1">
				<div class="um-field um-field-block um-field-type_block" data-key="um_block_gdpr_details">
					<?php
					if ( ! empty( $args['use_gdpr_content_id'] ) ) {
						$um_content_query = get_post( $args['use_gdpr_content_id'] );
						if ( ! empty( $um_content_query ) && ! is_wp_error( $um_content_query ) ) {
							echo wp_kses(
								UM()->frontend()::layouts()::link(
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
								),
								UM()->get_allowed_html( 'templates' )
							);
							?>
							<div class="um-gdpr-post-content-wrap um-toggle-block um-toggle-block-collapsed">
								<div class="um-gdpr-post-content-inner um-toggle-block-inner">
									<div class="um-gdpr-post-content">
										<?php
										$content = apply_filters( 'um_gdpr_policies_page_content', $um_content_query->post_content, $args );
										echo wp_kses( apply_filters( 'the_content', $content, $um_content_query->ID ), UM()->get_allowed_html( 'templates' ) );
										?>
									</div>
								</div>
							</div>
							<?php
						}
					}
					echo wp_kses(
						UM()->frontend()::layouts()::link(
							$toggle_show,
							array(
								'type'    => 'raw',
								'size'    => 's',
								'design'  => 'primary',
								'title'   => $toggle_show,
								'classes' => array(
									'um-hide-gdpr',
									'um-hide-gdpr-second-button',
									'um-display-none',
								),
								'data'    => array(
									'toggle-text' => $toggle_hide,
									'um-toggle'   => '.um-gdpr-post-content-wrap',
								),
							)
						),
						UM()->get_allowed_html( 'templates' )
					);
					?>
				</div>
				<div id="um_field_<?php echo esc_attr( $args['form_id'] ); ?>_use_gdpr_agreement" class="um-field um-field-bool um-field-gdpr_agreement um-field-type_bool" data-key="use_gdpr_agreement">
					<input type="hidden" name="use_gdpr_agreement" value="0">
					<div class="um-field-checkbox-area">
						<label class="um-checkbox-label um-size-md">
							<input name="use_gdpr_agreement" type="checkbox" value="1"><?php echo esc_html( $confirm ); ?>
						</label>
					</div>
					<?php if ( isset( UM()->form()->errors['use_gdpr_agreement'] ) ) { ?>
						<p class="um-field-hint um-field-error" id="um-error-for-use_gdpr_agreement"><?php echo esc_html( $error_message ); ?></p>
					<?php } ?>
				</div>
			</div>
		</div>
	</div>
</div>
