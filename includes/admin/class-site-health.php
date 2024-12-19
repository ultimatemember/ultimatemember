<?php
namespace um\admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Site_Health
 *
 * @package um\admin
 */
class Site_Health {

	/**
	 * String of a badge color.
	 * Options: blue, green, red, orange, purple and gray.
	 *
	 * @see https://make.wordpress.org/core/2019/04/25/site-health-check-in-5-2/
	 *
	 * @since 2.8.3
	 */
	const BADGE_COLOR = 'blue';

	/**
	 * Site_Health constructor.
	 */
	public function __construct() {
		add_filter( 'debug_information', array( $this, 'debug_information' ), 20 );
		add_filter( 'site_status_tests', array( $this, 'register_site_status_tests' ) );
	}

	public function register_site_status_tests( $tests ) {
		$custom_templates = UM()->common()->theme()->get_custom_templates_list();

		if ( ! empty( $custom_templates ) ) {
			$tests['direct']['um_override_templates'] = array(
				'label' => esc_html__( 'Are the Ultimate Member templates out of date?', 'ultimate-member' ),
				'test'  => array( $this, 'override_templates_test' ),
			);
		}

		$first_activation_date = get_option( 'um_first_activation_date', false );
		if ( ! empty( $first_activation_date ) && $first_activation_date < 1716336000 ) {
			$tests['direct']['um_outdated_icons'] = array(
				'label' => esc_html__( 'Are the icons in Ultimate Member Forms and Settings out of date?', 'ultimate-member' ),
				'test'  => array( $this, 'outdated_icons_test' ),
			);
		}

		return $tests;
	}

	public function override_templates_test() {
		$result = array(
			'label'       => __( 'You have the most recent version of custom Ultimate Member templates', 'ultimate-member' ),
			'status'      => 'good',
			'badge'       => array(
				'label' => UM_PLUGIN_NAME,
				'color' => self::BADGE_COLOR,
			),
			'description' => sprintf(
				'<p>%s</p>',
				__( 'Your custom Ultimate Member templates that are situated in the theme have the most recent version and are ready to use.', 'ultimate-member' )
			),
			'actions'     => '',
			'test'        => 'um_override_templates',
		);

		if ( UM()->common()->theme()->is_outdated_template_exist() ) {
			$result['label']          = __( 'Your custom templates are out of date', 'ultimate-member' );
			$result['status']         = 'critical';
			$result['badge']['color'] = 'red';
			$result['description']    = sprintf(
				'<p>%s</p>',
				__( 'Your custom Ultimate Member templates that are situated in the theme are out of date and may break the website\'s functionality.', 'ultimate-member' )
			);
			$result['actions']        = sprintf(
				'<p><a href="%s">%s</a></p>',
				admin_url( 'admin.php?page=um_options&tab=advanced&section=override_templates' ),
				esc_html__( 'Check status and update', 'ultimate-member' )
			);
		}

		return $result;
	}

	/**
	 * @return bool|array
	 */
	private function get_outdated_icons() {
		$result = array(
			'description' => '',
			'actions'     => '',
		);

		$old_icons = UM()->fonticons()->all;

		$forms = get_posts(
			array(
				'post_type'      => 'um_form',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);

		$forms_count = 0;
		$break_forms = array();
		if ( ! empty( $forms ) ) {
			foreach ( $forms as $form_id ) {
				$fields = UM()->query()->get_attr( 'custom_fields', $form_id );
				if ( empty( $fields ) ) {
					continue;
				}
				foreach ( $fields as $field ) {
					if ( empty( $field['icon'] ) ) {
						continue;
					}

					if ( in_array( $field['icon'], $old_icons, true ) ) {
						$break_forms[] = array(
							'id'    => $form_id,
							'title' => get_the_title( $form_id ),
							'link'  => get_edit_post_link( $form_id ),
						);
						++$forms_count;
						continue 2;
					}
				}
			}
		}

		if ( 0 < $forms_count ) {
			$result['description'] .= sprintf(
				'<p>%s</p>',
				__( 'Your fields\' icons in the Ultimate Member Forms are out of date.', 'ultimate-member' )
			);

			if ( ! empty( $break_forms ) ) {
				$result['description'] .= sprintf(
					'<p>%s',
					__( 'Related to Ultimate Member Forms: ', 'ultimate-member' )
				);

				$form_links = array();
				foreach ( $break_forms as $break_form ) {
					$form_links[] = sprintf(
						'<a href="%s" target="_blank">%s (#ID: %s)</a>',
						esc_url( $break_form['link'] ),
						esc_html( $break_form['title'] ),
						esc_html( $break_form['id'] )
					);
				}

				$result['description'] .= sprintf(
					'%s</p><hr />',
					implode( ', ', $form_links )
				);
			}

			$result['actions'] .= sprintf(
				'<p><a href="%s">%s</a></p>',
				admin_url( 'edit.php?post_type=um_form' ),
				esc_html__( 'Edit form fields and update', 'ultimate-member' )
			);
		}

		$result = apply_filters( 'um_get_outdated_icons_result', $result, $old_icons );

		if ( ! empty( $result['description'] ) ) {
			$result['description'] .= sprintf(
				'<p>%s</p>',
				__( 'As soon as legacy icons will be removed old icons may break the website\'s functionality.', 'ultimate-member' )
			);
		}

		if ( ! empty( $result['description'] ) && ! empty( $result['actions'] ) ) {
			return $result;
		}

		return false;
	}

	public function outdated_icons_test() {
		$result = array(
			'label'       => __( 'You have the most recent version of icons in Ultimate Member forms and settings', 'ultimate-member' ),
			'status'      => 'good',
			'badge'       => array(
				'label' => UM_PLUGIN_NAME,
				'color' => self::BADGE_COLOR,
			),
			'description' => sprintf(
				'<p>%s</p>',
				__( 'Your fields in the Ultimate Member Forms and settings have the most recent version and are ready to use.', 'ultimate-member' )
			),
			'actions'     => '',
			'test'        => 'um_outdated_icons',
		);

		$outdated_icons = $this->get_outdated_icons();

		if ( false !== $outdated_icons ) {
			$result['label']          = __( 'Some field icons and (or) Ultimate Member settings icons are out of date', 'ultimate-member' );
			$result['status']         = 'recommended';
			$result['badge']['color'] = 'orange';
			$result['description']    = $outdated_icons['description'];
			$result['actions']        = $outdated_icons['actions'];
		}

		return $result;
	}

	private function get_roles() {
		return UM()->roles()->get_roles();
	}

	private function get_forms() {
		$forms_data = get_posts(
			array(
				'post_type'      => 'um_form',
				'posts_per_page' => -1,
			)
		);
		$forms      = array();
		foreach ( $forms_data as $form ) {
			$forms[ 'ID#' . $form->ID ] = $form->post_title;
		}
		return $forms;
	}

	private function get_role_meta( $key ) {
		return get_option( "um_role_{$key}_meta", false );
	}

	public function array_map( $item ) {
		if ( is_array( $item ) ) {
			$item = maybe_serialize( $item );
		}
		return $item;
	}

	private function get_field_data( $info, $key, $field_key, $field ) {
		$row        = isset( $field['metakey'] ) ? false : true;
		$title      = $row ? __( 'Row: ', 'ultimate-member' ) . $field['id'] : __( 'Field: ', 'ultimate-member' ) . $field['metakey'];
		$field      = array_map( array( &$this, 'array_map' ), $field );
		$field_info = array(
			'um-field_' . $field_key => array(
				'label' => $title,
				'value' => $field,
			),
		);

		return $field_info;
	}

	private function get_member_directories() {
		$query              = new \WP_Query();
		$member_directories = $query->query(
			array(
				'post_type'      => 'um_directory',
				'posts_per_page' => -1,
			)
		);

		$directories = array();
		foreach ( $member_directories as $directory ) {
			$directories[ 'ID#' . $directory->ID ] = $directory->post_title;
		}

		return $directories;
	}

	/**
	 * Add our data to Site Health information.
	 *
	 * @since 2.7.0
	 *
	 * @param array $info The Site Health information.
	 *
	 * @return array The updated Site Health information.
	 */
	public function debug_information( $info ) {
		$labels = array(
			'yes'     => __( 'Yes', 'ultimate-member' ),
			'no'      => __( 'No', 'ultimate-member' ),
			'all'     => __( 'All', 'ultimate-member' ),
			'default' => __( 'Default', 'ultimate-member' ),
			'nopages' => __( 'No predefined page', 'ultimate-member' ),
		);

		$info['ultimate-member'] = array(
			'label'       => __( 'Ultimate Member', 'ultimate-member' ),
			'description' => __( 'This debug information for your Ultimate Member installation can assist you in getting support.', 'ultimate-member' ),
			'fields'      => array(),
		);

		// Pages settings.
		$pages            = array();
		$predefined_pages = UM()->config()->core_pages;
		foreach ( $predefined_pages as $page_s => $page ) {
			$page_id    = UM()->options()->get_predefined_page_option_key( $page_s );
			$page_title = ! empty( $page['title'] ) ? $page['title'] : '';
			if ( empty( $page_title ) ) {
				continue;
			}

			$predefined_page_id = UM()->options()->get( $page_id );

			if ( empty( $predefined_page_id ) ) {
				$pages[ $page_title ] = $labels['nopages'];
				continue;
			}
			// translators: %1$s is a predefined page title; %2$d is a predefined page ID; %3$s is a predefined page permalink.
			$pages[ $page_title ] = sprintf( __( '%1$s (ID#%2$d) | %3$s', 'ultimate-member' ), get_the_title( $predefined_page_id ), $predefined_page_id, get_permalink( $predefined_page_id ) );
		}

		$pages = apply_filters( 'um_debug_information_pages', $pages );

		$pages_settings = array(
			'um-pages' => array(
				'label' => __( 'Pages', 'ultimate-member' ),
				'value' => $pages,
			),
		);

		// User settings
		$permalink_base = UM()->config()->permalink_base_options;
		$display_name   = UM()->config()->display_name_options;

		$user_settings = array(
			'um-permalink_base'              => array(
				'label' => __( 'Profile Permalink Base', 'ultimate-member' ),
				'value' => isset( $permalink_base[ UM()->options()->get( 'permalink_base' ) ] ) ? $permalink_base[ UM()->options()->get( 'permalink_base' ) ] : $labels['no'],
			),
			'um-display_name'                => array(
				'label' => __( 'User Display Name', 'ultimate-member' ),
				'value' => isset( $display_name[ UM()->options()->get( 'display_name' ) ] ) ? $display_name[ UM()->options()->get( 'display_name' ) ] : $labels['no'],
			),
			'um-author_redirect'             => array(
				'label' => __( 'Automatically redirect author page to their profile?', 'ultimate-member' ),
				'value' => UM()->options()->get( 'author_redirect' ) ? $labels['yes'] : $labels['no'],
			),
			'um-members_page'                => array(
				'label' => __( 'Enable Members Directory', 'ultimate-member' ),
				'value' => UM()->options()->get( 'members_page' ) ? $labels['yes'] : $labels['no'],
			),
			'um-toggle_password'             => array(
				'label' => __( 'Show/hide password button', 'ultimate-member' ),
				'value' => UM()->options()->get( 'toggle_password' ) ? $labels['yes'] : $labels['no'],
			),
			'um-require_strongpass'          => array(
				'label' => __( 'Require Strong Passwords', 'ultimate-member' ),
				'value' => UM()->options()->get( 'require_strongpass' ) ? $labels['yes'] : $labels['no'],
			),
			'um-password_min_chars'          => array(
				'label' => __( 'Require Strong Passwords', 'ultimate-member' ),
				'value' => UM()->options()->get( 'password_min_chars' ),
			),
			'um-password_max_chars'          => array(
				'label' => __( 'Require Strong Passwords', 'ultimate-member' ),
				'value' => UM()->options()->get( 'password_max_chars' ),
			),
			'um-profile_noindex'             => array(
				'label' => __( 'Avoid indexing profile by search engines', 'ultimate-member' ),
				'value' => UM()->options()->get( 'profile_noindex' ) ? $labels['yes'] : $labels['no'],
			),
			'um-activation_link_expiry_time' => array(
				'label' => __( 'Activation link lifetime', 'ultimate-member' ),
				'value' => UM()->options()->get( 'activation_link_expiry_time' ),
			),
			'um-use_gravatars'               => array(
				'label' => __( 'Use Gravatars?', 'ultimate-member' ),
				'value' => UM()->options()->get( 'use_gravatars' ) ? $labels['yes'] : $labels['no'],
			),
			'um-delete_comments'             => array(
				'label' => __( 'Deleting user comments after deleting a user', 'ultimate-member' ),
				'value' => UM()->options()->get( 'delete_comments' ) ? $labels['yes'] : $labels['no'],
			),
		);

		if ( 'custom_meta' === UM()->options()->get( 'permalink_base' ) ) {
			$user_settings = UM()->array_insert_before(
				$user_settings,
				'um-display_name',
				array(
					'um-permalink_base_custom_meta' => array(
						'label' => __( 'Profile Permalink Base Custom Meta Key', 'ultimate-member' ),
						'value' => UM()->options()->get( 'permalink_base_custom_meta' ),
					),
				)
			);
		}

		if ( 'field' === UM()->options()->get( 'display_name' ) ) {
			$user_settings = UM()->array_insert_before(
				$user_settings,
				'um-author_redirect',
				array(
					'um-display_name_field' => array(
						'label' => __( 'Display Name Custom Field(s)', 'ultimate-member' ),
						'value' => UM()->options()->get( 'display_name_field' ),
					),
				)
			);
		}

		if ( UM()->options()->get( 'use_gravatars' ) ) {
			$gravatar_options = array(
				'default'   => __( 'Default', 'ultimate-member' ),
				'404'       => __( '404 ( File Not Found response )', 'ultimate-member' ),
				'mm'        => __( 'Mystery Man', 'ultimate-member' ),
				'identicon' => __( 'Identicon', 'ultimate-member' ),
				'monsterid' => __( 'Monsterid', 'ultimate-member' ),
				'wavatar'   => __( 'Wavatar', 'ultimate-member' ),
				'retro'     => __( 'Retro', 'ultimate-member' ),
				'blank'     => __( 'Blank ( a transparent PNG image )', 'ultimate-member' ),
			);

			$user_settings['um-use_um_gravatar_default_builtin_image'] = array(
				'label' => __( 'Use Gravatar builtin image', 'ultimate-member' ),
				'value' => $gravatar_options[ UM()->options()->get( 'use_um_gravatar_default_builtin_image' ) ],
			);
			if ( 'default' === UM()->options()->get( 'use_um_gravatar_default_builtin_image' ) ) {
				$user_settings['um-use_um_gravatar_default_image'] = array(
					'label' => __( 'Use Default plugin avatar as Gravatar\'s Default avatar', 'ultimate-member' ),
					'value' => UM()->options()->get( 'use_um_gravatar_default_image' ) ? $labels['yes'] : $labels['no'],
				);
			}
		}

		// Account settings
		$account_settings = array(
			'um-account_tab_password' => array(
				'label' => __( 'Password Account Tab', 'ultimate-member' ),
				'value' => UM()->options()->get( 'account_tab_password' ) ? $labels['yes'] : $labels['no'],
			),
			'um-account_tab_privacy'  => array(
				'label' => __( 'Privacy Account Tab', 'ultimate-member' ),
				'value' => UM()->options()->get( 'account_tab_privacy' ) ? $labels['yes'] : $labels['no'],
			),
		);

		if ( false !== UM()->account()->is_notifications_tab_visible() ) {
			$account_settings['um-account_tab_notifications'] = array(
				'label' => __( 'Notifications Account Tab', 'ultimate-member' ),
				'value' => UM()->options()->get( 'account_tab_notifications' ) ? $labels['yes'] : $labels['no'],
			);
		}

		$account_settings = array_merge(
			$account_settings,
			array(
				'um-account_tab_delete'                   => array(
					'label' => __( 'Delete Account Tab', 'ultimate-member' ),
					'value' => UM()->options()->get( 'account_tab_delete' ) ? $labels['yes'] : $labels['no'],
				),
				'um-delete_account_text'                  => array(
					'label' => __( 'Account Deletion Custom Text', 'ultimate-member' ),
					'value' => UM()->options()->get( 'delete_account_text' ),
				),
				'um-delete_account_no_pass_required_text' => array(
					'label' => __( 'Account Deletion without password Custom Text', 'ultimate-member' ),
					'value' => UM()->options()->get( 'delete_account_no_pass_required_text' ),
				),
				'um-account_name'                         => array(
					'label' => __( 'Add a First & Last Name fields', 'ultimate-member' ),
					'value' => UM()->options()->get( 'account_name' ) ? $labels['yes'] : $labels['no'],
				),
			)
		);

		if ( UM()->options()->get( 'account_name' ) ) {
			$account_settings['um-account_name_disable'] = array(
				'label' => __( 'Disable First & Last name field editing', 'ultimate-member' ),
				'value' => UM()->options()->get( 'account_name_disable' ) ? $labels['yes'] : $labels['no'],
			);
			$account_settings['um-account_name_require'] = array(
				'label' => __( 'Require First & Last Name', 'ultimate-member' ),
				'value' => UM()->options()->get( 'account_name_require' ) ? $labels['yes'] : $labels['no'],
			);
		}

		$account_settings['um-account_email'] = array(
			'label' => __( 'Allow users to change email', 'ultimate-member' ),
			'value' => UM()->options()->get( 'account_email' ) ? $labels['yes'] : $labels['no'],
		);

		$account_settings['um-account_general_password'] = array(
			'label' => __( 'Password is required?', 'ultimate-member' ),
			'value' => UM()->options()->get( 'account_general_password' ) ? $labels['yes'] : $labels['no'],
		);

		$account_settings['um-account_hide_in_directory'] = array(
			'label' => __( 'Allow users to hide their profiles from directory', 'ultimate-member' ),
			'value' => UM()->options()->get( 'account_hide_in_directory' ) ? $labels['yes'] : $labels['no'],
		);

		if ( UM()->options()->get( 'account_hide_in_directory' ) ) {
			$account_settings['um-account_hide_in_directory_default'] = array(
				'label' => __( 'Hide profiles from directory by default', 'ultimate-member' ),
				'value' => UM()->options()->get( 'account_hide_in_directory_default' ),
			);
		}

		// Uploads settings
		$profile_sizes_list = '';
		$profile_sizes      = UM()->options()->get( 'photo_thumb_sizes' );
		if ( ! empty( $profile_sizes ) ) {
			foreach ( $profile_sizes as $size ) {
				$profile_sizes_list = empty( $profile_sizes_list ) ? $size : $profile_sizes_list . ', ' . $size;
			}
		}
		$cover_sizes_list = '';
		$cover_sizes      = UM()->options()->get( 'cover_thumb_sizes' );
		if ( ! empty( $cover_sizes ) ) {
			foreach ( $cover_sizes as $size ) {
				$cover_sizes_list = empty( $cover_sizes_list ) ? $size : $cover_sizes_list . ', ' . $size;
			}
		}
		$uploads_settings = array(
			'um-profile_photo_max_size'    => array(
				'label' => __( 'Profile Photo Maximum File Size (bytes)', 'ultimate-member' ),
				'value' => UM()->options()->get( 'profile_photo_max_size' ),
			),
			'um-cover_photo_max_size'      => array(
				'label' => __( 'Cover Photo Maximum File Size (bytes)', 'ultimate-member' ),
				'value' => UM()->options()->get( 'cover_photo_max_size' ),
			),
			'um-photo_thumb_sizes'         => array(
				'label' => __( 'Profile Photo Thumbnail Sizes (px)', 'ultimate-member' ),
				'value' => $profile_sizes_list,
			),
			'um-cover_thumb_sizes'         => array(
				'label' => __( 'Cover Photo Thumbnail Sizes (px)', 'ultimate-member' ),
				'value' => $cover_sizes_list,
			),
			'um-image_orientation_by_exif' => array(
				'label' => __( 'Change image orientation', 'ultimate-member' ),
				'value' => UM()->options()->get( 'image_orientation_by_exif' ) ? $labels['yes'] : $labels['no'],
			),
			'um-image_compression'         => array(
				'label' => __( 'Image Quality', 'ultimate-member' ),
				'value' => UM()->options()->get( 'image_compression' ),
			),
			'um-image_max_width'           => array(
				'label' => __( 'Image Upload Maximum Width (px)', 'ultimate-member' ),
				'value' => UM()->options()->get( 'image_max_width' ),
			),
			'um-cover_min_width'           => array(
				'label' => __( 'Cover Photo Minimum Width (px)', 'ultimate-member' ),
				'value' => UM()->options()->get( 'cover_min_width' ),
			),
		);

		// Content Restriction settings
		$restricted_posts      = UM()->options()->get( 'restricted_access_post_metabox' );
		$restricted_posts_list = '';
		if ( ! empty( $restricted_posts ) ) {
			foreach ( $restricted_posts as $key => $posts ) {
				$restricted_posts_list = empty( $restricted_posts_list ) ? $key : $restricted_posts_list . ', ' . $key;
			}
		}
		$restricted_taxonomy      = UM()->options()->get( 'restricted_access_taxonomy_metabox' );
		$restricted_taxonomy_list = '';
		if ( ! empty( $restricted_taxonomy ) ) {
			foreach ( $restricted_taxonomy as $key => $posts ) {
				$restricted_taxonomy_list = empty( $restricted_taxonomy_list ) ? $key : $restricted_taxonomy_list . ', ' . $key;
			}
		}

		$accessible = absint( UM()->options()->get( 'accessible' ) );

		$restrict_settings = array(
			'um-accessible' => array(
				'label' => __( 'Global Site Access', 'ultimate-member' ),
				'value' => 0 === $accessible ? __( 'Site accessible to Everyone', 'ultimate-member' ) : __( 'Site accessible to Logged In Users', 'ultimate-member' ),
			),
		);

		if ( 2 === $accessible ) {
			$exclude_uris      = UM()->options()->get( 'access_exclude_uris' );
			$exclude_uris_list = '';
			if ( ! empty( $exclude_uris ) ) {
				$exclude_uris_list = implode( ', ', $exclude_uris );
			}
			$restrict_settings['um-access_redirect']          = array(
				'label' => __( 'Custom Redirect URL', 'ultimate-member' ),
				'value' => UM()->options()->get( 'access_redirect' ),
			);
			$restrict_settings['um-access_exclude_uris']      = array(
				'label' => __( 'Exclude the following URLs', 'ultimate-member' ),
				'value' => $exclude_uris_list,
			);
			$restrict_settings['um-home_page_accessible']     = array(
				'label' => __( 'Allow Homepage to be accessible', 'ultimate-member' ),
				'value' => UM()->options()->get( 'home_page_accessible' ) ? $labels['yes'] : $labels['no'],
			);
			$restrict_settings['um-category_page_accessible'] = array(
				'label' => __( 'Allow Category pages to be accessible', 'ultimate-member' ),
				'value' => UM()->options()->get( 'category_page_accessible' ) ? $labels['yes'] : $labels['no'],
			);
		}

		$restrict_settings['um-restricted_post_title_replace'] = array(
			'label' => __( 'Restricted Content Titles', 'ultimate-member' ),
			'value' => UM()->options()->get( 'restricted_post_title_replace' ) ? $labels['yes'] : $labels['no'],
		);
		if ( UM()->options()->get( 'restricted_post_title_replace' ) ) {
			$restrict_settings['um-restricted_access_post_title'] = array(
				'label' => __( 'Restricted Content Title Text', 'ultimate-member' ),
				'value' => stripslashes( UM()->options()->get( 'restricted_access_post_title' ) ),
			);
		}

		$restrict_settings['um-restricted_access_message'] = array(
			'label' => __( 'Restricted Access Message', 'ultimate-member' ),
			'value' => stripslashes( UM()->options()->get( 'restricted_access_message' ) ),
		);
		$restrict_settings['um-restricted_blocks']         = array(
			'label' => __( 'Enable the "Content Restriction" settings for the Gutenberg Blocks', 'ultimate-member' ),
			'value' => UM()->options()->get( 'restricted_blocks' ) ? $labels['yes'] : $labels['no'],
		);
		if ( UM()->options()->get( 'restricted_blocks' ) ) {
			$restrict_settings['um-restricted_block_message'] = array(
				'label' => __( 'Restricted Access Block Message', 'ultimate-member' ),
				'value' => stripslashes( UM()->options()->get( 'restricted_block_message' ) ),
			);
		}
		$restrict_settings['um-restricted_access_post_metabox']     = array(
			'label' => __( 'Enable the "Content Restriction" settings for post types', 'ultimate-member' ),
			'value' => $restricted_posts_list,
		);
		$restrict_settings['um-restricted_access_taxonomy_metabox'] = array(
			'label' => __( 'Enable the "Content Restriction" settings for taxonomies', 'ultimate-member' ),
			'value' => $restricted_taxonomy_list,
		);

		// Access other settings
		$blocked_emails    = str_replace( '<br />', ', ', nl2br( UM()->options()->get( 'blocked_emails' ) ) );
		$blocked_words     = str_replace( '<br />', ', ', nl2br( UM()->options()->get( 'blocked_words' ) ) );
		$allowed_callbacks = str_replace( '<br />', ', ', nl2br( UM()->options()->get( 'allowed_choice_callbacks' ) ) );

		$access_other_settings = array(
			'um-enable_reset_password_limit' => array(
				'label' => __( 'Enable the Reset Password Limit?', 'ultimate-member' ),
				'value' => UM()->options()->get( 'enable_reset_password_limit' ) ? $labels['yes'] : $labels['no'],
			),
		);
		if ( UM()->options()->get( 'enable_reset_password_limit' ) ) {
			$access_other_settings['um-reset_password_limit_number'] = array(
				'label' => __( 'Reset Password Limit ', 'ultimate-member' ),
				'value' => UM()->options()->get( 'reset_password_limit_number' ),
			);
		}
		$access_other_settings['um-change_password_request_limit']     = array(
			'label' => __( 'Change Password request limit ', 'ultimate-member' ),
			'value' => UM()->options()->get( 'change_password_request_limit' ),
		);
		$access_other_settings['um-only_approved_user_reset_password'] = array(
			'label' => __( 'Only approved user Reset Password', 'ultimate-member' ),
			'value' => UM()->options()->get( 'only_approved_user_reset_password' ),
		);
		$access_other_settings['um-blocked_emails']                    = array(
			'label' => __( 'Blocked Email Addresses', 'ultimate-member' ),
			'value' => stripslashes( $blocked_emails ),
		);
		$access_other_settings['um-blocked_words']                     = array(
			'label' => __( 'Blacklist Words', 'ultimate-member' ),
			'value' => stripslashes( $blocked_words ),
		);
		$access_other_settings['um-allowed_choice_callbacks']          = array(
			'label' => __( 'Allowed Choice Callbacks', 'ultimate-member' ),
			'value' => stripslashes( $allowed_callbacks ),
		);
		$access_other_settings['um-allow_url_redirect_confirm']        = array(
			'label' => __( 'Allow external link redirect confirm ', 'ultimate-member' ),
			'value' => UM()->options()->get( 'allow_url_redirect_confirm' ) ? $labels['yes'] : $labels['no'],
		);

		// Email settings
		$email_settings = array(
			'um-admin_email'    => array(
				'label' => __( 'Admin Email Address', 'ultimate-member' ),
				'value' => UM()->options()->get( 'admin_email' ),
			),
			'um-mail_from'      => array(
				'label' => __( 'Mail appears from', 'ultimate-member' ),
				'value' => UM()->options()->get( 'mail_from' ),
			),
			'um-mail_from_addr' => array(
				'label' => __( 'Mail appears from address', 'ultimate-member' ),
				'value' => UM()->options()->get( 'mail_from_addr' ),
			),
			'um-email_html'     => array(
				'label' => __( 'Use HTML for Emails?', 'ultimate-member' ),
				'value' => UM()->options()->get( 'email_html' ) ? $labels['yes'] : $labels['no'],
			),
		);

		$emails = UM()->config()->email_notifications;
		foreach ( $emails as $key => $email ) {
			if ( UM()->options()->get( $key . '_on' ) ) {
				$email_settings[ 'um-' . $key ] = array(
					// translators: %s is email template title.
					'label' => sprintf( __( '"%s" Subject', 'ultimate-member' ), $email['title'] ),
					'value' => UM()->options()->get( $key . '_sub' ),
				);

				$email_settings[ 'um-theme_' . $key ] = array(
					// translators: %s is email template title.
					'label' => sprintf( __( 'Template "%s" in theme?', 'ultimate-member' ), $email['title'] ),
					'value' => '' !== locate_template( array( 'ultimate-member/emails/' . $key . '.php' ) ) ? $labels['yes'] : $labels['no'],
				);
			}
		}

		// Appearance settings.
		// > Profile section.
		$icons_display_options       = array(
			'field' => __( 'Show inside text field', 'ultimate-member' ),
			'label' => __( 'Show with label', 'ultimate-member' ),
			'off'   => __( 'Turn off', 'ultimate-member' ),
		);
		$profile_header_menu_options = array(
			'bc' => __( 'Bottom of Icon', 'ultimate-member' ),
			'lc' => __( 'Left of Icon (right for RTL)', 'ultimate-member' ),
		);

		$profile_templates      = UM()->shortcodes()->get_templates( 'profile' );
		$profile_template_key   = UM()->options()->get( 'profile_template' );
		$profile_template_title = array_key_exists( $profile_template_key, $profile_templates ) ? $profile_templates[ $profile_template_key ] : __( 'No template name', 'ultimate-member' );
		$profile_secondary_btn  = UM()->options()->get( 'profile_secondary_btn' );
		$profile_cover_enabled  = UM()->options()->get( 'profile_cover_enabled' );
		$profile_empty_text     = UM()->options()->get( 'profile_empty_text' );

		$appearance_settings = array(
			'um-profile_template'         => array(
				'label' => __( 'Profile Default Template', 'ultimate-member' ),
				// translators: %1$s - profile template name, %2$s - profile template filename
				'value' => sprintf( __( '%1$s (filename: %2$s.php)', 'ultimate-member' ), $profile_template_title, $profile_template_key ),
			),
			'um-profile_max_width'        => array(
				'label' => __( 'Profile Maximum Width', 'ultimate-member' ),
				'value' => UM()->options()->get( 'profile_max_width' ),
			),
			'um-profile_area_max_width'   => array(
				'label' => __( 'Profile Area Maximum Width', 'ultimate-member' ),
				'value' => UM()->options()->get( 'profile_area_max_width' ),
			),
			'um-profile_icons'            => array(
				'label' => __( 'Profile Field Icons', 'ultimate-member' ),
				'value' => $icons_display_options[ UM()->options()->get( 'profile_icons' ) ],
			),
			'um-profile_primary_btn_word' => array(
				'label' => __( 'Profile Primary Button Text', 'ultimate-member' ),
				'value' => UM()->options()->get( 'profile_primary_btn_word' ),
			),
			'um-profile_secondary_btn'    => array(
				'label' => __( 'Profile Secondary Button', 'ultimate-member' ),
				'value' => $profile_secondary_btn ? $labels['yes'] : $labels['no'],
			),
		);
		if ( ! empty( $profile_secondary_btn ) ) {
			$appearance_settings['um-profile_secondary_btn_word'] = array(
				'label' => __( 'Profile Secondary Button Text ', 'ultimate-member' ),
				'value' => UM()->options()->get( 'profile_secondary_btn_word' ),
			);
		}

		$default_avatar = UM()->options()->get( 'default_avatar' );
		$default_cover  = UM()->options()->get( 'default_cover' );

		$appearance_settings['um-default_avatar']               = array(
			'label' => __( 'Default Profile Photo', 'ultimate-member' ),
			'value' => ! empty( $default_avatar['url'] ) ? $default_avatar['url'] : '',
		);
		$appearance_settings['um-default_cover']                = array(
			'label' => __( 'Default Cover Photo', 'ultimate-member' ),
			'value' => ! empty( $default_cover['url'] ) ? $default_cover['url'] : '',
		);
		$appearance_settings['um-disable_profile_photo_upload'] = array(
			'label' => __( 'Disable Profile Photo Upload', 'ultimate-member' ),
			'value' => UM()->options()->get( 'disable_profile_photo_upload' ) ? $labels['yes'] : $labels['no'],
		);
		$appearance_settings['um-profile_photosize']            = array(
			'label' => __( 'Profile Photo Size', 'ultimate-member' ),
			'value' => UM()->options()->get( 'profile_photosize' ) . 'x' . UM()->options()->get( 'profile_photosize' ) . 'px',
		);
		$appearance_settings['um-profile_cover_enabled']        = array(
			'label' => __( 'Profile Cover Photos', 'ultimate-member' ),
			'value' => $profile_cover_enabled ? $labels['yes'] : $labels['no'],
		);
		if ( ! empty( $profile_cover_enabled ) ) {
			$appearance_settings['um-profile_coversize']   = array(
				'label' => __( 'Profile Cover Size', 'ultimate-member' ),
				'value' => UM()->options()->get( 'profile_coversize' ) . 'px',
			);
			$appearance_settings['um-profile_cover_ratio'] = array(
				'label' => __( 'Profile Cover Ratio', 'ultimate-member' ),
				'value' => UM()->options()->get( 'profile_cover_ratio' ),
			);
		}
		$appearance_settings['um-profile_show_metaicon']     = array(
			'label' => __( 'Profile Header Meta Text Icon', 'ultimate-member' ),
			'value' => UM()->options()->get( 'profile_show_metaicon' ) ? $labels['yes'] : $labels['no'],
		);
		$appearance_settings['um-profile_show_name']         = array(
			'label' => __( 'Show display name in profile header', 'ultimate-member' ),
			'value' => UM()->options()->get( 'profile_show_name' ) ? $labels['yes'] : $labels['no'],
		);
		$appearance_settings['um-profile_show_social_links'] = array(
			'label' => __( 'Show social links in profile header', 'ultimate-member' ),
			'value' => UM()->options()->get( 'profile_show_social_links' ) ? $labels['yes'] : $labels['no'],
		);
		$appearance_settings['um-profile_show_bio']          = array(
			'label' => __( 'Show user description in header', 'ultimate-member' ),
			'value' => UM()->options()->get( 'profile_show_bio' ) ? $labels['yes'] : $labels['no'],
		);
		$appearance_settings['um-profile_show_html_bio']     = array(
			'label' => __( 'Enable HTML support for user description', 'ultimate-member' ),
			'value' => UM()->options()->get( 'profile_show_html_bio' ) ? $labels['yes'] : $labels['no'],
		);
		$appearance_settings['um-profile_bio_maxchars']      = array(
			'label' => __( 'User description maximum chars', 'ultimate-member' ),
			'value' => UM()->options()->get( 'profile_bio_maxchars' ),
		);
		$appearance_settings['um-profile_header_menu']       = array(
			'label' => __( 'Profile Header Menu Position', 'ultimate-member' ),
			'value' => $profile_header_menu_options[ UM()->options()->get( 'profile_header_menu' ) ],
		);
		$appearance_settings['um-profile_empty_text']        = array(
			'label' => __( 'Show a custom message if profile is empty', 'ultimate-member' ),
			'value' => $profile_empty_text ? $labels['yes'] : $labels['no'],
		);
		if ( ! empty( $profile_empty_text ) ) {
			$appearance_settings['um-profile_empty_text_emo'] = array(
				'label' => __( 'Show the emoticon', 'ultimate-member' ),
				'value' => UM()->options()->get( 'profile_empty_text_emo' ) ? $labels['yes'] : $labels['no'],
			);
		}

		// > Profile Menu section.
		$profile_menu = UM()->options()->get( 'profile_menu' );

		$appearance_settings['um-profile_menu'] = array(
			'label' => __( 'Enable profile menu', 'ultimate-member' ),
			'value' => $profile_menu ? $labels['yes'] : $labels['no'],
		);

		if ( ! empty( $profile_menu ) ) {
			/**
			 * Filters a privacy list extend.
			 *
			 * @since 2.7.0
			 * @hook um_profile_tabs_privacy_list
			 *
			 * @param {array} $privacy_option Add options for profile tabs' privacy.
			 *
			 * @return {array} Options for profile tabs' privacy.
			 *
			 * @example <caption>Add options for profile tabs' privacy.</caption>
			 * function um_profile_menu_link_attrs( $privacy_option ) {
			 *     // your code here
			 *     return $privacy_option;
			 * }
			 * add_filter( 'um_profile_tabs_privacy_list', 'um_profile_tabs_privacy_list', 10, 1 );
			 */
			$privacy_option = UM()->profile()->tabs_privacy();

			$tabs = UM()->profile()->tabs();
			foreach ( $tabs as $id => $tab ) {
				if ( ! empty( $tab['hidden'] ) ) {
					continue;
				}

				$tab_enabled = UM()->options()->get( 'profile_tab_' . $id );

				$appearance_settings[ 'um-profile_tab_' . $id ] = array(
					// translators: %s Profile Tab Title
					'label' => sprintf( __( '%s Tab', 'ultimate-member' ), $tab['name'] ),
					'value' => $tab_enabled ? $labels['yes'] : $labels['no'],
				);

				if ( ! isset( $tab['default_privacy'] ) && ! empty( $tab_enabled ) ) {
					$privacy = UM()->options()->get( 'profile_tab_' . $id . '_privacy' );
					if ( is_numeric( $privacy ) ) {
						$appearance_settings[ 'um-profile_tab_' . $id . '_privacy' ] = array(
							// translators: %s Profile Tab Title
							'label' => sprintf( __( 'Who can see %s Tab?', 'ultimate-member' ), $tab['name'] ),
							'value' => $privacy_option[ UM()->options()->get( 'profile_tab_' . $id . '_privacy' ) ],
						);
					}
				}
			}
			/**
			 * Filters appearance settings for Site Health extend.
			 *
			 * @since 2.7.0
			 * @hook um_profile_tabs_site_health
			 *
			 * @param {array} $appearance_settings Appearance settings for Site Health.
			 *
			 * @return {array} Appearance settings for Site Health.
			 *
			 * @example <caption>Add options for appearance settings for Site Health.</caption>
			 * function um_profile_tabs_site_health( $appearance_settings ) {
			 *     // your code here
			 *     return $appearance_settings;
			 * }
			 * add_filter( 'um_profile_tabs_site_health', 'um_profile_tabs_site_health', 10, 1 );
			 */
			$appearance_settings = apply_filters( 'um_profile_tabs_site_health', $appearance_settings );

			/**
			 * Filters user profile tabs
			 *
			 * @since 2.7.0
			 * @hook um_profile_tabs
			 *
			 * @param {array} $tabs tabs list.
			 *
			 * @return {array} tabs list.
			 *
			 * @example <caption>Add options for profile tabs' privacy.</caption>
			 * function um_profile_tabs( $tabs ) {
			 *     // your code here
			 *     return $tabs;
			 * }
			 * add_filter( 'um_profile_tabs', 'um_profile_tabs', 10, 1 );
			 */
			$tabs_options = apply_filters(
				'um_profile_tabs',
				array(
					'main'     => array(
						'name' => __( 'About', 'ultimate-member' ),
						'icon' => 'um-faicon-user',
					),
					'posts'    => array(
						'name' => __( 'Posts', 'ultimate-member' ),
						'icon' => 'um-faicon-pencil',
					),
					'comments' => array(
						'name' => __( 'Comments', 'ultimate-member' ),
						'icon' => 'um-faicon-comment',
					),
				)
			);

			$appearance_settings['um-profile_menu_default_tab'] = array(
				'label' => __( 'Profile menu default tab', 'ultimate-member' ),
				'value' => $tabs_options[ UM()->options()->get( 'profile_menu_default_tab' ) ],
			);
			$appearance_settings['um-profile_menu_icons']       = array(
				'label' => __( 'Enable menu icons in desktop view', 'ultimate-member' ),
				'value' => UM()->options()->get( 'profile_menu_icons' ) ? $labels['yes'] : $labels['no'],
			);
		}

		// > Registration Form section.
		$register_templates      = UM()->shortcodes()->get_templates( 'register' );
		$register_template_key   = UM()->options()->get( 'register_template' );
		$register_template_title = array_key_exists( $register_template_key, $register_templates ) ? $register_templates[ $register_template_key ] : __( 'No template name', 'ultimate-member' );
		$register_secondary_btn  = UM()->options()->get( 'register_secondary_btn' );

		$form_align_options = array(
			'center' => __( 'Centered', 'ultimate-member' ),
			'left'   => __( 'Left aligned', 'ultimate-member' ),
			'right'  => __( 'Right aligned', 'ultimate-member' ),
		);

		$appearance_settings['um-register_template']         = array(
			'label' => __( 'Registration Default Template', 'ultimate-member' ),
			// translators: %1$s - register template name, %2$s - register template filename
			'value' => sprintf( __( '%1$s (filename: %2$s.php)', 'ultimate-member' ), $register_template_title, $register_template_key ),
		);
		$appearance_settings['um-register_max_width']        = array(
			'label' => __( 'Registration Maximum Width', 'ultimate-member' ),
			'value' => UM()->options()->get( 'register_max_width' ),
		);
		$appearance_settings['um-register_align']            = array(
			'label' => __( 'Registration Shortcode Alignment', 'ultimate-member' ),
			'value' => $form_align_options[ UM()->options()->get( 'register_align' ) ],
		);
		$appearance_settings['um-register_icons']            = array(
			'label' => __( 'Registration Field Icons', 'ultimate-member' ),
			'value' => $icons_display_options[ UM()->options()->get( 'register_icons' ) ],
		);
		$appearance_settings['um-register_primary_btn_word'] = array(
			'label' => __( 'Registration Primary Button Text ', 'ultimate-member' ),
			'value' => UM()->options()->get( 'register_primary_btn_word' ),
		);
		$appearance_settings['um-register_secondary_btn']    = array(
			'label' => __( 'Registration Secondary Button', 'ultimate-member' ),
			'value' => $register_secondary_btn ? $labels['yes'] : $labels['no'],
		);
		if ( ! empty( $register_secondary_btn ) ) {
			$appearance_settings['um-register_secondary_btn_word'] = array(
				'label' => __( 'Registration Secondary Button Text', 'ultimate-member' ),
				'value' => UM()->options()->get( 'register_secondary_btn_word' ),
			);
			$appearance_settings['um-register_secondary_btn_url']  = array(
				'label' => __( 'Registration Secondary Button URL', 'ultimate-member' ),
				'value' => UM()->options()->get( 'register_secondary_btn_url' ),
			);
		}
		$appearance_settings['um-register_role'] = array(
			'label' => __( 'Registration Default Role', 'ultimate-member' ),
			'value' => ! empty( UM()->options()->get( 'register_role' ) ) ? UM()->options()->get( 'register_role' ) : __( 'Default', 'ultimate-member' ),
		);

		// > Login Form section.
		$login_templates      = UM()->shortcodes()->get_templates( 'login' );
		$login_template_key   = UM()->options()->get( 'login_template' );
		$login_template_title = array_key_exists( $login_template_key, $login_templates ) ? $login_templates[ $login_template_key ] : __( 'No template name', 'ultimate-member' );
		$login_secondary_btn  = UM()->options()->get( 'login_secondary_btn' );

		$appearance_settings['um-login_template']         = array(
			'label' => __( 'Login Default Template', 'ultimate-member' ),
			// translators: %1$s - login template name, %2$s - login template filename
			'value' => sprintf( __( '%1$s (filename: %2$s.php)', 'ultimate-member' ), $login_template_title, $login_template_key ),
		);
		$appearance_settings['um-login_max_width']        = array(
			'label' => __( 'Login Maximum Width', 'ultimate-member' ),
			'value' => UM()->options()->get( 'login_max_width' ),
		);
		$appearance_settings['um-login_align']            = array(
			'label' => __( 'Login Shortcode Alignment', 'ultimate-member' ),
			'value' => $form_align_options[ UM()->options()->get( 'login_align' ) ],
		);
		$appearance_settings['um-login_icons']            = array(
			'label' => __( 'Login Field Icons', 'ultimate-member' ),
			'value' => $icons_display_options[ UM()->options()->get( 'login_icons' ) ],
		);
		$appearance_settings['um-login_primary_btn_word'] = array(
			'label' => __( 'Login Primary Button Text', 'ultimate-member' ),
			'value' => UM()->options()->get( 'login_primary_btn_word' ),
		);
		$appearance_settings['um-login_secondary_btn']    = array(
			'label' => __( 'Login Secondary Button', 'ultimate-member' ),
			'value' => $login_secondary_btn ? $labels['yes'] : $labels['no'],
		);
		if ( ! empty( $login_secondary_btn ) ) {
			$appearance_settings['um-login_secondary_btn_word'] = array(
				'label' => __( 'Login Secondary Button Text', 'ultimate-member' ),
				'value' => UM()->options()->get( 'login_secondary_btn_word' ),
			);
			$appearance_settings['um-login_secondary_btn_url']  = array(
				'label' => __( 'Login Secondary Button URL', 'ultimate-member' ),
				'value' => UM()->options()->get( 'login_secondary_btn_url' ),
			);
		}
		$appearance_settings['um-login_forgot_pass_link'] = array(
			'label' => __( 'Login Forgot Password Link', 'ultimate-member' ),
			'value' => UM()->options()->get( 'login_forgot_pass_link' ) ? $labels['yes'] : $labels['no'],
		);
		$appearance_settings['um-login_show_rememberme']  = array(
			'label' => __( 'Show "Remember Me"', 'ultimate-member' ),
			'value' => UM()->options()->get( 'login_show_rememberme' ) ? $labels['yes'] : $labels['no'],
		);

		// Misc settings.
		$misc_settings = array(
			'um-form_asterisk'                   => array(
				'label' => __( 'Show an asterisk for required fields', 'ultimate-member' ),
				'value' => UM()->options()->get( 'form_asterisk' ) ? $labels['yes'] : $labels['no'],
			),
			'um-profile_title'                   => array(
				'label' => __( 'User Profile Title', 'ultimate-member' ),
				'value' => stripslashes( UM()->options()->get( 'profile_title' ) ),
			),
			'um-profile_desc'                    => array(
				'label' => __( 'User Profile Dynamic Meta Description', 'ultimate-member' ),
				'value' => stripslashes( UM()->options()->get( 'profile_desc' ) ),
			),
			'um-um_profile_object_cache_stop'    => array(
				'label' => __( 'Disable Cache User Profile', 'ultimate-member' ),
				'value' => UM()->options()->get( 'um_profile_object_cache_stop' ) ? $labels['yes'] : $labels['no'],
			),
			'um-enable_blocks'                   => array(
				'label' => __( 'Enable Gutenberg Blocks', 'ultimate-member' ),
				'value' => UM()->options()->get( 'enable_blocks' ) ? $labels['yes'] : $labels['no'],
			),
			'um-rest_api_version'                => array(
				'label' => __( 'REST API version', 'ultimate-member' ),
				'value' => UM()->options()->get( 'rest_api_version' ),
			),
			'um-disable_restriction_pre_queries' => array(
				'label' => __( 'Disable pre-queries for restriction content logic (advanced)', 'ultimate-member' ),
				'value' => UM()->options()->get( 'disable_restriction_pre_queries' ) ? $labels['yes'] : $labels['no'],
			),
			'um-member_directory_own_table'      => array(
				'label' => __( 'Enable custom table for usermeta', 'ultimate-member' ),
				'value' => UM()->options()->get( 'member_directory_own_table' ) ? $labels['yes'] : $labels['no'],
			),
			'um-uninstall_on_delete'             => array(
				'label' => __( 'Remove Data on Uninstall?', 'ultimate-member' ),
				'value' => UM()->options()->get( 'uninstall_on_delete' ) ? $labels['yes'] : $labels['no'],
			),
		);

		// Secure settings
		$secure_ban_admins_accounts = UM()->options()->get( 'secure_ban_admins_accounts' );

		$banned_capabilities_opt = UM()->options()->get( 'banned_capabilities' );
		$banned_capabilities     = array();
		if ( ! empty( $banned_capabilities_opt ) ) {
			if ( is_string( $banned_capabilities_opt ) ) {
				$banned_capabilities = array( $banned_capabilities_opt );
			} else {
				$banned_capabilities = $banned_capabilities_opt;
			}
		}

		$secure_settings = array(
			'um-banned_capabilities'        => array(
				'label' => __( 'Banned Administrative Capabilities', 'ultimate-member' ),
				'value' => ! empty( $banned_capabilities ) ? implode( ', ', $banned_capabilities ) : '',
			),
			'um-lock_register_forms'        => array(
				'label' => __( 'Lock All Register Forms', 'ultimate-member' ),
				'value' => UM()->options()->get( 'lock_register_forms' ) ? $labels['yes'] : $labels['no'],
			),
			'um-display_login_form_notice'  => array(
				'label' => __( 'Display Login form notice to reset passwords', 'ultimate-member' ),
				'value' => UM()->options()->get( 'display_login_form_notice' ) ? $labels['yes'] : $labels['no'],
			),
			'um-secure_ban_admins_accounts' => array(
				'label' => __( 'Enable ban for administrative capabilities', 'ultimate-member' ),
				'value' => $secure_ban_admins_accounts ? $labels['yes'] : $labels['no'],
			),
		);
		if ( ! empty( $secure_ban_admins_accounts ) ) {
			$secure_notify_admins_banned_accounts = UM()->options()->get( 'secure_notify_admins_banned_accounts' );

			$secure_settings['um-secure_notify_admins_banned_accounts'] = array(
				'label' => __( 'Notify Administrators', 'ultimate-member' ),
				'value' => $secure_notify_admins_banned_accounts ? $labels['yes'] : $labels['no'],
			);
			if ( ! empty( $secure_notify_admins_banned_accounts ) ) {
				$secure_notify_admins_banned_accounts_options = array(
					'instant' => __( 'Send Immediately', 'ultimate-member' ),
					'hourly'  => __( 'Hourly', 'ultimate-member' ),
					'daily'   => __( 'Daily', 'ultimate-member' ),
				);

				$secure_settings['um-secure_notify_admins_banned_accounts__interval'] = array(
					'label' => __( 'Notification Schedule', 'ultimate-member' ),
					'value' => $secure_notify_admins_banned_accounts_options[ UM()->options()->get( 'secure_notify_admins_banned_accounts__interval' ) ],
				);
			}
		}

		$secure_allowed_redirect_hosts = UM()->options()->get( 'secure_allowed_redirect_hosts' );
		$secure_allowed_redirect_hosts = explode( PHP_EOL, $secure_allowed_redirect_hosts );

		$secure_settings['um-secure_allowed_redirect_hosts'] = array(
			'label' => __( 'Allowed hosts for safe redirect', 'ultimate-member' ),
			'value' => $secure_allowed_redirect_hosts,
		);

		// Licenses settings.
		$license_settings = array(
			'um-licenses' => array(
				'label' => __( 'Licenses', 'ultimate-member' ),
				'value' => array(),
			),
		);

		/**
		 * Filters licenses settings for Site Health.
		 *
		 * @since 2.7.0
		 * @hook um_licenses_site_health
		 *
		 * @param {array} $license_settings licenses settings for Site Health.
		 *
		 * @return {array} licenses settings for Site Health.
		 *
		 * @example <caption>Extend licenses settings for Site Health.</caption>
		 * function um_licenses_site_health( $license_settings ) {
		 *     // your code here
		 *     return $license_settings;
		 * }
		 * add_filter( 'um_licenses_site_health', 'um_licenses_site_health', 10, 1 );
		 */
		$license_settings = apply_filters( 'um_licenses_site_health', $license_settings );

		$info['ultimate-member']['fields'] = array_merge( $info['ultimate-member']['fields'], $pages_settings, $user_settings, $account_settings, $uploads_settings, $restrict_settings, $access_other_settings, $email_settings, $appearance_settings, $misc_settings, $secure_settings, $license_settings );

		// User roles settings
		$roles_array = array();
		foreach ( $this->get_roles() as $key => $role ) {
			if ( strpos( $key, 'um_' ) === 0 ) {
				$key = substr( $key, 3 );
			}
			$rolemeta = $this->get_role_meta( $key );
			if ( false === $rolemeta ) {
				continue;
			}
			$priority = ! empty( $rolemeta['_um_priority'] ) ? $rolemeta['_um_priority'] : 0;

			$k                 = $priority . '-' . $role;
			$roles_array[ $k ] = $role . '(' . $priority . ')';

			krsort( $roles_array, SORT_NUMERIC );
		}

		$info['ultimate-member-user-roles'] = array(
			'label'       => __( 'User roles', 'ultimate-member' ),
			'description' => __( 'This debug information about user roles.', 'ultimate-member' ),
			'fields'      => array(
				'um-roles'         => array(
					'label' => __( 'User Roles (priority)', 'ultimate-member' ),
					'value' => implode( ', ', $roles_array ),
				),
				'um-register_role' => array(
					'label' => __( 'WordPress Default New User Role', 'ultimate-member' ),
					'value' => get_option( 'default_role' ),
				),
			),
		);

		foreach ( $this->get_roles() as $key => $role ) {
			if ( strpos( $key, 'um_' ) === 0 ) {
				$key = substr( $key, 3 );
			}

			$rolemeta = $this->get_role_meta( $key );
			if ( false === $rolemeta ) {
				continue;
			}

			$info[ 'ultimate-member-' . $key ] = array(
				'label'       => ' - ' . $role . __( ' role settings', 'ultimate-member' ),
				'description' => __( 'This debug information about user role.', 'ultimate-member' ),
				'fields'      => array(),
			);

			if ( array_key_exists( '_um_can_access_wpadmin', $rolemeta ) ) {
				$info[ 'ultimate-member-' . $key ]['fields'] = array_merge(
					$info[ 'ultimate-member-' . $key ]['fields'],
					array(
						'um-can_access_wpadmin' => array(
							'label' => __( 'Can access wp-admin?', 'ultimate-member' ),
							'value' => $rolemeta['_um_can_access_wpadmin'] ? $labels['yes'] : $labels['no'],
						),
					)
				);
			}

			if ( array_key_exists( '_um_can_not_see_adminbar', $rolemeta ) ) {
				$info[ 'ultimate-member-' . $key ]['fields'] = array_merge(
					$info[ 'ultimate-member-' . $key ]['fields'],
					array(
						'um-can_not_see_adminbar' => array(
							'label' => __( 'Force hiding adminbar in frontend?', 'ultimate-member' ),
							'value' => $rolemeta['_um_can_not_see_adminbar'] ? $labels['yes'] : $labels['no'],
						),
					)
				);
			}

			if ( array_key_exists( '_um_can_edit_everyone', $rolemeta ) ) {
				$info[ 'ultimate-member-' . $key ]['fields'] = array_merge(
					$info[ 'ultimate-member-' . $key ]['fields'],
					array(
						'um-can_edit_everyone' => array(
							'label' => __( 'Can edit other member accounts?', 'ultimate-member' ),
							'value' => $rolemeta['_um_can_edit_everyone'] ? $labels['yes'] : $labels['no'],
						),
					)
				);
			}

			if ( array_key_exists( '_um_can_edit_everyone', $rolemeta ) && 1 === absint( $rolemeta['_um_can_edit_everyone'] ) ) {
				$can_edit_roles_meta = ! empty( $rolemeta['_um_can_edit_roles'] ) ? $rolemeta['_um_can_edit_roles'] : array();
				$can_edit_roles      = array();
				if ( ! empty( $can_edit_roles_meta ) ) {
					if ( is_string( $can_edit_roles_meta ) ) {
						$can_edit_roles = array( $can_edit_roles_meta );
					} else {
						$can_edit_roles = $can_edit_roles_meta;
					}
				}

				$info[ 'ultimate-member-' . $key ]['fields'] = array_merge(
					$info[ 'ultimate-member-' . $key ]['fields'],
					array(
						'um-can_edit_roles' => array(
							'label' => __( 'Can edit these user roles only', 'ultimate-member' ),
							'value' => ! empty( $can_edit_roles ) ? implode( ', ', $can_edit_roles ) : $labels['all'],
						),
					)
				);
			}

			if ( array_key_exists( '_um_can_delete_everyone', $rolemeta ) ) {
				$info[ 'ultimate-member-' . $key ]['fields'] = array_merge(
					$info[ 'ultimate-member-' . $key ]['fields'],
					array(
						'um-can_delete_everyone' => array(
							'label' => __( 'Can delete other member accounts?', 'ultimate-member' ),
							'value' => $rolemeta['_um_can_delete_everyone'] ? $labels['yes'] : $labels['no'],
						),
					)
				);
			}

			if ( array_key_exists( '_um_can_delete_everyone', $rolemeta ) && 1 === absint( $rolemeta['_um_can_delete_everyone'] ) ) {
				$can_delete_roles_meta = ! empty( $rolemeta['_um_can_delete_roles'] ) ? $rolemeta['_um_can_delete_roles'] : array();
				$can_delete_roles      = array();
				if ( ! empty( $can_delete_roles_meta ) ) {
					if ( is_string( $can_delete_roles_meta ) ) {
						$can_delete_roles = array( $can_delete_roles_meta );
					} else {
						$can_delete_roles = $can_delete_roles_meta;
					}
				}

				$info[ 'ultimate-member-' . $key ]['fields'] = array_merge(
					$info[ 'ultimate-member-' . $key ]['fields'],
					array(
						'um-can_delete_roles' => array(
							'label' => __( 'Can delete these user roles only', 'ultimate-member' ),
							'value' => ! empty( $can_delete_roles ) ? implode( ', ', $can_delete_roles ) : $labels['all'],
						),
					)
				);
			}

			if ( array_key_exists( '_um_can_edit_profile', $rolemeta ) ) {
				$info[ 'ultimate-member-' . $key ]['fields'] = array_merge(
					$info[ 'ultimate-member-' . $key ]['fields'],
					array(
						'um-can_edit_profile' => array(
							'label' => __( 'Can edit their profile?', 'ultimate-member' ),
							'value' => $rolemeta['_um_can_edit_profile'] ? $labels['yes'] : $labels['no'],
						),
					)
				);
			}

			if ( array_key_exists( '_um_can_delete_profile', $rolemeta ) ) {
				$info[ 'ultimate-member-' . $key ]['fields'] = array_merge(
					$info[ 'ultimate-member-' . $key ]['fields'],
					array(
						'um-can_delete_profile' => array(
							'label' => __( 'Can delete their account?', 'ultimate-member' ),
							'value' => $rolemeta['_um_can_delete_profile'] ? $labels['yes'] : $labels['no'],
						),
					)
				);
			}

			if ( array_key_exists( '_um_can_view_all', $rolemeta ) ) {
				$info[ 'ultimate-member-' . $key ]['fields'] = array_merge(
					$info[ 'ultimate-member-' . $key ]['fields'],
					array(
						'um-can_view_all' => array(
							'label' => __( 'Can view other member profiles?', 'ultimate-member' ),
							'value' => $rolemeta['_um_can_view_all'] ? $labels['yes'] : $labels['no'],
						),
					)
				);
			}

			if ( array_key_exists( '_um_can_view_all', $rolemeta ) && 1 === absint( $rolemeta['_um_can_view_all'] ) ) {
				$can_view_roles_meta = ! empty( $rolemeta['_um_can_view_roles'] ) ? $rolemeta['_um_can_view_roles'] : array();
				$can_view_roles      = array();
				if ( ! empty( $can_view_roles_meta ) ) {
					if ( is_string( $can_view_roles_meta ) ) {
						$can_view_roles = array( $can_view_roles_meta );
					} else {
						$can_view_roles = $can_view_roles_meta;
					}
				}

				$info[ 'ultimate-member-' . $key ]['fields'] = array_merge(
					$info[ 'ultimate-member-' . $key ]['fields'],
					array(
						'um-can_view_roles' => array(
							'label' => __( 'Can view these user roles only', 'ultimate-member' ),
							'value' => ! empty( $can_view_roles ) ? implode( ', ', $can_view_roles ) : $labels['all'],
						),
					)
				);
			}

			if ( isset( $rolemeta['_um_profile_noindex'] ) && '' !== $rolemeta['_um_profile_noindex'] ) {
				$profile_noindex = $rolemeta['_um_profile_noindex'] ? $labels['yes'] : $labels['no'];
			} else {
				$profile_noindex = __( 'Default', 'ultimate-member' );
			}
			if ( isset( $rolemeta['_um_default_homepage'] ) && '' !== $rolemeta['_um_default_homepage'] ) {
				$default_homepage = $rolemeta['_um_default_homepage'] ? $labels['yes'] : $labels['no'];
			} else {
				$default_homepage = __( 'No such option', 'ultimate-member' );
			}

			if ( array_key_exists( '_um_can_make_private_profile', $rolemeta ) ) {
				$info[ 'ultimate-member-' . $key ]['fields'] = array_merge(
					$info[ 'ultimate-member-' . $key ]['fields'],
					array(
						'um-can_make_private_profile' => array(
							'label' => __( 'Can make their profile private?', 'ultimate-member' ),
							'value' => $rolemeta['_um_can_make_private_profile'] ? $labels['yes'] : $labels['no'],
						),
					)
				);
			}

			if ( array_key_exists( '_um_can_access_private_profile', $rolemeta ) ) {
				$info[ 'ultimate-member-' . $key ]['fields'] = array_merge(
					$info[ 'ultimate-member-' . $key ]['fields'],
					array(
						'um-can_access_private_profile' => array(
							'label' => __( 'Can view/access private profiles?', 'ultimate-member' ),
							'value' => $rolemeta['_um_can_access_private_profile'] ? $labels['yes'] : $labels['no'],
						),
					)
				);
			}

			$info[ 'ultimate-member-' . $key ]['fields'] = array_merge(
				$info[ 'ultimate-member-' . $key ]['fields'],
				array(
					'um-profile_noindex'  => array(
						'label' => __( 'Avoid indexing profile by search engines', 'ultimate-member' ),
						'value' => $profile_noindex,
					),
					'um-default_homepage' => array(
						'label' => __( 'Can view default homepage?', 'ultimate-member' ),
						'value' => $default_homepage,
					),
				)
			);

			if ( isset( $rolemeta['_um_default_homepage'] ) && 0 === absint( $rolemeta['_um_default_homepage'] ) ) {
				$info[ 'ultimate-member-' . $key ]['fields'] = array_merge(
					$info[ 'ultimate-member-' . $key ]['fields'],
					array(
						'um-redirect_homepage' => array(
							'label' => __( 'Custom Homepage Redirect', 'ultimate-member' ),
							'value' => $rolemeta['_um_redirect_homepage'],
						),
					)
				);
			}

			$status_options = array(
				'approved'  => __( 'Auto Approve', 'ultimate-member' ),
				'checkmail' => __( 'Require Email Activation', 'ultimate-member' ),
				'pending'   => __( 'Require Admin Review', 'ultimate-member' ),
			);

			if ( array_key_exists( '_um_status', $rolemeta ) && isset( $status_options[ $rolemeta['_um_status'] ] ) ) {
				$info[ 'ultimate-member-' . $key ]['fields'] = array_merge(
					$info[ 'ultimate-member-' . $key ]['fields'],
					array(
						'um-status' => array(
							'label' => __( 'Registration Status', 'ultimate-member' ),
							'value' => $status_options[ $rolemeta['_um_status'] ],
						),
					)
				);
			}

			if ( array_key_exists( '_um_status', $rolemeta ) && 'approved' === $rolemeta['_um_status'] ) {
				$auto_approve_act = array(
					'redirect_profile' => __( 'Redirect to profile', 'ultimate-member' ),
					'redirect_url'     => __( 'Redirect to URL', 'ultimate-member' ),
				);

				if ( isset( $auto_approve_act[ $rolemeta['_um_auto_approve_act'] ] ) ) {
					$info[ 'ultimate-member-' . $key ]['fields'] = array_merge(
						$info[ 'ultimate-member-' . $key ]['fields'],
						array(
							'um-auto_approve_act' => array(
								'label' => __( 'Custom Homepage Redirect', 'ultimate-member' ),
								'value' => $auto_approve_act[ $rolemeta['_um_auto_approve_act'] ],
							),
						)
					);
				}

				if ( 'redirect_url' === $rolemeta['_um_auto_approve_act'] && array_key_exists( '_um_auto_approve_url', $rolemeta ) ) {
					$info[ 'ultimate-member-' . $key ]['fields'] = array_merge(
						$info[ 'ultimate-member-' . $key ]['fields'],
						array(
							'um-auto_approve_url' => array(
								'label' => __( 'Set Custom Redirect URL', 'ultimate-member' ),
								'value' => $rolemeta['_um_auto_approve_url'],
							),
						)
					);
				}
			}

			if ( array_key_exists( '_um_status', $rolemeta ) && 'checkmail' === $rolemeta['_um_status'] ) {
				$checkmail_action = array(
					'show_message' => __( 'Show custom message', 'ultimate-member' ),
					'redirect_url' => __( 'Redirect to URL', 'ultimate-member' ),
				);

				if ( array_key_exists( '_um_login_email_activate', $rolemeta ) ) {
					$info[ 'ultimate-member-' . $key ]['fields'] = array_merge(
						$info[ 'ultimate-member-' . $key ]['fields'],
						array(
							'um-login_email_activate' => array(
								'label' => __( 'Login user after validating the activation link?', 'ultimate-member' ),
								'value' => $rolemeta['_um_login_email_activate'] ? $labels['yes'] : $labels['no'],
							),
						)
					);
				}

				if ( isset( $checkmail_action[ $rolemeta['_um_checkmail_action'] ] ) ) {
					$info[ 'ultimate-member-' . $key ]['fields'] = array_merge(
						$info[ 'ultimate-member-' . $key ]['fields'],
						array(
							'um-checkmail_action' => array(
								'label' => __( 'Action to be taken after registration', 'ultimate-member' ),
								'value' => $checkmail_action[ $rolemeta['_um_checkmail_action'] ],
							),
						)
					);
				}

				if ( 'show_message' === $rolemeta['_um_checkmail_action'] ) {
					if ( array_key_exists( '_um_checkmail_message', $rolemeta ) ) {
						$info[ 'ultimate-member-' . $key ]['fields'] = array_merge(
							$info[ 'ultimate-member-' . $key ]['fields'],
							array(
								'um-checkmail_message' => array(
									'label' => __( 'Personalize the custom message', 'ultimate-member' ),
									'value' => stripslashes( $rolemeta['_um_checkmail_message'] ),
								),
							)
						);
					}
				} else {
					if ( array_key_exists( '_um_checkmail_url', $rolemeta ) ) {
						$info[ 'ultimate-member-' . $key ]['fields'] = array_merge(
							$info[ 'ultimate-member-' . $key ]['fields'],
							array(
								'um-checkmail_url' => array(
									'label' => __( 'Set Custom Redirect URL', 'ultimate-member' ),
									'value' => $rolemeta['_um_checkmail_url'],
								),
							)
						);
					}
				}

				if ( array_key_exists( '_um_url_email_activate', $rolemeta ) ) {
					$info[ 'ultimate-member-' . $key ]['fields'] = array_merge(
						$info[ 'ultimate-member-' . $key ]['fields'],
						array(
							'um-url_email_activate' => array(
								'label' => __( 'URL redirect after email activation', 'ultimate-member' ),
								'value' => $rolemeta['_um_url_email_activate'],
							),
						)
					);
				}
			}

			if ( array_key_exists( '_um_status', $rolemeta ) && 'pending' === $rolemeta['_um_status'] ) {
				$pending_action = array(
					'show_message' => __( 'Show custom message', 'ultimate-member' ),
					'redirect_url' => __( 'Redirect to URL', 'ultimate-member' ),
				);

				if ( array_key_exists( '_um_pending_action', $rolemeta ) ) {
					$info[ 'ultimate-member-' . $key ]['fields'] = array_merge(
						$info[ 'ultimate-member-' . $key ]['fields'],
						array(
							'um-pending_action' => array(
								'label' => __( 'Action to be taken after registration', 'ultimate-member' ),
								'value' => $pending_action[ $rolemeta['_um_pending_action'] ],
							),
						)
					);
				}

				if ( 'show_message' === $rolemeta['_um_pending_action'] ) {
					if ( array_key_exists( '_um_pending_message', $rolemeta ) ) {
						$info[ 'ultimate-member-' . $key ]['fields'] = array_merge(
							$info[ 'ultimate-member-' . $key ]['fields'],
							array(
								'um-pending_message' => array(
									'label' => __( 'Personalize the custom message', 'ultimate-member' ),
									'value' => stripslashes( $rolemeta['_um_pending_message'] ),
								),
							)
						);
					}
				} else {
					if ( array_key_exists( '_um_pending_url', $rolemeta ) ) {
						$info[ 'ultimate-member-' . $key ]['fields'] = array_merge(
							$info[ 'ultimate-member-' . $key ]['fields'],
							array(
								'um-pending_url' => array(
									'label' => __( 'Set Custom Redirect URL', 'ultimate-member' ),
									'value' => $rolemeta['_um_pending_url'],
								),
							)
						);
					}
				}
			}

			$after_login_options = array(
				'redirect_profile' => __( 'Redirect to profile', 'ultimate-member' ),
				'redirect_url'     => __( 'Redirect to URL', 'ultimate-member' ),
				'refresh'          => __( 'Refresh active page', 'ultimate-member' ),
				'redirect_admin'   => __( 'Redirect to WordPress Admin', 'ultimate-member' ),
			);

			if ( array_key_exists( '_um_after_login', $rolemeta ) && isset( $after_login_options[ $rolemeta['_um_after_login'] ] ) ) {
				$info[ 'ultimate-member-' . $key ]['fields'] = array_merge(
					$info[ 'ultimate-member-' . $key ]['fields'],
					array(
						'um-after_login' => array(
							'label' => __( 'Action to be taken after login', 'ultimate-member' ),
							'value' => $after_login_options[ $rolemeta['_um_after_login'] ],
						),
					)
				);
			}

			if ( array_key_exists( '_um_login_redirect_url', $rolemeta ) && 'redirect_url' === $rolemeta['_um_login_redirect_url'] ) {
				if ( array_key_exists( '_um_pending_url', $rolemeta ) ) {
					$info[ 'ultimate-member-' . $key ]['fields'] = array_merge(
						$info[ 'ultimate-member-' . $key ]['fields'],
						array(
							'um-login_redirect_url' => array(
								'label' => __( 'Set Custom Redirect URL', 'ultimate-member' ),
								'value' => $rolemeta['_um_login_redirect_url'],
							),
						)
					);
				}
			}

			$redirect_options = array(
				'redirect_home' => __( 'Go to Homepage', 'ultimate-member' ),
				'redirect_url'  => __( 'Go to Custom URL', 'ultimate-member' ),
			);
			if ( ! isset( $rolemeta['_um_after_logout'] ) ) {
				$rolemeta['_um_after_logout'] = 'redirect_home';
			}
			if ( array_key_exists( '_um_after_logout', $rolemeta ) && isset( $redirect_options[ $rolemeta['_um_after_logout'] ] ) ) {
				$info[ 'ultimate-member-' . $key ]['fields'] = array_merge(
					$info[ 'ultimate-member-' . $key ]['fields'],
					array(
						'um-after_logout' => array(
							'label' => __( 'Action to be taken after logout', 'ultimate-member' ),
							'value' => $redirect_options[ $rolemeta['_um_after_logout'] ],
						),
					)
				);
			}

			if ( 'redirect_url' === $rolemeta['_um_after_logout'] ) {
				if ( array_key_exists( '_um_logout_redirect_url', $rolemeta ) ) {
					$info[ 'ultimate-member-' . $key ]['fields'] = array_merge(
						$info[ 'ultimate-member-' . $key ]['fields'],
						array(
							'um-logout_redirect_url' => array(
								'label' => __( 'Set Custom Redirect URL', 'ultimate-member' ),
								'value' => $rolemeta['_um_logout_redirect_url'],
							),
						)
					);
				}
			}

			if ( ! isset( $rolemeta['_um_after_delete'] ) ) {
				$rolemeta['_um_after_delete'] = 'redirect_home';
			}
			if ( array_key_exists( '_um_after_delete', $rolemeta ) && isset( $redirect_options[ $rolemeta['_um_after_delete'] ] ) ) {
				$info[ 'ultimate-member-' . $key ]['fields'] = array_merge(
					$info[ 'ultimate-member-' . $key ]['fields'],
					array(
						'um-after_delete' => array(
							'label' => __( 'Action to be taken after account is deleted', 'ultimate-member' ),
							'value' => $redirect_options[ $rolemeta['_um_after_delete'] ],
						),
					)
				);
			}

			if ( 'redirect_url' === $rolemeta['_um_after_delete'] ) {
				if ( array_key_exists( '_um_delete_redirect_url', $rolemeta ) ) {
					$info[ 'ultimate-member-' . $key ]['fields'] = array_merge(
						$info[ 'ultimate-member-' . $key ]['fields'],
						array(
							'um-delete_redirect_url' => array(
								'label' => __( 'Set Custom Redirect URL', 'ultimate-member' ),
								'value' => $rolemeta['_um_delete_redirect_url'],
							),
						)
					);
				}
			}

			if ( ! empty( $rolemeta['wp_capabilities'] ) ) {
				if ( array_key_exists( 'wp_capabilities', $rolemeta ) ) {
					$info[ 'ultimate-member-' . $key ]['fields'] = array_merge(
						$info[ 'ultimate-member-' . $key ]['fields'],
						array(
							'um-wp_capabilities' => array(
								'label' => __( 'WP Capabilities', 'ultimate-member' ),
								'value' => $rolemeta['wp_capabilities'],
							),
						)
					);
				}
			}

			$info = apply_filters( 'um_debug_information_user_role', $info, $key );
		}

		// Forms settings
		if ( ! empty( $this->get_forms() ) ) {
			$info['ultimate-member-forms'] = array(
				'label'       => __( 'Ultimate Member Forms', 'ultimate-member' ),
				'description' => __( 'This debug information for your Ultimate Member forms.', 'ultimate-member' ),
				'fields'      => array(
					'um-forms' => array(
						'label' => __( 'UM Forms', 'ultimate-member' ),
						'value' => $this->get_forms(),
					),
				),
			);

			foreach ( $this->get_forms() as $key => $form ) {
				if ( strpos( $key, 'ID#' ) === 0 ) {
					$key = substr( $key, 3 );
				}

				$info[ 'ultimate-member-' . $key ] = array(
					'label'       => ' - ' . $form . __( ' form settings', 'ultimate-member' ),
					'description' => __( 'This debug information for your Ultimate Member form.', 'ultimate-member' ),
					'fields'      => array(
						'um-form-shortcode' => array(
							'label' => __( 'Shortcode', 'ultimate-member' ),
							'value' => '[ultimatemember form_id="' . $key . '"]',
						),
						'um-mode'           => array(
							'label' => __( 'Type', 'ultimate-member' ),
							'value' => get_post_meta( $key, '_um_mode', true ),
						),
					),
				);

				if ( 'register' === get_post_meta( $key, '_um_mode', true ) ) {
					$info[ 'ultimate-member-' . $key ]['fields'] = array_merge(
						$info[ 'ultimate-member-' . $key ]['fields'],
						array(
							'um-register_role'             => array(
								'label' => __( 'User registration role', 'ultimate-member' ),
								'value' => 0 === absint( get_post_meta( $key, '_um_register_role', true ) ) ? $labels['default'] : get_post_meta( $key, '_um_register_role', true ),
							),
							'um-register_template'         => array(
								'label' => __( 'Template', 'ultimate-member' ),
								'value' => 0 === absint( get_post_meta( $key, '_um_register_template', true ) ) ? $labels['default'] : get_post_meta( $key, '_um_register_template', true ),
							),
							'um-register_primary_btn_word' => array(
								'label' => __( 'Primary Button Text', 'ultimate-member' ),
								'value' => ! get_post_meta( $key, '_um_register_primary_btn_word', true ) ? $labels['default'] : get_post_meta( $key, '_um_register_primary_btn_word', true ),
							),
							'um-register_use_gdpr'         => array(
								'label' => __( 'Enable privacy policy agreement', 'ultimate-member' ),
								'value' => get_post_meta( $key, '_um_register_use_gdpr', true ) ? $labels['yes'] : $labels['no'],
							),
						)
					);

					if ( 1 === absint( get_post_meta( $key, '_um_register_use_gdpr', true ) ) ) {
						$gdpr_content_id = get_post_meta( $key, '_um_register_use_gdpr_content_id', true );

						$info[ 'ultimate-member-' . $key ]['fields'] = array_merge(
							$info[ 'ultimate-member-' . $key ]['fields'],
							array(
								'um-register_use_gdpr_content_id' => array(
									'label' => __( 'Privacy policy content', 'ultimate-member' ),
									'value' => $gdpr_content_id ? get_the_title( $gdpr_content_id ) . '(' . $gdpr_content_id . ')' . get_the_permalink( $gdpr_content_id ) : '',
								),
								'um-register_use_gdpr_toggle_show' => array(
									'label' => __( 'Toggle Show text', 'ultimate-member' ),
									'value' => get_post_meta( $key, '_um_register_use_gdpr_toggle_show', true ),
								),
								'um-register_use_gdpr_toggle_hide' => array(
									'label' => __( 'Toggle Hide text', 'ultimate-member' ),
									'value' => get_post_meta( $key, '_um_register_use_gdpr_toggle_hide', true ),
								),
								'um-register_use_gdpr_agreement' => array(
									'label' => __( 'Checkbox agreement description', 'ultimate-member' ),
									'value' => get_post_meta( $key, '_um_register_use_gdpr_agreement', true ),
								),
								'um-register_use_gdpr_error_text' => array(
									'label' => __( 'Error Text', 'ultimate-member' ),
									'value' => get_post_meta( $key, '_um_register_use_gdpr_error_text', true ),
								),
							)
						);
					}

					$info = apply_filters( 'um_debug_information_register_form', $info, $key );

					$fields = get_post_meta( $key, '_um_custom_fields', true );
					if ( ! empty( $fields ) && is_array( $fields ) ) {
						foreach ( $fields as $field_key => $field ) {
							$field_info = $this->get_field_data( $info, $key, $field_key, $field );

							$info[ 'ultimate-member-' . $key ]['fields'] = array_merge(
								$info[ 'ultimate-member-' . $key ]['fields'],
								$field_info
							);
						}
					}
				} elseif ( 'login' === get_post_meta( $key, '_um_mode', true ) ) {
					$login_redirect_options = array(
						'0'                => __( 'Default', 'ultimate-member' ),
						'redirect_profile' => __( 'Redirect to profile', 'ultimate-member' ),
						'redirect_url'     => __( 'Redirect to URL', 'ultimate-member' ),
						'refresh'          => __( 'Refresh active page', 'ultimate-member' ),
						'redirect_admin'   => __( 'Redirect to WordPress Admin', 'ultimate-member' ),
					);

					$login_after_login = get_post_meta( $key, '_um_login_after_login', true );
					$login_after_login = '' === $login_after_login ? '0' : $login_after_login;

					$info[ 'ultimate-member-' . $key ]['fields'] = array_merge(
						$info[ 'ultimate-member-' . $key ]['fields'],
						array(
							'um-login_template'         => array(
								'label' => __( 'Template', 'ultimate-member' ),
								'value' => 0 === absint( get_post_meta( $key, '_um_login_template', true ) ) ? $labels['default'] : get_post_meta( $key, '_um_login_template', true ),
							),
							'um-login_primary_btn_word' => array(
								'label' => __( 'Primary Button Text', 'ultimate-member' ),
								'value' => ! get_post_meta( $key, '_um_login_primary_btn_word', true ) ? $labels['default'] : get_post_meta( $key, '_um_login_primary_btn_word', true ),
							),
							'um-login_forgot_pass_link' => array(
								'label' => __( 'Show Forgot Password Link?', 'ultimate-member' ),
								'value' => get_post_meta( $key, '_um_login_forgot_pass_link', true ) ? $labels['yes'] : $labels['no'],
							),
							'um-login_show_rememberme'  => array(
								'label' => __( 'Show "Remember Me"?', 'ultimate-member' ),
								'value' => get_post_meta( $key, '_um_login_show_rememberme', true ) ? $labels['yes'] : $labels['no'],
							),
							'um-login_after_login'      => array(
								'label' => __( 'Redirection after Login', 'ultimate-member' ),
								'value' => $login_redirect_options[ $login_after_login ],
							),
						)
					);

					if ( 'redirect_url' === get_post_meta( $key, '_um_login_after_login', true ) ) {
						$info[ 'ultimate-member-' . $key ]['fields'] = array_merge(
							$info[ 'ultimate-member-' . $key ]['fields'],
							array(
								'um-login_redirect_url' => array(
									'label' => __( 'Set Custom Redirect URL', 'ultimate-member' ),
									'value' => get_post_meta( $key, '_um_login_redirect_url', true ),
								),
							)
						);
					}

					$info = apply_filters( 'um_debug_information_login_form', $info, $key );

					$fields = get_post_meta( $key, '_um_custom_fields', true );
					if ( ! empty( $fields ) && is_array( $fields ) ) {
						foreach ( $fields as $field_key => $field ) {
							$field_info = $this->get_field_data( $info, $key, $field_key, $field );

							$info[ 'ultimate-member-' . $key ]['fields'] = array_merge(
								$info[ 'ultimate-member-' . $key ]['fields'],
								$field_info
							);
						}
					}
				} elseif ( 'profile' === get_post_meta( $key, '_um_mode', true ) ) {
					$info[ 'ultimate-member-' . $key ]['fields'] = array_merge(
						$info[ 'ultimate-member-' . $key ]['fields'],
						array(
							'um-profile_role'             => array(
								'label' => __( 'Make this profile form role-specific', 'ultimate-member' ),
								'value' => ! empty( get_post_meta( $key, '_um_profile_role', true ) ) ? get_post_meta( $key, '_um_profile_role', true ) : $labels['all'],
							),
							'um-profile_template'         => array(
								'label' => __( 'Template', 'ultimate-member' ),
								'value' => 0 === absint( get_post_meta( $key, '_um_profile_template', true ) ) ? $labels['default'] : get_post_meta( $key, '_um_profile_template', true ),
							),
							'um-profile_primary_btn_word' => array(
								'label' => __( 'Primary Button Text', 'ultimate-member' ),
								'value' => ! get_post_meta( $key, '_um_profile_primary_btn_word', true ) ? $labels['default'] : get_post_meta( $key, '_um_profile_primary_btn_word', true ),
							),
							'um-profile_cover_enabled'    => array(
								'label' => __( 'Enable Cover Photos', 'ultimate-member' ),
								'value' => get_post_meta( $key, '_um_profile_cover_enabled', true ) ? $labels['yes'] : $labels['no'],
							),
							'um-profile_disable_photo_upload' => array(
								'label' => __( 'Disable Profile Photo Upload', 'ultimate-member' ),
								'value' => get_post_meta( $key, '_um_profile_disable_photo_upload', true ) ? $labels['yes'] : $labels['no'],
							),
						)
					);

					if ( 0 === absint( get_post_meta( $key, '_um_profile_disable_photo_upload', true ) ) ) {
						$info[ 'ultimate-member-' . $key ]['fields'] = array_merge(
							$info[ 'ultimate-member-' . $key ]['fields'],
							array(
								'um-profile_photo_required' => array(
									'label' => __( 'Make Profile Photo Required', 'ultimate-member' ),
									'value' => get_post_meta( $key, '_um_profile_photo_required', true ) ? $labels['yes'] : $labels['no'],
								),
							)
						);
					}

					$info[ 'ultimate-member-' . $key ]['fields'] = array_merge(
						$info[ 'ultimate-member-' . $key ]['fields'],
						array(
							'um-profile_show_name'         => array(
								'label' => __( 'Show display name in profile header?', 'ultimate-member' ),
								'value' => get_post_meta( $key, '_um_profile_show_name', true ) ? $labels['yes'] : $labels['no'],
							),
							'um-profile_show_social_links' => array(
								'label' => __( 'Show social links in profile header?', 'ultimate-member' ),
								'value' => get_post_meta( $key, '_um_profile_show_social_links', true ) ? $labels['yes'] : $labels['no'],
							),
							'um-profile_show_bio'          => array(
								'label' => __( 'Show user description in profile header?', 'ultimate-member' ),
								'value' => get_post_meta( $key, '_um_profile_show_bio', true ) ? $labels['yes'] : $labels['no'],
							),
						)
					);

					$fields = get_post_meta( $key, '_um_custom_fields', true );
					if ( ! empty( $fields ) && is_array( $fields ) ) {
						foreach ( $fields as $field_key => $field ) {
							$field_info = $this->get_field_data( $info, $key, $field_key, $field );

							$info[ 'ultimate-member-' . $key ]['fields'] = array_merge(
								$info[ 'ultimate-member-' . $key ]['fields'],
								$field_info
							);
						}
					}

					$profile_metafields = get_post_meta( $key, '_um_profile_metafields', true );
					if ( ! empty( $profile_metafields ) && is_array( $profile_metafields ) ) {
						foreach ( $profile_metafields as $k => $field ) {
							$info[ 'ultimate-member-' . $key ]['fields'] = array_merge(
								$info[ 'ultimate-member-' . $key ]['fields'],
								array(
									'um-profile_metafields-' . $k => array(
										'label' => __( 'Field to show in user meta', 'ultimate-member' ),
										'value' => $field,
									),
								)
							);
						}
					}
				}
			}
		}

		// Members directory
		$options = array(
			'country'              => __( 'Country', 'ultimate-member' ),
			'gender'               => __( 'Gender', 'ultimate-member' ),
			'languages'            => __( 'Languages', 'ultimate-member' ),
			'role'                 => __( 'Roles', 'ultimate-member' ),
			'birth_date'           => __( 'Age', 'ultimate-member' ),
			'last_login'           => __( 'Last Login', 'ultimate-member' ),
			'user_registered'      => __( 'User Registered', 'ultimate-member' ),
			'first_name'           => __( 'First Name', 'ultimate-member' ),
			'last_name'            => __( 'Last Name', 'ultimate-member' ),
			'nickname'             => __( 'Nickname', 'ultimate-member' ),
			'secondary_user_email' => __( 'Secondary Email Address', 'ultimate-member' ),
			'description'          => __( 'Biography', 'ultimate-member' ),
			'phone_number'         => __( 'Phone Number', 'ultimate-member' ),
			'mobile_number'        => __( 'Mobile Number', 'ultimate-member' ),
			'role_select'          => __( 'Roles (Dropdown)', 'ultimate-member' ),
			'role_radio'           => __( 'Roles (Radio)', 'ultimate-member' ),
			'whatsapp'             => __( 'WhatsApp number', 'ultimate-member' ),
			'facebook'             => __( 'Facebook', 'ultimate-member' ),
			'twitter'              => __( 'X (formerly Twitter)', 'ultimate-member' ),
			'viber'                => __( 'Viber number', 'ultimate-member' ),
			'skype'                => __( 'Skype ID', 'ultimate-member' ),
			'telegram'             => __( 'Telegram', 'ultimate-member' ),
			'discord'              => __( 'Discord', 'ultimate-member' ),
			'youtube'              => __( 'Youtube', 'ultimate-member' ),
			'soundcloud'           => __( 'SoundCloud', 'ultimate-member' ),
			'user_registered_desc' => __( 'New users first', 'ultimate-member' ),
			'user_registered_asc'  => __( 'Old users first', 'ultimate-member' ),
			'username'             => __( 'Username', 'ultimate-member' ),
			'display_name'         => __( 'Display name', 'ultimate-member' ),
			'last_first_name'      => __( 'Last & First name', 'ultimate-member' ),
			'random'               => __( 'Random', 'ultimate-member' ),
			'other'                => __( 'Other (Custom Field)', 'ultimate-member' ),
		);

		$info['ultimate-member-directories'] = array(
			'label'       => __( 'Ultimate Member Directories', 'ultimate-member' ),
			'description' => __( 'This debug information about Ultimate Member directories.', 'ultimate-member' ),
			'fields'      => array(
				'um-directory' => array(
					'label' => __( 'Member directories', 'ultimate-member' ),
					'value' => ! empty( $this->get_member_directories() ) ? $this->get_member_directories() : $labels['no-dir'],
				),
			),
		);

		if ( ! empty( $this->get_member_directories() ) ) {
			foreach ( $this->get_member_directories() as $key => $directory ) {
				if ( 0 === strpos( $key, 'ID#' ) ) {
					$key = substr( $key, 3 );
				}

				$_um_view_types_value = get_post_meta( $key, '_um_view_types', true );
				$_um_view_types_value = empty( $_um_view_types_value ) ? array( 'grid', 'list' ) : $_um_view_types_value;
				$_um_view_types_value = is_string( $_um_view_types_value ) ? array( $_um_view_types_value ) : $_um_view_types_value;

				$info[ 'ultimate-member-directory-' . $key ] = array(
					'label'       => ' - ' . $directory . __( ' directory settings', 'ultimate-member' ),
					'description' => __( 'This debug information for your Ultimate Member directory.', 'ultimate-member' ),
					'fields'      => array(
						'um-directory-shortcode'    => array(
							'label' => __( 'Shortcode', 'ultimate-member' ),
							'value' => '[ultimatemember_directory id="' . $key . '"]',
						),
						'um-directory_template'     => array(
							'label' => __( 'Template', 'ultimate-member' ),
							'value' => get_post_meta( $key, '_um_directory_template', true ) ? get_post_meta( $key, '_um_directory_template', true ) : $labels['default'],
						),
						'um-directory-view_types'   => array(
							'label' => __( 'View types', 'ultimate-member' ),
							'value' => implode( ', ', $_um_view_types_value ),
						),
						'um-directory-default_view' => array(
							'label' => __( 'Default view type', 'ultimate-member' ),
							'value' => get_post_meta( $key, '_um_default_view', true ),
						),
					),
				);

				if ( isset( $options[ get_post_meta( $key, '_um_sortby', true ) ] ) ) {
					$sortby_label = $options[ get_post_meta( $key, '_um_sortby', true ) ];
				} else {
					$sortby_label = get_post_meta( $key, '_um_sortby', true );
				}

				$directory_roles_meta = get_post_meta( $key, '_um_roles', true );
				$directory_roles      = array();
				if ( ! empty( $directory_roles_meta ) ) {
					if ( is_string( $directory_roles_meta ) ) {
						$directory_roles = array( $directory_roles_meta );
					} else {
						$directory_roles = $directory_roles_meta;
					}
				}

				$directory_show_these_users_meta = get_post_meta( $key, '_um_show_these_users', true );
				$show_these_users                = array();
				if ( ! empty( $directory_show_these_users_meta ) ) {
					if ( is_string( $directory_show_these_users_meta ) ) {
						$show_these_users = array( $directory_show_these_users_meta );
					} else {
						$show_these_users = $directory_show_these_users_meta;
					}
				}

				$directory_exclude_these_users_meta = get_post_meta( $key, '_um_exclude_these_users', true );
				$exclude_these_users                = array();
				if ( ! empty( $directory_exclude_these_users_meta ) ) {
					if ( is_string( $directory_exclude_these_users_meta ) ) {
						$exclude_these_users = array( $directory_exclude_these_users_meta );
					} else {
						$exclude_these_users = $directory_exclude_these_users_meta;
					}
				}

				$info[ 'ultimate-member-directory-' . $key ]['fields'] = array_merge(
					$info[ 'ultimate-member-directory-' . $key ]['fields'],
					array(
						'um-directory-roles'               => array(
							'label' => __( 'User Roles to display', 'ultimate-member' ),
							'value' => ! empty( $directory_roles ) ? implode( ', ', $directory_roles ) : $labels['all'],
						),
						'um-directory-has_profile_photo'   => array(
							'label' => __( 'Only show members who have uploaded a profile photo', 'ultimate-member' ),
							'value' => get_post_meta( $key, '_um_has_profile_photo', true ) ? $labels['yes'] : $labels['no'],
						),
						'um-directory-has_cover_photo'     => array(
							'label' => __( 'Only show members who have uploaded a profile photo', 'ultimate-member' ),
							'value' => get_post_meta( $key, '_um_has_cover_photo', true ) ? $labels['yes'] : $labels['no'],
						),
						'um-directory-show_these_users'    => array(
							'label' => __( 'Only show specific users (Enter one username per line)', 'ultimate-member' ),
							'value' => ! empty( $show_these_users ) ? implode( ', ', $show_these_users ) : '',
						),
						'um-directory-exclude_these_users' => array(
							'label' => __( 'Exclude specific users (Enter one username per line)', 'ultimate-member' ),
							'value' => ! empty( $exclude_these_users ) ? implode( ', ', $exclude_these_users ) : '',
						),
					)
				);

				$info = apply_filters( 'um_debug_member_directory_general_extend', $info, $key );

				$info[ 'ultimate-member-directory-' . $key ]['fields'] = array_merge(
					$info[ 'ultimate-member-directory-' . $key ]['fields'],
					array(
						'um-directory-sortby' => array(
							'label' => __( 'Default sort users by', 'ultimate-member' ),
							'value' => $sortby_label,
						),
					)
				);

				if ( 'other' === get_post_meta( $key, '_um_sortby', true ) ) {
					$info[ 'ultimate-member-directory-' . $key ]['fields'] = array_merge(
						$info[ 'ultimate-member-directory-' . $key ]['fields'],
						array(
							'um-directory-enable_sorting' => array(
								'label' => __( 'Enable custom sorting', 'ultimate-member' ),
								'value' => get_post_meta( $key, '_um_enable_sorting', true ) ? $labels['yes'] : $labels['no'],
							),
							'um-directory-sortby_custom'  => array(
								'label' => __( 'Custom sorting meta key', 'ultimate-member' ),
								'value' => get_post_meta( $key, '_um_sortby_custom', true ),
							),
							'um-directory-sortby_custom_label' => array(
								'label' => __( 'Label of custom sort', 'ultimate-member' ),
								'value' => get_post_meta( $key, '_um_sortby_custom_label', true ),
							),
						)
					);
				}

				if ( 1 === absint( get_post_meta( $key, '_um_enable_sorting', true ) ) ) {
					$sorting_fields = get_post_meta( $key, '_um_sorting_fields', true );
					if ( ! empty( $sorting_fields ) ) {
						foreach ( $sorting_fields as $k => $field ) {
							if ( is_array( $field ) ) {
								$info[ 'ultimate-member-directory-' . $key ]['fields'] = array_merge(
									$info[ 'ultimate-member-directory-' . $key ]['fields'],
									array(
										'um-directory-sorting_fields-' . $k => array(
											'label' => __( 'Field(s) to enable in sorting', 'ultimate-member' ),
											'value' => __( 'Label: ', 'ultimate-member' ) . array_values( $field )[0] . ' | ' . __( 'Meta key: ', 'ultimate-member' ) . stripslashes( array_keys( $field )[0] ),
										),
									)
								);
							} else {
								if ( isset( $options[ $field ] ) ) {
									$sortby_label = $options[ $field ];
								} else {
									$sortby_label = $field;
								}
								$info[ 'ultimate-member-directory-' . $key ]['fields'] = array_merge(
									$info[ 'ultimate-member-directory-' . $key ]['fields'],
									array(
										'um-directory-sorting_fields-' . $k => array(
											'label' => __( 'Field to enable in sorting', 'ultimate-member' ),
											'value' => $sortby_label,
										),
									)
								);
							}
						}
					}
				}

				$info[ 'ultimate-member-directory-' . $key ]['fields'] = array_merge(
					$info[ 'ultimate-member-directory-' . $key ]['fields'],
					array(
						'um-directory-profile_photo' => array(
							'label' => __( 'Enable Profile Photo', 'ultimate-member' ),
							'value' => get_post_meta( $key, '_um_profile_photo', true ) ? $labels['yes'] : $labels['no'],
						),
						'um-directory-cover_photos'  => array(
							'label' => __( 'Enable Cover Photo', 'ultimate-member' ),
							'value' => get_post_meta( $key, '_um_cover_photos', true ) ? $labels['yes'] : $labels['no'],
						),
						'um-directory-show_name'     => array(
							'label' => __( 'Show display name', 'ultimate-member' ),
							'value' => get_post_meta( $key, '_um_show_name', true ) ? $labels['yes'] : $labels['no'],
						),
					)
				);

				$info = apply_filters( 'um_debug_member_directory_profile_extend', $info, $key );

				$info[ 'ultimate-member-directory-' . $key ]['fields'] = array_merge(
					$info[ 'ultimate-member-directory-' . $key ]['fields'],
					array(
						'um-directory-show_tagline' => array(
							'label' => __( 'Show tagline below profile name', 'ultimate-member' ),
							'value' => get_post_meta( $key, '_um_show_tagline', true ) ? $labels['yes'] : $labels['no'],
						),
					)
				);

				if ( 1 === absint( get_post_meta( $key, '_um_show_tagline', true ) ) ) {
					$tagline_fields = get_post_meta( $key, '_um_tagline_fields', true );
					if ( ! empty( $tagline_fields ) && is_array( $tagline_fields ) ) {
						foreach ( $tagline_fields as $k => $field ) {
							$label = isset( $options[ $field ] ) ? $options[ $field ] : $field;
							$info[ 'ultimate-member-directory-' . $key ]['fields'] = array_merge(
								$info[ 'ultimate-member-directory-' . $key ]['fields'],
								array(
									'um-directory-tagline_fields-' . $k => array(
										'label' => __( 'Field to display in tagline', 'ultimate-member' ),
										'value' => $label,
									),
								)
							);
						}
					}
				}

				$info[ 'ultimate-member-directory-' . $key ]['fields'] = array_merge(
					$info[ 'ultimate-member-directory-' . $key ]['fields'],
					array(
						'um-directory-show_userinfo' => array(
							'label' => __( 'Show extra user information below tagline?', 'ultimate-member' ),
							'value' => get_post_meta( $key, '_um_show_userinfo', true ) ? $labels['yes'] : $labels['no'],
						),
					)
				);

				if ( 1 === absint( get_post_meta( $key, '_um_show_userinfo', true ) ) ) {
					$reveal_fields = get_post_meta( $key, '_um_reveal_fields', true );
					if ( ! empty( $reveal_fields ) && is_array( $reveal_fields ) ) {
						foreach ( $reveal_fields as $k => $field ) {
							$label = isset( $options[ $field ] ) ? $options[ $field ] : $field;
							$info[ 'ultimate-member-directory-' . $key ]['fields'] = array_merge(
								$info[ 'ultimate-member-directory-' . $key ]['fields'],
								array(
									'um-directory-reveal_fields-' . $k => array(
										'label' => __( 'Field to display in extra user information section', 'ultimate-member' ),
										'value' => $label,
									),
								)
							);
						}
					}
				}

				$info[ 'ultimate-member-directory-' . $key ]['fields'] = array_merge(
					$info[ 'ultimate-member-directory-' . $key ]['fields'],
					array(
						'um-directory-show_social' => array(
							'label' => __( 'Show social connect icons in extra user information section', 'ultimate-member' ),
							'value' => get_post_meta( $key, '_um_show_social', true ) ? $labels['yes'] : $labels['no'],
						),
						'um-directory-search'      => array(
							'label' => __( 'Enable Search feature', 'ultimate-member' ),
							'value' => get_post_meta( $key, '_um_search', true ) ? $labels['yes'] : $labels['no'],
						),
					)
				);

				if ( 1 === absint( get_post_meta( $key, '_um_search', true ) ) ) {
					$directory_roles_can_search_meta = get_post_meta( $key, '_um_roles_can_search', true );
					$roles_can_search                = array();
					if ( ! empty( $directory_roles_can_search_meta ) ) {
						if ( is_string( $directory_roles_can_search_meta ) ) {
							$roles_can_search = array( $directory_roles_can_search_meta );
						} else {
							$roles_can_search = $directory_roles_can_search_meta;
						}
					}

					$info[ 'ultimate-member-directory-' . $key ]['fields'] = array_merge(
						$info[ 'ultimate-member-directory-' . $key ]['fields'],
						array(
							'um-directory-roles_can_search' => array(
								'label' => __( 'User Roles that can use search', 'ultimate-member' ),
								'value' => ! empty( $roles_can_search ) ? implode( ', ', $roles_can_search ) : $labels['all'],
							),
						)
					);
				}

				$info[ 'ultimate-member-directory-' . $key ]['fields'] = array_merge(
					$info[ 'ultimate-member-directory-' . $key ]['fields'],
					array(
						'um-directory-filters' => array(
							'label' => __( 'Enable Filters feature', 'ultimate-member' ),
							'value' => get_post_meta( $key, '_um_filters', true ) ? $labels['yes'] : $labels['no'],
						),
					)
				);

				if ( 1 === absint( get_post_meta( $key, '_um_filters', true ) ) ) {
					$directory_roles_can_filter_meta = get_post_meta( $key, '_um_roles_can_filter', true );
					$roles_can_filter                = array();
					if ( ! empty( $directory_roles_can_filter_meta ) ) {
						if ( is_string( $directory_roles_can_filter_meta ) ) {
							$roles_can_filter = array( $directory_roles_can_filter_meta );
						} else {
							$roles_can_filter = $directory_roles_can_filter_meta;
						}
					}

					$info[ 'ultimate-member-directory-' . $key ]['fields'] = array_merge(
						$info[ 'ultimate-member-directory-' . $key ]['fields'],
						array(
							'um-directory-roles_can_filter' => array(
								'label' => __( 'User Roles that can use filters', 'ultimate-member' ),
								'value' => ! empty( $roles_can_filter ) ? implode( ', ', $roles_can_filter ) : $labels['all'],
							),
						)
					);

					$search_fields = get_post_meta( $key, '_um_search_fields', true );
					if ( ! empty( $search_fields ) && is_array( $search_fields ) ) {
						foreach ( $search_fields as $k => $field ) {
							$label = isset( $options[ $field ] ) ? $options[ $field ] : $field;
							$info[ 'ultimate-member-directory-' . $key ]['fields'] = array_merge(
								$info[ 'ultimate-member-directory-' . $key ]['fields'],
								array(
									'um-directory-search_fields-' . $k => array(
										'label' => __( 'Filter meta to enable', 'ultimate-member' ),
										'value' => $label,
									),
								)
							);
						}
					}
				}

				$info[ 'ultimate-member-directory-' . $key ]['fields'] = array_merge(
					$info[ 'ultimate-member-directory-' . $key ]['fields'],
					array(
						'um-directory-filters_expanded' => array(
							'label' => __( 'Expand the filter bar by default', 'ultimate-member' ),
							'value' => get_post_meta( $key, '_um_filters_expanded', true ) ? $labels['yes'] : $labels['no'],
						),
					)
				);

				if ( 1 === absint( get_post_meta( $key, '_um_filters_expanded', true ) ) ) {
					$info[ 'ultimate-member-directory-' . $key ]['fields'] = array_merge(
						$info[ 'ultimate-member-directory-' . $key ]['fields'],
						array(
							'um-directory-filters_is_collapsible' => array(
								'label' => __( 'Can filter bar be collapsed', 'ultimate-member' ),
								'value' => get_post_meta( $key, '_um_filters_is_collapsible', true ) ? $labels['yes'] : $labels['no'],
							),
						)
					);
				}

				$search_filters = get_post_meta( $key, '_um_search_filters', true );
				if ( ! empty( $search_filters ) && is_array( $search_filters ) ) {
					foreach ( $search_filters as $k => $field ) {
						$label = isset( $options[ $k ] ) ? $options[ $k ] : $k;
						$value = $field;
						if ( is_array( $field ) ) {
							$value = __( 'From ', 'ultimate-member' ) . $field[0] . __( ' to ', 'ultimate-member' ) . $field[1];
						}
						$info[ 'ultimate-member-directory-' . $key ]['fields'] = array_merge(
							$info[ 'ultimate-member-directory-' . $key ]['fields'],
							array(
								'um-directory-search_filters-' . $k => array(
									'label' => __( 'Admin filtering', 'ultimate-member' ),
									'value' => $label . ' - ' . $value,
								),
							)
						);
					}
				}

				$info[ 'ultimate-member-directory-' . $key ]['fields'] = array_merge(
					$info[ 'ultimate-member-directory-' . $key ]['fields'],
					array(
						'um-directory-must_search'        => array(
							'label' => __( 'Show results only after search/filtration', 'ultimate-member' ),
							'value' => get_post_meta( $key, '_um_must_search', true ) ? $labels['yes'] : $labels['no'],
						),
						'um-directory-max_users'          => array(
							'label' => __( 'Maximum number of profiles', 'ultimate-member' ),
							'value' => get_post_meta( $key, '_um_max_users', true ),
						),
						'um-directory-profiles_per_page'  => array(
							'label' => __( 'Number of profiles per page', 'ultimate-member' ),
							'value' => get_post_meta( $key, '_um_profiles_per_page', true ),
						),
						'um-directory-profiles_per_page_mobile' => array(
							'label' => __( 'Maximum number of profiles', 'ultimate-member' ),
							'value' => get_post_meta( $key, '_um_profiles_per_page_mobile', true ),
						),
						'um-directory-directory_header'   => array(
							'label' => __( 'Results Text', 'ultimate-member' ),
							'value' => get_post_meta( $key, '_um_directory_header', true ),
						),
						'um-directory-directory_header_single' => array(
							'label' => __( 'Single Result Text', 'ultimate-member' ),
							'value' => get_post_meta( $key, '_um_directory_header_single', true ),
						),
						'um-directory-directory_no_users' => array(
							'label' => __( 'Custom text if no users were found', 'ultimate-member' ),
							'value' => get_post_meta( $key, '_um_directory_no_users', true ),
						),
					)
				);

				$info = apply_filters( 'um_debug_member_directory_extend', $info, $key );
			}
		}

		return $info;
	}
}
