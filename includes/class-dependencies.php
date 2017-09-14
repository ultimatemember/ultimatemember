<?php
namespace um;

// Exit if executed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Ultimate Member Dependency Checker
 *
 * Checks if Ultimate Member plugin is enabled
 */
if ( ! class_exists( 'um\Dependencies' ) ) {
    class Dependencies {

        private static $active_plugins;

        /**
         * For backward compatibility checking
         *
         * @var array
         */
        public $ext_required_version = array(
            'bbpress'               => '2.0-beta1',
            'followers'             => '2.0-beta1',
            'friends'               => '2.0-beta1',
            'groups'                => '2.0-beta1',
            'instagram'             => '2.0-beta1',
            'invitations'           => '2.0-beta1',
            'mailchimp'             => '2.0-beta1',
            'messaging'             => '2.0-beta1',
            'mycred'                => '2.0-beta1',
            'notices'               => '2.0-beta1',
            'notifications'         => '2.0-beta1',
            'online'                => '2.0-beta1',
            'private-content'       => '2.0-beta1',
            'profile-completeness'  => '2.0-beta1',
            'recaptcha'             => '2.0-beta1',
            'reviews'               => '2.0-beta1',
            'social-activity'       => '2.0-beta1',
            'social-login'          => '2.0-beta1',
            'terms-conditions'      => '2.0-beta1',
            'user-tags'             => '2.0-beta1',
            'verified-users'        => '2.0-beta1',
            'woocommerce'           => '2.0-beta1',
        );

        /**
         * Get all active plugins
         */
        public static function init() {

            self::$active_plugins = (array) get_option( 'active_plugins', array() );

            if ( is_multisite() )
                self::$active_plugins = array_merge( self::$active_plugins, get_site_option( 'active_sitewide_plugins', array() ) );
        }


        /**
         * Check if UltimateMember core plugin is active
         *
         * @return bool
         */
        public static function ultimatemember_active_check() {

            if ( ! self::$active_plugins ) self::init();

            return in_array( 'ultimate-member/ultimate-member.php', self::$active_plugins ) || array_key_exists( 'ultimate-member/ultimate-member.php', self::$active_plugins );

        }


        /**
         * Check if bbPress plugin is active
         *
         * @return bool
         */
        public static function bbpress_active_check() {

            if ( ! self::$active_plugins ) self::init();

            return in_array( 'bbpress/bbpress.php', self::$active_plugins ) || array_key_exists( 'bbpress/bbpress.php', self::$active_plugins );

        }


        /**
         * Check if myCRED plugin is active
         *
         * @return bool
         */
        public static function mycred_active_check() {

            if ( ! self::$active_plugins ) self::init();

            return in_array( 'mycred/mycred.php', self::$active_plugins ) || array_key_exists( 'mycred/mycred.php', self::$active_plugins );

        }


        /**
         * Check if Woocommerce plugin is active
         *
         * @return bool
         */
        public static function woocommerce_active_check() {

            if ( ! self::$active_plugins ) self::init();

            return in_array( 'woocommerce/woocommerce.php', self::$active_plugins ) || array_key_exists( 'woocommerce/woocommerce.php', self::$active_plugins );

        }


        /**
         * Compare UM core and extension versions
         *
         * @param string $um_required_ver
         * @param string $ext_ver
         * @param string $ext_key
         * @param string $ext_title
         * @return bool
         */
        public function compare_versions( $um_required_ver, $ext_ver, $ext_key, $ext_title ) {

            if ( version_compare( ultimatemember_version, $um_required_ver, '<' )
                || empty( $this->ext_required_version[$ext_key] )
                || version_compare( $this->ext_required_version[$ext_key], $ext_ver, '>' ) ) {

                $message = '';
                if ( version_compare( ultimatemember_version, $um_required_ver, '<' ) ) {
                    $message = sprintf( __( 'Sorry, but for this version of extension <strong>"%s"</strong> is required version of the <strong>%s</strong> core not lower than <strong>%s</strong>.', 'ultimate-member' ), $ext_title, ultimatemember_plugin_name, $um_required_ver ) .
                        '<br />' .
                        sprintf( __( 'Please update %s core to latest version or install previous versions of this extension.', 'ultimate-member' ), ultimatemember_plugin_name );
                } elseif ( empty( $this->ext_required_version[$ext_key] ) || version_compare( $this->ext_required_version[$ext_key], $ext_ver, '>' ) ) {
                    $message = sprintf( __( 'Sorry, but this version of <strong>%s</strong> does not work with extension <strong>"%s" %s</strong> version.', 'ultimate-member' ), ultimatemember_plugin_name, $ext_title, $ext_ver ) .
                        '<br />' .
                        sprintf( __( 'Please update extension <strong>"%s"</strong> to the latest version, or install previous versions of <strong>%s</strong>.', 'ultimate-member' ), $ext_title, ultimatemember_plugin_name );
                }

                return $message;
            }

            return true;
        }


        /**
         * @param string $extension_version Extension version
         * @return mixed
         */
        public static function php_version_check( $extension_version ) {

            return version_compare( phpversion(), $extension_version, '>=' );

        }

    }
}


if ( ! function_exists( 'is_um_active' ) ) {
    /**
     * Check UltimateMember core is active
     *
     * @return bool active - true | inactive - false
     */
    function is_um_active() {
        return Dependencies::ultimatemember_active_check();
    }
}