<?php
namespace um\core;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Account' ) ) {
    class Account {

        var $tabs;
        var $current_tab = 'general';
        var $register_fields = array();
        var $tab_output = array();


        function __construct() {

            add_shortcode( 'ultimatemember_account', array( &$this, 'ultimatemember_account' ) );

            add_action( 'template_redirect', array( &$this, 'account_page_restrict' ), 10001 );

            add_action( 'template_redirect', array( &$this, 'account_submit' ), 10002 );

            add_filter( 'um_predefined_fields_hook', array( &$this, 'predefined_fields_hook' ), 1 );

        }


        /**
         * Init AllTabs for user account
         *
         * @param $args
         */
        function init_tabs( $args ) {

            $tabs[100]['general'] = array(
                'icon'          => 'um-faicon-user',
                'title'         => __( 'Account', 'ultimate-member' ),
                'submit_title'  => __( 'Update Account', 'ultimate-member' ),
            );

            $tabs[200]['password'] = array(
                'icon'          => 'um-faicon-asterisk',
                'title'         => __( 'Change Password', 'ultimate-member' ),
                'submit_title'  => __( 'Update Password', 'ultimate-member' ),
            );

            $tabs[300]['privacy'] = array(
                'icon'          => 'um-faicon-lock',
                'title'         => __( 'Privacy', 'ultimate-member' ),
                'submit_title'  => __( 'Update Privacy', 'ultimate-member' ),
            );

            $tabs[400]['notifications'] = array(
                'icon'          => 'um-faicon-envelope',
                'title'         => __( 'Notifications', 'ultimate-member' ),
                'submit_title'  => __( 'Update Notifications', 'ultimate-member' ),
            );

            //if user cannot delete profile hide delete tab
            if ( um_user( 'can_delete_profile' ) || um_user( 'can_delete_everyone' ) ) {

                $tabs[99999]['delete'] = array(
                    'icon'          => 'um-faicon-trash-o',
                    'title'         => __( 'Delete Account', 'ultimate-member' ),
                    'submit_title'  => __( 'Delete Account', 'ultimate-member' ),
                );

            }

            $this->tabs = apply_filters( 'um_account_page_default_tabs_hook', $tabs );

            ksort( $this->tabs );

            $tabs_structed = array();
            foreach ( $this->tabs as $k => $arr ) {

                foreach ( $arr as $id => $info ) {

                    if ( ! empty( $args['tab'] ) && $id != $args['tab'] )
                        continue;

                    $output = $this->get_tab_fields( $id, $args );

                    if ( ! empty( $output ) )
                        $tabs_structed[$id] = $info;

                }

            }

            $this->tabs = $tabs_structed;
        }


        /**
         * Account Shortcode
         *
         * @param array $args
         * @return string
         */
        function ultimatemember_account( $args = array() ) {
            um_fetch_user( get_current_user_id() );

            ob_start();

            $defaults = array(
                'template' => 'account',
                'mode' => 'account',
                'form_id' => 'um_account_id',
            );
            $args = wp_parse_args( $args, $defaults );

            $args = apply_filters( 'um_account_shortcode_args_filter', $args );

            if ( ! empty( $args['tab'] ) ) {

                if ( $args['tab'] == 'account' )
                    $args['tab'] = 'general';

                $this->init_tabs( $args );

                $this->current_tab = $args['tab'];

                if ( ! empty( $this->tabs[$args['tab']] ) ) { ?>
                    <div class="um-form">
                        <form method="post" action="">
                            <?php do_action( 'um_account_page_hidden_fields', $args );
                            $this->render_account_tab( $args['tab'], $this->tabs[$args['tab']], $args );  ?>
                        </form>
                    </div>
                <?php }

            } else {

                $this->init_tabs( $args );

                do_action( "um_pre_{$args['mode']}_shortcode", $args );

                do_action( "um_before_form_is_loaded", $args );

                do_action( "um_before_{$args['mode']}_form_is_loaded", $args );

                UM()->shortcodes()->template_load( $args['template'], $args );

            }

            if ( ! is_admin() && ! defined( 'DOING_AJAX' ) ) {
                UM()->shortcodes()->dynamic_css( $args );
            }

            $output = ob_get_clean();

            return $output;
        }


        /**
         * Restrict access to Account page
         */
        function account_page_restrict() {

            if ( um_is_core_page( 'account' ) ) {

                //redirect to login for not logged in users
                if ( ! is_user_logged_in() ) {
                    $redirect_to = add_query_arg(
                        'redirect_to',
                        urlencode_deep( um_get_core_page( 'account' ) ) ,
                        um_get_core_page( 'login' )
                    );

                    exit( wp_redirect( $redirect_to ) );
                }


                //set data for fields
                UM()->fields()->set_mode = 'account';
                UM()->fields()->editing = true;

                if ( get_query_var('um_tab') )
                    $this->current_tab = get_query_var('um_tab');

            }
        }


        /**
         * Submit Account handler
         */
        function account_submit() {

            if ( um_submitting_account_page() ) {

                UM()->form()->post_form = $_POST;

                //validate process
                do_action( 'um_submit_account_errors_hook', UM()->form()->post_form );

                if ( ! isset( UM()->form()->errors ) ) {

                    if ( um_is_core_page( 'account' ) && get_query_var( 'um_tab' ) ) {
                        $this->current_tab = get_query_var( 'um_tab' );
                    } else {
                        $this->current_tab = UM()->form()->post_form['_um_account_tab'];
                    }

                    do_action( 'um_submit_account_details', UM()->form()->post_form );

                }

            }

        }


        /**
         * Filter account fields
         * @param  array $predefined_fields
         * @return array
         */
        function predefined_fields_hook( $predefined_fields ) {

            $account_hide_in_directory =  UM()->options()->get( 'account_hide_in_directory' );

            if ( ! $account_hide_in_directory )
                unset( $predefined_fields['hide_in_members'] );

            return $predefined_fields;
        }


        /**
         * Get Tab Link
         * @param  integer $id
         * @return string
         */
        function tab_link( $id ) {

            if ( get_option('permalink_structure') ) {

                $url = trailingslashit( untrailingslashit( um_get_core_page('account') ) );
                $url = $url . $id . '/';

            } else {

                $url = add_query_arg( 'um_tab', $id, um_get_core_page('account') );

            }

            return $url;
        }


        /**
         * @param $fields
         * @param $shortcode_args
         * @return mixed
         */
        function filter_fields_by_attrs( $fields, $shortcode_args ) {

            foreach ( $fields as $k => $field ) {
                if ( isset( $shortcode_args[$field['metakey']] ) && 0 == $shortcode_args[$field['metakey']] )
                    unset( $fields[$k] );
            }

            return $fields;

        }


        /**
         * * Get Tab Output
         *
         * @param integer $id
         * @param array $shortcode_args
         * @return mixed|null|string|void
         */
        function get_tab_fields( $id, $shortcode_args ) {
            $output = null;

            UM()->fields()->set_mode = 'account';
            UM()->fields()->editing = true;

            if ( ! empty( $this->tab_output[$id]['content'] ) && ! empty( $this->tab_output[$id]['hash'] ) &&
                $this->tab_output[$id]['hash'] == md5( json_encode( $shortcode_args ) ) )
                return $this->tab_output[$id]['content'];

            switch ( $id ) {

                case 'privacy':

                    $args = 'profile_privacy,hide_in_members';
                    $args = apply_filters( 'um_account_tab_privacy_fields', $args, $shortcode_args );

                    $fields = UM()->builtin()->get_specific_fields( $args );

                    $fields = apply_filters( 'um_account_secure_fields', $fields, $id );

                    $fields = $this->filter_fields_by_attrs( $fields, $shortcode_args );

                    foreach ( $fields as $key => $data ){
                        $output .= UM()->fields()->edit_field( $key, $data );
                    }

                    break;

                case 'delete':

                    $args = 'single_user_password';

	                $args = apply_filters( 'um_account_tab_delete_fields', $args, $shortcode_args );

                    $fields = UM()->builtin()->get_specific_fields( $args );

                    $fields = apply_filters( 'um_account_secure_fields', $fields, $id );

                    $fields = $this->filter_fields_by_attrs( $fields, $shortcode_args );

                    foreach ( $fields as $key => $data ) {
                        $output .= UM()->fields()->edit_field( $key, $data );
                    }

                    break;

                case 'general':

                    $args = 'user_login,first_name,last_name,user_email';

                    if ( ! UM()->options()->get( 'account_name' ) ) {
                        $args = 'user_login,user_email';
                    }

                    if ( ! UM()->options()->get( 'account_email' ) && ! um_user( 'can_edit_everyone' ) ) {
                        $args = str_replace(',user_email','', $args );
                    }

	                $args = apply_filters( 'um_account_tab_general_fields', $args, $shortcode_args );

                    $fields = UM()->builtin()->get_specific_fields( $args );

                    $fields = apply_filters( 'um_account_secure_fields', $fields, $id );

                    $fields = $this->filter_fields_by_attrs( $fields, $shortcode_args );

                    foreach ( $fields as $key => $data ) {
                        $output .= UM()->fields()->edit_field( $key, $data );
                    }

                    break;

                case 'password':

                    $args = 'user_password';

	                $args = apply_filters( 'um_account_tab_password_fields', $args, $shortcode_args );

                    $fields = UM()->builtin()->get_specific_fields( $args );

                    $fields = apply_filters( 'um_account_secure_fields', $fields, $id );

                    $fields = $this->filter_fields_by_attrs( $fields, $shortcode_args );

                    foreach ( $fields as $key => $data ) {
                        $output .= UM()->fields()->edit_field( $key, $data );
                    }

                    break;

                default :

                    $output = apply_filters( "um_account_content_hook_{$id}", $output, $shortcode_args );
                    break;

            }

            $this->tab_output[$id] = array( 'content' => $output, 'hash' => md5( json_encode( $shortcode_args ) ) );
            return $output;
        }


        /**
         * Render Account Tab HTML
         *
         * @param $tab_id
         * @param $tab_data
         * @param $args
         */
        function render_account_tab( $tab_id, $tab_data, $args ) {

            $output = $this->get_tab_fields( $tab_id, $args );

            if ( $output ) {

                if ( ! empty ( $tab_data['with_header'] ) ) { ?>

                    <div class="um-account-heading uimob340-hide uimob500-hide"><i class="<?php echo $tab_data['icon'] ?>"></i><?php echo $tab_data['title']; ?></div>

                <?php }

                do_action( "um_before_account_{$tab_id}", $args );

                echo $output;

                do_action( "um_after_account_{$tab_id}", $args );

                if ( ! isset( $tab_data['show_button'] ) || false !== $tab_data['show_button'] ) { ?>

                    <div class="um-col-alt um-col-alt-b">
                        <div class="um-left">
                            <input type="submit" name="um_account_submit" id="um_account_submit"  class="um-button" value="<?php echo ! empty( $tab_data['submit_title'] ) ? $tab_data['submit_title'] : $tab_data['title']; ?>" />
                        </div>

                        <?php do_action( "um_after_account_{$tab_id}_button" ); ?>

                        <div class="um-clear"></div>
                    </div>

                <?php }
            }
        }


        /**
         * Add class based on shortcode
         *
         * @param  string $mode
         * @return string
         */
        function get_class( $mode ) {

            $classes = 'um-'.$mode;

            if ( is_admin() ) {
                $classes .= ' um-in-admin';
            }

            if ( UM()->fields()->editing == true ) {
                $classes .= ' um-editing';
            }

            if ( UM()->fields()->viewing == true ) {
                $classes .= ' um-viewing';
            }

            $classes = apply_filters('um_form_official_classes__hook', $classes);
            return $classes;
        }
    }
}