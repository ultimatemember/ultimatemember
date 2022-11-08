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
					shortcode = '[ultimatemember_directory id="' + value + '"]';
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

			return [
				wp.element.createElement(
					"div",
					{
						className: 'um-member-directory-wrapper'
					},
					wp.i18n.__( 'UM Member Directory', 'ultimate-member' )
				),
				wp.element.createElement(
					wp.blockEditor.InspectorControls,
					{},
					wp.element.createElement(
						wp.components.PanelBody,
						{
							title: wp.i18n.__( 'UM Member Directory', 'ultimate-member' )
						},
						wp.element.createElement(
							wp.components.SelectControl,
							{
								label: wp.i18n.__( 'Select Directories', 'ultimate-member' ),
								className: 'um_select_directory',
								type: 'number',
								value: member_id,
								options: get_post,
								style: {
									height: '35px',
									lineHeight: '20px',
									padding: '0 7px'
								},
								onChange: function onChange( value ) {
									props.setAttributes({ member_id: value });
									var shortcode = umShortcode(value);
									props.setAttributes( { content: shortcode } );
								}
							}
						)
					)
				)
			]
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
