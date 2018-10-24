function condition_fields() {
    var first_group = 0,
        state_array = [],
        count = state_array.length,
        state = 'show';

    jQuery('.um-profile-body .um-field, .um-profile-body .um-field').each(function () {
        var conds = jQuery(this).attr('data-conds');

        if ( conds ){
            var array = JSON.parse(conds);

            jQuery.each(array, function() {
                var action = this[0],
                    field = this[1],
                    op = this[2],
                    val = this[3],
                    group = this[5],
                    depend_field;

                var input = jQuery('.um-profile-body .um-field[data-key="'+field+'"] .um-field-area input'),
                    select = jQuery('.um-profile-body .um-field[data-key="'+field+'"] .um-field-area>select'),
                    textarea = jQuery('.um-profile-body .um-field[data-key="'+field+'"] .um-field-area>textarea'),
                    content_block = jQuery('.um-profile-body .um-field[data-key="'+field+'"] .um-field-block');

                if( input.length > 0 && select.length === 0 ){

                    if( input.is(':checkbox') ){
                        var checked = jQuery('.um-profile-body .um-field[data-key="'+field+'"] input:checked');
                        checked.each(function () {
                            var checked_vals = jQuery(this).val();

                            if( checked_vals === val ){
                                depend_field = val;
                            }

                        });
                    } else if( input.is(':radio')) {
                        depend_field = jQuery('.um-profile-body .um-field[data-key="'+field+'"] input:checked').val();
                    } else {
                        depend_field = input.val();
                    }

                } else if( select.length > 0 ){

                    if( jQuery.inArray( val, select.val() ) > 0 ){
                        depend_field = val;
                    } else {
                        depend_field = select.val()
                    }

                } else if( textarea.length > 0 ){

                    depend_field = textarea.val();

                } else if( content_block.length > 0 ){

                    depend_field = content_block.text();

                }

                if( parseInt(group) !== first_group ){

                    if ( action === 'show') {

                        switch (op) {
                            case 'equals to':
                                if( depend_field == val ){
                                    state = 'show';
                                } else {
                                    state = 'hide';
                                }
                                break;

                            case 'not equals':
                                if( depend_field != val ){
                                    state = 'show';
                                } else {
                                    state = 'hide';
                                }
                                break;

                            case 'empty':
                                if( !depend_field || depend_field === '' ){
                                    state = 'show';
                                } else {
                                    state = 'hide';
                                }
                                break;

                            case 'not empty':
                                if( depend_field && depend_field !== '' ){
                                    state = 'show';
                                } else {
                                    state = 'hide';
                                }
                                break;

                            case 'greater than':
                                if( jQuery.isNumeric(val) && jQuery.isNumeric(depend_field) ){
                                    if( val > depend_field ){
                                        state = 'show'
                                    } else {
                                        state = 'hide'
                                    }
                                } else {
                                    state = 'hide';
                                }
                                break;

                            case 'less than':
                                if( jQuery.isNumeric(val) && jQuery.isNumeric(depend_field) ){
                                    if( val < depend_field ){
                                        state = 'show'
                                    } else {
                                        state = 'hide'
                                    }
                                } else {
                                    state = 'hide';
                                }
                                break;

                            case 'contains':
                                if( depend_field.search(val) >= 0 ){
                                    state = 'show';
                                } else {
                                    state = 'hide';
                                }
                                break;

                        }

                    } else { // if hide

                        switch (op) {
                            case 'equals to':
                                if (depend_field == val) {
                                    state = 'hide';
                                } else {
                                    state = 'show';
                                }
                                break;

                            case 'not equals':
                                if (depend_field != val) {
                                    state = 'hide';
                                } else {
                                    state = 'show';
                                }
                                break;

                            case 'empty':
                                if (!depend_field || depend_field === '') {
                                    state = 'hide';
                                } else {
                                    state = 'show';
                                }
                                break;

                            case 'not empty':
                                if (depend_field && depend_field !== '') {
                                    state = 'hide';
                                } else {
                                    state = 'show';
                                }
                                break;

                            case 'greater than':
                                if (jQuery.isNumeric(val) && jQuery.isNumeric(depend_field)) {
                                    if (val > depend_field) {
                                        state = 'hide'
                                    } else {
                                        state = 'show'
                                    }
                                } else {
                                    state = 'show';
                                }
                                break;

                            case 'less than':
                                if (jQuery.isNumeric(val) && jQuery.isNumeric(depend_field)) {
                                    if (val < depend_field) {
                                        state = 'hide'
                                    } else {
                                        state = 'show'
                                    }
                                } else {
                                    state = 'show';
                                }
                                break;

                            case 'contains':
                                if (depend_field.search(val) >= 0) {
                                    state = 'hide';
                                } else {
                                    state = 'show';
                                }
                                break;

                        }

                    }
                    first_group++;
                    state_array.push(state);
                } else {

                    if ( action === 'show') {

                        switch (op) {
                            case 'equals to':
                                if( depend_field == val ){
                                    state = 'show';
                                } else {
                                    state = 'not_show';
                                }
                                break;

                            case 'not equals':
                                if( depend_field != val ){
                                    state = 'show';
                                } else {
                                    state = 'not_show';
                                }
                                break;

                            case 'empty':
                                if( !depend_field || depend_field === '' ){
                                    state = 'show';
                                } else {
                                    state = 'not_show';
                                }
                                break;

                            case 'not empty':
                                if( depend_field && depend_field !== '' ){
                                    state = 'show';
                                } else {
                                    state = 'not_show';
                                }
                                break;

                            case 'greater than':
                                if( jQuery.isNumeric(val) && jQuery.isNumeric(depend_field) ){
                                    if( val > depend_field ){
                                        state = 'show'
                                    } else {
                                        state = 'not_show'
                                    }
                                } else {
                                    state = 'not_show';
                                }
                                break;

                            case 'less than':
                                if( jQuery.isNumeric(val) && jQuery.isNumeric(depend_field) ){
                                    if( val < depend_field ){
                                        state = 'show'
                                    } else {
                                        state = 'not_show'
                                    }
                                } else {
                                    state = 'not_show';
                                }
                                break;

                            case 'contains':
                                if( depend_field.search(val) >= 0 ){
                                    state = 'show';
                                } else {
                                    state = 'not_show';
                                }
                                break;

                        }

                    } else { // if hide

                        switch (op) {
                            case 'equals to':
                                if (depend_field == val) {
                                    state = 'hide';
                                } else {
                                    state = 'not_hide';
                                }
                                break;

                            case 'not equals':
                                if (depend_field != val) {
                                    state = 'hide';
                                } else {
                                    state = 'not_hide';
                                }
                                break;

                            case 'empty':
                                if (!depend_field || depend_field === '') {
                                    state = 'hide';
                                } else {
                                    state = 'not_hide';
                                }
                                break;

                            case 'not empty':
                                if (depend_field && depend_field !== '') {
                                    state = 'hide';
                                } else {
                                    state = 'not_hide';
                                }
                                break;

                            case 'greater than':
                                if (jQuery.isNumeric(val) && jQuery.isNumeric(depend_field)) {
                                    if (val > depend_field) {
                                        state = 'hide'
                                    } else {
                                        state = 'not_hide'
                                    }
                                } else {
                                    state = 'not_hide';
                                }
                                break;

                            case 'less than':
                                if (jQuery.isNumeric(val) && jQuery.isNumeric(depend_field)) {
                                    if (val < depend_field) {
                                        state = 'hide'
                                    } else {
                                        state = 'not_hide'
                                    }
                                } else {
                                    state = 'not_hide';
                                }
                                break;

                            case 'contains':
                                if (depend_field.search(val) >= 0) {
                                    state = 'hide';
                                } else {
                                    state = 'not_hide';
                                }
                                break;

                        }

                    }
                    if( state_array[count] ){
                        if( state_array[count] === 'show' || state_array[count] === 'not_hide' ){
                            if ( state === 'show' || state === 'not_hide' ){
                                state_array[count] = 'show';
                            } else {
                                state_array[count] = 'hide';
                            }
                        } else {
                            state_array[count] = 'hide';
                        }
                    } else {
                        if ( state === 'show' || state === 'not_hide' ){
                            state_array[count] = 'show';
                        } else {
                            state_array[count] = 'hide';
                        }
                    }
                }

            });

            if( jQuery.inArray( 'show', state_array ) < 0 ){
                jQuery(this).hide();
            } else {
                jQuery(this).show();
            }
        }

    });
}
jQuery(document).ready( function (){

    condition_fields();

    jQuery('.um-profile-body .um-field input, .um-profile-body .um-field textarea').on('change keyup', function () {
        condition_fields();
    });
    jQuery('.um-profile-body .um-field select').on('change', function () {
        condition_fields();
    });

});