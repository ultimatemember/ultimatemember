import { useSelect } from '@wordpress/data';
import { PanelBody, SelectControl, Spinner } from '@wordpress/components';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import ServerSideRender from '@wordpress/server-side-render';
import { registerBlockType } from '@wordpress/blocks';
import { useMemo } from '@wordpress/element';

registerBlockType('um-block/um-forms', {
	edit: function (props) {
		const blockProps = useBlockProps();
		const { attributes, setAttributes } = props;
		const { form_id } = attributes;

		const posts = useSelect(
			(select) => select('core').getEntityRecords('postType', 'um_form', { per_page: -1, _fields: ['id', 'title'] }),
			[]
		);

		const options = useMemo(() => {
			if (!posts) {
				return [{ label: wp.i18n.__('Loading...', 'ultimate-member'), value: '' }];
			}
			if (posts.length === 0) {
				return [{ label: wp.i18n.__('No forms found.', 'ultimate-member'), value: '' }];
			}
			return [{ label: wp.i18n.__('Select Form', 'ultimate-member'), value: '' }].concat(
				posts.map((post) => ({ label: post.title.rendered, value: post.id }))
			);
		}, [posts]);

		const onFormChange = (value) => setAttributes({ form_id: value });

		return (
			<div {...blockProps}>
				<ServerSideRender block="um-block/um-forms" attributes={attributes} />
				<InspectorControls>
					<PanelBody title={wp.i18n.__('Select Forms', 'ultimate-member')}>
						<SelectControl
							label={wp.i18n.__('Select Forms', 'ultimate-member')}
							className="um_select_forms"
							value={form_id}
							options={options}
							onChange={onFormChange}
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

			jQuery(mutation.addedNodes).find('.um-form').each(function() {
				let wrapper = document.querySelector('.um-form');

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
