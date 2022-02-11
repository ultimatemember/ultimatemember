/* Modal window jQuery plugin */

(function( $, undefined ) {
	$.fn.umModalN = function( options ) {
		// This is the easiest way to have default options.
		var settings = $.extend({
			// These are the defaults.
			color: "#556b2f",
			backgroundColor: "white"
		}, options );

		console.log( this );

		this.each( function( element ) {
			console.log( element );
			console.log( this );
			let $button = this;

			// this.on( 'click', function (e) {
			// 	e.preventDefault();
			// 	options.relatedButton = $button;
			// 	UM.modal.addModal( options, e );
			// } );

			// Do something to each element here.
		});

		return this;

		// if ( methods[ method ] ) {
		// 	return methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ) );
		// } else if ( typeof method === 'object' || ! method ) {
		// 	return methods.init.apply( this, arguments );
		// } else {
		// 	$.error( 'Method ' +  method + ' does not exist for jQuery.umModal plugin' );
		// }
	};
})( jQuery );



/*
* Plugin for preview shutter gallery
*/

(function( $, undefined ) {
/*    here is plugin code*/
    var options;

    var sb_clicked;

    var imgPreloader;

    var lightboxPreload = false;

    var default_options = {
        'href'      : null,
        'win_w'     : $(window).width(),
        'win_h'     : $(window).height(),
        'title'     : '&nbsp;',
        'width'     : false,
        'height'    : false,
        'class'     : '',
        'show_nav'  : true,
        'self_init' : true
    };

    var current_index;

    var isString = function(str) {
        return str && $.type(str) === "string";
    };

    if( typeof( sb_opened ) === "undefined" ) {
        sb_opened = false;
    }

    var methods = {
        init : function( settings ) {
            if ( 0 === jQuery(this).length ) {
                return false;
            }
            
            options = $.extend( {}, default_options, settings );

            options.uniq_id = parseInt(Math.random() * (100 - 1) + 1);

            this.data('options', options);

            if( this.data( 'options' ).type == 'gallery' ) {
                this.each( function() {
                    $( this ).addClass( 'shutter_box_item' );
                });
            } else if( this.data( 'options' ).type == 'lightbox' ) {
                $( this ).addClass( 'shutter_box_lightbox' );
            }

            var current_settings = this.data('options');

            if( current_settings.self_init ) {
                this.click(function (e) {
                    if( sb_clicked ) {
                        return false;
                    }

                    sb_clicked = true;
                    methods.before_show.apply($(this));

                    var obj = this;
                    var interavalLoop = 0;
                    var openInterval = setInterval(function () {
                        if (!sb_opened) {
                            //methods.show.apply( $( this ) );
                            methods.show.apply($(obj));
                            clearInterval(openInterval);
                        }

                        interavalLoop++;
                        if (interavalLoop > 100) {
                            clearInterval(openInterval);
                        }
                    }, 500);

                    e.preventDefault();
                    e.stopPropagation();
                });
            }
        },
        before_show: function( settings ) {
            if( typeof this.data( 'options' ).onSBLoad === "function" ) {
                this.data( 'options' ).onSBLoad.apply( $( this ) );
            }
        },
        after_load_content: function (settings) {
            if (typeof this.data('options').afterLoad === "function") {
                this.data('options').afterLoad.apply($(this));
            }
        },
        showBackground: function () {
            var background = $('.sb_background');

            if( background.hasClass( 'hidden' ) ) {
                //in all the rest browsers - fade slowly
                background.height($(document).height()).toggleClass('hidden').fadeTo('slow', 0.7);
            }
        },
        showPreLoader: function() {
            if( $( '.sb_background').length == 0 ) {
                $('body').append('<div class="sb_background hidden"><div class="sb_loader"></div></div>');
            } else {
                methods.showLoader.apply( $( this ) );
            }

            methods.showBackground.apply( $( this ) );
            lightboxPreload = true;
        },
        hidePreLoader: function() {
            $( '.sb_background' ).fadeTo( 'slow', 0, function() {
                $( this ).remove();
            });
        },
        show : function( settings ) {
            var obj = this;
            var body = $( 'body' );
            var current_settings = this.data( 'options' );

            //added to body SHUTTER BOX background and SHUTTER BOX content
            if( !lightboxPreload || $( '.sb_background').length == 0 ) {
                body.append( '<div class="sb_background hidden"></div>' );
            }

            if ( this.data( 'options' ).view_type == 'lightbox' ) {
                //for lightbox
                var title = this.data( 'options' ).title;

                if( $('.sb_lightbox').length > 0 ) {
                    $('.sb_lightbox').hide();
                    methods.hideLoader.apply( $( this ) );
                }

                var popup_block = $( '<div class="sb_lightbox ' + this.data( 'options').class + '"></div>').appendTo( 'body' );

                popup_block.html( '<div class="sb_lightbox_content">' +
                    '<div class="sb_lightbox_content_header">' +
                    '<div class="sb_lightbox_content_title">' +
                    title +
                    '</div>' +
                    '<div class="sb_lightbox_header_actions_line">' +
                    '<button type="button" class="sb_button sb_close">' +
                    '<span class="sb_fonticon"></span>' +
                    '</button>' +
                    '</div>' +
                    '</div>' +
                    '<div class="sb_lightbox_content_wrapper">' +
                    '<div class="sb_lightbox_content_body"></div>' +
                    '</div>' +
                    '</div>' );

                var lightbox = $('.sb_lightbox:last');
                var width = this.data( 'options' ).width;
                var height = this.data( 'options' ).height;

                if( this.data( 'options' ).type == 'ajax' ) {
                    //  width/height build
                    if( width ) {
                        lightbox.css({
                            'width': width
                        });
                    } else {
                        lightbox.css({
                            'width': '1px'
                        });
                    }

                    if( height ) {
                        lightbox.css({
                            'height': height
                        });
                    } else {
                        lightbox.css({
                            'height': '1px'
                        });
                    }

                    lightbox.css({
                        'left' : ( $(window).width() - lightbox.width() ) / 2 + 'px',
                        'top' : ( $(window).height() - lightbox.height() ) / 2 + 'px'
                    });
                }

                $(window).bind('resize.number' + $('.sb_lightbox').length, function () {
                    methods.resize.apply( $( obj ) );
                });

                if( !lightboxPreload ) {
                    methods.showBackground.apply( $( this ) );
                } else {
                    $( '.sb_background > .sb_loader').remove();
                    lightboxPreload = false;
                }
            } else if( this.data( 'options' ).view_type == 'popup' ) {
                //for popup multi add
                body.append(
                    '<div class="sb_basic sb_clean hidden">' +
                        '<div class="sb_content_modal">' +
                            '<div class="sb_popup">' +
                                '<div class="sb_popup_content">' +
                                    '<div class="sb_popup_content_header">' +
                                        '<div class="sb_popup_header_actions_line">' +
                                            '<button type="button" class="sb_button sb_close">' +
                                                '<span class="sb_fonticon"></span>' +
                                            '</button>' +
                                        '</div>' +
                                    '</div>' +
                                    '<div class="sb_popup_content_body">' +
                                        '<div class="sb_popup_content_navigation">' +
                                            '<button type="button" class="sb_button sb_popup_nav_back" data-current_step="1" disabled="true">' +
                                                '<span class="sb_fonticon"></span>' +
                                            '</button>' +
                                            '<ul class="sb_popup_nav_breadcrumbs"></ul>' +
                                        '</div>' +
                                        '<div class="sb_popup_content_container">' +
                                            '<div class="sb_popup_container_preview">' +
                                                '<div class="sb_loader_inner"></div>' +
                                            '</div>' +
                                            '<div class="sb_popup_container_sidebar">' +
                                                '<div class="sb_popup_sidebar_content">' +
                                                    '<div class="sb_loader_inner"></div>' +
                                                '</div>' +
                                            '</div>' +
                                        '</div>' +
                                    '</div>' +
                                '</div>' +
                            '</div>' +
                        '</div>' +
                    '</div>'
                );

                $( '.sb_background, .sb_basic' ).height( $( document ).height() ).toggleClass( 'hidden' ).fadeTo( 'slow', 0.95 );

            } else {
                //for gallery
                body.append(
                    '<div class="sb_basic sb_clean hidden">' +
                        '<div class="sb_content">' +
                            '<div class="sb_content_header">' +
                                '<div class="sb_tools">' +
                                    '<button type="button" class="sb_button sb_close">' +
                                        '<span class="sb_fonticon"></span>' +
                                    '</button>' +
                                '</div>'+
                            '</div>' +
                            '<div class="sb_content_body">' +
                            '</div>' +
                            '<div class="sb_content_footer">' +
                            '</div>' +
                        '</div>' +
                    '</div>'
                );

                $( '.sb_background, .sb_basic' ).height( $( document ).height() ).toggleClass( 'hidden' ).fadeTo( 'slow', 0.95 );
            }



            if( this.data( 'options' ).type == 'gallery' ) {

                var gallery_item = $( 'a.shutter_box_item' );
                var show_nav = this.data('options').show_nav;
                current_index = gallery_item.index(this);

                if( imgPreloader === undefined ) {
                    methods.showLoader.apply( $( this ) );
                    imgPreloader = {};
                }

                gallery_item.each( function( index ) {
                    imgPreloader[index] = new Image();
                    imgPreloader[index].onload = function() {
                        imgPreloader[index].onload = null;

                        imgPreloader[index].kx = parseFloat( imgPreloader[index].width / default_options.win_w );
                        imgPreloader[index].ky = parseFloat( imgPreloader[index].height / default_options.win_h );

                        if( imgPreloader[index].kx > 1 || imgPreloader[index].ky > 1 ) {
                            if( imgPreloader[index].kx > imgPreloader[index].ky ) {
                                imgPreloader[index].width = parseInt( parseInt( imgPreloader[index].width ) / imgPreloader[index].kx );
                                imgPreloader[index].height = parseInt( parseInt( imgPreloader[index].height ) / imgPreloader[index].kx );
                            } else if( imgPreloader[index].kx < imgPreloader[index].ky ) {
                                imgPreloader[index].width = parseInt( parseInt( imgPreloader[index].width ) / imgPreloader[index].ky );
                                imgPreloader[index].height = parseInt( parseInt( imgPreloader[index].height ) / imgPreloader[index].ky );
                            }
                        }

                        if( index == current_index ) {

                            if ( show_nav ) {
                                methods.showNav.apply($(obj));
                            }
                            methods.footerLoad.apply( $( obj ) );

                            if( imgPreloader[index].kx > 1 || imgPreloader[index].ky > 1 ) {
                                $( '.sb_content_body' ).append( '<img class="sb_image" src="' + imgPreloader[current_index].src + '" alt="" style="width:' + imgPreloader[current_index].width + 'px; height:' + imgPreloader[current_index].height + 'px; position: absolute;">' );
                            } else {
                                $( '.sb_content_body' ).append( '<img class="sb_image" src="' + imgPreloader[current_index].src + '" alt="" style="width:' + imgPreloader[current_index].width + 'px; height:' + imgPreloader[current_index].height + 'px; position: fixed; margin:-' + ( imgPreloader[current_index].height / 2 ) + 'px auto 0px; top:50%;">' );
                            }

                            methods.hideLoader.apply( $( this ) );

                        }
                    };
                    imgPreloader[index].src = $(this).attr( 'href' );
                });

            } else if( this.data( 'options' ).type == 'inline' ) {
                var href = this.data( 'options' ).href;
                var content = $( isString(href) ? href.replace(/.*(?=#[^\s]+$)/, '') : href ); //strip for ie7
                $('<div class="shutter_box_placeholder' + this.data('options').uniq_id + '"></div>').insertAfter(content).hide();
                content = $('<div>').html( content ).find( href ).show();

                if ( this.data( 'options' ).view_type == 'lightbox' ) {

                    $( '.sb_lightbox_content_body:last' ).append( content );

                    if( typeof current_settings.inlineBeforeLoad === "function" ) {
                        current_settings.inlineBeforeLoad.apply( $( this ) );
                    } else {
                        methods.inlineBeforeLoad.apply( $( this ) );
                    }

                    //  width/height build
                    if( width ) {
                        lightbox.css({
                            'width': width
                        });
                    } else {
                        lightbox.css({
                            'width': $( '.sb_lightbox_content_body:last').children().width() + 25 + 'px'
                        });
                    }

                    if( height ) {
                        lightbox.css({
                            'height': height
                        });
                    } else {
                        lightbox.css({
                            'height': $( '.sb_lightbox_content_body:last').children().height() + 60 + 'px'
                        });
                    }

                    lightbox.css({
                        'left' : ( $(window).width() - lightbox.width() ) / 2 + 'px',
                        'top' : ( $(window).height() - lightbox.height() ) / 2 + 'px'
                    });

                    methods.resize.apply( $( obj ) );
                } else {
                    $( '.sb_content_body' ).append( '<div class="sb_html_content"></div>' );
                    $( '.sb_html_content' ).append( content );
                }

            } else if( this.data( 'options' ).type == 'iframe' ) {
                var content = '<iframe class="shutterbox-iframe" style="width:100%;height:100%;float:left;margin:0;padding:0;" scrolling="auto" src="' + this.attr('href') + '"></iframe>';

                $( '.sb_lightbox_content_body' ).append( content );

                //  width/height build
                if( width ) {
                    lightbox.css({
                        'width': width
                    });
                } else {
                    lightbox.css({
                        'width': $( '.sb_lightbox_content_body').children().width() + 25 + 'px'
                    });
                }

                if( height ) {
                    lightbox.css({
                        'height': height
                    });
                } else {
                    lightbox.css({
                        'height': $( '.sb_lightbox_content_body').children().height() + 60 + 'px'
                    });
                }

                lightbox.css({
                    'left' : ( $(window).width() - lightbox.width() ) / 2 + 'px',
                    'top' : ( $(window).height() - lightbox.height() ) / 2 + 'px'
                });

            } else if( this.data( 'options' ).type == 'ajax' ) {

                var sb_content;

                if( sb_content === undefined ) {
                    methods.showLoader.apply( $( this ) );
                    sb_content = '';
                }

                var ajax_data = '';
                if( typeof current_settings.setAjaxData === "function" ) {
                    ajax_data = current_settings.setAjaxData.apply( $( this ), [current_settings.ajax_data] );
                } else {
                    ajax_data = methods.setAjaxData.apply( $( this ), [current_settings.ajax_data] );
                }

                jQuery.ajax({
                    type: "POST",
                    url: current_settings.href,
                    data: ajax_data,
                    dataType: current_settings.dataType,
                    timeout: 20000,
                    success: function( data ) {

                        if( current_settings.view_type == 'popup' || current_settings.view_type == 'lightbox' ) {

                            if( typeof current_settings.setAjaxResponse === "function" ) {
                                current_settings.setAjaxResponse.apply( $( this ), [data] );
                            }

                            if( current_settings.view_type == 'lightbox' ) {
                                //  width/height build
                                if( width ) {
                                    lightbox.css({
                                        'width': width
                                    });
                                } else {
                                    lightbox.css({
                                        'width': $( '.sb_lightbox_content_body:last').children().width() + 25 + 'px'
                                    });
                                }

                                if( height ) {
                                    lightbox.css({
                                        'height': height
                                    });
                                } else {
                                    lightbox.css({
                                        'height': $( '.sb_lightbox_content_body:last').children().height() + 60 + 'px'
                                    });
                                }

                                lightbox.css({
                                    'left' : ( $(window).width() - lightbox.width() ) / 2 + 'px',
                                    'top' : ( $(window).height() - lightbox.height() ) / 2 + 'px'
                                });
                            }

                            methods.hideLoader.apply( $( this ) );

                        } else {

                            methods.hideLoader.apply( $( this ) );

                            if( typeof current_settings.responseLoad === "function" && current_settings.dataType == 'json' ) {
                                sb_content = current_settings.responseLoad.apply( $( this ), data );
                            } else {
                                sb_content = data;
                            }

                            if( typeof current_settings.beforeAjaxContent === "function" ) {
                                sb_content = current_settings.beforeAjaxContent.apply( $( this ) ) + sb_content;
                            }

                            $( '.sb_content_body' ).append( '<div class="sb_html_content">' + sb_content + '</div>' );

                            if( typeof current_settings.afterLoadHTML === "function" && current_settings.dataType == 'html' ) {
                                current_settings.afterLoadHTML.apply( $( this ) );
                            }

                            methods.footerLoad.apply( $( obj ) );
                        }
                    },
                    error: function( x, t, m ) {
                        methods.hideLoader.apply( $( this ) );
                        if( t === "timeout" ) {
                            sb_content = 'Timeout, try again.';
                        } else {
                            sb_content = 'Error, try again later.';
                        }

                        $( '.sb_content_body' ).append( '<div class="sb_html_content">' + sb_content + '</div>' );
                    }
                });
            } else if( this.data( 'options' ).type == 'function' ) {
                if( typeof this.data( 'options' ).setContent === "function" ) {
                    this.data( 'options' ).setContent.apply( $( this ) );
                }
            }

            if( this.data( 'options' ).view_type == "lightbox" ) {
                if ( $('.sb_lightbox').length <= 1 ) {
                    //action when click on background or on close button
                    $('.sb_background').click( function(e) {
                        methods.close.apply($(obj));
                    });
                } else {
                    var background_events = $._data( $('.sb_background').get(0), 'events' );
                    var handler = background_events.click[0].handler;

                    $('.sb_background').unbind('click');
                    $('.sb_background').click( function(e) {
                        methods.close.apply($(obj));
                        $('.sb_background').unbind('click');
                        $('.sb_background').click( handler );
                    });
                }

                popup_block.find('.sb_close').click( function() {
                    methods.close.apply($(obj));
                });

            } else {
                $('.sb_background, .sb_close').click( function (e) {
                    methods.close.apply($(obj));
                });
            }

            //add close action on ESC button
            if ( $('.sb_lightbox').length <= 1 ) {
                $( document ).keydown( function(e) {
                    if ( e.keyCode === 27 ) {
                        methods.close.apply($(obj));
                        if ( $('.sb_lightbox').length <= 1 ) {
                            $( this ).unbind( e );
                        }
                    }
                });
            }

            $( '.sb_content_modal' ).click( function(e) {
                if( jQuery(this).find( '.sb_popup' ).length > 0 ) {
                    if( jQuery( '.sb_popup' ).has( e.target ).length == 0  ) {
                        methods.close.apply($(obj));
                    }
                } else {
                    methods.close.apply( $( obj ) );
                }
            });

            //for show/hide navigation when cursor is on shutter box
            var timeout;
            $( '.sb_basic' ).mousemove( function() {
                if( typeof timeout !== "undefined" ) {
                    clearTimeout( timeout );
                }

                $(this).removeClass( 'sb_clean' );

                if( !( $( '.sb_close' ).is( ":hover" ) || ( $( '.sb_left' ).length > 0 && $( '.sb_left' ).is( ":hover" ) ) || ( $( '.sb_right' ).length > 0 && $( '.sb_right' ).is( ":hover" ) ) || ( $( '.sb_footer_right' ).length > 0 && $( '.sb_footer_right' ).is( ":hover" ) ) ) ) {
                    timeout = setTimeout( function(){
                        $( '.sb_basic' ).addClass( 'sb_clean' );
                    }, 1500 );
                }
            });



            //for footer buttons
            if( typeof this.data('options').buttons === "object" ) {

                for( button in this.data('options').buttons ) {
                    jQuery( 'body' ).on( 'click', '.sb_' + button, this.data('options').buttons[button].handler );
                }
            }

            sb_clicked = false;
            sb_opened = true;
            methods.after_load_content.apply($(this));
        },
        showLoader: function() {
            if( this.data( 'options' ).view_type == "lightbox" ) {
                $( '.sb_lightbox_content_body' ).append( '<div class="sb_loader"></div>' );
            } else {
                $( '.sb_content_body' ).append( '<div class="sb_loader"></div>' );
            }
        },
        hideLoader: function() {
            $( '.sb_loader' ).remove();
        },
        resize: function( settings ) {
            var current_settings = this.data( 'options' );
            var lightbox = $('.sb_lightbox:last');

            //10% left/right margins if width more than window width
            if (lightbox.width() + $(window).width() * 0.2 > $(window).width()) {
                lightbox.css({
                    'width': $(window).width() * 0.9 + 'px'
                });
            } else {
                if (current_settings.width) {
                    lightbox.css({
                        'width': current_settings.width
                    });
                } else {
                    lightbox.css({
                        'width': lightbox.find('.sb_lightbox_content_body:last').children().width() + 25 + 'px'
                    });
                }
            }

            //10% top/bottom margins if height more than window height
            if ( ( lightbox.find('.sb_lightbox_content_body:last').children().height() + 60 ) + $(window).height() * 0.2 > $(window).height()) {
                lightbox.css({
                    'height': $(window).height() * 0.9 + 'px'
                });
            } else {
                if (current_settings.height) {
                    lightbox.css({
                        'height': current_settings.height
                    });
                } else {
                    lightbox.css({
                        'height': ( lightbox.find('.sb_lightbox_content_body:last').children().height() + 60 ) + 'px'
                    });
                }
            }

            lightbox.css({
                'left': ( $(window).width() - lightbox.width() ) / 2 + 'px',
                'top': ( $(window).height() - lightbox.height() ) / 2 + 'px'
            });
        },
        showNav: function() {
            var obj = this;

            if( this.data('options').type == 'gallery' ) {
                $( '.sb_content_header' ).append(
                    '<div class="sb_navigation_left">' +
                        '<button type="button" class="sb_button sb_left">' +
                            '<span class="sb_fonticon"></span>' +
                        '</button>' +
                    '</div>' +
                    '<div class="sb_navigation_right">' +
                        '<button type="button" class="sb_button sb_right">' +
                            '<span class="sb_fonticon"></span>' +
                        '</button>' +
                    '</div>'
                );

                //action when click on prev button
                $( '.sb_left' ).click( function() {
                    methods.prev.apply( $( obj ) );
                });
                //action when click on next button
                $( '.sb_right' ).click( function() {
                    methods.next.apply( $( obj ) );
                });

            }
        },
        prev : function() {
            if( imgPreloader[current_index - 1] === undefined ) {
                return false;
            }

            current_index = current_index - 1;
            $( '.sb_image' ).remove();
            $( '.sb_content_footer' ).html('');

            if( imgPreloader[current_index].kx > 1 || imgPreloader[current_index].ky > 1 ) {
                $( '.sb_content_body' ).append( '<img class="sb_image" src="' + imgPreloader[current_index].src + '" alt="" style="width:' + imgPreloader[current_index].width + 'px; height:' + imgPreloader[current_index].height + 'px; position: absolute;">' );
            } else {
                $( '.sb_content_body' ).append( '<img class="sb_image" src="' + imgPreloader[current_index].src + '" alt="" style="width:' + imgPreloader[current_index].width + 'px; height:' + imgPreloader[current_index].height + 'px; position: fixed; margin:-' + ( imgPreloader[current_index].height / 2 ) + 'px auto 0px; top:50%;">' );
            }

            methods.footerLoad.apply( $( this ) );
            //$( '.sb_content_footer' ).append( imgPreloader[current_index].hidden_content + '<div class="sb_footer_right">' + imgPreloader[current_index].buttons_content + '</div>' );


            if( imgPreloader[current_index + 1] !== undefined ) {
                $( '.sb_right' ).attr( 'disabled', false );
            }

            if( imgPreloader[current_index - 1] === undefined ) {
                $( '.sb_left' ).attr( 'disabled', true );
            }
        },
        next : function() {
            if( imgPreloader[current_index + 1] === undefined ) {
                return false;
            }

            current_index = current_index + 1;
            $( '.sb_image' ).remove();
            $( '.sb_content_footer' ).html('');

            if( imgPreloader[current_index].kx > 1 || imgPreloader[current_index].ky > 1 ) {
                $( '.sb_content_body' ).append( '<img class="sb_image" src="' + imgPreloader[current_index].src + '" alt="" style="width:' + imgPreloader[current_index].width + 'px; height:' + imgPreloader[current_index].height + 'px; position: absolute;">' );
            } else {
                $( '.sb_content_body' ).append( '<img class="sb_image" src="' + imgPreloader[current_index].src + '" alt="" style="width:' + imgPreloader[current_index].width + 'px; height:' + imgPreloader[current_index].height + 'px; position: fixed; margin:-' + ( imgPreloader[current_index].height / 2 ) + 'px auto 0px; top:50%;">' );
            }

            methods.footerLoad.apply( $( this ) );

            if( imgPreloader[current_index - 1] !== undefined ) {
                $( '.sb_left' ).attr( 'disabled', false );
            }

            if( imgPreloader[current_index + 1] === undefined ) {
                $( '.sb_right' ).attr( 'disabled', true );
            }
        },
        footerLoad : function() {
            var current_settings = this.data( 'options' );

            footer_content = '';
            footer_content += '<div class="sb_footer_right">';

            /*
            for static hiddens
            if( typeof options.hiddens === "object" ) {

                for( hidden in this.data('options').hiddens ) {

                    if( typeof this.data('options').hiddens[hidden] === "function" ) {
                        hidden_content += '<input type="hidden" name="sb_' + hidden + '" value="' + this.data('options').hiddens[hidden].apply( $(this) ) + '" />';
                    } else {
                        hidden_content += '<input type="hidden" name="sb_' + hidden + '" value="' + this.data('options').hiddens[hidden] + '" />';
                    }

                }

                $( '.sb_content_footer' ).append( hidden_content );
            }*/


            //for constant buttons
            if( typeof this.data( 'options' ).buttons === "object" ) {

                for( button in this.data( 'options' ).buttons ) {
                    if( typeof this.data( 'options' ).buttons[button].condition === "function" && this.data( 'options' ).buttons[button].condition.apply( jQuery(this) ) && this.data( 'options' ).buttons[button].type == 'constant' ) {
                        footer_content += '<button type="button" class="sb_' + button + '">' +
                            '<span class="sb_fonttext">' + this.data( 'options' ).buttons[button].title + '</span>' +
                        '</button>';
                    }
                }

            }

            //for different hiddens and button and custom content of footer
            if( typeof this.data( 'options' ).onFooterLoad === "function" ) {
                footer_content += this.data( 'options' ).onFooterLoad.apply( $( this ), [current_settings, current_index] );
            }

            footer_content += '</div>';

            $( '.sb_content_footer' ).append( footer_content );
        },
        setAjaxData: function( arg ) {
            return arg;
        },
        inlineBeforeLoad: function( arg ) {
            return arg;
        },
        close: function() {
            var local_options = this.data( 'options' );
            //for different hiddens and button and custom content of footer
            if( typeof local_options.onClose === "function" ) {
                local_options.onClose.apply( $( this ) );
            }

            if( this.data( 'options' ).view_type == 'lightbox' ) {
                if ( $('.sb_lightbox').length > 1 ) {
                    $('.sb_lightbox:last').fadeTo('slow', 0);
                    setTimeout(function () {
                        if (local_options.type == 'inline') {
                            $(".shutter_box_placeholder" + local_options.uniq_id).replaceWith( $( local_options.href ).hide() );
                        }
                        $(window).unbind('resize.number' + $('.sb_lightbox').length);
                        $('.sb_lightbox:last').remove();
                        $('.sb_lightbox:last').show();
                    }, 500);
                } else {
                    $('.sb_background, .sb_lightbox').fadeTo('slow', 0);
                    setTimeout(function () {
                        if (local_options.type == 'inline') {
                            $(".shutter_box_placeholder" + local_options.uniq_id).replaceWith( $( local_options.href ).hide() );
                        }
                        $('.sb_background, .sb_lightbox').remove();
                        sb_opened = false;
                    }, 500);

                    methods.destroy.apply( $( this ) );
                }
            } else {
                $('.sb_background, .sb_basic').fadeTo('slow', 0);
                setTimeout(function () {
                    if( local_options.type == 'inline' ) {
                        $(".shutter_box_placeholder" + local_options.uniq_id).replaceWith( $( local_options.href ).hide() );
                    }
                    $('.sb_background, .sb_basic').remove();
                    sb_opened = false;
                }, 500);

                methods.destroy.apply( $( this ) );
            }

            if( typeof local_options.afterClose === "function" ) {
                local_options.afterClose.apply( $( this ) );
            }
        },
        destroy : function( ) {
            $( '.sb_background, .sb_close, .sb_left, .sb_right' ).unbind( 'click' );
            $( '.sb_basic' ).unbind( 'mousemove' );
            $( 'a.shutter_box_item' ).unbind( 'each' );
        }
    };

    $.fn.shutter_box = function( method ) {
        if( methods[method] ) {
            return methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ) );
        } else if ( typeof method === 'object' || ! method ) {
            return methods.init.apply( this, arguments );
        } else {
            $.error( 'Method ' +  method + ' does not exist for jQuery.shutter_box plugin' );
        }
    };

})( jQuery );
