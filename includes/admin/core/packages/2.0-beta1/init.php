<?php ?>

<script type="text/javascript">
	jQuery( document ).ready( function() {
		var um_roles_data;
		var users_per_page = 100;
		var users_pages;
		var forums_pages;
		var products_pages;
		var current_page = 1;

		//upgrade styles
		um_add_upgrade_log( '<?php echo esc_js( __( 'Upgrade Styles...', 'ultimate-member' ) ) ?>' );

		jQuery.ajax({
			url: wp.ajax.settings.url,
			type: 'POST',
			dataType: 'json',
			data: {
				action: 'um_styles20beta1',
				nonce: um_admin_scripts.nonce
			},
			success: function( response ) {
				if ( typeof response.data != 'undefined' ) {
					um_add_upgrade_log( response.data.message );

					setTimeout( function () {
						upgrade_roles();
					}, um_request_throttle );
				} else {
					um_wrong_ajax();
				}
			},
			error: function() {
				um_something_wrong();
			}
		});


		function upgrade_roles() {
			um_add_upgrade_log( '<?php echo esc_js( __( 'Upgrade Roles...', 'ultimate-member' ) ) ?>' );
			jQuery.ajax({
				url: wp.ajax.settings.url,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'um_user_roles20beta1',
					nonce: um_admin_scripts.nonce
				},
				success: function( response ) {
					if ( typeof response.data != 'undefined' ) {
						um_add_upgrade_log( response.data.message );
						um_roles_data = response.data.roles;

						um_add_upgrade_log( '<?php echo esc_js( __( 'Upgrade Users...', 'ultimate-member' ) ) ?>' );

						setTimeout( function () {
							get_users_per_role();
						}, um_request_throttle );
					} else {
						um_wrong_ajax();
					}
				},
				error: function() {
					um_something_wrong();
				}
			});
		}


		/**
		 *
		 * @returns {boolean}
		 */
		function get_users_per_role() {
			current_page = 1;
			if ( um_roles_data.length ) {
				var role = um_roles_data.shift();
				um_add_upgrade_log( '<?php echo esc_js( __( 'Getting ', 'ultimate-member' ) ) ?>"'  + role.role_key + '"<?php echo esc_js( __( ' users...', 'ultimate-member' ) ) ?>' );
				jQuery.ajax({
					url: wp.ajax.settings.url,
					type: 'POST',
					dataType: 'json',
					data: {
						action: 'um_get_users_per_role20beta1',
						key_in_meta: role.key_in_meta,
						nonce: um_admin_scripts.nonce
					},
					success: function( response ) {
						if ( typeof response.data.count != 'undefined' ) {
							um_add_upgrade_log( '<?php echo esc_js( __( 'There are ', 'ultimate-member' ) ) ?>'  + response.data.count + '<?php echo esc_js( __( ' users...', 'ultimate-member' ) ) ?>' );
							um_add_upgrade_log( '<?php echo esc_js( __( 'Start users upgrading...', 'ultimate-member' ) ) ?>');
							users_pages = Math.ceil( response.data.count / users_per_page );

							setTimeout( function () {
								update_user_per_page( role.role_key, role.key_in_meta );
							}, um_request_throttle );
						} else {
							um_wrong_ajax();
						}
					},
					error: function() {
						um_something_wrong();
					}
				});
			} else {
				setTimeout( function () {
					upgrade_content_restriction();
				}, um_request_throttle );
			}

			return false;
		}


		function update_user_per_page( role_key, key_in_meta ) {
			if ( current_page <= users_pages ) {
				jQuery.ajax({
					url: wp.ajax.settings.url,
					type: 'POST',
					dataType: 'json',
					data: {
						action: 'um_update_users_per_page20beta1',
						role_key: role_key,
						key_in_meta: key_in_meta,
						page: current_page,
						nonce: um_admin_scripts.nonce
					},
					success: function( response ) {
						if ( typeof response.data != 'undefined' ) {
							um_add_upgrade_log( response.data.message );
							current_page++;
							setTimeout( function () {
								update_user_per_page( role_key, key_in_meta );
							}, um_request_throttle );
						} else {
							um_wrong_ajax();
						}
					},
					error: function() {
						um_something_wrong();
					}
				});
			} else {
				setTimeout( function () {
					get_users_per_role();
				}, um_request_throttle );
			}
		}


		function upgrade_content_restriction() {
			um_add_upgrade_log( '<?php echo esc_js( __( 'Upgrade Content Restriction Settings...', 'ultimate-member' ) ) ?>' );

			jQuery.ajax({
				url: wp.ajax.settings.url,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'um_content_restriction20beta1',
					nonce: um_admin_scripts.nonce
				},
				success: function( response ) {
					if ( typeof response.data != 'undefined' ) {
						um_add_upgrade_log( response.data.message );
						setTimeout( function () {
							upgrade_settings();
						}, um_request_throttle );
					} else {
						um_wrong_ajax();
					}
				},
				error: function() {
					um_something_wrong();
				}
			});
		}


		function upgrade_settings() {
			um_add_upgrade_log( '<?php echo esc_js( __( 'Upgrade Settings...', 'ultimate-member' ) ) ?>' );

			jQuery.ajax({
				url: wp.ajax.settings.url,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'um_settings20beta1',
					nonce: um_admin_scripts.nonce
				},
				success: function( response ) {
					if ( typeof response.data != 'undefined' ) {
						um_add_upgrade_log( response.data.message );
						setTimeout( function () {
							upgrade_menus();
						}, um_request_throttle );
					} else {
						um_wrong_ajax();
					}
				},
				error: function() {
					um_something_wrong();
				}
			});
		}


		function upgrade_menus() {
			um_add_upgrade_log( '<?php echo esc_js( __( 'Upgrade Menu Items...', 'ultimate-member' ) ) ?>' );

			jQuery.ajax({
				url: wp.ajax.settings.url,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'um_menus20beta1',
					nonce: um_admin_scripts.nonce
				},
				success: function( response ) {
					if ( typeof response.data != 'undefined' ) {
						um_add_upgrade_log( response.data.message );
						setTimeout( function () {
							upgrade_mc_lists();
						}, um_request_throttle );
					} else {
						um_wrong_ajax();
					}
				},
				error: function() {
					um_something_wrong();
				}
			});
		}


		function upgrade_mc_lists() {
			um_add_upgrade_log( '<?php echo esc_js( __( 'Upgrade Mailchimp Lists...', 'ultimate-member' ) ) ?>' );

			jQuery.ajax({
				url: wp.ajax.settings.url,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'um_mc_lists20beta1',
					nonce: um_admin_scripts.nonce
				},
				success: function( response ) {
					if ( typeof response.data != 'undefined' ) {
						um_add_upgrade_log( response.data.message );
						setTimeout( function () {
							upgrade_social_login();
						}, um_request_throttle );
					} else {
						um_wrong_ajax();
					}
				},
				error: function() {
					um_something_wrong();
				}
			});
		}


		function upgrade_social_login() {
			um_add_upgrade_log( '<?php echo esc_js( __( 'Upgrade Social Login Forms...', 'ultimate-member' ) ) ?>' );

			jQuery.ajax({
				url: wp.ajax.settings.url,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'um_social_login20beta1',
					nonce: um_admin_scripts.nonce
				},
				success: function( response ) {
					if ( typeof response.data != 'undefined' ) {
						um_add_upgrade_log( response.data.message );
						setTimeout( function () {
							upgrade_cpt();
						}, um_request_throttle );
					} else {
						um_wrong_ajax();
					}
				},
				error: function() {
					um_something_wrong();
				}
			});
		}


		function upgrade_cpt() {
			um_add_upgrade_log( '<?php echo esc_js( __( 'Upgrade UM Custom Post Types...', 'ultimate-member' ) ) ?>' );

			jQuery.ajax({
				url: wp.ajax.settings.url,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'um_cpt20beta1',
					nonce: um_admin_scripts.nonce
				},
				success: function( response ) {
					if ( typeof response.data != 'undefined' ) {
						um_add_upgrade_log( response.data.message );
						setTimeout( function () {
							get_forums();
						}, um_request_throttle );
					} else {
						um_wrong_ajax();
					}
				},
				error: function() {
					um_something_wrong();
				}
			});
		}


		function get_forums() {
			um_add_upgrade_log( '<?php echo esc_js( __( 'Upgrade bbPress Forums...', 'ultimate-member' ) ) ?>' );
			um_add_upgrade_log( '<?php echo esc_js( __( 'Get bbPress Forums count...', 'ultimate-member' ) ) ?>' );
			current_page = 1;
			jQuery.ajax({
				url: wp.ajax.settings.url,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'um_get_forums20beta1',
					nonce: um_admin_scripts.nonce
				},
				success: function( response ) {
					if ( typeof response.data != 'undefined' ) {
						um_add_upgrade_log( response.data.message );

						forums_pages = Math.ceil( response.data.count / users_per_page );

						setTimeout( function () {
							update_forums_per_page();
						}, um_request_throttle );
					} else {
						um_wrong_ajax();
					}
				},
				error: function() {
					um_something_wrong();
				}
			});
		}


		function update_forums_per_page() {
			if ( current_page <= forums_pages ) {
				jQuery.ajax({
					url: wp.ajax.settings.url,
					type: 'POST',
					dataType: 'json',
					data: {
						action: 'um_update_forum_per_page20beta1',
						page: current_page,
						nonce: um_admin_scripts.nonce
					},
					success: function( response ) {
						if ( typeof response.data != 'undefined' ) {
							um_add_upgrade_log( response.data.message );
							current_page++;
							setTimeout( function () {
								update_forums_per_page();
							}, um_request_throttle );
						} else {
							um_wrong_ajax();
						}
					},
					error: function() {
						um_something_wrong();
					}
				});
			} else {
				setTimeout( function () {
					get_products();
				}, um_request_throttle );
			}
		}


		function get_products() {
			um_add_upgrade_log( '<?php echo esc_js( __( 'Upgrade Woocommerce Products...', 'ultimate-member' ) ) ?>' );
			um_add_upgrade_log( '<?php echo esc_js( __( 'Get all Products...', 'ultimate-member' ) ) ?>' );

			current_page = 1;

			jQuery.ajax({
				url: wp.ajax.settings.url,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'um_get_products20beta1',
					nonce: um_admin_scripts.nonce
				},
				success: function( response ) {
					if ( typeof response.data != 'undefined' ) {
						um_add_upgrade_log( response.data.message );

						products_pages = Math.ceil( response.data.count / users_per_page );
						setTimeout( function () {
							update_products_per_page();
						}, um_request_throttle );
					} else {
						um_wrong_ajax();
					}
				},
				error: function() {
					um_something_wrong();
				}
			});
		}


		function update_products_per_page() {
			if ( current_page <= products_pages ) {
				jQuery.ajax({
					url: wp.ajax.settings.url,
					type: 'POST',
					dataType: 'json',
					data: {
						action: 'um_update_products_per_page20beta1',
						page: current_page,
						nonce: um_admin_scripts.nonce
					},
					success: function( response ) {
						if ( typeof response.data != 'undefined' ) {
							um_add_upgrade_log( response.data.message );
							current_page++;
							setTimeout( function () {
								update_products_per_page();
							}, um_request_throttle );
						} else {
							um_wrong_ajax();
						}
					},
					error: function() {
						um_something_wrong();
					}
				});
			} else {
				setTimeout( function () {
					upgrade_email_templates();
				}, um_request_throttle );
			}
		}


		function upgrade_email_templates() {
			um_add_upgrade_log( '<?php echo esc_js( __( 'Upgrade Email Templates...', 'ultimate-member' ) ) ?>' );
			jQuery.ajax({
				url: wp.ajax.settings.url,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'um_email_templates20beta1',
					nonce: um_admin_scripts.nonce
				},
				success: function( response ) {
					if ( typeof response.data != 'undefined' ) {
						um_add_upgrade_log( response.data.message );
						//switch to the next package
						um_run_upgrade();
					} else {
						um_wrong_ajax();
					}
				},
				error: function() {
					um_something_wrong();
				}
			});
		}
	});
</script>