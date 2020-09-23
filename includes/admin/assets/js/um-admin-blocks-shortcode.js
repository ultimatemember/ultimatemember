//-------------------------------------\\
//--------- Um Forms shortcode --------\\
//-------------------------------------\\

wp.blocks.registerBlockType( 'um-block/um-forms', {
	title: wp.i18n.__( 'Form', 'ultimate-member' ),
	description: wp.i18n.__( 'Choose display form', 'ultimate-member' ),
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

	edit: wp.data.withSelect( function( select ) {
		return {
			posts: select( 'core' ).getEntityRecords( 'postType', 'um_form', {
				per_page: -1
			})
		};
	} )( function( props ) {
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
				return wp.element.createElement(
					'p',
					{
						className: className
					},
					wp.element.createElement(
						wp.components.Spinner,
						null
					),
					wp.i18n.__( 'Loading Forms', 'ultimate-member' )
				);
			}

			if ( 0 === posts.length ) {
				return wp.element.createElement(
					'p',
					null,
					wp.i18n.__( 'No Posts', 'ultimate-member' )
				);
			}

			if ( form_id === undefined ) {
				props.setAttributes({ form_id: posts[0]['id'] });
				var shortcode = umShortcode( posts[0]['id'] );
				props.setAttributes( { content: shortcode } );
			}

			var get_post = get_option( posts );

			return wp.element.createElement(
				'div',
				{
					className: className
				},
				wp.element.createElement(
					wp.components.SelectControl,
					{
						label: wp.i18n.__( 'Select Forms', 'ultimate-member' ),
						className: 'um_select_forms',
						type: 'number',
						value: form_id,
						options: get_post,
						onChange: function onChange( value ) {
							props.setAttributes({ form_id: value });
							var shortcode = umShortcode( value );
							props.setAttributes( { content: shortcode } );
						}
					}
				)
			);
		} // end withSelect
	), // end edit

	save: function save( props ) {

		return wp.element.createElement(
			wp.editor.RichText.Content,
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

wp.blocks.registerBlockType( 'um-block/um-member-directories', {
	title: wp.i18n.__( 'Member Directory', 'ultimate-member' ),
	description: wp.i18n.__( 'Choose display directory', 'ultimate-member' ),
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

	edit: wp.data.withSelect( function( select ) {
		return {
			posts: select( 'core' ).getEntityRecords( 'postType', 'um_directory', {
				per_page: -1
			})
		};
	} )( function( props ) {
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
				return wp.element.createElement(
					'p',
					{
						className: className
					},
					wp.element.createElement(
						wp.components.Spinner,
						null
					),
					wp.i18n.__( 'Loading Forms', 'ultimate-member' )
				);
			}

			if ( 0 === posts.length ) {
				return wp.element.createElement(
					'p',
					null,
					wp.i18n.__( 'No Posts', 'ultimate-member' )
				);
			}

			if ( member_id === undefined ) {
				props.setAttributes({ member_id: posts[0]['id'] });
				var shortcode = umShortcode(posts[0]['id']);
				props.setAttributes( { content: shortcode } );
			}

			var get_post = get_option( posts );

			return wp.element.createElement(
				'div',
				{
					className: className
				},
				wp.element.createElement(
					wp.components.SelectControl,
					{
						label: wp.i18n.__( 'Select Directories', 'ultimate-member' ),
						className: 'um_select_directory',
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

		return wp.element.createElement(
			wp.editor.RichText.Content,
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
wp.blocks.registerBlockType( 'um-block/um-password-reset', {
	title: wp.i18n.__( 'Password Reset', 'ultimate-member' ),
	description: wp.i18n.__( 'Displaying the password reset form', 'ultimate-member' ),
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
			wp.element.createElement(
				"div",
				{
					className: "um-password-reset-wrapper"
				},
				wp.i18n.__( 'Password Reset', 'ultimate-member' )
			)
		]
	},

	save: function( props ) {

		return wp.element.createElement(
			wp.editor.RichText.Content,
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
wp.blocks.registerBlockType( 'um-block/um-account', {
	title: wp.i18n.__( 'Account', 'ultimate-member' ),
	description: wp.i18n.__( 'Displaying the account page of the current user', 'ultimate-member' ),
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
			tab     = props.attributes.tab;

		function get_options() {
			var option = [];

			option.push( { label: wp.i18n.__( 'All', 'ultimate-member' ), value: 'all' } );

			for ( var key in um_account_settings ) {
				if ( um_account_settings.hasOwnProperty( key ) && um_account_settings[ key ]['enabled'] ) {
					option.push(
						{
							label: um_account_settings[ key ]['label'],
							value: key
						}
					)
				}
			}

			return option;
		}

		function umShortcode( value ) {

			var shortcode = '[ultimatemember_account';

			if ( value !== 'all' ) {
				shortcode = shortcode + ' tab="' + value + '"';
			}

			shortcode = shortcode + ']';

			props.setAttributes({ content: shortcode });
		}

		if ( content === undefined ) {
			props.setAttributes({ content: '[ultimatemember_account]' });
		}

		return [
			wp.element.createElement(
				"div",
				{
					className: 'um-account-wrapper'
				},
				wp.i18n.__( 'Account', 'ultimate-member' )
			),
			wp.element.createElement(
				wp.editor.InspectorControls,
				{},
				wp.element.createElement(
					wp.components.PanelBody,
					{
						title: wp.i18n.__( 'Account Tab', 'ultimate-member' )
					},
					wp.element.createElement(
						wp.components.SelectControl,
						{
							label: wp.i18n.__( 'Select Tab', 'ultimate-member' ),
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

		return wp.element.createElement(
			wp.editor.RichText.Content,
			{
				tagName: 'p',
				className: props.className,
				value: props.attributes.content
			}
		);
	}
});