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
