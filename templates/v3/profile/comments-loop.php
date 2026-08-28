<?php
/**
 * Template for the profile posts
 *
 * This template can be overridden by copying it to your-theme/ultimate-member/profile/posts.php
 *
 * Page: "Profile"
 *
 * @version 2.6.1
 *
 * @var object $comments
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! empty( $comments ) ) {
	?>
	<div class="um-user-profile-comments-loop um-list">
		<?php
		foreach ( $comments as $comment ) {
			UM()->get_template( 'v3/profile/comments-item.php', '', array( 'comment' => $comment ), true );
		}
		?>
	</div>
	<?php
}
