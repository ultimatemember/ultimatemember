'use strict';

var um_components     = wp.components,
	umSelectControl   = um_components.SelectControl,
	umTextareaControl = um_components.TextareaControl;


function um_admin_blocks_custom_fields( um_condition_fields, props ) {
	return wp.hooks.applyFilters( 'um_admin_blocks_custom_fields', [], um_condition_fields, props );
}

var um_block_restriction = wp.compose.createHigherOrderComponent(
	function( BlockEdit ) {
		var um_condition_fields = {
			um_who_access:      'um_block_settings_hide',
			um_roles_access:    'um_block_settings_hide',
			um_message_type:    'um_block_settings_hide',
			um_message_content: 'um_block_settings_hide'
		};

		um_condition_fields = wp.hooks.applyFilters( 'um_admin_blocks_condition_fields_default', um_condition_fields );

		return function( props ) {
			let initialIsRestrict = props.attributes.um_is_restrict !== undefined ? props.attributes.um_is_restrict : false;

			if ( props.attributes.um_is_restrict !== true ) {
				um_condition_fields['um_who_access']      = 'um_block_settings_hide';
				um_condition_fields['um_roles_access']    = 'um_block_settings_hide';
				um_condition_fields['um_message_type']    = 'um_block_settings_hide';
				um_condition_fields['um_message_content'] = 'um_block_settings_hide';
			} else {
				um_condition_fields['um_who_access'] = '';

				if ( parseInt( props.attributes.um_who_access ) === 0 || typeof props.attributes.um_who_access === 'undefined' ) {
					um_condition_fields['um_roles_access']    = 'um_block_settings_hide';
					um_condition_fields['um_message_type']    = 'um_block_settings_hide';
					um_condition_fields['um_message_content'] = 'um_block_settings_hide';
				} else if ( parseInt( props.attributes.um_who_access ) === 1  ) {
					um_condition_fields['um_roles_access'] = '';
					um_condition_fields['um_message_type'] = '';

					if ( parseInt( props.attributes.um_message_type ) === 2 ) {
						um_condition_fields['um_message_content'] = '';
					} else {
						um_condition_fields['um_message_content'] = 'um_block_settings_hide';
					}
				} else {
					um_condition_fields['um_message_type'] = '';

					if ( parseInt( props.attributes.um_message_type ) === 2 ) {
						um_condition_fields['um_message_content'] = '';
					} else {
						um_condition_fields['um_message_content'] = 'um_block_settings_hide';
					}
				}
			}

			um_condition_fields = wp.hooks.applyFilters( 'um_admin_blocks_condition_fields', um_condition_fields, props );

			return wp.element.createElement(
				wp.element.Fragment,
				{},
				wp.element.createElement( BlockEdit, props ),
				wp.element.createElement(
					wp.blockEditor.InspectorControls,
					{},
					wp.element.createElement(
						wp.components.PanelBody,
						{
							title: wp.i18n.__( 'Ultimate Member: Content Restriction', 'ultimate-member' ),
							className: 'um_block_settings'
						},
						wp.element.createElement(
							wp.components.ToggleControl,
							{
								label: wp.i18n.__( 'Restrict access?', 'ultimate-member' ),
								checked: initialIsRestrict,
								onChange: function onChange( value ) {
									props.setAttributes( { um_is_restrict: value } );
									if ( value === false ) {
										um_condition_fields['um_who_access']      = 'um_block_settings_hide';
										um_condition_fields['um_roles_access']    = 'um_block_settings_hide';
										um_condition_fields['um_message_type']    = 'um_block_settings_hide';
										um_condition_fields['um_message_content'] = 'um_block_settings_hide';
									} else {
										um_condition_fields['um_who_access'] = '';
									}

									um_condition_fields = wp.hooks.applyFilters( 'um_admin_blocks_condition_fields_on_change', um_condition_fields, 'um_is_restrict', value );
								}
							}
						),
						wp.element.createElement(
							umSelectControl,
							{
								type: 'number',
								className: um_condition_fields['um_who_access'],
								label: wp.i18n.__( 'Who can access this block?', 'ultimate-member' ),
								value: props.attributes.um_who_access,
								options: [
									{
										label: wp.i18n.__( 'Everyone', 'ultimate-member' ),
										value: 0
									},
									{
										label: wp.i18n.__( 'Logged in users', 'ultimate-member' ),
										value: 1
									},
									{
										label: wp.i18n.__( 'Logged out users', 'ultimate-member' ),
										value: 2
									}
								],
								onChange: function onChange( value ) {
									props.setAttributes( { um_who_access: value } );
									if ( parseInt( value ) === 0 ) {
										um_condition_fields['um_message_type']    = 'um_block_settings_hide';
										um_condition_fields['um_message_content'] = 'um_block_settings_hide';
										um_condition_fields['um_roles_access']    = 'um_block_settings_hide';
									} else if ( parseInt( value ) === 1 ) {
										um_condition_fields['um_message_type'] = '';
										um_condition_fields['um_roles_access'] = '';
									} else {
										um_condition_fields['um_message_type'] = '';
										um_condition_fields['um_roles_access'] = 'um_block_settings_hide';
									}

									um_condition_fields = wp.hooks.applyFilters( 'um_admin_blocks_condition_fields_on_change', um_condition_fields, 'um_who_access', value );
								}
							}
						),
						wp.element.createElement(
							umSelectControl,
							{
								multiple: true,
								className: um_condition_fields['um_roles_access'],
								label: wp.i18n.__( 'What roles can access this block?', 'ultimate-member' ),
								value: props.attributes.um_roles_access,
								options: um_restrict_roles,
								onChange: function onChange( value ) {
									props.setAttributes( { um_roles_access: value } );
								}
							}
						),
						wp.element.createElement(
							umSelectControl,
							{
								type: 'number',
								className: um_condition_fields['um_message_type'],
								label: wp.i18n.__( 'Restriction action', 'ultimate-member' ),
								value: props.attributes.um_message_type,
								options: [
									{
										label: wp.i18n.__( 'Hide block', 'ultimate-member' ),
										value: 0
									},
									{
										label: wp.i18n.__( 'Show global default message', 'ultimate-member' ),
										value: 1
									},
									{
										label: wp.i18n.__( 'Show custom message', 'ultimate-member' ),
										value: 2
									}
								],
								onChange: function onChange( value ) {
									props.setAttributes( { um_message_type: value } );
									if ( parseInt( value ) === 2 ) {
										um_condition_fields['um_message_content'] = '';
									} else {
										um_condition_fields['um_message_content'] = 'um_block_settings_hide';
									}
								}
							}
						),
						wp.element.createElement(
							umTextareaControl,
							{
								type: 'number',
								className: um_condition_fields['um_message_content'],
								label: wp.i18n.__( 'Custom restricted access message', 'ultimate-member' ),
								value: props.attributes.um_message_content,
								onChange: function onChange( value ) {
									props.setAttributes( { um_message_content: value } );
								}
							}
						),
						um_admin_blocks_custom_fields( um_condition_fields, props )
					)
				)
			);
		};
	},
	'um_block_restriction'
);

wp.hooks.addFilter( 'editor.BlockEdit', 'um-block/um_block_restriction', um_block_restriction );
