<?php
namespace um\common;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Color
 *
 * @package um\common
 *
 * @since 2.8.4
 */
class Color {

	/**
	 * Mix
	 *
	 * @param  array     $color_1 RGB color 1.
	 * @param  array     $color_2 RGB color 2.
	 * @param  float|int $weight  Mix weight.
	 *
	 * @return array
	 */
	private function mix( $color_1 = array( 0, 0, 0 ), $color_2 = array( 0, 0, 0 ), $weight = 0.5 ) {
		$f = function ( $x ) use ( $weight ) {
			return $weight * $x;
		};

		$g = function ( $x ) use ( $weight ) {
			return ( 1 - $weight ) * $x;
		};

		$h = function ( $x, $y ) {
			return round( $x + $y );
		};

		return array_map( $h, array_map( $f, $color_1 ), array_map( $g, $color_2 ) );
	}

	/**
	 * @param $base_color
	 * @param $color
	 * @param $type
	 *
	 * @return float|int
	 */
	private function get_weight( $base_color, $color, $type = 'tint' ) {
		$t1 = $base_color;
		$t2 = $color;

		if ( is_string( $base_color ) ) {
			$t1 = $this->hex2rgb( $base_color );
		}

		if ( is_string( $base_color ) ) {
			$t2 = $this->hex2rgb( $color );
		}

		$base_r = $t1[0];
		$col2_r = $t2[0];

		if ( 'tint' === $type ) {
			$mix_r = 255;
		} else {
			$mix_r = 0;
		}

		return ( $mix_r - $col2_r ) / ( $mix_r - $base_r );
	}

	/**
	 * Generates design palette based on 1 brand color.
	 *
	 * @param string $base_color Base (brand) design color.
	 *
	 * @return array[]
	 */
	public function generate_palette( $base_color ) {
		$palette = array(
			'600' => array(
				'bg' => $base_color,
				'fg' => $this->hex_inverse_bw( $base_color ),
			),
		);

		$tint_map = array(
			'500' => 0.7578125,
			'400' => 0.5703125,
			'300' => 0.3203125,
			'200' => 0.171875,
			'100' => 0.0859375,
			'50'  => 0.046875,
			'25'  => 0.0234375,
		);

		foreach ( $tint_map as $k => $weight ) {
			$bg            = $this->tint( $base_color, $weight );
			$fg            = $this->hex_inverse_bw( $bg );
			$palette[ $k ] = array(
				'bg' => $bg,
				'fg' => $fg,
			);
		}

		$shade_map = array(
			'700' => 0.8267716535433071,
			'800' => 0.6535433070866141,
			'900' => 0.5196850393700787,
		);

		foreach ( $shade_map as $k => $weight ) {
			$bg            = $this->shade( $base_color, $weight );
			$fg            = $this->hex_inverse_bw( $bg );
			$palette[ $k ] = array(
				'bg' => $bg,
				'fg' => $fg,
			);
		}

		ksort( $palette, SORT_NUMERIC );

		return $palette;
	}

	/**
	 * Tint.
	 *
	 * @param string|array $color  Base color in HEX or RGB.
	 * @param float|int    $weight Weight of tint.
	 *
	 * @return string|array
	 */
	public function tint( $color, $weight = 0.5 ) {
		if ( $weight > 1 ) {
			$weight = 1;
		}

		$t = $color;

		if ( is_string( $color ) ) {
			$t = $this->hex2rgb( $color );
		}

		$u = $this->mix( $t, array( 255, 255, 255 ), $weight );

		if ( is_string( $color ) ) {
			return $this->rgb2hex( $u );
		}

		return $u;
	}

	/**
	 * Inverse and convert to HEX.
	 *
	 * @param string|array $color Base color.
	 *
	 * @return string
	 */
	public function hex_inverse_bw( $color ) {
		$t = $color;

		if ( is_string( $color ) ) {
			$t = $this->hex2rgb( $color );
		}

		list( $r, $g, $b ) = $t;

		$luminance = ( 0.2126 * $r + 0.7152 * $g + 0.0722 * $b );

		return $luminance < 140 ? '#ffffff' : '#000000';
	}

	/**
	 * Tone.
	 *
	 * @param string|array $color  Base color in HEX or RGB.
	 * @param float|int    $weight Weight of tone.
	 *
	 * @return string|array
	 */
	public function tone( $color, $weight = 0.5 ) {
		$t = $color;

		if ( is_string( $color ) ) {
			$t = $this->hex2rgb( $color );
		}

		$u = $this->mix( $t, array( 128, 128, 128 ), $weight );

		if ( is_string( $color ) ) {
			return $this->rgb2hex( $u );
		}

		return $u;
	}

	/**
	 * Shade.
	 *
	 * @param string|array $color  Base color in HEX or RGB.
	 * @param float|int    $weight Weight of shade. Max 1
	 *
	 * @return string|array
	 */
	public function shade( $color, $weight = 0.5 ) {
		if ( $weight > 1 ) {
			$weight = 1;
		}

		$t = $color;

		if ( is_string( $color ) ) {
			$t = $this->hex2rgb( $color );
		}

		$u = $this->mix( $t, array( 0, 0, 0 ), $weight );

		if ( is_string( $color ) ) {
			return $this->rgb2hex( $u );
		}

		return $u;
	}

	/**
	 * hex2rgb
	 *
	 * @param string $hex
	 *
	 * @return array
	 */
	public function hex2rgb( $hex = '#000000' ) {
		$f = function ( $x ) {
			return hexdec( $x );
		};

		return array_map( $f, str_split( str_replace( '#', '', $hex ), 2 ) );
	}

	/**
	 * rgb2hex
	 *
	 * @param array $rgb
	 *
	 * @return string
	 */
	public function rgb2hex( $rgb = array( 0, 0, 0 ) ) {
		$f = function ( $x ) {
			return str_pad( dechex( $x ), 2, '0', STR_PAD_LEFT );
		};

		return '#' . implode( '', array_map( $f, $rgb ) );
	}
}
