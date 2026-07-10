<?php
/**
 * Template for the header of profile page
 *
 * This template can be overridden by copying it to your-theme/ultimate-member/templates/v3/profile/menu.php
 *
 * Page: "Profile"
 *
 * @version 3.0.0
 *
 * @var int         $current_user_id
 * @var int         $user_profile_id
 * @var array       $wrapper_classes
 * @var array       $profile_args
 * @var array       $tabs
 * @var array       $subnav
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

echo wp_kses(
	UM()->frontend()::layouts()::tabs(
		array(
			'id'            => 'um-profile-navigation-' . $user_profile_id,
			'wrapper_class' => array( 'um-profile-nav' ),
			'orientation'   => 'horizontal',
			'tabs_only'     => true,
			'color'         => 'underline',
			'size'          => 'm',
			'tabs'          => $tabs,
			'responsive'    => count( $tabs ) > 3,
			'max_tabs'      => 3,
		)
	),
	UM()->get_allowed_html( 'templates' )
);

if ( ! empty( $subnav ) ) {
	echo wp_kses(
		UM()->frontend()::layouts()::tabs(
			array(
				'id'            => 'um-profile-sub-navigation-' . $user_profile_id,
				'wrapper_class' => array( 'um-profile-subnav' ),
				'orientation'   => 'horizontal',
				'tabs_only'     => true,
				'color'         => 'secondary',
				'tabs'          => $subnav,
				'responsive'    => count( $subnav ) > 2,
				'max_tabs'      => 2,
			)
		),
		UM()->get_allowed_html( 'templates' )
	);
}
