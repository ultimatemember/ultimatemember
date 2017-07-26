<?php
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'UM_Functions' ) ) {

    class UM_Functions {

        var $options;


        /**
         * @var array variable for Flags
         */
        var $screenload_flags;


        function __construct() {

            $this->init_variables();

        }


        /**
         * What type of request is this?
         *
         * @param string $type String containing name of request type (ajax, frontend, cron or admin)
         *
         * @return bool
         */
        public function is_request( $type ) {
            switch ( $type ) {
                case 'admin' :
                    return is_admin();
                case 'ajax' :
                    return defined( 'DOING_AJAX' );
                case 'cron' :
                    return defined( 'DOING_CRON' );
                case 'frontend' :
                    return ( ! is_admin() || defined( 'DOING_AJAX' ) ) && ! defined( 'DOING_CRON' );
            }

            return false;
        }


        /**
         * Get ajax routed URL
         *
         * @param string $route
         * @param string $method
         *
         * @return string
         */
        public function get_ajax_route( $route, $method ) {

            $route = str_replace( array( '\\', '/' ), '!', $route );
            $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : '';
            $nonce = wp_create_nonce( $ip . get_current_user_id() . $route . $method );

            if ( is_admin() ) {
                $url = add_query_arg( array(
                    'action'        => 'um_router',
                    'um_action'     => 'route',
                    'um_resource'   => $route,
                    'um_method'     => $method,
                    'um_verify'     => $nonce
                ), get_admin_url( null, 'admin-ajax.php' ) );
            } else if ( get_option( 'permalink_structure' ) ) {
                $url = get_site_url( null, 'um-api/route/' . $route . '/' . $method . '/' . $nonce );
            } else {
                $url = add_query_arg( array(
                    'um_page'       => 'api',
                    'um_action'     => 'route',
                    'um_resource'   => $route,
                    'um_method'     => $method,
                    'um_verify'     => $nonce
                ), get_site_url() );
            }
            return $url;
        }


        /**
         * Set variables
         */
        function init_variables() {
            $this->options = get_option( 'um_options' );
        }


        function um_get_option( $option_id ) {
            if ( isset( $this->options[ $option_id ] ) )
                return apply_filters( "um_get_option_filter__{$option_id}", $this->options[ $option_id ] );

            switch ( $option_id ) {
                case 'site_name':
                    return get_bloginfo( 'name' );
                    break;
                case 'admin_email':
                    return get_bloginfo( 'admin_email' );
                    break;
                default:
                    return '';
                    break;

            }
        }


        function um_update_option( $option_id, $value ) {
            $this->options[ $option_id ] = $value;
            update_option( 'um_options', $this->options );
        }


        function um_remove_option( $option_id ) {
            if ( ! empty( $this->options[ $option_id ] ) )
                unset( $this->options[ $option_id ] );

            update_option( 'um_options', $this->options );
        }


        function um_get_default( $option_id ) {
            $settings_defaults = UM()->config()->settings_defaults;
            if ( ! isset( $settings_defaults[$option_id] ) )
                return false;

            return $settings_defaults[$option_id];
        }


        /**
         * Help Tip displaying
         *
         * Function for render/displaying UltimateMember help tip
         *
         * @since  2.0.0
         *
         * @param string $tip Help tip text
         * @param bool $allow_html Allow sanitized HTML if true or escape
         * @param bool $echo Return HTML or echo
         * @return string
         */
        function tooltip( $tip, $allow_html = false, $echo = true ) {
            if ( $allow_html ) {

                $tip = htmlspecialchars( wp_kses( html_entity_decode( $tip ), array(
                    'br'     => array(),
                    'em'     => array(),
                    'strong' => array(),
                    'small'  => array(),
                    'span'   => array(),
                    'ul'     => array(),
                    'li'     => array(),
                    'ol'     => array(),
                    'p'      => array(),
                ) ) );

            } else {
                $tip = esc_attr( $tip );
            }

            ob_start(); ?>

            <span class="um_tooltip dashicons dashicons-editor-help" title="<?php echo $tip ?>"></span>

            <?php if ( $echo ) {
                ob_get_flush();
                return '';
            } else {
                return ob_get_clean();
            }

        }

    }

}