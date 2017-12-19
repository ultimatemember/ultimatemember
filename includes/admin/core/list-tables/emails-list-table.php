<?php
if ( ! defined( 'ABSPATH' ) )
    exit; // Exit if accessed directly

global $wpdb;

if ( isset($_REQUEST['_wp_http_referer']) ) {
    $redirect = remove_query_arg(array('_wp_http_referer' ), wp_unslash( $_REQUEST['_wp_http_referer'] ) );
} else {
    $redirect = get_admin_url(). 'admin.php?page=ultimatemember';
}

//remove extra query arg
if ( !empty( $_GET['_wp_http_referer'] ) ) {
    do_action( 'wp_client_redirect', remove_query_arg( array( '_wp_http_referer', '_wpnonce'), wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
    exit;
}

$order_by = 'u.user_registered';
if ( isset( $_GET['orderby'] ) ) {
    switch( $_GET['orderby'] ) {
        case 'username' :
            $order_by = 'u.user_login';
            break;
        case 'nickname' :
            $order_by = 'u.user_nicename';
            break;
        case 'email' :
            $order_by = 'u.user_email';
            break;
    }
}

$order = ( isset( $_GET['order'] ) && 'asc' ==  strtolower( $_GET['order'] ) ) ? 'ASC' : 'DESC';


if( ! class_exists( 'WP_List_Table' ) )
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );

	class UM_WPML_Column_Extends{
        static function add_management_column($columns){
            global $sitepress;
	        $new_columns = $columns;
	        $active_languages = $sitepress->get_active_languages();
	        $current_language = $sitepress->get_current_language();
	        unset( $active_languages[ $current_language ] );

	        if ( count( $active_languages ) > 0 ) {
		        $flags_column = '';
		        foreach ( $active_languages as $language_data ) {
			        $flags_column .= '<img src="' . $sitepress->get_flag_url( $language_data['code'] ). '" width="18" height="12" alt="' . $language_data['display_name'] . '" title="' . $language_data['display_name'] . '" style="margin:2px" />';
		        }

		        $new_columns = array();
		        foreach ( $columns as $column_key => $column_content ) {
			        $new_columns[ $column_key ] = $column_content;
			        if ( 'email' === $column_key && ! isset( $new_columns['icl_translations'] ) )  {
				        $new_columns['icl_translations'] = $flags_column;
			        }
		        }
	        }

	        return $new_columns;
        }

		static function add_content_for_management_column( $item ) {
			global $sitepress;
			$html = '';

			$active_languages = $sitepress->get_active_languages();
			$current_language = $sitepress->get_current_language();
			unset( $active_languages[ $current_language ] );
			foreach ( $active_languages as $language_data ) {
				$html .= self::get_status_html( $item['key'], $language_data['code'] );
			}
			return $html;
		}

		static function get_status_html( $template, $code ) {
			global $sitepress;
			$status = 'add';
			$lang = '';
			$active_languages = $sitepress->get_active_languages();
			$translation = array(
				'edit' => array( 'icon' => 'edit_translation.png', 'text' => sprintf(
					__( 'Edit the %s translation', 'sitepress' ),
					$active_languages[$code]['display_name'] ) ),
				'add'  => array( 'icon' => 'add_translation.png', 'text' => sprintf(
					__( 'Add translation to %s', 'sitepress' ),
					$active_languages[$code]['display_name']
				)
				)
			);

			$default_language_code = $sitepress->get_locale_from_language_code($sitepress->get_default_language());
			$current_language_code = $sitepress->get_locale_from_language_code($code);

			if ( $default_language_code != $current_language_code ) {
				$lang = $current_language_code.'/';
			}
			$template_path = trailingslashit( get_stylesheet_directory() . '/ultimate-member/email' ) . $lang . $template . '.php';

			if ( file_exists( $template_path ) ) {
				$status = 'edit';
			}

			$link = add_query_arg( array( 'email' => $template, 'lang' => $code ) );

			return self::render_status_icon( $link, $translation[$status]['text'], $translation[$status]['icon'] );
		}

		static function render_status_icon( $link, $text, $img ) {

			$icon_html = '<a href="' . $link . '" title="' . $text . '">';
			$icon_html .= '<img style="padding:1px;margin:2px;" border="0" src="'
				. ICL_PLUGIN_URL . '/res/img/'
				. $img . '" alt="'
				. $text . '" width="16" height="16"/>';
			$icon_html .= '</a>';

			return $icon_html;
		}
	}

class UM_Emails_List_Table extends WP_List_Table {

    var $no_items_message = '';
    var $sortable_columns = array();
    var $default_sorting_field = '';
    var $actions = array();
    var $bulk_actions = array();
    var $columns = array();

    function __construct( $args = array() ){
        $args = wp_parse_args( $args, array(
            'singular'  => __( 'item', 'ultimate-member' ),
            'plural'    => __( 'items', 'ultimate-member' ),
            'ajax'      => false
        ) );

        $this->no_items_message = $args['plural'] . ' ' . __( 'not found.', 'ultimate-member' );

        parent::__construct( $args );


    }

    function __call( $name, $arguments ) {
        return call_user_func_array( array( $this, $name ), $arguments );
    }

    function prepare_items() {
        $columns  = $this->get_columns();
        $hidden   = array();
        $sortable = $this->get_sortable_columns();
        $this->_column_headers = array( $columns, $hidden, $sortable );
    }

    function column_default( $item, $column_name ) {
        if( isset( $item[ $column_name ] ) ) {
            return $item[ $column_name ];
        } else {
            return '';
        }
    }

    function no_items() {
        echo $this->no_items_message;
    }

    function set_sortable_columns( $args = array() ) {
        $return_args = array();
        foreach( $args as $k=>$val ) {
            if( is_numeric( $k ) ) {
                $return_args[ $val ] = array( $val, $val == $this->default_sorting_field );
            } else if( is_string( $k ) ) {
                $return_args[ $k ] = array( $val, $k == $this->default_sorting_field );
            } else {
                continue;
            }
        }
        $this->sortable_columns = $return_args;
        return $this;
    }

    function get_sortable_columns() {
        return $this->sortable_columns;
    }

    function set_columns( $args = array() ) {
        if ( count( $this->bulk_actions ) ) {
            $args = array_merge( array( 'cb' => '<input type="checkbox" />' ), $args );
        }
        $this->columns = $args;

        if ( UM()->external_integrations()->is_wpml_active() ) {
	        $this->columns = UM_WPML_Column_Extends::add_management_column( $this->columns );
        }
        return $this;
    }

    function get_columns() {
        return $this->columns;
    }

    function set_actions( $args = array() ) {
        $this->actions = $args;
        return $this;
    }

    function get_actions() {
        return $this->actions;
    }

    function set_bulk_actions( $args = array() ) {
        $this->bulk_actions = $args;
        return $this;
    }

    function get_bulk_actions() {
        return $this->bulk_actions;
    }

    function column_email( $item ) {
        $active = UM()->options()->get( $item['key'] . '_on' );

        return '<span class="dashicons um-notification-status ' . ( ! empty( $active ) ? 'um-notification-is-active dashicons-yes' : 'dashicons-no-alt' ) . '"></span><a href="' . add_query_arg( array( 'email' => $item['key'] ) ) . '"><strong>'. $item['title'] . '</strong></a>';
    }


    function column_recipients( $item ) {
        if ( $item['recipient'] == 'admin' )
            return UM()->options()->get( 'admin_email' );
        else
            return __( 'Member', 'ultimate-member' );
    }


    function column_configure( $item ) {
        return '<a class="button um-email-configure" href="' . add_query_arg( array( 'email' => $item['key'] ) ) . '"><span class="dashicons dashicons-admin-generic"></span></a>';
    }

    function column_icl_translations( $item ) {
        if ( UM()->external_integrations()->is_wpml_active() ) {
            return UM_WPML_Column_Extends::add_content_for_management_column( $item );
        }

        return '';
    }


    function wpc_set_pagination_args( $attr = array() ) {
        $this->set_pagination_args( $attr );
    }
}


$ListTable = new UM_Emails_List_Table( array(
    'singular'  => __( 'Email Notification', 'ultimate-member' ),
    'plural'    => __( 'Email Notifications', 'ultimate-member' ),
    'ajax'      => false
));

$per_page   = 20;
$paged      = $ListTable->get_pagenum();

$ListTable->set_columns( array(
    'email'         => __( 'Email', 'ultimate-member' ),
    'recipients'    => __( 'Recipient(s)', 'ultimate-member' ),
    'configure'     => '',
) );

$emails = UM()->config()->email_notifications;

$ListTable->prepare_items();
$ListTable->items = $emails;
$ListTable->wpc_set_pagination_args( array( 'total_items' => count( $emails ), 'per_page' => $per_page ) ); ?>

<form action="" method="get" name="um-settings-emails" id="um-settings-emails">
    <input type="hidden" name="page" value="um_options" />
    <input type="hidden" name="tab" value="email" />
    <?php if ( ! empty( $_GET['section'] ) ) { ?>
        <input type="hidden" name="section" value="<?php echo $_GET['section'] ?>" />
    <?php }

    $ListTable->display(); ?>
</form>