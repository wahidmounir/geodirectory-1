<?php

/**
 * Core class to access map markers data via the REST API.
 *
 * @since 2.0.0
 *
 * @see WP_REST_Taxonomies_Controller
 */
class GeoDir_REST_Markers_Controller extends WP_REST_Controller {

    /**
     * Constructor.
     *
     * @access public
     */
    public function __construct() {
        $this->namespace = GEODIR_REST_SLUG . '/v' . GEODIR_REST_API_VERSION;
		$this->rest_base = 'markers';
		
		add_filter( 'geodir_rest_markers_query_where', array( $this, 'set_query_where' ), 10, 2 );
    }

    /**
     * Registers the routes for the objects of the controller.
     *
     * @access public
     *
     * @see register_rest_route()
     */
    public function register_routes() {

        register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_item' ),
				'permission_callback' => array( $this, 'get_item_permissions_check' ),
				'args'            	  => $this->get_collection_params(),
			)
		) );

		register_rest_route( $this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', array(
			'args' => array(
				'id' => array(
					'description' => __( 'Unique identifier for the object.' ),
					'type'        => 'integer',
				),
			),
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_marker_item' ),
				'permission_callback' => array( $this, 'get_marker_item_permissions_check' ),
			)
		) );
    }
	
	/**
	 * Checks if a given request has access to read and manage markers.
	 *
	 * @since 2.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool True if the request has read access for the item, otherwise false.
	 */
	public function get_item_permissions_check( $request ) {
		return $this->show_in_rest();
	}
	
	/**
	 * Retrieves the query params for markers collections.
	 *
	 * @since 2.0.0
	 *
	 * @return array Markers collection parameters.
	 */
	public function get_collection_params() {
		$query_params = array();

		$query_params['post_type'] = array(
			'type'              => 'string',
			'description'       => __( 'Limit results to specific post type.' ),
			'sanitize_callback' => 'sanitize_key',
		);

		$query_params['term']   = array(
			'type'              => 'array',
			'default'           => array(),
			'description'       => __( 'Limit results to specific term IDs.' ),
			'items'             => array(
				'type'          => 'integer',
			),
		);

		$query_params['post']   = array(
			'type'              => 'array',
			'default'           => array(),
			'description'       => __( 'Limit results to specific post IDs.' ),
			'items'             => array(
				'type'          => 'integer',
			),
		);

		$query_params['search']	= array(
			'type'               => 'string',
			'description'        => __( 'Limit results to those matching post title.' ),
			'sanitize_callback'  => 'sanitize_text_field',
			'validate_callback'  => 'rest_validate_request_arg',
		);

		/**
		 * Filter collection parameters for the markers controller.
		 *
		 * @since 2.0.0
		 *
		 * @param array $query_params JSON Schema-formatted collection parameters.
		 */
		return apply_filters( 'geodir_rest_markers_collection_params', $query_params );
	}
    
    public function show_in_rest() {
        return apply_filters( 'geodir_rest_markers_show_in_rest', true, $this );
    }
	
	/**
	 * Retrieves the makers.
	 *
	 * @since 2.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return array|WP_Error Array on success, or WP_Error object on failure.
	 */
	public function get_item( $request ) {
		global $geodir_icon_basedir, $geodir_rest_cache_icons;
		if ( empty( $geodir_rest_cache_icons ) ) {
			$geodir_rest_cache_icons = array();
		}

		if ( ! $this->show_in_rest() ) {
			return new WP_Error( 'rest_invalid_access', __( 'Cannot view markers.' ) );
		}
		
		if ( ! ( ! empty( $request['post_type'] ) && geodir_is_gd_post_type( $request['post_type'] ) ) ) {
			return new WP_Error( 'rest_invalid_param', __( 'Enter a valid GD post type for post_type parameter.' ) );
		}

		$wp_upload_dir = wp_upload_dir();
		$geodir_icon_basedir = $wp_upload_dir['basedir'];

		$response = array();

		$items = $this->get_markers( $request );
		
		if ( ! empty( $items ) ) {
			$response[ 'total' ] 		= count( $items );
			$response[ 'baseurl' ] 		= $wp_upload_dir['baseurl'];
			$response[ 'content_url' ] 	= trailingslashit( WP_CONTENT_URL );
			$response[ 'icons' ] 		= $geodir_rest_cache_icons;
			$response[ 'items' ] 		= $items;
		}

		return rest_ensure_response( $response );
	}
	
	public function get_markers( $request ) {
        global $wpdb, $plugin_prefix;

		$items = array();
		$detail_table = $plugin_prefix . $request['post_type'] . '_detail';
		
		$fields = "p.ID, p.post_title, pd.default_category, pd.latitude, pd.longitude";
		$fields = apply_filters( 'geodir_rest_markers_query_fields', $fields, $request );
		
		$join = "LEFT JOIN {$detail_table} AS pd ON pd.post_id = p.ID";
		$join = apply_filters( 'geodir_rest_markers_query_join', $join, $request );
		
		if ( ! empty( $request['post'] ) && is_array( $request['post'] ) && count( $request['post'] ) == 1 ) {
			$where = "p.post_status IN('publish', 'pending', 'draft', 'gd-closed')";
		} else {
			$where = "p.post_status = 'publish'";
		}
		$where = apply_filters( 'geodir_rest_markers_query_where', $where, $request );

		if ( $where ) {
			$where = "WHERE {$where}";
		}
		
		$group_by = apply_filters( 'geodir_rest_markers_query_group_by', "", $request );
		if ( $group_by ) {
			$group_by = "GROUP BY {$group_by}";
		}
		
		$order_by = apply_filters( 'geodir_rest_markers_query_order_by', "", $request );
		if ( $order_by ) {
			$order_by = "ORDER BY {$order_by}";
		}
		
		$limit = apply_filters( 'geodir_rest_markers_query_limit', "", $request );
		if ( $limit ) {
			$limit = "LIMIT {$limit}";
		}
		
		$sql = "SELECT {$fields} FROM {$wpdb->posts} AS p {$join} {$where} {$group_by} {$order_by} {$limit}";
		
		$sql = apply_filters( 'geodir_rest_markers_query', $sql, $request );
		
		$results = $wpdb->get_results( $sql );
		
		if ( ! empty( $results ) ) {
			foreach ( $results as $item ) {
				if ( ! empty( $item->latitude ) && ! empty( $item->longitude ) ) {
					$items[] = $this->prepare_item_for_response( $item, $request );
				}
			}
		}

		return apply_filters( 'geodir_rest_get_markers', $items, $request );
    }
	
	/*
	icons
	array(
		'34' => array(						icon id
			'i' => '/2014/08/Feature.png',	icon relative url
			'w' => 36,						icon width
			'h' => 45,						icon height
		)
	);
	items
	array(
		'p' => 145,						post id
		'm' => 145,						marker id / post id
		'lt' => '12.9773088',			latitude
		'ln' => '77.57075069999996',	longitude
		't' => 'Longwood Gardens',		post title	
		'i' => '34'						icon id
	);
	*/
	public function prepare_item_for_response( $item, $request ) {
		global $geodir_icon_basedir, $geodir_rest_cache_marker, $geodir_rest_cache_icons;
		if ( empty( $geodir_rest_cache_marker ) ) {
			$geodir_rest_cache_marker = array();
		}

		$default_category = ! empty( $item->default_category ) ? $item->default_category : '';
		
		$post_title = $item->post_title;
		// @todo need to check for special chars
		/*
		if ( ! empty( $post_title ) ) {
			$post_title = htmlentities( $post_title, ENT_QUOTES, get_option( 'blog_charset' ) ); // Quotes in csv title import break maps
			$post_title = wp_specialchars_decode( $post_title ); // Fixed #post-320722 on 2016-12-08
		}
		*/

		$response = array();
		$response['m'] 	= $item->ID;
		$response['lt'] = $item->latitude;
		$response['ln'] = $item->longitude;
		$response['t'] 	= $post_title;
		
		$icon_id = ! empty( $default_category ) ? $default_category : 'd'; // d = default
		
		if ( empty( $geodir_rest_cache_icons[ $icon_id ] ) ) {
			$icon_url 		= '';
			$icon_width 	= 36;
			$icon_height 	= 45;
			
			if ( ! empty( $geodir_rest_cache_marker ) && ! empty( $geodir_rest_cache_marker[ $default_category ]['i'] ) ) {
				$icon_url 	= $geodir_rest_cache_marker[ $default_category ]['i'];
			} else {
				$icon_url = geodir_get_cat_icon( $default_category, false, true );
				if ( empty( $icon_url ) ) {
					$icon_id = 'd';
					$icon_url = geodir_default_marker_icon( false );
				}

				if ( $default_category ) {
					$geodir_rest_cache_marker[ $default_category ]['i'] = $icon_url;
				}
			}
			
			if ( ! empty( $icon_url ) ) {
				if ( ! empty( $geodir_rest_cache_marker ) && ! empty( $geodir_rest_cache_marker[ $icon_url ]['w'] ) ) {
					$icon_width 	= $geodir_rest_cache_marker[ $icon_url ]['w'];
					$icon_height 	= $geodir_rest_cache_marker[ $icon_url ]['h'];
				} else {
					$icon_size = geodir_get_marker_size( $geodir_icon_basedir . $icon_url );
					if ( ! empty( $icon_size ) ) {
						$icon_width 	= $icon_size['w'];
						$icon_height 	= $icon_size['h'];
					}

					$geodir_rest_cache_marker[ $icon_url ]['w'] = $icon_width;
					$geodir_rest_cache_marker[ $icon_url ]['h'] = $icon_height;
				}
			}
			
			$geodir_rest_cache_icons[ $icon_id ] = array(
				'i' => $icon_url,
				'w' => $icon_width,
				'h' => $icon_height
			);
		}
		$response['i'] 	= $icon_id;

		/**
		 * Filters a marker data returned from the API.
		 *
		 * @param WP_REST_Response $response The response object.
		 * @param object           $item     The original marker data.
		 * @param WP_REST_Request  $request  Request used to generate the response.
		 */
		return apply_filters( 'geodir_rest_prepare_marker', $response, $item, $request );
	}
	
	public function set_query_where( $where, $request ) {
		global $wpdb;

		if ( ! empty( $request['search'] ) ) {
			$where .= " AND p.post_title LIKE '%" . addslashes( $request['search'] ) . "%'";
		}

		if ( ! empty( $request['post'] ) && is_array( $request['post'] ) ) {
			$where .= " AND p.ID IN( '" . implode( "','", $request['post'] ) . "' )";
		}

		if ( ! empty( $request['term'] ) && is_array( $request['term'] ) ) {
			$terms_where = array();
			foreach ( $request['term'] as $term_id ) {
				if ( ! empty( $term_id ) ) {
					$terms_where[] = $wpdb->prepare( "FIND_IN_SET( %d, pd.post_category )", array( $term_id ) );
				}
			}
			if ( ! empty( $terms_where ) ) {
				$where .= " AND ( " . implode( " OR ", $terms_where ) . " )";
			}
		}

		return $where;
	}

	/**
	 * Retrieves the makers.
	 *
	 * @since 2.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return array|WP_Error Array on success, or WP_Error object on failure.
	 */
	public function get_marker_item( $request ) {
		$id = ! empty( $request['id'] ) ? (int)$request['id'] : 0; 

		$response		  = array();
		$response['html'] = $id > 0 ? $this->marker_content( $id ) : '';

		return $response;
	}

	public function marker_content( $id ) {
		$content = geodir_get_map_popup_content( $id );

		return $content;
	}

	/**
	 * Checks if a given request has access to read and manage markers.
	 *
	 * @since 2.0.0
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool True if the request has read access for the item, otherwise false.
	 */
	public function get_marker_item_permissions_check( $request ) {
		return $this->show_in_rest();
	}
}
