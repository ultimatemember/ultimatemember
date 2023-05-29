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
	 * Current account tab.
	 *
	 * @var string
	 */
	public $current_tab = 'general';

	/**
	 * Account constructor.
	 */
	public function __construct() {
		add_action( 'template_redirect', array( &$this, 'account_page_restrict' ), 10001 );
		add_action( 'um_after_account_privacy', array( &$this, 'um_after_account_privacy' ), 10, 1 );
	}

	/**
	 * Init AllTabs for user account
	 *
	 * @param $args
	 *
	 * @throws \Exception
	 */
	public function init_tabs( $args ) {
		$tabs = array();

		if ( isset( $args['tab'] ) ) {
			$tabs = explode( ',', $args['tab'] );
			$tabs = array_map( 'trim', $tabs );
			$tabs = array_diff( $tabs, array( '' ) );
		}

		$this->tabs = $this->get_tabs();

		ksort( $this->tabs );

		$tabs_structed = array();
		foreach ( $this->tabs as $k => $arr ) {

			foreach ( $arr as $id => $info ) {

				if ( isset( $args['tab'] ) && 1 < count( $tabs ) && ! array_key_exists( $id, array_flip( $tabs ) ) ) {
					continue;
				}

				if ( ! empty( $args['tab'] ) && 1 >= count( $tabs ) && $id !== $args['tab'] ) {
					continue;
				}

				$tab_args    = $this->get_tab_fields( $id, $args );
				$tab_content = $this->get_tab_content( $id, $args );

				if ( ! empty( $tab_args ) || ! empty( $tab_content ) ) {
					$tabs_structed[ $id ] = $info;
				}
			}
		}
		$this->tabs = $tabs_structed;
	}

	/**
	 * Restrict access to Account page.
	 */
	public function account_page_restrict() {
		if ( ! um_is_predefined_page( 'account' ) ) {
			return;
		}

		// Redirect to log in for not logged-in users.
		if ( ! is_user_logged_in() ) {
			$redirect_to = add_query_arg(
				'redirect_to',
				urlencode_deep( um_get_predefined_page_url( 'account' ) ),
				um_get_predefined_page_url( 'login' )
			);

			wp_safe_redirect( $redirect_to );
			exit;
		}

		//set data for fields
//		UM()->fields()->set_mode = 'account';
//		UM()->fields()->editing  = true;

		if ( get_query_var( 'um_tab' ) ) {
			$this->current_tab = get_query_var( 'um_tab' );
		}
	}

	/**
	 * Add export and erase user's data in privacy tab
	 *
	 * @param $args
	 */
	public function um_after_account_privacy( $args ) {
		global $wpdb;
		$user_id = get_current_user_id();

		// Export data
		$export_label  = '<label>' . esc_html__( 'Download your data', 'ultimate-member' );
		$export_label .= '<br>' . esc_attr__( 'You can request a file with the information that we believe is most relevant and useful to you.', 'ultimate-member' );
		$export_label .= '</label>';

		$export_args = array(
			'id'        => 'um-privacy-export-tab',
			'class'     => 'um-top-label um-single-button',
			'prefix_id' => '',
			'fields'    => array(
				'label'   => array(
					'type'    => 'block',
					'id'      => 'um-export-label',
					'content' => $export_label,
				),
				'export'  => array(),
				'content' => array(),
			),
			'hiddens'   => array(
				'um-action-export-tab' => 'account-privacy-export-tab',
				'export-tab-nonce'     => wp_create_nonce( 'um-privacy-export-tab' ),
			),
			'buttons'   => array(
				'request-data' => array(
					'type'  => 'submit',
					'label' => __( 'Request data', 'ultimate-member' ),
					'class' => array(
						'um-button-primary',
					),
				),
			),
		);

		$completed = $wpdb->get_row(
			"SELECT ID
			FROM $wpdb->posts
			WHERE post_author = $user_id AND
			      post_type = 'user_request' AND
			      post_name = 'export_personal_data' AND
			      post_status = 'request-completed'
			ORDER BY ID DESC
			LIMIT 1",
			ARRAY_A
		);

		$pending = $wpdb->get_row(
			"SELECT ID, post_status
			FROM $wpdb->posts
			WHERE post_author = $user_id AND
			      post_type = 'user_request' AND
			      post_name = 'export_personal_data' AND
			      post_status != 'request-completed'
			ORDER BY ID DESC
			LIMIT 1",
			ARRAY_A
		);

		if ( ! empty( $completed ) ) {
			$exports_url = wp_privacy_exports_url();

			$exports_content  = '<p>' . esc_html__( 'You could download your previous data:', 'ultimate-member' ) . '</p>';
			$exports_content .= '<a href="' . esc_attr( $exports_url . get_post_meta( $completed['ID'], '_export_file_name', true ) ) . '">' . esc_html__( 'Download Personal Data', 'ultimate-member' ) . '</a>';
			$exports_content .= '<p>' . esc_html__( 'You could send a new request for an export of personal your data.', 'ultimate-member' ) . '</p>';

			$export_args['fields']['export'] = array(
				'type'    => 'block',
				'id'      => 'um-export-data',
				'content' => $exports_content,
			);
		}

		if ( ! empty( $pending ) && 'request-pending' === $pending['post_status'] ) {
			$exports_content = '<p>' . esc_html__( 'A confirmation email has been sent to your email. Click the link within the email to confirm your export request.', 'ultimate-member' ) . '</p>';

			$export_args['fields']['content'] = array(
				'type'    => 'block',
				'id'      => 'um-export-data',
				'content' => $exports_content,
			);
			unset( $export_args['buttons'] );
		} elseif ( ! empty( $pending ) && 'request-confirmed' === $pending['post_status'] ) {
			$exports_content = '<p>' . esc_html__( 'The administrator has not yet approved downloading the data. Please expect an email with a link to your data.', 'ultimate-member' ) . '</p>';

			$export_args['fields']['content'] = array(
				'type'    => 'block',
				'id'      => 'um-export-data',
				'content' => $exports_content,
			);
			unset( $export_args['buttons'] );
		} else {
			if ( UM()->account()->current_password_is_required( 'privacy_download_data' ) ) {
				$export_args['fields']['content'] = array(
					'type'        => 'password',
					'label'       => __( 'Enter your current password to confirm a new export of your personal data.', 'ultimate-member' ),
					'id'          => 'um-export-data',
					'required'    => true,
					'placeholder' => __( 'Password', 'ultimate-member' ),
					'value'       => '',
				);
			} else {
				$export_args['fields']['content'] = array(
					'type'    => 'block',
					'id'      => 'um-export-data',
					'content' => __( 'To export of your personal data, click the button below.', 'ultimate-member' ),
				);
			}
		}

		$export_form = UM()->frontend()->form(
			array(
				'id' => 'um-privacy-export-tab',
			)
		);

		$export_form->set_data( $export_args );
		$export_form->display();

		// Erase data
		$erase_label  = '<label>' . esc_html__( 'Erase of your data', 'ultimate-member' );
		$erase_label .= '<br>' . esc_attr__( 'You can request erasing of the data that we have about you.', 'ultimate-member' );
		$erase_label .= '</label>';

		$erase_args = array(
			'id'        => 'um-privacy-erase-tab',
			'class'     => 'um-top-label um-single-button',
			'prefix_id' => '',
			'fields'    => array(
				'label'   => array(
					'type'    => 'block',
					'id'      => 'um-erase-label',
					'content' => $erase_label,
				),
				'erase'   => array(),
				'content' => array(),
			),
			'hiddens'   => array(
				'um-action-erase-tab' => 'account-privacy-erase-tab',
				'erase-tab-nonce'     => wp_create_nonce( 'um-privacy-erase-tab' ),
			),
			'buttons'   => array(
				'request-erase-data' => array(
					'type'  => 'submit',
					'label' => __( 'Request data erase', 'ultimate-member' ),
					'class' => array(
						'um-button-primary',
					),
				),
			),
		);

		$erase_completed = $wpdb->get_row(
			"SELECT ID
			FROM $wpdb->posts
			WHERE post_author = $user_id AND
			      post_type = 'user_request' AND
			      post_name = 'remove_personal_data' AND
			      post_status = 'request-completed'
			ORDER BY ID DESC
			LIMIT 1",
			ARRAY_A
		);

		if ( ! empty( $erase_completed ) ) {
			$erase_content  = '<p>' . esc_html__( 'Your personal data has been deleted.', 'ultimate-member' ) . '</p>';
			$erase_content .= '<p>' . esc_html__( 'You could send a new request for deleting your personal data.', 'ultimate-member' ) . '</p>';

			$erase_args['fields']['erase'] = array(
				'type'    => 'block',
				'id'      => 'um-erase-data',
				'content' => $erase_content,
			);
		}

		$erase_pending = $wpdb->get_row(
			"SELECT ID, post_status
			FROM $wpdb->posts
			WHERE post_author = $user_id AND
			      post_type = 'user_request' AND
			      post_name = 'remove_personal_data' AND
			      post_status != 'request-completed'
			ORDER BY ID DESC
			LIMIT 1",
			ARRAY_A
		);

		if ( ! empty( $erase_pending ) && 'request-pending' === $erase_pending['post_status'] ) {
			$erase_content = '<p>' . esc_html__( 'A confirmation email has been sent to your email. Click the link within the email to confirm your deletion request.', 'ultimate-member' ) . '</p>';

			$erase_args['fields']['content'] = array(
				'type'    => 'block',
				'id'      => 'um-erase-data',
				'content' => $erase_content,
			);
			unset( $erase_args['buttons'] );
		} elseif ( ! empty( $erase_pending ) && 'request-confirmed' === $erase_pending['post_status'] ) {
			$erase_content = '<p>' . esc_html__( 'The administrator has not yet approved deleting your data. Please expect an email with a link to your data.', 'ultimate-member' ) . '</p>';

			$erase_args['fields']['content'] = array(
				'type'    => 'block',
				'id'      => 'um-erase-data',
				'content' => $erase_content,
			);
			unset( $erase_args['buttons'] );
		} else {
			if ( UM()->account()->current_password_is_required( 'privacy_erase_data' ) ) {
				$erase_args['fields']['content'] = array(
					'type'        => 'password',
					'label'       => __( 'Enter your current password to confirm the erasure of your personal data.', 'ultimate-member' ),
					'id'          => 'um-erase-data',
					'required'    => true,
					'placeholder' => __( 'Password', 'ultimate-member' ),
					'value'       => '',
				);
			} else {
				$erase_args['fields']['content'] = array(
					'type'    => 'block',
					'id'      => 'um-erase-data',
					'content' => __( 'Require erasure of your personal data, click on the button below.', 'ultimate-member' ),
				);
			}
		}

		$erase_form = UM()->frontend()->form(
			array(
				'id' => 'um-privacy-erase-tab',
			)
		);
		$erase_form->set_data( $erase_args );
		$erase_form->display();
	}
}
