<?php
/**
 * Google Map related functions.
 *
 * @since 1.0.0
 * @package GeoDirectory
 */

/**
 * Creates a global variable for storing map json data.
 *
 * @since 1.0.0
 * @package GeoDirectory
 * @global array $map_jason Empty array.
 */
function  geodir_init_map_jason()
{
    global $map_jason;
    $map_jason = array();
}

/**
 * Creates a global variable for storing map canvas data.
 *
 * @since 1.0.0
 * @package GeoDirectory
 * @global array $map_canvas_arr Empty array.
 */
function geodir_init_map_canvas_array() {
    global $map_canvas_arr;
    $map_canvas_arr = array();

	global $gd_maps_canvas;
	$gd_maps_canvas = array();
}


/**
 * Creates marker json using given $post object.
 *
 * @since 1.0.0
 * @since 1.5.0 Converts icon url to HTTPS if HTTPS is active.
 * @since 1.6.1 Marker icons size added.
 *
 * @package GeoDirectory
 * @param null|WP_Post $post Post object.
 * @global object $wpdb WordPress Database object.
 * @global array $map_jason Map data in json format.
 * @global bool $add_post_in_marker_array Displays posts in marker array when the value is true.
 * @global array $geodir_cat_icons Category icons array. syntax: array( 'category name' => 'icon url').
 * @global array  $gd_marker_sizes Array of the marker icons sizes.
 */
function create_marker_jason_of_posts($post)
{
    global $wpdb, $map_jason, $add_post_in_marker_array, $geodir_cat_icons, $gd_marker_sizes;

    if (!empty($post) && isset($post->ID) && $post->ID > 0 && (is_main_query() || $add_post_in_marker_array) && $post->marker_json != '') {

        if(isset($map_jason[$post->ID])){return null;}

        $srcharr = array("'", "/", "-", '"', '\\');
        $replarr = array("&prime;", "&frasl;", "&ndash;", "&ldquo;", '');


        $geodir_cat_icons = geodir_get_term_icon();
        $icon = !empty($geodir_cat_icons) && isset($geodir_cat_icons[$post->default_category]) ? $geodir_cat_icons[$post->default_category] : '';

        $post_title = $post->post_title;
        $title = str_replace($srcharr, $replarr, $post_title);

        if (is_ssl()) {
            $icon = str_replace("http:","https:",$icon );
        }
        
        if ($icon != '') {
            $gd_marker_sizes = empty($gd_marker_sizes) ? array() : $gd_marker_sizes;
            
            if (isset($gd_marker_sizes[$icon])) {
                $icon_size = $gd_marker_sizes[$icon];
            } else {
                $icon_size = geodir_get_marker_size($icon);
                $gd_marker_sizes[$icon] = $icon_size;
            }               
        } else {
            $icon_size = array('w' => 36, 'h' => 45);
        }

        $post_json = '{"id":"' . $post->ID
                     . '","t": "' . $title
                     . '","lt": "' . $post->post_latitude
                     . '","ln": "' . $post->post_longitude
                     . '","mk_id":"' . $post->ID . '_' . $post->default_category
                     . '","i":"' . $icon
                     . '","w":"' . $icon_size['w']
                     . '","h":"' . $icon_size['h'] . '"}';

        /**
         * Filter the json data when creating output for post json marker..
         *
         * @since 1.5.7
         * @param string $post_json JSON representation of the post marker info.
         * @param object $post The post object.
         */
        $post_map_json = apply_filters('geodir_create_marker_jason_of_posts',$post_json, $post);

        // only assign it if it has a value
        if($post_map_json){
            $map_jason[$post->ID] = $post_map_json;
        }

    }
}

/**
 * Send jason data to script and show listing map.
 *
 * @since 1.0.0
 * @package GeoDirectory
 * @global array $map_jason Map data in json format.
 * @global array $map_canvas_arr Map canvas array.
 */
function send_marker_jason_to_js()
{
    global $map_jason, $map_canvas_arr;

    if (is_array($map_canvas_arr) && !empty($map_canvas_arr)) {
        foreach ($map_canvas_arr as $canvas => $jason) {
            if (is_array($map_jason) && !empty($map_jason)) {

                // on details page only show the main marker on the map
                if(geodir_is_page('detail')){
                    global $post;
                    if(isset($map_jason[$post->ID])){
                        $map_jason = array($map_jason[$post->ID]);
                    }
                }
                $canvas_jason = $canvas . "_jason";
                $map_canvas_arr[$canvas] = array_unique($map_jason);
                unset($cat_content_info);
                $cat_content_info[] = implode(',', $map_canvas_arr[$canvas]);
                $totalcount = count(array_unique($map_jason));
                if (!empty($cat_content_info)) {
                    $json_content = substr(implode(',', $cat_content_info), 1);
                    $json_content = htmlentities($json_content, ENT_QUOTES, get_option('blog_charset')); // Quotes in csv title import break maps - FIXED by kiran on 2nd March, 2016
                    $json_content = wp_specialchars_decode($json_content); // Fixed #post-320722 on 2016-12-08
                    $canvas_jason = '[{"totalcount":"' . $totalcount . '",' . $json_content . ']';
                } else {
                    $canvas_jason = '[{"totalcount":"0"}]';
                }
                $map_canvas_jason_args = array($canvas . '_jason' => $canvas_jason);

                /**
                 * Filter the send_marker_jason_to_js() function map canvas json args.
                 *
                 * You can use this filter to modify map canvas json args.
                 *
                 * @since 1.0.0
                 * @package GeoDirectory
                 * @param string $canvas Map canvas array key.
                 * @param array $map_canvas_jason_args Map canvas args.
                 */
                $map_canvas_jason_args = apply_filters('geodir_map_canvas_jason_' . $canvas, $map_canvas_jason_args);

                wp_localize_script('geodir-map-widget', $canvas . '_jason_args', $map_canvas_jason_args);
            } else {
                $canvas_jason = '[{"totalcount":"0"}]';
                $map_canvas_jason_args = array($canvas . '_jason' => $canvas_jason);

                /**
                 * Filter the send_marker_jason_to_js() function map canvas json args.
                 *
                 * You can use this filter to modify map canvas json args.
                 *
                 * @since 1.0.0
                 * @package GeoDirectory
                 * @param string $canvas Map canvas array key.
                 * @param array $map_canvas_jason_args Map canvas args.
                 */
                $map_canvas_jason_args = apply_filters('geodir_map_canvas_jason_' . $canvas, $map_canvas_jason_args);
                wp_localize_script('geodir-map-widget', $canvas . '_jason_args', $map_canvas_jason_args);
            }
        }

    }
}

/**
 * Home map Taxonomy walker.
 *
 * @since 1.0.0
 * @since 1.6.16 Fix: Category are not unticked on page refresh for map post type other than default.
 * @package GeoDirectory
 *
 * @global object $gd_session GeoDirectory Session object.
 *
 * @param string $cat_taxonomy Name of the taxonomy e.g place_category.
 * @param int $cat_parent Optional. Parent term ID to retrieve its child terms. Default 0.
 * @param bool $hide_empty Optional. Do you want to hide the terms that has no posts. Default true.
 * @param int $padding Optional. CSS padding value in pixels. e.g: 12 will be considers as 12px.
 * @param string $map_canvas Unique canvas name for your map.
 * @param bool $child_collapse Do you want to collapse child terms by default?.
 * @param bool $is_home_map Optional. Is this a home page map? Default: false.
 * @return string|void
 */
function home_map_taxonomy_walker($cat_taxonomy, $cat_parent = 0, $hide_empty = true, $padding = 0, $map_canvas = '', $child_collapse, $is_home_map = false)
{
    global $cat_count, $geodir_cat_icons, $gd_session;

    $exclude_categories = geodir_get_option('geodir_exclude_cat_on_map',array());
    $exclude_categories_new = geodir_get_option('geodir_exclude_cat_on_map_upgrade');

    // check if exclude categories saved before fix of categories identical names
    if ($exclude_categories_new) {
        $gd_cat_taxonomy = isset($cat_taxonomy[0]) ? $cat_taxonomy[0] : '';
        $exclude_categories = !empty($exclude_categories[$gd_cat_taxonomy]) && is_array($exclude_categories[$gd_cat_taxonomy]) ? array_unique($exclude_categories[$gd_cat_taxonomy]) : array();
    }

    $exclude_cat_str = implode(',', $exclude_categories);

    if ($exclude_cat_str == '') {
        $exclude_cat_str = '0';
    }

    $cat_terms = get_terms($cat_taxonomy, array('parent' => $cat_parent, 'exclude' => $exclude_cat_str, 'hide_empty ' => $hide_empty));

    if ($hide_empty) {
        $cat_terms = geodir_filter_empty_terms($cat_terms);
    }

    $main_list_class = '';
    //If there are terms, start displaying
    if (count($cat_terms) > 0) {
        //Displaying as a list
        $p = $padding * 15;
        $padding++;

        if ($cat_parent == 0) {
            $list_class = 'main_list';
            $display = '';
        } else {
            $list_class = 'sub_list';
            $display = !$child_collapse ? '' : 'display:none';
        }

        $out = '<ul class="treeview ' . $list_class . '" style="margin-left:' . $p . 'px;' . $display . ';">';

        $geodir_cat_icons = geodir_get_term_icon();

        $geodir_default_map_search_pt = (geodir_get_option('geodir_default_map_search_pt')) ? geodir_get_option('geodir_default_map_search_pt') :  'gd_place';
        if ($is_home_map && $homemap_catlist_ptype = $gd_session->get('homemap_catlist_ptype')) {
            $geodir_default_map_search_pt = $homemap_catlist_ptype;
        }
        $post_type = isset($_REQUEST['post_type']) ? $_REQUEST['post_type'] : (isset($_REQUEST['gd_posttype']) ? $_REQUEST['gd_posttype'] : $geodir_default_map_search_pt);
        
        foreach ($cat_terms as $cat_term):
            $icon = !empty($geodir_cat_icons) && isset($geodir_cat_icons[$cat_term->term_id]) ? $geodir_cat_icons[$cat_term->term_id] : '';

            if (!in_array($cat_term->term_id, $exclude_categories)):
                //Secret sauce.  Function calls itself to display child elements, if any
                $checked = 'checked="checked"';

                // Untick the category by default on home map
                if ($is_home_map && $geodir_home_map_untick = geodir_get_option('geodir_home_map_untick')) {
                    if (geodir_wpml_is_taxonomy_translated($post_type . 'category')) { // if WPML
                        global $sitepress;
                        $default_lang = $sitepress->get_default_language();
                        $term_id = icl_object_id($cat_term->term_id, $post_type.'category', true, $default_lang);
                    }else{
                        $term_id = $cat_term->term_id;
                    }
                    if (!empty($geodir_home_map_untick) && in_array($post_type . '_' . $term_id, $geodir_home_map_untick)) {
                        $checked = '';
                    }
                }

                $term_check = '<input type="checkbox" ' . $checked . ' id="' .$map_canvas.'_tick_cat_'. $cat_term->term_id . '" class="group_selector ' . $main_list_class . '"';
                $term_check .= ' name="' . $map_canvas . '_cat[]" ';
                $term_check .= '  title="' . esc_attr(geodir_utf8_ucfirst($cat_term->name)) . '" value="' . $cat_term->term_id . '" onclick="javascript:build_map_ajax_search_param(\'' . $map_canvas . '\',false, this)">';
                $term_img = '<img height="15" width="15" alt="' . $cat_term->taxonomy . '" src="' . $icon . '" title="' . geodir_utf8_ucfirst($cat_term->name) . '"/>';
                $out .= '<li>' . $term_check . '<label for="' . $map_canvas.'_tick_cat_'. $cat_term->term_id . '">' . $term_img . geodir_utf8_ucfirst($cat_term->name) . '</label><i class="fa fa-long-arrow-down"></i>';

            endif;


            // get sub category by recursion
            $out .= home_map_taxonomy_walker($cat_taxonomy, $cat_term->term_id, $hide_empty, $padding, $map_canvas, $child_collapse, $is_home_map);

            $out .= '</li>';

        endforeach;

        $out .= '</ul>';

        return $out;
    } else {
        if ($cat_parent == 0)
            return _e('No category', 'geodirectory');
    }
    return;
}

/**
 * Get the map JS API provider name.
 *
 * @since 1.6.1
 * @package GeoDirectory
 *
 * @return string The map API provider name.
 */
function geodir_map_name() {
    $geodir_map_name = geodir_get_option('maps_api', 'google');
    
    if (!in_array($geodir_map_name, array('none', 'auto', 'google', 'osm'))) {
        $geodir_map_name = 'auto';
    }

    /**
     * Filter the map JS API provider name.
     *
     * @since 1.6.1
     * @param string $geodir_map_name The map API provider name.
     */
    return apply_filters('geodir_map_name', $geodir_map_name);
}

/**
 * Get the marker icon size.
 * This will return width and height of icon in array (ex: w => 36, h => 45).
 *
 * @since 1.6.1
 * @package GeoDirectory
 *
 * @global $gd_marker_sizes Array of the marker icons sizes.
 *
 * @param string $icon Marker icon url.
 * @return array The icon size.
 */
function geodir_get_marker_size($icon, $default_size = array('w' => 36, 'h' => 45)) {
    global $gd_marker_sizes;
    
    if (empty($gd_marker_sizes)) {
        $gd_marker_sizes = array();
    }
      
    if (!empty($gd_marker_sizes[$icon])) {
        return $gd_marker_sizes[$icon];
    }
    
    if (empty($icon)) {
        $gd_marker_sizes[$icon] = $default_size;
        
        return $default_size;
    }
    
    $icon_url = $icon;
    
    $uploads = wp_upload_dir(); // Array of key => value pairs
      
    if (!path_is_absolute($icon)) {
        $icon = str_replace($uploads['baseurl'], $uploads['basedir'], $icon);
    }
    
    if (!path_is_absolute($icon) && strpos($icon, WP_CONTENT_URL) !== false) {
        $icon = str_replace(WP_CONTENT_URL, WP_CONTENT_DIR, $icon);
    }
    
    $sizes = array();
    if (is_file($icon) && file_exists($icon)) {
        $size = getimagesize(trim($icon));
        
        if (!empty($size[0]) && !empty($size[1])) {
            $sizes = array('w' => $size[0], 'h' => $size[1]);
        }
    }
    
    $sizes = !empty($sizes) ? $sizes : $default_size;
    
    $gd_marker_sizes[$icon_url] = $sizes;
    
    return $sizes;
}

add_action('wp_footer', 'geodir_map_load_script', 10);
add_action('admin_footer', 'geodir_map_load_script', 10);
/**
 * Adds the marker cluster script for OpenStreetMap when Google JS Library not loaded.
 *
 * @since 1.6.1
 * @package GeoDirectory
 */
function geodir_map_load_script() {
    if (in_array(geodir_map_name(), array('auto', 'google')) && wp_script_is( 'geodirectory-googlemap-script', 'done')) {
        $plugin_url = geodir_plugin_url();
?>
<script type="text/javascript">
if (!(window.google && typeof google.maps !== 'undefined')) {
    var css = document.createElement("link");css.setAttribute("rel","stylesheet");css.setAttribute("type","text/css");css.setAttribute("media","all");css.setAttribute("id","geodirectory-leaflet-style-css");css.setAttribute("href","<?php echo $plugin_url;?>/assets/leaflet/leaflet.css?ver=<?php echo GEODIRECTORY_VERSION;?>");
    document.getElementsByTagName("head")[0].appendChild(css);
    var css = document.createElement("link");css.setAttribute("rel","stylesheet");css.setAttribute("type","text/css");css.setAttribute("media","all");css.setAttribute("id","geodirectory-leaflet-routing-style");css.setAttribute("href","<?php echo $plugin_url;?>/assets/leaflet/routing/leaflet-routing-machine.css?ver=<?php echo GEODIRECTORY_VERSION;?>");
    document.getElementsByTagName("head")[0].appendChild(css);
    document.write('<' + 'script id="geodirectory-leaflet-script" src="<?php echo $plugin_url;?>/assets/leaflet/leaflet.min.js?ver=<?php echo GEODIRECTORY_VERSION;?>" type="text/javascript"><' + '/script>');
    document.write('<' + 'script id="geodirectory-leaflet-geo-script" src="<?php echo $plugin_url;?>/assets/leaflet/osm.geocode.min.js?ver=<?php echo GEODIRECTORY_VERSION;?>" type="text/javascript"><' + '/script>');
    document.write('<' + 'script id="geodirectory-leaflet-routing-script" src="<?php echo $plugin_url;?>/assets/leaflet/routing/leaflet-routing-machine.min.js?ver=<?php echo GEODIRECTORY_VERSION;?>" type="text/javascript"><' + '/script>');
    document.write('<' + 'script id="geodirectory-o-overlappingmarker-script" src="<?php echo $plugin_url;?>/assets/jawj/oms-leaflet.min.js?ver=<?php echo GEODIRECTORY_VERSION;?>" type="text/javascript"><' + '/script>');
}
</script>
<?php
    }
}

function geodir_default_marker_icon( $full_path = false ) {
    $icon = geodir_get_option( 'geodir_default_marker_icon' );
    
    if ( !$icon ) {
        $icon = geodir_file_relative_url( geodir_plugin_url() . '/includes/maps/icons/pin.png' );
        geodir_update_option( 'geodir_default_marker_icon', $icon );
    }
    
    $icon = geodir_file_relative_url( $icon, $full_path );
    
    return apply_filters( 'geodir_default_marker_icon', $icon, $full_path );
}

/**
 * Returns the default language of the map.
 *
 * @since   1.0.0
 * @package GeoDirectory
 * @return string Returns the default language.
 */
function geodir_get_map_default_language() {
    $geodir_default_map_language = geodir_get_option( 'map_language' );
    if ( empty( $geodir_default_map_language ) ) {
        $geodir_default_map_language = 'en';
    }

    /**
     * Filter default map language.
     *
     * @since 1.0.0
     *
     * @param string $geodir_default_map_language Default map language.
     */
    return apply_filters( 'geodir_default_map_language', $geodir_default_map_language );
}

/**
 * Returns the Google maps api key.
 *
 * @since   1.6.4
 * @since   2.0.0 Added $query param.
 * @param bool $query If this is for a query and if so add the key=.
 * @package GeoDirectory
 * @return string Returns the api key.
 */
function geodir_get_map_api_key($query = false) {
    $key = geodir_get_option( 'google_maps_api_key' );

    if($key && $query){
        $key = "&key=".$key;
    }

    /**
     * Filter Google maps api key.
     *
     * @since 1.6.4
     *
     * @param string $key Google maps api key.
     */
    return apply_filters( 'geodir_google_api_key', $key );
}

/**
 * Map Taxonomy walker.
 *
 * @since 2.0.0
 * @package GeoDirectory
 *
 * @global object $gd_session GeoDirectory Session object.
 *
 * @param string $cat_taxonomy Name of the taxonomy e.g place_category.
 * @param int $cat_parent Optional. Parent term ID to retrieve its child terms. Default 0.
 * @param bool $hide_empty Optional. Do you want to hide the terms that has no posts. Default true.
 * @param int $padding Optional. CSS padding value in pixels. e.g: 12 will be considers as 12px.
 * @param string $map_canvas Unique canvas name for your map.
 * @param bool $child_collapse Do you want to collapse child terms by default?.
 * @param bool $is_home_map Optional. Is this a home page map? Default: false.
 * @return string|void
 */
function geodir_map_taxonomy_walker( $cat_taxonomy, $cat_parent = 0, $hide_empty = true, $padding = 0, $map_canvas = '', $child_collapse, $is_home_map = false ) {
    global $cat_count, $geodir_cat_icons, $gd_session;

    $exclude_categories 	= geodir_get_option( 'geodir_exclude_cat_on_map', array() );
    $exclude_categories_new = geodir_get_option( 'geodir_exclude_cat_on_map_upgrade' );

    // check if exclude categories saved before fix of categories identical names
    if ($exclude_categories_new) {
        $gd_cat_taxonomy = isset($cat_taxonomy[0]) ? $cat_taxonomy[0] : '';
        $exclude_categories = !empty($exclude_categories[$gd_cat_taxonomy]) && is_array($exclude_categories[$gd_cat_taxonomy]) ? array_unique($exclude_categories[$gd_cat_taxonomy]) : array();
    }

    $exclude_cat_str = implode(',', $exclude_categories);

    if ($exclude_cat_str == '') {
        $exclude_cat_str = '0';
    }

    $cat_terms = get_terms( array( 'taxonomy' => $cat_taxonomy, 'parent' => $cat_parent, 'exclude' => $exclude_cat_str, 'hide_empty ' => $hide_empty ) );

    if ($hide_empty) {
        $cat_terms = geodir_filter_empty_terms($cat_terms);
    }

    $main_list_class = '';
    //If there are terms, start displaying
    if (count($cat_terms) > 0) {
        //Displaying as a list
        $p = $padding * 15;
        $padding++;

        if ($cat_parent == 0) {
            $list_class = 'main_list';
            $display = '';
        } else {
            $list_class = 'sub_list';
            $display = !$child_collapse ? '' : 'display:none';
        }

        $out = '<ul class="treeview ' . $list_class . '" style="margin-left:' . $p . 'px;' . $display . ';">';

        $geodir_cat_icons = geodir_get_term_icon();

        $geodir_default_map_search_pt = (geodir_get_option('geodir_default_map_search_pt')) ? geodir_get_option('geodir_default_map_search_pt') :  'gd_place';
        if ($is_home_map && $homemap_catlist_ptype = $gd_session->get('homemap_catlist_ptype')) {
            $geodir_default_map_search_pt = $homemap_catlist_ptype;
        }
        $post_type = isset($_REQUEST['post_type']) ? $_REQUEST['post_type'] : (isset($_REQUEST['gd_posttype']) ? $_REQUEST['gd_posttype'] : $geodir_default_map_search_pt);
        
        foreach ($cat_terms as $cat_term):
            $icon = !empty($geodir_cat_icons) && isset($geodir_cat_icons[$cat_term->term_id]) ? $geodir_cat_icons[$cat_term->term_id] : '';

            if (!in_array($cat_term->term_id, $exclude_categories)):
                //Secret sauce.  Function calls itself to display child elements, if any
                $checked = 'checked="checked"';

                // Untick the category by default on home map
                if ($is_home_map && $geodir_home_map_untick = geodir_get_option('geodir_home_map_untick')) {
                    if (geodir_wpml_is_taxonomy_translated($post_type . 'category')) { // if WPML
                        global $sitepress;
                        $default_lang = $sitepress->get_default_language();
                        $term_id = icl_object_id($cat_term->term_id, $post_type.'category', true, $default_lang);
                    }else{
                        $term_id = $cat_term->term_id;
                    }
                    if (!empty($geodir_home_map_untick) && in_array($post_type . '_' . $term_id, $geodir_home_map_untick)) {
                        $checked = '';
                    }
                }

                $term_check = '<input type="checkbox" ' . $checked . ' id="' .$map_canvas.'_tick_cat_'. $cat_term->term_id . '" class="group_selector ' . $main_list_class . '"';
                $term_check .= ' name="' . $map_canvas . '_cat[]" ';
                $term_check .= '  title="' . esc_attr(geodir_utf8_ucfirst($cat_term->name)) . '" value="' . $cat_term->term_id . '" onclick="javascript:build_map_ajax_search_param(\'' . $map_canvas . '\',false, this)">';
                $term_img = '<img height="15" width="15" alt="' . $cat_term->taxonomy . '" src="' . $icon . '" title="' . geodir_utf8_ucfirst($cat_term->name) . '"/>';
                $out .= '<li>' . $term_check . '<label for="' . $map_canvas.'_tick_cat_'. $cat_term->term_id . '">' . $term_img . geodir_utf8_ucfirst($cat_term->name) . '</label><i class="fa fa-long-arrow-down"></i>';

            endif;


            // get sub category by recursion
            $out .= geodir_map_taxonomy_walker( $cat_taxonomy, $cat_term->term_id, $hide_empty, $padding, $map_canvas, $child_collapse, $is_home_map );

            $out .= '</li>';

        endforeach;

        $out .= '</ul>';

        return $out;
    } else {
        if ($cat_parent == 0)
            return _e('No category', 'geodirectory');
    }
    return;
}

function geodir_get_map_popup_content( $item ) {
	global $post, $gd_post;

	$content = '';

	if ( is_int( $item ) ) {
		$item = geodir_get_post_info( $item );
	}

	if ( ! ( ! empty( $item->post_type ) && geodir_is_gd_post_type( $item->post_type ) ) ) {
		return $content;
	}

	$post		= $item;
	$gd_post 	= $item;

	setup_postdata( $gd_post );

	$content = GeoDir_Template_Loader::map_popup_template_content();

	if ( $content != '' ) {
		$content = trim( $content );
	}

	return $content;
}