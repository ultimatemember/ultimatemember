<style type="text/css">
<?php

if ( isset( $max_width ) && $max_width) {
print ".um-$form_id.um {
	max-width: $max_width;
}";
}

if ( isset( $align ) && in_array( $align, array( 'left', 'right' ) ) ) {
print ".um-$form_id.um {
	margin-$align: 0px !important;
}";
}

?>
</style>
