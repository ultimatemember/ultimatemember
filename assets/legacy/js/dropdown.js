(function ($) {

	/**
	 * The constructor of the dropdown object
	 * @param   {object}  element  The menu element.
	 * @returns {object}           The dropdown menu object.
	 */
	function um_dropdownMenu(element) {

		var self = {
			get: function(){
				return self;
			},

			show: function () {
				self.hideAll();

				/* add dropdown into the <body> */
				self.$menu = self.$element.find('.um-new-dropdown');
				if ( !self.$menu.length ) {
					self.$menu = $('div.um-new-dropdown[data-element="' + self.data.element + '"]').first();
				}

				self.$dropdown = self.$menu.clone();
				self.$dropdown.on('click', 'li a', self.itemHandler).attr('data-cloned', '1'); /* add the handler for menu items */
				$(window).on('resize', self.updatePosition); /* update the position on window resize */

				var parent = '' !== self.data.parent ? self.data.parent : document.body;
				$(parent).append(self.$dropdown);

				/* trigger event */
				self.$element.trigger('um_new_dropdown_render', {
					dropdown_layout: self.$dropdown,
					trigger: self.data.trigger,
					element: self.data.elemen,
					obj: self.$element
				});

				/* set styles and show */
				self.$dropdown.css(self.calculatePosition()).show();
				self.$element.addClass('um-new-dropdown-shown').data('um-new-dropdown-show', true);

				return self;
			},

			hide: function () {
				if ( self.$dropdown && self.$dropdown.is(':visible') ) {
					$(window).off('resize', self.updatePosition);
					self.$dropdown.remove();
					self.$element.removeClass('um-new-dropdown-shown').data('um-new-dropdown-show', false);
				}

				return self;
			},

			hideAll: function () {
				if ( self.$element.data('um-new-dropdown-show') ) {
					self.hide();
				}

				$( 'div.um-new-dropdown[data-cloned="1"]' ).remove();
				$('.um-new-dropdown-shown').removeClass('um-new-dropdown-shown').data('um-new-dropdown-show', false);

				return self;
			},

			calculatePosition: function () {
				var rect = self.$element.get(0).getBoundingClientRect(),
					height = self.$dropdown.innerHeight() || 150,
					width = self.data.width || 150,
					place = '';

				var offset;
				if ( '' !== self.data.parent ) {
					var parentPos = $( self.data.parent ).offset();
					var childPos = self.$element.offset();

					offset = {
						top: childPos.top - parentPos.top,
						left: childPos.left - parentPos.left
					};
				} else {
					offset = self.$element.offset();
				}

				var base_width = '' !== self.data.parent ? $( self.data.parent )[0].offsetWidth : window.innerWidth;
				var base_height = '' !== self.data.parent ? $( self.data.parent )[0].offsetHeight : window.innerHeight;

				var css = {
					position: 'absolute',
					width: width + 'px'
				};

				/* vertical position */
				if ( base_height - rect.bottom > height ) {
					css.top = offset.top + rect.height + 'px';
					place += 'bottom';
				} else {
					place += 'top';
					css.top = offset.top - height + 'px';
				}

				/* horisontal position */
				if ( offset.left > width || offset.left > base_width / 2 ) {
					css.left = offset.left + rect.width - width + 'px';
					place += '-left';
				} else {
					css.left = offset.left + 'px';
					place += '-right';
				}

				/* border */
				switch ( place ) {
					case 'bottom-right':
						css.borderRadius = '0px 5px 5px 5px';
						break;
					case 'bottom-left':
						css.borderRadius = '5px 0px 5px 5px';
						break;
					case 'top-right':
						css.borderRadius = '5px 5px 5px 0px';
						break;
					case 'top-left':
						css.borderRadius = '5px 5px 0px 5px';
						break;
				}

				return css;
			},

			updatePosition: function () {
				if ( self.$dropdown && self.$dropdown.is(':visible') ) {
					self.$dropdown.css(self.calculatePosition());
				}

				return self;
			},

			itemHandler: function (e) {
				e.stopPropagation();

				/* trigger 'click' in the original menu */
				var attrClass = $(e.currentTarget).attr('class');
				self.$menu.find('li a[class="' + attrClass + '"]').trigger('click');

				/* hide dropdown */
				if ( self.$element.data('um-new-dropdown-show') ) {
					self.hide();
				}
			},

			triggerHandler: function(e) {
				e.stopPropagation();

				self.$element = $(e.currentTarget);

				if ( self.$element.data('um-new-dropdown-show') ) {
					self.hide();
				} else {
					self.show();
				}
			}
		};

		// hidden dropdown menu block generated via PHP. Is used for cloning when 'action' on the 'link'
		self.$menu = $(element);

		// 'link' data
		self.data = self.$menu.data();

		// base 'link' which we use for 'action' and show a clone of the hidden dropdown
		self.$element = self.$menu.closest(self.data.element);
		if ( ! self.$element.length ) {
			self.$element = $( self.data.element ).first();
		}

		self.$dropdown = $(document.body).children('div[data-element="' + self.data.element + '"]');

		if ( typeof self.data.initted === 'undefined' ) {
			// single init based on 'initted' data and add 'action' handler for the 'link'
			self.$menu.data('initted', true);
			self.data = self.$menu.data();

			// screenTriggers is used to not duplicate the triggers for more than 1 element on the page
			if ( typeof um_dropdownMenu.screenTriggers === 'undefined' ) {
				um_dropdownMenu.screenTriggers = {};
			}

			if ( um_dropdownMenu.screenTriggers[ self.data.element ] !== self.data.trigger ) {
				um_dropdownMenu.screenTriggers[ self.data.element ] = self.data.trigger;
				$(document.body).on( self.data.trigger, self.data.element, self.triggerHandler );
			}
		}

		if ( typeof um_dropdownMenu.globalHandlersInitted === 'undefined' ) {
			um_dropdownMenu.globalHandlersInitted = true;
			//var globalParent = '' !== self.data.parent ? self.data.parent : document.body;
			$( document.body ).on('click', function(e) {
				if ( ! $( e.target ).closest('.um-new-dropdown').length ) {
					self.hideAll();
				}
			});
		}

		return self;
	}

	/* Add the method um_dropdownMenu() to the jQuery */
	$.fn.um_dropdownMenu = function (action) {
		if ( typeof action === 'string' && action ) {
			return this.map( function (i, menu) {
				var obj = um_dropdownMenu( menu );
				return typeof obj[action] === 'function' ? obj[action]() : obj[action];
			} ).toArray();
		} else {
			return this.each( function (i, menu) {
				um_dropdownMenu( menu );
			} );
		}
	};

})(jQuery);


function um_init_new_dropdown() {
	jQuery('.um-new-dropdown').um_dropdownMenu();
}

/* Init all dropdown menus on page load */
jQuery( document ).ready( function($) {
	um_init_new_dropdown();
});
