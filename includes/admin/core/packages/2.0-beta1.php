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

$active_color = UM()->options()->get( 'active_color' );
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

$secondary_color = UM()->options()->get( 'secondary_color' );
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

$primary_btn_color = UM()->options()->get( 'primary_btn_color' );
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

$primary_btn_hover = UM()->options()->get( 'primary_btn_hover' );
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

$primary_btn_text = UM()->options()->get( 'primary_btn_text' );
if ( $primary_btn_text ) {
	$css .= "
.um .um-button {
	color: $primary_btn_text;
}
";
}

$secondary_btn_color = UM()->options()->get( 'secondary_btn_color' );
if ( $secondary_btn_color ) {
	$css .= "
.um .um-button.um-alt,
.um input[type=submit].um-button.um-alt {
	background: $secondary_btn_color;
}
";
}

$secondary_btn_hover = UM()->options()->get( 'secondary_btn_hover' );
if ( $secondary_btn_hover ) {
	$css .= "
.um .um-button.um-alt:hover,
.um input[type=submit].um-button.um-alt:hover{
	background: $secondary_btn_hover;
}
";
}

$secondary_btn_text = UM()->options()->get( 'secondary_btn_text' );
if ( $secondary_btn_text ) {
	$css .= "
.um .um-button.um-alt,
.um input[type=submit].um-button.um-alt {
	color: $secondary_btn_text;
}
";
}

$help_tip_color = UM()->options()->get( 'help_tip_color' );
if ( $help_tip_color ) {
	$css .= "
.um .um-tip {
	color: $help_tip_color;
}
";
}

$form_field_label = UM()->options()->get( 'form_field_label' );
if ( $form_field_label ) {
	$css .= "
.um .um-field-label {
	color: $form_field_label;
}
";
}

$form_border = UM()->options()->get( 'form_border' );
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

$form_border_hover = UM()->options()->get( 'form_border_hover' );
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

$form_bg_color = UM()->options()->get( 'form_bg_color' );
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

$form_bg_color_focus = UM()->options()->get( 'form_bg_color_focus' );
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

$form_text_color = UM()->options()->get( 'form_text_color' );
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

$form_placeholder = UM()->options()->get( 'form_placeholder' );
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

$form_icon_color = UM()->options()->get( 'form_icon_color' );
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

$form_asterisk_color = UM()->options()->get( 'form_asterisk_color' );
if ( $form_asterisk_color ) {
	$css .= "
.um span.um-req
{
	color: $form_asterisk_color;
}
";
}


$profile_photocorner = UM()->options()->get( 'profile_photocorner' );
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

$profile_main_bg = UM()->options()->get( 'profile_main_bg' );
if ( $profile_main_bg ) {
	$css .= "
.um-profile {
	background-color: $profile_main_bg;
}
";
}

$profile_header_bg = UM()->options()->get( 'profile_header_bg' );
if ( $profile_header_bg ) {
	$css .= "
.um-profile.um .um-header {
	background-color: $profile_header_bg;
}
";
}

$profile_header_text = UM()->options()->get( 'profile_header_text' );
if ( $profile_header_text ) {
	$css .= "
.um-profile.um .um-profile-meta {
	color: $profile_header_text;
}
";
}

$profile_header_link_color = UM()->options()->get( 'profile_header_link_color' );
if ( $profile_header_link_color ) {
	$css .= "
.um-profile.um .um-name a {
	color: $profile_header_link_color;
}
";
}

$profile_header_link_hcolor = UM()->options()->get( 'profile_header_link_hcolor' );
if ( $profile_header_link_hcolor ) {
	$css .= "
.um-profile.um .um-name a:hover {
	color: $profile_header_link_hcolor;
}
";
}

$profile_header_icon_color = UM()->options()->get( 'profile_header_icon_color' );
if ( $profile_header_icon_color ) {
	$css .= "
.um-profile.um .um-profile-headericon a {
	color: $profile_header_icon_color;
}
";
}

$profile_header_icon_hcolor = UM()->options()->get( 'profile_header_icon_hcolor' );
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
UM()->options()->remove( 'active_color' );
UM()->options()->remove( 'secondary_color' );
UM()->options()->remove( 'profile_align' );
UM()->options()->remove( 'register_align' );
UM()->options()->remove( 'login_align' );
UM()->options()->remove( 'primary_btn_color' );
UM()->options()->remove( 'primary_btn_hover' );
UM()->options()->remove( 'primary_btn_text' );
UM()->options()->remove( 'secondary_btn_color' );
UM()->options()->remove( 'secondary_btn_hover' );
UM()->options()->remove( 'secondary_btn_text' );
UM()->options()->remove( 'help_tip_color' );
UM()->options()->remove( 'form_field_label' );
UM()->options()->remove( 'form_border' );
UM()->options()->remove( 'form_border_hover' );
UM()->options()->remove( 'form_bg_color' );
UM()->options()->remove( 'form_bg_color_focus' );
UM()->options()->remove( 'form_text_color' );
UM()->options()->remove( 'form_placeholder' );
UM()->options()->remove( 'form_icon_color' );
UM()->options()->remove( 'form_asterisk_color' );
UM()->options()->remove( 'profile_photocorner' );
UM()->options()->remove( 'profile_main_bg' );
UM()->options()->remove( 'profile_header_bg' );
UM()->options()->remove( 'profile_header_text' );
UM()->options()->remove( 'profile_header_link_color' );
UM()->options()->remove( 'profile_header_link_hcolor' );
UM()->options()->remove( 'profile_header_icon_color' );
UM()->options()->remove( 'profile_header_icon_hcolor' );

//remove duplicates for UM Pages settings
delete_option( 'um_core_pages' );

global $wpdb;

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

$roles_associations = array();

$all_wp_roles = array_keys( get_editable_roles() );
if ( ! empty( $um_roles ) ) {
	foreach ( $um_roles as $um_role ) {

		//old role key which inserted for each user to usermeta "role"
		$key_in_meta = $um_role->post_name;

		if ( preg_match( "/[a-z0-9]+$/i", $um_role->post_title ) ) {
			$role_key = sanitize_title( $um_role->post_title );
		} else {
			$auto_increment = UM()->options()->get( 'custom_roles_increment' );
			$auto_increment = ! empty( $auto_increment ) ? $auto_increment : 1;
			$role_key = 'custom_role_' . $auto_increment;

			$auto_increment++;
			UM()->options()->update( 'custom_roles_increment', $auto_increment );
		}

		if ( ! in_array( $role_key, $all_wp_roles ) ) {
			$role_keys[] = $role_key;
		}

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
			foreach ( $all_role_metadata as $metadata ) {

				if ( '_um_can_edit_roles' == $metadata['meta_key'] || '_um_can_delete_roles' == $metadata['meta_key']
				     || '_um_can_view_roles' == $metadata['meta_key'] || '_um_can_follow_roles' == $metadata['meta_key']
				     || '_um_can_friend_roles' == $metadata['meta_key'] || '_um_can_review_roles' == $metadata['meta_key'] ) {
					$metadata['meta_value'] = maybe_unserialize( $metadata['meta_value'] );
				}

				$role_metadata[ $metadata['meta_key'] ] = $metadata['meta_value'];
			}
		}

		if ( ! in_array( $role_key, $all_wp_roles ) ) {
			$role_meta = array_merge( $role_metadata, array(
				'name'              => $um_role->post_title,
				'wp_capabilities'   => array( 'read' => true ),
				'_um_is_custom'     => true,
			) );
		} else {
			$role_meta = $role_metadata;
		}

		//$old_key = ! empty( $role_meta['_um_core'] ) ? $role_meta['_um_core'] : $role_key;
		if ( ! in_array( $role_key, $all_wp_roles ) ) {
			$roles_associations[ $key_in_meta ] = 'um_' . $role_key;
		} else {
			$roles_associations[ $key_in_meta ] = $role_key;
		}


		//$r_key = ! empty( $role_meta['_um_core'] ) ? $role_meta['_um_core'] : $role_key;
		//get all users with UM role
		$args = array(
			'meta_query'   => array(
				array(
					'key'       => 'role',
					'value'     => $key_in_meta
				)
			),
			'number'       => '',
			'count_total'  => false,
		);
		$all_users = get_users( $args );

		//update roles for users
		foreach ( $all_users as $k => $user ) {
			$user_object = get_userdata( $user->ID );

			if ( ! in_array( $role_key, $all_wp_roles ) ) {
				$user_object->add_role( 'um_' . $role_key );
			} else {
				if ( ! in_array( $role_key, (array) $user_object->roles ) ) {
					$user_object->add_role( $role_key );
				}
			}
		}

		if ( ! empty( $role_meta['_um_core'] ) )
			unset( $role_meta['_um_core'] );

		update_option( "um_role_{$role_key}_meta", $role_meta );
	}

	//update user role meta where role keys stored
	foreach ( $um_roles as $um_role ) {

		$key_in_meta = $um_role->post_name;

		$role_key = $roles_associations[ $key_in_meta ];
		if ( strpos( $role_key, 'um_' ) === 0 )
			$role_key = substr( $role_key, 3 );

		$role_meta = get_option( "um_role_{$role_key}_meta" );

		$role_metadata = array();
		if ( ! empty( $role_meta ) ) {
			foreach ( $role_meta as $metakey => $metadata ) {

				if ( '_um_can_edit_roles' == $metakey || '_um_can_delete_roles' == $metakey
				     || '_um_can_view_roles' == $metakey || '_um_can_follow_roles' == $metakey
				     || '_um_can_friend_roles' == $metakey || '_um_can_review_roles' == $metakey ) {

					if ( ! empty( $metadata ) ) {
						foreach ( $metadata as $i => $role_k ) {
							$metadata[ $i ] = $roles_associations[ $role_k ];
						}
					}
				} elseif ( '_um_profilec_upgrade_role' == $metakey ) {
					$metadata = $roles_associations[ $metadata ];
				}

				$role_meta[ $metakey ] = $metadata;
			}
		}

		update_option( "um_role_{$role_key}_meta", $role_meta );
	}
}

update_option( 'um_roles', $role_keys );

global $wp_roles, $wp_version;
if ( version_compare( $wp_version, '4.9', '<' ) ) {
	$wp_roles->_init();
} elseif ( method_exists( $wp_roles, 'for_site' ) ) {
	$wp_roles->for_site( get_current_blog_id() );
}

//Content Restriction transfer

//for check all post types and taxonomies
$all_post_types = get_post_types( array( 'public' => true ) );

$all_taxonomies = get_taxonomies( array( 'public' => true ) );
$exclude_taxonomies = UM()->excluded_taxonomies();

foreach ( $all_taxonomies as $key => $taxonomy ) {
	if( in_array( $key, $exclude_taxonomies ) )
		unset( $all_taxonomies[ $key ] );
}

foreach ( $all_post_types as $key => $value ) {
	$all_post_types[ $key ] = true;
}

foreach ( $all_taxonomies as $key => $value ) {
	$all_taxonomies[ $key ] = true;
}

UM()->options()->update( 'restricted_access_post_metabox', $all_post_types );
UM()->options()->update( 'restricted_access_taxonomy_metabox', $all_taxonomies );


$roles_array = UM()->roles()->get_roles( false, array( 'administrator' ) );

$posts = get_posts( array(
	'post_type'     => 'any',
	'meta_key'      => '_um_custom_access_settings',
	'meta_value'    => '1',
	'fields'        => 'ids',
	'numberposts'   => -1
) );

if ( ! empty( $posts ) ) {
	foreach ( $posts as $post_id ) {
		$um_accessible = get_post_meta( $post_id, '_um_accessible', true );
		$um_access_roles = get_post_meta( $post_id, '_um_access_roles', true );
		$um_access_redirect = ( $um_accessible == '2' ) ? get_post_meta( $post_id, '_um_access_redirect', true ) : get_post_meta( $post_id, '_um_access_redirect2', true );

		$access_roles = array();
		if ( ! empty( $um_access_roles ) ) {
			foreach ( $roles_array as $role => $role_label ) {
				//if ( in_array( substr( $role, 3 ), $um_access_roles ) )
				if ( false !== array_search( $role, $roles_associations ) && in_array( array_search( $role, $roles_associations ), $um_access_roles ) )
					$access_roles[ $role ] = '1';
				else
					$access_roles[ $role ] = '0';
			}
		} else {
			foreach ( $roles_array as $role => $role_label ) {
				$access_roles[ $role ] = '0';
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
$exclude_taxonomies = UM()->excluded_taxonomies();

foreach ( $all_taxonomies as $key => $taxonomy ) {
	if ( in_array( $key , $exclude_taxonomies ) )
		continue;

	$terms = get_terms( array(
		'taxonomy'      => $taxonomy,
		'hide_empty'    => false,
		'fields'        => 'ids'
	) );

	if ( empty( $terms ) )
		continue;

	foreach ( $terms as $term_id ) {
		$term_meta = get_option( "category_{$term_id}" );

		if ( empty( $term_meta ) )
			continue;

		$um_accessible = ! empty( $term_meta['_um_accessible'] ) ? $term_meta['_um_accessible'] : false;
		$um_access_roles = ! empty( $term_meta['_um_roles'] ) ? $term_meta['_um_roles'] : array();
		$redirect = ! empty( $term_meta['_um_redirect'] ) ? $term_meta['_um_redirect'] : '';
		$redirect2 = ! empty( $term_meta['_um_redirect2'] ) ? $term_meta['_um_redirect2'] : '';
		$um_access_redirect = ( $um_accessible == '2' ) ? $redirect : $redirect2;

		$access_roles = array();
		if ( ! empty( $um_access_roles ) ) {
			foreach ( $roles_array as $role => $role_label ) {
				//if ( in_array( substr( $role, 3 ), $um_access_roles ) )
				if ( false !== array_search( $role, $roles_associations ) && in_array( array_search( $role, $roles_associations ), $um_access_roles ) )
					$access_roles[ $role ] = '1';
				else
					$access_roles[ $role ] = '0';
			}
		} else {
			foreach ( $roles_array as $role => $role_label ) {
				$access_roles[ $role ] = '0';
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
//"use_global" meta  change to "_use_custom_settings"

//also update for forms metadata where "member" or "admin"
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

		$role = get_post_meta( $form_id, "_um_{$form_type}_role", true );
		if ( $role ) {
			//update_post_meta( $form_id, "_um_{$form_type}_role", 'um_' . $role );
			update_post_meta( $form_id, "_um_{$form_type}_role", $roles_associations[ $role ] );
		}
	}
}

//for metadata for all UM Member Directories
//also update for forms metadata where "member" or "admin"
$member_directories = get_posts( array(
	'post_type'     => 'um_directory',
	'numberposts'   => -1,
	'fields'        => 'ids'
) );

foreach ( $member_directories as $directory_id ) {
	$directory_roles = get_post_meta( $directory_id, '_um_roles', true );

	if ( ! empty( $directory_roles ) ) {
		/*$directory_roles = array_map( function( $item ) {
			return 'um_' . $item;
		}, $directory_roles );*/


		foreach ( $directory_roles as $i => $role_k ) {
			$directory_roles[ $i ] = $roles_associations[ $role_k ];
		}

		update_post_meta( $directory_id, '_um_roles', $directory_roles );
	}

	$um_roles_can_search = get_post_meta( $directory_id, '_um_roles_can_search', true );

	if ( ! empty( $um_roles_can_search ) ) {
		/*$um_roles_can_search = array_map( function( $item ) {
			return 'um_' . $item;
		}, $um_roles_can_search );*/

		foreach ( $um_roles_can_search as $i => $role_k ) {
			$um_roles_can_search[ $i ] = $roles_associations[ $role_k ];
		}

		update_post_meta( $directory_id, '_um_roles_can_search', $um_roles_can_search );
	}
}


/**
 * Transferring email templates to new logic
 */
$emails = UM()->config()->email_notifications;
foreach ( $emails as $email_key => $value ) {

	$in_theme = UM()->mail()->template_in_theme( $email_key, true );
	$theme_template_path = UM()->mail()->get_template_file( 'theme', $email_key );

	if ( ! $in_theme ) {
		$setting_value = UM()->options()->get( $email_key );

		UM()->mail()->copy_email_template( $email_key );

		$fp = fopen( $theme_template_path, "w" );
		$result = fputs( $fp, $setting_value );
		fclose( $fp );
	} else {
		$theme_template_path_html = UM()->mail()->get_template_file( 'theme', $email_key, true );

		$setting_value = preg_replace( '/<\/body>|<\/head>|<html>|<\/html>|<body.*?>|<head.*?>/' , '', file_get_contents( $theme_template_path_html ) );

		if ( file_exists( $theme_template_path_html ) ) {
			if ( copy( $theme_template_path_html, $theme_template_path ) ) {
				$fp = fopen( $theme_template_path, "w" );
				$result = fputs( $fp, $setting_value );
				fclose( $fp );
			}
		}
	}
}


/**
 * Transferring menu restriction data
 */
$menus = get_posts( array(
	'post_type' => 'nav_menu_item',
	'meta_query' => array(
		array(
			'key' => 'menu-item-um_nav_roles',
			'compare' => 'EXISTS',
		)
	),
	'numberposts' => -1,
) );

foreach ( $menus as $menu ) {
	$menu_roles = get_post_meta( $menu->ID, 'menu-item-um_nav_roles', true );


	foreach ( $menu_roles as $i => $role_k ) {
		$menu_roles[ $i ] = $roles_associations[ $role_k ];
	}

	/*$menu_roles = array_map( function( $item ) {
		if ( strpos( $item, 'um_' ) === 0 )
			return $item;

		return 'um_' . $item;
	}, $menu_roles );*/

	update_post_meta( $menu->ID, 'menu-item-um_nav_roles', $menu_roles );
}

$profile_tab_main_roles = UM()->options()->get( 'profile_tab_main_roles' );
$profile_tab_main_roles = ! $profile_tab_main_roles ? array() : $profile_tab_main_roles;
if ( ! empty( $profile_tab_main_roles ) ) {
	/*$profile_tab_main_roles = array_map( function( $item ) {
		return 'um_' . $item;
	}, $profile_tab_main_roles );*/

	foreach ( $profile_tab_main_roles as $i => $role_k ) {
		$profile_tab_main_roles[ $i ] = $roles_associations[ $role_k ];
	}

	UM()->options()->update( 'profile_tab_main_roles', $profile_tab_main_roles );
}

$profile_tab_posts_roles = UM()->options()->get( 'profile_tab_posts_roles' );
$profile_tab_posts_roles = ! $profile_tab_posts_roles ? array() : $profile_tab_posts_roles;
if ( ! empty( $profile_tab_posts_roles ) ) {
	/*$profile_tab_posts_roles = array_map( function( $item ) {
		return 'um_' . $item;
	}, $profile_tab_posts_roles );*/

	foreach ( $profile_tab_posts_roles as $i => $role_k ) {
		$profile_tab_posts_roles[ $i ] = $roles_associations[ $role_k ];
	}

	UM()->options()->update( 'profile_tab_posts_roles', $profile_tab_posts_roles );
}

$profile_tab_comments_roles = UM()->options()->get( 'profile_tab_comments_roles' );
$profile_tab_comments_roles = ! $profile_tab_comments_roles ? array() : $profile_tab_comments_roles;
if ( ! empty( $profile_tab_comments_roles ) ) {
	/*$profile_tab_comments_roles = array_map( function( $item ) {
		return 'um_' . $item;
	}, $profile_tab_comments_roles );*/

	foreach ( $profile_tab_comments_roles as $i => $role_k ) {
		$profile_tab_comments_roles[ $i ] = $roles_associations[ $role_k ];
	}

	UM()->options()->update( 'profile_tab_comments_roles', $profile_tab_comments_roles );
}

$profile_tab_activity_roles = UM()->options()->get( 'profile_tab_activity_roles' );
$profile_tab_activity_roles = ! $profile_tab_activity_roles ? array() : $profile_tab_activity_roles;
if ( ! empty( $profile_tab_activity_roles ) ) {
	/*$profile_tab_activity_roles = array_map( function( $item ) {
		return 'um_' . $item;
	}, $profile_tab_activity_roles );*/

	foreach ( $profile_tab_activity_roles as $i => $role_k ) {
		$profile_tab_activity_roles[ $i ] = $roles_associations[ $role_k ];
	}

	UM()->options()->update( 'profile_tab_activity_roles', $profile_tab_activity_roles );
}

$profile_tab_messages_roles = UM()->options()->get( 'profile_tab_messages_roles' );
$profile_tab_messages_roles = ! $profile_tab_messages_roles ? array() : $profile_tab_messages_roles;
if ( ! empty( $profile_tab_messages_roles ) ) {
	/*$profile_tab_messages_roles = array_map( function( $item ) {
		return 'um_' . $item;
	}, $profile_tab_messages_roles );*/

	foreach ( $profile_tab_messages_roles as $i => $role_k ) {
		$profile_tab_messages_roles[ $i ] = $roles_associations[ $role_k ];
	}

	UM()->options()->update( 'profile_tab_messages_roles', $profile_tab_messages_roles );
}

$profile_tab_reviews_roles = UM()->options()->get( 'profile_tab_reviews_roles' );
$profile_tab_reviews_roles = ! $profile_tab_reviews_roles ? array() : $profile_tab_reviews_roles;
if ( ! empty( $profile_tab_reviews_roles ) ) {
	/*$profile_tab_reviews_roles = array_map( function( $item ) {
		return 'um_' . $item;
	}, $profile_tab_reviews_roles );*/

	foreach ( $profile_tab_reviews_roles as $i => $role_k ) {
		$profile_tab_reviews_roles[ $i ] = $roles_associations[ $role_k ];
	}

	UM()->options()->update( 'profile_tab_reviews_roles', $profile_tab_reviews_roles );
}

$profile_tab_purchases_roles = UM()->options()->get( 'profile_tab_purchases_roles' );
$profile_tab_purchases_roles = ! $profile_tab_purchases_roles ? array() : $profile_tab_purchases_roles;
if ( ! empty( $profile_tab_purchases_roles ) ) {
	/*$profile_tab_purchases_roles = array_map( function( $item ) {
		return 'um_' . $item;
	}, $profile_tab_purchases_roles );*/

	foreach ( $profile_tab_purchases_roles as $i => $role_k ) {
		$profile_tab_purchases_roles[ $i ] = $roles_associations[ $role_k ];
	}

	UM()->options()->update( 'profile_tab_purchases_roles', $profile_tab_purchases_roles );
}

$profile_tab_product_reviews = UM()->options()->get( 'profile_tab_product-reviews_roles' );
$profile_tab_product_reviews = ! $profile_tab_product_reviews ? array() : $profile_tab_product_reviews;
if ( ! empty( $profile_tab_product_reviews ) ) {
	/*$profile_tab_product_reviews = array_map( function( $item ) {
		return 'um_' . $item;
	}, $profile_tab_product_reviews );*/

	foreach ( $profile_tab_product_reviews as $i => $role_k ) {
		$profile_tab_product_reviews[ $i ] = $roles_associations[ $role_k ];
	}

	UM()->options()->update( 'profile_tab_product-reviews_roles', $profile_tab_product_reviews );
}


$profile_tab_forums_roles = UM()->options()->get( 'profile_tab_forums_roles' );
$profile_tab_forums_roles = ! $profile_tab_forums_roles ? array() : $profile_tab_forums_roles;
if ( ! empty( $profile_tab_forums_roles ) ) {
	/*$profile_tab_forums_roles = array_map( function( $item ) {
		return 'um_' . $item;
	}, $profile_tab_forums_roles );*/

	foreach ( $profile_tab_forums_roles as $i => $role_k ) {
		$profile_tab_forums_roles[ $i ] = $roles_associations[ $role_k ];
	}

	UM()->options()->update( 'profile_tab_forums_roles', $profile_tab_forums_roles );
}

$profile_tab_friends_roles = UM()->options()->get( 'profile_tab_friends_roles' );
$profile_tab_friends_roles = ! $profile_tab_friends_roles ? array() : $profile_tab_friends_roles;
if ( ! empty( $profile_tab_friends_roles ) ) {
	/*$profile_tab_friends_roles = array_map( function( $item ) {
		return 'um_' . $item;
	}, $profile_tab_friends_roles );*/

	foreach ( $profile_tab_friends_roles as $i => $role_k ) {
		$profile_tab_friends_roles[ $i ] = $roles_associations[ $role_k ];
	}

	UM()->options()->update( 'profile_tab_friends_roles', $profile_tab_friends_roles );
}


$register_role = UM()->options()->get( 'register_role' );
if ( ! empty( $register_role ) ) {
	//$register_role = 'um_' . $register_role;
	$register_role = $roles_associations[ $register_role ];

	UM()->options()->update( 'register_role', $register_role );
}

$woo_oncomplete_role = UM()->options()->get( 'woo_oncomplete_role' );
if ( ! empty( $woo_oncomplete_role ) ) {
	//$woo_oncomplete_role = 'um_' . $woo_oncomplete_role;
	$woo_oncomplete_role = $roles_associations[ $woo_oncomplete_role ];
	UM()->options()->update( 'woo_oncomplete_role', $woo_oncomplete_role );
}

$woo_oncomplete_except_roles = UM()->options()->get( 'woo_oncomplete_except_roles' );
$woo_oncomplete_except_roles = ! $woo_oncomplete_except_roles ? array() : $woo_oncomplete_except_roles;
if ( ! empty( $woo_oncomplete_except_roles ) ) {
	/*$woo_oncomplete_except_roles = array_map( function( $item ) {
		return 'um_' . $item;
	}, $woo_oncomplete_except_roles );*/

	foreach ( $woo_oncomplete_except_roles as $i => $role_k ) {
		$woo_oncomplete_except_roles[ $i ] = $roles_associations[ $role_k ];
	}

	UM()->options()->update( 'woo_oncomplete_except_roles', $woo_oncomplete_except_roles );
}

//for metadata for all bbPress forums
//also update for forms metadata where "member" or "admin"
$wc_products = get_posts( array(
	'post_type'     => 'product',
	'numberposts'   => -1,
	'fields'        => 'ids'
) );

foreach ( $wc_products as $product_id ) {
	$woo_product_role = get_post_meta( $product_id, '_um_woo_product_role', true );

	if ( ! empty( $woo_product_role ) ) {
		//$woo_product_role = 'um_' . $woo_product_role;
		$woo_product_role = $roles_associations[ $woo_product_role ];
		update_post_meta( $product_id, '_um_woo_product_role', $woo_product_role );
	}

	$woo_product_activated_role = get_post_meta( $product_id, '_um_woo_product_activated_role', true );

	if ( ! empty( $woo_product_activated_role ) ) {
		//$woo_product_activated_role = 'um_' . $woo_product_activated_role;
		$woo_product_activated_role = $roles_associations[ $woo_product_activated_role ];
		update_post_meta( $product_id, '_um_woo_product_activated_role', $woo_product_activated_role );
	}

	$woo_product_downgrade_pending_role = get_post_meta( $product_id, '_um_woo_product_downgrade_pending_role', true );

	if ( ! empty( $woo_product_downgrade_pending_role ) ) {
		//$woo_product_downgrade_pending_role = 'um_' . $woo_product_downgrade_pending_role;
		$woo_product_downgrade_pending_role = $roles_associations[ $woo_product_downgrade_pending_role ];
		update_post_meta( $product_id, '_um_woo_product_downgrade_pending_role', $woo_product_downgrade_pending_role );
	}

	$woo_product_downgrade_onhold_role = get_post_meta( $product_id, '_um_woo_product_downgrade_onhold_role', true );

	if ( ! empty( $woo_product_downgrade_onhold_role ) ) {
		//$woo_product_downgrade_onhold_role = 'um_' . $woo_product_downgrade_onhold_role;
		$woo_product_downgrade_onhold_role = $roles_associations[ $woo_product_downgrade_onhold_role ];
		update_post_meta( $product_id, '_um_woo_product_downgrade_onhold_role', $woo_product_downgrade_onhold_role );
	}

	$woo_product_downgrade_expired_role = get_post_meta( $product_id, '_um_woo_product_downgrade_expired_role', true );

	if ( ! empty( $woo_product_downgrade_expired_role ) ) {
		//$woo_product_downgrade_expired_role = 'um_' . $woo_product_downgrade_expired_role;
		$woo_product_downgrade_expired_role = $roles_associations[ $woo_product_downgrade_expired_role ];
		update_post_meta( $product_id, '_um_woo_product_downgrade_expired_role', $woo_product_downgrade_expired_role );
	}

	$woo_product_downgrade_cancelled_role = get_post_meta( $product_id, '_um_woo_product_downgrade_cancelled_role', true );

	if ( ! empty( $woo_product_downgrade_cancelled_role ) ) {
		//$woo_product_downgrade_cancelled_role = 'um_' . $woo_product_downgrade_cancelled_role;
		$woo_product_downgrade_cancelled_role = $roles_associations[ $woo_product_downgrade_cancelled_role ];
		update_post_meta( $product_id, '_um_woo_product_downgrade_cancelled_role', $woo_product_downgrade_cancelled_role );
	}
}


$bb_forums = get_posts( array(
	'post_type'     => 'forum',
	'numberposts'   => -1,
	'fields'        => 'ids'
) );

foreach ( $bb_forums as $forum_id ) {
	$bbpress_can_topic = get_post_meta( $forum_id, '_um_bbpress_can_topic', true );
	$bbpress_can_topic = ! $bbpress_can_topic ? array() : $bbpress_can_topic;
	if ( ! empty( $bbpress_can_topic ) ) {
		/*$bbpress_can_topic = array_map( function( $item ) {
			return 'um_' . $item;
		}, $bbpress_can_topic );*/

		foreach ( $bbpress_can_topic as $i => $role_k ) {
			$bbpress_can_topic[ $i ] = $roles_associations[ $role_k ];
		}

		update_post_meta( $forum_id, '_um_bbpress_can_topic', $bbpress_can_topic );
	}


	$bbpress_can_reply = get_post_meta( $forum_id, '_um_bbpress_can_reply', true );
	$bbpress_can_reply = ! $bbpress_can_reply ? array() : $bbpress_can_reply;
	if ( ! empty( $bbpress_can_reply ) ) {
		/*$bbpress_can_reply = array_map( function( $item ) {
			return 'um_' . $item;
		}, $bbpress_can_reply );*/

		foreach ( $bbpress_can_reply as $i => $role_k ) {
			$bbpress_can_reply[ $i ] = $roles_associations[ $role_k ];
		}

		update_post_meta( $forum_id, '_um_bbpress_can_reply', $bbpress_can_reply );
	}
}



$mc_lists = get_posts( array(
	'post_type'     => 'um_mailchimp',
	'numberposts'   => -1,
	'fields'        => 'ids'
) );

foreach ( $mc_lists as $list_id ) {
	$um_roles = get_post_meta( $list_id, '_um_roles', true );
	$um_roles = ! $um_roles ? array() : $um_roles;
	if ( ! empty( $um_roles ) ) {
		/*$um_roles = array_map( function( $item ) {
			return 'um_' . $item;
		}, $um_roles );*/

		foreach ( $um_roles as $i => $role_k ) {
			$um_roles[ $i ] = $roles_associations[ $role_k ];
		}

		update_post_meta( $list_id, '_um_roles', $um_roles );
	}
}


$um_social_login = get_posts( array(
	'post_type'     => 'um_social_login',
	'numberposts'   => -1,
	'fields'        => 'ids'
) );

foreach ( $um_social_login as $social_login_id ) {
	$assigned_role = get_post_meta( $social_login_id, '_um_assigned_role', true );

	if ( ! empty( $assigned_role ) ) {
		//$assigned_role = 'um_' . $assigned_role;
		$assigned_role = $roles_associations[ $assigned_role ];
		update_post_meta( $social_login_id, '_um_assigned_role', $assigned_role );
	}
}