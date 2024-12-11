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
 * @var object   $comments
 * @var int      $per_page
 * @var int      $count_comments
 * @var int      $last_id
 * @var int|null $current_user_id
 * @var int      $user_profile_id
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! empty( $comments ) ) {
	UM()->get_template( 'v3/profile/comments-loop.php', '', array( 'comments' => $comments ), true );

	if ( $count_comments > $per_page ) {
		$loader = UM()->frontend()::layouts()::ajax_loader( 's', array( 'classes' => array( 'um-user-comments-loader', 'um-display-none' ) ) );

		$button = UM()->frontend()::layouts()::button(
			__( 'Load more', 'ultimate-member' ),
			array(
				'type'    => 'button',
				'size'    => 's',
				'design'  => 'tertiary-gray',
				'data'    => array(
					'nonce'   => wp_create_nonce( 'um_user_profile_comments' . $user_profile_id ),
					'author'  => $user_profile_id,
					'page'    => 1,
					'pages'   => ceil( $count_comments / $per_page ),
					'last_id' => $last_id,
				),
				'classes' => array( 'um-user-comments-load-more' ),
			)
		);
		echo wp_kses( $loader . $button, UM()->get_allowed_html( 'templates' ) );
	}
} else {
	?>
	<span class="um-profile-note">
		<?php
		if ( $user_profile_id === $current_user_id ) {
			esc_html_e( 'You have not made any comments.', 'ultimate-member' );
		} else {
			esc_html_e( 'This user has not made any comments.', 'ultimate-member' );
		}
		?>
	</span>
	<?php
}
