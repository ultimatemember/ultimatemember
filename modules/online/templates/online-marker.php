<?php if ( ! defined( 'ABSPATH' ) ) exit;

$class = $is_online ? 'online' : 'offline';
$title = $is_online ? __( 'Online', 'ultimate-member' ) : __( 'Offline', 'ultimate-member' ); ?>

<span class="um-online-status <?php echo esc_attr( $class ) ?> um-tip-n" title="<?php echo esc_attr( $title ) ?>">
	<i class="fas fa-circle"></i>
</span>
