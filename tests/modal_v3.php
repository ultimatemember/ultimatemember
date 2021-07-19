<?php

namespace um\tests\modal;

/*
 * Test JS library UM-Modal
 */

add_action( 'admin_menu', function() {
	add_submenu_page( 'ultimatemember', __( 'UM-Modal', 'ultimatemember' ), __( 'UM-Modal', 'ultimatemember' ), 'administrator', 'um-modal', '\um\tests\modal\page' );
}, 9999 );

function show_code( $function_name ) {
	if ( function_exists( $function_name ) ) {
		ob_start();
		$function_name();
		return htmlspecialchars( ob_get_clean() );
	}
}

function page() {
	UM()->admin_enqueue()->load_modal();
	?>
	<div class="wrap">
		<h1 class="wp-heading-inline"><?php _e( 'UM-Modal testing', 'ultimatemember' ); ?></h1>

		<h2 class="title">Test 00 - Simple modal with text</h2>
		<h3 class="title">Code:</h3>
		<pre><?php echo show_code( '\um\tests\modal\test_00' ); ?></pre>
		<h3 class="title">Sample:</h3>
		<div><?php echo test_00(); ?></div>

		<hr>

		<h2 class="title">Test 01 - Open modal using data attributes</h2>
		<h3 class="title">Code:</h3>
		<pre><?php echo show_code( '\um\tests\modal\test_01' ); ?></pre>
		<h3 class="title">Sample:</h3>
		<div><?php echo test_01(); ?></div>

		<hr>

		<h2 class="title">Test 02 - Modal with content loaded by AJAX</h2>
		<h3 class="title">Code:</h3>
		<pre><?php echo show_code( '\um\tests\modal\test_02' ); ?></pre>
		<h3 class="title">Sample:</h3>
		<div><?php echo test_02(); ?></div>

		<hr>

		<h2 class="title">Test 03 - Image popup</h2>
		<h3 class="title">Code:</h3>
		<pre><?php echo show_code( '\um\tests\modal\test_03' ); ?></pre>
		<h3 class="title">Sample:</h3>
		<div><?php echo test_03(); ?></div>

	</div>
	<?php
}

function test_00() {
	?>
	<button class="button button-primary umModalSimple">Show modal</button>

	<script type="text/javascript">
		jQuery(function () {
			jQuery('.umModalSimple').umModalBtn('Hello world!', {
				header: 'It is a simple modal'
			});
		});
	</script>
	<?php
}

function test_01() {
	?>
	<button class="button button-primary umModalBtn" data-content=".umModalContent" data-header="Available attributes">Show hidden block</button>

	<div class="umModalContent" style="display:none;">
		<ul>
			<li>data-content <em>(required)</em></li>
			<li>data-classes</li>
			<li>data-duration</li>
			<li>data-footer</li>
			<li>data-header</li>
			<li>data-remoteContent</li>
			<li>data-size</li>
			<li>data-template</li>
		</ul>
	</div>
	<?php
}

function test_02() {
	?>
	<button class="button button-primary umAddField">Add field</button>

	<script type="text/template" id="tmpl-um-modal-fields">
		<div class="um-modal um-admin-modal" id="UM_fields">
			<span class="um-modal-close umModalClose">&times;</span>
			<div class="um-modal-header um-admin-modal-head">
				<h3>Fields Manager</h3>
			</div>
			<div class="um-modal-body um-admin-modal-body"></div>
		</div>
	</script>

	<script type="text/javascript">
		jQuery(function () {
			function get_fields_manager(event) {
				let $btn = jQuery(event.currentTarget);
				let $modal = this;

				return jQuery.ajax( {
					url: wp.ajax.settings.url,
					type: 'POST',
					dataType: 'html',
					data: {
						action: 'um_dynamic_modal_content',
						act_id: 'um_admin_show_fields',
						arg1: $btn.data( 'arg1' ),
						arg2: $btn.data( 'arg2' ),
						arg3: $btn.data( 'arg3' ),
						nonce: um_admin_scripts.nonce
					},
					success: function (data) {
						UM.modal.setContent(data, $modal);
					}
				} );
			}

			jQuery('.umAddField').umModalBtn(get_fields_manager, {
				size: 'normal',
				template: 'um-modal-fields'
			});
		});
	</script>
	<?php
}

function test_03() {
	$cover_photo_small = um_get_cover_uri( um_profile( 'cover_photo' ), 300 );
	$cover_photo_large = um_get_cover_uri( um_profile( 'cover_photo' ), 600 );
	?>
	<a href="#" class="um-photo-modal" data-src="<?php echo esc_url( $cover_photo_large ); ?>">
		<img src="<?php echo esc_url( $cover_photo_small ); ?>" title="Image">
	</a>

	<script type="text/template" id="tmpl-um-modal-photo">
		<div class="um-modal">
			<span class="um-modal-close um-modal-close-fixed umModalClose">&times;</span>
			<div class="um-modal-body"></div>
		</div>
	</script>

	<script type="text/javascript">
		jQuery(function () {
			function get_enlarger_image(event) {
				let $btn = jQuery(event.currentTarget);
				return '<img src="' + $btn.data('src') + '">';
			}

			jQuery('.um-photo-modal[data-src]').umModalBtn(get_enlarger_image, {
				template: 'um-modal-photo'
			});
		});
	</script>
	<?php
}
