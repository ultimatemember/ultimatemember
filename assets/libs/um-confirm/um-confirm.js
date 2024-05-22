/*
 * WPO Confirm Plugin
 * Open dialog popup (YES/NO)
 */

(function( $, undefined ) {
    var options;

    var default_options = {
        'message' : '',
        'yes_label' : 'Yes',
        'no_label' : 'No'
    };

    var methods = {
        init : function( settings ) {
            //merge default & current options
            options = $.extend( {}, default_options, settings );

            $( this ).each( function() {

                $( this ).data( 'options', options );

                methods.build.apply( $( this ), [options] );

                //init links clicks for show confirm
                $( this ).click( function(e) {
                    var options = $( this ).data( 'options' );
                    $( '#um_confirm_message' ).html( options.message );
                    $( '#um_confirm_button_yes' ).html( options.yes_label );
                    $( '#um_confirm_button_no' ).html( options.no_label );

                    methods.show.apply( this );

                    e.stopPropagation();
                });

            });

        },
        build : function( settings ) {

            if( !methods.is_builded.apply( this ) ) {

                var obj = $( '<div id="um_confirm_block"></div>').appendTo( 'body' ).html( '<div class="um_confirm">' +
                    '<div id="um_confirm_title">Confirmation</div>' +
                '<div id="um_confirm_message"></div>' +
                    '<div id="um_confirm_buttons">' +
                    '<div id="um_confirm_button_yes" class="um_confirm_button">Yes</div>' +
                '<div id="um_confirm_button_no" class="um_confirm_button">No</div>' +
                '</div>' +
                '</div>' +
                '<div id="um_confirm_block_back"></div>' );

                $( document ).on( 'click', '#um_confirm_button_yes', function() {
                    var obj = $( '#um_confirm_block').data( 'obj' );
                    methods.yes.apply( obj );
                });

                $( document ).on( 'click', '#um_confirm_button_no', function() {
                    var obj = $( '#um_confirm_block').data( 'obj' );
                    methods.no.apply( obj );
                });

                $( document ).on( 'click', '#um_confirm_block_back', function() {
                    var obj = $( '#um_confirm_block').data( 'obj' );
                    methods.close.apply( obj );
                });


            }
        },
        is_builded : function() {
            //return confirm already exists
            return $('#um_confirm_block').length;
        },
        show : function() {
            $( '#um_confirm_block').data( 'obj', this ).show();
            var width = $('.um_confirm').width();
            var height = $('.um_confirm').height();
            $('.um_confirm').css('margin', '-' + height/2 + 'px 0 0 -' + width/2 + 'px' );
        },
        close : function() {
            var opt = $( this ).data( 'options' );

            $( '#um_confirm_message' ).html( '' );
            $( '#um_confirm_block' ).hide();

            if( typeof opt.onClose === "function" ) {
                opt.onClose.apply( this );
            }
        },
        yes : function() {
            var opt = $( this ).data( 'options' );

            var data = {};
            if( $( '#um_confirm_block').find('form').length ) {
                var temp = $( '#um_confirm_block').find('form').serializeArray();
                for( key in temp ) {
                    data[ temp[ key ]['name'] ] = temp[ key ]['value'];
                }
            }

            methods.close.apply( this );

            if( typeof opt.onYes === "function" ) {
                opt.onYes.apply( this, [ data ] );
            }
        },
        no : function() {
            var opt = $( this ).data( 'options' );

            var data = {};
            if( $( '#um_confirm_block').find('form').length ) {
                var temp = $( '#um_confirm_block').find('form').serializeArray();
                for( key in temp ) {
                    data[ temp[ key ]['name'] ] = temp[ key ]['value'];
                }
            }

            methods.close.apply( this );

            if( typeof opt.onNo === "function" ) {
                opt.onNo.apply( this, [ data ] );
            }
        }
    };

    $.fn.um_confirm = function( method ) {
        if( methods[method] ) {
            return methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ) );
        } else if ( typeof method === 'object' || ! method ) {
            return methods.init.apply( this, arguments );
        } else {
            $.error( 'Method ' +  method + ' does not exist for jQuery.um_confirm plugin' );
        }
    };

    $.um_confirm = function( settings ) {
        options = $.extend( {}, default_options, settings );
        $( settings.object ).data( 'options', options );


        methods.build.apply( $( settings.object ), [options] );
        if ( options.title ) {
            $( '#um_confirm_title' ).html( options.title );
        }
        $( '#um_confirm_message' ).html( options.message );
        $( '#um_confirm_button_yes' ).html( options.yes_label );
        $( '#um_confirm_button_no' ).html( options.no_label );
        methods.show.apply( settings.object );
    }

})( jQuery );
