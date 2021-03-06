<?php
/**
 * Setup menus in WP admin.
 *
 * @author   AyeCode
 * @category Admin
 * @package  GeoDirectory/Admin
 * @version  2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * GeoDir_Admin_Menus Class.
 */
class GeoDir_Admin_Menus {

	/**
	 * Hook in tabs.
	 */
	public function __construct() {
		// Add menus
		add_action( 'admin_menu', array( $this, 'admin_menu' ), 9 );
		add_action( 'admin_menu', array( $this, 'settings_menu' ), 50 );
		add_action( 'admin_menu', array( $this, 'status_menu' ), 60 );
		add_action( 'admin_menu', array( $this, 'cpt_settings_menu' ), 10 );

		if ( apply_filters( 'geodirectory_show_addons_page', true ) ) {
			add_action( 'admin_menu', array( $this, 'addons_menu' ), 70 );
		}

		// Add endpoints custom URLs in Appearance > Menus > Pages.
		add_action( 'admin_head-nav-menus.php', array( $this, 'add_nav_menu_meta_boxes' ) );
	}

	/**
	 * Add menu items.
	 */
	public function admin_menu() {
		global $menu;
		
		// @todo we should change this to manage_geodirectory capability on install
		if (current_user_can('manage_options')) $menu[] = array('', 'read', 'separator-geodirectory', '', 'wp-menu-separator geodirectory');

		add_menu_page( __( 'Geodirectory Dashboard', 'geodirectory' ), __( 'GeoDirectory', 'geodirectory' ), 'manage_options', 'geodirectory', array( $this, 'dashboard_page' ), 'dashicons-admin-site', '55.1984' );
		add_submenu_page( 'geodirectory', __( 'Geodirectory Dashboard', 'geodirectory' ), __( 'Dashboard', 'geodirectory' ), 'manage_options', 'geodirectory', array( $this, 'dashboard_page' ) );
		

	}
	
	/**
	 * Dashboard page.
	 */
	public function dashboard_page(){
		$dashboard = GeoDir_Admin_Dashboard::instance();
		
		$dashboard->output();
	}

	/**
	 * Add CPT Settings menu.
	 */
	public function cpt_settings_menu(){
		// Add CPT setting to each GD CPT
		$post_types = geodir_get_option( 'post_types' );
		if(!empty($post_types)){
			foreach($post_types as $name => $cpt){
				//echo '###'.$name;
				//print_r($cpt);
				add_submenu_page('edit.php?post_type='.$name, __('Settings', 'geodirectory'), __('Settings', 'geodirectory'), 'manage_options', 'gd-cpt-settings', array( $this, 'settings_page' ) );
			}
		}
	}

	/**
	 * Add menu item.
	 */
	public function settings_menu() {
		$settings_page = add_submenu_page( 'geodirectory', __( 'GeoDirectory settings', 'geodirectory' ),  __( 'Settings', 'geodirectory' ) , 'manage_options', 'gd-settings', array( $this, 'settings_page' ) );

		//add_action( 'load-' . $settings_page, array( $this, 'settings_page_init' ) );
	}


	/**
	 * Add menu item.
	 */
	public function status_menu() {
		add_submenu_page( 'geodirectory', __( 'GeoDirectory status', 'geodirectory' ),  __( 'Status', 'geodirectory' ) , 'manage_options', 'gd-status', array( $this, 'status_page' ) );
	}

	/**
	 * Addons menu item.
	 */
	public function addons_menu() {
		add_submenu_page( 'geodirectory', __( 'GeoDirectory extensions', 'geodirectory' ),  __( 'Extensions', 'geodirectory' ) , 'manage_options', 'gd-addons', array( $this, 'addons_page' ) );
	}




	/**
	 * Init the reports page.
	 */
	public function reports_page() {
		GeoDir_Admin_Reports::output();
	}

	/**
	 * Init the settings page.
	 */
	public function settings_page() {
		GeoDir_Admin_Settings::output();
	}



	/**
	 * Init the status page.
	 */
	public function status_page() {
		GeoDir_Admin_Status::output();
	}

	/**
	 * Init the addons page.
	 */
	public function addons_page() {
		//echo '### addons page';
		GeoDir_Admin_Addons::output();
	}

	/**
	 * Add custom nav meta box.
	 *
	 * Adapted from http://www.johnmorrisonline.com/how-to-add-a-fully-functional-custom-meta-box-to-wordpress-navigation-menus/.
	 */
	public function add_nav_menu_meta_boxes() {
		add_meta_box( 'geodirectory_endpoints_nav_link', __( 'GeoDirectory endpoints', 'geodirectory' ), array( $this, 'nav_menu_links' ), 'nav-menus', 'side', 'low' );
	}

	/**
	 * Output menu links.
	 */
	public function nav_menu_links() {
		// Get items from account menu.
		$endpoints = $this->get_endpoints();

		$endpoints = apply_filters( 'geodirectory_custom_nav_menu_items', $endpoints );




		?>
		<div id="geodirectory-endpoints" class="posttypediv">

			<?php

			if(!empty($endpoints['cpt_archives'])){
			?>
			<h4><?php _e('CPT Archives','geodirectory');?></h4>
			<div id="tabs-panel-geodirectory-endpoints" class="tabs-panel tabs-panel-active">
				<ul id="geodirectory-endpoints-checklist" class="categorychecklist form-no-clear">
					<?php
					$walker = new Walker_Nav_Menu_Checklist(array());
					echo walk_nav_menu_tree(array_map('wp_setup_nav_menu_item', $endpoints['cpt_archives']), 0, (object) array('walker' => $walker));
					?>
				</ul>
			</div>
			<?php }


			if(!empty($endpoints['cpt_add_listing'])){
				?>
				<h4><?php _e('CPT Add Listing','geodirectory');?></h4>
				<div id="tabs-panel-geodirectory-endpoints" class="tabs-panel tabs-panel-active">
					<ul id="geodirectory-endpoints-checklist" class="categorychecklist form-no-clear">
						<?php
						$walker = new Walker_Nav_Menu_Checklist(array());
						echo walk_nav_menu_tree(array_map('wp_setup_nav_menu_item', $endpoints['cpt_add_listing']), 0, (object) array('walker' => $walker));
						?>
					</ul>
				</div>
			<?php }

			if(!empty($endpoints['pages'])){
				?>
				<h4><?php _e('GD Pages','geodirectory');?></h4>
				<div id="tabs-panel-geodirectory-endpoints" class="tabs-panel tabs-panel-active">
					<ul id="geodirectory-endpoints-checklist" class="categorychecklist form-no-clear">
						<?php
						$walker = new Walker_Nav_Menu_Checklist(array());
						echo walk_nav_menu_tree(array_map('wp_setup_nav_menu_item', $endpoints['pages']), 0, (object) array('walker' => $walker));
						?>
					</ul>
				</div>
			<?php }



				?>
			<p class="button-controls">
				<span class="list-controls">
					<a href="<?php echo admin_url( 'nav-menus.php?page-tab=all&selectall=1#geodirectory-endpoints' ); ?>" class="select-all"><?php _e( 'Select all', 'geodirectory' ); ?></a>
				</span>
				<span class="add-to-menu">
					<input type="submit" class="button-secondary submit-add-to-menu right" value="<?php esc_attr_e( 'Add to menu', 'geodirectory' ); ?>" name="add-post-type-menu-item" id="submit-geodirectory-endpoints">
					<span class="spinner"></span>
				</span>
			</p>
		</div>
		<?php
	}

	/**
	 * Get GD menu items.
	 *
	 * @since 2.0.0
	 * @return array
	 */
	public function get_endpoints() {
		$items = array();
		$items['cpt_archives'] = array();
		$items['cpt_add_listing'] = array();
		$items['pages'] = array();
		$items['special'] = array();
		$loop_index = 999;

		// Get the add listing page id and url
		$add_listing_page_id = geodir_add_listing_page_id();
		if($add_listing_page_id){
			$add_listing_page_url = get_page_link($add_listing_page_id);
		}
		

		// Add the Location menu item
		$gd_location_page_id = geodir_location_page_id();
		if($gd_location_page_id){
			$item = new stdClass();
			$item->object_id = $gd_location_page_id;
			$item->db_id = 0;
			$item->object =  'page';
			$item->menu_item_parent = 0;
			$item->type = 'post_type';
			$item->title = __('Location page','geodirectory');
			$item->url = get_page_link($gd_location_page_id);
			$item->target = '';
			$item->attr_title = '';
			$item->classes = array('gd-menu-item');
			$item->xfn = '';

			$items['pages'][] = $item;
		}

		// Add the Search menu item
		$gd_search_page_id = geodir_search_page_id();
		if($gd_search_page_id){
			$item = new stdClass();
			$item->object_id = $gd_search_page_id;
			$item->db_id = 0;
			$item->object =  'page';
			$item->menu_item_parent = 0;
			$item->type = 'post_type';
			$item->title = __('Search page','geodirectory');
			$item->url = get_page_link($gd_search_page_id);
			$item->target = '';
			$item->attr_title = '';
			$item->classes = array('gd-menu-item');
			$item->xfn = '';

			$items['pages'][] = $item;
		}

		// Add the Search menu item
		$gd_tc_page_id = geodir_terms_and_conditions_page_id();
		if($gd_tc_page_id){
			$item = new stdClass();
			$item->object_id = $gd_tc_page_id;
			$item->db_id = 0;
			$item->object =  'page';
			$item->menu_item_parent = 0;
			$item->type = 'post_type';
			$item->title = __('Terms and Conditions page','geodirectory');
			$item->url = get_page_link($gd_tc_page_id);
			$item->target = '';
			$item->attr_title = '';
			$item->classes = array('gd-menu-item');
			$item->xfn = '';

			$items['pages'][] = $item;
		}

		// Add CPT setting to each GD CPT
		$post_types = geodir_get_option( 'post_types' );
		if(!empty($post_types)){
			foreach($post_types as $name => $cpt){

				// item for archives
				$item = new stdClass();
				$loop_index++;
				//echo '###'.$name;
				//print_r($cpt);
				//add_submenu_page('edit.php?post_type='.$name, __('Settings', 'geodirectory'), __('Settings', 'geodirectory'), 'manage_options', 'gd-cpt-settings', array( $this, 'settings_page' ) );
//				$items['cpt_archives'][$name] = array(
//					'menu-item-type'            =>  'post_type_archive',
//					'menu-item-title'           =>  __($cpt['labels']['name'],'geodirectory'),
//					'menu-item-url'             =>  get_post_type_archive_link( $name ),
//					'menu-item-classes'         =>  'gd-menu-item',
//				);

				$item->object_id = $loop_index;
				$item->db_id = 0;
				$item->object =  $name;
				$item->menu_item_parent = 0;
				$item->type = 'post_type_archive';
				$item->title = __($cpt['labels']['name'],'geodirectory');
				$item->url = get_post_type_archive_link($name);
				$item->target = '';
				$item->attr_title = '';
				$item->classes = array('gd-menu-item');
				$item->xfn = '';

				$items['cpt_archives'][$name] = $item;


				if($add_listing_page_id){
					// item for add listing
					$add_item = new stdClass();
					$loop_index++;

					$add_item->object_id = $loop_index;
					$add_item->db_id = 0;
					$add_item->object =  'page';
					$add_item->menu_item_parent = 0;
					$add_item->type = 'custom';
					$add_item->title = sprintf( __('Add %s', 'geodirectory'), __($cpt['labels']['singular_name'],'geodirectory') );
					$add_item->url = trailingslashit($add_listing_page_url)."?listing_type=$name";
					$add_item->target = '';
					$add_item->attr_title = '';
					$add_item->classes = array('gd-menu-item');
					$add_item->xfn = '';

					$items['cpt_add_listing'][$name] = $add_item;
				}

			}
		}

		return apply_filters( 'geodirectory_menu_items', $items );
	}
}

return new GeoDir_Admin_Menus();
