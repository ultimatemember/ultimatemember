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
	 * Array of labels.
	 *
	 * @since 2.10.5
	 *
	 * @var array
	 */
	private static $labels = array();

	/**
	 * Site_Health constructor.
	 */
	public function __construct() {
		add_filter( 'debug_information', array( $this, 'debug_information' ), 20 );
		add_filter( 'site_status_tests', array( $this, 'register_site_status_tests' ) );
	}

	/**
	 * Return labels array with translations.
	 *
	 * @return array Labels array containing translations.
	 */
	public static function get_labels() {
		if ( empty( self::$labels ) ) {
			self::$labels = array(
				'yes'     => __( 'Yes', 'ultimate-member' ),
				'no'      => __( 'No', 'ultimate-member' ),
				'all'     => __( 'All', 'ultimate-member' ),
				'default' => __( 'Default', 'ultimate-member' ),
				'nopages' => __( 'No predefined page', 'ultimate-member' ),
				'empty'   => __( 'Empty', 'ultimate-member' ),
			);
		}

		return self::$labels;
	}

	/**
	 * Register site status tests.
	 *
	 * @param array $tests The array of site status tests.
	 *
	 * @return array The modified array of site status tests.
	 */
	public function register_site_status_tests( $tests ) {
		// Searching for custom templates and outdated versions.
		$custom_templates = UM()->common()->theme()->get_custom_templates_list();
		if ( ! empty( $custom_templates ) ) {
			$tests['direct']['um_override_templates'] = array(
				'label' => esc_html__( 'Are the Ultimate Member templates out of date?', 'ultimate-member' ),
				'test'  => array( $this, 'override_templates_test' ),
			);
		}

		// Searching for outdated icons.
		$first_activation_date = get_option( 'um_first_activation_date', false );
		if ( ! empty( $first_activation_date ) && $first_activation_date < 1716336000 ) {
			$tests['direct']['um_outdated_icons'] = array(
				'label' => esc_html__( 'Are the icons in Ultimate Member Forms and Settings out of date?', 'ultimate-member' ),
				'test'  => array( $this, 'outdated_icons_test' ),
			);
		}

		// Searching in the global registered custom fields banned or blacklisted fields.
		$custom_fields = get_option( 'um_fields', array() );
		if ( ! empty( $custom_fields ) ) {
			foreach ( array_keys( $custom_fields ) as $key ) {
				if ( self::field_is_banned( $key ) ) {
					$tests['direct']['um_banned_fields'] = array(
						'label' => esc_html__( 'Are the banned custom fields?', 'ultimate-member' ),
						'test'  => array( $this, 'banned_fields_test' ),
					);
					break;
				}
			}
		}

		return $tests;
	}

	/**
	 * Calculates the status of custom Ultimate Member templates and provides a detailed result array.
	 *
	 * @return array Result array containing details about the custom templates status.
	 */
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
	 * Retrieves outdated icons in Ultimate Member forms and provides details and actions for updating.
	 *
	 * @return array|bool Returns an array with description and actions for updating outdated icons, or null if nothing needs updating.
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

		/**
		 * Filters the site health test information about outdated icons.
		 *
		 * @hook um_get_outdated_icons_result
		 *
		 * @since 2.8.6
		 *
		 * @param {array} $result The site health test information about outdated icons.
		 *
		 * @return {array} The site health test information about outdated icons.
		 */
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

	/**
	 * Retrieve information about outdated icons in Ultimate Member forms and settings.
	 *
	 * @return array Information about the status of icons, including label, status, badge, description, actions, and test name.
	 */
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

	/**
	 * Check if a specific field is banned for a user based on the meta key.
	 *
	 * @param string $metakey The meta key to check if it's banned.
	 *
	 * @return bool True if the field is banned, false otherwise.
	 */
	private static function field_is_banned( $metakey ) {
		return UM()->user()->is_metakey_banned( $metakey ) || in_array( strtolower( $metakey ), UM()->builtin()->blacklist_fields, true );
	}

	/**
	 * Retrieve information about banned fields in Ultimate Member forms.
	 *
	 * @return bool|array Information about banned fields, including description, actions, and form data.
	 */
	public function get_banned_fields() {
		$result = array(
			'description' => '',
			'actions'     => '',
		);

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
					if ( empty( $field['metakey'] ) ) {
						continue;
					}

					if ( self::field_is_banned( $field['metakey'] ) ) {
						if ( ! array_key_exists( $form_id, $break_forms ) ) {
							$break_forms[ $form_id ] = array(
								'title'  => get_the_title( $form_id ),
								'link'   => get_edit_post_link( $form_id ),
								'fields' => array(
									$field['metakey'] => isset( $field['title'] ) ? $field['title'] : __( 'Unknown title', 'ultimate-member' ),
								),
							);

							++$forms_count;
						} else {
							$break_forms[ $form_id ]['fields'][ $field['metakey'] ] = isset( $field['title'] ) ? $field['title'] : __( 'Unknown title', 'ultimate-member' );
						}
					}
				}
			}
		}

		if ( 0 < $forms_count ) {
			$result['description'] .= sprintf(
				'<p>%s</p>',
				__( 'Please note that some fields in your Ultimate Member Forms are currently on a restricted list that disallows their use. This is particularly related to the Ultimate Member Forms and their fields below.', 'ultimate-member' )
			);

			if ( ! empty( $break_forms ) ) {
				$forms_description = array();
				foreach ( $break_forms as $form_id => $form_data ) {
					$fields = array();
					foreach ( $form_data['fields'] as $metakey => $field_title ) {
						// translators: %1$s is the field title, %2$s is the field metakey
						$fields[] = sprintf( __( '%1$s (<code>%2$s</code>)', 'ultimate-member' ), $field_title, $metakey );
					}

					$forms_description[] = '<h4>' . sprintf(
						// translators: %1$s is the form link, %2$s is the form title, %3$s is the form ID
						__( 'Fields in <a href="%1$s" target="_blank">%2$s (#ID: %3$s)</a>:', 'ultimate-member' ),
						esc_url( $form_data['link'] ),
						esc_html( $form_data['title'] ),
						esc_html( $form_id )
					) . '</h4><ul><li>' . implode( '</li><li>', $fields ) . '</li></ul>';
				}

				$result['description'] .= implode( ' ', $forms_description );
			}

			$result['actions'] .= sprintf(
				'<p><a href="%s">%s</a></p>',
				admin_url( 'edit.php?post_type=um_form' ),
				esc_html__( 'Edit form fields and update', 'ultimate-member' )
			);
		}

		/**
		 * Filters the site health test information about banned fields.
		 *
		 * @hook um_get_banned_fields_result
		 *
		 * @since 2.10.3
		 *
		 * @param {array} $result The site health test information about banned fields.
		 *
		 * @return {array} The site health test information about banned fields.
		 */
		$result = apply_filters( 'um_get_banned_fields_result', $result );

		if ( ! empty( $result['description'] ) ) {
			$result['description'] .= sprintf(
				'<p>%s</p>',
				__( 'The using meta keys from restricted list in Ultimate Member Forms may break the website\'s functionality and is unsecure.', 'ultimate-member' )
			);
		}

		if ( ! empty( $result['description'] ) && ! empty( $result['actions'] ) ) {
			return $result;
		}

		return false;
	}

	/**
	 * Retrieve information about banned fields in Ultimate Member forms.
	 *
	 * @return array Information about the status of banned fields, including label, status, badge, description, actions, and test name.
	 */
	public function banned_fields_test() {
		$result = array(
			'label'       => __( 'You have correct Ultimate Member fields', 'ultimate-member' ),
			'status'      => 'good',
			'badge'       => array(
				'label' => UM_PLUGIN_NAME,
				'color' => self::BADGE_COLOR,
			),
			'description' => sprintf(
				'<p>%s</p>',
				__( 'Your all custom Ultimate Member fields are correct.', 'ultimate-member' )
			),
			'actions'     => '',
			'test'        => 'um_banned_fields',
		);

		$banned_fields = $this->get_banned_fields();
		if ( false !== $banned_fields ) {
			$result['label']          = __( 'Some field from Ultimate Member forms has banned meta key', 'ultimate-member' );
			$result['status']         = 'critical';
			$result['badge']['color'] = 'red';
			$result['description']    = $banned_fields['description'];
			$result['actions']        = $banned_fields['actions'];
		}

		return $result;
	}

	/**
	 * Retrieve role metadata by slug.
	 *
	 * @todo replace with UM()->roles()->role_data( $slug );
	 *
	 * @param string $slug The slug of the role.
	 *
	 * @return mixed|false Role metadata if found, false if not found.
	 */
	private function get_role_meta( $slug ) {
		return get_option( "um_role_{$slug}_meta", false );
	}

	/**
	 * Retrieve field data based on the field key and field configuration.
	 *
	 * @param string $field_key The key of the field.
	 * @param array $field The configuration of the field.
	 *
	 * @return array Field data including label and value.
	 */
	private function get_field_data( $field_key, $field ) {
		$row   = array_key_exists( 'type', $field ) && 'row' === $field['type'];
		$title = $row ? __( 'Row: ', 'ultimate-member' ) . $field['id'] : __( 'Field: ', 'ultimate-member' ) . $field['metakey'];
		$field = array_map(
			function ( $item ) {
				if ( is_array( $item ) ) {
					$item = maybe_serialize( $item );
				}
				return $item;
			},
			$field
		);

		return array(
			'field_' . $field_key => array(
				'label' => $title,
				'value' => $field,
			),
		);
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
		$info['ultimate-member'] = array(
			'label'       => __( 'Ultimate Member Settings', 'ultimate-member' ),
			'description' => __( 'This debug information for your Ultimate Member installation can assist you in getting support.', 'ultimate-member' ),
			'fields'      => $this->settings_debug_information(),
		);

		// User roles settings
		$info = $this->roles_debug_information( $info );

		// Forms settings
		$info = $this->forms_debug_information( $info );

		// Members directory
		if ( UM()->options()->get( 'members_page' ) ) {
			$info = $this->member_directories_debug_information( $info );
		}

		/**
		 * Filters the site health information.
		 *
		 * @hook um_site_health_extend
		 *
		 * @since 2.10.3
		 *
		 * @param {array} $info The site health info to be filtered.
		 *
		 * @return {array} The filtered site health info.
		 */
		return apply_filters( 'um_site_health_extend', $info );
	}

	/**
	 * Retrieve debug information for various settings in the application.
	 *
	 * @return array Debug information for general, access, emails, appearance, advanced, and license settings.
	 */
	public function settings_debug_information() {
		// General settings
		$general = $this->general_settings_debug_information();

		// Access settings
		$access = $this->access_settings_debug_information();

		// Emails settings
		$emails = $this->emails_settings_debug_information();

		// Appearance settings
		$appearance = $this->appearance_settings_debug_information();

		// Advanced settings
		$advanced = $this->advanced_settings_debug_information();

		// License settings
		$license = $this->license_settings_debug_information();

		return array_merge( $general, $access, $emails, $appearance, $advanced, $license );
	}

	/**
	 * Retrieve debug information about general settings in Ultimate Member.
	 *
	 * @return array Information about the general settings including pages, user settings, and related options.
	 */
	public function general_settings_debug_information() {
		$labels = self::get_labels();

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

		$pages_settings = array(
			'pages_separator' => array(
				'label' => __( 'General > Pages', 'ultimate-member' ),
				'value' => '---------------------------------------------------------------------',
			),
			'pages'           => array(
				'label' => __( 'Pages', 'ultimate-member' ),
				'value' => $pages,
			),
		);

		// User settings
		$permalink_base = UM()->config()->permalink_base_options;
		$display_name   = UM()->config()->display_name_options;

		$user_settings = array(
			'user_separator' => array(
				'label' => __( 'General > Users', 'ultimate-member' ),
				'value' => '---------------------------------------------------------------------',
			),
			'register_role'  => array(
				'label' => __( 'Registration Default Role', 'ultimate-member' ),
				'value' => ! empty( UM()->options()->get( 'register_role' ) ) ? UM()->options()->get( 'register_role' ) : __( 'Default', 'ultimate-member' ),
			),
			'permalink_base' => array(
				'label' => __( 'Profile Permalink Base', 'ultimate-member' ),
				'value' => isset( $permalink_base[ UM()->options()->get( 'permalink_base' ) ] ) ? $permalink_base[ UM()->options()->get( 'permalink_base' ) ] : $labels['no'],
			),
		);

		if ( 'custom_meta' === UM()->options()->get( 'permalink_base' ) ) {
			$user_settings['permalink_base_custom_meta'] = array(
				'label' => __( 'Profile Permalink Base Custom Meta Key', 'ultimate-member' ),
				'value' => UM()->options()->get( 'permalink_base_custom_meta' ),
			);
		}

		$user_settings['display_name'] = array(
			'label' => __( 'User Display Name', 'ultimate-member' ),
			'value' => isset( $display_name[ UM()->options()->get( 'display_name' ) ] ) ? $display_name[ UM()->options()->get( 'display_name' ) ] : $labels['no'],
		);

		if ( 'field' === UM()->options()->get( 'display_name' ) ) {
			$user_settings['display_name_field'] = array(
				'label' => __( 'Display Name Custom Field(s)', 'ultimate-member' ),
				'value' => UM()->options()->get( 'display_name_field' ),
			);
		}

		$user_settings = array_merge(
			$user_settings,
			array(
				'author_redirect' => array(
					'label' => __( 'Hide author pages (enable author page redirect to user profile)', 'ultimate-member' ),
					'value' => UM()->options()->get( 'author_redirect' ) ? $labels['yes'] : $labels['no'],
				),
				'members_page'    => array(
					'label' => __( 'Enable Members Directory', 'ultimate-member' ),
					'value' => UM()->options()->get( 'members_page' ) ? $labels['yes'] : $labels['no'],
				),
				'use_gravatars'   => array(
					'label' => __( 'Use Gravatars?', 'ultimate-member' ),
					'value' => UM()->options()->get( 'use_gravatars' ) ? $labels['yes'] : $labels['no'],
				),
			)
		);

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

			$user_settings['use_um_gravatar_default_builtin_image'] = array(
				'label' => __( 'Use Gravatar builtin image', 'ultimate-member' ),
				'value' => $gravatar_options[ UM()->options()->get( 'use_um_gravatar_default_builtin_image' ) ],
			);
			if ( 'default' === UM()->options()->get( 'use_um_gravatar_default_builtin_image' ) ) {
				$user_settings['use_um_gravatar_default_image'] = array(
					'label' => __( 'Replace Gravatar\'s Default avatar (Set Default plugin avatar as Gravatar\'s Default avatar)', 'ultimate-member' ),
					'value' => UM()->options()->get( 'use_um_gravatar_default_image' ) ? $labels['yes'] : $labels['no'],
				);
			}
		}

		$user_settings = array_merge(
			$user_settings,
			array(
				'admin_ignore_user_status' => array(
					'label' => __( 'Ignore the "User Role > Registration Options" if this user is added from the wp-admin dashboard', 'ultimate-member' ),
					'value' => UM()->options()->get( 'admin_ignore_user_status' ) ? $labels['yes'] : $labels['no'],
				),
				'delete_comments'          => array(
					'label' => __( 'Delete user comments (enable deleting user comments after deleting a user)', 'ultimate-member' ),
					'value' => UM()->options()->get( 'delete_comments' ) ? $labels['yes'] : $labels['no'],
				),
				'toggle_password'          => array(
					'label' => __( 'Toggle Password Visibility (enable password show/hide icon on password field)', 'ultimate-member' ),
					'value' => UM()->options()->get( 'toggle_password' ) ? $labels['yes'] : $labels['no'],
				),
				'require_strongpass'       => array(
					'label' => __( 'Require Strong Passwords', 'ultimate-member' ),
					'value' => UM()->options()->get( 'require_strongpass' ) ? $labels['yes'] : $labels['no'],
				),
			)
		);

		if ( UM()->options()->get( 'require_strongpass' ) ) {
			$user_settings = array_merge(
				$user_settings,
				array(
					'password_min_chars' => array(
						'label' => __( 'Password minimum length', 'ultimate-member' ),
						'value' => UM()->options()->get( 'password_min_chars' ),
					),
					'password_max_chars' => array(
						'label' => __( 'Password maximum length', 'ultimate-member' ),
						'value' => UM()->options()->get( 'password_max_chars' ),
					),
				)
			);
		}

		$user_settings = array_merge(
			$user_settings,
			array(
				'activation_link_expiry_time' => array(
					'label' => __( 'Email activation link expiration (days)', 'ultimate-member' ),
					'value' => UM()->options()->get( 'activation_link_expiry_time' ) ? UM()->options()->get( 'activation_link_expiry_time' ) : __( 'Not expired', 'ultimate-member' ),
				),
				'profile_noindex'             => array(
					'label' => __( 'Avoid indexing profile by search engines', 'ultimate-member' ),
					'value' => UM()->options()->get( 'profile_noindex' ) ? $labels['yes'] : $labels['no'],
				),
				'profile_title'               => array(
					'label' => __( 'User Profile Title', 'ultimate-member' ),
					'value' => stripslashes( UM()->options()->get( 'profile_title' ) ),
				),
				'profile_desc'                => array(
					'label' => __( 'User Profile Dynamic Meta Description', 'ultimate-member' ),
					'value' => stripslashes( UM()->options()->get( 'profile_desc' ) ),
				),
			)
		);

		// Account settings
		$account_settings = array(
			'account_separator'        => array(
				'label' => __( 'General > Account', 'ultimate-member' ),
				'value' => '---------------------------------------------------------------------',
			),
			'account_name'             => array(
				'label' => __( 'Display First & Last name fields (enable to display First & Last name fields)', 'ultimate-member' ),
				'value' => UM()->options()->get( 'account_name' ) ? $labels['yes'] : $labels['no'],
			),
			'account_email'            => array(
				'label' => __( 'Allow users to change email (enable changing email via the account page)', 'ultimate-member' ),
				'value' => UM()->options()->get( 'account_email' ) ? $labels['yes'] : $labels['no'],
			),
			'account_general_password' => array(
				'label' => __( 'Require password to update account (enable required password)', 'ultimate-member' ),
				'value' => UM()->options()->get( 'account_general_password' ) ? $labels['yes'] : $labels['no'],
			),
			'account_tab_password'     => array(
				'label' => __( 'Password Account Tab', 'ultimate-member' ),
				'value' => UM()->options()->get( 'account_tab_password' ) ? $labels['yes'] : $labels['no'],
			),
			'account_tab_privacy'      => array(
				'label' => __( 'Privacy Account Tab', 'ultimate-member' ),
				'value' => UM()->options()->get( 'account_tab_privacy' ) ? $labels['yes'] : $labels['no'],
			),
		);

		if ( UM()->options()->get( 'account_name' ) ) {
			$account_settings = UM()->array_insert_before(
				$account_settings,
				'account_email',
				array(
					'account_name_disable' => array(
						'label' => __( 'Disable First & Last name field editing', 'ultimate-member' ),
						'value' => UM()->options()->get( 'account_name_disable' ) ? $labels['yes'] : $labels['no'],
					),
					'account_name_require' => array(
						'label' => __( 'Require First & Last Name', 'ultimate-member' ),
						'value' => UM()->options()->get( 'account_name_require' ) ? $labels['yes'] : $labels['no'],
					),
				)
			);
		}

		if ( UM()->options()->get( 'account_tab_privacy' ) ) {
			$account_settings['account_hide_in_directory'] = array(
				'label' => __( 'Allow users to hide their profiles from directory', 'ultimate-member' ),
				'value' => UM()->options()->get( 'account_hide_in_directory' ) ? $labels['yes'] : $labels['no'],
			);

			if ( UM()->options()->get( 'account_hide_in_directory' ) ) {
				$account_settings['account_hide_in_directory_default'] = array(
					'label' => __( 'Hide profiles from directory by default', 'ultimate-member' ),
					'value' => UM()->options()->get( 'account_hide_in_directory_default' ),
				);
			}
		}

		if ( false !== UM()->account()->is_notifications_tab_visible() ) {
			$account_settings['account_tab_notifications'] = array(
				'label' => __( 'Notifications Account Tab', 'ultimate-member' ),
				'value' => UM()->options()->get( 'account_tab_notifications' ) ? $labels['yes'] : $labels['no'],
			);
		}

		$account_settings = array_merge(
			$account_settings,
			array(
				'account_tab_delete' => array(
					'label' => __( 'Delete Account Tab', 'ultimate-member' ),
					'value' => UM()->options()->get( 'account_tab_delete' ) ? $labels['yes'] : $labels['no'],
				),
			)
		);

		if ( UM()->options()->get( 'account_tab_delete' ) ) {
			if ( UM()->account()->current_password_is_required( 'delete' ) ) {
				$account_settings['delete_account_text'] = array(
					'label' => __( 'Account Deletion Text', 'ultimate-member' ),
					'value' => UM()->options()->get( 'delete_account_text' ),
				);
			} else {
				$account_settings['delete_account_no_pass_required_text'] = array(
					'label' => __( 'Account Deletion without password Text', 'ultimate-member' ),
					'value' => UM()->options()->get( 'delete_account_no_pass_required_text' ),
				);
			}
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
			'uploads_separator'         => array(
				'label' => __( 'General > Uploads', 'ultimate-member' ),
				'value' => '---------------------------------------------------------------------',
			),
			'image_orientation_by_exif' => array(
				'label' => __( 'Change image orientation', 'ultimate-member' ),
				'value' => UM()->options()->get( 'image_orientation_by_exif' ) ? $labels['yes'] : $labels['no'],
			),
			'image_compression'         => array(
				'label' => __( 'Image Quality', 'ultimate-member' ),
				'value' => UM()->options()->get( 'image_compression' ),
			),
			'image_max_width'           => array(
				'label' => __( 'Image Upload Maximum Width (px)', 'ultimate-member' ),
				'value' => UM()->options()->get( 'image_max_width' ),
			),
			'profile_photo_max_size'    => array(
				'label' => __( 'Profile Photo Maximum File Size (bytes)', 'ultimate-member' ),
				'value' => UM()->options()->get( 'profile_photo_max_size' ),
			),
			'photo_thumb_sizes'         => array(
				'label' => __( 'Profile Photo Thumbnail Sizes (px)', 'ultimate-member' ),
				'value' => $profile_sizes_list,
			),
			'cover_photo_max_size'      => array(
				'label' => __( 'Cover Photo Maximum File Size (bytes)', 'ultimate-member' ),
				'value' => UM()->options()->get( 'cover_photo_max_size' ),
			),
			'cover_min_width'           => array(
				'label' => __( 'Cover Photo Minimum Width (px)', 'ultimate-member' ),
				'value' => UM()->options()->get( 'cover_min_width' ),
			),
			'cover_thumb_sizes'         => array(
				'label' => __( 'Cover Photo Thumbnail Sizes (px)', 'ultimate-member' ),
				'value' => $cover_sizes_list,
			),
		);

		return array_merge( $pages_settings, $user_settings, $account_settings, $uploads_settings );
	}

	/**
	 * Retrieve debug information related to access settings in Ultimate Member.
	 *
	 * @return array Debug information about access settings, including restrictions, accessible options, and other settings.
	 */
	public function access_settings_debug_information() {
		$labels = self::get_labels();

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
			'access_separator' => array(
				'label' => __( 'Access > Restriction Content', 'ultimate-member' ),
				'value' => '---------------------------------------------------------------------',
			),
			'accessible'       => array(
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
			$restrict_settings['access_redirect']          = array(
				'label' => __( 'Custom Redirect URL', 'ultimate-member' ),
				'value' => UM()->options()->get( 'access_redirect' ),
			);
			$restrict_settings['access_exclude_uris']      = array(
				'label' => __( 'Exclude the following URLs', 'ultimate-member' ),
				'value' => $exclude_uris_list,
			);
			$restrict_settings['home_page_accessible']     = array(
				'label' => __( 'Allow Homepage to be accessible', 'ultimate-member' ),
				'value' => UM()->options()->get( 'home_page_accessible' ) ? $labels['yes'] : $labels['no'],
			);
			$restrict_settings['category_page_accessible'] = array(
				'label' => __( 'Allow Category pages to be accessible', 'ultimate-member' ),
				'value' => UM()->options()->get( 'category_page_accessible' ) ? $labels['yes'] : $labels['no'],
			);
		}

		$restrict_settings['restricted_post_title_replace'] = array(
			'label' => __( 'Restricted Post Title', 'ultimate-member' ),
			'value' => UM()->options()->get( 'restricted_post_title_replace' ) ? $labels['yes'] : $labels['no'],
		);
		if ( UM()->options()->get( 'restricted_post_title_replace' ) ) {
			$restrict_settings['restricted_access_post_title'] = array(
				'label' => __( 'Restricted Access Post Title', 'ultimate-member' ),
				'value' => stripslashes( UM()->options()->get( 'restricted_access_post_title' ) ),
			);
		}

		$restrict_settings['restricted_access_message'] = array(
			'label' => __( 'Restricted Access Message', 'ultimate-member' ),
			'value' => stripslashes( UM()->options()->get( 'restricted_access_message' ) ),
		);
		$restrict_settings['restricted_blocks']         = array(
			'label' => __( 'Restricted Gutenberg Blocks (enable the "Content Restriction" settings for the Gutenberg Blocks)', 'ultimate-member' ),
			'value' => UM()->options()->get( 'restricted_blocks' ) ? $labels['yes'] : $labels['no'],
		);
		if ( UM()->options()->get( 'restricted_blocks' ) ) {
			$restrict_settings['restricted_block_message'] = array(
				'label' => __( 'Restricted Access Block Message', 'ultimate-member' ),
				'value' => stripslashes( UM()->options()->get( 'restricted_block_message' ) ),
			);
		}
		$restrict_settings['restricted_access_post_metabox']     = array(
			'label' => __( 'Enable the "Content Restriction" settings for post types', 'ultimate-member' ),
			'value' => $restricted_posts_list,
		);
		$restrict_settings['restricted_access_taxonomy_metabox'] = array(
			'label' => __( 'Enable the "Content Restriction" settings for taxonomies', 'ultimate-member' ),
			'value' => $restricted_taxonomy_list,
		);

		// Access other settings
		$blocked_emails = str_replace( '<br />', ', ', nl2br( UM()->options()->get( 'blocked_emails' ) ) );
		$blocked_words  = str_replace( '<br />', ', ', nl2br( UM()->options()->get( 'blocked_words' ) ) );

		$access_other_settings = array(
			'access_other_separator'      => array(
				'label' => __( 'Access > Other', 'ultimate-member' ),
				'value' => '---------------------------------------------------------------------',
			),
			'enable_reset_password_limit' => array(
				'label' => __( 'Enable the Reset Password Limit?', 'ultimate-member' ),
				'value' => UM()->options()->get( 'enable_reset_password_limit' ) ? $labels['yes'] : $labels['no'],
			),
		);
		if ( UM()->options()->get( 'enable_reset_password_limit' ) ) {
			$access_other_settings['reset_password_limit_number'] = array(
				'label' => __( 'Password Limit (maximum reset password limit)', 'ultimate-member' ),
				'value' => UM()->options()->get( 'reset_password_limit_number' ),
			);
		}
		$access_other_settings['change_password_request_limit']     = array(
			'label' => __( 'Change Password request limit ', 'ultimate-member' ),
			'value' => UM()->options()->get( 'change_password_request_limit' ) ? $labels['yes'] : $labels['no'],
		);
		$access_other_settings['only_approved_user_reset_password'] = array(
			'label' => __( 'Only approved user Reset Password', 'ultimate-member' ),
			'value' => UM()->options()->get( 'only_approved_user_reset_password' ) ? $labels['yes'] : $labels['no'],
		);
		$access_other_settings['blocked_emails']                    = array(
			'label' => __( 'Blocked Email Addresses', 'ultimate-member' ),
			'value' => stripslashes( $blocked_emails ),
		);
		$access_other_settings['blocked_words']                     = array(
			'label' => __( 'Blacklist Words', 'ultimate-member' ),
			'value' => stripslashes( $blocked_words ),
		);

		return array_merge( $restrict_settings, $access_other_settings );
	}

	/**
	 * Retrieve debug information about the email settings in Ultimate Member.
	 *
	 * @return array Information about the email settings, including labels, values, and template status.
	 */
	public function emails_settings_debug_information() {
		$labels = self::get_labels();

		// Email settings
		$email_settings = array(
			'email_separator' => array(
				'label' => __( 'Emails', 'ultimate-member' ),
				'value' => '---------------------------------------------------------------------',
			),
			'admin_email'     => array(
				'label' => __( 'Admin Email Address', 'ultimate-member' ),
				'value' => UM()->options()->get( 'admin_email' ),
			),
			'mail_from'       => array(
				'label' => __( 'Mail appears from', 'ultimate-member' ),
				'value' => UM()->options()->get( 'mail_from' ),
			),
			'mail_from_addr'  => array(
				'label' => __( 'Mail appears from address', 'ultimate-member' ),
				'value' => UM()->options()->get( 'mail_from_addr' ),
			),
			'email_html'      => array(
				'label' => __( 'Use HTML for Emails?', 'ultimate-member' ),
				'value' => UM()->options()->get( 'email_html' ) ? $labels['yes'] : $labels['no'],
			),
		);

		$emails = UM()->config()->email_notifications;
		foreach ( $emails as $key => $email ) {
			$email_settings[ $key . '-enabled' ] = array(
				// translators: %s is email template title.
				'label' => sprintf( __( 'Email "%s" Enabled', 'ultimate-member' ), $email['title'] ),
				'value' => UM()->options()->get( $key . '_on' ) ? $labels['yes'] : $labels['no'],
			);

			if ( UM()->options()->get( $key . '_on' ) ) {
				$email_settings[ $key . '-subject' ] = array(
					// translators: %s is email template title.
					'label' => sprintf( __( '"%s" Subject', 'ultimate-member' ), $email['title'] ),
					'value' => UM()->options()->get( $key . '_sub' ),
				);

				$email_settings[ $key . '-in-theme' ] = array(
					// translators: %s is email template title.
					'label' => sprintf( __( 'Template "%s" in theme?', 'ultimate-member' ), $email['title'] ),
					'value' => '' !== locate_template( array( 'ultimate-member/emails/' . $key . '.php' ) ) ? $labels['yes'] : $labels['no'],
				);
			}
		}

		return $email_settings;
	}

	/**
	 * Retrieve debug information related to appearance settings in Ultimate Member.
	 *
	 * @return array Debug information about appearance settings, including profile template, profile photo settings, cover photo settings, and more.
	 */
	public function appearance_settings_debug_information() {
		$labels = self::get_labels();

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
		$default_avatar         = UM()->options()->get( 'default_avatar' );
		$default_cover          = UM()->options()->get( 'default_cover' );

		$appearance_settings = array(
			'appearance_separator'         => array(
				'label' => __( 'Appearance > Profile', 'ultimate-member' ),
				'value' => '---------------------------------------------------------------------',
			),
			'profile_template'             => array(
				'label' => __( 'Profile Default Template', 'ultimate-member' ),
				// translators: %1$s - profile template name, %2$s - profile template filename
				'value' => sprintf( __( '%1$s (filename: %2$s.php)', 'ultimate-member' ), $profile_template_title, $profile_template_key ),
			),
			'profile_max_width'            => array(
				'label' => __( 'Profile Maximum Width', 'ultimate-member' ),
				'value' => UM()->options()->get( 'profile_max_width' ),
			),
			'profile_area_max_width'       => array(
				'label' => __( 'Profile Area Maximum Width', 'ultimate-member' ),
				'value' => UM()->options()->get( 'profile_area_max_width' ),
			),
			'default_avatar'               => array(
				'label' => __( 'Default Profile Photo', 'ultimate-member' ),
				'value' => ! empty( $default_avatar['url'] ) ? $default_avatar['url'] : '',
			),
			'disable_profile_photo_upload' => array(
				'label' => __( 'Disable Profile Photo Upload', 'ultimate-member' ),
				'value' => UM()->options()->get( 'disable_profile_photo_upload' ) ? $labels['yes'] : $labels['no'],
			),
			'profile_photosize'            => array(
				'label' => __( 'Profile Photo Size', 'ultimate-member' ),
				'value' => UM()->options()->get( 'profile_photosize' ) . 'x' . UM()->options()->get( 'profile_photosize' ) . 'px',
			),
			'default_cover'                => array(
				'label' => __( 'Default Cover Photo', 'ultimate-member' ),
				'value' => ! empty( $default_cover['url'] ) ? $default_cover['url'] : '',
			),
			'profile_cover_enabled'        => array(
				'label' => __( 'Profile Cover Photos', 'ultimate-member' ),
				'value' => $profile_cover_enabled ? $labels['yes'] : $labels['no'],
			),
		);

		if ( ! empty( $profile_cover_enabled ) ) {
			$appearance_settings['profile_coversize']   = array(
				'label' => __( 'Profile Cover Size', 'ultimate-member' ),
				'value' => UM()->options()->get( 'profile_coversize' ) . 'px',
			);
			$appearance_settings['profile_cover_ratio'] = array(
				'label' => __( 'Profile Cover Ratio', 'ultimate-member' ),
				'value' => UM()->options()->get( 'profile_cover_ratio' ),
			);
		}

		$appearance_settings = array_merge(
			$appearance_settings,
			array(
				'profile_show_metaicon'     => array(
					'label' => __( 'Profile Header Meta Text Icon', 'ultimate-member' ),
					'value' => UM()->options()->get( 'profile_show_metaicon' ) ? $labels['yes'] : $labels['no'],
				),
				'profile_show_name'         => array(
					'label' => __( 'Show display name in profile header', 'ultimate-member' ),
					'value' => UM()->options()->get( 'profile_show_name' ) ? $labels['yes'] : $labels['no'],
				),
				'profile_show_social_links' => array(
					'label' => __( 'Show social links in profile header', 'ultimate-member' ),
					'value' => UM()->options()->get( 'profile_show_social_links' ) ? $labels['yes'] : $labels['no'],
				),
				'profile_show_bio'          => array(
					'label' => __( 'Show user description in profile header', 'ultimate-member' ),
					'value' => UM()->options()->get( 'profile_show_bio' ) ? $labels['yes'] : $labels['no'],
				),
			)
		);

		if ( UM()->options()->get( 'profile_show_bio' ) ) {
			$appearance_settings['profile_bio_maxchars'] = array(
				'label' => __( 'User description maximum chars', 'ultimate-member' ),
				'value' => UM()->options()->get( 'profile_bio_maxchars' ),
			);
		}

		$appearance_settings = array_merge(
			$appearance_settings,
			array(
				'profile_show_html_bio'    => array(
					'label' => __( 'Enable HTML support for user description', 'ultimate-member' ),
					'value' => UM()->options()->get( 'profile_show_html_bio' ) ? $labels['yes'] : $labels['no'],
				),
				'profile_header_menu'      => array(
					'label' => __( 'Profile Header Menu Position', 'ultimate-member' ),
					'value' => $profile_header_menu_options[ UM()->options()->get( 'profile_header_menu' ) ],
				),
				'profile_primary_btn_word' => array(
					'label' => __( 'Profile Primary Button Text', 'ultimate-member' ),
					'value' => UM()->options()->get( 'profile_primary_btn_word' ),
				),
				'profile_secondary_btn'    => array(
					'label' => __( 'Profile Secondary Button', 'ultimate-member' ),
					'value' => $profile_secondary_btn ? $labels['yes'] : $labels['no'],
				),
			)
		);

		if ( ! empty( $profile_secondary_btn ) ) {
			$appearance_settings['profile_secondary_btn_word'] = array(
				'label' => __( 'Profile Secondary Button Text ', 'ultimate-member' ),
				'value' => UM()->options()->get( 'profile_secondary_btn_word' ),
			);
		}

		$appearance_settings = array_merge(
			$appearance_settings,
			array(
				'profile_icons'      => array(
					'label' => __( 'Profile Field Icons', 'ultimate-member' ),
					'value' => $icons_display_options[ UM()->options()->get( 'profile_icons' ) ],
				),
				'profile_empty_text' => array(
					'label' => __( 'Show a custom message if profile is empty', 'ultimate-member' ),
					'value' => $profile_empty_text ? $labels['yes'] : $labels['no'],
				),
			)
		);

		if ( ! empty( $profile_empty_text ) ) {
			$appearance_settings['profile_empty_text_emo'] = array(
				'label' => __( 'Show the emoticon', 'ultimate-member' ),
				'value' => UM()->options()->get( 'profile_empty_text_emo' ) ? $labels['yes'] : $labels['no'],
			);
		}

		// > Profile Menu section.
		$profile_menu = UM()->options()->get( 'profile_menu' );

		$appearance_settings['appearance_profile_menu_separator'] = array(
			'label' => __( 'Appearance > Profile Menu', 'ultimate-member' ),
			'value' => '---------------------------------------------------------------------',
		);

		$appearance_settings['profile_menu'] = array(
			'label' => __( 'Enable profile menu', 'ultimate-member' ),
			'value' => $profile_menu ? $labels['yes'] : $labels['no'],
		);

		if ( ! empty( $profile_menu ) ) {
			$privacy_option = UM()->profile()->tabs_privacy();

			$tabs = UM()->profile()->tabs();
			foreach ( $tabs as $id => $tab ) {
				if ( ! empty( $tab['hidden'] ) ) {
					continue;
				}

				$tab_enabled = UM()->options()->get( 'profile_tab_' . $id );

				$appearance_settings[ 'profile_tab_' . $id ] = array(
					// translators: %s Profile Tab Title
					'label' => sprintf( __( '%s Tab', 'ultimate-member' ), $tab['name'] ),
					'value' => $tab_enabled ? $labels['yes'] : $labels['no'],
				);

				if ( ! isset( $tab['default_privacy'] ) && ! empty( $tab_enabled ) ) {
					$privacy = UM()->options()->get( 'profile_tab_' . $id . '_privacy' );
					if ( is_numeric( $privacy ) ) {
						$appearance_settings[ 'profile_tab_' . $id . '_privacy' ] = array(
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

			$appearance_settings['profile_menu_default_tab'] = array(
				'label' => __( 'Profile menu default tab', 'ultimate-member' ),
				'value' => isset( $tabs[ UM()->options()->get( 'profile_menu_default_tab' ) ]['name'] ) ? $tabs[ UM()->options()->get( 'profile_menu_default_tab' ) ]['name'] : '',
			);
			$appearance_settings['profile_menu_icons']       = array(
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

		$appearance_settings = array_merge(
			$appearance_settings,
			array(
				'appearance_register_separator' => array(
					'label' => __( 'Appearance > Registration Form', 'ultimate-member' ),
					'value' => '---------------------------------------------------------------------',
				),
				'register_template'             => array(
					'label' => __( 'Registration Default Template', 'ultimate-member' ),
					// translators: %1$s - register template name, %2$s - register template filename
					'value' => sprintf( __( '%1$s (filename: %2$s.php)', 'ultimate-member' ), $register_template_title, $register_template_key ),
				),
				'register_max_width'            => array(
					'label' => __( 'Registration Maximum Width', 'ultimate-member' ),
					'value' => UM()->options()->get( 'register_max_width' ),
				),
				'register_align'                => array(
					'label' => __( 'Registration Shortcode Alignment', 'ultimate-member' ),
					'value' => $form_align_options[ UM()->options()->get( 'register_align' ) ],
				),
				'register_primary_btn_word'     => array(
					'label' => __( 'Registration Primary Button Text ', 'ultimate-member' ),
					'value' => UM()->options()->get( 'register_primary_btn_word' ),
				),
				'register_secondary_btn'        => array(
					'label' => __( 'Registration Secondary Button', 'ultimate-member' ),
					'value' => $register_secondary_btn ? $labels['yes'] : $labels['no'],
				),
			)
		);

		if ( ! empty( $register_secondary_btn ) ) {
			$appearance_settings['register_secondary_btn_word'] = array(
				'label' => __( 'Registration Secondary Button Text', 'ultimate-member' ),
				'value' => UM()->options()->get( 'register_secondary_btn_word' ),
			);
			$appearance_settings['register_secondary_btn_url']  = array(
				'label' => __( 'Registration Secondary Button URL', 'ultimate-member' ),
				'value' => UM()->options()->get( 'register_secondary_btn_url' ),
			);
		}

		$appearance_settings['register_icons'] = array(
			'label' => __( 'Registration Field Icons', 'ultimate-member' ),
			'value' => $icons_display_options[ UM()->options()->get( 'register_icons' ) ],
		);

		// > Login Form section.
		$login_templates      = UM()->shortcodes()->get_templates( 'login' );
		$login_template_key   = UM()->options()->get( 'login_template' );
		$login_template_title = array_key_exists( $login_template_key, $login_templates ) ? $login_templates[ $login_template_key ] : __( 'No template name', 'ultimate-member' );
		$login_secondary_btn  = UM()->options()->get( 'login_secondary_btn' );

		$appearance_settings = array_merge(
			$appearance_settings,
			array(
				'appearance_login_separator' => array(
					'label' => __( 'Appearance > Login Form', 'ultimate-member' ),
					'value' => '---------------------------------------------------------------------',
				),
				'login_template'             => array(
					'label' => __( 'Login Default Template', 'ultimate-member' ),
					// translators: %1$s - login template name, %2$s - login template filename
					'value' => sprintf( __( '%1$s (filename: %2$s.php)', 'ultimate-member' ), $login_template_title, $login_template_key ),
				),
				'login_max_width'            => array(
					'label' => __( 'Login Maximum Width', 'ultimate-member' ),
					'value' => UM()->options()->get( 'login_max_width' ),
				),
				'login_align'                => array(
					'label' => __( 'Login Shortcode Alignment', 'ultimate-member' ),
					'value' => $form_align_options[ UM()->options()->get( 'login_align' ) ],
				),
				'login_primary_btn_word'     => array(
					'label' => __( 'Login Primary Button Text', 'ultimate-member' ),
					'value' => UM()->options()->get( 'login_primary_btn_word' ),
				),
				'login_secondary_btn'        => array(
					'label' => __( 'Login Secondary Button', 'ultimate-member' ),
					'value' => $login_secondary_btn ? $labels['yes'] : $labels['no'],
				),
			)
		);

		if ( ! empty( $login_secondary_btn ) ) {
			$appearance_settings['login_secondary_btn_word'] = array(
				'label' => __( 'Login Secondary Button Text', 'ultimate-member' ),
				'value' => UM()->options()->get( 'login_secondary_btn_word' ),
			);
			$appearance_settings['login_secondary_btn_url']  = array(
				'label' => __( 'Login Secondary Button URL', 'ultimate-member' ),
				'value' => UM()->options()->get( 'login_secondary_btn_url' ),
			);
		}

		$appearance_settings = array_merge(
			$appearance_settings,
			array(
				'login_forgot_pass_link' => array(
					'label' => __( 'Login Forgot Password Link', 'ultimate-member' ),
					'value' => UM()->options()->get( 'login_forgot_pass_link' ) ? $labels['yes'] : $labels['no'],
				),
				'login_show_rememberme'  => array(
					'label' => __( 'Show "Remember Me" checkbox', 'ultimate-member' ),
					'value' => UM()->options()->get( 'login_show_rememberme' ),
				),
				'login_icons'            => array(
					'label' => __( 'Login Field Icons', 'ultimate-member' ),
					'value' => $icons_display_options[ UM()->options()->get( 'login_icons' ) ],
				),
			)
		);

		return $appearance_settings;
	}

	/**
	 * Retrieve advanced settings debug information for Ultimate Member.
	 *
	 * @return array An array containing detailed information about various advanced settings, including labels and values.
	 */
	public function advanced_settings_debug_information() {
		$labels = self::get_labels();

		$general = array(
			'advanced_separator'        => array(
				'label' => __( 'Advanced > General', 'ultimate-member' ),
				'value' => '---------------------------------------------------------------------',
			),
			'form_asterisk'             => array(
				'label' => __( 'Required fields\' asterisk (Show an asterisk for required fields)', 'ultimate-member' ),
				'value' => UM()->options()->get( 'form_asterisk' ) ? $labels['yes'] : $labels['no'],
			),
			'profile_object_cache_stop' => array(
				'label' => __( 'Disable Cache User Profile', 'ultimate-member' ),
				'value' => UM()->options()->get( 'um_profile_object_cache_stop' ) ? $labels['yes'] : $labels['no'],
			),
			'uninstall_on_delete'       => array(
				'label' => __( 'Remove Data on Uninstall?', 'ultimate-member' ),
				'value' => UM()->options()->get( 'uninstall_on_delete' ) ? $labels['yes'] : $labels['no'],
			),
		);

		$feature = array(
			'advanced_features_separator'     => array(
				'label' => __( 'Advanced > Features', 'ultimate-member' ),
				'value' => '---------------------------------------------------------------------',
			),
			'enable_blocks'                   => array(
				'label' => __( 'Enable Gutenberg Blocks', 'ultimate-member' ),
				'value' => UM()->options()->get( 'enable_blocks' ) ? $labels['yes'] : $labels['no'],
			),
			'member_directory_own_table'      => array(
				'label' => __( 'Enable custom table for usermeta', 'ultimate-member' ),
				'value' => UM()->options()->get( 'member_directory_own_table' ) ? $labels['yes'] : $labels['no'],
			),
			'enable_as_email_sending'         => array(
				'label' => __( 'Email sending by Action Scheduler', 'ultimate-member' ),
				'value' => UM()->options()->get( 'enable_as_email_sending' ) ? $labels['yes'] : $labels['no'],
			),
			'disable_restriction_pre_queries' => array(
				'label' => __( 'Disable pre-queries for restriction content logic (advanced)', 'ultimate-member' ),
				'value' => UM()->options()->get( 'disable_restriction_pre_queries' ) ? $labels['yes'] : $labels['no'],
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
			'advanced_security_separator' => array(
				'label' => __( 'Advanced > Security', 'ultimate-member' ),
				'value' => '---------------------------------------------------------------------',
			),
			'ajax_nopriv_rate_limit'      => array(
				'label' => __( 'Enable Rate Limiting', 'ultimate-member' ),
				'value' => UM()->options()->get( 'ajax_nopriv_rate_limit' ) ? $labels['yes'] : $labels['no'],
			),
			'banned_capabilities'         => array(
				'label' => __( 'Banned Administrative Capabilities', 'ultimate-member' ),
				'value' => ! empty( $banned_capabilities ) ? implode( ', ', $banned_capabilities ) : '',
			),
			'lock_register_forms'         => array(
				'label' => __( 'Lock All Register Forms', 'ultimate-member' ),
				'value' => UM()->options()->get( 'lock_register_forms' ) ? $labels['yes'] : $labels['no'],
			),
			'display_login_form_notice'   => array(
				'label' => __( 'Display Login form notice to reset passwords', 'ultimate-member' ),
				'value' => UM()->options()->get( 'display_login_form_notice' ) ? $labels['yes'] : $labels['no'],
			),
			'secure_ban_admins_accounts'  => array(
				'label' => __( 'Administrative capabilities ban (enable ban for administrative capabilities)', 'ultimate-member' ),
				'value' => $secure_ban_admins_accounts ? $labels['yes'] : $labels['no'],
			),
		);
		if ( ! empty( $secure_ban_admins_accounts ) ) {
			$secure_notify_admins_banned_accounts = UM()->options()->get( 'secure_notify_admins_banned_accounts' );

			$secure_settings['secure_notify_admins_banned_accounts'] = array(
				'label' => __( 'Notify Administrators', 'ultimate-member' ),
				'value' => $secure_notify_admins_banned_accounts ? $labels['yes'] : $labels['no'],
			);
			if ( ! empty( $secure_notify_admins_banned_accounts ) ) {
				$secure_notify_admins_banned_accounts_options = array(
					'instant' => __( 'Send Immediately', 'ultimate-member' ),
					'hourly'  => __( 'Hourly', 'ultimate-member' ),
					'daily'   => __( 'Daily', 'ultimate-member' ),
				);

				$secure_settings['secure_notify_admins_banned_accounts__interval'] = array(
					'label' => __( 'Notification Schedule', 'ultimate-member' ),
					'value' => $secure_notify_admins_banned_accounts_options[ UM()->options()->get( 'secure_notify_admins_banned_accounts__interval' ) ],
				);
			}
		}

		$secure_allowed_redirect_hosts = UM()->options()->get( 'secure_allowed_redirect_hosts' );
		$secure_allowed_redirect_hosts = explode( PHP_EOL, $secure_allowed_redirect_hosts );

		$secure_settings['secure_allowed_redirect_hosts'] = array(
			'label' => __( 'Allowed hosts for safe redirect', 'ultimate-member' ),
			'value' => $secure_allowed_redirect_hosts,
		);

		$developers = array(
			'advanced_developers_separator' => array(
				'label' => __( 'Advanced > Developers', 'ultimate-member' ),
				'value' => '---------------------------------------------------------------------',
			),
			'allowed_choice_callbacks'      => array(
				'label' => __( 'Allowed Choice Callbacks', 'ultimate-member' ),
				'value' => UM()->options()->get( 'allowed_choice_callbacks' ),
			),
			'rest_api_version'              => array(
				'label' => __( 'REST API version', 'ultimate-member' ),
				'value' => UM()->options()->get( 'rest_api_version' ),
			),
			'allow_url_redirect_confirm'    => array(
				'label' => __( 'Allow external link redirect confirm (enable JS.confirm for external links)', 'ultimate-member' ),
				'value' => UM()->options()->get( 'allow_url_redirect_confirm' ) ? $labels['yes'] : $labels['no'],
			),
		);

		return array_merge( $general, $feature, $secure_settings, $developers );
	}

	/**
	 * Retrieve license settings debug information for Ultimate Member.
	 *
	 * @return array License settings for Site Health, including label and value.
	 */
	public function license_settings_debug_information() {
		// Licenses settings.
		$license_settings = array(
			'licenses' => array(
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
		return apply_filters( 'um_licenses_site_health', $license_settings );
	}

	/**
	 * Retrieve information about roles and role settings in the site health check.
	 *
	 * @param array $info The existing site health information array.
	 *
	 * @return array Updated site health information including user roles and their settings.
	 */
	public function roles_debug_information( $info ) {
		$labels = self::get_labels();

		$all_roles = UM()->roles()->get_roles();

		// User roles settings
		$roles_array = array();
		foreach ( $all_roles as $slug => $role ) {
			if ( strpos( $slug, 'um_' ) === 0 ) {
				$slug = substr( $slug, 3 );
			}
			$rolemeta = $this->get_role_meta( $slug );
			if ( false === $rolemeta ) {
				continue;
			}
			$priority = ! empty( $rolemeta['_um_priority'] ) ? $rolemeta['_um_priority'] : 0;

			$k                 = $priority . '-' . $role;
			$roles_array[ $k ] = $role . '(' . $priority . ')';

			krsort( $roles_array, SORT_NUMERIC );
		}

		$info['ultimate-member-user-roles'] = array(
			'label'       => __( 'Ultimate Member User Roles', 'ultimate-member' ),
			'description' => __( 'This debug information about user roles.', 'ultimate-member' ),
			'fields'      => array(
				'roles'         => array(
					'label' => __( 'User Roles (priority)', 'ultimate-member' ),
					'value' => implode( ', ', $roles_array ),
				),
				'register_role' => array(
					'label' => __( 'WordPress Default New User Role', 'ultimate-member' ),
					'value' => $all_roles[ get_option( 'default_role' ) ],
				),
			),
		);

		foreach ( $all_roles as $slug => $role ) {
			if ( strpos( $slug, 'um_' ) === 0 ) {
				$slug = substr( $slug, 3 );
			}

			$rolemeta = $this->get_role_meta( $slug );
			if ( false === $rolemeta ) {
				continue;
			}

			$debug_info = array();

			if ( array_key_exists( '_um_can_access_wpadmin', $rolemeta ) ) {
				$debug_info[] = array(
					'can_access_wpadmin' => array(
						'label' => __( 'Can access wp-admin?', 'ultimate-member' ),
						'value' => $rolemeta['_um_can_access_wpadmin'] ? $labels['yes'] : $labels['no'],
					),
				);
			}

			if ( array_key_exists( '_um_can_not_see_adminbar', $rolemeta ) ) {
				$debug_info[] = array(
					'can_not_see_adminbar' => array(
						'label' => __( 'Force hiding adminbar in frontend?', 'ultimate-member' ),
						'value' => $rolemeta['_um_can_not_see_adminbar'] ? $labels['yes'] : $labels['no'],
					),
				);
			}

			if ( array_key_exists( '_um_can_edit_everyone', $rolemeta ) ) {
				$debug_info[] = array(
					'can_edit_everyone' => array(
						'label' => __( 'Can edit other member accounts?', 'ultimate-member' ),
						'value' => $rolemeta['_um_can_edit_everyone'] ? $labels['yes'] : $labels['no'],
					),
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

				$debug_info[] = array(
					'can_edit_roles' => array(
						'label' => __( 'Can edit these user roles only', 'ultimate-member' ),
						'value' => ! empty( $can_edit_roles ) ? implode( ', ', $can_edit_roles ) : $labels['all'],
					),
				);
			}

			if ( array_key_exists( '_um_can_delete_everyone', $rolemeta ) ) {
				$debug_info[] = array(
					'can_delete_everyone' => array(
						'label' => __( 'Can delete other member accounts?', 'ultimate-member' ),
						'value' => $rolemeta['_um_can_delete_everyone'] ? $labels['yes'] : $labels['no'],
					),
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

				$debug_info[] = array(
					'can_delete_roles' => array(
						'label' => __( 'Can delete these user roles only', 'ultimate-member' ),
						'value' => ! empty( $can_delete_roles ) ? implode( ', ', $can_delete_roles ) : $labels['all'],
					),
				);
			}

			if ( array_key_exists( '_um_can_edit_profile', $rolemeta ) ) {
				$debug_info[] = array(
					'can_edit_profile' => array(
						'label' => __( 'Can edit their profile?', 'ultimate-member' ),
						'value' => $rolemeta['_um_can_edit_profile'] ? $labels['yes'] : $labels['no'],
					),
				);
			}

			if ( array_key_exists( '_um_can_delete_profile', $rolemeta ) ) {
				$debug_info[] = array(
					'can_delete_profile' => array(
						'label' => __( 'Can delete their account?', 'ultimate-member' ),
						'value' => $rolemeta['_um_can_delete_profile'] ? $labels['yes'] : $labels['no'],
					),
				);
			}

			if ( array_key_exists( '_um_can_view_all', $rolemeta ) ) {
				$debug_info[] = array(
					'can_view_all' => array(
						'label' => __( 'Can view other member profiles?', 'ultimate-member' ),
						'value' => $rolemeta['_um_can_view_all'] ? $labels['yes'] : $labels['no'],
					),
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

				$debug_info[] = array(
					'can_view_roles' => array(
						'label' => __( 'Can view these user roles only', 'ultimate-member' ),
						'value' => ! empty( $can_view_roles ) ? implode( ', ', $can_view_roles ) : $labels['all'],
					),
				);
			}

			if ( array_key_exists( '_um_can_make_private_profile', $rolemeta ) ) {
				$debug_info[] = array(
					'can_make_private_profile' => array(
						'label' => __( 'Can make their profile private?', 'ultimate-member' ),
						'value' => $rolemeta['_um_can_make_private_profile'] ? $labels['yes'] : $labels['no'],
					),
				);
			}

			if ( array_key_exists( '_um_can_access_private_profile', $rolemeta ) ) {
				$debug_info[] = array(
					'can_access_private_profile' => array(
						'label' => __( 'Can view/access private profiles?', 'ultimate-member' ),
						'value' => $rolemeta['_um_can_access_private_profile'] ? $labels['yes'] : $labels['no'],
					),
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

			$debug_info[] = array(
				'profile_noindex'  => array(
					'label' => __( 'Avoid indexing profile by search engines', 'ultimate-member' ),
					'value' => $profile_noindex,
				),
				'default_homepage' => array(
					'label' => __( 'Can view default homepage?', 'ultimate-member' ),
					'value' => $default_homepage,
				),
			);

			if ( isset( $rolemeta['_um_default_homepage'] ) && 0 === absint( $rolemeta['_um_default_homepage'] ) ) {
				$debug_info[] = array(
					'redirect_homepage' => array(
						'label' => __( 'Custom Homepage Redirect', 'ultimate-member' ),
						'value' => $rolemeta['_um_redirect_homepage'],
					),
				);
			}

			$status_options = array(
				'approved'  => __( 'Auto Approve', 'ultimate-member' ),
				'checkmail' => __( 'Require Email Activation', 'ultimate-member' ),
				'pending'   => __( 'Require Admin Review', 'ultimate-member' ),
			);

			if ( array_key_exists( '_um_status', $rolemeta ) && isset( $status_options[ $rolemeta['_um_status'] ] ) ) {
				$debug_info[] = array(
					'status' => array(
						'label' => __( 'Registration Status', 'ultimate-member' ),
						'value' => $status_options[ $rolemeta['_um_status'] ],
					),
				);
			}

			if ( array_key_exists( '_um_status', $rolemeta ) && 'approved' === $rolemeta['_um_status'] ) {
				$auto_approve_act = array(
					'redirect_profile' => __( 'Redirect to profile', 'ultimate-member' ),
					'redirect_url'     => __( 'Redirect to URL', 'ultimate-member' ),
				);

				if ( isset( $auto_approve_act[ $rolemeta['_um_auto_approve_act'] ] ) ) {
					$debug_info[] = array(
						'auto_approve_act' => array(
							'label' => __( 'Auto-approve action', 'ultimate-member' ),
							'value' => $auto_approve_act[ $rolemeta['_um_auto_approve_act'] ],
						),
					);
				}

				if ( 'redirect_url' === $rolemeta['_um_auto_approve_act'] && array_key_exists( '_um_auto_approve_url', $rolemeta ) ) {
					$debug_info[] = array(
						'auto_approve_url' => array(
							'label' => __( 'Set Custom Redirect URL', 'ultimate-member' ),
							'value' => $rolemeta['_um_auto_approve_url'],
						),
					);
				}
			} elseif ( array_key_exists( '_um_status', $rolemeta ) && 'checkmail' === $rolemeta['_um_status'] ) {
				$checkmail_action = array(
					'show_message' => __( 'Show custom message', 'ultimate-member' ),
					'redirect_url' => __( 'Redirect to URL', 'ultimate-member' ),
				);

				if ( array_key_exists( '_um_login_email_activate', $rolemeta ) ) {
					$debug_info[] = array(
						'login_email_activate' => array(
							'label' => __( 'Login user after validating the activation link?', 'ultimate-member' ),
							'value' => $rolemeta['_um_login_email_activate'] ? $labels['yes'] : $labels['no'],
						),
					);
				}

				if ( isset( $checkmail_action[ $rolemeta['_um_checkmail_action'] ] ) ) {
					$debug_info[] = array(
						'checkmail_action' => array(
							'label' => __( 'Action to be taken after registration', 'ultimate-member' ),
							'value' => $checkmail_action[ $rolemeta['_um_checkmail_action'] ],
						),
					);
				}

				if ( 'show_message' === $rolemeta['_um_checkmail_action'] ) {
					if ( array_key_exists( '_um_checkmail_message', $rolemeta ) ) {
						$debug_info[] = array(
							'checkmail_message' => array(
								'label' => __( 'Personalize the custom message', 'ultimate-member' ),
								'value' => stripslashes( $rolemeta['_um_checkmail_message'] ),
							),
						);
					}
				} elseif ( array_key_exists( '_um_checkmail_url', $rolemeta ) ) {
					$debug_info[] = array(
						'checkmail_url' => array(
							'label' => __( 'Set Custom Redirect URL', 'ultimate-member' ),
							'value' => $rolemeta['_um_checkmail_url'],
						),
					);
				}

				if ( array_key_exists( '_um_url_email_activate', $rolemeta ) ) {
					$debug_info[] = array(
						'url_email_activate' => array(
							'label' => __( 'URL redirect after email activation', 'ultimate-member' ),
							'value' => $rolemeta['_um_url_email_activate'],
						),
					);
				}
			} elseif ( array_key_exists( '_um_status', $rolemeta ) && 'pending' === $rolemeta['_um_status'] ) {
				$pending_action = array(
					'show_message' => __( 'Show custom message', 'ultimate-member' ),
					'redirect_url' => __( 'Redirect to URL', 'ultimate-member' ),
				);

				if ( array_key_exists( '_um_pending_action', $rolemeta ) ) {
					$debug_info[] = array(
						'pending_action' => array(
							'label' => __( 'Action to be taken after registration', 'ultimate-member' ),
							'value' => $pending_action[ $rolemeta['_um_pending_action'] ],
						),
					);
				}

				if ( 'show_message' === $rolemeta['_um_pending_action'] ) {
					if ( array_key_exists( '_um_pending_message', $rolemeta ) ) {
						$debug_info[] = array(
							'pending_message' => array(
								'label' => __( 'Personalize the custom message', 'ultimate-member' ),
								'value' => stripslashes( $rolemeta['_um_pending_message'] ),
							),
						);
					}
				} elseif ( array_key_exists( '_um_pending_url', $rolemeta ) ) {
					$debug_info[] = array(
						'pending_url' => array(
							'label' => __( 'Set Custom Redirect URL', 'ultimate-member' ),
							'value' => $rolemeta['_um_pending_url'],
						),
					);
				}
			}

			$after_login_options = array(
				'redirect_profile' => __( 'Redirect to profile', 'ultimate-member' ),
				'redirect_url'     => __( 'Redirect to URL', 'ultimate-member' ),
				'refresh'          => __( 'Refresh active page', 'ultimate-member' ),
				'redirect_admin'   => __( 'Redirect to WordPress Admin', 'ultimate-member' ),
			);

			if ( array_key_exists( '_um_after_login', $rolemeta ) && isset( $after_login_options[ $rolemeta['_um_after_login'] ] ) ) {
				$debug_info[] = array(
					'after_login' => array(
						'label' => __( 'Action to be taken after login', 'ultimate-member' ),
						'value' => $after_login_options[ $rolemeta['_um_after_login'] ],
					),
				);

				if ( 'redirect_url' === $rolemeta['_um_after_login'] && array_key_exists( '_um_login_redirect_url', $rolemeta ) ) {
					$debug_info[] = array(
						'login_redirect_url' => array(
							'label' => __( 'Set Custom Redirect URL', 'ultimate-member' ),
							'value' => $rolemeta['_um_login_redirect_url'],
						),
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
				$debug_info[] = array(
					'after_logout' => array(
						'label' => __( 'Action to be taken after logout', 'ultimate-member' ),
						'value' => $redirect_options[ $rolemeta['_um_after_logout'] ],
					),
				);

				if ( 'redirect_url' === $rolemeta['_um_after_logout'] && array_key_exists( '_um_logout_redirect_url', $rolemeta ) ) {
					$debug_info[] = array(
						'logout_redirect_url' => array(
							'label' => __( 'Set Custom Redirect URL', 'ultimate-member' ),
							'value' => $rolemeta['_um_logout_redirect_url'],
						),
					);
				}
			}

			if ( ! isset( $rolemeta['_um_after_delete'] ) ) {
				$rolemeta['_um_after_delete'] = 'redirect_home';
			}
			if ( array_key_exists( '_um_after_delete', $rolemeta ) && isset( $redirect_options[ $rolemeta['_um_after_delete'] ] ) ) {
				$debug_info[] = array(
					'after_delete' => array(
						'label' => __( 'Action to be taken after account is deleted', 'ultimate-member' ),
						'value' => $redirect_options[ $rolemeta['_um_after_delete'] ],
					),
				);

				if ( 'redirect_url' === $rolemeta['_um_after_delete'] && array_key_exists( '_um_delete_redirect_url', $rolemeta ) ) {
					$debug_info[] = array(
						'delete_redirect_url' => array(
							'label' => __( 'Set Custom Redirect URL', 'ultimate-member' ),
							'value' => $rolemeta['_um_delete_redirect_url'],
						),
					);
				}
			}

			if ( ! empty( $rolemeta['wp_capabilities'] ) ) {
				$debug_info[] = array(
					'wp_capabilities' => array(
						'label' => __( 'WP Capabilities', 'ultimate-member' ),
						'value' => $rolemeta['wp_capabilities'],
					),
				);
			}

			/**
			 * Filters user role settings for Site Health.
			 *
			 * @since 2.10.5
			 * @hook um_debug_information_user_role
			 *
			 * @param {array}  $debug_info User Role settings for Site Health.
			 * @param {array}  $rolemeta   User Role metadata.
			 * @param {string} $slug       User Role slug.
			 *
			 * @return {array} User Role settings for Site Health.
			 *
			 * @example <caption>Extend User Role settings for Site Health.</caption>
			 * function um_debug_information_user_role( $debug_info, $rolemeta, $slug ) {
			 *     // your code here
			 *     return $debug_info;
			 * }
			 * add_filter( 'um_debug_information_user_role', 'um_debug_information_user_role', 10, 3 );
			 */
			$debug_info = apply_filters( 'um_debug_information_user_role', $debug_info, $rolemeta, $slug );

			$debug_info = array_merge( ...$debug_info );

			$info[ 'ultimate-member-role-' . $slug ] = array(
				'label'       => ' - ' . $role . __( ' role settings', 'ultimate-member' ),
				'description' => __( 'This debug information about user role.', 'ultimate-member' ),
				'fields'      => $debug_info,
			);
		}

		return $info;
	}

	/**
	 * Retrieve debug information about Ultimate Member forms.
	 *
	 * @param array $info Information array containing debug information.
	 *
	 * @return array Information about Ultimate Member forms including form IDs, titles, settings, and labels.
	 */
	public function forms_debug_information( $info ) {
		$all_forms = get_posts(
			array(
				'post_type'      => 'um_form',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);

		$forms_formatted = array();
		if ( ! empty( $all_forms ) ) {
			foreach ( $all_forms as $form_id ) {
				$forms_formatted[ 'ID#' . $form_id ] = get_the_title( $form_id );
			}
		}

		$info['ultimate-member-forms'] = array(
			'label'       => __( 'Ultimate Member Forms', 'ultimate-member' ),
			'description' => __( 'This debug information for your Ultimate Member forms.', 'ultimate-member' ),
			'fields'      => array(
				'forms' => array(
					'label' => __( 'UM Forms', 'ultimate-member' ),
					'value' => ! empty( $forms_formatted ) ? $forms_formatted : __( 'No Ultimate Member Forms', 'ultimate-member' ),
				),
			),
		);

		if ( ! empty( $all_forms ) ) {
			$labels = self::get_labels();

			$icons_display_options = array(
				'field' => __( 'Show inside text field', 'ultimate-member' ),
				'label' => __( 'Show with label', 'ultimate-member' ),
				'off'   => __( 'Turn off', 'ultimate-member' ),
			);

			foreach ( $all_forms as $form_id ) {
				$form_mode  = get_post_meta( $form_id, '_um_mode', true );
				$debug_info = array(
					array(
						'form-shortcode' => array(
							'label' => __( 'Shortcode', 'ultimate-member' ),
							'value' => '[ultimatemember form_id="' . $form_id . '"]',
						),
						'mode'           => array(
							'label' => __( 'Type', 'ultimate-member' ),
							'value' => $form_mode,
						),
					),
				);

				if ( 'register' === $form_mode ) {
					$debug_info[] = array(
						'use_custom_settings' => array(
							'label' => __( 'Apply custom settings to this form', 'ultimate-member' ),
							'value' => get_post_meta( $form_id, '_um_register_use_custom_settings', true ) ? $labels['yes'] : $labels['no'],
						),
					);

					if ( get_post_meta( $form_id, '_um_register_use_custom_settings', true ) ) {
						$debug_info[] = array(
							'role'             => array(
								'label' => __( 'User registration role', 'ultimate-member' ),
								'value' => 0 === absint( get_post_meta( $form_id, '_um_register_role', true ) ) ? $labels['default'] : get_post_meta( $form_id, '_um_register_role', true ),
							),
							'template'         => array(
								'label' => __( 'Template', 'ultimate-member' ),
								'value' => 0 === absint( get_post_meta( $form_id, '_um_register_template', true ) ) ? $labels['default'] : get_post_meta( $form_id, '_um_register_template', true ),
							),
							'max_width'        => array(
								'label' => __( 'Max. Width (px)', 'ultimate-member' ),
								'value' => get_post_meta( $form_id, '_um_register_max_width', true ),
							),
							'icons'            => array(
								'label' => __( 'Field Icons', 'ultimate-member' ),
								'value' => $icons_display_options[ get_post_meta( $form_id, '_um_register_icons', true ) ],
							),
							'primary_btn_word' => array(
								'label' => __( 'Primary Button Text', 'ultimate-member' ),
								'value' => ! get_post_meta( $form_id, '_um_register_primary_btn_word', true ) ? $labels['default'] : get_post_meta( $form_id, '_um_register_primary_btn_word', true ),
							),
							'secondary_btn'    => array(
								'label' => __( 'Show Secondary Button', 'ultimate-member' ),
								'value' => get_post_meta( $form_id, '_um_register_secondary_btn', true ) ? $labels['yes'] : $labels['no'],
							),
						);

						if ( get_post_meta( $form_id, '_um_register_secondary_btn', true ) ) {
							$debug_info[] = array(
								'secondary_btn_word' => array(
									'label' => __( 'Secondary Button Text', 'ultimate-member' ),
									'value' => get_post_meta( $form_id, '_um_register_secondary_btn_word', true ),
								),
							);
						}
					}

					$debug_info[] = array(
						'use_gdpr' => array(
							'label' => __( 'Enable privacy policy agreement', 'ultimate-member' ),
							'value' => get_post_meta( $form_id, '_um_register_use_gdpr', true ) ? $labels['yes'] : $labels['no'],
						),
					);

					if ( get_post_meta( $form_id, '_um_register_use_gdpr', true ) ) {
						$gdpr_content_id = get_post_meta( $form_id, '_um_register_use_gdpr_content_id', true );

						$debug_info[] = array(
							'use_gdpr_content_id'  => array(
								'label' => __( 'Privacy policy content', 'ultimate-member' ),
								'value' => $gdpr_content_id ? get_the_title( $gdpr_content_id ) . ' (ID#' . $gdpr_content_id . ') ' . get_the_permalink( $gdpr_content_id ) : '',
							),
							'use_gdpr_toggle_show' => array(
								'label' => __( 'Toggle Show text', 'ultimate-member' ),
								'value' => get_post_meta( $form_id, '_um_register_use_gdpr_toggle_show', true ),
							),
							'use_gdpr_toggle_hide' => array(
								'label' => __( 'Toggle Hide text', 'ultimate-member' ),
								'value' => get_post_meta( $form_id, '_um_register_use_gdpr_toggle_hide', true ),
							),
							'use_gdpr_agreement'   => array(
								'label' => __( 'Checkbox agreement description', 'ultimate-member' ),
								'value' => get_post_meta( $form_id, '_um_register_use_gdpr_agreement', true ),
							),
							'use_gdpr_error_text'  => array(
								'label' => __( 'Error Text', 'ultimate-member' ),
								'value' => get_post_meta( $form_id, '_um_register_use_gdpr_error_text', true ),
							),
						);
					}
				} elseif ( 'login' === $form_mode ) {
					$login_redirect_options = array(
						'0'                => __( 'Default', 'ultimate-member' ),
						'redirect_profile' => __( 'Redirect to profile', 'ultimate-member' ),
						'redirect_url'     => __( 'Redirect to URL', 'ultimate-member' ),
						'refresh'          => __( 'Refresh active page', 'ultimate-member' ),
						'redirect_admin'   => __( 'Redirect to WordPress Admin', 'ultimate-member' ),
					);

					$login_after_login = get_post_meta( $form_id, '_um_login_after_login', true );
					$login_after_login = '' === $login_after_login ? '0' : $login_after_login;

					$debug_info[] = array(
						'use_custom_settings' => array(
							'label' => __( 'Apply custom settings to this form', 'ultimate-member' ),
							'value' => get_post_meta( $form_id, '_um_login_use_custom_settings', true ) ? $labels['yes'] : $labels['no'],
						),
					);

					if ( get_post_meta( $form_id, '_um_login_use_custom_settings', true ) ) {
						$debug_info[] = array(
							'template'         => array(
								'label' => __( 'Template', 'ultimate-member' ),
								'value' => 0 === absint( get_post_meta( $form_id, '_um_login_template', true ) ) ? $labels['default'] : get_post_meta( $form_id, '_um_login_template', true ),
							),
							'max_width'        => array(
								'label' => __( 'Max. Width (px)', 'ultimate-member' ),
								'value' => get_post_meta( $form_id, '_um_login_max_width', true ),
							),
							'icons'            => array(
								'label' => __( 'Field Icons', 'ultimate-member' ),
								'value' => $icons_display_options[ get_post_meta( $form_id, '_um_login_icons', true ) ],
							),
							'primary_btn_word' => array(
								'label' => __( 'Primary Button Text', 'ultimate-member' ),
								'value' => ! get_post_meta( $form_id, '_um_login_primary_btn_word', true ) ? $labels['default'] : get_post_meta( $form_id, '_um_login_primary_btn_word', true ),
							),
							'secondary_btn'    => array(
								'label' => __( 'Show Secondary Button', 'ultimate-member' ),
								'value' => get_post_meta( $form_id, '_um_login_secondary_btn', true ) ? $labels['yes'] : $labels['no'],
							),
						);

						if ( get_post_meta( $form_id, '_um_login_secondary_btn', true ) ) {
							$debug_info[] = array(
								'secondary_btn_word' => array(
									'label' => __( 'Secondary Button Text', 'ultimate-member' ),
									'value' => get_post_meta( $form_id, '_um_login_secondary_btn_word', true ),
								),
							);
						}

						$debug_info[] = array(
							'forgot_pass_link' => array(
								'label' => __( 'Show Forgot Password Link?', 'ultimate-member' ),
								'value' => get_post_meta( $form_id, '_um_login_forgot_pass_link', true ) ? $labels['yes'] : $labels['no'],
							),
							'show_rememberme'  => array(
								'label' => __( 'Show "Remember Me"?', 'ultimate-member' ),
								'value' => get_post_meta( $form_id, '_um_login_show_rememberme', true ) ? $labels['yes'] : $labels['no'],
							),
						);
					}

					$debug_info[] = array(
						'after_login' => array(
							'label' => __( 'Redirection after Login', 'ultimate-member' ),
							'value' => $login_redirect_options[ $login_after_login ],
						),
					);

					if ( 'redirect_url' === get_post_meta( $form_id, '_um_login_after_login', true ) ) {
						$debug_info[] = array(
							'redirect_url' => array(
								'label' => __( 'Set Custom Redirect URL', 'ultimate-member' ),
								'value' => get_post_meta( $form_id, '_um_login_redirect_url', true ),
							),
						);
					}
				} elseif ( 'profile' === $form_mode ) {
					$debug_info[] = array(
						'use_custom_settings' => array(
							'label' => __( 'Apply custom settings to this form', 'ultimate-member' ),
							'value' => get_post_meta( $form_id, '_um_profile_use_custom_settings', true ) ? $labels['yes'] : $labels['no'],
						),
					);

					if ( get_post_meta( $form_id, '_um_profile_use_custom_settings', true ) ) {
						$debug_info[] = array(
							'role'             => array(
								'label' => __( 'Make this profile form role-specific', 'ultimate-member' ),
								'value' => ! empty( get_post_meta( $form_id, '_um_profile_role', true ) ) ? get_post_meta( $form_id, '_um_profile_role', true ) : $labels['all'],
							),
							'template'         => array(
								'label' => __( 'Template', 'ultimate-member' ),
								'value' => 0 === absint( get_post_meta( $form_id, '_um_profile_template', true ) ) ? $labels['default'] : get_post_meta( $form_id, '_um_profile_template', true ),
							),
							'max_width'        => array(
								'label' => __( 'Max. Width (px)', 'ultimate-member' ),
								'value' => get_post_meta( $form_id, '_um_profile_max_width', true ),
							),
							'area_max_width'   => array(
								'label' => __( 'Profile Area Max. Width (px)', 'ultimate-member' ),
								'value' => get_post_meta( $form_id, '_um_profile_area_max_width', true ),
							),
							'icons'            => array(
								'label' => __( 'Field Icons', 'ultimate-member' ),
								'value' => $icons_display_options[ get_post_meta( $form_id, '_um_profile_icons', true ) ],
							),
							'primary_btn_word' => array(
								'label' => __( 'Primary Button Text', 'ultimate-member' ),
								'value' => ! get_post_meta( $form_id, '_um_profile_primary_btn_word', true ) ? $labels['default'] : get_post_meta( $form_id, '_um_profile_primary_btn_word', true ),
							),
							'secondary_btn'    => array(
								'label' => __( 'Show Secondary Button', 'ultimate-member' ),
								'value' => get_post_meta( $form_id, '_um_profile_secondary_btn', true ) ? $labels['yes'] : $labels['no'],
							),
						);

						if ( get_post_meta( $form_id, '_um_profile_secondary_btn', true ) ) {
							$debug_info[] = array(
								'secondary_btn_word' => array(
									'label' => __( 'Secondary Button Text', 'ultimate-member' ),
									'value' => get_post_meta( $form_id, '_um_profile_secondary_btn_word', true ),
								),
							);
						}

						$debug_info[] = array(
							'cover_enabled' => array(
								'label' => __( 'Enable Cover Photos', 'ultimate-member' ),
								'value' => get_post_meta( $form_id, '_um_profile_cover_enabled', true ) ? $labels['yes'] : $labels['no'],
							),
						);

						if ( get_post_meta( $form_id, '_um_profile_cover_enabled', true ) ) {
							$debug_info[] = array(
								'coversize'   => array(
									'label' => __( 'Cover Photo Size', 'ultimate-member' ),
									'value' => get_post_meta( $form_id, '_um_profile_coversize', true ),
								),
								'cover_ratio' => array(
									'label' => __( 'Cover photo ratio', 'ultimate-member' ),
									'value' => get_post_meta( $form_id, '_um_profile_cover_ratio', true ),
								),
							);
						}

						$debug_info[] = array(
							'disable_photo_upload' => array(
								'label' => __( 'Disable Profile Photo Upload', 'ultimate-member' ),
								'value' => get_post_meta( $form_id, '_um_profile_disable_photo_upload', true ) ? $labels['yes'] : $labels['no'],
							),
						);

						if ( 0 === absint( get_post_meta( $form_id, '_um_profile_disable_photo_upload', true ) ) ) {
							$debug_info[] = array(
								'photosize'      => array(
									'label' => __( 'Profile Photo Size', 'ultimate-member' ),
									'value' => get_post_meta( $form_id, '_um_profile_photosize', true ),
								),
								'photo_required' => array(
									'label' => __( 'Make Profile Photo Required', 'ultimate-member' ),
									'value' => get_post_meta( $form_id, '_um_profile_photo_required', true ) ? $labels['yes'] : $labels['no'],
								),
							);
						}

						$debug_info[] = array(
							'show_name'         => array(
								'label' => __( 'Show display name in profile header?', 'ultimate-member' ),
								'value' => get_post_meta( $form_id, '_um_profile_show_name', true ) ? $labels['yes'] : $labels['no'],
							),
							'show_social_links' => array(
								'label' => __( 'Show social links in profile header?', 'ultimate-member' ),
								'value' => get_post_meta( $form_id, '_um_profile_show_social_links', true ) ? $labels['yes'] : $labels['no'],
							),
							'show_bio'          => array(
								'label' => __( 'Show user description in profile header?', 'ultimate-member' ),
								'value' => get_post_meta( $form_id, '_um_profile_show_bio', true ) ? $labels['yes'] : $labels['no'],
							),
						);
					}

					$profile_metafields = get_post_meta( $form_id, '_um_profile_metafields', true );
					if ( ! empty( $profile_metafields ) && is_array( $profile_metafields ) ) {
						$debug_info[] = array(
							'metafields-' . $form_id => array(
								'label' => __( 'Field(s) to show in user meta', 'ultimate-member' ),
								'value' => implode( ', ', $profile_metafields ),
							),
						);
					}
				}

				$fields = get_post_meta( $form_id, '_um_custom_fields', true );
				if ( ! empty( $fields ) && is_array( $fields ) ) {
					foreach ( $fields as $field_key => $field ) {
						$debug_info[] = $this->get_field_data( $field_key, $field );
					}
				}

				/**
				 * Filters form settings for Site Health.
				 *
				 * @since 2.10.5
				 * @hook um_debug_information_{$form_mode}_form
				 *
				 * @param {array}  $info Registration form settings for Site Health.
				 * @param {string} $form_id  Registration form ID.
				 *
				 * @return {array} Registration form settings for Site Health.
				 *
				 * @example <caption>Extend Registration form settings for Site Health.</caption>
				 * function um_debug_information_user_role( $info, $form_id ) {
				 *     // your code here
				 *     return $info;
				 * }
				 * add_filter( 'um_debug_information_register_form', 'um_debug_information_register_form', 10, 2 );
				 * @example <caption>Extend Login form settings for Site Health.</caption>
				 * function um_debug_information_login_form( $info, $form_id ) {
				 *     // your code here
				 *     return $info;
				 * }
				 * add_filter( 'um_debug_information_login_form', 'um_debug_information_login_form', 10, 2 );
				 */
				$debug_info = apply_filters( "um_debug_information_{$form_mode}_form", $debug_info, $form_id );

				$debug_info = array_merge( ...$debug_info );

				$info[ 'ultimate-member-form-' . $form_id ] = array(
					// translators: %s is the form title.
					'label'       => sprintf( __( ' - Form "%s" settings', 'ultimate-member' ), get_the_title( $form_id ) ),
					'description' => __( 'This debug information for your Ultimate Member form.', 'ultimate-member' ),
					'fields'      => $debug_info,
				);
			}
		}

		return $info;
	}

	/**
	 * Get information about member directories for site health check.
	 *
	 * @param array $info Additional information for site health check.
	 *
	 * @return array Information about Ultimate Member directories including labels, fields, and debug details.
	 */
	public function member_directories_debug_information( $info ) {
		$all_member_directories = get_posts(
			array(
				'post_type'      => 'um_directory',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);

		$formatted_directories = array();
		foreach ( $all_member_directories as $directory_id ) {
			$formatted_directories[ 'ID#' . $directory_id ] = get_the_title( $directory_id );
		}

		$info['ultimate-member-directories'] = array(
			'label'       => __( 'Ultimate Member Directories', 'ultimate-member' ),
			'description' => __( 'This debug information about Ultimate Member directories.', 'ultimate-member' ),
			'fields'      => array(
				'directory' => array(
					'label' => __( 'Member directories', 'ultimate-member' ),
					'value' => ! empty( $formatted_directories ) ? $formatted_directories : __( 'No member directories', 'ultimate-member' ),
				),
			),
		);

		if ( ! empty( $all_member_directories ) ) {
			$labels  = self::get_labels();
			$options = array_unique( array_merge( UM()->member_directory()->filter_fields, UM()->member_directory()->default_sorting, UM()->member_directory()->sort_fields, UM()->member_directory()->searching_fields ) );

			foreach ( $all_member_directories as $directory_id ) {
				$_um_view_types_value = get_post_meta( $directory_id, '_um_view_types', true );
				$_um_view_types_value = empty( $_um_view_types_value ) ? array( 'grid', 'list' ) : $_um_view_types_value;
				$_um_view_types_value = is_string( $_um_view_types_value ) ? array( $_um_view_types_value ) : $_um_view_types_value;

				$debug_info = array(
					array(
						'shortcode'    => array(
							'label' => __( 'Shortcode', 'ultimate-member' ),
							'value' => '[ultimatemember_directory id="' . $directory_id . '"]',
						),
						'template'     => array(
							'label' => __( 'Template', 'ultimate-member' ),
							'value' => get_post_meta( $directory_id, '_um_directory_template', true ) ? get_post_meta( $directory_id, '_um_directory_template', true ) : $labels['default'],
						),
						'view_types'   => array(
							'label' => __( 'View types', 'ultimate-member' ),
							'value' => implode( ', ', $_um_view_types_value ),
						),
						'default_view' => array(
							'label' => __( 'Default view type', 'ultimate-member' ),
							'value' => get_post_meta( $directory_id, '_um_default_view', true ),
						),
					),
				);

				$directory_roles_meta = get_post_meta( $directory_id, '_um_roles', true );
				$directory_roles      = array();
				if ( ! empty( $directory_roles_meta ) ) {
					if ( is_string( $directory_roles_meta ) ) {
						$directory_roles = array( $directory_roles_meta );
					} else {
						$directory_roles = $directory_roles_meta;
					}
				}

				$directory_show_these_users_meta = get_post_meta( $directory_id, '_um_show_these_users', true );
				$show_these_users                = array();
				if ( ! empty( $directory_show_these_users_meta ) ) {
					if ( is_string( $directory_show_these_users_meta ) ) {
						$show_these_users = array( $directory_show_these_users_meta );
					} else {
						$show_these_users = $directory_show_these_users_meta;
					}
				}

				$directory_exclude_these_users_meta = get_post_meta( $directory_id, '_um_exclude_these_users', true );
				$exclude_these_users                = array();
				if ( ! empty( $directory_exclude_these_users_meta ) ) {
					if ( is_string( $directory_exclude_these_users_meta ) ) {
						$exclude_these_users = array( $directory_exclude_these_users_meta );
					} else {
						$exclude_these_users = $directory_exclude_these_users_meta;
					}
				}

				$debug_info[] = array(
					'roles'               => array(
						'label' => __( 'User Roles to display', 'ultimate-member' ),
						'value' => ! empty( $directory_roles ) ? implode( ', ', $directory_roles ) : $labels['all'],
					),
					'has_profile_photo'   => array(
						'label' => __( 'Only show members who have uploaded a profile photo', 'ultimate-member' ),
						'value' => get_post_meta( $directory_id, '_um_has_profile_photo', true ) ? $labels['yes'] : $labels['no'],
					),
					'has_cover_photo'     => array(
						'label' => __( 'Only show members who have uploaded a cover photo', 'ultimate-member' ),
						'value' => get_post_meta( $directory_id, '_um_has_cover_photo', true ) ? $labels['yes'] : $labels['no'],
					),
					'show_these_users'    => array(
						'label' => __( 'Only show specific users', 'ultimate-member' ),
						'value' => ! empty( $show_these_users ) ? implode( ', ', $show_these_users ) : $labels['empty'],
					),
					'exclude_these_users' => array(
						'label' => __( 'Exclude specific users', 'ultimate-member' ),
						'value' => ! empty( $exclude_these_users ) ? implode( ', ', $exclude_these_users ) : $labels['empty'],
					),
				);

				/**
				 * Filters general member directory settings for Site Health.
				 *
				 * @since 2.10.5
				 * @hook um_debug_member_directory_general_extend
				 *
				 * @param {array}  $info Member directory settings for Site Health.
				 * @param {string} $directory_id  Member directory ID.
				 *
				 * @return {array} Member directory settings for Site Health.
				 *
				 * @example <caption>Extend Member directory settings for Site Health.</caption>
				 * function um_debug_member_directory_general_extend( $info, $directory_id ) {
				 *     // your code here
				 *     return $info;
				 * }
				 * add_filter( 'um_debug_member_directory_general_extend', 'um_debug_member_directory_general_extend', 10, 2 );
				 */
				$debug_info = apply_filters( 'um_debug_member_directory_general_extend', $debug_info, $directory_id );

				$md_privacy_options = array(
					0 => __( 'Anyone', 'ultimate-member' ),
					1 => __( 'Guests only', 'ultimate-member' ),
					2 => __( 'Members only', 'ultimate-member' ),
					3 => __( 'Only specific roles', 'ultimate-member' ),
				);
				$directory_privacy  = get_post_meta( $directory_id, '_um_privacy', true );

				$debug_info[] = array(
					'privacy' => array(
						'label' => __( 'Who can see this member directory', 'ultimate-member' ),
						'value' => array_key_exists( $directory_privacy, $md_privacy_options ) ? $md_privacy_options[ $directory_privacy ] : __( 'Invalid', 'ultimate-member' ),
					),
				);
				if ( 3 === absint( $directory_privacy ) ) {
					$directory_privacy_roles = get_post_meta( $directory_id, '_um_privacy_roles', true );
					$directory_privacy_roles = ! empty( $directory_privacy_roles ) && is_array( $directory_privacy_roles ) ? $directory_privacy_roles : array();

					$debug_info[] = array(
						'privacy_roles' => array(
							'label' => __( 'Allowed roles', 'ultimate-member' ),
							'value' => $directory_privacy_roles,
						),
					);
				}

				if ( isset( $options[ get_post_meta( $directory_id, '_um_sortby', true ) ] ) ) {
					$sortby_label = $options[ get_post_meta( $directory_id, '_um_sortby', true ) ];
				} else {
					$sortby_label = get_post_meta( $directory_id, '_um_sortby', true );
				}

				$debug_info[] = array(
					'sortby' => array(
						'label' => __( 'Default sort users by', 'ultimate-member' ),
						'value' => $sortby_label,
					),
				);

				if ( 'other' === get_post_meta( $directory_id, '_um_sortby', true ) ) {
					$debug_info[] = array(
						'sortby_custom'       => array(
							'label' => __( 'Meta key', 'ultimate-member' ),
							'value' => get_post_meta( $directory_id, '_um_sortby_custom', true ),
						),
						'sortby_custom_type'  => array(
							'label' => __( 'Data type', 'ultimate-member' ),
							'value' => UM()->member_directory()->sort_data_types[ get_post_meta( $directory_id, '_um_sortby_custom_type', true ) ],
						),
						'sortby_custom_order' => array(
							'label' => __( 'Order', 'ultimate-member' ),
							'value' => 'ASC' === get_post_meta( $directory_id, '_um_sortby_custom_order', true ) ? __( 'Ascending', 'ultimate-member' ) : __( 'Descending', 'ultimate-member' ),
						),
						'sortby_custom_label' => array(
							'label' => __( 'Label of custom sort', 'ultimate-member' ),
							'value' => get_post_meta( $directory_id, '_um_sortby_custom_label', true ),
						),
					);
				}

				$debug_info[] = array(
					'enable_sorting' => array(
						'label' => __( 'Enable custom sorting', 'ultimate-member' ),
						'value' => get_post_meta( $directory_id, '_um_enable_sorting', true ) ? $labels['yes'] : $labels['no'],
					),
				);

				if ( 1 === absint( get_post_meta( $directory_id, '_um_enable_sorting', true ) ) ) {
					$sorting_fields = get_post_meta( $directory_id, '_um_sorting_fields', true );
					if ( ! empty( $sorting_fields ) ) {
						foreach ( $sorting_fields as $k => $field ) {
							if ( is_array( $field ) ) {
								$debug_info[] = array(
									'sorting_fields-' . $k => array(
										'label' => __( 'Field(s) to enable in sorting', 'ultimate-member' ),
										'value' => $field,
									),
								);
							} else {
								$sortby_label = isset( $options[ $field ] ) ? $options[ $field ] : $field;
								$debug_info[] = array(
									'sorting_fields-' . $k => array(
										'label' => __( 'Field(s) to enable in sorting', 'ultimate-member' ),
										'value' => $sortby_label,
									),
								);
							}
						}
					}
				}

				$debug_info[] = array(
					'search' => array(
						'label' => __( 'Enable Search feature', 'ultimate-member' ),
						'value' => get_post_meta( $directory_id, '_um_search', true ) ? $labels['yes'] : $labels['no'],
					),
				);

				if ( 1 === absint( get_post_meta( $directory_id, '_um_search', true ) ) ) {
					$directory_roles_can_search_meta = get_post_meta( $directory_id, '_um_roles_can_search', true );
					$roles_can_search                = array();
					if ( ! empty( $directory_roles_can_search_meta ) ) {
						if ( is_string( $directory_roles_can_search_meta ) ) {
							$roles_can_search = array( $directory_roles_can_search_meta );
						} else {
							$roles_can_search = $directory_roles_can_search_meta;
						}
					}

					$debug_info[] = array(
						'roles_can_search' => array(
							'label' => __( 'User Roles that can use search', 'ultimate-member' ),
							'value' => ! empty( $roles_can_search ) ? implode( ', ', $roles_can_search ) : $labels['all'],
						),
					);

					if ( ! empty( get_post_meta( $directory_id, '_um_search_exclude_fields', true ) ) ) {
						$debug_info[] = array(
							'search_exclude_fields' => array(
								'label' => __( 'Exclude fields from search', 'ultimate-member' ),
								'value' => get_post_meta( $directory_id, '_um_search_exclude_fields', true ),
							),
						);
					}

					if ( ! empty( get_post_meta( $directory_id, '_um_search_include_fields', true ) ) ) {
						$debug_info[] = array(
							'search_include_fields' => array(
								'label' => __( 'Fields to search by', 'ultimate-member' ),
								'value' => get_post_meta( $directory_id, '_um_search_include_fields', true ),
							),
						);
					}
				}

				$debug_info[] = array(
					'filters' => array(
						'label' => __( 'Enable Filters feature', 'ultimate-member' ),
						'value' => get_post_meta( $directory_id, '_um_filters', true ) ? $labels['yes'] : $labels['no'],
					),
				);

				if ( 1 === absint( get_post_meta( $directory_id, '_um_filters', true ) ) ) {
					$directory_roles_can_filter_meta = get_post_meta( $directory_id, '_um_roles_can_filter', true );
					$roles_can_filter                = array();
					if ( ! empty( $directory_roles_can_filter_meta ) ) {
						if ( is_string( $directory_roles_can_filter_meta ) ) {
							$roles_can_filter = array( $directory_roles_can_filter_meta );
						} else {
							$roles_can_filter = $directory_roles_can_filter_meta;
						}
					}

					$debug_info[] = array(
						'roles_can_filter' => array(
							'label' => __( 'User Roles that can use filters', 'ultimate-member' ),
							'value' => ! empty( $roles_can_filter ) ? implode( ', ', $roles_can_filter ) : $labels['all'],
						),
					);

					$search_fields = get_post_meta( $directory_id, '_um_search_fields', true );
					if ( ! empty( $search_fields ) && is_array( $search_fields ) ) {
						$field_labels = array();
						foreach ( $search_fields as $field ) {
							$field_label    = isset( $options[ $field ] ) ? $options[ $field ] : $field;
							$field_labels[] = $field_label;
						}
						$debug_info[] = array(
							'search_fields-' . $directory_id => array(
								'label' => __( 'Filter meta to enable', 'ultimate-member' ),
								'value' => $field_labels,
							),
						);
					}

					$debug_info[] = array(
						'filters_expanded' => array(
							'label' => __( 'Expand the filter bar by default', 'ultimate-member' ),
							'value' => get_post_meta( $directory_id, '_um_filters_expanded', true ) ? $labels['yes'] : $labels['no'],
						),
					);

					if ( 1 === absint( get_post_meta( $directory_id, '_um_filters_expanded', true ) ) ) {
						$debug_info[] = array(
							'filters_is_collapsible' => array(
								'label' => __( 'Can filter bar be collapsed', 'ultimate-member' ),
								'value' => get_post_meta( $directory_id, '_um_filters_is_collapsible', true ) ? $labels['yes'] : $labels['no'],
							),
						);
					}
				}

				$search_filters = get_post_meta( $directory_id, '_um_search_filters', true );
				if ( ! empty( $search_filters ) && is_array( $search_filters ) ) {
					$field_labels = array();
					foreach ( $search_filters as $k => $field ) {
						$field_label = isset( $options[ $k ] ) ? $options[ $k ] : $k;
						$value       = $field;
						if ( is_array( $field ) ) {
							if ( array_key_exists( $k, UM()->member_directory()->filter_types ) && in_array( UM()->member_directory()->filter_types[ $k ], array( 'datepicker', 'timepicker', 'slider' ), true ) ) {
								// translators: %1$s is the "From" value, %2$s is the "To" value.
								$value = sprintf( __( 'From %1$s; To %2$s', 'ultimate-member' ), $field[0], $field[1] );
							} else {
								$value = implode( ', ', $field );
							}
						}
						$field_labels[] = $field_label . ' - ' . $value;
					}
					$debug_info[] = array(
						'search_filters-' . $directory_id => array(
							'label' => __( 'Admin filtering', 'ultimate-member' ),
							'value' => $field_labels,
						),
					);
				}

				$debug_info[] = array(
					'show_profile_photo' => array(
						'label' => __( 'Enable Profile Photo', 'ultimate-member' ),
						'value' => get_post_meta( $directory_id, '_um_profile_photo', true ) ? $labels['yes'] : $labels['no'],
					),
					'show_cover_photos'  => array(
						'label' => __( 'Enable Cover Photo', 'ultimate-member' ),
						'value' => get_post_meta( $directory_id, '_um_cover_photos', true ) ? $labels['yes'] : $labels['no'],
					),
					'show_display_name'  => array(
						'label' => __( 'Show display name', 'ultimate-member' ),
						'value' => get_post_meta( $directory_id, '_um_show_name', true ) ? $labels['yes'] : $labels['no'],
					),
				);

				/**
				 * Filters member directory profile card settings for Site Health.
				 *
				 * @since 2.10.5
				 * @hook um_debug_member_directory_profile_extend
				 *
				 * @param {array}  $info Member directory settings for Site Health.
				 * @param {string} $directory_id  Member directory ID.
				 *
				 * @return {array} Member directory settings for Site Health.
				 *
				 * @example <caption>Extend Member directory settings for Site Health.</caption>
				 * function um_debug_member_directory_profile_extend( $info, $directory_id ) {
				 *     // your code here
				 *     return $info;
				 * }
				 * add_filter( 'um_debug_member_directory_profile_extend', 'um_debug_member_directory_profile_extend', 10, 2 );
				 */
				$debug_info = apply_filters( 'um_debug_member_directory_profile_extend', $debug_info, $directory_id );

				$debug_info[] = array(
					'show_tagline' => array(
						'label' => __( 'Show tagline below profile name', 'ultimate-member' ),
						'value' => get_post_meta( $directory_id, '_um_show_tagline', true ) ? $labels['yes'] : $labels['no'],
					),
				);

				if ( 1 === absint( get_post_meta( $directory_id, '_um_show_tagline', true ) ) ) {
					$tagline_fields = get_post_meta( $directory_id, '_um_tagline_fields', true );
					if ( ! empty( $tagline_fields ) && is_array( $tagline_fields ) ) {
						$field_labels = array();
						foreach ( $tagline_fields as $field ) {
							$label          = isset( $options[ $field ] ) ? $options[ $field ] : $field;
							$field_labels[] = $label;
						}

						$debug_info[] = array(
							'tagline_fields-' . $directory_id => array(
								'label' => __( 'Field to display in tagline', 'ultimate-member' ),
								'value' => $field_labels,
							),
						);
					}
				}

				$debug_info[] = array(
					'show_userinfo' => array(
						'label' => __( 'Show extra user information below tagline?', 'ultimate-member' ),
						'value' => get_post_meta( $directory_id, '_um_show_userinfo', true ) ? $labels['yes'] : $labels['no'],
					),
				);

				if ( 1 === absint( get_post_meta( $directory_id, '_um_show_userinfo', true ) ) ) {
					$reveal_fields = get_post_meta( $directory_id, '_um_reveal_fields', true );
					if ( ! empty( $reveal_fields ) && is_array( $reveal_fields ) ) {
						$field_labels = array();
						foreach ( $reveal_fields as $field ) {
							$label          = isset( $options[ $field ] ) ? $options[ $field ] : $field;
							$field_labels[] = $label;
						}

						$debug_info[] = array(
							'reveal_fields-' . $directory_id => array(
								'label' => __( 'Field to display in extra user information section', 'ultimate-member' ),
								'value' => $field_labels,
							),
						);
					}
				}

				$debug_info[] = array(
					'show_social'              => array(
						'label' => __( 'Show social connect icons in extra user information section', 'ultimate-member' ),
						'value' => get_post_meta( $directory_id, '_um_show_social', true ) ? $labels['yes'] : $labels['no'],
					),
					'userinfo_animate'         => array(
						'label' => __( 'Hide extra user information to the reveal section', 'ultimate-member' ),
						'value' => get_post_meta( $directory_id, '_um_userinfo_animate', true ) ? $labels['yes'] : $labels['no'],
					),
					'must_search'              => array(
						'label' => __( 'Show results only after search/filtration', 'ultimate-member' ),
						'value' => get_post_meta( $directory_id, '_um_must_search', true ) ? $labels['yes'] : $labels['no'],
					),
					'max_users'                => array(
						'label' => __( 'Maximum number of profiles', 'ultimate-member' ),
						'value' => get_post_meta( $directory_id, '_um_max_users', true ),
					),
					'profiles_per_page'        => array(
						'label' => __( 'Number of profiles per page', 'ultimate-member' ),
						'value' => get_post_meta( $directory_id, '_um_profiles_per_page', true ),
					),
					'profiles_per_page_mobile' => array(
						'label' => __( 'Maximum number of profiles', 'ultimate-member' ),
						'value' => get_post_meta( $directory_id, '_um_profiles_per_page_mobile', true ),
					),
					'header'                   => array(
						'label' => __( 'Results Text', 'ultimate-member' ),
						'value' => get_post_meta( $directory_id, '_um_directory_header', true ),
					),
					'header_single'            => array(
						'label' => __( 'Single Result Text', 'ultimate-member' ),
						'value' => get_post_meta( $directory_id, '_um_directory_header_single', true ),
					),
					'no_users_text'            => array(
						'label' => __( 'Custom text if no users were found', 'ultimate-member' ),
						'value' => get_post_meta( $directory_id, '_um_directory_no_users', true ),
					),
				);

				/**
				 * Filters member directory settings for Site Health.
				 *
				 * @since 2.10.5
				 * @hook um_debug_member_directory_extend
				 *
				 * @param {array}  $info Member directory settings for Site Health.
				 * @param {string} $directory_id  Member directory ID.
				 *
				 * @return {array} Member directory settings for Site Health.
				 *
				 * @example <caption>Extend Member directory settings for Site Health.</caption>
				 * function um_debug_member_directory_extend( $info, $directory_id ) {
				 *     // your code here
				 *     return $info;
				 * }
				 * add_filter( 'um_debug_member_directory_extend', 'um_debug_member_directory_extend', 10, 2 );
				 */
				$debug_info = apply_filters( 'um_debug_member_directory_extend', $debug_info, $directory_id );

				$debug_info = array_merge( ...$debug_info );

				$info[ 'ultimate-member-directory-' . $directory_id ] = array(
					// translators: %s is the member directory title.
					'label'       => sprintf( __( ' - Member directory "%s" settings', 'ultimate-member' ), get_the_title( $directory_id ) ),
					'description' => __( 'This debug information for your Ultimate Member directory.', 'ultimate-member' ),
					'fields'      => $debug_info,
				);
			}
		}

		return $info;
	}
}
