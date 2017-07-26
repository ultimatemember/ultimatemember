<?php
if ( ! defined( 'ABSPATH' ) )
    exit; // Exit if accessed directly


/**
 * This populates all existing UM users with meta_key `last_login` as `user_registered` if the meta key doesn't exist.
 * Target Version: 1.3.39
 */

global $wpdb;

$old_options = get_option( 'um_options' );
add_option( 'um_options_backup20', $old_options );

$forms_query = new WP_Query;

$registration_forms = $forms_query->query( array(
    'post_type' => 'um_form',
    'meta_query' => array(
        'relation' => 'AND',
        array(
            'key'   => '_um_mode',
            'value' => 'register'
        ),
        array(
            'key'   => '_um_register_use_globals',
            'value' => true
        )
    ),
    'fields' => 'ids'
) );

$login_forms = $forms_query->query( array(
    'post_type' => 'um_form',
    'meta_query' => array(
        'relation' => 'AND',
        array(
            'key'   => '_um_mode',
            'value' => 'login'
        ),
        array(
            'key'   => '_um_login_use_globals',
            'value' => true
        )
    ),
    'fields' => 'ids'
) );

$profile_forms = $forms_query->query( array(
    'post_type' => 'um_form',
    'meta_query' => array(
        'relation' => 'AND',
        array(
            'key'   => '_um_mode',
            'value' => 'profile'
        ),
        array(
            'key'   => '_um_profile_use_globals',
            'value' => true
        )
    ),
    'fields' => 'ids'
) );

$css = '';

$active_color = um_get_option( 'active_color' );
if ( $active_color ) {
    $css .= "
.um .um-tip:hover,
.um .um-field-radio.active:not(.um-field-radio-state-disabled) i,
.um .um-field-checkbox.active:not(.um-field-radio-state-disabled) i,
.um .um-member-name a:hover,
.um .um-member-more a:hover,
.um .um-member-less a:hover,
.um .um-members-pagi a:hover,
.um .um-cover-add:hover,
.um .um-profile-subnav a.active,
.um .um-item-meta a,
.um-account-name a:hover,
.um-account-nav a.current,
.um-account-side li a.current span.um-account-icon,
.um-account-side li a.current:hover span.um-account-icon,
.um-dropdown li a:hover,
i.um-active-color,
span.um-active-color
{
    color: $active_color;
}

.um .um-field-group-head,
.picker__box,
.picker__nav--prev:hover,
.picker__nav--next:hover,
.um .um-members-pagi span.current,
.um .um-members-pagi span.current:hover,
.um .um-profile-nav-item.active a,
.um .um-profile-nav-item.active a:hover,
.upload,
.um-modal-header,
.um-modal-btn,
.um-modal-btn.disabled,
.um-modal-btn.disabled:hover,
div.uimob800 .um-account-side li a.current,
div.uimob800 .um-account-side li a.current:hover
{
    background: $active_color;
}
";
}

$secondary_color = um_get_option( 'secondary_color' );
if ( $secondary_color ) {
    $css .= "
.um .um-field-group-head:hover,
.picker__footer,
.picker__header,
.picker__day--infocus:hover,
.picker__day--outfocus:hover,
.picker__day--highlighted:hover,
.picker--focused .picker__day--highlighted,
.picker__list-item:hover,
.picker__list-item--highlighted:hover,
.picker--focused .picker__list-item--highlighted,
.picker__list-item--selected,
.picker__list-item--selected:hover,
.picker--focused .picker__list-item--selected {
	background: $secondary_color;
}
";
}

$css .= "
.um {
	margin-left: auto!important;
	margin-right: auto!important;
}";

$primary_btn_color = um_get_option( 'primary_btn_color' );
if ( $primary_btn_color ) {
    $css .= "
.um input[type=submit]:disabled:hover {
	background: $primary_btn_color;
}

.um input[type=submit].um-button,
.um input[type=submit].um-button:focus,
.um a.um-button,
.um a.um-button.um-disabled:hover,
.um a.um-button.um-disabled:focus,
.um a.um-button.um-disabled:active {
	background: $primary_btn_color;
}

.um a.um-link {
	color: $primary_btn_color;
}
";
}

$primary_btn_hover = um_get_option( 'primary_btn_hover' );
if ( $primary_btn_hover ) {
    $css .= "
.um input[type=submit].um-button:hover,
.um a.um-button:hover {
	background-color: $primary_btn_hover;
}

.um a.um-link:hover, 
.um a.um-link-hvr:hover {
	color: $primary_btn_hover;
}
";
}

$primary_btn_text = um_get_option( 'primary_btn_text' );
if ( $primary_btn_text ) {
    $css .= "
.um .um-button {
	color: $primary_btn_text;
}
";
}

$secondary_btn_color = um_get_option( 'secondary_btn_color' );
if ( $secondary_btn_color ) {
    $css .= "
.um .um-button.um-alt,
.um input[type=submit].um-button.um-alt {
	background: $secondary_btn_color;
}
";
}

$secondary_btn_hover = um_get_option( 'secondary_btn_hover' );
if ( $secondary_btn_hover ) {
    $css .= "
.um .um-button.um-alt:hover,
.um input[type=submit].um-button.um-alt:hover{
	background: $secondary_btn_hover;
}
";
}

$secondary_btn_text = um_get_option( 'secondary_btn_text' );
if ( $secondary_btn_text ) {
    $css .= "
.um .um-button.um-alt,
.um input[type=submit].um-button.um-alt {
	color: $secondary_btn_text;
}
";
}

$help_tip_color = um_get_option( 'help_tip_color' );
if ( $help_tip_color ) {
    $css .= "
.um .um-tip {
	color: $help_tip_color;
}
";
}

$form_field_label = um_get_option( 'form_field_label' );
if ( $form_field_label ) {
    $css .= "
.um .um-field-label {
	color: $form_field_label;
}
";
}

$form_border = um_get_option( 'form_border' );
if ( $form_border ) {
    $css .= "
.um .um-form input[type=text],
.um .um-form input[type=tel],
.um .um-form input[type=number],
.um .um-form input[type=password],
.um .um-form textarea,
.um .upload-progress,
.select2-container .select2-choice,
.select2-drop,
.select2-container-multi .select2-choices,
.select2-drop-active,
.select2-drop.select2-drop-above
{
	border: $form_border !important;
}

.um .um-form .select2-container-multi .select2-choices .select2-search-field input[type=text] {
    border: none !important
}
";
}

$form_border_hover = um_get_option( 'form_border_hover' );
if ( $form_border_hover ) {
    $css .= "
.um .um-form input[type=text]:focus,
.um .um-form input[type=tel]:focus,
.um .um-form input[type=number]:focus,
.um .um-form input[type=password]:focus,
.um .um-form .um-datepicker.picker__input.picker__input--active,
.um .um-form .um-datepicker.picker__input.picker__input--target,
.um .um-form textarea:focus {
	border: $form_border_hover !important;
}
";
}

$form_bg_color = um_get_option( 'form_bg_color' );
if ( $form_bg_color ) {
    $css .= "
.um .um-form input[type=text],
.um .um-form input[type=tel],
.um .um-form input[type=number],
.um .um-form input[type=password],
.um .um-form textarea,
.select2-container .select2-choice,
.select2-container-multi .select2-choices
{
	background-color: $form_bg_color;
}
";
}

$form_bg_color_focus = um_get_option( 'form_bg_color_focus' );
if ( $form_bg_color_focus ) {
    $css .= "
.um .um-form input[type=text]:focus,
.um .um-form input[type=tel]:focus,
.um .um-form input[type=number]:focus,
.um .um-form input[type=password]:focus,
.um .um-form textarea:focus {
	background-color: $form_bg_color_focus;
}
";
}

$form_text_color = um_get_option( 'form_text_color' );
if ( $form_text_color ) {
    $css .= "
.um .um-form input[type=text],
.um .um-form input[type=tel],
.um .um-form input[type=password],
.um .um-form textarea
{
	color: $form_text_color;
}

.um .um-form input:-webkit-autofill {
    -webkit-box-shadow:0 0 0 50px white inset; /* Change the color to your own background color */
    -webkit-text-fill-color: $form_text_color;
}

.um .um-form input:-webkit-autofill:focus {
    -webkit-box-shadow: none,0 0 0 50px white inset;
    -webkit-text-fill-color: $form_text_color;
}
";
}

$form_placeholder = um_get_option( 'form_placeholder' );
if ( $form_placeholder ) {
    $css .= "
.um .um-form ::-webkit-input-placeholder
{
	color:  $form_placeholder;
	opacity: 1 !important;
}

.um .um-form ::-moz-placeholder
{
	color:  $form_placeholder;
	opacity: 1 !important;
}

.um .um-form ::-moz-placeholder
{
	color:  $form_placeholder;
	opacity: 1 !important;
}

.um .um-form ::-ms-input-placeholder
{
	color:  $form_placeholder;
	opacity: 1 !important;
}

.select2-default,
.select2-default *,
.select2-container-multi .select2-choices .select2-search-field input
{
	color:  $form_placeholder;
}
";
}

$form_icon_color = um_get_option( 'form_icon_color' );
if ( $form_icon_color ) {
    $css .= "
.um .um-field-icon i,
.select2-container .select2-choice .select2-arrow:before,
.select2-search:before,
.select2-search-choice-close:before
{
	color: $form_icon_color;
}
";
}

$form_asterisk_color = um_get_option( 'form_asterisk_color' );
if ( $form_asterisk_color ) {
    $css .= "
.um span.um-req
{
	color: $form_asterisk_color;
}
";
}


$profile_photocorner = um_get_option( 'profile_photocorner' );
if ( $profile_photocorner == 1 ) {
    $css .= "
.um .um-profile-photo a.um-profile-photo-img,
.um .um-profile-photo img,
.um .um-profile-photo span.um-profile-photo-overlay
{ 
    -moz-border-radius: 999px !important; 
    -webkit-border-radius: 999px !important; 
    border-radius: 999px !important 
}
";
} else if ( $profile_photocorner == 2 ) {
    $css .= "
.um .um-profile-photo a.um-profile-photo-img,
.um .um-profile-photo img,
.um .um-profile-photo span.um-profile-photo-overlay
{ 
    -moz-border-radius: 4px !important; 
    -webkit-border-radius: 4px !important; 
    border-radius: 4px !important 
}
";
} else if ( $profile_photocorner == 3 ) {
    $css .= "
.um .um-profile-photo a.um-profile-photo-img,
.um .um-profile-photo img,
.um .um-profile-photo span.um-profile-photo-overlay
{ 
    -moz-border-radius: 0px !important; 
    -webkit-border-radius: 0px !important; 
    border-radius: 0px !important 
}
";
}

$profile_main_bg = um_get_option( 'profile_main_bg' );
if ( $profile_main_bg ) {
    $css .= "
.um-profile {
	background-color: $profile_main_bg;
}
";
}

$profile_header_bg = um_get_option( 'profile_header_bg' );
if ( $profile_header_bg ) {
    $css .= "
.um-profile.um .um-header {
	background-color: $profile_header_bg;
}
";
}

$profile_header_text = um_get_option( 'profile_header_text' );
if ( $profile_header_text ) {
    $css .= "
.um-profile.um .um-profile-meta {
	color: $profile_header_text;
}
";
}

$profile_header_link_color = um_get_option( 'profile_header_link_color' );
if ( $profile_header_link_color ) {
    $css .= "
.um-profile.um .um-name a {
	color: $profile_header_link_color;
}
";
}

$profile_header_link_hcolor = um_get_option( 'profile_header_link_hcolor' );
if ( $profile_header_link_hcolor ) {
    $css .= "
.um-profile.um .um-name a:hover {
	color: $profile_header_link_hcolor;
}
";
}

$profile_header_icon_color = um_get_option( 'profile_header_icon_color' );
if ( $profile_header_icon_color ) {
    $css .= "
.um-profile.um .um-profile-headericon a {
	color: $profile_header_icon_color;
}
";
}

$profile_header_icon_hcolor = um_get_option( 'profile_header_icon_hcolor' );
if ( $profile_header_icon_hcolor ) {
    $css .= "
.um-profile.um .um-profile-headericon a:hover,
.um-profile.um .um-profile-edit-a.active {
	color: $profile_header_icon_hcolor;
}
";
}


foreach ( $registration_forms as $form_id ) {

    $align = get_post_meta( $form_id, '_um_register_align', true );
    if ( $align && $align != 'center' ) {
        $css .= "
.um-$form_id.um {
    float: $align;
}
";
    }

    $primary_btn_color = get_post_meta( $form_id, '_um_register_primary_btn_color', true );
    if ( $primary_btn_color ) {
        $css .= "
.um-$form_id.um input[type=submit]:disabled:hover {
	background: $primary_btn_color;
}

.um-$form_id.um input[type=submit].um-button,
.um-$form_id.um input[type=submit].um-button:focus,
.um-$form_id.um a.um-button,
.um-$form_id.um a.um-button.um-disabled:hover,
.um-$form_id.um a.um-button.um-disabled:focus,
.um-$form_id.um a.um-button.um-disabled:active {
	background: $primary_btn_color;
}

.um-$form_id.um a.um-link {
	color: $primary_btn_color;
}
";
    }

    $primary_btn_hover = get_post_meta( $form_id, '_um_register_primary_btn_hover', true );
    if ( $primary_btn_hover ) {
        $css .= "
.um-$form_id.um input[type=submit].um-button:hover,
.um-$form_id.um a.um-button:hover {
	background-color: $primary_btn_hover;
}

.um-$form_id.um a.um-link:hover, 
.um-$form_id.um a.um-link-hvr:hover {
	color: $primary_btn_hover;
}
";
    }

    $primary_btn_text = get_post_meta( $form_id, '_um_register_primary_btn_text', true );
    if ( $primary_btn_text ) {
        $css .= "
.um-$form_id.um .um-button {
	color: $primary_btn_text;
}
";
    }

    $secondary_button = get_post_meta( $form_id, '_um_register_secondary_btn', true );
    if ( $secondary_button ) {

        $secondary_btn_color = get_post_meta( $form_id, '_um_register_secondary_btn_color', true );
        if ( $secondary_btn_color ) {
            $css .= "
.um-$form_id.um .um-button.um-alt,
.um-$form_id.um input[type=submit].um-button.um-alt {
	background: $secondary_btn_color;
}
";
        }

        $secondary_btn_hover = get_post_meta( $form_id, '_um_register_secondary_btn_hover', true );
        if ( $secondary_btn_hover ) {
            $css .= "
.um-$form_id.um .um-button.um-alt:hover,
.um-$form_id.um input[type=submit].um-button.um-alt:hover{
	background: $secondary_btn_hover;
}
";
        }

        $secondary_btn_text = get_post_meta( $form_id, '_um_register_secondary_btn_text', true );
        if ( $secondary_btn_text ) {
            $css .= "
.um-$form_id.um .um-button.um-alt,
.um-$form_id.um input[type=submit].um-button.um-alt {
	color: $secondary_btn_text;
}
";
        }
    }
}


foreach ( $login_forms as $form_id ) {

    $align = get_post_meta( $form_id, '_um_login_align', true );
    if ( $align && $align != 'center' ) {
        $css .= "
.um-$form_id.um {
    float: $align;
}
";
    }

    $primary_btn_color = get_post_meta( $form_id, '_um_login_primary_btn_color', true );
    if ( $primary_btn_color ) {
        $css .= "
.um-$form_id.um input[type=submit]:disabled:hover {
	background: $primary_btn_color;
}

.um-$form_id.um input[type=submit].um-button,
.um-$form_id.um input[type=submit].um-button:focus,
.um-$form_id.um a.um-button,
.um-$form_id.um a.um-button.um-disabled:hover,
.um-$form_id.um a.um-button.um-disabled:focus,
.um-$form_id.um a.um-button.um-disabled:active {
	background: $primary_btn_color;
}

.um-$form_id.um a.um-link {
	color: $primary_btn_color;
}
";
    }

    $primary_btn_hover = get_post_meta( $form_id, '_um_login_primary_btn_hover', true );
    if ( $primary_btn_hover ) {
        $css .= "
.um-$form_id.um input[type=submit].um-button:hover,
.um-$form_id.um a.um-button:hover {
	background-color: $primary_btn_hover;
}

.um-$form_id.um a.um-link:hover, 
.um-$form_id.um a.um-link-hvr:hover {
	color: $primary_btn_hover;
}
";
    }

    $primary_btn_text = get_post_meta( $form_id, '_um_login_primary_btn_text', true );
    if ( $primary_btn_text ) {
        $css .= "
.um-$form_id.um .um-button {
	color: $primary_btn_text;
}
";
    }

    $secondary_button = get_post_meta( $form_id, '_um_login_secondary_btn', true );
    if ( $secondary_button ) {

        $secondary_btn_color = get_post_meta( $form_id, '_um_login_secondary_btn_color', true );
        if ( $secondary_btn_color ) {
            $css .= "
.um-$form_id.um .um-button.um-alt,
.um-$form_id.um input[type=submit].um-button.um-alt {
	background: $secondary_btn_color;
}
";
        }

        $secondary_btn_hover = get_post_meta( $form_id, '_um_login_secondary_btn_hover', true );
        if ( $secondary_btn_hover ) {
            $css .= "
.um-$form_id.um .um-button.um-alt:hover,
.um-$form_id.um input[type=submit].um-button.um-alt:hover{
	background: $secondary_btn_hover;
}
";
        }

        $secondary_btn_text = get_post_meta( $form_id, '_um_login_secondary_btn_text', true );
        if ( $secondary_btn_text ) {
            $css .= "
.um-$form_id.um .um-button.um-alt,
.um-$form_id.um input[type=submit].um-button.um-alt {
	color: $secondary_btn_text;
}
";
        }
    }
}


foreach ( $profile_forms as $form_id ) {
    $align = get_post_meta( $form_id, '_um_profile_align', true );
    if ( $align && $align != 'center' ) {
        $css .= "
.um-$form_id.um {
    float: $align;
}
";
    }

    $primary_btn_color = get_post_meta( $form_id, '_um_profile_primary_btn_color', true );
    if ( $primary_btn_color ) {
        $css .= "
.um-$form_id.um input[type=submit]:disabled:hover {
	background: $primary_btn_color;
}

.um-$form_id.um input[type=submit].um-button,
.um-$form_id.um input[type=submit].um-button:focus,
.um-$form_id.um a.um-button,
.um-$form_id.um a.um-button.um-disabled:hover,
.um-$form_id.um a.um-button.um-disabled:focus,
.um-$form_id.um a.um-button.um-disabled:active {
	background: $primary_btn_color;
}

.um-$form_id.um a.um-link {
	color: $primary_btn_color;
}
";
    }

    $primary_btn_hover = get_post_meta( $form_id, '_um_profile_primary_btn_hover', true );
    if ( $primary_btn_hover ) {
        $css .= "
.um-$form_id.um input[type=submit].um-button:hover,
.um-$form_id.um a.um-button:hover {
	background-color: $primary_btn_hover;
}

.um-$form_id.um a.um-link:hover, 
.um-$form_id.um a.um-link-hvr:hover {
	color: $primary_btn_hover;
}
";
    }

    $primary_btn_text = get_post_meta( $form_id, '_um_profile_primary_btn_text', true );
    if ( $primary_btn_text ) {
        $css .= "
.um-$form_id.um .um-button {
	color: $primary_btn_text;
}
";
    }

    $secondary_button = get_post_meta( $form_id, '_um_profile_secondary_btn', true );
    if ( $secondary_button ) {

        $secondary_btn_color = get_post_meta( $form_id, '_um_profile_secondary_btn_color', true );
        if ( $secondary_btn_color ) {
            $css .= "
.um-$form_id.um .um-button.um-alt,
.um-$form_id.um input[type=submit].um-button.um-alt {
	background: $secondary_btn_color;
}
";
        }

        $secondary_btn_hover = get_post_meta( $form_id, '_um_profile_secondary_btn_hover', true );
        if ( $secondary_btn_hover ) {
            $css .= "
.um-$form_id.um .um-button.um-alt:hover,
.um-$form_id.um input[type=submit].um-button.um-alt:hover{
	background: $secondary_btn_hover;
}
";
        }

        $secondary_btn_text = get_post_meta( $form_id, '_um_profile_secondary_btn_text', true );
        if ( $secondary_btn_text ) {
            $css .= "
.um-$form_id.um .um-button.um-alt,
.um-$form_id.um input[type=submit].um-button.um-alt {
	color: $secondary_btn_text;
}
";
        }
    }

    $profile_photocorner = get_post_meta( $form_id, '_um_profile_photocorner', true );
    if ( $profile_photocorner == 1 ) {
        $css .= "
.um-$form_id.um .um-profile-photo a.um-profile-photo-img,
.um-$form_id.um .um-profile-photo img,
.um-$form_id.um .um-profile-photo span.um-profile-photo-overlay
{ 
    -moz-border-radius: 999px !important; 
    -webkit-border-radius: 999px !important; 
    border-radius: 999px !important 
}
";
    } else if ( $profile_photocorner == 2 ) {
        $css .= "
.um-$form_id.um .um-profile-photo a.um-profile-photo-img,
.um-$form_id.um .um-profile-photo img,
.um-$form_id.um .um-profile-photo span.um-profile-photo-overlay
{ 
    -moz-border-radius: 4px !important; 
    -webkit-border-radius: 4px !important; 
    border-radius: 4px !important 
}
";
    } else if ( $profile_photocorner == 3 ) {
        $css .= "
.um-$form_id.um .um-profile-photo a.um-profile-photo-img,
.um-$form_id.um .um-profile-photo img,
.um-$form_id.um .um-profile-photo span.um-profile-photo-overlay
{ 
    -moz-border-radius: 0px !important; 
    -webkit-border-radius: 0px !important; 
    border-radius: 0px !important 
}
";
    }

    $profile_main_bg = get_post_meta( $form_id, '_um_profile_main_bg', true );
    if ( $profile_main_bg ) {
        $css .= "
.um-$form_id.um-profile {
	background-color: $profile_main_bg;
}
";
    }

    $main_text_color = get_post_meta( $form_id, '_um_profile_main_text_color', true );
    if ( $main_text_color ) {
        $css .= "
.um-$form_id.um .um-profile-body.main *{
	color: $main_text_color;
}
";
    }

    $profile_header_bg = get_post_meta( $form_id, '_um_profile_header_bg', true );
    if ( $profile_header_bg ) {
        $css .= "
.um-$form_id.um .um-header {
	background-color: $profile_header_bg;
}
";
    }

    $profile_header_text = get_post_meta( $form_id, '_um_profile_header_text', true );
    if ( $profile_header_text ) {
        $css .= "
.um-$form_id.um .um-profile-meta {
	color: $profile_header_text;
}
";
    }


    $profile_header_link_color = get_post_meta( $form_id, '_um_profile_header_link_color', true );
    if ( $profile_header_link_color ) {
        $css .= "
.um-$form_id.um .um-name a {
	color: $profile_header_link_color;
}
";
    }

    $profile_header_link_hcolor = get_post_meta( $form_id, '_um_profile_header_link_hcolor', true );
    if ( $profile_header_link_hcolor ) {
        $css .= "
.um-$form_id.um .um-name a:hover {
	color: $profile_header_link_hcolor;
}
";
    }

    $profile_header_icon_color = get_post_meta( $form_id, '_um_profile_header_icon_color', true );
    if ( $profile_header_icon_color ) {
        $css .= "
.um-$form_id.um .um-profile-headericon a {
	color: $profile_header_icon_color;
}
";
    }

    $profile_header_icon_hcolor = get_post_meta( $form_id, '_um_profile_header_icon_hcolor', true );
    if ( $profile_header_icon_hcolor ) {
        $css .= "
.um-$form_id.um .um-profile-headericon a:hover,
.um-$form_id.um .um-profile-edit-a.active {
	color: $profile_header_icon_hcolor;
}
";
    }
}

$uploads        = wp_upload_dir();
$upload_dir     = $uploads['basedir'] . DIRECTORY_SEPARATOR . 'ultimatemember' . DIRECTORY_SEPARATOR;
$css_doc_file   = fopen( $upload_dir. 'um_old_settings.css', 'w+' );
fwrite( $css_doc_file, $css );
fclose( $css_doc_file );

//remove old options
um_remove_option( 'active_color' );
um_remove_option( 'secondary_color' );
um_remove_option( 'profile_align' );
um_remove_option( 'register_align' );
um_remove_option( 'login_align' );
um_remove_option( 'primary_btn_color' );
um_remove_option( 'primary_btn_hover' );
um_remove_option( 'primary_btn_text' );
um_remove_option( 'secondary_btn_color' );
um_remove_option( 'secondary_btn_hover' );
um_remove_option( 'secondary_btn_text' );
um_remove_option( 'help_tip_color' );
um_remove_option( 'form_field_label' );
um_remove_option( 'form_border' );
um_remove_option( 'form_border_hover' );
um_remove_option( 'form_bg_color' );
um_remove_option( 'form_bg_color_focus' );
um_remove_option( 'form_text_color' );
um_remove_option( 'form_placeholder' );
um_remove_option( 'form_icon_color' );
um_remove_option( 'form_asterisk_color' );
um_remove_option( 'profile_photocorner' );
um_remove_option( 'profile_main_bg' );
um_remove_option( 'profile_header_bg' );
um_remove_option( 'profile_header_text' );
um_remove_option( 'profile_header_link_color' );
um_remove_option( 'profile_header_link_hcolor' );
um_remove_option( 'profile_header_icon_color' );
um_remove_option( 'profile_header_icon_hcolor' );

//remove duplicates for UM Pages settings
delete_option( 'um_core_pages' );



//UM Roles to WP Roles
//all UM Roles from post type
$role_keys = array();

register_post_type( 'um_role', array(
    'labels' => array(
        'name'                  => __( 'User Roles' ),
        'singular_name'         => __( 'User Role' ),
        'add_new'               => __( 'Add New' ),
        'add_new_item'          => __('Add New User Role' ),
        'edit_item'             => __('Edit User Role'),
        'not_found'             => __('You did not create any user roles yet'),
        'not_found_in_trash'    => __('Nothing found in Trash'),
        'search_items'          => __('Search User Roles')
    ),
    'show_ui' => true,
    'show_in_menu' => false,
    'public' => false,
    'supports' => array('title')
) );


$um_roles = get_posts( array(
    'post_type'         => 'um_role',
    'posts_per_page'    => -1,
    'post_status'       => 'publish'
) );


if ( ! empty( $um_roles ) ) {
    foreach ( $um_roles as $um_role ) {
        $role_key = sanitize_title( $um_role->post_title );

        $role_keys[] = $role_key;

        $all_role_metadata = $wpdb->get_results( $wpdb->prepare(
            "SELECT pm.meta_key,
                    pm.meta_value
            FROM {$wpdb->postmeta} pm 
            WHERE pm.post_id = %d AND 
                  pm.meta_key LIKE %s",
            $um_role->ID,
            "_um_%"
        ), ARRAY_A );

        $role_metadata = array();
        if ( ! empty( $all_role_metadata ) ) {
            foreach( $all_role_metadata as $metadata ) {
                $role_metadata[$metadata['meta_key']] = $metadata['meta_value'];
            }
        }

        $role_meta = array_merge( $role_metadata, array(
            'name'         => $um_role->post_title,
            '_um_is_custom' => true,
        ) );

        if ( ! empty( $role_meta['_um_core'] ) )
            unset( $role_meta['_um_core'] );

        update_option( "um_role_{$role_key}_meta", $role_meta );
    }


    //get all users with UM role
    $args = array(
        'meta_query'   => array(
            array(
                'key'       => 'role',
                'compare'   => 'EXISTS',
            )
        ),
        'number'       => '',
        'count_total'  => false,
    );
    $all_users = get_users( $args );
    //update roles for users
    foreach ( $all_users as $k => $user ) {
        $user_roleID = get_user_meta( $user->ID, 'role', true );

        if ( ! empty( $user_roleID ) ) {
            $userdata = get_userdata( $user->ID );
            $userdata->add_role( 'um_' . $user_roleID );
        }
    }
}

update_option( 'um_roles', $role_keys );



//Content Restriction transfer
$roles_array = UM()->roles()->get_roles( false, array( 'administrator' ) );

$posts = get_posts( array(
    'meta_key'      => '_um_custom_access_settings',
    'meta_value'    => '1',
    'fields'        => 'ids'
) );
if ( ! empty( $posts ) ) {
    foreach ( $posts as $post_id ) {
        $um_accessible = get_post_meta( $post_id, '_um_accessible', true );
        $um_access_roles = get_post_meta( $post_id, '_um_access_roles', true );
        $um_access_redirect = ( $um_accessible == '2' ) ? get_post_meta( $post_id, '_um_access_redirect', true ) : get_post_meta( $post_id, '_um_access_redirect2', true );

        $access_roles = array();
        if ( ! empty( $um_access_roles ) ) {
            foreach ( $roles_array as $role ) {
                if ( in_array( substr( $role, 3 ), $um_access_roles ) )
                    $access_roles[$role] = '1';
                else
                    $access_roles[$role] = '0';
            }
        } else {
            foreach ( $roles_array as $role ) {
                $access_roles[$role] = '0';
            }
        }
        $restrict_options = array(
            '_um_custom_access_settings'        => '1',
            '_um_accessible'                    => $um_accessible,
            '_um_access_roles'                  => $access_roles,
            '_um_noaccess_action'               => '1',
            '_um_restrict_by_custom_message'    => '0',
            '_um_restrict_custom_message'       => '',
            '_um_access_redirect'               => '1',
            '_um_access_redirect_url'           => ! empty( $um_access_redirect ) ? $um_access_redirect : '',
            '_um_access_hide_from_queries'      => '0',
        );

        update_post_meta( $post_id, 'um_content_restriction', $restrict_options );
    }
}


$all_taxonomies = get_taxonomies( array( 'public' => true ) );
$exclude_taxonomies = array(
    'nav_menu',
    'link_category',
    'post_format',
    'um_user_tag',
    'um_hashtag',
);

foreach ( $all_taxonomies as $key => $taxonomy ) {
    if ( in_array( $key , $exclude_taxonomies ) )
        continue;

    $terms = get_terms( array(
        'taxonomy'      => $taxonomy,
        'hide_empty'    => false,
        'fields'        => 'ids'
    ) );

    foreach ( $terms as $term_id ) {
        $term_meta = get_option( "{$taxonomy}_{$term_id}" );

        if ( empty( $term_meta ) )
            continue;

        $um_accessible = $term_meta['_um_accessible'];
        $um_access_roles = $term_meta['_um_access_roles'];
        $um_access_redirect = ( $um_accessible == '2' ) ? $term_meta['_um_access_redirect'] : $term_meta['_um_access_redirect2'];

        $access_roles = array();
        if ( ! empty( $um_access_roles ) ) {
            foreach ( $roles_array as $role ) {
                if ( in_array( substr( $role, 3 ), $um_access_roles ) )
                    $access_roles[$role] = '1';
                else
                    $access_roles[$role] = '0';
            }
        } else {
            foreach ( $roles_array as $role ) {
                $access_roles[$role] = '0';
            }
        }

        $restrict_options = array(
            '_um_custom_access_settings'        => '1',
            '_um_accessible'                    => $um_accessible,
            '_um_access_roles'                  => $access_roles,
            '_um_noaccess_action'               => '1',
            '_um_restrict_by_custom_message'    => '0',
            '_um_restrict_custom_message'       => '',
            '_um_access_redirect'               => '1',
            '_um_access_redirect_url'           => ! empty( $um_access_redirect ) ? $um_access_redirect : '',
            '_um_access_hide_from_queries'      => '0',
        );

        update_term_meta( $term_id, 'um_content_restriction', $restrict_options );
    }
}



//for metadata for all UM forms
$forms = get_posts( array(
    'post_type'     => 'um_form',
    'numberposts'   => -1,
    'fields'        => 'ids'
) );

foreach ( $forms as $form_id ) {
    $form_type = get_post_meta( $form_id, '_um_mode', true );

    if ( ! empty( $form_type ) ) {
        $use_globals = get_post_meta( $form_id, "_um_{$form_type}_use_globals", true );
        $use_custom_settings = empty( $use_globals ) ? true : false;

        update_post_meta( $form_id, "_um_{$form_type}_use_custom_settings", $use_custom_settings );
        delete_post_meta( $form_id, "_um_{$form_type}_use_globals" );
    }
}