<?php
namespace umm\member_directory\includes\admin;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


if ( ! class_exists( 'umm\member_directory\includes\admin\Site_Health' ) ) {


	/**
	 * Class Site_Health
	 *
	 * @package um\admin
	 */
	class Site_Health {


		/**
		 * Site_Health constructor.
		 */
		public function __construct() {
			add_filter( 'debug_information', array( $this, 'debug_information' ) );
			add_filter( 'um_debug_information_pages', array( $this, 'um_debug_information_pages' ), 10, 1 );
		}


		private function get_member_directories() {
			$query = new \WP_Query;
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
		 * @since 3.0
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
				'no-dir'  => __( 'No directories', 'ultimate-member' ),
			);

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
				'secondary_user_email' => __( 'Secondary E-mail Address', 'ultimate-member' ),
				'description'          => __( 'Biography', 'ultimate-member' ),
				'phone_number'         => __( 'Phone Number', 'ultimate-member' ),
				'mobile_number'        => __( 'Mobile Number', 'ultimate-member' ),
				'role_select'          => __( 'Roles (Dropdown)', 'ultimate-member' ),
				'role_radio'           => __( 'Roles (Radio)', 'ultimate-member' ),
				'whatsapp'             => __( 'WhatsApp number', 'ultimate-member' ),
				'facebook'             => __( 'Facebook', 'ultimate-member' ),
				'twitter'              => __( 'Twitter', 'ultimate-member' ),
				'viber'                => __( 'Viber number', 'ultimate-member' ),
				'skype'                => __( 'Skype ID', 'ultimate-member' ),
				'telegram'             => __( 'Telegram', 'ultimate-member' ),
				'discord'              => __( 'Discord', 'ultimate-member' ),
				'youtube'              => __( 'Youtube', 'ultimate-member' ),
				'soundcloud'           => __( 'SoundCloud', 'ultimate-member' ),
				'vkontakte'            => __( 'Vkontakte', 'ultimate-member' ),
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
						'value' => ! empty ( $this->get_member_directories() ) ? $this->get_member_directories() : $labels['no-dir'],
					),
				),
			);

			if ( ! empty( $this->get_member_directories() ) ) {
				foreach ( $this->get_member_directories() as $key => $directory ) {
					if ( strpos($key, 'ID#') === 0 ) {
						$key = substr($key, 3);
					}

					$info['ultimate-member-directory-' . $key ] = array(
						'label'       => ' - ' . $directory . __( ' directory settings', 'ultimate-member' ),
						'description' => __( 'This debug information for your Ultimate Member directory.', 'ultimate-member' ),
						'fields'      => array(
							'um-directory-shortcode'  => array(
								'label' => __( 'Shortcode', 'ultimate-member' ),
								'value' => '[ultimatemember form_id="' . $key . '"]',
							),
							'um-directory_template'   => array(
								'label' => __( 'Template', 'ultimate-member' ),
								'value' => get_post_meta( $key, '_um_directory_template', true ),
							),
							'um-directory-view_types' => array(
								'label' => __( 'View type(s)', 'ultimate-member' ),
								'value' => implode(', ', get_post_meta( $key, '_um_view_types', true ) ),
							),
						),
					);

					if ( ! empty( get_post_meta( $key, '_um_view_types', true ) ) ) {
						$info['ultimate-member-directory-' . $key ]['fields'] = array_merge(
							$info['ultimate-member-directory-' . $key ]['fields'],
							array(
								'um-directory-default_view' => array(
									'label' => __( 'Default view type', 'ultimate-member' ),
									'value' => get_post_meta( $key, '_um_default_view', true ),
								),
							)
						);
					}

					if ( isset( $options[ get_post_meta( $key, '_um_sortby', true ) ] ) ) {
						$sortby_label = $options[ get_post_meta( $key, '_um_sortby', true ) ];
					} else {
						$sortby_label = get_post_meta( $key, '_um_sortby', true );
					}

					$info['ultimate-member-directory-' . $key ]['fields'] = array_merge(
						$info['ultimate-member-directory-' . $key ]['fields'],
						array(
							'um-directory-roles'               => array(
								'label' => __( 'Default view type', 'ultimate-member' ),
								'value' => ! empty( get_post_meta( $key, '_um_roles', true ) ) ? implode(', ', get_post_meta( $key, '_um_roles', true ) ) : $labels['all'],
							),
							'um-directory-has_profile_photo'   => array(
								'label' => __( 'Only show members who have uploaded a profile photo', 'ultimate-member' ),
								'value' => get_post_meta( $key,'_um_has_profile_photo', true ) ? $labels['yes'] : $labels['no'],
							),
							'um-directory-has_cover_photo'     => array(
								'label' => __( 'Only show members who have uploaded a profile photo', 'ultimate-member' ),
								'value' => get_post_meta( $key,'_um_has_cover_photo', true ) ? $labels['yes'] : $labels['no'],
							),
							'um-directory-show_these_users'    => array(
								'label' => __( 'Only show specific users (Enter one username per line)', 'ultimate-member' ),
								'value' => ! empty( get_post_meta( $key, '_um_show_these_users', true ) ) ? implode(', ', get_post_meta( $key, '_um_show_these_users', true ) ) : '',
							),
							'um-directory-exclude_these_users' => array(
								'label' => __( 'Exclude specific users (Enter one username per line)', 'ultimate-member' ),
								'value' => ! empty( get_post_meta( $key, '_um_exclude_these_users', true ) ) ? implode(', ', get_post_meta( $key, '_um_exclude_these_users', true ) ) : '',
							),
							'um-directory-sortby'              => array(
								'label' => __( 'Default sort users by', 'ultimate-member' ),
								'value' => $sortby_label,
							),
						)
					);

					if ( 'other' == get_post_meta( $key, '_um_sortby', true ) ) {
						$info['ultimate-member-directory-' . $key ]['fields'] = array_merge(
							$info['ultimate-member-directory-' . $key ]['fields'],
							array(
								'um-directory-sortby_custom'       => array(
									'label' => __( 'Meta key', 'ultimate-member' ),
									'value' => get_post_meta( $key, '_um_sortby_custom', true ),
								),
								'um-directory-sortby_custom_label' => array(
									'label' => __( 'Label of custom sort', 'ultimate-member' ),
									'value' => get_post_meta( $key, '_um_sortby_custom_label', true ),
								),
								'um-directory-enable_sorting'      => array(
									'label' => __( 'Enable custom sorting', 'ultimate-member' ),
									'value' => get_post_meta( $key, '_um_enable_sorting', true ) ? $labels['yes'] : $labels['no'],
								),
							)
						);
					}

					if ( 1 == get_post_meta( $key, '_um_enable_sorting', true ) ) {
						$sorting_fields = get_post_meta( $key, '_um_sorting_fields', true );
						if ( ! empty( $sorting_fields) ) {
							foreach ( $sorting_fields as $k => $field ) {
								if ( is_array( $field ) ) {
									$info['ultimate-member-directory-' . $key ]['fields'] = array_merge(
										$info['ultimate-member-directory-' . $key ]['fields'],
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
									$info['ultimate-member-directory-' . $key ]['fields'] = array_merge(
										$info['ultimate-member-directory-' . $key ]['fields'],
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

					$info['ultimate-member-directory-' . $key ]['fields'] = array_merge(
						$info['ultimate-member-directory-' . $key ]['fields'],
						array(
							'um-directory-profile_photo' => array(
								'label' => __( 'Enable Profile Photo', 'ultimate-member' ),
								'value' => get_post_meta( $key,'_um_profile_photo', true ) ? $labels['yes'] : $labels['no'],
							),
							'um-directory-cover_photos'  => array(
								'label' => __( 'Enable Cover Photo', 'ultimate-member' ),
								'value' => get_post_meta( $key,'_um_cover_photos', true ) ? $labels['yes'] : $labels['no'],
							),
							'um-directory-show_name'     => array(
								'label' => __( 'Show display name', 'ultimate-member' ),
								'value' => get_post_meta( $key,'_um_show_name', true ) ? $labels['yes'] : $labels['no'],
							),
							'um-directory-show_tagline'     => array(
								'label' => __( 'Show tagline below profile name', 'ultimate-member' ),
								'value' => get_post_meta( $key,'_um_show_tagline', true ) ? $labels['yes'] : $labels['no'],
							),
						)
					);

					if ( 1 == get_post_meta( $key, '_um_show_tagline', true ) ) {
						$tagline_fields = get_post_meta( $key, '_um_tagline_fields', true );
						if ( ! empty( $tagline_fields) ) {
							foreach ( $tagline_fields as $k => $field ) {
								$label = isset( $options[ $field ] ) ? $options[ $field ] : $field;
								$info['ultimate-member-directory-' . $key ]['fields'] = array_merge(
									$info['ultimate-member-directory-' . $key ]['fields'],
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

					$info['ultimate-member-directory-' . $key ]['fields'] = array_merge(
						$info['ultimate-member-directory-' . $key ]['fields'],
						array(
							'um-directory-show_userinfo' => array(
								'label' => __( 'Show extra user information below tagline?', 'ultimate-member' ),
								'value' => get_post_meta( $key,'_um_show_userinfo', true ) ? $labels['yes'] : $labels['no'],
							),
						)
					);

					if ( 1 == get_post_meta( $key, '_um_show_userinfo', true ) ) {
						$reveal_fields = get_post_meta( $key, '_um_reveal_fields', true );
						if ( ! empty( $reveal_fields) ) {
							foreach ( $reveal_fields as $k => $field ) {
								$label = isset( $options[ $field ] ) ? $options[ $field ] : $field;
								$info['ultimate-member-directory-' . $key ]['fields'] = array_merge(
									$info['ultimate-member-directory-' . $key ]['fields'],
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

					$info['ultimate-member-directory-' . $key ]['fields'] = array_merge(
						$info['ultimate-member-directory-' . $key ]['fields'],
						array(
							'um-directory-show_social' => array(
								'label' => __( 'Show social connect icons in extra user information section', 'ultimate-member' ),
								'value' => get_post_meta( $key,'_um_show_social', true ) ? $labels['yes'] : $labels['no'],
							),
							'um-directory-userinfo_animate' => array(
								'label' => __( 'Hide extra user information to the reveal section', 'ultimate-member' ),
								'value' => get_post_meta( $key,'_um_userinfo_animate', true ) ? $labels['yes'] : $labels['no'],
							),
							'um-directory-search' => array(
								'label' => __( 'Enable Search feature', 'ultimate-member' ),
								'value' => get_post_meta( $key,'_um_search', true ) ? $labels['yes'] : $labels['no'],
							),
						)
					);

					if ( 1 == get_post_meta( $key, '_um_search', true ) ) {
						$info['ultimate-member-directory-' . $key ]['fields'] = array_merge(
							$info['ultimate-member-directory-' . $key ]['fields'],
							array(
								'um-directory-roles_can_search' => array(
									'label' => __( 'User Roles that can use search', 'ultimate-member' ),
									'value' => ! empty( get_post_meta( $key, '_um_roles_can_search', true ) ) ? implode(', ', get_post_meta( $key, '_um_roles_can_search', true ) ) : $labels['all'],
								),
							)
						);
					}

					$info['ultimate-member-directory-' . $key ]['fields'] = array_merge(
						$info['ultimate-member-directory-' . $key ]['fields'],
						array(
							'um-directory-filters' => array(
								'label' => __( 'Enable Filters feature', 'ultimate-member' ),
								'value' => get_post_meta( $key,'_um_filters', true ) ? $labels['yes'] : $labels['no'],
							),
						)
					);

					if ( 1 == get_post_meta( $key, '_um_filters', true ) ) {
						$info['ultimate-member-directory-' . $key ]['fields'] = array_merge(
							$info['ultimate-member-directory-' . $key ]['fields'],
							array(
								'um-directory-roles_can_filter' => array(
									'label' => __( 'User Roles that can use filters', 'ultimate-member' ),
									'value' => ! empty( get_post_meta( $key, '_um_roles_can_filter', true ) ) ? implode(', ', get_post_meta( $key, '_um_roles_can_filter', true ) ) : $labels['all'],
								),
							)
						);

						$search_fields = get_post_meta( $key, '_um_search_fields', true );
						if ( ! empty( $search_fields ) ) {
							foreach ( $search_fields as $k => $field ) {
								$label = isset( $options[ $field ] ) ? $options[ $field ] : $field;
								$info['ultimate-member-directory-' . $key ]['fields'] = array_merge(
									$info['ultimate-member-directory-' . $key ]['fields'],
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

					$info['ultimate-member-directory-' . $key ]['fields'] = array_merge(
						$info['ultimate-member-directory-' . $key ]['fields'],
						array(
							'um-directory-filters_expanded' => array(
								'label' => __( 'Expand the filter bar by default', 'ultimate-member' ),
								'value' => get_post_meta( $key,'_um_filters_expanded', true ) ? $labels['yes'] : $labels['no'],
							),
						)
					);

					if ( 1 == get_post_meta( $key, '_um_filters_expanded', true ) ) {
						$info['ultimate-member-directory-' . $key ]['fields'] = array_merge(
							$info['ultimate-member-directory-' . $key ]['fields'],
							array(
								'um-directory-filters_is_collapsible' => array(
									'label' => __( 'Can filter bar be collapsed', 'ultimate-member' ),
									'value' => get_post_meta( $key, '_um_filters_is_collapsible', true ) ? $labels['yes'] : $labels['no'],
								),
							)
						);
					}

					$search_filters = get_post_meta( $key, '_um_search_filters', true );
					if ( ! empty( $search_filters ) ) {
						foreach ( $search_filters as $k => $field ) {
							$label = isset( $options[ $k ] ) ? $options[ $k ] : $k;
							$value = $field;
							if ( is_array( $field ) ) {
								$value = __( 'From ', 'ultimate-member' ) . $field[0] . __( ' to ', 'ultimate-member' ) . $field[1];
							}
							$info['ultimate-member-directory-' . $key ]['fields'] = array_merge(
								$info['ultimate-member-directory-' . $key ]['fields'],
								array(
									'um-directory-search_filters-' . $k => array(
										'label' => __( 'Admin filtering', 'ultimate-member' ),
										'value' => $label . ' - ' . $value,
									),
								)
							);
						}
					}

					$info['ultimate-member-directory-' . $key ]['fields'] = array_merge(
						$info['ultimate-member-directory-' . $key ]['fields'],
						array(
							'um-directory-must_search' => array(
								'label' => __( 'Show results only after search/filtration', 'ultimate-member' ),
								'value' => get_post_meta( $key,'_um_must_search', true ) ? $labels['yes'] : $labels['no'],
							),
							'um-directory-max_users' => array(
								'label' => __( 'Maximum number of profiles', 'ultimate-member' ),
								'value' => get_post_meta( $key,'_um_max_users', true ),
							),
							'um-directory-profiles_per_page' => array(
								'label' => __( 'Number of profiles per page', 'ultimate-member' ),
								'value' => get_post_meta( $key,'_um_profiles_per_page', true ),
							),
							'um-directory-profiles_per_page_mobile' => array(
								'label' => __( 'Maximum number of profiles', 'ultimate-member' ),
								'value' => get_post_meta( $key,'_um_profiles_per_page_mobile', true ),
							),
							'um-directory-directory_header' => array(
								'label' => __( 'Results Text', 'ultimate-member' ),
								'value' => get_post_meta( $key,'_um_directory_header', true ),
							),
							'um-directory-directory_header_single' => array(
								'label' => __( 'Single Result Text', 'ultimate-member' ),
								'value' => get_post_meta( $key,'_um_directory_header_single', true ),
							),
							'um-directory-directory_no_users' => array(
								'label' => __( 'Custom text if no users were found', 'ultimate-member' ),
								'value' => get_post_meta( $key,'_um_directory_no_users', true ),
							),
						)
					);
				}
			}

			return $info;
		}


		/**
		 * Extend predefined pages.
		 *
		 * @since 3.0
		 *
		 * @param array $pages
		 *
		 * @return array
		 */
		public function um_debug_information_pages( $pages ) {
			$pages['Members page'] = get_the_title( UM()->options()->get('core_members') ) . ' (ID#' . UM()->options()->get('core_members') . ') | ' . get_permalink( UM()->options()->get('core_members') );

			return $pages;
		}
	}
}
