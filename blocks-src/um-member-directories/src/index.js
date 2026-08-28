import { useSelect } from '@wordpress/data';
import { PanelBody, SelectControl, Spinner } from '@wordpress/components';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import ServerSideRender from '@wordpress/server-side-render';
import { registerBlockType } from "@wordpress/blocks";
import { useMemo } from '@wordpress/element';

const initChoicesInPreview = (directory) => {
	if (
		typeof window.Choices !== 'function' ||
		typeof window.UM !== 'object' ||
		typeof UM.common !== 'object' ||
		typeof UM.common.choices !== 'object'
	) {
		return;
	}

	if (typeof UM.common.choices.choicesInstances !== 'object') {
		UM.common.choices.choicesInstances = {};
	}

	directory.find('.js-choice').each(function() {
		const field = jQuery(this);
		if ('active' === field.attr('data-choice')) {
			return;
		}

		const element = field[0];
		let attrs = {};

		if (field.attr('multiple')) {
			attrs = { removeItemButton: true };

			const maxSelections = field.data('max_selections');
			if (maxSelections) {
				attrs.maxItemCount = maxSelections;
			}
		} else if (field.hasClass('um-no-search')) {
			attrs = { searchEnabled: false };
		} else if (field.hasClass('um-no-native-search')) {
			attrs = { searchEnabled: true, searchChoices: false };
		}

		if (field.hasClass('um-add-choices')) {
			attrs.addChoices = true;
			attrs.addItems = true;
		}

		if (field.hasClass('um-no-native-sorting')) {
			attrs.shouldSort = false;
		}

		if (field.hasClass('um-responsive')) {
			const outerClasses = ['choices', 'um-responsive'];
			['um-ui-xs', 'um-ui-s', 'um-ui-m', 'um-ui-l', 'um-ui-xl'].forEach((className) => {
				if (field.hasClass(className)) {
					outerClasses.push(className);
				}
			});
			attrs.classNames = { containerOuter: outerClasses };
		}

		const choices = new window.Choices(element, attrs);
		UM.common.choices.choicesInstances[element.id] = choices;

		wp.hooks.doAction('um_after_choices_element_init', field, choices);
	});
};

const initMemberDirectoryPreview = (directory) => {
	if (
		typeof window.UM === 'object' &&
		typeof UM.frontend === 'object' &&
		typeof UM.frontend.directory === 'function' &&
		typeof UM.frontend.directories === 'object'
	) {
		const hash = UM.frontend.directories.getHash(directory);
		const directoryObject = new UM.frontend.directory(hash);
		UM.frontend.directories.list[hash] = directoryObject;

		wp.hooks.doAction('um_member_directory_on_init', directory, hash);

		const layout = directoryObject.getDataFromURL('view_type');
		if (typeof layout !== 'undefined') {
			directoryObject.setLayout(layout, { ignoreURL: true });
		} else {
			directoryObject.setLayout(directoryObject.defaultLayout, { ignoreURL: true });
		}

		const page = directoryObject.getDataFromURL('page');
		if (typeof page !== 'undefined') {
			directoryObject.setPage(page, { ignoreURL: true });
		}

		const search = directoryObject.getDataFromURL('search');
		if (typeof search !== 'undefined') {
			directoryObject.setSearch(search, { ignoreURL: true });
		}

		const sort = directoryObject.getDataFromURL('sort');
		if (typeof sort !== 'undefined') {
			directoryObject.setOrder(sort, { ignoreURL: true });
		}

		initChoicesInPreview(directory);

		directoryObject.setFilters(true);

		if (false === wp.hooks.applyFilters('um_member_directory_ignore_after_search', false, directory)) {
			const mustSearch = parseInt(directory.data('must-search'));
			if (1 === mustSearch && '' === directoryObject.getSearch() && 0 === Object.keys(directoryObject.getFilters()).length) {
				return;
			}
		}

		if (!wp.hooks.applyFilters('um_member_directory_prevent_default_request', false, directoryObject)) {
			directoryObject.request({ defaultRequest: true });
		}

		return;
	}

	if (typeof window.um_ajax_get_members === 'function') {
		window.um_ajax_get_members(directory);
	}

	if (typeof window.um_slider_filter_init === 'function') {
		window.um_slider_filter_init(directory);
	}

	if (typeof window.um_datepicker_filter_init === 'function') {
		window.um_datepicker_filter_init(directory);
	}

	if (typeof window.um_timepicker_filter_init === 'function') {
		window.um_timepicker_filter_init(directory);
	}
};

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
				initMemberDirectoryPreview(directory);

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
				if (directory.hasClass('um-members-grid') && typeof window.UM_Member_Grid === 'function') {
					window.UM_Member_Grid(directory);
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
