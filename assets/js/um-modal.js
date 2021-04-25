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
 * @function UM.modal.clear();                       Remove a modal
 *
 * @function UM.modal.responsive();                  Update modal size and position
 *
 * @function UM.modal.setContent( content );         Update a modal content
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
 *	id {string}         - The value of the attribute 'id'. Default null.
 *	attr {object}       - Additional attributes in a format key:value. Default {}.
 *	class {string}      - Class of size: small, normal, large. Default 'large'.
 *	closeButton {bool}  - Show close button or not. Default false.
 *	duration {int}      - The duration of the animation, ms. Default 400.
 *	header {string}     - Text in the modal header. No header if empty. Default ''.
 *	buttons {Array}     - Buttons in the modal footer. No footer if empty. Default [].
 *	type {string}       - The type of modal: body, photo, popup. Default 'body'.
 *	load {string}       - URL for the remote content to load with the jQuery.load() function. Default null.
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
 *		buttons: [{
 *			attr: 'alt="Reload"',
 *			class: 'um-modal-btn',
 *			href: location.href,
 *			title: 'Reload'
 *		}, {
 *			attr: 'data-action="um_remove_modal"',
 *			class: 'um-modal-btn alt',
 *			title: 'Cancel'
 *		}]
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
			$modal.on('touchmove', UM.modal.handler.stopEvent);
			$modal.find('.um-modal-body').append(content);

			UM.modal.addOverlay().after($modal);

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

			UM.modal.initTipsy();
			UM.modal.responsive($modal);
			$modal.animate({opacity: 1}, options.duration);
			return $modal;
		},

		addOverlay: function () {
			if ( $('body > .um-modal-overlay').length < 1 ) {
				$(document.body).addClass('um-overflow-hidden')
								.append('<div class="um-modal-overlay"></div>')
								.on('touchmove', UM.modal.handler.stopEvent);
			}
			return $('body > .um-modal-overlay');
		},

		clear: function (modal) {
			var $modal = UM.modal.getModal(modal);
			if ( !$modal ) {
				return;
			}

			if ( typeof $.fn.cropper === 'function' ) {
				$modal.find('img.cropper-hidden').cropper('destroy');
			}

			$modal.find('.tipsy').remove();
			$modal.find('div[id^="um_"]').hide().appendTo('body');

			$(document.body).removeClass('um-overflow-hidden')
							.off('touchmove')
							.children('.um-modal-overlay, .um-modal').remove();

			if ( $(document.body).css('overflow-y') === 'hidden' ) {
				$(document.body).css('overflow-y', 'visible');
			}
		},

		getModal: function (modal) {
			var $modal = typeof modal === 'undefined' ? $('.um-modal:visible').not('.um-modal-hidden') : $(modal);
			return $modal.length ? $modal.last() : null;
		},

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

			tpl += options.header ? '<div class="um-modal-header">' + options.header + '</div>' : '';

			if ( options.closeButton ) {
				tpl += '<span data-action="um_remove_modal" class="um-modal-close" aria-label="Close view photo modal"><i class="um-faicon-times"></i></span>';
			}

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
					if ( typeof el.href === 'undefined' || !el.href ) {
						tpl += '<button class="' + el.class + '" ' + el.attr + '>' + el.title + '</button>';
					} else {
						tpl += '<a href="' + el.href + '" class="' + el.class + '" ' + el.attr + '>' + el.title + '</a>';
					}
				});
				tpl += '</div>';
			}

			tpl += '</div>';
			return $(tpl);
		},

		handler: {
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
				} else {
					return;
				}

				UM.modal.addModal(content, options);
			},
			stopEvent: function (e) {
				e.preventDefault();
				e.stopPropagation();
			}
		},

		initTipsy: function () {
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
		},

		isValidHttpUrl: function (string) {
			let url;

			try {
				url = new URL(string);
			} catch ( _ ) {
				return false;
			}

			return url.protocol === "http:" || url.protocol === "https:";
		},

		newModal: function (id, size, isPhoto, source) {

			UM.modal.clear();
			UM.dropdown.hideAll();

			var template = $('#' + id), content, options = {attr: {}, class: ''};

			// prepare content
			if ( isPhoto && source ) {
				content = '<img src="' + source + '" />';
			} else if ( template.find('.um-modal-body').length ) {
				content = template.find('.um-modal-body').children();
			} else {
				content = template;
			}

			// prepare options
			if ( size ) {
				options.class = size;
			}
			if ( isPhoto ) {
				options.class += ' is-photo';
				options.closeButton = true;
				options.type = 'photo';
			} else {
				options.class += ' no-photo';
			}
			if ( template.is('[id]') ) {
				options.id = template.attr('id');
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

		setContent: function (content, modal) {
			var $modal = UM.modal.getModal(modal);
			$modal.find('.um-modal-body').html(content);
			UM.modal.responsive($modal);
		}

	};


	/* event handlers */
	$(document.body)
					.on('click', '[data-modal-content]', UM.modal.handler.open)
					.on('click', '.um-modal-overlay, [data-action="um_remove_modal"]', UM.modal.clear);


	/* integration with jQuery */
	$.fn.umModal = function (options) {
		UM.modal.addModal(this.clone(), options);
	};
})(jQuery);