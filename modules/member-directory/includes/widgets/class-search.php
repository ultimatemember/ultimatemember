<?php
namespace umm\member_directory\includes\widgets;


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Class Search
 *
 * @package umm\member_directory\includes\widgets
 */
class Search extends \WP_Widget {


	/**
	 * Search constructor.
	 */
	function __construct() {
		parent::__construct(
			'um_search_widget',
			__( 'Ultimate Member - Search', 'ultimate-member' ),
			array(
				'description' => __( 'Shows the search member form.', 'ultimate-member' ),
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
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return;
		}

		if ( ! empty( $_GET['legacy-widget-preview'] ) && defined( 'IFRAME_REQUEST' ) && IFRAME_REQUEST ) {
			return;
		}

		$title = array_key_exists( 'title', $instance ) ? $instance['title'] : '';
		$title = apply_filters( 'widget_title', $title );

		// before and after widget arguments are defined by themes
		echo $args['before_widget'];
		if ( ! empty( $title ) ) {
			echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
		}

		// display the search form
		if ( version_compare( get_bloginfo('version'),'5.4', '<' ) ) {
			echo do_shortcode( '[ultimatemember_searchform /]' );
		} else {
			echo apply_shortcodes( '[ultimatemember_searchform /]' );
		}


		echo $args['after_widget'];
	}


	/**
	 * Widget Backend
	 *
	 * @param array $instance
	 */
	public function form( $instance ) {
		$title = array_key_exists( 'title', $instance ) ? $instance['title'] : __( 'Search Users', 'ultimate-member' );
		?>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title', 'ultimate-member' ); ?>:</label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
			       name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text"
			       value="<?php echo esc_attr( $title ); ?>" />
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
		return $instance;
	}
}
