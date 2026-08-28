import { useSelect } from '@wordpress/data';
import { PanelBody, Placeholder, SelectControl } from '@wordpress/components';
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
		const formSettings = window.um_forms_settings && form_id ? window.um_forms_settings[form_id] : null;
		const selectedForm = posts ? posts.find((post) => post.id === Number(form_id)) : null;
		const formName = selectedForm ? selectedForm.title.rendered : '';
		const showFormPlaceholder = formSettings && ['login', 'register'].includes(formSettings.mode);

		return (
			<div {...blockProps}>
				{showFormPlaceholder ? (
					<Placeholder
						label={wp.i18n.sprintf(
							wp.i18n.__('Ultimate Member - Form: "%s"', 'ultimate-member'),
							formName
						)}
					/>
				) : (
					<ServerSideRender block="um-block/um-forms" attributes={attributes} />
				)}
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

const initProfileForm = (form) => {
	const isProfileForm =
		form.dataset.mode === 'profile' ||
		form.classList.contains('um-profile') ||
		!!form.querySelector('.um-profile-photo, .um-profile-body, .um-profile-header-core');

	if (!isProfileForm || !window.UM || !window.UM.frontend) {
		return;
	}

	const initResponsive = () => {
		if (window.UM.frontend.responsive) {
			window.UM.frontend.responsive.setClass();
		}
	};
	const scheduleResponsive = () => {
		initResponsive();
		window.requestAnimationFrame(initResponsive);
	};
	const profileRoot = form.classList.contains('um') ? form : form.closest('.um') || form;

	if (profileRoot.dataset.umBlockProfileInitialized === '1') {
		scheduleResponsive();
		return;
	}

	profileRoot.dataset.umBlockProfileInitialized = '1';

	if (window.UM.frontend.image && window.UM.frontend.image.lazyload) {
		window.UM.frontend.image.lazyload.init();
	}

	if (window.UM.common && window.UM.common.choices) {
		window.UM.common.choices.init();
		window.UM.common.choices.initChild();
	}

	if (window.UM.frontend.tabs) {
		window.UM.frontend.tabs.init();
	}

	scheduleResponsive();

	if (window.ResizeObserver) {
		const observer = new ResizeObserver(initResponsive);
		observer.observe(profileRoot);
	}
};

const initRenderedForms = (nodes) => {
	const forms = jQuery(nodes)
		.filter('.um, .um-form, .um-form-new, .um-profile')
		.add(jQuery(nodes).find('.um, .um-form, .um-form-new, .um-profile'));

	forms.each(function() {
		let wrapper = this;

		if (wrapper) {
			if (wrapper.dataset.umBlockClickInitialized !== '1') {
				wrapper.dataset.umBlockClickInitialized = '1';
				wrapper.addEventListener('click', (event) => {
					if (event.target !== wrapper) {
						event.preventDefault();
						event.stopPropagation();
					}
				});
			}

			initProfileForm(wrapper);
		}
	});
};

jQuery(function() {
	initRenderedForms(document);

	let observer = new MutationObserver(function(mutations) {
		mutations.forEach(function(mutation) {
			initRenderedForms(mutation.addedNodes);
		});
	});

	observer.observe(document, {attributes: false, childList: true, characterData: false, subtree:true});
});
