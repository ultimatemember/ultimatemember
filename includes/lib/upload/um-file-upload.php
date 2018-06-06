<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

$dirname = dirname( __FILE__ );
do {
    $dirname = dirname( $dirname );
    $wp_config = "{$dirname}/wp-config.php";
    $wp_load = "{$dirname}/wp-load.php";
}
while( !file_exists( $wp_config ) );

if ( ! file_exists( $wp_load ) ) {
    $dirs = glob( $dirname . '/*' , GLOB_ONLYDIR );

    foreach ( $dirs as $key => $value ) {
        $wp_load = "{$value}/wp-load.php";
        if ( file_exists( $wp_load ) ) {
            break;
        }
    }
}

require_once( $wp_load );

$ret['error'] = null;
$ret = array();

/* commented for enable download files on registration form
 * if ( ! is_user_logged_in() ) {
    $ret['error'] = 'Invalid user';
    die( json_encode( $ret ) );
}*/

$nonce = $_POST['_wpnonce'];
$id = $_POST['key'];
$timestamp = $_POST['timestamp'];

UM()->fields()->set_id = $_POST['set_id'];
UM()->fields()->set_mode = $_POST['set_mode'];

/**
 * UM hook
 *
 * @type filter
 * @title um_file_upload_nonce
 * @description Change File Upload nonce
 * @input_vars
 * [{"var":"$nonce","type":"bool","desc":"Nonce"}]
 * @change_log
 * ["Since: 2.0"]
 * @usage
 * <?php add_filter( 'um_file_upload_nonce', 'function_name', 10, 1 ); ?>
 * @example
 * <?php
 * add_filter( 'um_file_upload_nonce', 'my_file_upload_nonce', 10, 1 );
 * function my_file_upload_nonce( $nonce ) {
 *     // your code here
 *     return $nonce;
 * }
 * ?>
 */
$um_file_upload_nonce = apply_filters( "um_file_upload_nonce", true );

if(  $um_file_upload_nonce  ){
	if ( ! wp_verify_nonce( $nonce, 'um_upload_nonce-'.$timestamp  ) && is_user_logged_in()) {
	    // This nonce is not valid.
	    $ret['error'] = 'Invalid nonce';
	    die( json_encode( $ret ) );
	}
}



if(isset($_FILES[$id]['name'])) {

    if(!is_array($_FILES[$id]['name'])) {
	
		$temp = $_FILES[$id]["tmp_name"];

	    /**
	     * UM hook
	     *
	     * @type filter
	     * @title um_upload_file_name
	     * @description Change File Upload nonce
	     * @input_vars
	     * [{"var":"$filename","type":"string","desc":"Filename"},
	     * {"var":"$id","type":"int","desc":"ID"},
	     * {"var":"$name","type":"string","desc":"Name"}]
	     * @change_log
	     * ["Since: 2.0"]
	     * @usage
	     * <?php add_filter( 'um_upload_file_name', 'function_name', 10, 3 ); ?>
	     * @example
	     * <?php
	     * add_filter( 'um_upload_file_name', 'my_upload_file_name', 10, 3 );
	     * function my_upload_file_name( $filename, $id, $name ) {
	     *     // your code here
	     *     return $filename;
	     * }
	     * ?>
	     */
        $file = apply_filters( 'um_upload_file_name', $id . "-" . $_FILES[ $id ]["name"], $id, $_FILES[ $id ]["name"] );
		$file = sanitize_file_name( $file );
		$extension = strtolower( pathinfo( $file, PATHINFO_EXTENSION ) );
		
		$error = UM()->files()->check_file_upload( $temp, $extension, $id );
		if ( $error ){
			$ret['error'] = $error;
		} else {
			$ret[] = UM()->files()->new_file_upload_temp( $temp, $file );
			$ret['icon'] = UM()->files()->get_fonticon_by_ext( $extension );
			$ret['icon_bg'] = UM()->files()->get_fonticon_bg_by_ext( $extension );
			$ret['filename'] = $file;
		}

    }
	
} else {
	$ret['error'] = __('A theme or plugin compatibility issue','ultimate-member');
}
echo json_encode($ret);
