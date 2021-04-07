(function ($) {

	if ( typeof window.UM === 'undefined' ) {
		window.UM = {};
	}

	window.UM.modal = {
		filterOptions: function (options) {
			return $.extend({
				id: null,
				attr: {},
				class: 'large', // normal, large
				header: '',
				buttons: [],
				load: null
			}, options || {});
		},
		getTemplate: function (options) {
			options = UM.modal.filterOptions(options);

			var tpl = '<div class="um-modal ' + options.class + '"';
			if ( options.id ) {
				tpl += ' id="' + options.id + '"';
			}
			if ( typeof options.attr === 'object' ) {
				for( var ak in options.attr ){
					tpl += ' ' + ak + '="' + options.attr[ak] + '"';
				}
			}
			tpl += '>';

			tpl += options.header ? '<div class="um-modal-header">' + options.header + '</div>' : '';

			tpl += '<div class="um-modal-body"></div>';

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
				var $button = $(e.currentTarget),
								content = '',
								options = {};

				if ( $button.is('[data-modal-content]') ) {
					content = $($button.attr('data-modal-content')).html();
				}

				if ( $button.is('[data-modal-content-load]') ) {
					content = '<div class="um-ajax-loading"></div>';
					options.load = $button.attr('data-modal-content-load');
				}

				if ( $button.is('[data-modal-header]') ) {
					options.header = $button.attr('data-modal-header');
				}

				if ( $button.is('[data-modal-size]') ) {
					options.class = $button.attr('data-modal-size');
				}

				window.UM.modal.addModal(content, options);
			}
		},
		addModal: function (content, options) {
			options = UM.modal.filterOptions(options);

			window.UM.modal.clear();
			window.UM.modal.addOverlay();

			var modal = window.UM.modal.getTemplate(options);
			modal.find('.um-modal-body').append(content);
			$(document.body).append(modal);

			$('body > .um-modal').on('touchmove', function (e) {
				e.stopPropagation();
			}).show();
			if ( typeof options.load === 'string' && options.load ) {
				$('body > .um-modal .um-modal-body').load(options.load);
			}

			return modal;
		},
		addOverlay: function () {
			if ( $('body > .um-modal-overlay').length < 1 ) {
				$(document.body).addClass('um-modal-opened').bind("touchmove", function (e) {
					e.preventDefault();
				}).append('<div class="um-modal-overlay"></div>');
			}
		},
		clear: function () {
			if ( $('body > .um-modal-overlay').length > 0 ) {
				$(document.body).removeClass('um-modal-opened').children('.um-modal-overlay, .um-modal').remove();
			}
		}
	};


	// event handlers
	$(function () {
		$(document.body).on('click', '.um-modal-open', UM.modal.handler.open);
	});

	// integration with jQuery
	$.fn.umModal = function (options) {
		window.UM.modal.addModal(this, options);
	};
})(jQuery);