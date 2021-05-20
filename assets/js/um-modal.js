/*!
 * UM-Modal
 * @author	Ultimate Member
 * @link		http://ultimatemember.com/
 * @since		UM 3.0
 * @version	1.0.0
 */

/**
 * TUTORIAL
 *
 * @function UM.modal.addModal( content, options );  Add a modal
 *
 * @param {string|object} content   A content for the modal body.
 * This parameter can be:
 *	1) HTML           - the content is a custom HTML code
 *	2) URL            - retrieve the remote content
 *	3) DOM element    - move DOM element into the modal
 *	4) jQuery object  - insert elements from the set of jQuery object
 *	5) 'loading'      - the content is a 'loading' icon
 *
 * @param {object} options   The modal settings. Optional.
 * The object properties:
 *	id {string}         - The value of the attribute 'id'. Default null.
 *	attr {object}       - Additional attributes. Example {title: "The modal"}. Default {}.
 *	class {string}      - The class of the modal size: small, normal, large. Default 'large'.
 *	closeButton {bool}  - Show close button or not. Default false.
 *	duration {int}      - The duration of the animation, ms. Default 400.
 *	header {string}     - Text in the modal header. No header if empty. Default ''.
 *	buttons {Array}     - Buttons in the modal footer. No footer if empty. Default [].
 *	type {string}       - The type of modal: body, photo, popup. Default 'body'.
 *	load {string}       - URL for the remote content to load with the jQuery.load() function. Default null.
 *
 * @function UM.modal.clear();                       Remove all modals
 *
 * @function UM.modal.close();                       Remove current modal
 *
 * @function UM.modal.hide();                        Hide current modal
 *
 * @function UM.modal.responsive();                  Update modal size and position
 *
 * @function UM.modal.setContent( content );         Update a modal content
 */

/**
 * EXAMPLES
 *
 * Example 1 - The simplest modal
 *	UM.modal.addModal( 'Hello world!' );
 *
 * Example 2 - Modal with a remote content
 *	UM.modal.addModal( 'http://um_wp5/notifications/' );
 *
 * Example 3 - Modal with header
 *	UM.modal.addModal( 'Hello world!', {
 *		header: 'Header text'
 *	} );
 *
 * Example 4 - Modal with header and footer
 *	UM.modal.addModal( 'Hello world!', {
 *		header: 'Header text',
 *		buttons: [
 *			'<input type="submit" value="Add" class="um-modal-btn">',
 *			'<a href="javascript:void(0);" data-action="um_remove_modal" class="um-modal-btn alt">Cancel</a>'
 *		]
 *	} );
 *
 * Example 5 - Empty popup with a 'loading' icon
 *	UM.modal.addModal('loading', {
 *		type: 'popup'
 *	});
 *
 * Example 6 - Popup with a remote content. See https://api.jquery.com/load/ for details.
 *	UM.modal.addModal('loading', {
 *		type: 'popup',
 *		load: '/notifications/ .um-notification-shortcode'
 *	});
 *
 * Example 7 - The image in popup
 *	UM.modal.addModal('<img src="/um-download/7/test_image_upload_37/3305/1ba055bd95" />', {
 *		closeButton: true,
 *		type: 'photo'
 *	});
 *
 * Example 8 - Add a modal via jQuery
 *	jQuery('.um-cover-e img').umModal({ closeButton: true, type: 'photo' });
 */

(function ($) {

	if ( typeof window.UM === 'undefined' ) {
		window.UM = {};
	}

	/**
	 * The UM-Modal library
	 * @type object
	 */
	UM.modal = {

		/**
		 * An array of modals
		 */
		all: [],

		/**
		 * Add and display a modal
		 * @param   {string|object} content  A content of the modal body.
		 * @param   {object} options         Modal properties. Optional.
		 * @returns {object}                 A modal jQuery object.
		 */
		addModal: function (content, options) {
			options = UM.modal.getOptions(options);

			if ( UM.modal.isValidHttpUrl(content) ) {
				options.load = content;
				content = 'loading';
			}
			if ( content === 'loading' ) {
				content = '<div class="loading"></div>';
			}

			var $modal = UM.modal.getTemplate(options);
			$modal.on('touchmove', UM.modal.stopEvent);
			$modal.find('.um-modal-body').append(content);

			if ( typeof options.load === 'string' && options.load ) {
				$modal.find('.um-modal-body').load(options.load, function () {
					UM.modal.responsive($modal);
				});
			}

			var $photo = $modal.find('.um-photo img');
			if ( $photo.length ) {
				$photo.on('load', function () {
					UM.modal.responsive($modal);
				});
			}

			var $imageUploader = $modal.find('.um-single-image-upload');
			if ( $imageUploader.length ) {
				initImageUpload_UM($imageUploader);
			}

			var $fileUploader = $modal.find('.um-single-file-upload');
			if ( $fileUploader.length ) {
				initFileUpload_UM($fileUploader);
			}

			if ( typeof $.fn.tipsy === 'function' ) {
				if ( typeof init_tipsy === 'function' ) {
					init_tipsy();
				} else {
					jQuery('.um-tip-n').tipsy({gravity: 'n', opacity: 1, offset: 3});
					jQuery('.um-tip-w').tipsy({gravity: 'w', opacity: 1, offset: 3});
					jQuery('.um-tip-e').tipsy({gravity: 'e', opacity: 1, offset: 3});
					jQuery('.um-tip-s').tipsy({gravity: 's', opacity: 1, offset: 3});
				}
			}

			UM.modal.hide();
			UM.modal.all.push($modal);
			UM.modal.show($modal, options.duration);

			return $modal;
		},

		/**
		 * Add and display a modal overlay
		 * @returns {object}                 A modal overlay jQuery object.
		 */
		addOverlay: function () {
			if ( $('body > .um-modal-overlay').length < 1 ) {
				$(document.body).addClass('um-overflow-hidden')
								.append('<div class="um-modal-overlay"></div>')
								.on('touchmove', UM.modal.stopEvent);
			}
			return $('body > .um-modal-overlay');
		},

		/**
		 * Remove all modals and overlay
		 */
		clear: function () {
			UM.modal.all = [];
			$(document.body)
							.removeClass('um-overflow-hidden')
							.off('touchmove')
							.children('.um-modal-overlay, .um-modal').remove();

			if ( $(document.body).css('overflow-y') === 'hidden' ) {
				$(document.body).css('overflow-y', 'visible');
			}
		},

		/**
		 * Close current modal
		 */
		close: function () {
			var $modal = UM.modal.getModal();

			// Save and close tinyMCE editor if exists.
			if ( $modal ) {
				var $editor = $modal.find('div.um-admin-editor:visible');
				if ( $editor.length > 0 ) {
					if ( typeof tinyMCE === 'object' ) {
						tinyMCE.triggerSave();
					}
					if ( typeof $um_tiny_editor === 'object' ) {
						$('div.um_tiny_placeholder:empty').replaceWith($um_tiny_editor.html());
					}
					$editor.find('#wp-um_editor_edit-wrap').remove();
				}
			}

			if ( UM.modal.all.length > 1 ) {
				UM.modal.all.pop().remove();
				UM.modal.addOverlay().after(UM.modal.getModal());
			} else {
				UM.modal.clear();
			}
		},

		/**
		 * Get current modal
		 * @param   {object} modal  A modal element. Optional.
		 * @returns {object|null}   A modal jQuery object or NULL.
		 */
		getModal: function (modal) {
			var $modal;

			if ( typeof modal === 'object' ) {
				$modal = $(modal);
			} else if ( typeof modal === 'string' && UM.modal.all.length >= 1 ) {
				$.each(UM.modal.all, function (i, $m) {
					if ( $m.is(modal) ) {
						$modal = $m;
					}
				});
			} else if ( UM.modal.all.length >= 1 ) {
				$modal = UM.modal.all[UM.modal.all.length - 1];
			} else {
				$modal = $('div.um-modal:not(.um-modal-hidden)').filter(':visible');
			}

			return $modal.length ? $modal.last() : null;
		},

		/**
		 * Filter modal options
		 * @param   {Object} options  Modal options.
		 * @returns {Object}          Modal options.
		 */
		getOptions: function (options) {
			var defOptions = wp.hooks.applyFilters('modalDefOptions', {
				id: null,
				attr: {},
				class: 'large', // small, normal, large
				closeButton: false,
				duration: 400,
				header: '',
				buttons: [],
				type: 'body', // body, photo, popup
				load: null
			});

			return $.extend(defOptions, options || {});
		},

		/**
		 * Build a template for a new modal
		 * @param   {Object} options  Modal options.
		 * @returns {object}          A modal template jQuery object.
		 */
		getTemplate: function (options) {
			options = UM.modal.getOptions(options);

			var tpl = '<div class="um-modal ' + options.class + '"';
			if ( options.id ) {
				tpl += ' id="' + options.id + '"';
			}
			for ( var ak in options.attr ) {
				tpl += ' ' + ak + '="' + options.attr[ak] + '"';
			}
			tpl += '>';

			if ( options.closeButton ) {
				tpl += '<span data-action="um_remove_modal" class="um-modal-close" aria-label="Close view photo modal"><i class="um-faicon-times"></i></span>';
			}

			tpl += options.header ? '<div class="um-modal-header">' + options.header + '</div>' : '';

			switch ( options.type ) {
				default:
				case 'body':
					tpl += '<div class="um-modal-body"></div>';
					break;
				case 'photo':
					tpl += '<div class="um-modal-body um-photo"></div>';
					break;
				case 'popup':
					tpl += '<div class="um-modal-body um-popup"></div>';
					break;
			}

			if ( options.buttons.length ) {
				tpl += '<div class="um-modal-footer">';
				$.each(options.buttons, function (i, el) {
					tpl += $(el).addClass('.um-modal-btn').html();
				});
				tpl += '</div>';
			}

			tpl += '</div>';
			return $(tpl);
		},

		/**
		 * Add a modal based on button attributes.
		 * @param   {object} e  jQuery.Event
		 * @returns {object}    A modal jQuery object.
		 */
		open: function (e) {
			var $button = $(e.currentTarget), content, options = {};

			if ( $button.is('[data-modal-size]') ) {
				options.class = $button.attr('data-modal-size');
			}

			if ( $button.is('[data-modal-header]') ) {
				options.header = $button.attr('data-modal-header');
			}

			if ( $button.is('[data-modal-content]') ) {
				content = $button.data('modal-content');
				if ( UM.modal.isValidHttpUrl(content) ) {
					options.load = content;
				} else {
					content = $(content).html();
				}
				return UM.modal.addModal(content, options);
			}
		},

		/**
		 * Hide current modal
		 * @param   {object} modal  A modal element. Optional.
		 * @returns {object|null}  Hidden modal if exists.
		 */
		hide: function (modal) {
			var $modal = UM.modal.getModal(modal);
			if ( $modal ) {
				$modal.detach();
				return $modal;
			}
		},

		/**
		 * Show current modal
		 * @param   {object} modal  A modal element. Optional.
		 * @param   {int} duration  A number determining how long the animation will run.
		 * @returns {object|null}   Shown modal if exists.
		 */
		show: function (modal, duration) {
			var $modal = UM.modal.getModal(modal);
			if ( $modal ) {
				UM.modal.addOverlay().after($modal);
				UM.modal.responsive($modal);
				$modal.animate({opacity: 1}, duration || 10);
				return $modal;
			}
		},

		/**
		 * Return TRUE if this string is an URL
		 * @param   {String}  string
		 * @returns {Boolean}
		 */
		isValidHttpUrl: function (string) {
			let url;

			try {
				url = new URL(string);
			} catch ( _ ) {
				return false;
			}

			return url.protocol === "http:" || url.protocol === "https:";
		},

		/**
		 * Build a new modal with a pattern.
		 * @param   {string}  id     The id of the element used as a pattern for the modal content.
		 * @param   {string}  size   The class of the modal size: small, normal, large.
		 * @param   {boolean} ajax   Show loading icon and load the body of the modal by AJAX if true.
		 * @param   {string}  image  The image src in the image popup.
		 * @param   {boolean} admin  Is it the admin modal?
		 * @returns {object}         A modal jQuery object.
		 */
		newModal: function (id, size, ajax, image, admin) {

			UM.modal.hide();
			UM.dropdown.hideAll();
			$('.tipsy').hide();

			var template = $('#' + id), content, options = {attr: {}, class: '', id: id};

			// prepare content
			if ( image ) {
				content = '<img src="' + image + '" />';
			} else if ( template.find('.um-modal-body').length ) {
				content = template.find('.um-modal-body').children();
			} else {
				content = template.clone().children();
			}

			// prepare options
			if ( size ) {
				options.class = size;
			}
			if ( admin ) {
				options.class += ' um-admin-modal';
				options.closeButton = true;
			}
			if ( ajax === true && !image ) {
				options.class += ' loading';
				options.type = 'popup';
			}
			if ( image ) {
				options.class += ' is-photo';
				options.closeButton = true;
				options.type = 'photo';
			}

			if ( template.is('[data-user_id]') ) {
				options.attr['data-user_id'] = template.attr('data-user_id');
			} else if ( template.find('[data-user_id]').length ) {
				options.attr['data-user_id'] = template.find('[data-user_id]').attr('data-user_id');
			}
			if ( template.find('.um-modal-header').length ) {
				options.header = template.find('.um-modal-header').text().trim();
			}

			// add modal
			return UM.modal.addModal(content, options);
		},

		/**
		 * Update modal size and position
		 * @param   {object} modal  A modal element. Optional.
		 */
		responsive: function (modal) {
			var $modal = UM.modal.getModal(modal);
			if ( !$modal ) {
				return;
			}

			$modal.removeClass('uimob340').removeClass('uimob500');

			var w = window.innerWidth
							|| document.documentElement.clientWidth
							|| document.body.clientWidth;

			var h = window.innerHeight
							|| document.documentElement.clientHeight
							|| document.body.clientHeight;

			var $photo = $('.um-modal-body.um-photo img:visible'), half_gap;

			if ( $photo.length ) {

				$photo.css({
					'max-height': h * 0.8,
					'max-width': w * 0.8
				});

				$modal.css({
					'width': $photo.width(),
					'margin-left': '-' + $photo.width() / 2 + 'px'
				});

				half_gap = (h - $modal.innerHeight()) / 2 + 'px';

			} else {
				if ( w <= 340 ) {
					half_gap = 0;
					$modal.addClass('uimob340');
				} else if ( w <= 500 ) {
					half_gap = 0;
					$modal.addClass('uimob500');
				} else if ( w <= 800 ) {
					half_gap = (h - $modal.innerHeight()) / 2 + 'px';
				} else if ( w <= 960 ) {
					half_gap = (h - $modal.innerHeight()) / 2 + 'px';
				} else if ( w > 960 ) {
					half_gap = (h - $modal.innerHeight()) / 2 + 'px';
				}
				initCrop_UM();
			}

			$modal.css({
				bottom: half_gap,
				maxHeight: h
			});
		},

		/**
		 * Update a modal content
		 * @param   {string} content  A new content
		 * @param   {object} modal    A modal element. Optional.
		 */
		setContent: function (content, modal) {
			var $modal = UM.modal.getModal(modal);
			$modal.find('.um-modal-body').html(content);
			UM.modal.responsive($modal);
		},

		/**
		 * Stop event propagation
		 * @param {object} e  jQuery.Event
		 */
		stopEvent: function (e) {
			e.preventDefault();
			e.stopPropagation();
		}
	};


	/* event handlers */
	$(document.body)
					.on('click', '[data-modal-content]', UM.modal.open)
					.on('click', '.um-modal-overlay, [data-action="um_remove_modal"]', UM.modal.close);


	/* integration with jQuery */
	$.fn.umModal = function (options) {
		UM.modal.addModal(this.clone(), options);
	};
})(jQuery);