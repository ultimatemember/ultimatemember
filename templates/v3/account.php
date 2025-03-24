<?php
/**
 * Template for the account page
 *
 * This template can be overridden by copying it to your-theme/ultimate-member/templates/account.php
 *
 * Page: "Account"
 *
 * @version 2.7.0
 *
 * @var string $mode
 * @var int    $form_id
 * @var array  $args
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="um <?php echo esc_attr( $this->get_class( $mode ) ); ?> um-<?php echo esc_attr( $form_id ); ?>">
	<?php
	$account_tabs = array();
	foreach ( UM()->account()->tabs as $id => $info ) {
		$tab_enabled = UM()->options()->get( 'account_tab_' . $id );

		if ( isset( $info['without_setting'] ) || ! empty( $tab_enabled ) || 'general' === $id ) {
			$current = UM()->account()->current_tab === $id;

			// TODO Remove condition for $current if we need all account tabs loaded.
			$content = '';
			if ( $current ) {
				ob_start();
				$info['with_header'] = true;
				UM()->account()->render_account_tab( $id, $info, $args );
				$content = ob_get_clean();
			}

			$account_tabs[ $id ] = array(
				'title'       => $info['title'],
				'description' => array_key_exists( 'description', $info ) ? $info['description'] : '',
				'content'     => $content,
				'url'         => UM()->account()->tab_link( $id ),
			);

			if ( UM()->account()->current_tab === $id ) {
				$account_tabs[ $id ]['current'] = true;
			}
		}
	}

	echo UM()->frontend()::layouts()::tabs(
		array(
			'wrapper_class' => '',
			'tabs'          => $account_tabs,
		)
	);
	?>

	<?php
	/**
	 * UM hook
	 *
	 * @type action
	 * @title um_after_account_page_load
	 * @description After account form
	 * @change_log
	 * ["Since: 2.0"]
	 * @usage add_action( 'um_after_account_page_load', 'function_name', 10 );
	 * @example
	 * <?php
	 * add_action( 'um_after_account_page_load', 'my_after_account_page_load', 10 );
	 * function my_after_account_page_load() {
	 *     // your code here
	 * }
	 * ?>
	 */
	do_action( 'um_after_account_page_load' );
	?>
</div>
