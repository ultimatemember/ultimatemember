<?php
/**
 * Template for the account page
 *
 * This template can be overridden by copying it to yourtheme/ultimate-member/templates/account.php
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

	<div class="um-form">

		<form method="post" action="">

			<?php
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

			<div class="um-account-meta radius-<?php echo esc_attr( UM()->options()->get( 'profile_photocorner' ) ); ?> uimob340-show uimob500-show">

				<div class="um-account-meta-img">
					<a href="<?php echo esc_url( um_user_profile_url() ); ?>"><?php echo get_avatar( um_user( 'ID' ), 120 ); ?></a>
				</div>

				<div class="um-account-name">
					<a href="<?php echo esc_url( um_user_profile_url() ); ?>">
						<?php echo esc_html( um_user( 'display_name' ) ); ?>
					</a>
					<div class="um-account-profile-link">
						<a href="<?php echo esc_url( um_user_profile_url() ); ?>" class="um-link">
							<?php esc_html_e( 'View profile', 'ultimate-member' ); ?>
						</a>
					</div>
				</div>

			</div>

			<div class="um-account-side uimob340-hide uimob500-hide">

				<div class="um-account-meta radius-<?php echo esc_attr( UM()->options()->get( 'profile_photocorner' ) ); ?>">

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
							<a href="<?php echo esc_url( um_user_profile_url() ); ?>" class="um-link">
								<?php esc_html_e( 'View profile', 'ultimate-member' ); ?>
							</a>
						</div>
					</div>

				</div>

				<ul>
					<?php
					foreach ( UM()->account()->tabs as $id => $info ) {
						$tab_enabled = UM()->options()->get( 'account_tab_' . $id );
						if ( isset( $info['custom'] ) || ! empty( $tab_enabled ) || 'general' === $id ) {
							?>
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
										<i class="<?php if ( is_rtl() ) { ?>um-faicon-angle-left<?php } else { ?>um-faicon-angle-right<?php } ?>"></i>
									</span>
								</a>
							</li>
							<?php
						}
					}
					?>
				</ul>
			</div>

			<div class="um-account-main" data-current_tab="<?php echo esc_attr( UM()->account()->current_tab ); ?>">

				<?php
				/** This action is documented in includes/core/um-actions-profile.php */
				do_action( 'um_before_form', $args );

				foreach ( UM()->account()->tabs as $id => $info ) {
					$tab_enabled = UM()->options()->get( 'account_tab_' . $id );
					$current_tab = UM()->account()->current_tab;

					if ( isset( $info['custom'] ) || ! empty( $tab_enabled ) || 'general' === $id ) {
						?>
						<div class="um-account-nav uimob340-show uimob500-show">
							<a href="javascript:void(0);" data-tab="<?php echo esc_attr( $id ); ?>" class="<?php if ( $id === $current_tab ) echo 'current'; ?>">
								<?php echo esc_html( $info['title'] ); ?>
								<span class="ico"><i class="<?php echo esc_attr( $info['icon'] ); ?>"></i></span>
								<span class="arr"><i class="um-faicon-angle-down"></i></span>
							</a>
						</div>

						<div class="um-account-tab um-account-tab-<?php echo esc_attr( $id ); ?>" data-tab="<?php echo esc_attr( $id  )?>">
							<?php $info['with_header'] = true;
							UM()->account()->render_account_tab( $id, $info, $args ); ?>
						</div>
						<?php
					}
				}
				?>
			</div>
			<div class="um-clear"></div>
		</form>

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
</div>
