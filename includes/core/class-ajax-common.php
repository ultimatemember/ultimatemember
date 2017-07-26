<?php
namespace um\core;

// Exit if executed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'AJAX_Common' ) ) {
    class AJAX_Common {

        /**
         * AJAX_Common constructor.
         */
        function __construct() {
            // UM_EVENT => nopriv
            $ajax_actions = array(
                'router'   => false
            );

            foreach ( $ajax_actions as $action => $nopriv ) {

                add_action( 'wp_ajax_um_' . $action, array( $this, $action ) );

                if ( $nopriv )
                    add_action( 'wp_ajax_nopriv_um_' . $action, array( $this, $action ) );

            }


            /**
             * Fallback for ajax urls
             * @uses action hooks: wp_head, admin_head
             */
            add_action( 'wp_head', array( $this, 'ultimatemember_ajax_urls' ) );
            add_action( 'admin_head', array( $this, 'ultimatemember_ajax_urls' ) );

        }


        function ultimatemember_ajax_urls() {
            $enable_ajax_urls = apply_filters( "um_enable_ajax_urls", true );

            if ( $enable_ajax_urls ) { ?>

                <script type="text/javascript">

                    var ultimatemember_image_upload_url = '<?php echo um_url . 'includes/lib/upload/um-image-upload.php'; ?>';
                    var ultimatemember_file_upload_url = '<?php echo um_url . 'includes/lib/upload/um-file-upload.php'; ?>';
                    var ultimatemember_ajax_url = '<?php echo admin_url('admin-ajax.php'); ?>';

                </script>

            <?php }
        }


        /**
         * Router method
         */
        function router() {
            $router = new Router();
            $router->backend_requests();
        }
    }
}