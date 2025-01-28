<?php
namespace um\admin\list_table;

use WP_List_Table;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Class Restriction_Rules
 */
class Restriction_Rules extends WP_List_Table {

	/**
	 * @var string
	 */
	public $no_items_message = '';

	/**
	 * @var array
	 */
	public $sortable_columns = array();

	/**
	 * @var string
	 */
	public $default_sorting_field = '';

	/**
	 * @var array
	 */
	public $actions = array();

	/**
	 * @var array
	 */
	public $bulk_actions = array();

	/**
	 * @var array
	 */
	public $columns = array();

	/**
	 * UM_Restrictions_List_Table constructor.
	 *
	 * @param array $args
	 */
	public function __construct( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'singular' => __( 'item', 'ultimate-member' ),
				'plural'   => __( 'items', 'ultimate-member' ),
				'ajax'     => false,
			)
		);

		$this->no_items_message = $args['plural'] . ' ' . __( 'not found.', 'ultimate-member' );

		parent::__construct( $args );
	}

	/**
	 * @param callable $name
	 * @param array $arguments
	 *
	 * @return mixed
	 */
	public function __call( $name, $arguments ) {
		return call_user_func_array( array( $this, $name ), $arguments );
	}

	/**
	 *
	 */
	public function prepare_items() {
		$screen = $this->screen;

		$columns               = $this->get_columns();
		$hidden                = array();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$per_page = $this->get_items_per_page( str_replace( '-', '_', $screen->id . '_per_page' ), 9999999 );
		$paged    = $this->get_pagenum();

		$rules = get_option( 'um_restriction_rules', array() );
		foreach ( $rules as $k => $rule ) {
			if ( empty( $rule['id'] ) ) {
				unset( $rules[ $k ] );
			}
		}

		usort(
			$rules,
			static function ( $a, $b ) {
				return $a['_um_priority'] - $b['_um_priority'];
			}
		);

		$this->items = array_slice( $rules, ( $paged - 1 ) * $per_page, $per_page );

		$this->set_pagination_args(
			array(
				'total_items' => count( $rules ),
				'per_page'    => $per_page,
			)
		);
	}

	/**
	 * @param object $item
	 * @param string $column_name
	 *
	 * @return string
	 */
	public function column_default( $item, $column_name ) {
		if ( isset( $item[ $column_name ] ) ) {
			return $item[ $column_name ];
		}

		return '';
	}

	/**
	 *
	 */
	public function no_items() {
		echo wp_kses( $this->no_items_message, UM()->get_allowed_html( 'wp-admin' ) );
	}

	/**
	 * @param array $args
	 *
	 * @return $this
	 */
	public function set_sortable_columns( $args = array() ) {
		$return_args = array();
		foreach ( $args as $k => $val ) {
			if ( is_numeric( $k ) ) {
				$return_args[ $val ] = array( $val, $val === $this->default_sorting_field );
			} elseif ( is_string( $k ) ) {
				$return_args[ $k ] = array( $val, $k === $this->default_sorting_field );
			} else {
				continue;
			}
		}
		$this->sortable_columns = $return_args;
		return $this;
	}

	/**
	 * @return array
	 */
	public function get_sortable_columns() {
		return $this->sortable_columns;
	}

	/**
	 * @param array $args
	 *
	 * @return $this
	 */
	public function set_columns( $args = array() ) {
		if ( count( $this->bulk_actions ) ) {
			$args = array_merge( array( 'cb' => '<input type="checkbox" />' ), $args );
		}
		$this->columns = $args;
		return $this;
	}

	/**
	 * @return array
	 */
	public function get_columns() {
		return $this->columns;
	}

	/**
	 * @param array $args
	 *
	 * @return $this
	 */
	public function set_actions( $args = array() ) {
		$this->actions = $args;
		return $this;
	}

	/**
	 * @return array
	 */
	public function get_actions() {
		return $this->actions;
	}

	/**
	 * @param array $args
	 *
	 * @return $this
	 */
	public function set_bulk_actions( $args = array() ) {
		$this->bulk_actions = $args;
		return $this;
	}

	/**
	 * @return array
	 */
	public function get_bulk_actions() {
		return $this->bulk_actions;
	}

	/**
	 * @param object $item
	 *
	 * @return string
	 */
	public function column_cb( $item ) {
		if ( empty( $item['id'] ) ) {
			return '';
		}
		return sprintf( '<input type="checkbox" name="item[]" value="%s" />', $item['id'] );
	}

	/**
	 * @param $item
	 *
	 * @return string
	 */
	public function column_title( $item ) {
		if ( empty( $item['id'] ) ) {
			return '';
		}

		$actions = array();
		$id      = $item['id'];

		$actions['edit'] = '<a href="admin.php?page=um_restriction_rules&tab=edit&id=' . esc_attr( $id ) . '">' . __( 'Edit', 'ultimate-member' ) . '</a>';
		if ( 'active' === $item['_um_status'] ) {
			$actions['deactivate'] = '<a href="admin.php?page=um_restriction_rules&action=deactivate&id=' . esc_attr( $id ) . '&_wpnonce=' . wp_create_nonce( 'um_restriction_deactivate' . $item['id'] . get_current_user_id() ) . '" onclick="return confirm( \'' . __( 'Are you sure you want to deactivate this restriction rule?', 'ultimate-member' ) . '\' )">' . __( 'Deactivate', 'ultimate-member' ) . '</a>';
		} else {
			$actions['activate'] = '<a href="admin.php?page=um_restriction_rules&action=activate&id=' . esc_attr( $id ) . '&_wpnonce=' . wp_create_nonce( 'um_restriction_activate' . $item['id'] . get_current_user_id() ) . '" onclick="return confirm( \'' . __( 'Are you sure you want to activate this restriction rule?', 'ultimate-member' ) . '\' )">' . __( 'Activate', 'ultimate-member' ) . '</a>';
		}
		$actions['delete'] = '<a href="admin.php?page=um_restriction_rules&action=delete&id=' . esc_attr( $id ) . '&_wpnonce=' . wp_create_nonce( 'um_restriction_delete' . $item['id'] . get_current_user_id() ) . '" onclick="return confirm( \'' . __( 'Are you sure you want to delete this restriction rule?', 'ultimate-member' ) . '\' )">' . __( 'Delete', 'ultimate-member' ) . '</a>';

		/**
		 * Filters the role actions in WP ListTable Ultimate Member > Access Rules screen.
		 *
		 * @since 2.8.x
		 * @hook um_restriction_row_actions
		 *
		 * @param {array}  $actions Action links.
		 * @param {string} $id      Role key.
		 *
		 * @example <caption>Add custom action to rule's row.</caption>
		 * function my_um_restriction_row_actions( $actions, $id ) {
		 *     $actions['{action_key}'] = "<a href="{action_link}">Action Title</a>";
		 *     return $actions;
		 * }
		 * add_action( 'um_restriction_row_actions', 'my_um_restriction_row_actions', 10, 2 );
		 */
		$actions = apply_filters( 'um_restriction_row_actions', $actions, $id );

		return sprintf( '%1$s %2$s', '<strong><a class="row-title" href="admin.php?page=um_restriction_rules&tab=edit&id=' . esc_attr( $id ) . '">' . stripslashes( $item['title'] ) . '</a></strong>', $this->row_actions( $actions ) );
	}

	/**
	 * @param $item
	 */
	public function column_status( $item ) {
		if ( empty( $item['id'] ) ) {
			return;
		}
		echo 'active' === $item['_um_status'] ? esc_html__( 'Active', 'ultimate-member' ) : esc_html__( 'Inactive', 'ultimate-member' );
	}

	/**
	 * @param $item
	 */
	public function column_type( $item ) {
		if ( empty( $item['id'] ) ) {
			return;
		}
		echo esc_html( $item['_um_type'] );
	}

	/**
	 * @param array $item
	 */
	public function column_description( $item ) {
		if ( empty( $item['id'] ) ) {
			return;
		}
		if ( empty( $item['_um_description'] ) ) {
			return;
		}
		echo wp_kses( $item['_um_description'], UM()->get_allowed_html( 'wp-admin' ) );
	}

	/**
	 * @param $item
	 */
	public function column_entities( $item ) {
		if ( empty( $item['id'] ) ) {
			return;
		}
		$option     = get_option( 'um_restriction_rule_' . $item['id'] );
		$post_types = get_post_types( array( 'public' => true ), 'names' );

		$output = '';
		if ( ! empty( $option['include']['_um_include'] ) ) {
			$output .= __( 'Include', 'ultimate-member' ) . ': ';
			foreach ( $option['include']['_um_include'] as $key => $entity ) {
				if ( 'site' === $key ) {
					$output .= __( 'Website', 'ultimate-member' ) . ', ';
				}
				if ( in_array( $key, $post_types, true ) ) {
					$obj = get_post_type_object( $key );
					if ( ! empty( $obj ) ) {
						$output .= $obj->labels->singular_name . ', ';
					}
				} elseif ( 'tags' === $key ) {
					$output .= 'Tags, ';
				} elseif ( 'category' === $key ) {
					$output .= 'Category, ';
				}
			}
			$last_position = strrpos( $output, ', ' );
			if ( false !== $last_position ) {
				$output = substr( $output, 0, $last_position );
			}
		}
		if ( ! empty( $option['exclude']['_um_exclude'] ) ) {
			if ( '' !== $output ) {
				$output .= '<br>';
			}
			$output .= __( 'Exclude', 'ultimate-member' ) . ': ';
			foreach ( $option['exclude']['_um_exclude'] as $key => $entity ) {
				if ( 'site' === $key ) {
					$output .= __( 'Website', 'ultimate-member' ) . ', ';
				}

				if ( in_array( $key, $post_types, true ) ) {
					$obj     = get_post_type_object( $key );
					$output .= $obj->labels->singular_name . ', ';
				} elseif ( 'tags' === $key ) {
					$output .= 'Tags, ';
				} elseif ( 'category' === $key ) {
					$output .= 'Category, ';
				}
			}
			$last_position = strrpos( $output, ', ' );
			if ( false !== $last_position ) {
				$output = substr( $output, 0, $last_position );
			}
		}
		echo wp_kses( $output, UM()->get_allowed_html( 'wp-admin' ) );
	}

	/**
	 * @param $item
	 */
	public function column_rules( $item ) {
		if ( empty( $item['id'] ) ) {
			return;
		}
		$option = get_option( 'um_restriction_rule_' . $item['id'] );
		$output = '';
		if ( ! empty( $option['rules']['_um_users'] ) ) {
			$output .= esc_html__( 'Logged in users:', 'ultimate-member' );
			$output .= '<br />';
			foreach ( $option['rules']['_um_users'] as $key => $rule ) {
				$num     = absint( $key ) + 1;
				$output .= __( 'Group #', 'ultimate-member' ) . $num . ': ';
				foreach ( $rule as $k => $v ) {
					switch ( $k ) {
						case 'role':
							$output .= __( 'Role', 'ultimate-member' ) . ', ';
							break;
						case 'user':
							$output .= __( 'User', 'ultimate-member' ) . ', ';
							break;
					}
				}
				$last_position = strrpos( $output, ', ' );
				if ( false !== $last_position ) {
					$output = substr( $output, 0, $last_position );
				}
				$output .= '<br />';
			}
		} elseif ( array_key_exists( '_um_authentification', $option['rules'] ) && 'loggedin' === $option['rules']['_um_authentification'] ) {
				$output = __( 'Logged in users:', 'ultimate-member' );
		} else {
			$output = __( 'Logged out users', 'ultimate-member' );
		}

		echo wp_kses( $output, UM()->get_allowed_html( 'wp-admin' ) );
	}

	/**
	 * @param $item
	 */
	public function column_action( $item ) {
		if ( empty( $item['id'] ) ) {
			return;
		}

		$label  = '';
		$action = get_option( 'um_restriction_rule_' . $item['id'] );
		switch ( $action['action']['_um_action'] ) {
			case 0:
				$label = __( 'Display 404', 'ultimate-member' );
				break;
			case 1:
				$label = __( 'Show access restricted message', 'ultimate-member' );
				break;
			case 2:
				$label = __( 'Redirect user', 'ultimate-member' );
				break;
		}
		echo wp_kses( $label, UM()->get_allowed_html( 'wp-admin' ) );
	}

	/**
	 * @param array $attr
	 */
	public function um_set_pagination_args( $attr = array() ) {
		$this->set_pagination_args( $attr );
	}
}
