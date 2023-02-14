<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

foreach ( $data as $k => $v ) {
	if ( 'social_urls' === $k ) {
		continue;
	}

	if ( 0 === strpos( $k, 'label_' ) ) {
		continue;
	}
	?>
	<div class="um-member-metaline um-member-metaline-<?php echo $k; ?>">
		<strong><?php echo $data[ 'label_' . $k ]; ?>:</strong> <?php echo $v; ?>
	</div>
	<?php
}

if ( ! empty( $data['social_urls'] ) ) {
	?>
	<div class="um-member-connect">
		<?php echo $data['social_urls']; ?>
	</div>
	<?php
}
