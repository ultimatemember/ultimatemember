<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<table id="um-users-overview-table">
	<tr>
		<td>
			<span>
				<a class="count" href="<?php echo esc_url( admin_url( 'users.php' ) ); ?>">
					<?php
					global $wpdb;
					$count_users = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->users}" );
					echo absint( $count_users );
					?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'users.php' ) ); ?>">
					<?php esc_html_e( 'Users', 'ultimate-member' ); ?>
				</a>
			</span>
		</td>
		<td>
			<span>
			<a class="count" href="<?php echo esc_url( admin_url( 'users.php?um_status=awaiting_admin_review' ) ); ?>">
				<?php echo absint( UM()->query()->count_users_by_status( 'awaiting_admin_review' ) ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'users.php?um_status=awaiting_admin_review' ) ); ?>" class="warning">
				<?php esc_html_e( 'Pending Review', 'ultimate-member' ); ?>
			</a>
			</span>
		</td>
	</tr>
	<tr>
		<td>
			<span>
			<a class="count" href="<?php echo esc_url( admin_url( 'users.php?um_status=approved' ) ); ?>">
				<?php echo absint( UM()->query()->count_users_by_status( 'approved' ) ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'users.php?um_status=approved' ) ); ?>">
				<?php esc_html_e( 'Approved', 'ultimate-member' ); ?>
			</a>
			</span>
		</td>
		<td>
			<span>
			<a class="count" href="<?php echo esc_url( admin_url( 'users.php?um_status=awaiting_email_confirmation' ) ); ?>">
				<?php echo absint( UM()->query()->count_users_by_status( 'awaiting_email_confirmation' ) ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'users.php?um_status=awaiting_email_confirmation' ) ); ?>" class="warning">
				<?php esc_html_e( 'Awaiting Email Confirmation', 'ultimate-member' ); ?>
			</a>
			</span>
		</td>
	</tr>
	<tr>
		<td>
			<span>
			<a class="count" href="<?php echo esc_url( admin_url( 'users.php?um_status=rejected' ) ); ?>">
				<?php echo absint( UM()->query()->count_users_by_status( 'rejected' ) ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'users.php?um_status=rejected' ) ); ?>">
				<?php esc_html_e( 'Rejected', 'ultimate-member' ); ?>
			</a></span>
		</td>
		<td>
			<span>
			<a class="count" href="<?php echo esc_url( admin_url( 'users.php?um_status=inactive' ) ); ?>">
				<?php echo absint( UM()->query()->count_users_by_status( 'inactive' ) ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'users.php?um_status=inactive' ) ); ?>">
				<?php esc_html_e( 'Inactive', 'ultimate-member' ); ?>
			</a>
			</span>
		</td>
	</tr>
</table>
<div class="clear"></div>
