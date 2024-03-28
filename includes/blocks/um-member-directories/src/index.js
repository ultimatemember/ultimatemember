import { useSelect } from '@wordpress/data';
import { PanelBody, SelectControl, Spinner } from '@wordpress/components';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import ServerSideRender from '@wordpress/server-side-render';
import { registerBlockType } from "@wordpress/blocks";

registerBlockType('um-block/um-member-directories', {
	edit: function (props) {
		let { member_id, setAttributes } = props.attributes;
		const blockProps = useBlockProps();
		const posts = useSelect((select) => {
			return select('core').getEntityRecords('postType', 'um_directory', {
				per_page: -1,
				_fields: ['id', 'title']
			});
		});

		if (!posts) {
			return (
				<p>
					<Spinner />
					{wp.i18n.__('Loading...', 'ultimate-member')}
				</p>
			);
		}

		if (posts.length === 0) {
			return 'No posts found.';
		}

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

			if (value !== undefined && value !== '') {
				shortcode = '[ultimatemember form_id="' + value + '"]';
			}

			return shortcode;
		}

		let posts_data = [{ id: '', title: '' }].concat(posts);

		let get_post = posts_data.map((post) => {
			return {
				label: post.title.rendered,
				value: post.id
			};
		});

		return (
			<div {...blockProps}>
				<ServerSideRender block="um-block/um-member-directories" attributes={props.attributes} />
				<InspectorControls>
					<PanelBody title={wp.i18n.__('Select Directories', 'ultimate-member')}>
						<SelectControl
							label={wp.i18n.__('Select Directories', 'ultimate-member')}
							className="um_select_directory"
							value={member_id}
							options={get_post}
							style={{ height: '35px', lineHeight: '20px', padding: '0 7px' }}
							onChange={(value) => {
								props.setAttributes({ member_id: value });
								umShortcode(value);
							}}
						/>
					</PanelBody>
				</InspectorControls>
			</div>
		);

	},
	save: function save(props) {
		return null;
	}
});

jQuery(window).on( 'load', function($) {
	var observer = new MutationObserver(function(mutations) {
		mutations.forEach(function(mutation) {

			jQuery(mutation.addedNodes).find('.um.um-directory').each(function() {
				var directory = jQuery(this);
				um_ajax_get_members( directory );
				um_slider_filter_init( directory );
				um_datepicker_filter_init( directory );
				um_timepicker_filter_init( directory );
			});
			jQuery(mutation.addedNodes).find('.um-member').each(function() {
				var directory = jQuery(this).parent();
				UM_Member_Grid(directory);
			});
		});
	});

	observer.observe(document, {attributes: false, childList: true, characterData: false, subtree:true});
});
