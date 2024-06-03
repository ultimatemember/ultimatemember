<?php
namespace um\ajax;

use Elementor\Core\Admin\UI\Components\Button;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Directory
 *
 * @package um\ajax
 */
class Dev {

	/**
	 * Directory constructor.
	 */
	public function __construct() {
		add_action( 'wp_ajax_nopriv_um_get_button_snippet', array( $this, 'get_button_snippet' ) );
		add_action( 'wp_ajax_um_get_button_snippet', array( $this, 'get_button_snippet' ) );
	}

	public function get_button_snippet() {
		if ( empty( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'get_button' ) ) {
			wp_send_json_error( __('Wrong nonce', 'ultimate-member') );
		}

		if ( empty( $_POST['content'] ) ) {
			wp_send_json_error( __('Cannot be empty', 'ultimate-member') );
		}

		$args = wp_parse_args(
			$_POST,
			array(
				'content'       => 'Button',
				'type'          => 'button',
				'icon_position' => null,    // leading || trailing || content
				'icon'          => null,
				'design'        => 'secondary-gray',
				'size'          => 'l',
				'width'         => 'auto',
			)
		);

		$button = UM()->frontend()::layouts()::button( $args['content'], $args );
		ob_start();
		if ( is_null( $args['icon_position'] ) ) {
		?>
<code style="float:left;width: 100%;overflow:auto;border:1px solid #aaa; background: #eee;">
	<pre>
<?php echo esc_html( '<?php' ) . '<br />'; ?>
echo wp_kses(
	UM()->frontend()::layouts()::button(
		'Button',
		array(
<?php if ( 'button' !== $args['type'] ) { ?>
			'type' => '<?php echo $args['type']; ?>',
<?php } ?>
<?php if ( 'l' !== $args['size'] ) { ?>
			'size' => '<?php echo $args['size']; ?>',
<?php } ?>
<?php if ( $args['disabled'] ) { ?>
			'disabled' => true,
<?php } ?>
<?php if ( 'full' === $args['width'] ) { ?>
			'width' => '<?php echo $args['width']; ?>',
<?php } ?>
<?php if ( 'secondary-gray' !== $args['design'] ) { ?>
			'design' => '<?php echo $args['design']; ?>',
<?php } ?>
		)
	),
	UM()->get_allowed_html( 'templates' )
);
<?php echo '?>'; ?>
	</pre>
</code>
		<?php
		} else {
			?>
<code style="float:left;width: 100%;overflow:auto;border:1px solid #aaa; background: #eee;">
	<pre>
<?php echo esc_html( '<?php' ) . '<br />'; ?>
<?php echo ( $args['icon'] || 'content' === $args['icon_position'] ) ? esc_html( '$svg_html = \'SVG html from https://tablericons.com/. Size 20px, Stroke 1.5px\';' ) . '<br />' : ''; ?>
echo wp_kses(
	UM()->frontend()::layouts()::button(
<?php if ( 'content' === $args['icon_position'] ) { ?>
	$svg_html
<?php } else { ?>
	'<?php echo esc_html( $args['content'] ); ?>',
<?php } ?>
		array(
<?php if ( 'button' !== $args['type'] ) { ?>
			'type'          => '<?php echo $args['type']; ?>',
<?php } ?>
<?php if ( 'l' !== $args['size'] ) { ?>
			'size'          => '<?php echo $args['size']; ?>',
<?php } ?>
<?php if ( $args['disabled'] ) { ?>
			'disabled'      => true,
<?php } ?>
<?php if ( 'full' === $args['width'] ) { ?>
			'width'         => '<?php echo $args['width']; ?>',
<?php } ?>
<?php if ( 'secondary-gray' !== $args['design'] ) { ?>
			'design'        => '<?php echo $args['design']; ?>',
<?php } ?>
			'icon_position' => '<?php echo $args['icon_position']; ?>',
<?php if ( $args['icon'] ) { ?>
			'icon'          => $svg_html,
<?php } ?>
		)
	),
	UM()->get_allowed_html( 'templates' )
);
<?php echo '?>'; ?>
	</pre>
</code>
			<?php
		}
		$snippet = ob_get_clean();

		$content = UM()->ajax()->esc_html_spaces( $button ) . $snippet;

		wp_send_json_success( $content );
	}
}
