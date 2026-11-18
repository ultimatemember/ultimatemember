<?php
namespace um\core;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'um\core\Invitation_Codes' ) ) {

	/**
	 * Class Invitation_Codes
	 *
	 * Handles one-time invitation codes for registration forms.
	 *
	 * @package um\core
	 *
	 * @since 2.15.0
	 */
	class Invitation_Codes {

		/**
		 * @var string Custom table name.
		 */
		public $table_name = '';

		/**
		 * Invitation_Codes constructor.
		 */
		public function __construct() {
			global $wpdb;
			$this->table_name = $wpdb->prefix . 'um_invitation_codes';
		}

		/**
		 * Create the invitation codes table when it does not exist yet.
		 *
		 * Covers existing installations that updated the plugin without re-activation.
		 *
		 * @since 2.15.0
		 */
		public function maybe_create_table() {
			static $checked = false;

			if ( $checked ) {
				return;
			}

			global $wpdb;

			$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $this->table_name ) ) );
			if ( $table_exists !== $this->table_name ) {
				UM()->setup()->create_db();
			}

			$checked = true;
		}

		/**
		 * Generate a random code part.
		 *
		 * Ambiguous characters are excluded to keep codes easy to read and retype.
		 *
		 * @since 2.15.0
		 *
		 * @param int $length Code length.
		 *
		 * @return string
		 */
		public function generate_random_code( $length ) {
			$charset = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
			$max     = strlen( $charset ) - 1;

			$code = '';
			for ( $i = 0; $i < $length; $i++ ) {
				$code .= substr( $charset, wp_rand( 0, $max ), 1 );
			}

			return $code;
		}

		/**
		 * Generate invitation codes.
		 *
		 * @since 2.15.0
		 *
		 * @param array $args {
		 *     Generation arguments.
		 *
		 *     @type int    $count  Number of codes to generate.
		 *     @type string $prefix Optional code prefix.
		 *     @type int    $length Random part length.
		 *     @type string $expiry Optional expiry date in Y-m-d format.
		 * }
		 *
		 * @return array|\WP_Error Generated codes or error object.
		 */
		public function generate_codes( $args ) {
			global $wpdb;

			$this->maybe_create_table();

			$args = wp_parse_args(
				$args,
				array(
					'count'  => 10,
					'prefix' => '',
					'length' => 12,
					'expiry' => '',
				)
			);

			$count  = min( 100, max( 1, absint( $args['count'] ) ) );
			$length = min( 32, max( 6, absint( $args['length'] ) ) );

			$prefix = sanitize_text_field( $args['prefix'] );
			$prefix = preg_replace( '/[^A-Za-z0-9\-_]/', '', $prefix );
			$prefix = substr( $prefix, 0, 20 );
			if ( '' !== $prefix && '-' !== substr( $prefix, -1 ) ) {
				$prefix .= '-';
			}

			$expiry = null;
			if ( ! empty( $args['expiry'] ) ) {
				$expiry_input = sanitize_text_field( $args['expiry'] );
				$date         = \DateTime::createFromFormat( 'Y-m-d', $expiry_input );
				if ( ! $date || $date->format( 'Y-m-d' ) !== $expiry_input ) {
					return new \WP_Error( 'um_invitation_codes_expiry', __( 'Invalid expiry date. Please use the YYYY-MM-DD format.', 'ultimate-member' ) );
				}
				$expiry = $date->format( 'Y-m-d' ) . ' 23:59:59';
			}

			$now   = current_time( 'mysql' );
			$codes = array();

			for ( $i = 0; $i < $count; $i++ ) {
				$attempts = 0;

				do {
					$code   = $prefix . $this->generate_random_code( $length );
					$exists = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM %i WHERE code = %s', $this->table_name, $code ) );
					++$attempts;
				} while ( $exists && $attempts < 10 );

				if ( $exists ) {
					continue;
				}

				$data   = array(
					'code'   => $code,
					'status' => 'available',
				);
				$format = array( '%s', '%s' );

				if ( null !== $expiry ) {
					$data['expiry'] = $expiry;
					$format[]       = '%s';
				}

				$data['created_at'] = $now;
				$format[]           = '%s';

				$inserted = $wpdb->insert( $this->table_name, $data, $format );

				if ( $inserted ) {
					$codes[] = $code;
				}
			}

			if ( empty( $codes ) ) {
				return new \WP_Error( 'um_invitation_codes_generate', __( 'Codes could not be generated. Please try again.', 'ultimate-member' ) );
			}

			/**
			 * Fires after invitation codes have been generated.
			 *
			 * @since 2.15.0
			 * @hook um_invitation_codes_generated
			 *
			 * @param {array} $codes Generated codes.
			 * @param {array} $args  Generation arguments.
			 *
			 * @example <caption>Make any custom action after invitation codes have been generated.</caption>
			 * function my_invitation_codes_generated( $codes, $args ) {
			 *     // your code here
			 * }
			 * add_action( 'um_invitation_codes_generated', 'my_invitation_codes_generated', 10, 2 );
			 */
			do_action( 'um_invitation_codes_generated', $codes, $args );

			return $codes;
		}

		/**
		 * Delete invitation codes by IDs.
		 *
		 * @since 2.15.0
		 *
		 * @param array $ids Code IDs.
		 *
		 * @return int|false Number of deleted rows or false on error.
		 */
		public function delete_codes( $ids ) {
			global $wpdb;

			$this->maybe_create_table();

			$ids = array_filter( array_map( 'absint', (array) $ids ) );
			if ( empty( $ids ) ) {
				return 0;
			}

			$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

			return $wpdb->query(
				$wpdb->prepare( "DELETE FROM %i WHERE id IN ( {$placeholders} )", array_merge( array( $this->table_name ), $ids ) ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			);
		}

		/**
		 * Whitelist of sortable columns.
		 *
		 * @since 2.15.0
		 *
		 * @return array
		 */
		public function get_sortable_columns_map() {
			return array(
				'id'         => 'id',
				'code'       => 'code',
				'status'     => 'status',
				'expiry'     => 'expiry',
				'created_at' => 'created_at',
				'used_at'    => 'used_at',
			);
		}

		/**
		 * Get invitation codes list.
		 *
		 * @since 2.15.0
		 *
		 * @param array $args {
		 *     Query arguments.
		 *
		 *     @type int    $per_page Items per page.
		 *     @type int    $page     Current page.
		 *     @type string $search   Code search string.
		 *     @type string $status   Filter: available|expired|used.
		 *     @type string $orderby  Order by column.
		 *     @type string $order    Order direction.
		 * }
		 *
		 * @return array Items and total count.
		 */
		public function get_codes( $args = array() ) {
			global $wpdb;

			$this->maybe_create_table();

			$args = wp_parse_args(
				$args,
				array(
					'per_page' => 20,
					'page'     => 1,
					'search'   => '',
					'status'   => '',
					'orderby'  => 'id',
					'order'    => 'DESC',
				)
			);

			$now     = current_time( 'mysql' );
			$search  = trim( sanitize_text_field( $args['search'] ) );
			$status  = sanitize_key( $args['status'] );
			$order   = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';
			$orderby = array_key_exists( $args['orderby'], $this->get_sortable_columns_map() ) ? $args['orderby'] : 'id';

			$where   = 'WHERE 1=1';
			$prepare = array( $this->table_name );

			if ( '' !== $search ) {
				$where    .= ' AND code LIKE %s';
				$prepare[] = '%' . $wpdb->esc_like( $search ) . '%';
			}

			if ( 'used' === $status ) {
				$where .= " AND status = 'used'";
			} elseif ( 'available' === $status ) {
				$where    .= " AND status = 'available' AND ( expiry IS NULL OR expiry > %s )";
				$prepare[] = $now;
			} elseif ( 'expired' === $status ) {
				$where    .= " AND status = 'available' AND expiry IS NOT NULL AND expiry <= %s";
				$prepare[] = $now;
			}

			$total = absint(
				$wpdb->get_var(
					$wpdb->prepare( "SELECT COUNT(id) FROM %i {$where}", $prepare ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
				)
			);

			$where .= ' ORDER BY ' . $orderby . ' ' . $order;

			$per_page = min( 100, max( 1, absint( $args['per_page'] ) ) );
			$page     = max( 1, absint( $args['page'] ) );
			$offset   = ( $page - 1 ) * $per_page;

			$prepare[] = $per_page;
			$prepare[] = $offset;

			$items = $wpdb->get_results(
				$wpdb->prepare( "SELECT * FROM %i {$where} LIMIT %d OFFSET %d", $prepare ) // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
			);

			return array(
				'items' => $items,
				'total' => $total,
			);
		}

		/**
		 * Get counts per status for list table views.
		 *
		 * @since 2.15.0
		 *
		 * @return array
		 */
		public function get_counts() {
			global $wpdb;

			$this->maybe_create_table();

			$now = current_time( 'mysql' );

			$counts = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT COUNT(id) AS total,
					SUM( status = 'used' ) AS used,
					SUM( status = 'available' AND expiry IS NOT NULL AND expiry <= %s ) AS expired,
					SUM( status = 'available' AND ( expiry IS NULL OR expiry > %s ) ) AS available
					FROM %i",
					$now,
					$now,
					$this->table_name
				),
				ARRAY_A
			);

			return array_map( 'absint', (array) $counts );
		}

		/**
		 * Check if a code is valid, available and not expired.
		 *
		 * @since 2.15.0
		 *
		 * @param string $code Invitation code.
		 *
		 * @return bool
		 */
		public function validate_code( $code ) {
			global $wpdb;

			$code = trim( sanitize_text_field( $code ) );
			if ( '' === $code ) {
				return false;
			}

			$this->maybe_create_table();

			$now = current_time( 'mysql' );

			$row = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT id FROM %i
					WHERE code = %s AND status = 'available' AND ( expiry IS NULL OR expiry > %s )
					LIMIT 1",
					$this->table_name,
					$code,
					$now
				)
			);

			return ! empty( $row );
		}

		/**
		 * Consume an available code on successful registration.
		 *
		 * The conditional UPDATE is atomic, so two parallel submissions of the
		 * same code cannot mark it used twice.
		 *
		 * @since 2.15.0
		 *
		 * @param string $code    Invitation code.
		 * @param int    $user_id Registered user ID.
		 *
		 * @return bool True when the code has been consumed.
		 */
		public function consume_code( $code, $user_id ) {
			global $wpdb;

			$code = trim( sanitize_text_field( $code ) );
			if ( '' === $code || ! $user_id ) {
				return false;
			}

			$this->maybe_create_table();

			$now = current_time( 'mysql' );

			$wpdb->query(
				$wpdb->prepare(
					"UPDATE %i
					SET status = 'used', used_at = %s, used_by = %d
					WHERE code = %s AND status = 'available' AND ( expiry IS NULL OR expiry > %s )",
					$this->table_name,
					$now,
					absint( $user_id ),
					$code,
					$now
				)
			);

			return 1 === absint( $wpdb->rows_affected );
		}

		/**
		 * AJAX: Generate invitation codes.
		 *
		 * @since 2.15.0
		 */
		public function ajax_generate_codes() {
			if ( ! check_ajax_referer( 'um-admin-nonce', 'nonce', false ) ) {
				wp_send_json_error( esc_js( __( 'Wrong Nonce', 'ultimate-member' ) ) );
			}

			if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( __( 'Please login as administrator', 'ultimate-member' ) );
			}

			$count = isset( $_POST['um_ic_count'] ) ? absint( $_POST['um_ic_count'] ) : 10;
			$codes = $this->generate_codes(
				array(
					'count'  => $count,
					'prefix' => isset( $_POST['um_ic_prefix'] ) ? wp_unslash( $_POST['um_ic_prefix'] ) : '',
					'length' => isset( $_POST['um_ic_length'] ) ? absint( $_POST['um_ic_length'] ) : 12,
					'expiry' => isset( $_POST['um_ic_expiry'] ) ? sanitize_text_field( wp_unslash( $_POST['um_ic_expiry'] ) ) : '',
				)
			);

			if ( is_wp_error( $codes ) ) {
				wp_send_json_error( $codes->get_error_message() );
			}

			wp_send_json_success(
				array(
					'count'    => count( $codes ),
					'codes'    => $codes,
					'redirect' => add_query_arg(
						array(
							'msg'   => 'g',
							'count' => count( $codes ),
						),
						admin_url( 'admin.php?page=um_invitation_codes' )
					),
				)
			);
		}
	}
}
