<?php
namespace umm\online\includes\widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Online_List
 *
 * @package umm\online\includes\widgets
 */
class Online_List extends \WP_Widget {


	/**
	 * Online_List constructor.
	 */
	function __construct() {
		parent::__construct(
			'um_online_users',
			__( 'Ultimate Member - Online Users', 'ultimate-member' ),
			array(
				'description' => __( 'Shows your online users.', 'ultimate-member' ),
			)
		);
	}


	/**
	 * Creating widget front-end
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {
//		// Maybe disable preview
//		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
//			return;
//		}
//
//		if ( ! empty( $_GET['legacy-widget-preview'] ) && defined( 'IFRAME_REQUEST' ) && IFRAME_REQUEST ) {
//			return;
//		}
		$title = array_key_exists( 'title', $instance ) ? $instance['title'] : __( 'Who is Online', 'ultimate-member' );
		$title = apply_filters( 'widget_title', $title );

		$max  = array_key_exists( 'max', $instance ) ? $instance['max'] : 11;
		$role = array_key_exists( 'role', $instance ) ? $instance['role'] : 'all';

		// before and after widget arguments are defined by themes
		echo $args['before_widget'];
		if ( ! empty( $title ) ) {
			echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
		}

		// This is where you run the code and display the output
		if ( version_compare( get_bloginfo( 'version' ), '5.4', '<' ) ) {
			echo do_shortcode('[ultimatemember_online max="' . esc_attr( $max ) . '" roles="' . esc_attr( $role ) . '" /]');
		} else {
			echo apply_shortcodes('[ultimatemember_online max="' . esc_attr( $max ) . '" roles="' . esc_attr( $role ) . '" /]');
		}
		echo $args['after_widget'];
	}


	/**
	 * Widget Backend
	 *
	 * @param array $instance
	 *
	 * @return string|void
	 */
	public function form( $instance ) {
		$title = array_key_exists( 'title', $instance ) ? $instance['title'] : __( 'Who is Online', 'ultimate-member' );
		$max   = array_key_exists( 'max', $instance ) ? $instance['max'] : 11;
		$role  = array_key_exists( 'role', $instance ) ? $instance['role'] : 'all';
		?>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title', 'ultimate-member' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'max' ) ); ?>"><?php esc_html_e( 'Maximum number of users in first view', 'ultimate-member' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'max' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'max' ) ); ?>" type="text" value="<?php echo esc_attr( $max ); ?>" />
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'role' ) ); ?>"><?php esc_html_e( 'Show specific community role?', 'ultimate-member' ); ?></label>
			<select name="<?php echo esc_attr( $this->get_field_name( 'role' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'role' ) ); ?>">
				<option value="all" <?php selected( 'all', $role ); ?>><?php esc_html_e( 'All roles', 'ultimate-member' ); ?></option>
				<?php foreach ( UM()->roles()->get_roles() as $key => $value ) { ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $key, $role ); ?>><?php echo esc_html( $value ); ?></option>
				<?php } ?>
			</select>
		</p>

		<?php 
	}


	/**
	 * Updating widget replacing old instances with new
	 *
	 * @param array $new_instance
	 * @param array $old_instance
	 *
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ! empty( $new_instance['title'] ) ? sanitize_text_field( $new_instance['title'] ) : '';
		$instance['max']   = ! empty( $new_instance['max'] ) ? absint( $new_instance['max'] ) : 11;
		$instance['role']  = ! empty( $new_instance['role'] ) ? sanitize_key( $new_instance['role'] ) : 'all';
		return $instance;
	}
}
