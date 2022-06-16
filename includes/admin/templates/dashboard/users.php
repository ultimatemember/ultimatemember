<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$users_page_url  = admin_url( 'users.php' );
$users_count_map = array(
	''                            => __( 'Users', 'ultimate-member' ),
	'approved'                    => __( 'Approved', 'ultimate-member' ),
	'rejected'                    => __( 'Rejected', 'ultimate-member' ),
	'awaiting_admin_review'       => __( 'Pending Review', 'ultimate-member' ),
	'awaiting_email_confirmation' => __( 'Awaiting E-mail Confirmation', 'ultimate-member' ),
	'inactive'                    => __( 'Inactive', 'ultimate-member' ),
);
?>

<div class="um-admin-metabox">
	<div class="um-users-flex">
		<table>
			<?php
			$i = 0;
			foreach ( $users_count_map as $key => $title ) {
				$url     = ( '' !== $key ) ? add_query_arg( array( 'um_status' => $key ), $users_page_url ) : $users_page_url;
				$count   = ( '' !== $key ) ? UM()->query()->count_users_by_status( $key ) : UM()->query()->count_users();
				$warning = ( 'awaiting_admin_review' === $key || 'awaiting_email_confirmation' === $key ) ? 'um-warning': '';

				if ( 3 === $i ) {
					?>
					</table><table>
					<?php
				}
				?>

				<tr>
					<td class="um-users-counter">
						<a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $count ); ?></a>
					</td>
					<td>
						<a href="<?php echo esc_url( $url ); ?>" class="<?php echo esc_attr( $warning ); ?>"><?php echo esc_html( $title ); ?></a>
					</td>
				</tr>

				<?php
				$i++;
			}
			?>
		</table>
	</div>
	<div class="um-admin-clear"></div>
</div>
