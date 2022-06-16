//-------------------------------------\\
//---- Um Online members shortcode ----\\
//-------------------------------------\\

wp.blocks.registerBlockType( 'um-block/um-online', {
	title: wp.i18n.__( 'UM online members', 'ultimate-member-pro' ),
	description: wp.i18n.__( 'Displaying online members', 'ultimate-member-pro' ),
	icon: 'groups',
	category: 'um-blocks',
	attributes: {
		max: {
			type: 'string'
		},
		role: {
			type: 'select'
		},
		content: {
			source: 'html',
			selector: 'p'
		}
	},

	edit: wp.data.withSelect( function( select ) {
		return {
			roles: um_online_roles
		};
	} )( function( props ) {

			var roles         = props.roles,
				className     = props.className,
				attributes    = props.attributes,
				setAttributes = props.setAttributes,
				role          = props.attributes.role,
				content       = props.attributes.content,
				max           = props.attributes.max;

			function get_option( roles ) {

				var option = [];

				Object.keys(roles).map(function(key) {
					option.push(
						{
							label: roles[key],
							value: key
						}
					);
				});

				return option;
			}

			function umShortcode( max, role ) {
				var shortcode = '[ultimatemember_online';

				if ( max !== undefined && max !== '' ) {
					shortcode = shortcode + ' max="' + max + '"';
				}
				if ( role !== undefined && 0 !== role.length  ) {
					shortcode = shortcode + ' roles="' + role + '"';
				}

				shortcode = shortcode + ']';

				props.setAttributes({ content: shortcode });
			}

			if ( ! roles ) {
				return wp.element.createElement(
					'p',
					{
						className: className
					},
					wp.element.createElement(
						wp.components.Spinner,
						null
					),
					wp.i18n.__( 'Loading roles', 'ultimate-member-pro' )
				);
			}

			if ( 0 === roles.length ) {
				return wp.element.createElement(
					'p',
					null,
					wp.i18n.__( 'No roles', 'ultimate-member-pro' )
				);
			}

			if ( content === undefined ) {
				props.setAttributes({ content: '[ultimatemember_online]' });
			}

			var get_roles = get_option( roles );

			return [
				wp.element.createElement(
					"div",
					{
						className: 'um-online-members-wrapper'
					},
					wp.i18n.__( 'Online members', 'ultimate-member-pro' )
				),
				wp.element.createElement(
					wp.blockEditor.InspectorControls,
					{},
					wp.element.createElement(
						wp.components.PanelBody,
						{
							title: wp.i18n.__( 'Online members', 'ultimate-member-pro' )
						},
						wp.element.createElement(
							wp.components.SelectControl,
							{
								label: wp.i18n.__( 'Select role', 'ultimate-member-pro' ),
								className: 'um_select_role',
								type: 'number',
								value: props.attributes.role,
								options: get_roles,
								multiple: true,
								onChange: function onChange( value ) {
									props.setAttributes( { role: value } );
									umShortcode( max, value );
								}
							}
						),
						wp.element.createElement(
							wp.components.TextControl,
							{
								label: wp.i18n.__( 'Maximum amount of members shown on a one screen', 'ultimate-member-pro' ),
								className: 'um_select_max',
								type: 'number',
								value: props.attributes.max,
								min: 0,
								onChange: function onChange( value ) {
									props.setAttributes( { max: value } );
									umShortcode( value, role );
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
