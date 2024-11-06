import { useSelect } from '@wordpress/data';
import { PanelBody, SelectControl, Spinner } from '@wordpress/components';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import ServerSideRender from '@wordpress/server-side-render';
import { registerBlockType } from "@wordpress/blocks";
import { useMemo } from '@wordpress/element';

registerBlockType('um-block/um-member-directories', {
	edit: function (props) {
		const blockProps = useBlockProps();
		const { attributes, setAttributes } = props;
		const { member_id } = attributes;

		const posts = useSelect((select) => {
			return select('core').getEntityRecords('postType', 'um_directory', {
				per_page: -1,
				_fields: ['id', 'title']
			});
		}, []);

		const options = useMemo(() => {
			if (!posts) {
				return [{ label: wp.i18n.__('Loading...', 'ultimate-member'), value: '' }];
			}
			if (posts.length === 0) {
				return [{ label: wp.i18n.__('No posts found.', 'ultimate-member'), value: '' }];
			}
			return [{ label: wp.i18n.__('Select Directory', 'ultimate-member'), value: '' }].concat(
				posts.map((post) => ({
					label: post.title.rendered,
					value: post.id
				}))
			);
		}, [posts]);

		const onMemberIdChange = (value) => setAttributes({ member_id: value });

		if (!posts) {
			return (
				<p>
					<Spinner />
					{wp.i18n.__('Loading...', 'ultimate-member')}
				</p>
			);
		}

		return (
			<div {...blockProps}>
				<ServerSideRender block="um-block/um-member-directories" attributes={attributes} />
				<InspectorControls>
					<PanelBody title={wp.i18n.__('Select Directories', 'ultimate-member')}>
						<SelectControl
							label={wp.i18n.__('Select Directories', 'ultimate-member')}
							className="um_select_directory"
							value={member_id}
							options={options}
							onChange={onMemberIdChange}
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

			jQuery(mutation.addedNodes).find('.um.um-directory').each(function() {
				let wrapper = document.querySelector('.um-directory');
				let directory = jQuery(this);
				um_ajax_get_members( directory );
				um_slider_filter_init( directory );
				um_datepicker_filter_init( directory );
				um_timepicker_filter_init( directory );

				if (wrapper) {
					wrapper.addEventListener('click', (event) => {
						if (event.target !== wrapper) {
							event.preventDefault();
							event.stopPropagation();
						}
					});
				}
			});
			jQuery(mutation.addedNodes).find('.um-member').each(function() {
				let wrapper = document.querySelector('.um-member');
				let directory = jQuery(this).parent();
				UM_Member_Grid(directory);

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
