import { useSelect } from '@wordpress/data';
import { Disabled, PanelBody, SelectControl, Spinner } from '@wordpress/components';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import ServerSideRender from '@wordpress/server-side-render';
import { registerBlockType } from "@wordpress/blocks";
import { useMemo, useEffect, useRef } from '@wordpress/element';

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
		const directoryObject = new UM.frontend.directory(hash, directory);
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

// Keep Masonry scoped to this preview. Its stock element filter rejects iframe nodes.
const createPreviewMasonry = (element) => {
	class PreviewMasonry extends window.Masonry {
		_filterFindItemElements(elements) {
			return Array.from(elements).filter((node) => node.nodeType === 1 && node.matches('.um-member'));
		}
	}
	const options = wp.hooks.applyFilters('um_member_directory_grid_masonry_attrs', {
		itemSelector: '.um-member',
		columnWidth: '.um-member',
		gutter: '.um-gutter-sizer',
		resize: false,
		transitionDuration: 0
	}, jQuery(element));
	const previous = jQuery.data(element, 'masonry');
	if (previous) previous.destroy();
	const masonry = new PreviewMasonry(element, options);
	// UM's resize handler must reuse this instance rather than create a second one.
	jQuery.data(element, 'masonry', masonry);
	return masonry;
};

registerBlockType('um-block/um-member-directories', {
	edit: function (props) {
		const blockProps = useBlockProps();
		const previewRef = useRef(null);
		const { attributes, setAttributes } = props;
		const { member_id } = attributes;

		const posts = useSelect((select) => {
			return select('core').getEntityRecords('postType', 'um_directory', {
				per_page: -1,
				_fields: ['id', 'title']
			});
		}, []);

		useEffect(() => {
			const root = previewRef.current;
			if (!root) {
				return;
			}
			const initialized = new WeakSet();
			const grids = new Map();
			let frame;
			const layout = () => {
				frame = undefined;
				jQuery(root).find('.um.um-directory').each(function() {
					const directory = jQuery(this);
					// Apply UM's container breakpoints before measuring cards.
					if (!directory.find('.um-members.um-members-grid').length) return;
					const width = directory.width();
					if (!width) return;
					directory.removeClass('uimob340 uimob500 uimob800 uimob960');
					const breakpoint = [340, 500, 800, 960].find((limit) => width <= limit);
					if (breakpoint) directory.addClass('uimob' + breakpoint);
					directory.find('.um-member-cover, .um-member-cover .um-cover-e').each(function() {
						const cover = jQuery(this);
						const ratio = parseFloat(cover.data('ratio'));
						if (ratio > 0) cover.height(Math.round(cover.width() / ratio));
					});
				});
				jQuery(root).find('.um-members.um-members-grid').each(function() {
					if (!this.isConnected || !this.getBoundingClientRect().width || typeof window.Masonry !== 'function') return;
					let masonry = grids.get(this);
					if (!masonry) {
						masonry = createPreviewMasonry(this);
						grids.set(this, masonry);
					} else {
						masonry.reloadItems();
						masonry.layout();
					}
					this.querySelectorAll('.um-member').forEach((card) => resizeObserver.observe(card));
				});
				grids.forEach((masonry, grid) => {
					if (!root.contains(grid)) {
						masonry.destroy();
						grids.delete(grid);
					}
				});
			};
			const scheduleLayout = () => {
				if (frame === undefined) frame = requestAnimationFrame(layout);
			};
			const sizes = new WeakMap();
			const resizeObserver = new ResizeObserver((entries) => {
				entries.forEach(({ target, contentRect }) => {
					const size = contentRect.width + ':' + contentRect.height;
					if (sizes.get(target) !== size) {
						sizes.set(target, size);
						scheduleLayout();
					}
				});
			});
			resizeObserver.observe(root);
			root.addEventListener('load', scheduleLayout, true);
			const initialize = () => {
				jQuery(root).find('.um.um-directory').each(function() {
					if (!initialized.has(this)) {
						initialized.add(this);
						initMemberDirectoryPreview(jQuery(this));
					}
				});
				scheduleLayout();
			};
			const observer = new MutationObserver(initialize);
			observer.observe(root, { childList: true, subtree: true });
			initialize();
			return () => {
				observer.disconnect();
				resizeObserver.disconnect();
				root.removeEventListener('load', scheduleLayout, true);
				cancelAnimationFrame(frame);
				grids.forEach((masonry) => masonry.destroy());
			};
		}, [posts]);

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
			<div {...blockProps} ref={previewRef}>
				<Disabled>
					<ServerSideRender block="um-block/um-member-directories" attributes={attributes} />
				</Disabled>
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

