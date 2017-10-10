<?php
namespace um\core;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Templates' ) ) {
    class Templates {

        function __construct() {

            $this->message_mode = false;

            $this->loop = array();

            add_shortcode( 'ultimatemember', array( &$this, 'ultimatemember' ) );

            add_shortcode('um_loggedin', array(&$this, 'um_loggedin'));
            add_shortcode('um_loggedout', array(&$this, 'um_loggedout'));
            add_shortcode('um_show_content', array(&$this, 'um_shortcode_show_content_for_role') );
            add_shortcode('ultimatemember_searchform', array(&$this, 'ultimatemember_searchform') );


            add_filter('body_class', array(&$this, 'body_class'), 0);

            $base_uri = apply_filters('um_emoji_base_uri', 'https://s.w.org/images/core/emoji/');

            $this->emoji[':)'] = $base_uri . '72x72/1f604.png';
            $this->emoji[':smiley:'] = $base_uri . '72x72/1f603.png';
            $this->emoji[':D'] = $base_uri . '72x72/1f600.png';
            $this->emoji[':$'] = $base_uri . '72x72/1f60a.png';
            $this->emoji[':relaxed:'] = $base_uri . '72x72/263a.png';
            $this->emoji[';)'] = $base_uri . '72x72/1f609.png';
            $this->emoji[':heart_eyes:'] = $base_uri . '72x72/1f60d.png';
            $this->emoji[':kissing_heart:'] = $base_uri . '72x72/1f618.png';
            $this->emoji[':kissing_closed_eyes:'] = $base_uri . '72x72/1f61a.png';
            $this->emoji[':kissing:'] = $base_uri . '72x72/1f617.png';
            $this->emoji[':kissing_smiling_eyes:'] = $base_uri . '72x72/1f619.png';
            $this->emoji[';P'] = $base_uri . '72x72/1f61c.png';
            $this->emoji[':P'] = $base_uri . '72x72/1f61b.png';
            $this->emoji[':stuck_out_tongue_closed_eyes:'] = $base_uri . '72x72/1f61d.png';
            $this->emoji[':flushed:'] = $base_uri . '72x72/1f633.png';
            $this->emoji[':grin:'] = $base_uri . '72x72/1f601.png';
            $this->emoji[':pensive:'] = $base_uri . '72x72/1f614.png';
            $this->emoji[':relieved:'] = $base_uri . '72x72/1f60c.png';
            $this->emoji[':unamused'] = $base_uri . '72x72/1f612.png';
            $this->emoji[':('] = $base_uri . '72x72/1f61e.png';
            $this->emoji[':persevere:'] = $base_uri . '72x72/1f623.png';
            $this->emoji[":'("] = $base_uri . '72x72/1f622.png';
            $this->emoji[':joy:'] = $base_uri . '72x72/1f602.png';
            $this->emoji[':sob:'] = $base_uri . '72x72/1f62d.png';
            $this->emoji[':sleepy:'] = $base_uri . '72x72/1f62a.png';
            $this->emoji[':disappointed_relieved:'] = $base_uri . '72x72/1f625.png';
            $this->emoji[':cold_sweat:'] = $base_uri . '72x72/1f630.png';
            $this->emoji[':sweat_smile:'] = $base_uri . '72x72/1f605.png';
            $this->emoji[':sweat:'] = $base_uri . '72x72/1f613.png';
            $this->emoji[':weary:'] = $base_uri . '72x72/1f629.png';
            $this->emoji[':tired_face:'] = $base_uri . '72x72/1f62b.png';
            $this->emoji[':fearful:'] = $base_uri . '72x72/1f628.png';
            $this->emoji[':scream:'] = $base_uri . '72x72/1f631.png';
            $this->emoji[':angry:'] = $base_uri . '72x72/1f620.png';
            $this->emoji[':rage:'] = $base_uri . '72x72/1f621.png';
            $this->emoji[':triumph'] = $base_uri . '72x72/1f624.png';
            $this->emoji[':confounded:'] = $base_uri . '72x72/1f616.png';
            $this->emoji[':laughing:'] = $base_uri . '72x72/1f606.png';
            $this->emoji[':yum:'] = $base_uri . '72x72/1f60b.png';
            $this->emoji[':mask:'] = $base_uri . '72x72/1f637.png';
            $this->emoji[':cool:'] = $base_uri . '72x72/1f60e.png';
            $this->emoji[':sleeping:'] = $base_uri . '72x72/1f634.png';
            $this->emoji[':dizzy_face:'] = $base_uri . '72x72/1f635.png';
            $this->emoji[':astonished:'] = $base_uri . '72x72/1f632.png';
            $this->emoji[':worried:'] = $base_uri . '72x72/1f61f.png';
            $this->emoji[':frowning:'] = $base_uri . '72x72/1f626.png';
            $this->emoji[':anguished:'] = $base_uri . '72x72/1f627.png';
            $this->emoji[':smiling_imp:'] = $base_uri . '72x72/1f608.png';
            $this->emoji[':imp:'] = $base_uri . '72x72/1f47f.png';
            $this->emoji[':open_mouth:'] = $base_uri . '72x72/1f62e.png';
            $this->emoji[':grimacing:'] = $base_uri . '72x72/1f62c.png';
            $this->emoji[':neutral_face:'] = $base_uri . '72x72/1f610.png';
            $this->emoji[':confused:'] = $base_uri . '72x72/1f615.png';
            $this->emoji[':hushed:'] = $base_uri . '72x72/1f62f.png';
            $this->emoji[':no_mouth:'] = $base_uri . '72x72/1f636.png';
            $this->emoji[':innocent:'] = $base_uri . '72x72/1f607.png';
            $this->emoji[':smirk:'] = $base_uri . '72x72/1f60f.png';
            $this->emoji[':expressionless:'] = $base_uri . '72x72/1f611.png';

        }


        /**
         * Get template path
         *
         *
         * @param $slug
         * @return string
         */
        function get_template( $slug ) {
            $file_list = um_path . "templates/{$slug}.php";
            $theme_file = get_stylesheet_directory() . "/ultimate-member/templates/{$slug}.php";

            if ( file_exists( $theme_file ) ) {
                $file_list = $theme_file;
            }

            return $file_list;
        }
    }
}