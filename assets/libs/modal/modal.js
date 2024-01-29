/*!
 * UM-Modal
 *
 * @author  Ultimate Member
 * @since   UM 3.0
 * @version 1.0.0
 *
 * @link    https://docs.ultimatemember.com/
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

		this.defaultOptions = {
			attributes: {},
			classes: '',
			duration: 400, // ms
			footer: '',
			header: '',
			size: 'normal', // small, normal, large
			template: '',
			content: '',
		};

		this.defaultTemplate = '<div class="um um-modal"><span class="um-modal-close">&times;</span><div class="um-modal-header"></div><div class="um-modal-body"></div><div class="um-modal-footer"></div></div>';

	}


	/**
	 *
	 * @type {{constructor: ModalManagerUM, addModal: (function((string|Object), Object, *): (*|jQuery|HTMLElement)), addOverlay: (function(): (*|jQuery|HTMLElement)), clear: clear, close: (function(): ModalManagerUM), filterOptions: (function(Object): *), getModal: (function(Object): null), hide: (function(Object): (*|Object)), loading: (function(Boolean, Object): ModalManagerUM), responsive: (function(Object): ModalManagerUM), setContent: (function(string, *=): ModalManagerUM), show: (function(Object): (*|Object)), stopEvent: stopEvent}}
	 */
	ModalManagerUM.prototype = {

		constructor: ModalManagerUM,

		/**
		 * Add and display a modal
		 * @param   {object} options         Modal properties. Optional.
		 * @param   {object} event         Modal properties. Optional.
		 * @returns {object}                 A modal jQuery object.
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
					if ( typeof res === 'object' && typeof res.readyState === 'number' && typeof res.done === 'function' ) {

						/**
						 * Action fired before loading modal content by AJAX
						 */
						wp.hooks.doAction( 'um-modal-before-ajax', $modal, options, res );
						this.loading( true, $modal );

						res.done( function (data) {

							/**
							 * Action fired after loading modal content by AJAX
							 */
							wp.hooks.doAction( 'um-modal-after-ajax', $modal, data, res );
							UM.modal.loading( false, $modal );
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
		 * Close All modals
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
		 * Filter modal options
		 * @param   {Object} options  Modal options.
		 * @returns {Object}          Modal options.
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
		 * Get the current modal
		 * @param   {Object} modal  A modal element. Optional.
		 * @returns {Object|null}   A modal jQuery object or NULL.
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
		 * Hide current modal
		 * @param   {Object} modal  A modal element. Optional.
		 * @returns {Object|null}   Hidden modal if exists.
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
		 *
		 * @param   {Boolean} isLoading  The modal is awaiting a request.
		 * @param   {Object} modal       A modal element. Optional.
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
		 * Update modal size and position
		 * @param   {object} modal   A modal element. Optional.
		 * @returns {ModalManagerUM}
		 */
		responsive: function( modal ) {
			let $modal = this.getModal( modal );

			if ( $modal ) {
				$modal.css( {opacity: 0} );
				//$modal.removeClass( 'uimob340' ).removeClass( 'uimob500' ).removeClass( 'uimob800' ).removeClass( 'uimob960' );

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
					//$modal.addClass( 'uimob340' );
				} else if ( w <= 500 ) {
					modalStyle.bottom = 0;
					modalStyle.height = h;
					modalStyle.width = w;
					//$modal.addClass( 'uimob500' );
				} else if ( w <= 800 ) {
					modalStyle.bottom = (h - $modal.innerHeight()) / 2 + 'px';
					modalStyle.maxHeight = h - modalHeightDiff;
					modalBodyStyle.maxHeight = modalStyle.maxHeight - modalBodyHeightDiff;
					//$modal.addClass( 'uimob800' );
				} else if ( w <= 960 ) {
					modalStyle.bottom = (h - $modal.innerHeight()) / 2 + 'px';
					modalStyle.maxHeight = h - modalHeightDiff;
					modalBodyStyle.maxHeight = modalStyle.maxHeight - modalBodyHeightDiff;
					//$modal.addClass( 'uimob960' );
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
				$modal.css( {opacity: 1} );
				$modal.find('.um-modal-body').css( modalBodyStyle );
			}

			return this;
		},

		/**
		 * Update a modal content
		 * @param   {string} content  A new content
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
		 * Show current modal
		 * @param   {object} modal  A modal element. Optional.
		 * @returns {object|null}   Shown modal if exists.
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

		buttonClick: function(e) {
			e.preventDefault();
			wp.hooks.doAction( 'um-modal-button-clicked', e.data.settings, e );
			UM.modal.addModal( e.data.settings, e );
		},

		/**
		 * Stop event propagation
		 * @param {object} e  jQuery.Event
		 */
		stopEvent: function (e) {
			e.preventDefault();
			e.stopPropagation();
		},

		destroy: function (modal) {
			let $modal = this.getModal( modal );
			if ( $modal ) {
				wp.hooks.doAction( 'um-modal-before-destroy', $modal );
				$modal.each( function( element ) {
					let $button = $( this )[0].umModalOptions.relatedButton;
					$button.off( 'click', UM.modal.buttonClick );
					$button.data( 'um-modal-ready', false );
				});
				this.clear();
			}
			return $modal;
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


	$.fn.umModal = function( options ) {
		const settings = $.extend({
			// These are the defaults.
			classes:  '',
			duration: 400, // ms
			footer:   '',
			header:   '',
			size:     'normal', // small, normal, large
			content:  '',
		}, options );

		this.each( function( element ) {
			let $button = $( this );

			if ( ! $button.data( 'um-modal-ready' ) ) {
				settings.relatedButton = $button;
				$button.on( 'click', { settings: settings }, UM.modal.buttonClick );
				$button.data( 'um-modal-ready', true );
			}
		});

		return this;
	};
})( jQuery );
