<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * GeoDirectory Search widget.
 *
 * @since 1.0.0
 */
class GeoDir_Widget_Post_Distance extends WP_Super_Duper {

    /**
     * Register the advanced search widget with WordPress.
     *
     */
    public function __construct() {

        $options = array(
            'textdomain'    => GEODIRECTORY_TEXTDOMAIN,
            'block-icon'    => 'admin-site',
            'block-category'=> 'widgets',
            'block-keywords'=> "['post','distance','geodir']",
            'class_name'    => __CLASS__,
            'base_id'       => 'gd_post_distance', // this us used as the widget id and the shortcode id.
            'name'          => __('GD > Distance To Post','geodirectory'), // the name of the widget.
            'widget_ops'    => array(
                'classname'   => 'geodir-post-distance', // widget class
                'description' => esc_html__('Shows the distance do the current post.','geodirectory'), // widget description
                'geodirectory' => true,
            ),
        );

        parent::__construct( $options );
    }

    /**
     * The Super block output function.
     *
     * @param array $args
     * @param array $widget_args
     * @param string $content
     *
     * @return mixed|string|void
     */
    public function output( $args = array(), $widget_args = array(), $content = '' ) {
        global $gd_post;
		if ( ! isset( $gd_post->distance ) ) {
			return;
		}

		$distance = round( (float) $gd_post->distance, 2 );
		if ( ! (float) $distance > 0 ) {
			$distance = '0';
		}
		$distance .= ' ' . geodir_get_option( 'search_distance_long' );

		ob_start();
        ?>
        <span class="geodir_post_meta_icon geodir-i-distance" style=""><i class="fa fa-road"></i> </span><?php echo $distance; ?>
        <?php
        return ob_get_clean();
    }

}