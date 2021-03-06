<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * GeoDirectory Search widget.
 *
 * @since 1.0.0
 */
class GeoDir_Widget_Search extends WP_Super_Duper {

    /**
     * Register the advanced search widget with WordPress.
     *
     */
    public function __construct() {


        $options = array(
            'textdomain'    => GEODIRECTORY_TEXTDOMAIN,
            'block-icon'    => 'admin-site',
            'block-category'=> 'widgets',
            'block-keywords'=> "['search','geo','geodir']",

            'class_name'    => __CLASS__,
            'base_id'       => 'gd_search', // this us used as the widget id and the shortcode id.
            'name'          => __('GD > Search','geodirectory'), // the name of the widget.
            //'disable_widget'=> true,
            'widget_ops'    => array(
                'classname'   => 'geodir-search-container', // widget class
                'description' => esc_html__('Shows the GeoDirectory search bar.','geodirectory'), // widget description
                'geodirectory' => true,
            ),

            //@todo add options via advanced search
//            'arguments'     => array(
//                'post_type'  => array(
//                    'title' => __('Default Post Type:', 'geodirectory'),
//                    'desc' => __('The custom post types to show by default. Only used when there are multiple CPTs.', 'geodirectory'),
//                    'type' => 'select',
//                    'options'   =>  $this->post_type_options(),
//                    'default'  => 'image',
//                    'desc_tip' => true,
//                    'advanced' => true
//                ),
//                'show_filters'  => array(
//                    'title' => __('Show filters:', 'geodirectory'),
//                    'desc' => __('Slide or fade transition.', 'geodirectory'),
//                    'type' => 'select',
//                    'options'   =>  array(
//                        "slide" => __('Slide', 'geodirectory'),
//                        "fade" => __('Fade', 'geodirectory'),
//                    ),
//                    'default'  => 'slide',
//                    'desc_tip' => true,
//                    'element_require' => '[%type%]=="slider"',
//                    'advanced' => true
//                ),
//
//            )
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
    public function output($args = array(), $widget_args = array(),$content = ''){

        ob_start();
        /**
         * @var bool $ajax_load Ajax load or not.
         * @var string $animation Fade or slide.
         * @var bool $slideshow Auto start or not.
         * @var int $controlnav 0 = none, 1 =  standard, 2 = thumbnails
         * @var bool $show_title If the title should be shown or not.
         * @var int/empty $limit If the number of images should be limited.
         */
        extract($args, EXTR_SKIP);

        // prints the widget
        extract($args, EXTR_SKIP);

        if(isset($post_type) && $post_type){
            geodir_get_search_post_type($post_type);// set the post type
        }else{
            geodir_get_search_post_type();// set the post type
        }

        geodir_get_template_part('listing', 'filter-form');


        // after outputing the search reset the CPT
        global $geodir_search_post_type;
        $geodir_search_post_type = '';

        return ob_get_clean();
    }


    /**
     * Get the post type options for search.
     *
     * @return array
     */
    public function post_type_options(){
        $options = array(''=>__('Auto','geodirectory'));

        $post_types = geodir_get_posttypes('options-plural');
        if(!empty($post_types)){
            $options = array_merge($options,$post_types);
        }

        //print_r($options);

        return $options;
    }

}