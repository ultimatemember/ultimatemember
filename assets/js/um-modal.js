/**
 * Ultimate Member modal script
 */


(function ($) {

	if ( typeof window.UM === 'undefined' ) {
		window.UM = {};
	}

	UM.modal = {

		addModal: function (content, options) {
			options = UM.modal.getOptions( options );

			if ( content === 'loading' ) {
				content = '<div class="loading"></div>';
			}
			if ( UM.modal.isValidHttpUrl( content ) ) {
				content = '<div class="loading"></div>';
				options.load = content;
			}

			var $modal = UM.modal.getTemplate( options );
			$modal.on( 'touchmove', UM.modal.handler.stopEvent )
					.find( '.um-modal-body' ).append( content );

			UM.modal.addOverlay();
			$( document.body ).append( $modal );
			UM.modal.initTipsy();

			if ( typeof options.load === 'string' && options.load ) {
				$modal.find( '.um-modal-body' ).load( options.load );
			}

			$modal.animate( {opacity: 1}, options.duration );
			return $modal;
		},

		addOverlay: function () {
			if ( $( 'body > .um-modal-overlay' ).length < 1 ) {
				$( document.body ).addClass( 'um-overflow-hidden' )
						.append( '<div class="um-modal-overlay"></div>' )
						.on( 'touchmove', UM.modal.handler.stopEvent );
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

			$modal.find( '.tipsy' ).remove();
			$modal.find( 'div[id^="um_"]' ).hide().appendTo( 'body' );

			$( document.body ).removeClass( 'um-overflow-hidden' )
					.off( 'touchmove' )
					.children( '.um-modal-overlay, .um-modal' ).remove();

			if ( $( document.body ).css( 'overflow-y' ) === 'hidden' ) {
				$( document.body ).css( 'overflow-y', 'visible' );
			}
		},

		getModal: function (modal) {
			var $modal = typeof modal === 'undefined' ? $( '.um-modal:visible' ).not( '.um-modal-hidden' ) : $( modal );
			return $modal.length ? $modal.last() : null;
		},

		getOptions: function (options) {
			var defOptions = wp.hooks.applyFilters( 'modalDefOptions', {
				id: null,
				attr: {},
				class: 'large', // small, normal, large
				closeButton: false,
				duration: 400,
				header: '',
				buttons: [],
				type: 'body', // body, photo, popup
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
					content = $button.data( 'modal-content' );
					if ( UM.modal.isValidHttpUrl( content ) ) {
						options.load = content;
					} else {
						content = $( content ).html();
					}
				} else {
					return;
				}

				UM.modal.addModal( content, options );
			},
			stopEvent: function (e) {
				e.preventDefault();
				e.stopPropagation();
			}
		},

		initTipsy: function () {
			if ( typeof $.fn.tipsy === 'function' ) {
				jQuery( '.um-tip-n' ).tipsy( {gravity: 'n', opacity: 1, offset: 3} );
				jQuery( '.um-tip-w' ).tipsy( {gravity: 'w', opacity: 1, offset: 3} );
				jQuery( '.um-tip-e' ).tipsy( {gravity: 'e', opacity: 1, offset: 3} );
				jQuery( '.um-tip-s' ).tipsy( {gravity: 's', opacity: 1, offset: 3} );
			}
		},

		isValidHttpUrl: function (string) {
			let url;

			try {
				url = new URL( string );
			} catch (_) {
				return false;
			}

			return url.protocol === "http:" || url.protocol === "https:";
		},

		newModal: function (id, size, isPhoto, source) {

			UM.modal.clear();
			UM.dropdown.hideAll();

			var template = $( '#' + id ), content, options = {attr: {}, class: ''};

			// prepare content
			if ( template.find( '.um-modal-body' ).length ) {
				content = template.find( '.um-modal-body' ).children();
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
			if ( template.is( '[id]' ) ) {
				options.id = template.attr( 'id' );
			}
			if ( template.is( '[data-user_id]' ) ) {
				options.attr['data-user_id'] = template.attr( 'data-user_id' );
			} else if ( template.find( '[data-user_id]' ).length ) {
				options.attr['data-user_id'] = template.find( '[data-user_id]' ).attr( 'data-user_id' );
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
					$modal.find( '.um-photo' ).append( $photo );
					UM.modal.responsive( $modal );
				} );

			} else {

				initImageUpload_UM( $modal.find( '.um-single-image-upload' ) );
				initFileUpload_UM( $modal.find( '.um-single-file-upload' ) );
				UM.modal.responsive( $modal );

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

			var $photo = $( '.um-modal-body.um-photo img:visible' ), half_gap;

			if ( $photo.length ) {

				$photo.css( {
					'max-height': h * 0.8,
					'max-width': w * 0.8
				} );

				$modal.css( {
					'width': $photo.width(),
					'margin-left': '-' + $photo.width() / 2 + 'px'
				} );

				half_gap = (h - $modal.innerHeight()) / 2 + 'px';

			} else {
				if ( w <= 340 ) {
					half_gap = 0;
					$modal.addClass( 'uimob340' );
				} else if ( w <= 500 ) {
					half_gap = 0;
					$modal.addClass( 'uimob500' );
				} else if ( w <= 800 ) {
					half_gap = (h - $modal.innerHeight()) / 2 + 'px';
				} else if ( w <= 960 ) {
					half_gap = (h - $modal.innerHeight()) / 2 + 'px';
				} else if ( w > 960 ) {
					half_gap = (h - $modal.innerHeight()) / 2 + 'px';
				}
				initCrop_UM();
			}

			$modal.css( {
				bottom: half_gap,
				maxHeight: h
			} );
		},

		setContent: function (content, modal) {
			var $modal = UM.modal.getModal( modal );
			$modal.find( '.um-modal-body' ).html( content );
			UM.modal.responsive( $modal );
		}

	};


	// event handlers
	$( function () {
		$( document.body )
				.on( 'click', '[data-modal-content]', UM.modal.handler.open )
				.on( 'click', '.um-modal-overlay, [data-action="um_remove_modal"]', UM.modal.clear );
	} );


	// integration with jQuery
	$.fn.umModal = function (options) {
		UM.modal.addModal( this, options );
	};
})( jQuery );


jQuery( document ).ready( function () {

	jQuery( document ).on( 'click', '.um-modal .um-single-file-preview a.cancel', function (e) {
		e.preventDefault();

		var parent = jQuery( this ).parents( '.um-modal-body' );
		var src = jQuery( this ).parents( '.um-modal-body' ).find( '.um-single-fileinfo a' ).attr( 'href' );
		var mode = parent.find( '.um-single-file-upload' ).data( 'set_mode' );

		jQuery.ajax( {
			url: wp.ajax.settings.url,
			type: 'post',
			data: {
				action: 'um_remove_file',
				src: src,
				mode: mode,
				nonce: um_scripts.nonce
			},
			success: function () {
				parent.find( '.um-single-file-preview' ).hide();
				parent.find( '.ajax-upload-dragdrop' ).show();
				parent.find( '.um-modal-btn.um-finish-upload' ).addClass( 'disabled' );
				UM.modal.responsive();
			}
		} );

		return false;
	} );

	jQuery( document ).on( 'click', '.um-modal .um-single-image-preview a.cancel', function (e) {
		e.preventDefault();

		var parent = jQuery( this ).parents( '.um-modal-body' );
		var src = jQuery( this ).parents( '.um-modal-body' ).find( '.um-single-image-preview img' ).attr( 'src' );
		var mode = parent.find( '.um-single-image-upload' ).data( 'set_mode' );

		jQuery.ajax( {
			url: wp.ajax.settings.url,
			type: 'post',
			data: {
				action: 'um_remove_file',
				src: src,
				mode: mode,
				nonce: um_scripts.nonce
			},
			success: function () {
				jQuery( 'img.cropper-hidden' ).cropper( 'destroy' );
				parent.find( '.um-single-image-preview img' ).attr( 'src', '' );
				parent.find( '.um-single-image-preview' ).hide();
				parent.find( '.ajax-upload-dragdrop' ).show();
				parent.find( '.um-modal-btn.um-finish-upload' ).addClass( 'disabled' );
				UM.modal.responsive();
			}
		} );

		return false;
	} );

	jQuery( document ).on( 'click', '.um-finish-upload.file:not(.disabled)', function () {

		var key = jQuery( this ).attr( 'data-key' );

		var preview = jQuery( this ).parents( '.um-modal-body' ).find( '.um-single-file-preview' ).html();

		UM.modal.clear();

		jQuery( '.um-single-file-preview[data-key=' + key + ']' ).fadeIn().html( preview );

		var file = jQuery( '.um-field[data-key=' + key + ']' ).find( '.um-single-fileinfo a' ).data( 'file' );

		jQuery( '.um-single-file-preview[data-key=' + key + ']' ).parents( '.um-field' ).find( '.um-btn-auto-width' ).html( jQuery( this ).attr( 'data-change' ) );

		jQuery( '.um-single-file-preview[data-key=' + key + ']' ).parents( '.um-field' ).find( 'input[type="hidden"]' ).val( file );

	} );

	jQuery( document ).on( 'click', '.um-finish-upload.image:not(.disabled)', function () {

		var elem = jQuery( this );
		var key = jQuery( this ).attr( 'data-key' );
		var img_c = jQuery( this ).parents( '.um-modal-body' ).find( '.um-single-image-preview' );
		var src = img_c.find( 'img' ).attr( 'src' );
		var coord = img_c.attr( 'data-coord' );
		var file = img_c.find( 'img' ).data( 'file' );
		var user_id = 0;
		if ( jQuery( this ).parents( '#um_upload_single' ).data( 'user_id' ) ) {
			user_id = jQuery( this ).parents( '#um_upload_single' ).data( 'user_id' );
		}

		var form_id = 0;
		var mode = '';
		if ( jQuery( 'div.um-field-image[data-key="' + key + '"]' ).length === 1 ) {
			var $formWrapper = jQuery( 'div.um-field-image[data-key="' + key + '"]' ).closest( '.um-form' );
			form_id = $formWrapper.find( 'input[name="form_id"]' ).val();
			mode = $formWrapper.attr( 'data-mode' );
		}

		if ( coord ) {

			jQuery( this ).html( jQuery( this ).attr( 'data-processing' ) ).addClass( 'disabled' );

			jQuery.ajax( {
				url: wp.ajax.settings.url,
				type: 'POST',
				dataType: 'json',
				data: {
					action: 'um_resize_image',
					src: src,
					coord: coord,
					user_id: user_id,
					key: key,
					set_id: form_id,
					set_mode: mode,
					nonce: um_scripts.nonce
				},
				success: function (response) {

					if ( response.success ) {

						d = new Date();

						if ( key === 'profile_photo' ) {
							jQuery( '.um-profile-photo-img img' ).attr( 'src', response.data.image.source_url + "?" + d.getTime() );
						} else if ( key === 'cover_photo' ) {
							jQuery( '.um-cover-e' ).empty().html( '<img src="' + response.data.image.source_url + "?" + d.getTime() + '" alt="" />' );
							if ( jQuery( '.um' ).hasClass( 'um-editing' ) ) {
								jQuery( '.um-cover-overlay' ).show();
							}
						}

						jQuery( '.um-single-image-preview[data-key=' + key + ']' ).fadeIn().find( 'img' ).attr( 'src', response.data.image.source_url + "?" + d.getTime() );

						UM.modal.clear();

						jQuery( 'img.cropper-invisible' ).remove();

						jQuery( '.um-single-image-preview[data-key=' + key + ']' ).parents( '.um-field' ).find( '.um-btn-auto-width' ).html( elem.attr( 'data-change' ) );

						jQuery( '.um-single-image-preview[data-key=' + key + ']' ).parents( '.um-field' ).find( 'input[type="hidden"]' ).val( response.data.image.filename );
					}

				}
			} );

		} else {

			d = new Date();

			jQuery( '.um-single-image-preview[data-key=' + key + ']' ).fadeIn().find( 'img' ).attr( 'src', src + "?" + d.getTime() );

			UM.modal.clear();

			jQuery( '.um-single-image-preview[data-key=' + key + ']' ).parents( '.um-field' ).find( '.um-btn-auto-width' ).html( elem.attr( 'data-change' ) );

			jQuery( '.um-single-image-preview[data-key=' + key + ']' ).parents( '.um-field' ).find( 'input[type=hidden]' ).val( file );


		}
	} );

	jQuery( document.body ).on( 'click', 'a[data-modal^="um_"], span[data-modal^="um_"]', function (e) {
		e.preventDefault();

		var modal_id = jQuery( this ).attr( 'data-modal' ), size = 'normal';

		if ( jQuery( this ).data( 'modal-size' ) ) {
			size = jQuery( this ).data( 'modal-size' );
		}

		if ( jQuery( this ).data( 'modal-copy' ) ) {

			jQuery( '#' + modal_id ).html( jQuery( this ).parents( '.um-field' ).find( '.um-modal-hidden-content' ).html() );

			if ( jQuery( this ).parents( '.um-profile-photo' ).attr( 'data-user_id' ) ) {
				jQuery( '#' + modal_id ).attr( 'data-user_id', jQuery( this ).parents( '.um-profile-photo' ).attr( 'data-user_id' ) );
			}

			if ( jQuery( this ).parents( '.um-cover' ).attr( 'data-ratio' ) ) {
				jQuery( '#' + modal_id ).attr( 'data-ratio', jQuery( this ).parents( '.um-cover' ).attr( 'data-ratio' ) );
			}

			if ( jQuery( this ).parents( '.um-cover' ).attr( 'data-user_id' ) ) {
				jQuery( '#' + modal_id ).attr( 'data-user_id', jQuery( this ).parents( '.um-cover' ).attr( 'data-user_id' ) );
			}

			if ( jQuery( 'input[type="hidden"][name="user_id"]' ).length > 0 ) {
				jQuery( '#' + modal_id ).attr( 'data-user_id', jQuery( 'input[type="hidden"][name="user_id"]' ).val() );
			}

		}

		UM.modal.newModal( modal_id, size );
		return false;
	} );

} );