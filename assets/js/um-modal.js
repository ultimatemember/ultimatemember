/*!
 * UM-Modal
 * @author	Ultimate Member
 * @since		UM 3.0
 * @version	1.0.0
 *
 * @link		https://docs.ultimatemember.com/
 * @link    https://docs.google.com/document/d/1Tczv8Z0nfeaM9IB930j6kjFjrmh5dCNmISO1wwocijw/
 */

(function ($) {

	/**
	 * The UM-Modal library
	 * @returns {ModalManagerUM}
	 */
	function ModalManagerUM() {

		/**
		 * An array of modals
		 */
		this.M = [];

		this.defaultTemplate = '<div class="um-modal"><span data-action="um_remove_modal" class="um-modal-close"><i class="um-faicon-times"></i></span><div class="um-modal-header"></div><div class="um-modal-body"></div><div class="um-modal-footer"></div></div>';

		this.loading = false;

	}
	ModalManagerUM.prototype = {

		constructor: ModalManagerUM,

		/**
		 * Add and display a modal
		 * @param   {string|object} content  A content of the modal body.
		 * @param   {object} options         Modal properties. Optional.
		 * @returns {object}                 A modal jQuery object.
		 */
		addModal: function (content, options, event) {
			options = this.getOptions( options );

			let $modal;
			if( options.template ){
				let template = wp.template( options.template );
				if( template ){
					$modal = $( template( options ) );
				}
			} else {
				$modal = $( this.defaultTemplate );
			}
			$modal.on( 'touchmove', this.stopEvent );


			let $modalBody = $modal.find( '.um-modal-body' );
			if ( content === 'loading' ) {
				this.loading = true;
			}else
			if ( this.isValidHttpUrl( content ) ) {
				this.loading = true;
				$modalBody.load( content, function () {
					$modalBody.removeClass( 'loading' );
					UM.modal.responsive( $modal );
				} );
			}else
			if ( typeof options.remoteContent === 'string' && options.remoteContent ) {
				this.loading = true;
				$modalBody.load( options.remoteContent, function () {
					$modalBody.removeClass( 'loading' );
					UM.modal.responsive( $modal );
				} );
			}else
			if ( typeof content === 'function' ) {
				let res = content.apply($modal, [event, options]);
				if( typeof res === 'object' && res instanceof jqXHR ){
					this.loading = true;
					wp.hooks.doAction( 'um-modal-before-ajax', $modal, options, jqXHR );

				} else {
					$modalBody.append( res );
					wp.hooks.doAction( 'um-modal-before-add', $modal, options );
				}

			}else{
				$modalBody.append(content);
				wp.hooks.doAction( 'um-modal-before-add', $modal, options );
			}

			if ( this.loading ) {
				$modalBody.addClass( 'loading' );
			} else {
				let $img = $modalBody.find( 'img' );
				if ( $img.length ) {
					$img.on( 'load', function(){
						UM.modal.responsive( $modal );
					} );
				}
			}

			this.hide();
			this.M.push( $modal );
			this.show( $modal, options.duration );

			return $modal;
		},

		/**
		 * Add and display a modal overlay
		 * @returns {object}                 A modal overlay jQuery object.
		 */
		addOverlay: function () {
			if ( $( 'body > .um-modal-overlay' ).length < 1 ) {
				$( document.body ).addClass( 'um-overflow-hidden' )
						.append( '<div class="um-modal-overlay"></div>' )
						.on( 'touchmove', this.stopEvent );
			}
			return $( 'body > .um-modal-overlay' );
		},

		/**
		 * Remove all modals and overlay
		 */
		clear: function () {
			this.M = [];
			$( document.body )
					.removeClass( 'um-overflow-hidden' )
					.off( 'touchmove' )
					.children( '.um-modal-overlay, .um-modal' ).remove();

			if ( $( document.body ).css( 'overflow-y' ) === 'hidden' ) {
				$( document.body ).css( 'overflow-y', 'visible' );
			}
		},

		/**
		 * Close current modal
		 */
		close: function () {
			let $modal = this.getModal();

			wp.hooks.doAction( 'um-modal-before-close', $modal );

			// Save and close tinyMCE editor if exists.
			if ( $modal ) {
				let $editor = $modal.find( 'div.um-admin-editor:visible' );
				if ( $editor.length > 0 ) {
					if ( typeof tinyMCE === 'object' ) {
						tinyMCE.triggerSave();
					}
					if ( typeof $um_tiny_editor === 'object' ) {
						$( 'div.um_tiny_placeholder:empty' ).replaceWith( $um_tiny_editor.html() );
					}
					$editor.find( '#wp-um_editor_edit-wrap' ).remove();
				}
			}

			if ( this.M.length > 1 ) {
				this.M.pop().remove();
				this.show();
			} else {
				this.clear();
			}
		},

		/**
		 * Get the current modal
		 * @param   {object} modal  A modal element. Optional.
		 * @returns {object|null}   A modal jQuery object or NULL.
		 */
		getModal: function (modal) {
			let $modal;

			if ( typeof modal === 'object' ) {
				$modal = $( modal );
			} else if ( typeof modal === 'string' && this.M.length >= 1 ) {
				$.each( this.M, function (i, $m) {
					if ( $m.is( modal ) ) {
						$modal = $m;
					}
				} );
			} else if ( this.M.length >= 1 ) {
				$modal = this.M[this.M.length - 1];
			} else {
				$modal = $( 'div.um-modal:not(.um-modal-hidden)' ).filter( ':visible' );
			}

			return $modal.length ? $modal.last() : null;
		},

		/**
		 * Filter modal options
		 * @param   {Object} options  Modal options.
		 * @returns {Object}          Modal options.
		 */
		getOptions: function (options) {

			/**
			 * UM Hook
			 * @name        um-modal-ajax
			 * @description Use this filter to modify default modal options.
			 * @example
			 *  wp.hooks.addFilter('um-modal-def-options', 'ultimatemember', function (defOptions) {
			 *    // your code here
			 *    return defOptions;
			 *  }, 10);
			 */
			let defOptions = wp.hooks.applyFilters( 'um-modal-def-options', {
				id: null,
				attr: {},
				class: 'large', // small, normal, large
				closeButton: false,
				duration: 400,
				header: '',
				footer: '',
				template: '',
				type: 'body', // body, photo, popup
				load: null
			} );

			return $.extend( defOptions, options || {} );
		},

		/**
		 * Hide current modal
		 * @param   {object} modal  A modal element. Optional.
		 * @returns {object|null}  Hidden modal if exists.
		 */
		hide: function (modal) {
			let $modal = this.getModal( modal );
			if ( $modal ) {
				$modal.detach();				
				wp.hooks.doAction( 'um-modal-hidden', $modal );
				return $modal;
			}
		},

		/**
		 * Finds whether a variable is an URL
		 * @param   {String}  string
		 * @returns {Boolean}
		 */
		isValidHttpUrl: function (string) {
			let url;

			try {
				url = new URL( string );
			} catch (_) {
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

			this.hide();
			UM.dropdown.hideAll();
			$( '.tipsy' ).hide();

			let template = $( '#' + id ), content, options = {attr: {}, class: '', id: id};

			// prepare content
			if ( image ) {
				content = '<img src="' + image + '" />';
			} else if ( template.find( '.um-modal-body' ).length ) {
				content = template.find( '.um-modal-body' ).children();
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

			if ( template.is( '[data-user_id]' ) ) {
				options.attr['data-user_id'] = template.attr( 'data-user_id' );
			} else if ( template.find( '[data-user_id]' ).length ) {
				options.attr['data-user_id'] = template.find( '[data-user_id]' ).attr( 'data-user_id' );
			}
			if ( template.find( '.um-modal-header' ).length ) {
				options.header = template.find( '.um-modal-header' ).text().trim();
			}

			// add modal
			return this.addModal( content, options );
		},

		/**
		 * Add a modal based on button attributes.
		 * @param   {Object} button  The button jQuery object.
		 * @returns {Object}         A modal jQuery object.
		 */
		open: function (button) {
			let $btn = $( button ),
					id = null,
					size = 'normal',
					ajax = false,
					image = null,
					admin = false;

			// Get parameters
			if ( $btn.data( 'modal' ) ) {
				id = $btn.data( 'modal' );
			}
			if ( $btn.data( 'modal-size' ) ) {
				size = $btn.data( 'modal-size' );
			}
			if ( $btn.data( 'dynamic-content' ) ) {
				ajax = true;
			}
			if ( $( document.body ).is( '.wp-admin' ) ) {
				admin = true;
			}

			// Find template
			let $tpl = jQuery( '#' + id );
			if ( $tpl.length < 1 ) {
				$tpl = jQuery( id );
			}

			// Modify template content
			if ( $tpl.length > 0 && $btn.data( 'modal-copy' ) ) {
				let ratio,
						user_id,
						$hiddenContent = $btn.parents( '.um-field' ).find( '.um-modal-hidden-content' );

				if ( $hiddenContent.length > 0 ) {
					$tpl.html( $hiddenContent );
				}

				if ( $btn.parents( '[data-user_id]' ).length ) {
					user_id = $btn.parents( '[data-user_id]' ).data( 'user_id' );
				} else if ( jQuery( 'input[type="hidden"][name="user_id"]' ).length > 0 ) {
					user_id = jQuery( 'input[type="hidden"][name="user_id"]' ).val();
				}
				if ( user_id ) {
					$tpl.attr( 'data-user_id', user_id );
				}

				if ( $btn.parents( '[data-ratio]' ).length ) {
					ratio = $btn.parents( '[data-ratio]' ).data( 'ratio' );
					$tpl.attr( 'data-ratio', ratio );
				}
			}

			// For multilevel modals
			if ( $tpl.length > 0 && $btn.data( 'back' ) ) {
				$tpl.find( 'a.um-admin-modal-back' ).attr( 'data-modal', $btn.data( 'back' ) );
			}

			let $modal = this.newModal( id, size, ajax, image, admin );

			/**
			 * UM Hook
			 * @name        um-modal-ajax
			 * @description Use this filter to load modal content by AJAX.
			 * @example
			 *  wp.hooks.addFilter('um-modal-ajax', 'ultimatemember', function (jqXHR, $modal, $btn, data) {
			 *  	return jQuery.ajax( {
			 *			url: wp.ajax.settings.url,
			 *			type: 'POST',
			 *			data: {
			 *				// your code here
			 *			},
			 *			success: function (data) {
			 *				// your code here
			 *			}
			 *		} );
			 *  }, 10);
			 */
			let jqXHR = wp.hooks.applyFilters('um-modal-ajax', null, $modal, $btn, $btn.data());

			/**
			 * UM Hook
			 * @name        um-modal-opened
			 * @description Call additional scripts after the modal opening
			 * @example
			 *  wp.hooks.addAction('um-modal-opened', 'ultimatemember', function ($modal, $btn, data, jqXHR) {
			 *    // your code here
			 *  }, 10);
			 */
			wp.hooks.doAction( 'um-modal-opened', $modal, $btn, $btn.data(), jqXHR );

			return $modal;
		},

		/**
		 * Update modal size and position
		 * @param   {object} modal  A modal element. Optional.
		 */
		responsive: function (modal) {
			let $modal = this.getModal( modal );
			if ( !$modal ) {
				return;
			}

			$modal.removeClass( 'uimob340' ).removeClass( 'uimob500' );

			let w = window.innerWidth
					|| document.documentElement.clientWidth
					|| document.body.clientWidth;

			let h = window.innerHeight
					|| document.documentElement.clientHeight
					|| document.body.clientHeight;

			let $photo = $( '.um-modal-body.um-photo img' ).filter(':visible'), half_gap, modalStyle={};

			if ( $photo.length ) {

				$photo.css({
					maxHeight: h * 0.8,
					maxWidth: w * 0.8
				});

				modalStyle.bottom = (h - $modal.innerHeight()) / 2 + 'px';
				modalStyle.marginLeft = '-' + $photo.width() / 2 + 'px';
				modalStyle.width = $photo.width();

			} else {
				if ( w <= 340 ) {
					modalStyle.bottom = 0;
					modalStyle.height = h;
					modalStyle.width = w;
					$modal.addClass('uimob340');
				} else if ( w <= 500 ) {
					modalStyle.bottom = 0;
					modalStyle.height = h;
					modalStyle.width = w;
					$modal.addClass('uimob500');
				} else if ( w <= 800 ) {
					modalStyle.bottom = (h - $modal.innerHeight()) / 2 + 'px';
					modalStyle.maxHeight = h;
					$modal.addClass('uimob800');
					modalStyle.maxHeight = h;
				} else if ( w <= 960 ) {
					modalStyle.bottom = (h - $modal.innerHeight()) / 2 + 'px';
					modalStyle.maxHeight = h;
					$modal.addClass('uimob960');
				} else if ( w > 960 ) {
					modalStyle.bottom = (h - $modal.innerHeight()) / 2 + 'px';
					modalStyle.maxHeight = h;
				}

				if ( $modal.width() > w ) {
					modalStyle.width = w * 0.8;
					modalStyle.marginLeft = '-' + modalStyle.width / 2 + 'px';
				}
			}

			modalStyle = wp.hooks.applyFilters( 'um-modal-responsive', modalStyle, $modal );

			$modal.css( modalStyle );
		},

		/**
		 * Update a modal content
		 * @param   {string} content  A new content
		 * @param   {object} modal    A modal element. Optional.
		 */
		setContent: function (content, modal) {
			let $modal = this.getModal( modal );
			$modal.find( '.um-modal-body' ).html( content );
			this.responsive( $modal );
		},

		/**
		 * Show current modal
		 * @param   {object} modal  A modal element. Optional.
		 * @param   {int} duration  A number determining how long the animation will run.
		 * @returns {object|null}   Shown modal if exists.
		 */
		show: function (modal, duration) {
			let $modal = this.getModal( modal );
			if ( $modal ) {
				this.addOverlay().after( $modal );
				this.responsive( $modal );
				$modal.animate( {opacity: 1}, duration || 10 );
				wp.hooks.doAction( 'um-modal-shown', $modal );
				return $modal;
			}
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


	/* Global ModalManagerUM */
	if ( typeof window.UM === 'undefined' ) {
		window.UM = {};
	}
	UM.modal = new ModalManagerUM();


	/* event handlers */
	$( document.body )
			.on( 'click', '[data-modal^="um_"], [data-modal^="UM_"]', function (e) {
				e.preventDefault();
				if ( $( e.currentTarget ).is( '.um-admin-modal-back' ) ) {
					return false;
				}
				UM.modal.open( e.currentTarget );
			} )
			.on( 'click', '.um-modal-overlay, [data-action="um_remove_modal"]', function (e) {
				e.preventDefault();
				UM.modal.close();
			} );


	/* integration with jQuery */
	$.fn.umModal = function (options) {
		UM.modal.addModal( this.clone(), options );
	};
	$.fn.umModalBtn = function ( content, options) {
		this.on('click', function(e){
			e.preventDefault();
			UM.modal.addModal( content, options, e );
		});
	};
})( jQuery );