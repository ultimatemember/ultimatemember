/**
 * UM-Modal
 *
 * @link    https://ultimatemember.com/
 * @author  Ultimate Member
 * @since   UM 3.0
 * @version 1.0.1
 */

(function ($) {

	/**
	 * Initializes a new instance of ModalManagerUM.
	 * @constructs ModalManagerUM
	 * @param {Array}  M
	 * @param {Object} defaultOptions
	 * @param {String} defaultTemplate
	 */
	function ModalManagerUM() {

		/**
		 * All open modals are stored here.
		 * @type {Array}
		 */
		this.M = [];

		/**
		 * Default modal options.
		 * @type {Object}
		 */
		this.defaultOptions = {
			attributes: {},
			classes: '',
			content: '',
			duration: 400, // ms
			footer: '',
			header: '',
			size: 'normal', // small, normal, large
			template: ''
		};

		/**
		 * Default modal template.
		 * @type {String}
		 */
		this.defaultTemplate = '<div class="um-modal"><span class="um-modal-close">&times;</span><div class="um-modal-header"></div><div class="um-modal-body"></div><div class="um-modal-footer"></div></div>';

	}


	/**
	 * ModalManagerUM prototype.
	 * @type {{constructor: ModalManagerUM, addModal: (function(Object, jQuery.Event): jQuery), addOverlay: (function(): jQuery), clear: (function(): ModalManagerUM), close: (function(): ModalManagerUM), closeAll: (function(): ModalManagerUM), filterOptions: (function(Object): Object), getModal: (function(Object): (jQuery|null)), hide: (function(Object): (jQuery|null)), loading: (function(Boolean, Object): ModalManagerUM), responsive: (function(Object): ModalManagerUM), setContent: (function(String, Object): ModalManagerUM), show: (function(Object): (jQuery|null)), stopEvent: (function(): ModalManagerUM)}}
	 */
	ModalManagerUM.prototype = {

		constructor: ModalManagerUM,

		/**
		 * Add and display a modal.
		 * @param   {Object}       options Modal properties. Optional. Options object is passed to the function that requests the content as the second argument.
		 * @param   {jQuery.Event} event   Event object. Optional. The event object is passed to the function that requests the content as the first argument.
		 * @returns {jQuery}               A modal jQuery object.
		 */
		addModal: function (options, event) {
			options = this.filterOptions( options );

			/* Template */
			let $modal;
			if ( options.template ) { // Custom template
				let template = wp.template( options.template );
				if ( template ) {
					$modal = $( template( options ) );
				}
			}
			if ( !$modal ) { // Default template
				$modal = $( this.defaultTemplate );
				if ( options.header ) {
					$modal.find( '.um-modal-header' ).html( options.header );
				} else {
					$modal.find( '.um-modal-header' ).remove();
				}
				if ( options.footer ) {
					$modal.find( '.um-modal-footer' ).html( options.footer );
				} else {
					$modal.find( '.um-modal-footer' ).remove();
				}
			}

			/* Content */
			let $modalBody = $modal.find( '.um-modal-body' );

			switch ( typeof options.content ) {
				case 'function':
					let res = options.content.apply( $modal, [event, options] );
					if ( typeof res === 'object' && typeof res.done === 'function' && typeof res.fail === 'function' ) {

						// Action fired before loading modal content by AJAX.
						wp.hooks.doAction( 'um-modal-before-ajax', $modal, options, res );
						this.loading( true, $modal );

						res.done( function (data) {
							UM.modal.loading( false, $modal );

							let content = typeof data === 'string' ? data : ( typeof data === 'object' && typeof data.data === 'string' ? data.data : '' );
							if ( content && $modalBody.is( ':empty' ) ) {
								UM.modal.setContent( content, $modal ).responsive( $modal );
							} else {
								console.warn( 'UM-Modal: received modal content is empty.' );
							}

							// Action fired after loading modal content by AJAX.
							wp.hooks.doAction( 'um-modal-after-ajax', $modal, data, res );
						} );

						res.fail( function (data) {
							UM.modal.loading( false, $modal );
							console.error( data );

							// Action fired after loading modal content by AJAX is failed.
							wp.hooks.doAction( 'um-modal-after-ajax-fail', $modal, data, res );
						} );

					} else {
						this.setContent( res, $modal );
					}
					break;

				case 'object':
					this.setContent( $( options.content ).clone(), $modal );
					break;

				case 'string':
					if ( options.content === 'loading' ) {
						this.loading( true, $modal );
					} else
						if ( /^https?:/.test( options.content ) ) {
						this.loading( true, $modal );
						$modalBody.load( options.content.trim(), function () {
							this.loading( false, $modal );
							UM.modal.responsive( $modal );
						} );
					} else
					if ( /^(#|\.)/.test( options.content ) && $( options.content ).length ) {
						this.setContent( $( options.content ).clone().children(), $modal );
					} else {
						this.setContent( options.content, $modal );
					}
					break;

				default:
					this.setContent( options.content, $modal );
			}

			/* Attributes, classes and styles */
			$modal.get( 0 ).umModalOptions = options;
			if ( typeof options.attributes === 'object' ) {
				for ( let i in options.attributes ) {
					$modal.attr( i, options.attributes[i] );
				}
			}
			if ( options.classes.length ) {
				$modal.addClass( options.classes );
			}
			if ( options.size.length ) {
				$modal.addClass( options.size );
			}

			/* Handlers */
			$modal.on( 'click', 'a:not([href^="javascript"])', this.stopEvent );
			$modal.on( 'touchmove', this.stopEvent );

			$modalBody.find( 'img' ).on( 'load', function () {
				UM.modal.responsive( $modal );
			} );

			/**
			 * UM Hook
			 * @name        um-modal-before-add
			 * @description Action fired before modal content is added.
			 * @example
			 *  wp.hooks.addAction('um-modal-before-add', 'ultimatemember', function ($modal, options) {
			 *    // your code here
			 *  }, 10);
			 */
			wp.hooks.doAction( 'um-modal-before-add', $modal, options );

			/* Add to the stack of modals and display */
			this.hide();
			this.M.push( $modal );
			this.show( $modal );

			return $modal;
		},

		/**
		 * Add and display a modal overlay.
		 * @returns {jQuery} A modal overlay jQuery object.
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
		 * Remove all modals and overlay.
		 * @returns {ModalManagerUM}
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
			return this;
		},

		/**
		 * Close current modal.
		 * @returns {ModalManagerUM}
		 */
		close: function () {
			let $modal = this.getModal();

			if ( $modal && $modal.length ) {

				/**
				 * UM Hook
				 * @name        um-modal-before-close
				 * @description Action fired before close a modal.
				 * @example
				 *  wp.hooks.addAction('um-modal-before-close', 'ultimatemember', function ($modal) {
				 *    // your code here
				 *  }, 10);
				 */
				wp.hooks.doAction( 'um-modal-before-close', $modal );
			}

			if ( this.M.length > 1 ) {
				this.M.pop().remove();
				this.show();
			} else {
				this.clear();
			}

			return this;
		},

		/**
		 * Close All modals.
		 * @returns {ModalManagerUM}
		 */
		closeAll: function () {
			let $modal = this.getModal();

			// trigger hook for currently visible modal
			if ( $modal && $modal.length ) {

				/**
				 * UM Hook
				 * @name        um-modal-before-close
				 * @description Action fired before close a modal.
				 * @example
				 *  wp.hooks.addAction('um-modal-before-close', 'ultimatemember', function ($modal) {
				 *    // your code here
				 *  }, 10);
				 */
				wp.hooks.doAction( 'um-modal-before-close', $modal );
			}

			this.clear();
			return this;
		},

		/**
		 * Filter modal options.
		 * @param   {Object} options Modal options.
		 * @returns {Object}         Modal options.
		 */
		filterOptions: function (options) {

			/**
			 * UM Hook
			 * @name        um-modal-def-options
			 * @description Use this filter to modify default modal options.
			 * @example
			 *  wp.hooks.addFilter('um-modal-def-options', 'ultimatemember', function (defOptions) {
			 *    // your code here
			 *    return defOptions;
			 *  }, 10);
			 */
			let defOptions = wp.hooks.applyFilters( 'um-modal-def-options', this.defaultOptions );

			return $.extend( {}, defOptions, options );
		},

		/**
		 * Get the current modal.
		 * @param   {Object}      modal A modal element. Optional.
		 * @returns {jQuery|null}       A modal jQuery object or NULL.
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
		 * Hide current modal.
		 * @param   {Object}      modal A modal element. Optional.
		 * @returns {jQuery|null}       Hidden modal if exists.
		 */
		hide: function (modal) {
			let $modal = this.getModal( modal );
			if ( $modal ) {
				$modal.detach();

				/**
				 * UM Hook
				 * @name        um-modal-hidden
				 * @description Action fired when modal is hidden.
				 * @example
				 *  wp.hooks.addAction('um-modal-hidden', 'ultimatemember', function ($modal) {
				 *    // your code here
				 *  }, 10);
				 */
				wp.hooks.doAction( 'um-modal-hidden', $modal );
			}
			return $modal;
		},

		/**
		 * Add or remove class 'loading' that displays loading icon.
		 * @param   {Boolean}        isLoading The modal is awaiting a request.
		 * @param   {Object}         modal     A modal element. Optional.
		 * @returns {ModalManagerUM}
		 */
		loading: function (isLoading, modal) {
			let $modal = this.getModal( modal );

			if ( isLoading ) {
				$modal.addClass( 'loading' );
				$modal.on( 'click', this.stopEvent );
			} else {
				$modal.removeClass( 'loading' );
				$modal.off( 'click' );
			}

			return this;
		},

		/**
		 * Update modal size and position.
		 * @param   {Object}         modal A modal element. Optional.
		 * @returns {ModalManagerUM}
		 */
		responsive: function( modal ) {
			let $modal = this.getModal( modal );

			if ( $modal ) {
				$modal.removeClass( 'uimob340' ).removeClass( 'uimob500' ).removeClass( 'uimob800' ).removeClass( 'uimob960' );

				const modalHeightDiff = 30;
				let modalBodyHeightDiff = $modal.find('.um-modal-header').outerHeight() * 1;

				let w = window.innerWidth
					|| document.documentElement.clientWidth
					|| document.body.clientWidth;

				let h = window.innerHeight
					|| document.documentElement.clientHeight
					|| document.body.clientHeight;

				let $photo = $modal.find( '.um-modal-body > img' ).filter( ':visible' ), modalStyle = {}, modalBodyStyle = {};

				if ( $photo.length ) {

					$photo.css( {
						maxHeight: h * 0.8,
						maxWidth: w * 0.8
					} );

					modalStyle.bottom = (h - $modal.innerHeight()) / 2 + 'px';
					modalStyle.marginLeft = '-' + $photo.width() / 2 + 'px';
					modalStyle.width = $photo.width();

				} else if ( w <= 340 ) {
					modalStyle.bottom = 0;
					modalStyle.height = h;
					modalStyle.width = w;
					$modal.addClass( 'uimob340' );
				} else if ( w <= 500 ) {
					modalStyle.bottom = 0;
					modalStyle.height = h;
					modalStyle.width = w;
					$modal.addClass( 'uimob500' );
				} else if ( w <= 800 ) {
					modalStyle.bottom = (h - $modal.innerHeight()) / 2 + 'px';
					modalStyle.maxHeight = h - modalHeightDiff;
					modalBodyStyle.maxHeight = modalStyle.maxHeight - modalBodyHeightDiff;
					$modal.addClass( 'uimob800' );
				} else if ( w <= 960 ) {
					modalStyle.bottom = (h - $modal.innerHeight()) / 2 + 'px';
					modalStyle.maxHeight = h - modalHeightDiff;
					modalBodyStyle.maxHeight = modalStyle.maxHeight - modalBodyHeightDiff;
					$modal.addClass( 'uimob960' );
				} else if ( w > 960 ) {
					modalStyle.bottom = (h - $modal.innerHeight()) / 2 + 'px';
					modalStyle.maxHeight = h - modalHeightDiff;
					modalBodyStyle.maxHeight = modalStyle.maxHeight - modalBodyHeightDiff;
				}

				if ( $modal.width() > w ) {
					modalStyle.width = w * 0.9;
					modalStyle.marginLeft = '-' + modalStyle.width / 2 + 'px';
				}

				/**
				 * UM Hook
				 * @name        um-modal-responsive
				 * @description Filters modal styles.
				 * @example
				 *  wp.hooks.addFilter('um-modal-responsive', 'ultimatemember', function (modalStyle, $modal) {
				 *    // your code here
				 *    return modalStyle;
				 *  }, 10);
				 */
				modalStyle = wp.hooks.applyFilters( 'um-modal-responsive', modalStyle, $modal );

				$modal.css( modalStyle );
				$modal.find('.um-modal-body').css( modalBodyStyle );
			}

			return this;
		},

		/**
		 * Update a modal content.
		 * @param   {String}         content A new content.
		 * @param   {Object}         modal   A modal element. Optional.
		 * @returns {ModalManagerUM}
		 */
		setContent: function (content, modal) {
			let $modal = this.getModal( modal );

			if ( $modal ) {
				$modal.find( '.um-modal-body' ).html( content );

				/**
				 * UM Hook
				 * @name        um-modal-content-added
				 * @description Action fired when modal content is inserted.
				 * @example
				 *  wp.hooks.addAction('um-modal-content-added', 'ultimatemember', function ($modal) {
				 *    // your code here
				 *  }, 10);
				 */
				wp.hooks.doAction( 'um-modal-content-added', $modal );
			}

			return this;
		},

		/**
		 * Show current modal.
		 * @param   {Object}      modal A modal element. Optional.
		 * @returns {jQuery|null}       Shown modal if exists.
		 */
		show: function (modal) {
			let $modal = this.getModal( modal );
			if ( $modal ) {
				let options = $modal.get( 0 ).umModalOptions;
				this.addOverlay().after( $modal );
				this.responsive( $modal );
				$modal.animate( {opacity: 1}, options.duration );

				/**
				 * UM Hook
				 * @name        um-modal-shown
				 * @description Action fired when modal is shown.
				 * @example
				 *  wp.hooks.addAction('um-modal-shown', 'ultimatemember', function ($modal) {
				 *    // your code here
				 *  }, 10);
				 */
				wp.hooks.doAction( 'um-modal-shown', $modal );
			}
			return $modal;
		},

		/**
		 * Stop event propagation.
		 * @param   {jQuery.Event}   e Event object.
		 * @returns {ModalManagerUM}
		 */
		stopEvent: function (e) {
			e.preventDefault();
			e.stopPropagation();
			return this;
		}
	};

	/* Global ModalManagerUM */
	if ( typeof window.UM === 'undefined' ) {
		window.UM = {};
	}
	UM.modal = new ModalManagerUM();

	/* event handlers */
	$( document.body ).on( 'click', '.um-modal-overlay, .um-modal-close', function (e) {
		e.preventDefault();
		UM.modal.close();
	} );

	$( window ).on( 'load',function() {
		UM.modal.responsive();
	});

	$( window ).on( 'resize', function() {
		UM.modal.responsive();
	});

	/**
	 * Initialise modal's open button with jQuery.
	 * @param   {Object} options Modal properties. Optional.
	 * @returns {Object}         Query object.
	 */
	$.fn.umModal = function( options ) {
		this.on( 'click', function (event) {
			event.preventDefault();

			const settings = $.extend( UM.modal.defaultOptions, options || {}, {
				relatedButton: $( event.currentTarget )
			} );

			// Action fired when button is clicked before modal is shown.
			wp.hooks.doAction( 'um-modal-button-clicked', settings, event );

			UM.modal.addModal( settings, event );
		} ).data( 'um-modal-ready', true );
		return this;
	};
})( jQuery );

/**
 * @example How to initialize a button that retrieves a modal content by AJAX and displays response in a new modal.
 *
jQuery( '.um_button_example' ).umModal( {
	content: function ( event, options ) {
		let $btn = jQuery( event.currentTarget );
		return wp.ajax.send( 'um_ajax_action_example', {
			data: {
				nonce: um_admin_scripts.nonce,
				user_id: $btn.data( 'user_id' )
			}
		} );
	},
	header: wp.i18n.__( 'Modal header example', 'ultimate-member' ),
	size: 'large'
} );
*/
