(function ($) {

	if ( typeof window.UM === 'undefined' ) {
		window.UM = {};
	}

	window.UM.modal = {

		addModal: function (content, options) {
			options = UM.modal.getOptions( options );

			window.UM.modal.addOverlay();

			var $modal = window.UM.modal.getTemplate( options );
			$modal.find( '.um-modal-body' ).append( content );
			$modal.css( {
				display: 'block',
				opacity: 0
			} ).animate( {opacity: 1}, options.duration ).on( 'touchmove', function (e) {
				e.stopPropagation();
			} );

			$( document.body ).append( $modal );

			if ( typeof options.load === 'string' && options.load ) {
				$modal.find( '.um-modal-body' ).load( options.load );
			}

			return $modal;
		},
		addOverlay: function () {
			if ( $( 'body > .um-modal-overlay' ).length < 1 ) {
				$( document.body ).addClass( 'um-modal-opened' ).on( "touchmove", function (e) {
					e.preventDefault();
				} ).append( '<div class="um-modal-overlay"></div>' );
			}
		},
		clear: function (modal) {
			var $modal = UM.modal.getModal( modal );
			if ( !$modal ) {
				return;
			}
			
			if ( typeof $.fn.cropper === 'function' ) {
				$modal.find( 'img.cropper-hidden' ).cropper( 'destroy' );
			}

			$('.tipsy').remove();
			$modal.find( 'div[id^="um_"]' ).hide().appendTo( 'body' );

			$( document.body ).removeClass( 'um-modal-opened' ).off( 'touchmove' ).children( '.um-modal-overlay, .um-modal' ).remove();

			if ( $( document.body ).css( 'overflow-y' ) === 'hidden' ) {
				$( document.body ).css( 'overflow-y', 'auto' );
			}
		},
		getModal: function (modal) {
			var $modal = typeof modal === 'undefined' ? $( '.um-modal:visible' ).not( '.um-modal-hidden' ) : $( modal );
			return $modal.length ? $modal : null;
		},
		getOptions: function (options) {
			var defOptions = wp.hooks.applyFilters( 'modalDefOptions', {
				id: null,
				attr: {},
				class: 'large', // normal, large
				duration: 400,
				header: '',
				buttons: [],
				type: 'body',
				load: null
			} );

			return $.extend( defOptions, options || {} );
		},
		getTemplate: function (options) {
			options = UM.modal.getOptions( options );

			var tpl = '<div class="um-modal ' + options.class + '"';
			if ( options.id ) {
				tpl += ' id="' + options.id + '"';
			}
			if ( typeof options.attr === 'object' ) {
				for ( var ak in options.attr ) {
					tpl += ' ' + ak + '="' + options.attr[ak] + '"';
				}
			}
			tpl += '>';

			tpl += options.header ? '<div class="um-modal-header">' + options.header + '</div>' : '';

			switch ( options.type ) {
				default:
				case 'body':
					tpl += '<div class="um-modal-body"></div>';
					break;
				case 'photo':
					tpl += '<div class="um-modal-body photo"></div>';
					break;
				case 'popup':
					tpl += '<div class="um-modal-body popup"></div>';
					break;
			}

			if ( options.buttons.length ) {
				tpl += '<div class="um-modal-footer">';
				$.each( options.buttons, function (i, el) {
					if ( typeof el.href === 'undefined' || !el.href ) {
						tpl += '<button class="' + el.class + '" ' + el.attr + '>' + el.title + '</button>';
					} else {
						tpl += '<a href="' + el.href + '" class="' + el.class + '" ' + el.attr + '>' + el.title + '</a>';
					}
				} );
				tpl += '</div>';
			}

			tpl += '</div>';
			return $( tpl );
		},
		handler: {
			open: function (e) {
				var $button = $( e.currentTarget ), content, options = {};

				if ( $button.is( '[data-modal-size]' ) ) {
					options.class = $button.attr( 'data-modal-size' );
				}

				if ( $button.is( '[data-modal-header]' ) ) {
					options.header = $button.attr( 'data-modal-header' );
				}

				if ( $button.is( '[data-modal-content]' ) ) {
					content = $( $button.attr( 'data-modal-content' ) ).html();
				}

				if ( $button.is( '[data-modal-content-load]' ) ) {
					content = '<div class="um-ajax-loading"></div>';
					options.load = $button.attr( 'data-modal-content-load' );
				}

				window.UM.modal.addModal( content, options );
			}
		},
		newModal: function (id, size, isPhoto, source) {

			UM.modal.clear();
			UM.dropdown.hideAll();

			var template = $( '#' + id ), content, options = {attr: {}, class: ''};

			// prepare content
			if ( template.find( '.um-modal-body' ).length ) {
				content = template.find( '.um-modal-body' ).children();
			} else if ( template.find( '.um-modal-photo' ).length ) {
				content = template.find( '.um-modal-photo' ).children();
			} else {
				content = template;
			}

			// prepare options
			if ( size ) {
				options.class = size;
			}
			if ( isPhoto ) {
				options.class += ' is-photo';
				options.type = 'photo';
			} else {
				options.class += ' no-photo';
			}
			if ( template.is( '[id]' ) ) {
				options.id = template.attr( 'id' );
			}
			if ( template.is( '[data-user_id]' ) ) {
				options.attr['data-user_id'] = template.attr( 'data-user_id' );
			}
			if ( template.find( '.um-modal-header' ).length ) {
				options.header = template.find( '.um-modal-header' ).text().trim();
			}

			// add modal
			var $modal = UM.modal.addModal( content, options );

			// resize modal
			if ( isPhoto ) {
				var $photo = $( '<img src="' + source + '" />' );

				$photo.on( 'load', function () {
					$modal.find( '.photo' ).append( $photo );
					UM.modal.responsive($modal);
				} );

			} else {

				initImageUpload_UM( $modal.find( '.um-single-image-upload' ) );
				initFileUpload_UM( $modal.find( '.um-single-file-upload' ) );
				UM.modal.responsive($modal);

			}
		},
		responsive: function (modal) {
			var $modal = UM.modal.getModal( modal );
			if ( !$modal ) {
				return;
			}
			$modal.removeClass( 'uimob340' ).removeClass( 'uimob500' );

			var w = window.innerWidth
					|| document.documentElement.clientWidth
					|| document.body.clientWidth;

			var h = window.innerHeight
					|| document.documentElement.clientHeight
					|| document.body.clientHeight;

			var half_gap = (h - $modal.innerHeight()) / 2 + 'px';
			var photo_modal = $modal.find( '.um-modal-photo:visible' );

			if ( photo_modal.length ) {
				var $photo = $( '.um-modal-photo img' ), photo_maxw = w * 0.8, photo_maxh = h * 0.8;

				$photo.css( {
					'max-height': photo_maxh,
					'max-width': photo_maxw
				} );

				$modal.css( {
					'width': $photo.width(),
					'margin-left': '-' + $photo.width() / 2 + 'px'
				} );

			} else {
				if ( w <= 340 ) {
					half_gap = 0;
					$modal.addClass( 'uimob340' );
				} else if ( w <= 500 ) {
					half_gap = 0;
					$modal.addClass( 'uimob500' );
				} else if ( w <= 800 ) {
				} else if ( w <= 960 ) {
				} else if ( w > 960 ) {
				}
				initCrop_UM();
			}

			$modal.css( {
				bottom: half_gap,
				maxHeight: h
			} );
		},
		tipsy: function(){
			if ( typeof $.fn.tipsy === 'function' ) {
				jQuery('.um-tip-n').tipsy({gravity: 'n', opacity: 1, offset: 3 });
				jQuery('.um-tip-w').tipsy({gravity: 'w', opacity: 1, offset: 3 });
				jQuery('.um-tip-e').tipsy({gravity: 'e', opacity: 1, offset: 3 });
				jQuery('.um-tip-s').tipsy({gravity: 's', opacity: 1, offset: 3 });
			}
		}
	};


	// event handlers
	$( function () {
		$( document.body ).on( 'click', '.um-modal-open', UM.modal.handler.open );
	} );

	// integration with jQuery
	$.fn.umModal = function (options) {
		window.UM.modal.addModal( this, options );
	};
})( jQuery );