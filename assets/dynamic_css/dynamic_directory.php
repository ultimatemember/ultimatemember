<style type="text/css">
<?php

if ($css_profile_card_bg) {

print ".um-$form_id .um-member {
	background: $css_profile_card_bg;
}";

}

if ($css_profile_card_text) {

print ".um-$form_id .um-member-card * {
	color: $css_profile_card_text!important;
}";

}

if ($css_card_bordercolor) {

print ".um-$form_id .um-member {
	border-color: $css_card_bordercolor!important;
}";

}

if ($css_img_bordercolor) {

print ".um-$form_id .um-member-photo img {
	border-color: $css_img_bordercolor!important;
}";

}

if ($css_stats_bg) {

print ".um-$form_id .um-member-stats {
	background: $css_stats_bg!important;
}";

}

if ($css_stats_bordercolor) {

print ".um-$form_id .um-member-stats {
	border-color: $css_stats_bordercolor!important;
}";
print ".um-$form_id .um-member-stat {
	border-color: $css_stats_bordercolor!important;
}";

}

if ($css_stats_num_color) {

print ".um-$form_id .um-member-stat, .um-$form_id .um-member-stat a {
	color: $css_stats_num_color!important;
}";

}

if ($css_stats_lbl_color) {

print ".um-$form_id .um-member-statname {
	color: $css_stats_lbl_color!important;
}";

}

?>
</style>