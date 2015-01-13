<?php

class UM_FontIcons {

	function __construct() {

		if ( !get_option('um_cache_fonticons') ) {
			$file = um_path . 'assets/css/um-fonticons.css';
			$css = file_get_contents($file);

			preg_match_all('/(um-icon-.*?)\s?\{/', $css, $matches);
			unset($matches[1][0]);
			foreach($matches[1] as $match) {
				$icon = str_replace(':before','',$match);
				$array[] = $icon;
			}
			
			update_option('um_cache_fonticons', $array);
		}
		
		$this->all = get_option('um_cache_fonticons');
		
	}

}