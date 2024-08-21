<?php
/**
 * Template for the profile posts
 *
 * This template can be overridden by copying it to yourtheme/ultimate-member/profile/posts.php
 *
 * Page: "Profile"
 *
 * @version 2.6.1
 *
 * @var object $posts
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! empty( $posts ) ) {
	?>
	<div class="um-user-profile-posts-loop um-list">
		<?php
		foreach ( $posts as $post ) {
			UM()->get_template( 'v3/profile/posts-item.php', '', array( 'post' => $post ), true );
		}
		?>
	</div>
	<?php
}
