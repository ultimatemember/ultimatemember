<?php
namespace um\admin;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Admin' ) ) {

    class Admin {
        var $templates_path;

        function __construct() {
            $this->templates_path = um_path . 'includes/admin/templates/';

            add_action( 'admin_init', array( &$this, 'admin_init' ), 0 );

        }





        /**
         * Init admin action/filters + request handlers
         */
        function admin_init() {
            require_once 'core/um-admin-actions-user.php';
            require_once 'core/um-admin-actions-modal.php';
            require_once 'core/um-admin-actions.php';

            require_once 'core/um-admin-filters-fields.php';

            if ( is_admin() && current_user_can('manage_options') &&
                ! empty( $_REQUEST['um_adm_action'] ) ) {
                do_action( "um_admin_do_action__", $_REQUEST['um_adm_action'] );
                do_action( "um_admin_do_action__{$_REQUEST['um_adm_action']}", $_REQUEST['um_adm_action'] );
            }

        }

    }

}