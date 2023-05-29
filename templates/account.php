<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div id="um-account" class="um">

	<?php echo UM()->common()->user()->get_avatar( um_profile_id(), 'l' ); ?>

	<?php
	$tabs = UM()->config()->get( 'account_tabs' );

	if ( ! empty( $tabs ) ) {
		?>
		<ul>
			<?php
			foreach ( $tabs as $key => $data ) {
				if ( UM()->frontend()->account()->current_tab === $key ) {
					var_dump( 'current' );
				}
				?>
				<li>
					<span class="um-account-tab-icon">
						<i class="<?php echo esc_attr( $data['icon'] ); ?>"></i>
					</span>
					<span class="um-account-title"><?php echo esc_html( $data['title'] ); ?></span>
				</li>
				<?php
			}
			?>
		</ul>
		<?php
	}

	$current_account_tab = $tabs[ UM()->frontend()->account()->current_tab ];

	//$current_account_tab['fields']

	$submit_button       = $current_account_tab['submit_title'];

	//var_dump( $submit_button );

	//var_dump( $args );

	/**
	 * UM hook
	 *
	 * @type action
	 * @title um_account_page_hidden_fields
	 * @description Show hidden fields on account form
	 * @input_vars
	 * [{"var":"$args","type":"array","desc":"Account shortcode arguments"}]
	 * @change_log
	 * ["Since: 2.0"]
	 * @usage add_action( 'um_account_page_hidden_fields', 'function_name', 10, 1 );
	 * @example
	 * <?php
	 * add_action( 'um_account_page_hidden_fields', 'my_account_page_hidden_fields', 10, 1 );
	 * function my_account_page_hidden_fields( $args ) {
	 *     // your code here
	 * }
	 * ?>
	 */
	do_action( 'um_account_page_hidden_fields', $args );
	?>

	<div class="um-account-meta uimob340-show uimob500-show">

		<div class="um-account-meta-img">
			<a href="<?php echo esc_url( um_user_profile_url() ); ?>"><?php echo get_avatar( um_user( 'ID' ), 120 ); ?></a>
		</div>

		<div class="um-account-name">
			<a href="<?php echo esc_url( um_user_profile_url() ); ?>">
				<?php echo esc_html( um_user( 'display_name' ) ); ?>
			</a>
			<div class="um-account-profile-link">
				<a href="<?php echo esc_url( um_user_profile_url() ); ?>" class="um-link um-link-always-active">
					<?php esc_html_e( 'View profile', 'ultimate-member' ); ?>
				</a>
			</div>
		</div>

	</div>

	<div class="um-account-side uimob340-hide uimob500-hide">

		<div class="um-account-meta">

			<div class="um-account-meta-img uimob800-hide">
				<a href="<?php echo esc_url( um_user_profile_url() ); ?>">
					<?php echo get_avatar( um_user( 'ID' ), 120 ); ?>
				</a>
			</div>

			<?php if ( UM()->mobile()->isMobile() ) { ?>

				<div class="um-account-meta-img-b uimob800-show" title="<?php echo esc_attr( um_user( 'display_name' ) ); ?>">
					<a href="<?php echo esc_url( um_user_profile_url() ); ?>">
						<?php echo get_avatar( um_user( 'ID' ), 120 ); ?>
					</a>
				</div>

			<?php } else { ?>

				<div class="um-account-meta-img-b uimob800-show um-tip-<?php echo is_rtl() ? 'e' : 'w'; ?>" title="<?php echo esc_attr( um_user( 'display_name' ) ); ?>">
					<a href="<?php echo esc_url( um_user_profile_url() ); ?>">
						<?php echo get_avatar( um_user( 'ID' ), 120 ); ?>
					</a>
				</div>

			<?php } ?>

			<div class="um-account-name uimob800-hide">
				<a href="<?php echo esc_url( um_user_profile_url() ); ?>">
					<?php echo um_user( 'display_name', 'html' ); ?>
				</a>
				<div class="um-account-profile-link">
					<a href="<?php echo esc_url( um_user_profile_url() ); ?>" class="um-link um-link-always-active">
						<?php esc_html_e( 'View profile', 'ultimate-member' ); ?>
					</a>
				</div>
			</div>

		</div>

		<ul>
			<?php foreach ( UM()->account()->tabs as $id => $info ) {
				if ( isset( $info['custom'] ) || UM()->options()->get( "account_tab_{$id}" ) == 1 || $id == 'general' ) { ?>

					<li>
						<a data-tab="<?php echo esc_attr( $id )?>" href="<?php echo esc_url( UM()->account()->tab_link( $id ) ); ?>" class="um-account-link <?php if ( $id == UM()->account()->current_tab ) echo 'current'; ?>">
							<?php if ( UM()->mobile()->isMobile() ) { ?>
								<span class="um-account-icontip uimob800-show" title="<?php echo esc_attr( $info['title'] ); ?>">
									<i class="<?php echo esc_attr( $info['icon'] ); ?>"></i>
								</span>
							<?php } else { ?>
								<span class="um-account-icontip uimob800-show um-tip-<?php echo is_rtl() ? 'e' : 'w'; ?>" title="<?php echo esc_attr( $info['title'] ); ?>">
									<i class="<?php echo esc_attr( $info['icon'] ); ?>"></i>
								</span>
							<?php } ?>

							<span class="um-account-icon uimob800-hide">
								<i class="<?php echo esc_attr( $info['icon'] ); ?>"></i>
							</span>
							<span class="um-account-title uimob800-hide"><?php echo esc_html( $info['title'] ); ?></span>
							<span class="um-account-arrow uimob800-hide">
								<i class="<?php if ( is_rtl() ) { ?>fas fa-angle-left<?php } else { ?>fas fa-angle-right<?php } ?>"></i>
							</span>
						</a>
					</li>

				<?php }
			} ?>
		</ul>
	</div>

	<div class="um-account-main" data-current_tab="<?php echo esc_attr( UM()->account()->current_tab ); ?>">

		<?php
		/**
		 * UM hook
		 *
		 * @type action
		 * @title um_before_form
		 * @description Show some content before account form
		 * @input_vars
		 * [{"var":"$args","type":"array","desc":"Account shortcode arguments"}]
		 * @change_log
		 * ["Since: 2.0"]
		 * @usage add_action( 'um_before_form', 'function_name', 10, 1 );
		 * @example
		 * <?php
		 * add_action( 'um_before_form', 'my_before_form', 10, 1 );
		 * function my_before_form( $args ) {
		 *     // your code here
		 * }
		 * ?>
		 */
		do_action( 'um_before_form', $args );
		foreach ( UM()->account()->tabs as $id => $info ) {

			$current_tab = UM()->account()->current_tab;

			if ( isset( $info['custom'] ) || UM()->options()->get( 'account_tab_' . $id ) == 1 || $id == 'general' ) { ?>

				<div class="um-account-nav uimob340-show uimob500-show">
					<a href="javascript:void(0);" data-tab="<?php echo esc_attr( $id ); ?>" class="<?php if ( $id == $current_tab ) echo 'current'; ?>">
						<?php echo esc_html( $info['title'] ); ?>
						<span class="ico"><i class="<?php echo esc_attr( $info['icon'] ); ?>"></i></span>
						<span class="arr"><i class="fas fa-angle-down"></i></span>
					</a>
				</div>

				<div class="um-account-tab um-account-tab-<?php echo esc_attr( $id ); ?>" data-tab="<?php echo esc_attr( $id  )?>">
					<?php $info['with_header'] = true;
					UM()->account()->render_account_tab( $id, $info, $args ); ?>
				</div>

			<?php }
		} ?>

	</div>
	<div class="um-clear"></div>

	<?php
	/**
	 * Make some action after account shortcode content loading.
	 *
	 * @since 1.3
	 * @hook um_after_account_page_load
	 */
	do_action( 'um_after_account_page_load' );
	?>
</div>
