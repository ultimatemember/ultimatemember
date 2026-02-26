<?php
/**
 * Template for the message after registration process
 *
 * This template can be overridden by copying it to your-theme/ultimate-member/templates/message.php
 *
 * Call: function parse_shortcode_args()
 *
 * @version 2.6.1
 *
 * @var string $mode
 * @var int    $form_id
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} ?>

<div class="um <?php echo esc_attr( $this->get_class( $mode ) ); ?> um-<?php echo esc_attr( $form_id ); ?>">

	<div class="um-postmessage">
		<?php
		// The registration message comes from role/form settings and is output with safe HTML escaping.
		// Allows basic HTML formatting while preventing XSS attacks.
		echo wp_kses_post( $this->custom_message ); ?>
	</div>

</div>
