<?php
require_once '../../../../wp-load.php';

function um_legacy_emoji() {
	$base_uri = 'https://s.w.org/images/core/emoji/72x72/';

	$emojis = array(
		':)'                             => '1f604.png',
		':smiley:'                       => '1f603.png',
		':D'                             => '1f600.png',
		':$'                             => '1f60a.png',
		':relaxed:'                      => '263a.png',
		';)'                             => '1f609.png',
		':heart_eyes:'                   => '1f60d.png',
		':kissing_heart:'                => '1f618.png',
		':kissing_closed_eyes:'          => '1f61a.png',
		':kissing:'                      => '1f617.png',
		':kissing_smiling_eyes:'         => '1f619.png',
		';P'                             => '1f61c.png',
		':P'                             => '1f61b.png',
		':stuck_out_tongue_closed_eyes:' => '1f61d.png',
		':flushed:'                      => '1f633.png',
		':grin:'                         => '1f601.png',
		':pensive:'                      => '1f614.png',
		':relieved:'                     => '1f60c.png',
		':unamused'                      => '1f612.png',
		':('                             => '1f61e.png',
		':persevere:'                    => '1f623.png',
		":'("                            => '1f622.png',
		':joy:'                          => '1f602.png',
		':sob:'                          => '1f62d.png',
		':sleepy:'                       => '1f62a.png',
		':disappointed_relieved:'        => '1f625.png',
		':cold_sweat:'                   => '1f630.png',
		':sweat_smile:'                  => '1f605.png',
		':sweat:'                        => '1f613.png',
		':weary:'                        => '1f629.png',
		':tired_face:'                   => '1f62b.png',
		':fearful:'                      => '1f628.png',
		':scream:'                       => '1f631.png',
		':angry:'                        => '1f620.png',
		':rage:'                         => '1f621.png',
		':triumph'                       => '1f624.png',
		':confounded:'                   => '1f616.png',
		':laughing:'                     => '1f606.png',
		':yum:'                          => '1f60b.png',
		':mask:'                         => '1f637.png',
		':cool:'                         => '1f60e.png',
		':sleeping:'                     => '1f634.png',
		':dizzy_face:'                   => '1f635.png',
		':astonished:'                   => '1f632.png',
		':worried:'                      => '1f61f.png',
		':frowning:'                     => '1f626.png',
		':anguished:'                    => '1f627.png',
		':smiling_imp:'                  => '1f608.png',
		':imp:'                          => '1f47f.png',
		':open_mouth:'                   => '1f62e.png',
		':grimacing:'                    => '1f62c.png',
		':neutral_face:'                 => '1f610.png',
		':confused:'                     => '1f615.png',
		':hushed:'                       => '1f62f.png',
		':no_mouth:'                     => '1f636.png',
		':innocent:'                     => '1f607.png',
		':smirk:'                        => '1f60f.png',
		':expressionless:'               => '1f611.png',
	);
	array_walk(
		$emojis,
		function ( &$item1, $key, $prefix ) {
			$item1 = $prefix . $item1;
		},
		$base_uri
	);

	return $emojis;
}


$emojis = um_legacy_emoji();

$content_by_legacy_emotize     = '';
$content_by_wp_staticize_emoji = '';
$final = '';
foreach ( $emojis as $code => $val ) {
	$key     = $code;
	$content = $code;

	if ( strpos( $code, ')' ) !== false ) {
		$code = str_replace( ')', '\)', $code );
	}

	if ( strpos( $code, '(' ) !== false ) {
		$code = str_replace( '(', '\(', $code );
	}

	if ( strpos( $code, '$' ) !== false ) {
		$code = str_replace( '$', '\$', $code );
	}

	$content = preg_replace( "~(?i)<a.*?</a>(*SKIP)(*F)|{$code}~", '<img src="' . $val . '" alt="' . $code . '" title="' . $code . '" class="emoji" />', $content );

	$content_by_legacy_emotize     .= '<b>' . $key . '</b>:' . $content . ';<br/>';
	$content_by_wp_staticize_emoji .= '<b>' . $key . '</b>:' . wp_staticize_emoji( convert_smilies( $key ) ) . ';<br/>';
	$final .= '<b>' . $key . '</b>:' . UM()->shortcodes()->emotize( convert_smilies( $key ) ) . ';&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' . wp_staticize_emoji( UM()->shortcodes()->emotize( convert_smilies( $key ) ) ) . ';<br/>';
}

echo 'Legacy<br />' . $content_by_legacy_emotize;
echo '<br />WP native<br />' . $content_by_wp_staticize_emoji;
echo '<br />Final result<br />' . $final;
exit;
