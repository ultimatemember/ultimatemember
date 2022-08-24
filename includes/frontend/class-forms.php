<?php
namespace um\frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! class_exists( 'um\frontend\Forms' ) ) {


	/**
	 * Class Forms
	 *
	 * @package um\frontend
	 */
	class Forms {

		/**
		 * Forms constructor.
		 */
		function __construct() {
			//add_action( 'wp_footer', array( &$this, 'um_add_form_honeypot_js' ), 99999999999999999 );
		}

		/**
		 * Makes the honeypot value empty
		 */
		function um_add_form_honeypot_js() {
			?>
			<script type="text/javascript">
				jQuery( window ).on( 'load', function() {
					jQuery('input[name="<?php echo esc_js( UM()->honeypot ); ?>"]').val('');
				});
			</script>
			<?php
		}
	}
}
