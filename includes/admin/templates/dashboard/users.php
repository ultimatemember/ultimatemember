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
					<?php echo UM()->query()->count_users(); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'users.php' ) ); ?>">
					<?php _e( 'Users', 'ultimate-member' ); ?>
				</a>
			</span>
		</td>
		<td>
			<span>
			<a class="count" href="<?php echo esc_url( admin_url( 'users.php?um_status=awaiting_admin_review' ) ); ?>">
				<?php echo UM()->query()->count_users_by_status( 'awaiting_admin_review' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'users.php?um_status=awaiting_admin_review' ) ); ?>" class="warning">
				<?php _e( 'Pending Review', 'ultimate-member' ); ?>
			</a>
			</span>
		</td>
	</tr>
	<tr>
		<td>
			<span>
			<a class="count" href="<?php echo esc_url( admin_url( 'users.php?um_status=approved' ) ); ?>">
				<?php echo UM()->query()->count_users_by_status( 'approved' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'users.php?um_status=approved' ) ); ?>">
				<?php _e( 'Approved', 'ultimate-member' ); ?>
			</a>
			</span>
		</td>
		<td>
			<span>
			<a class="count" href="<?php echo esc_url( admin_url( 'users.php?um_status=awaiting_email_confirmation' ) ); ?>">
				<?php echo UM()->query()->count_users_by_status( 'awaiting_email_confirmation' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'users.php?um_status=awaiting_email_confirmation' ) ); ?>" class="warning">
				<?php _e( 'Awaiting Email Confirmation', 'ultimate-member' ); ?>
			</a>
			</span>
		</td>
	</tr>
	<tr>
		<td>
			<span>
			<a class="count" href="<?php echo esc_url( admin_url( 'users.php?um_status=rejected' ) ); ?>">
				<?php echo UM()->query()->count_users_by_status( 'rejected' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'users.php?um_status=rejected' ) ); ?>">
				<?php _e( 'Rejected', 'ultimate-member' ); ?>
			</a></span>
		</td>
		<td>
			<span>
			<a class="count" href="<?php echo esc_url( admin_url( 'users.php?um_status=inactive' ) ); ?>">
				<?php echo UM()->query()->count_users_by_status( 'inactive' ); ?>
			</a>
			<a href="<?php echo esc_url( admin_url( 'users.php?um_status=inactive' ) ); ?>">
				<?php _e( 'Inactive', 'ultimate-member' ); ?>
			</a>
			</span>
		</td>
	</tr>
</table>
<div class="clear"></div>
