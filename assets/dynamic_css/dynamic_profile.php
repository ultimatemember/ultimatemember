<style type="text/css">
<?php

$photosize = str_replace('px','',$photosize);
$photosize_up = ( $photosize / 2 ) + 10;
$meta_padding = ( $photosize + 60 ) . 'px';

if ( $area_max_width ) {
print "
.um-$form_id.um .um-profile-body {
	max-width: $area_max_width;
}
";
}

print "
.um-$form_id.um .um-profile-photo a.um-profile-photo-img {
	width: ".$photosize."px;
	height: ".$photosize."px;
}
";

print "
.um-$form_id.um .um-profile-photo a.um-profile-photo-img {
	top: -".$photosize_up."px;
}
";

print "
.um-$form_id.um .um-profile-meta {
	padding-left: $meta_padding;
}
";
?>
</style>