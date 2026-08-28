<?php
/**
 * Template for the account page
 *
 * This template can be overridden by copying it to your-theme/ultimate-member/templates/account.php
 *
 * Page: "Account"
 *
 * @version 3.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
global $wpdb;
$user_id = get_current_user_id();

$export_completed = $wpdb->get_row(
	$wpdb->prepare(
		"SELECT ID
		FROM $wpdb->posts
		WHERE post_author = %d AND
			  post_type = 'user_request' AND
			  post_name = 'export_personal_data' AND
			  post_status = 'request-completed'
		ORDER BY ID DESC
		LIMIT 1",
		$user_id
	),
	ARRAY_A
);

$export_pending = $wpdb->get_row(
	$wpdb->prepare(
		"SELECT ID, post_status
		FROM $wpdb->posts
		WHERE post_author = %d AND
			  post_type = 'user_request' AND
			  post_name = 'export_personal_data' AND
			  post_status != 'request-completed'
		ORDER BY ID DESC
		LIMIT 1",
		$user_id
	),
	ARRAY_A
);

$remove_completed = $wpdb->get_row(
	$wpdb->prepare(
		"SELECT ID
		FROM $wpdb->posts
		WHERE post_author = %d AND
			  post_type = 'user_request' AND
			  post_name = 'remove_personal_data' AND
			  post_status = 'request-completed'
		ORDER BY ID DESC
		LIMIT 1",
		$user_id
	),
	ARRAY_A
);

$remove_pending = $wpdb->get_row(
	$wpdb->prepare(
		"SELECT ID, post_status
		FROM $wpdb->posts
		WHERE post_author = %d AND
			  post_type = 'user_request' AND
			  post_name = 'remove_personal_data' AND
			  post_status != 'request-completed'
		ORDER BY ID DESC
		LIMIT 1",
		$user_id
	),
	ARRAY_A
);
?>

<?php echo UM()->frontend()::layouts()::divider(); ?>

<form method="post" action="" class="um-form-new">
	<div class="um-form-rows">
		<div class="um-form-rows-heading">
			<div class="um-text"><?php esc_html_e( 'Download your data', 'ultimate-member' ); ?></div>
			<div class="um-supporting-text">
				<?php
				if ( ! empty( $export_completed ) ) {
					$exports_url = wp_privacy_exports_url();
					printf( __( 'You could %s your previous data or send a new request for an export of personal your data.', 'ultimate-member' ), '<a class="um-link" href="' . esc_attr( $exports_url . get_post_meta( $export_completed['ID'], '_export_file_name', true ) ) . '">' . esc_html__( 'download', 'ultimate-member' ) . '</a>' );
				} else {
					esc_html_e( 'You can request a file with the information that we believe is most relevant and useful to you.', 'ultimate-member' );
				}
				?>
			</div>
		</div>
		<div class="um-form-row">
			<div class="um-form-cols um-form-cols-1">
				<div class="um-form-col um-form-col-1">
					<?php
					if ( ! empty( $export_pending ) && 'request-pending' === $export_pending['post_status'] ) {
						echo '<p class="um-supporting-text">' . esc_html__( 'A confirmation email has been sent to your email. Click the link within the email to confirm your export request.', 'ultimate-member' ) . '</p>';
					} elseif ( ! empty( $export_pending ) && 'request-confirmed' === $export_pending['post_status'] ) {
						echo '<p class="um-supporting-text">' . esc_html__( 'The administrator has not yet approved downloading the data. Please expect an email with a link to your data.', 'ultimate-member' ) . '</p>';
					} else {
						if ( UM()->account()->current_password_is_required( 'privacy_download_data' ) ) {
							UM()->form()->form_suffix = '-export-request';
							$fields = UM()->builtin()->get_specific_fields( 'single_user_password' );
							foreach ( $fields as $key => $data ) {
								if ( 'single_user_password' === $key ) {
									$data['help'] = __( 'Enter your current password to confirm a new export of your personal data.', 'ultimate-member' );
								}

								echo UM()->fields()->edit_field( $key, $data );
							}
						} else {
							?>
							<label name="um-export-data">
								<?php esc_html_e( 'To export of your personal data, click the button below.', 'ultimate-member' ); ?>
							</label>
							<?php
						}
					}
					?>
				</div>
			</div>
		</div>
	</div>

	<?php if ( empty( $export_pending ) ) { ?>
		<div class="um-form-submit">
			<?php
			echo UM()->frontend()::layouts()::button(
				__( 'Request download', 'ultimate-member' ),
				array(
					'design'  => 'primary',
					'id'      => 'um_account_request_download_data',
					'data'    => array(
						'action' => 'um-export-data',
						'nonce'  => wp_create_nonce( 'um-export-data' ),
						'error'  => __( 'Password is required.', 'ultimate-member' ),
						'hint'   => __( 'Enter your current password to confirm a new export of your personal data.', 'ultimate-member' ),
					),
					'classes' => array(
						'um-request-button',
						'um-export-data-button',
					),
				)
			);

			echo UM()->frontend()::layouts()::ajax_loader( 'm' );
			?>
		</div>
	<?php } ?>
</form>

<?php echo UM()->frontend()::layouts()::divider(); ?>

<form method="post" action="" class="um-form-new">
	<div class="um-form-rows">
		<div class="um-form-rows-heading">
			<div class="um-text"><?php esc_html_e( 'Erase of your data', 'ultimate-member' ); ?></div>
			<div class="um-supporting-text">
				<?php
				if ( ! empty( $remove_completed ) ) {
					esc_html__( 'Your personal data has been deleted. You could send a new request for deleting your personal data.', 'ultimate-member' );
				} else {
					esc_html_e( 'You can request erasing of the data that we have about you.', 'ultimate-member' );
				}
				?>
			</div>
		</div>
		<div class="um-form-row">
			<div class="um-form-cols um-form-cols-1">
				<div class="um-form-col um-form-col-1">
					<?php
					if ( ! empty( $remove_pending ) && 'request-pending' === $remove_pending['post_status'] ) {
						echo '<p class="um-supporting-text">' . esc_html__( 'A confirmation email has been sent to your email. Click the link within the email to confirm your deletion request.', 'ultimate-member' ) . '</p>';
					} elseif ( ! empty( $remove_pending ) && 'request-confirmed' === $remove_pending['post_status'] ) {
						echo '<p class="um-supporting-text">' . esc_html__( 'The administrator has not yet approved deleting your data. Please expect an email with a link to your data.', 'ultimate-member' ) . '</p>';
					} else {
						if ( UM()->account()->current_password_is_required( 'privacy_erase_data' ) ) {
							UM()->form()->form_suffix = '-erase-request';
							$fields = UM()->builtin()->get_specific_fields( 'single_user_password' );
							foreach ( $fields as $key => $data ) {
								if ( 'single_user_password' === $key ) {
									$data['help'] = __( 'Enter your current password to confirm the erasure of your personal data.', 'ultimate-member' );
								}

								echo UM()->fields()->edit_field( $key, $data );
							}
						} else {
							?>
							<label name="um-export-data">
								<?php esc_html_e( 'Require erasure of your personal data, click on the button below.', 'ultimate-member' ); ?>
							</label>
							<?php
						}
					}
					?>
				</div>
			</div>
		</div>
	</div>
	<?php if ( empty( $remove_pending ) ) { ?>
		<div class="um-form-submit">
			<?php
			echo UM()->frontend()::layouts()::button(
				__( 'Request erase', 'ultimate-member' ),
				array(
					'design'  => 'primary',
					'id'      => 'um_account_request_erase_data',
					'data'    => array(
						'action' => 'um-erase-data',
						'nonce'  => wp_create_nonce( 'um-erase-data' ),
						'error'  => __( 'Password is required.', 'ultimate-member' ),
						'hint'   => __( 'Enter your current password to confirm the erasure of your personal data.', 'ultimate-member' ),
					),
					'classes' => array(
						'um-request-button',
						'um-erase-data-button',
					),
				)
			);

			echo UM()->frontend()::layouts()::ajax_loader( 'm' );
			?>
		</div>
	<?php } ?>
</form>

<?php
UM()->form()->form_suffix = ''; // flush form suffix.

do_action( 'um_account_after_personal_data_form' );
