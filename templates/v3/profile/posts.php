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
 * @var object   $posts
 * @var int      $per_page
 * @var int      $count_posts
 * @var int      $last_id
 * @var int|null $current_user_id
 * @var int      $user_profile_id
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! empty( $posts ) ) {
	UM()->get_template( 'v3/profile/posts-loop.php', '', array( 'posts' => $posts ), true );

	if ( $count_posts > $per_page ) {
		$loader = UM()->frontend()::layouts()::ajax_loader( 's', array( 'classes' => array( 'um-user-posts-loader', 'um-display-none' ) ) );

		$button = UM()->frontend()::layouts()::button(
			__( 'Load more', 'ultimate-member' ),
			array(
				'type'    => 'button',
				'size'    => 's',
				'design'  => 'tertiary-gray',
				'data'    => array(
					'nonce'   => wp_create_nonce( 'um_user_profile_posts' . $user_profile_id ),
					'author'  => $user_profile_id,
					'page'    => 1,
					'pages'   => ceil( $count_posts / $per_page ),
					'last_id' => $last_id,
				),
				'classes' => array( 'um-user-posts-load-more' ),
			)
		);
		echo wp_kses( $loader . $button, UM()->get_allowed_html( 'templates' ) );
	}
} else {
	?>
	<span class="um-profile-note">
		<?php
		if ( $user_profile_id === $current_user_id ) {
			esc_html_e( 'You have not created any posts.', 'ultimate-member' );
		} else {
			esc_html_e( 'This user has not created any posts.', 'ultimate-member' );
		}
		?>
	</span>
	<?php
}
