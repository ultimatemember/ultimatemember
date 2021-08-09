<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
} ?>


<div class="um-admin-metabox">
	<?php /*UM()->admin_forms( array(
		'class'		=> 'um-member-directory-shortcode um-top-label',
		'prefix_id'	=> 'um_metadata',
		'fields' => array(
			array(
				'id'        => '_um_is_default',
				'type'      => 'select',
				'label'     => __( 'Default Member Directory', 'ultimate-member' ),
				'tooltip'   => sprintf( __( 'If you set this member directory as default you will have an ability to use %s shortcode for the displaying directory at the page. Otherwise you will have to use %s', 'ultimate-member' ), UM()->shortcodes()->get_default_shortcode( get_the_ID() ), UM()->shortcodes()->get_shortcode( get_the_ID() ) ),
				'value'     => UM()->query()->get_meta_value( '_um_is_default' ),
				'options'   => array(
					0	=> __( 'No', 'ultimate-member' ),
					1	=> __( 'Yes', 'ultimate-member' ),
				),
			),
		)
	) )->render_form();*/ ?>

<!--	<div class="um-admin-clear"></div>-->

	<p><?php echo UM()->shortcodes()->get_shortcode( get_the_ID() ); ?></p>
<!--	<p>--><?php //echo UM()->shortcodes()->get_default_shortcode( get_the_ID() ); ?><!--</p>-->
</div>
