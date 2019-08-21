(function (blocks, editor, components, i18n, element) {
	var um_el               = wp.element.createElement,
		UmRegisterBlockType = wp.blocks.registerBlockType,
		UmRichText          = wp.editor.RichText,
		UmTextControl       = wp.components.TextControl,
		UmSelectControl     = wp.components.SelectControl,
		UmToggleControl     = wp.components.ToggleControl,
		UmPanelBody         = wp.components.PanelBody,
		UmRangeControl      = wp.components.RangeControl,
		UmSpinner           = wp.components.Spinner,
		UmData              = wp.data,
		UmWithSelect        = wp.data.withSelect,
		UmApiFetch          = wp.apiFetch;

	//-------------------------------------\\
	//--------- Um Forms shortcode --------\\
	//-------------------------------------\\

	UmRegisterBlockType('um-block/um-forms', {
		title: i18n.__( 'UM Form' , 'ultimate-member' ),
		description: i18n.__( 'Choose display form', 'ultimate-member' ),
		icon: 'forms',
		category: 'um-blocks',
		attributes: {
			content: {
				source: 'html',
				selector: 'p'
			},
			form_id: {
				type: 'select'
			}
		},

		edit: UmWithSelect(function (select) {
			return {
				posts: select('core').getEntityRecords( 'postType', 'um_form', {
					per_page: -1
				})
			};
		})(function ( props ) {
			var posts         = props.posts,
				className     = props.className,
				attributes    = props.attributes,
				setAttributes = props.setAttributes,
				form_id       = props.attributes.form_id,
				content       = props.attributes.content;

			function get_option( posts ) {

				var option = [];

				posts.map( function( post ) {
					option.push(
						{
							label: post.title.rendered,
							value: post.id
						}
					);
				});

				return option;
			}

			function umShortcode( value ) {

				var shortcode = '';

				if ( value !== undefined ) {
					shortcode = '[ultimatemember form_id="' + value + '"]';
				}

				return shortcode;
			}


			if ( ! posts ) {
				return um_el(
					'p',
					{
						className: className
					},
					um_el(
						UmSpinner,
						null
					),
					i18n.__( 'Loading Forms', 'ultimate-member' )
				);
			}

			if ( 0 === posts.length ) {
				return um_el(
					'p',
					null,
					i18n.__( 'No Posts', 'ultimate-member' )
				);
			}

			if ( form_id === undefined ) {
				props.setAttributes({ form_id: posts[0]['id'] });
				var shortcode = umShortcode(posts[0]['id']);
				props.setAttributes( { content: shortcode } );
			}

			var get_post = get_option( posts );

			return um_el(
				'div',
				{
					className: className
				},
				um_el(
					UmSelectControl,
					{
						label: i18n.__( 'Select Forms', 'ultimate-member' ),
						className: "um_select_users",
						type: 'number',
						value: form_id,
						options: get_post,
						onChange: function onChange( value ) {
							props.setAttributes({ form_id: value });
							var shortcode = umShortcode(value);
							props.setAttributes( { content: shortcode } );
						}
					}
				)
			);
		} // end withSelect
		), // end edit

		save: function save( props ) {

			return um_el(
				UmRichText.Content,
				{
					tagName: 'p',
					className: props.className,
					value: props.attributes.content
				}
			);
		}

	});

	//-------------------------------------\\
	//-- Um Member Directories shortcode --\\
	//-------------------------------------\\

	UmRegisterBlockType( 'um-block/um-member-directories', {
		title: i18n.__( 'UM Member Directories', 'ultimate-member'),
		description: i18n.__( 'Choose display form', 'ultimate-member' ),
		icon: 'groups',
		category: 'um-blocks',
		attributes: {
			content: {
				source: 'html',
				selector: 'p'
			},
			member_id: {
				type: 'select'
			}
		},

		edit: UmWithSelect( function( select ) {
			return {
				posts: select('core').getEntityRecords( 'postType', 'um_directory', {
					per_page: -1
				})
			};
		})(function ( props ) {
			var posts         = props.posts,
				className     = props.className,
				attributes    = props.attributes,
				setAttributes = props.setAttributes,
				member_id     = props.attributes.member_id,
				content       = props.attributes.content;

			function get_option( posts ) {
				var option = [];

				posts.map( function( post ) {
					option.push(
						{
							label: post.title.rendered,
							value: post.id
						}
					);
				});

				return option;
			}

			function umShortcode( value ) {

				var shortcode = '';

				if ( value !== undefined ) {
					shortcode = '[ultimatemember form_id="' + value + '"]';
				}

				return shortcode;
			}

			if ( ! posts ) {
				return um_el(
					'p',
					{
						className: className
					},
					um_el(
						UmSpinner,
						null
					),
					i18n.__( 'Loading Forms', 'ultimate-member' )
				);
			}

			if ( 0 === posts.length ) {
				return um_el(
					'p',
					null,
					i18n.__( 'No Posts', 'ultimate-member' )
				);
			}

			if ( member_id === undefined ) {
				props.setAttributes({ member_id: posts[0]['id'] });
				var shortcode = umShortcode(posts[0]['id']);
				props.setAttributes( { content: shortcode } );
			}

			var get_post = get_option( posts );

			return um_el(
				'div',
				{
					className: className
				},
				um_el(
					UmSelectControl,
					{
						label: i18n.__( 'Select Directories', 'ultimate-member' ),
						className: "um_select_member",
						type: 'number',
						value: member_id,
						options: get_post,
						onChange: function onChange( value ) {
							props.setAttributes({ member_id: value });
							var shortcode = umShortcode(value);
							props.setAttributes( { content: shortcode } );
						}
					}
				)
			);
		} // end withSelect
		), // end edit

		save: function save( props ) {

			return um_el(
				UmRichText.Content,
				{
					tagName: 'p',
					className: props.className,
					value: props.attributes.content
				}
			);
		}

	});

	//-------------------------------------\\
	//--------- Um password reset ---------\\
	//-------------------------------------\\
	UmRegisterBlockType('um-block/um-password-reset', {
		title: i18n.__( 'UM Password Reset', 'ultimate-member' ),
		description: i18n.__( 'Password Reset', 'ultimate-member' ),
		icon: 'unlock',
		category: 'um-blocks',
		attributes: {
			content: {
				source: 'html',
				selector: 'p'
			}
		},

		edit: function( props ) {
			var content = props.attributes.content;
			props.setAttributes({ content: '[ultimatemember_password]' });

			return [
				um_el(
					"div",
					{
						className: "um-password-reset-wrapper"
					},
					i18n.__( 'Password Reset', 'ultimate-member' )
				)
			]
		},

		save: function( props ) {

			return um_el(
				UmRichText.Content,
				{
					tagName: 'p',
					className: props.className,
					value: props.attributes.content
				}
			);
		}
	});

	//-------------------------------------\\
	//------------ Um Account -------------\\
	//-------------------------------------\\
	UmRegisterBlockType('um-block/um-account', {
		title: i18n.__( 'UM Account', 'ultimate-member' ),
		description: i18n.__( 'UM Account', 'ultimate-member' ),
		icon: 'id',
		category: 'um-blocks',
		attributes: {
			content: {
				source: 'html',
				selector: 'p'
			},
			tab: {
				type: 'select'
			}
		},

		edit: function( props ) {
			var content = props.attributes.content,
				tab     = props.attributes.tab,
				options = um_account_settings;

			function get_options() {
				var option = [];

				option.push( { label: i18n.__( 'All', 'ultimate-member' ), value: 'all' } );

				for ( var key in options ) {
					if ( options.hasOwnProperty( key ) && options[ key ]['enabled'] ) {
						option.push(
							{
								label: options[ key ]['label'],
								value: key
							}
						)
					}
				}

				return option;
			}

			function umShortcode( value ) {

				var shortcode = '[ultimatemember_account';

				if ( value != 'all' ) {
					shortcode = shortcode + ' tab="' + value + '"';
				}

				shortcode = shortcode + ']';

				props.setAttributes({ content: shortcode });
			}

			if ( content === undefined ) {
				props.setAttributes({ content: '[ultimatemember_account]' });
			}

			return [
				um_el(
					"div",
					{
						className: "um-account-wrapper"
					},
					i18n.__( 'UM Account', 'ultimate-member' )
				),
				um_el(
					wp.editor.InspectorControls,
					{},
					um_el(
						UmPanelBody,
						{
							title: i18n.__( 'Account Tab', 'ultimate-member' )
						},
						um_el(
							UmSelectControl,
							{
								label: i18n.__( 'Select Tab', 'ultimate-member' ),
								className: "um_select_account_tab",
								type: 'number',
								value: props.attributes.tab,
								options: get_options(),
								onChange: function onChange( value ) {
									props.setAttributes({ tab: value });
									umShortcode( value );
								}
							}
						)
					)
				)
			]
		},

		save: function( props ) {

			return um_el(
				UmRichText.Content,
				{
					tagName: 'p',
					className: props.className,
					value: props.attributes.content
				}
			);
		}
	});

	//-------------------------------------\\
	//------ Social Activity function -----\\
	//-------------------------------------\\
	var um_users = getUsers();

	function getUsers() {

		var options   = [],
			user_list = '';

		UmApiFetch( { path : '/wp/v2/users/' } ).then(
			function ( answer ) {
				user_list = answer;

				user_list.map(function (user) {
					options.push(
						{
							label: user.name,
							value: user.id
						}
					);
				});

			}
		);

		return options;
	}

	//-------------------------------------\\
	//----- Social Activity Shortcode -----\\
	//-------------------------------------\\
	UmRegisterBlockType( 'um-block/um-user-profile-wall', {
		title: i18n.__( 'User Profile Wall', 'ultimate-member' ),
		description: i18n.__( 'Used on the user profile page', 'ultimate-member' ),
		icon: 'businessman',
		category: 'um-blocks',
		attributes: { // Necessary for saving block content.
			content: {
				source: 'html',
				selector: 'p'
			},
			user_id: {
				type: 'select'
			},
			hashtag: {
				type: 'string'
			},
			wall_post: {
				type: 'number'
			},
			user_wall: {
				type: 'boolean'
			}
		},

		edit: function( props ) {
			var user_id      = props.attributes.user_id,
				hashtag      = props.attributes.hashtag,
				wall_post    = props.attributes.wall_post,
				user_wall    = props.attributes.user_wall,
				attributes   = props.attributes,
				content      = props.attributes.content;

			function onChangeContent( newContent ) {
				props.setAttributes( { content: newContent } );
			}

			function umShortcode() {

				var shortcode = '';

				if ( attributes.user_id !== undefined ) {

					shortcode = '[ultimatemember_wall user_id="' + attributes.user_id + '"';

					if( attributes.hashtag !== undefined ) {
						shortcode = shortcode + ' hashtag="' + attributes.hashtag + '"';
					}

					if( attributes.wall_post !== undefined ) {
						shortcode = shortcode + ' wall_post="' + attributes.wall_post + '"';
					}

					if( attributes.user_wall !== undefined ) {
						shortcode = shortcode + ' user_wall="' + attributes.user_wall + '"';
					}

					shortcode = shortcode + ']';

					props.setAttributes( { content: shortcode } );

				}
			}

			return [
				um_el(
					"div",
					{
						className: "um-social-activity-wrapper"
					},
					um_el(
						UmSelectControl,
						{
							label: i18n.__( 'Select User', 'ultimate-member' ),
							className: "um_select_users",
							type: 'number',
							value: props.attributes.user_id,
							options: um_users,
							onChange: function onChange( value ) {
								props.setAttributes({ user_id: value });
								attributes['user_id'] = value;
								umShortcode();
							}
						}
					),
					um_el(
						UmTextControl,
						{
							className: "um_hashtag",
							label: i18n.__( 'Hashtag', 'ultimate-member' ),
							value: props.attributes.hashtag,
							onChange: function onChange( value ) {
								 props.setAttributes({ hashtag: value });
								 attributes['hashtag'] = value;
								 umShortcode();
							}
						}
					)

				),
				um_el(
					wp.editor.InspectorControls,
					{},
					um_el(
						UmPanelBody,
						{
							title: i18n.__( 'Shortcode Attribute', 'ultimate-member' )
						},
						um_el(
							UmRangeControl,
							{
								label: i18n.__( 'Show the form on the wall?', 'ultimate-member' ),
								value: props.attributes.wall_post,
								min: 2,
								max: 20,
								onChange: function onChange( value ) {
									props.setAttributes({ wall_post: value });
									attributes['wall_post'] = value;
									umShortcode();
								}
							}
						),
						um_el(
							UmToggleControl,
							{
								label: i18n.__( 'Show the form on the wall?', 'ultimate-member' ),
								checked: props.attributes.user_wall,
								onChange: function onChange( value ) {
									props.setAttributes({ user_wall: value });
									attributes['user_wall'] = value;
									umShortcode();
								}
							}
						)
					)
				)
			]
		},

		save: function( props ) {

			return um_el(
				UmRichText.Content,
				{
					tagName: 'p',
					className: props.className,
					value: props.attributes.content
				}
			);
		}
	});

})(
	window.wp.blocks,
	window.wp.editor,
	window.wp.components,
	window.wp.i18n,
	window.wp.element
);