<?php

namespace um\tests\modal;

/*
 * Test JS library UM-Modal
 */

add_action( 'admin_menu', function() {
	add_submenu_page( 'ultimatemember', __( 'Modal testing', 'ultimatemember' ), __( 'Modal testing', 'ultimatemember' ), 'administrator', 'um-modal', '\um\tests\modal\page' );
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
		<h1 class="wp-heading-inline"><?php _e( 'Modal testing', 'ultimatemember' ); ?></h1>

		<h2 class="title">Test 00 - Simple modal with text</h2>
		<h3 class="title">Sample:</h3>
		<div><?php echo test_00(); ?></div>
		<h3 class="title">Code:</h3>
		<pre><?php echo show_code( '\um\tests\modal\test_00' ); ?></pre>

		<hr>

		<h2 class="title">Test 01 - Open modal using data attributes</h2>
		<h3 class="title">Sample:</h3>
		<div><?php echo test_01(); ?></div>
		<h3 class="title">Code:</h3>
		<pre><?php echo show_code( '\um\tests\modal\test_01' ); ?></pre>

		<hr>

		<h2 class="title">Test 02 - Modal with content loaded by AJAX</h2>
		<h3 class="title">Sample:</h3>
		<div><?php echo test_02(); ?></div>
		<h3 class="title">Code:</h3>
		<pre><?php echo show_code( '\um\tests\modal\test_02' ); ?></pre>

		<hr>

		<h2 class="title">Test 03 - Image popup</h2>
		<h3 class="title">Sample:</h3>
		<div><?php echo test_03(); ?></div>
		<h3 class="title">Code:</h3>
		<pre><?php echo show_code( '\um\tests\modal\test_03' ); ?></pre>

		<hr>

		<h2 class="title">Test 04 - Multilevel modal</h2>
		<h3 class="title">Sample:</h3>
		<div><?php echo test_04(); ?></div>
		<h3 class="title">Code:</h3>
		<pre><?php echo show_code( '\um\tests\modal\test_04' ); ?></pre>

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
			<li>data-size</li>
			<li>data-template</li>
		</ul>
	</div>
	<?php
}

function test_02() {
  $forms = get_posts(array(
      'post_type' => 'um_form'
  ));
  $form = current($forms);
	?>
  <button class="button button-primary umAddField" data-arg2="<?php echo esc_attr( $form->ID ); ?>">Add field</button>

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
						UM.modal.setContent(data, $modal).responsive();
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

function test_04() {
	?>
	<button class="button button-primary umModalBtn" data-content=".umModalContentL1" data-template="um-modal-level-1">Show first level</button>

	<div class="umModalContentL1" style="display:none;">
		<button class="button button-primary umModalBtn" data-content=".umModalContentL2" data-template="um-modal-level-2">Show second level</button>
	</div>

	<div class="umModalContentL2" style="display:none;">
		<button class="button button-primary umModalBtn" data-content=".umModalContentL3" data-template="um-modal-level-3">Show third level</button>
	</div>

	<div class="umModalContentL3" style="display:none;">
		<p>You can add one more modal level here. But it is enough for testing. Enjoy.</p>
	</div>

	<script type="text/template" id="tmpl-um-modal-level-1">
		<div class="um-modal um-admin-modal">
			<span class="um-modal-close umModalClose">&times;</span>
			<div class="um-modal-header">
				<h3>The first level</h3>
			</div>
			<div class="um-modal-body"></div>
		</div>
	</script>

	<script type="text/template" id="tmpl-um-modal-level-2">
		<div class="um-modal um-admin-modal">
			<span class="um-modal-close umModalClose">&times;</span>
			<div class="um-modal-header">
				<h3>The second level</h3>
			</div>
			<div class="um-modal-body"></div>
		</div>
	</script>

	<script type="text/template" id="tmpl-um-modal-level-3">
		<div class="um-modal um-admin-modal">
			<span class="um-modal-close umModalClose">&times;</span>
			<div class="um-modal-header">
				<h3>The third level</h3>
			</div>
			<div class="um-modal-body"></div>
		</div>
	</script>
	<?php
}
