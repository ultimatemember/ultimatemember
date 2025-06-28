<?php
namespace um\common\apis;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Google_Maps_Api
 *
 * @package um\common\apis
 *
 * @since 2.9.3
 */
class Google_Maps_Api {

	/**
	 * @return string
	 */
	public static function get_locale() {
		/**
		 * @return array
		 */
		$default_locale = UM()->options()->get( 'um_google_lang_as_default' );
		if ( $default_locale ) {
			$locale  = get_locale();
			$locales = array_keys( UM()->config()->get( 'google_maps_locales' ) );
			if ( ! in_array( $locale, $locales, true ) ) {
				$locale = str_replace( '_', '-', $locale );
				if ( ! in_array( $locale, $locales, true ) ) {
					$locale = explode( '-', $locale );
					if ( isset( $locale[1] ) ) {
						$locale = $locale[1];
					}
				}
			}
		} else {
			$locale = UM()->options()->get( 'um_google_lang' );
		}

		return $locale;
	}

	/**
	 * Singleton for Google Maps Javascript API Dynamic Library Import
	 * @return void
	 */
	public function add_inline_script() {
		static $script_added = false;
		if ( ! $script_added ) {
			ob_start();
			?>
			(g=>{var h,a,k,p="The Google Maps JavaScript API",c="google",l="importLibrary",q="__ib__",m=document,b=window;b=b[c]||(b[c]={});var d=b.maps||(b.maps={}),r=new Set,e=new URLSearchParams,u=()=>h||(h=new Promise(async(f,n)=>{await (a=m.createElement("script"));e.set("libraries",[...r]+"");for(k in g)e.set(k.replace(/[A-Z]/g,t=>"_"+t[0].toLowerCase()),g[k]);e.set("callback",c+".maps."+q);a.src=`https://maps.${c}apis.com/maps/api/js?`+e;d[q]=f;a.onerror=()=>h=n(Error(p+" could not load."));a.nonce=m.querySelector("script[nonce]")?.nonce||"";m.head.append(a)}));d[l]?console.warn(p+" only loads once. Ignoring:",g):d[l]=(f,...n)=>r.add(f)&&u().then(()=>d[l](f,...n))})({
				key: "<?php echo esc_js( UM()->options()->get( 'um_google_maps_js_api_key' ) ); ?>",
				v: "weekly",
			});
			<?php
			$script = ob_get_clean();
			wp_add_inline_script( 'um_common', $script, 'before' );
		}
		$script_added = true;
	}
}
