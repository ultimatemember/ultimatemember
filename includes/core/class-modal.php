<?php
namespace um\core;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Modal' ) ) {
    class Modal {

        function __construct() {

            add_action('wp_footer', array(&$this, 'load_modal_content'), 9);

        }

        /***
         ***	@Load modal content
         ***/
        function load_modal_content(){
            if ( !is_admin() ) {
                foreach( glob( um_path . 'templates/modal/*.php' ) as $modal_content) {
                    include_once $modal_content;
                }
            }

        }

    }
}