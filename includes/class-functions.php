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
                $url = get_home_url( null, 'um-api/route/' . $route . '/' . $method . '/' . $nonce );
            } else {
                $url = add_query_arg( array(
                    'um_page'       => 'api',
                    'um_action'     => 'route',
                    'um_resource'   => $route,
                    'um_method'     => $method,
                    'um_verify'     => $nonce
                ), get_home_url() );
            }
            return $url;
        }


        /**
         * Set variables
         */
        function init_variables() {
            $this->options = get_option( 'um_options' );
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


        function excluded_taxonomies() {
            $taxes = array(
                'nav_menu',
                'link_category',
                'post_format',
            );

            return apply_filters( 'um_excluded_taxonomies', $taxes );
        }


        /**
         * Output templates
         *
         * @access public
         * @param string $template_name
         * @param string $basename (default: '')
         * @param array $t_args (default: array())
         * @param bool $echo
         *
         * @return string|void
         */
        function get_template( $template_name, $basename = '', $t_args = array(), $echo = false ) {
            if ( ! empty( $t_args ) && is_array( $t_args ) ) {
                extract( $t_args );
            }

            $path = '';
            if( $basename ) {
	            $array = explode( '/', trim( $basename, '/' ) );
	            $path  = $array[0];
            }

            $located = $this->locate_template( $template_name, $path );
            if ( ! file_exists( $located ) ) {
                _doing_it_wrong( __FUNCTION__, sprintf( '<code>%s</code> does not exist.', $located ), '2.1' );
                return;
            }

            $located = apply_filters( 'um_get_template', $located, $template_name, $path, $t_args );

            ob_start();
            do_action( 'um_before_template_part', $template_name, $path, $located, $t_args );
            include( $located );
            do_action( 'um_after_template_part', $template_name, $path, $located, $t_args );
            $html = ob_get_clean();

            if ( ! $echo ) {
                return $html;
            } else {
                echo $html;
                return;
            }
        }


        /**
         * Locate a template and return the path for inclusion.
         *
         * @access public
         * @param string $template_name
         * @param string $path (default: '')
         * @return string
         */
        function locate_template( $template_name, $path = '' ) {
            // check if there is template at theme folder
            $template = locate_template( array(
                trailingslashit( 'ultimate-member/' . $path ) . $template_name
            ) );

            if( !$template ) {
                if( $path ) {
                    $template = trailingslashit( trailingslashit( WP_PLUGIN_DIR ) . $path );
                } else {
                    $template = trailingslashit( um_path );
                }
                $template .= 'templates/' . $template_name;
            }
            // Return what we found.
            return apply_filters( 'um_locate_template', $template, $template_name, $path );
        }

    }

}