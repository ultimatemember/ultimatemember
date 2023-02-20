<?php
namespace um\frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Account
 *
 * @package um\frontend
 */
class Account {


	/**
	 * Account constructor.
	 */
	public function __construct() {
		add_action( 'um_after_account_privacy', array( &$this, 'um_after_account_privacy' ), 10, 1 );
	}


	/**
	 * Add export and erase user's data in privacy tab
	 *
	 * @param $args
	 */
	public function um_after_account_privacy( $args ) {
		global $wpdb;
		$user_id = get_current_user_id();

		$export_args = array(
			'id'        => 'um-privacy-export-tab',
			'class'     => 'um-top-label um-single-button',
			'prefix_id' => '',
			'fields'    => array(
				array(
					'type'        => 'password',
					'label'       => __( 'Enter your current password to confirm a new export of your personal data.', 'ultimate-member' ),
					'id'          => 'um-export-data',
					'required'    => true,
					'placeholder' => __( 'Password', 'ultimate-member' ),
					'value'       => '',
				),
			),
			'hiddens'   => array(
				'um-action' => 'account-privacy-export-tab',
				'nonce'     => wp_create_nonce( 'um-privacy-export-tab' ),
			),
			'buttons'   => array(
				'submit-password' => array(
					'type'  => 'submit',
					'label' => __( 'Request data', 'ultimate-member' ),
					'class' => array(
						'um-button-primary',
					),
				),
			),
		);

		$export_form = UM()->frontend()->form(
			array(
				'id' => 'um-privacy-export-tab',
			)
		);

//		$export_form->add_notice(
//			__( 'Enter your new password below and confirm it.', 'ultimate-member' ),
//			'resetpass-info'
//		);
		$export_form->set_data( $export_args );
		$export_form->display();


		$erase_args = array(
			'id'        => 'um-privacy-erase-tab',
			'class'     => 'um-top-label um-single-button',
			'prefix_id' => '',
			'fields'    => array(
				array(
					'type'        => 'password',
					'label'       => __( 'Enter your current password to confirm the erasure of your personal data.', 'ultimate-member' ),
					'id'          => 'um-erase-data',
					'required'    => true,
					'placeholder' => __( 'Password', 'ultimate-member' ),
					'value'       => '',
				),
			),
			'hiddens'   => array(
				'um-action' => 'account-privacy-export-tab',
				'nonce'     => wp_create_nonce( 'um-privacy-export-tab' ),
			),
			'buttons'   => array(
				'submit-password' => array(
					'type'  => 'submit',
					'label' => __( 'Request data erase', 'ultimate-member' ),
					'class' => array(
						'um-button-primary',
					),
				),
			),
		);

		$erase_form = UM()->frontend()->form(
			array(
				'id' => 'um-privacy-erase-tab',
			)
		);
		$erase_form->set_data( $erase_args );
		$erase_form->display();

		/*?>

		<div class="um-field um-field-export_data">
			<div class="um-field-label">
				<label>
					<?php esc_html_e( 'Download your data', 'ultimate-member' ); ?>
				</label>
				<span class="um-tip um-tip-<?php echo is_rtl() ? 'e' : 'w' ?>" original-title="<?php esc_attr_e( 'You can request a file with the information that we believe is most relevant and useful to you.', 'ultimate-member' ); ?>">
				<i class="fas fa-question-circle"></i>
			</span>
				<div class="um-clear"></div>
			</div>
			<?php $completed = $wpdb->get_row(
				"SELECT ID
			FROM $wpdb->posts
			WHERE post_author = $user_id AND
			      post_type = 'user_request' AND
			      post_name = 'export_personal_data' AND
			      post_status = 'request-completed'
			ORDER BY ID DESC
			LIMIT 1",
				ARRAY_A );

			if ( ! empty( $completed ) ) {

				$exports_url = wp_privacy_exports_url();

				echo '<p>' . esc_html__( 'You could download your previous data:', 'ultimate-member' ) . '</p>';
				echo '<a href="'.esc_attr( $exports_url . get_post_meta( $completed['ID'], '_export_file_name', true ) ) . '">' . esc_html__( 'Download Personal Data', 'ultimate-member' ) . '</a>';
				echo '<p>' . esc_html__( 'You could send a new request for an export of personal your data.', 'ultimate-member' ) . '</p>';

			}

			$pending = $wpdb->get_row(
				"SELECT ID, post_status
			FROM $wpdb->posts
			WHERE post_author = $user_id AND
			      post_type = 'user_request' AND
			      post_name = 'export_personal_data' AND
			      post_status != 'request-completed'
			ORDER BY ID DESC
			LIMIT 1",
				ARRAY_A );

			if ( ! empty( $pending ) && $pending['post_status'] == 'request-pending' ) {
				echo '<p>' . esc_html__( 'A confirmation email has been sent to your email. Click the link within the email to confirm your export request.', 'ultimate-member' ) . '</p>';
			} elseif ( ! empty( $pending ) && $pending['post_status'] == 'request-confirmed' ) {
				echo '<p>' . esc_html__( 'The administrator has not yet approved downloading the data. Please expect an email with a link to your data.', 'ultimate-member' ) . '</p>';
			} else {
				if ( UM()->account()->current_password_is_required( 'privacy_download_data' ) ) { ?>

					<label name="um-export-data">
						<?php esc_html_e( 'Enter your current password to confirm a new export of your personal data.', 'ultimate-member' ); ?>
					</label>
					<div class="um-field-area">
						<input id="um-export-data" type="password" placeholder="<?php esc_attr_e( 'Password', 'ultimate-member' )?>">
						<div class="um-field-error um-export-data">
							<span class="um-field-arrow"><i class="fas fa-caret-up"></i></span><?php esc_html_e( 'You must enter a password', 'ultimate-member' ); ?>
						</div>
						<div class="um-field-area-response um-export-data"></div>
					</div>

				<?php } else { ?>

					<label name="um-export-data">
						<?php esc_html_e( 'To export of your personal data, click the button below.', 'ultimate-member' ); ?>
					</label>
					<div class="um-field-area-response um-export-data"></div>

				<?php } ?>

				<a class="um-request-button um-export-data-button" data-action="um-export-data" href="javascript:void(0);">
					<?php esc_html_e( 'Request data', 'ultimate-member' ); ?>
				</a>
			<?php } ?>

		</div>

		<div class="um-field um-field-export_data">
			<div class="um-field-label">
				<label>
					<?php esc_html_e( 'Erase of your data', 'ultimate-member' ); ?>
				</label>
				<span class="um-tip um-tip-<?php echo is_rtl() ? 'e' : 'w' ?>" original-title="<?php esc_attr_e( 'You can request erasing of the data that we have about you.', 'ultimate-member' ); ?>">
				<i class="fas fa-question-circle"></i>
			</span>
				<div class="um-clear"></div>
			</div>

			<?php $completed = $wpdb->get_row(
				"SELECT ID
			FROM $wpdb->posts
			WHERE post_author = $user_id AND
			      post_type = 'user_request' AND
			      post_name = 'remove_personal_data' AND
			      post_status = 'request-completed'
			ORDER BY ID DESC
			LIMIT 1",
				ARRAY_A );

			if ( ! empty( $completed ) ) {

				echo '<p>' . esc_html__( 'Your personal data has been deleted.', 'ultimate-member' ) . '</p>';
				echo '<p>' . esc_html__( 'You could send a new request for deleting your personal data.', 'ultimate-member' ) . '</p>';

			}

			$pending = $wpdb->get_row(
				"SELECT ID, post_status
			FROM $wpdb->posts
			WHERE post_author = $user_id AND
			      post_type = 'user_request' AND
			      post_name = 'remove_personal_data' AND
			      post_status != 'request-completed'
			ORDER BY ID DESC
			LIMIT 1",
				ARRAY_A );

			if ( ! empty( $pending ) && $pending['post_status'] == 'request-pending' ) {
				echo '<p>' . esc_html__( 'A confirmation email has been sent to your email. Click the link within the email to confirm your deletion request.', 'ultimate-member' ) . '</p>';
			} elseif ( ! empty( $pending ) && $pending['post_status'] == 'request-confirmed' ) {
				echo '<p>' . esc_html__( 'The administrator has not yet approved deleting your data. Please expect an email with a link to your data.', 'ultimate-member' ) . '</p>';
			} else {
				if ( UM()->account()->current_password_is_required( 'privacy_erase_data' ) ) { ?>

					<label name="um-erase-data">
						<?php esc_html_e( 'Enter your current password to confirm the erasure of your personal data.', 'ultimate-member' ); ?>
						<input id="um-erase-data" type="password" placeholder="<?php esc_attr_e( 'Password', 'ultimate-member' )?>">
						<div class="um-field-error um-erase-data">
							<span class="um-field-arrow"><i class="fas fa-caret-up"></i></span><?php esc_html_e( 'You must enter a password', 'ultimate-member' ); ?>
						</div>
						<div class="um-field-area-response um-erase-data"></div>
					</label>

				<?php } else { ?>

					<label name="um-erase-data">
						<?php esc_html_e( 'Require erasure of your personal data, click on the button below.', 'ultimate-member' ); ?>
						<div class="um-field-area-response um-erase-data"></div>
					</label>

				<?php } ?>

				<a class="um-request-button um-erase-data-button" data-action="um-erase-data" href="javascript:void(0);">
					<?php esc_html_e( 'Request data erase', 'ultimate-member' ); ?>
				</a>
			<?php } ?>

		</div>

		<?php */
	}
}
