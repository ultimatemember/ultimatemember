(function ($) {

	if ( typeof window.UM === 'undefined' ) {
		window.UM = {};
	}

	window.UM.modal = {
		getTemplate: function (options) {
			options = $.extend( {
				'id': null,
				'class': 'large', // normal, large
				'header': '',
				'buttons': [
//					{
//						'title': 'Cancel',
//						'class': 'um-modal-btn',
//						'href': 'javascript:void(0);',
//						'attr': 'data-action="um_remove_modal"'
//					}
				]
			}, options || {} );

			var tpl = '';
			tpl += '<div class="um-modal ' + options.class + '" ' + (options.id ? 'id="' + options.id + '"' : '') + '>';
			tpl += options.header ? '<div class="um-modal-header">' + options.header + '</div>' : '';
			tpl += '<div class="um-modal-body"></div>';
			tpl += '<div class="um-modal-footer">';

			if ( options.buttons.length ) {
				$.each( options.buttons, function (i, el) {
					if ( typeof el.href === 'undefined' || !el.href ) {
						tpl += '<button class="' + el.class + '" ' + el.attr + '>' + el.title + '</button>';
					} else {
						tpl += '<a href="' + el.href + '" class="' + el.class + '" ' + el.attr + '>' + el.title + '</a>';
					}
				} );
			}

			tpl += '</div>';
			tpl += '</div>';
			return $( tpl );
		},
		showModal: function (content, options) {
			var modal = window.UM.modal.getTemplate( options );
			modal.find( '.um-modal-body' ).append( content );
			$( document.body ).append( modal );
			$( 'body > .um-modal' ).show();
			if ( typeof options.load === 'string' && options.load ) {
				$( 'body > .um-modal .um-modal-body' ).load( options.load );
			}
		},
		showOverlay: function () {
			if ( $( 'body > .um-modal-overlay' ).length < 1 ) {
				$( document.body ).addClass('um-modal-opened').append( '<div class="um-modal-overlay"></div>' );
			}
		}
	};

	$( function () {
		$( document.body ).on( 'click', '.um-modal-open', function (e) {
			var $button = $( e.currentTarget ),
			content = '',
			options = {};

			if ( $button.is( '[data-modal-content]' ) ) {
				content = $( $button.attr( 'data-modal-content' ) ).html();
			}
			
			if ( $button.is( '[data-modal-content-load]' ) ) {
				content = '<div class="um-ajax-loading"></div>';
				options.load = $button.attr( 'data-modal-content-load' );
			}

			if ( $button.is( '[data-modal-header]' ) ) {
				options.header = $button.attr( 'data-modal-header' );
			}

			if ( $button.is( '[data-modal-size]' ) ) {
				options.class = $button.attr( 'data-modal-size' );
			}

			window.UM.modal.showOverlay();
			window.UM.modal.showModal( content, options );

		} );
	} );

	$.fn.umModal = function (options) {
		window.UM.modal.showOverlay();
		window.UM.modal.showModal( this, options );
	};
})( jQuery );