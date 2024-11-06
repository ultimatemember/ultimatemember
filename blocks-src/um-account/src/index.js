import { registerBlockType } from '@wordpress/blocks';
import ServerSideRender from '@wordpress/server-side-render';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, SelectControl } from "@wordpress/components";
import { useMemo, useCallback } from '@wordpress/element';

registerBlockType('um-block/um-account', {
	edit: function (props) {
		const { attributes, setAttributes } = props;
		const { tab } = attributes;
		const blockProps = useBlockProps();

		const options = useMemo(() => {
			const option = [{ label: wp.i18n.__('All', 'ultimate-member'), value: 'all' }];
			for (const key in um_account_settings) {
				if (um_account_settings.hasOwnProperty(key) && um_account_settings[key]['enabled']) {
					option.push({
						label: um_account_settings[key]['label'],
						value: key
					});
				}
			}
			return option;
		}, []);

		const onTabChange = useCallback((value) => {
			setAttributes({ tab: value });
			const shortcode = `[ultimatemember_account${value !== 'all' ? ` tab="${value}"` : ''}]`;
			setAttributes({ content: shortcode });
		}, [setAttributes]);

		return (
			<div {...blockProps}>
				<ServerSideRender block="um-block/um-account" attributes={attributes} />
				<InspectorControls>
					<PanelBody title={wp.i18n.__('Account Tab', 'ultimate-member')}>
						<SelectControl
							label={wp.i18n.__('Select Tab', 'ultimate-member')}
							className="um_select_account_tab"
							value={tab}
							options={options}
							style={{ height: '35px', lineHeight: '20px', padding: '0 7px' }}
							onChange={onTabChange}
						/>
					</PanelBody>
				</InspectorControls>
			</div>
		);
	},

	save: () => null
});

jQuery(window).on( 'load', function($) {
	let observer = new MutationObserver(function(mutations) {
		mutations.forEach(function(mutation) {

			jQuery(mutation.addedNodes).find('.um.um-account').each(function() {
				let current_tab = jQuery(this).find('.um-account-main').attr('data-current_tab');
				let wrapper = document.querySelector('.um-form');

				if ( current_tab ) {
					jQuery(this).find('.um-account-tab[data-tab="'+current_tab+'"]').show();
					jQuery(this).find('.um-account-tab:not(:visible)').find( 'input, select, textarea' ).not( ':disabled' ).addClass('um_account_inactive').prop( 'disabled', true ).attr( 'disabled', true );
					um_responsive();
				}

				if (wrapper) {
					wrapper.addEventListener('click', (event) => {
						if (event.target !== wrapper) {
							event.preventDefault();
							event.stopPropagation();
						}
					});
				}
			});
		});
	});

	observer.observe(document, {attributes: false, childList: true, characterData: false, subtree:true});
});
