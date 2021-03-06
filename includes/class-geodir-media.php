<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Media Class
 *
 * @version 2.0.0
 */
class GeoDir_Media {


	/**
	 * Get the post type fields that are for file uploads and return the allowed file types.
	 *
	 * @param $post_type
	 *
	 * @return array
	 */
	public static function get_file_fields($post_type){
		global $wpdb;
		$fields = array();

		$result = $wpdb->get_results($wpdb->prepare("SELECT htmlvar_name,extra_fields FROM ".GEODIR_CUSTOM_FIELDS_TABLE." WHERE post_type=%s AND field_type='file' ",$post_type),ARRAY_A);
		if(!empty($result)){
			foreach($result as $field){
				$extra_fields = isset($field['extra_fields']) ? maybe_unserialize($field['extra_fields']) : array();
				$fields[$field['htmlvar_name']] = isset($extra_fields['gd_file_types']) ? maybe_unserialize($extra_fields['gd_file_types']) : array();
			}
		}

		return $fields;
	}

	/**
	 * Check if the file type is an image.
	 *
	 * @param $mime_type
	 *
	 * @return bool|resource
	 */
	public static function is_image($mime_type){

		switch ( $mime_type ) {
			case 'image/jpeg':
				$image = true;
				break;
			case 'image/png':
				$image = true;
				break;
			case 'image/gif':
				$image = true;
				break;
			default:
				$image = false;
				break;
		}

		return $image;
	}

	/**
	 * Handles post image upload.
	 *
	 * @since 1.0.0
	 * @package GeoDirectory
	 */
	public static function post_attachment_upload() {

		// the post id
		$field_id = isset($_POST["imgid"]) ? esc_attr($_POST["imgid"]) : '';
		$post_id = isset($_POST["post_id"]) ? absint($_POST["post_id"]) : '';

		// set GD temp upload dir
		add_filter( 'upload_dir', array( __CLASS__, 'temp_upload_dir' ) );

		// change file orientation if needed
		//$fixed_file = geodir_exif($_FILES[$imgid . 'async-upload']);

		$fixed_file = $_FILES[ $field_id . 'async-upload' ];

		// handle file upload
		$status = wp_handle_upload( $fixed_file, array(
			'test_form' => true,
			'action'    => 'geodir_post_attachment_upload'
		) );
		// unset GD temp upload dir
		remove_filter( 'upload_dir', array( __CLASS__, 'temp_upload_dir' ) );

		if ( ! isset( $status['url'] ) && isset( $status['error'] ) ) {
			print_r( $status );
		}
		//print_r( $status );exit;


		// send the uploaded file url in response
		if ( isset( $status['url'] ) && $post_id) {

			// insert to DB
			$file_info = self::insert_attachment($post_id,$field_id,$status['url'],'', '', -1,0);

			$wp_upload_dir = wp_upload_dir();
			echo $wp_upload_dir['baseurl'] . $file_info['file'] ."|".$file_info['ID']."||";

		} elseif( isset( $status['url'] )) {
			echo $status['url'];
		}
		else
		{
			echo 'x';
		}

		exit;
	}

	/**
	 * Get the attachment id from the file path.
	 *
	 * @param $path
	 * @param string $post_id
	 *
	 * @return null|string
	 */
	public static function get_id_from_file_path($path,$post_id = ''){
		global $wpdb;

		if($post_id){
			$result = $wpdb->get_var($wpdb->prepare("SELECT ID FROM ".GEODIR_ATTACHMENT_TABLE." WHERE post_id=%d AND file=%s",$post_id,$path));

		}else{
			$result = $wpdb->get_var($wpdb->prepare("SELECT ID FROM ".GEODIR_ATTACHMENT_TABLE." WHERE file=%s",$path));

		}

		return $result;

	}

	/**
	 * Create the image sizes and return the image metadata.
	 *
	 * @param $file
	 *
	 * @return array
	 */
	public static function create_image_sizes( $file ){
		$metadata = array();
		$imagesize = getimagesize( $file );
		$metadata['width'] = $imagesize[0];
		$metadata['height'] = $imagesize[1];

		// Make the file path relative to the upload dir.
		$metadata['file'] = _wp_relative_upload_path($file);

		// Make thumbnails and other intermediate sizes.
		$_wp_additional_image_sizes = wp_get_additional_image_sizes();

		$sizes = array();
		foreach ( get_intermediate_image_sizes() as $s ) {
			$sizes[$s] = array( 'width' => '', 'height' => '', 'crop' => false );
			if ( isset( $_wp_additional_image_sizes[$s]['width'] ) ) {
				// For theme-added sizes
				$sizes[$s]['width'] = intval( $_wp_additional_image_sizes[$s]['width'] );
			} else {
				// For default sizes set in options
				$sizes[$s]['width'] = get_option( "{$s}_size_w" );
			}

			if ( isset( $_wp_additional_image_sizes[$s]['height'] ) ) {
				// For theme-added sizes
				$sizes[$s]['height'] = intval( $_wp_additional_image_sizes[$s]['height'] );
			} else {
				// For default sizes set in options
				$sizes[$s]['height'] = get_option( "{$s}_size_h" );
			}

			if ( isset( $_wp_additional_image_sizes[$s]['crop'] ) ) {
				// For theme-added sizes
				$sizes[$s]['crop'] = $_wp_additional_image_sizes[$s]['crop'];
			} else {
				// For default sizes set in options
				$sizes[$s]['crop'] = get_option( "{$s}_crop" );
			}
		}

		/**
		 * Filters the image sizes automatically generated when uploading an image.
		 *
		 * @since 2.9.0
		 * @since 4.4.0 Added the `$metadata` argument.
		 *
		 * @param array $sizes    An associative array of image sizes.
		 * @param array $metadata An associative array of image metadata: width, height, file.
		 */
		$sizes = apply_filters( 'intermediate_image_sizes_advanced', $sizes, $metadata );

		if ( $sizes ) { 
			$editor = wp_get_image_editor( $file );

			if ( ! is_wp_error( $editor ) )
				$metadata['sizes'] = $editor->multi_resize( $sizes );
		} else {
			$metadata['sizes'] = array();
		}

		// Fetch additional metadata from EXIF/IPTC.
		$image_meta = wp_read_image_metadata( $file );
		if ( $image_meta )
			$metadata['image_meta'] = $image_meta;

		return $metadata;
	}

	/**
	 * Insert the file info to the DB and return the attachment ID.
	 *
	 * @param $post_id
	 * @param $url
	 * @param string $title
	 * @param string $caption
	 * @param string $order
	 * @param int $is_approved
	 * @param bool $is_placeholder If the images is a placeholder url and should not be auto imported.
	 *
	 * @return array|WP_Error
	 */
	public static function insert_attachment($post_id,$type = 'file',$url,$title = '', $caption = '', $order = '', $is_approved = 1,$is_placeholder = false){
		global $wpdb;

		// check we have what we need
		if(!$post_id || !$url){
			return new WP_Error( 'file_insert', __( "No post_id or file url, file insert failed.", "geodirectory" ) );
		}
		$metadata = '';
		if($is_placeholder){ // if a placeholder image, such as a image name that will be uploaded manually to the upload dir
			$upload_dir = wp_upload_dir();
			$file = $upload_dir['subdir'].'/'.basename($url);
			$file_type = wp_check_filetype( basename($url));
		}else{
			$post_type = get_post_type($post_id);
			// check for revisions
			if($post_type == 'revision'){
				$post_type = get_post_type(wp_get_post_parent_id($post_id));
			}
			$allowed_file_types = self::get_file_fields($post_type);
			$allowed_file_types = isset($allowed_file_types[$type]) ? $allowed_file_types[$type] : array( 'jpg','jpe','jpeg','gif','png','bmp','ico');

			if($order === 0 && $type=='post_images'){
				$attachment_id = media_sideload_image($url, $post_id, $title, 'id');
				// return error object if its an error
				if (!$attachment_id || is_wp_error( $attachment_id ) ) {
					return $attachment_id;
				}

				$metadata = wp_get_attachment_metadata( $attachment_id );
				$file_type = wp_check_filetype(basename($url));
				$file = array(
					'file'  => $metadata['file'],
					'type'  => $file_type['type']
				);

				// only set the featured image if its approved
				if($is_approved ){
					set_post_thumbnail($post_id, $attachment_id);
				}
			}else{
				// move the temp image to the uploads directory
				$file = self::get_external_media( $url, $title ,$allowed_file_types);
			}

			// return error object if its an error
			if ( is_wp_error($file  ) ) {
				return $file;
			}

			if(isset($file['type']) && $file['type']){
				if(self::is_image($file['type'])){
					// create the different image sizes and get the image meta data
					$metadata = self::create_image_sizes( $file['file'] );
				}elseif(in_array( $file['type'], wp_get_audio_extensions() )){// audio
					$metadata =  wp_read_audio_metadata($file['file']);
				}elseif(in_array( $file['type'], wp_get_video_extensions() )){// audio
					$metadata =  wp_read_video_metadata($file['file']);
				}
			}

			// if image meta fail then return error object
			if ( is_wp_error($metadata ) ) {
				return $metadata;
			}

			// pre slash the file path
			if(!empty($file['file'])){
				$file['file'] = "/"._wp_relative_upload_path($file['file']);
			}

			$file_type = $file['type'];
			$file = $file['file'];
		}

		$file_info = array(
			'post_id' => $post_id,
			'date_gmt'    => gmdate('Y-m-d H:i:s'),
			'user_id'   => get_current_user_id(),
			'title' => $title,
			'caption' => $caption,
			'file' => $file,
			'mime_type' => $file_type,
			'menu_order' => $order,
			'featured' => $order === 0 ? 1 : 0,
			'is_approved' => $is_approved,
			'metadata' => maybe_serialize($metadata),
			'type'  => $type
		);

		// insert into the DB
		$result = $wpdb->insert(
			GEODIR_ATTACHMENT_TABLE,
			$file_info,
			array(
				'%d',
				'%s',
				'%d',
				'%s',
				'%s',
				'%s',
				'%s',
				'%d',
				'%d',
				'%d',
				'%s',
				'%s'
			)
		);

		// if DB save failed then return error object
		if ( $result === false ) {
			return new WP_Error( 'file_insert', __( "Failed to insert file info to DB.", "geodirectory" ) );
		}

		$file_info['ID'] = $wpdb->insert_id;

		// return the file info
		return $file_info;

	}

	/**
	 * Insert the image info to the DB and return the attachment ID.
	 *
	 * @param $post_id
	 * @param $image_url
	 * @param string $image_title
	 * @param string $image_caption
	 * @param string $order
	 * @param int $is_approved
	 *
	 * @return array|WP_Error
	 */
	public static function update_texts($post_id,$type,$file_string='') {
		global $wpdb;

		$return = '';
		if(!empty($file_string)){

			if ( strpos( $file_string, '|' ) !== false ) {
				$image_info = explode( "|", $file_string );
			}else {
				$image_info[0] = $file_string;
			}

			//print_r($image_info);//exit;
			/*
			 * $image_info[0] = image_url;
			 * $image_info[1] = image_id;
			 * $image_info[2] = image_title;
			 * $image_info[3] = image_caption;
			 */
			$image_id      = ! empty( $image_info[1] ) ? absint( $image_info[1] ) : '';
			$image_title   = ! empty( $image_info[2] ) ? sanitize_text_field( $image_info[2] ) : '';
			$image_caption = ! empty( $image_info[3] ) ? sanitize_text_field( $image_info[3] ) : '';
			// insert into the DB
			$result = $wpdb->update(
				GEODIR_ATTACHMENT_TABLE,
				array(
					'title' => $image_title,
					'caption' => $image_caption,
				),
				array('ID' => $image_id,'type'=>$type,'post_id'=>$post_id),
				array(
					'%s',
					'%s',
				)
			);

			$return = $image_id;
		}else{// delete
			//delete_attachment($id, $post_id){
		}

		return $return;
	}

	/**
	 * Insert the image info to the DB and return the attachment ID.
	 *
	 * @param int $file_id
	 * @param int $post_id
	 * @param string $field
	 * @param string $file_url
	 * @param string $file_title
	 * @param string $file_caption
	 * @param string $order
	 * @param int $is_approved
	 *
	 * @return array|WP_Error
	 */
	public static function update_attachment($file_id, $post_id,$field,$file_url,$file_title = '', $file_caption = '', $order = '',$is_approved = '1'){
		global $wpdb;

		// check we have what we need
		if(!$file_id || !$post_id || !$file_url){
			return new WP_Error( 'image_insert', __( "No image_id, post_id or image url, image update failed.", "geodirectory" ) );
		}

		// if menu order is 0 then its featured and we need to set the post thumbnail
		if($order === 0 && $field=='post_images'){
			// Get the path to the upload directory.
			$wp_upload_dir = wp_upload_dir();
			$filename = $wp_upload_dir['basedir'] . $wpdb->get_var($wpdb->prepare("SELECT file FROM ".GEODIR_ATTACHMENT_TABLE." WHERE ID = %d",$file_id));
			$featured_img_url = get_the_post_thumbnail_url($post_id,'full');
			//echo $featured_img_url.'###'.$file_url;exit;
			if($featured_img_url != $file_url){
				$file = wp_check_filetype(basename($file_url));
				$attachment = array(
					'guid'           => $file_url,
					'post_mime_type' => $file['type'],
					'post_title'     => $file_title,
					'post_content'   => $file_caption,
					'post_status'    => 'inherit'
				);
				$attachment_id = wp_insert_attachment( $attachment, $filename, $post_id );

				// Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
				require_once( ABSPATH . 'wp-admin/includes/image.php' );

				// Generate the metadata for the attachment, and update the database record.
				$attach_data = wp_generate_attachment_metadata( $attachment_id , $filename );
				wp_update_attachment_metadata( $attachment_id , $attach_data );
				set_post_thumbnail($post_id, $attachment_id);
			}
		}

		// insert into the DB
		$result = $wpdb->update(
			GEODIR_ATTACHMENT_TABLE,
			array(
				'title' => $file_title,
				'caption' => $file_caption,
				'menu_order' => $order,
				'featured' => $order === 0 && $field=='post_images' ? 1 : 0,
				'is_approved' => $is_approved,
			),
			array('ID' => $file_id),
			array(
				'%s',
				'%s',
				'%d',
				'%d',
				'%d'
			)
		);


		// if DB save failed then return error object
		if ( $result === false ) {
			return new WP_Error( 'image_insert', __( "Failed to update image info to DB.", "geodirectory" ) );
		}


		// return the file path
		return $wpdb->get_row($wpdb->prepare("SELECT * FROM ".GEODIR_ATTACHMENT_TABLE." WHERE ID = %d",$file_id),ARRAY_A);

	}

	/**
	 * Get files via url.
	 *
	 * @param $url
	 * @param string $file_name
	 * @param array $allowed_file_types
	 *
	 * @return array|bool|mixed
	 */
	public static function get_external_media( $url, $file_name = '', $allowed_file_types = array('image/jpg', 'image/jpeg', 'image/gif', 'image/png') ) {
		// Gives us access to the download_url() and wp_handle_sideload() functions
		require_once( ABSPATH . 'wp-admin/includes/file.php' );

		// URL to the external image.
		$timeout_seconds = 5;

		// Download file to temp dir
		$temp_file = download_url( $url, $timeout_seconds );

		if ( ! is_wp_error( $temp_file ) ) {

			// make sure its an image
			$file_type = wp_check_filetype(basename($url));

			// Set an array containing a list of acceptable formats
			if(!empty($file_type['ext']) && !empty($file_type['type']) && (in_array($file_type['type'],$allowed_file_types) || in_array($file_type['ext'],$allowed_file_types))){}else{return false;}

			// Set the fiel name tot he title if it exists
			$_file_name = !empty($file_name) ? $file_name.".".$file_type['ext'] : basename( $url );

			// Array based on $_FILE as seen in PHP file uploads
			$file = array(
				'name'     => $_file_name, // ex: wp-header-logo.png
				'type'     => $file_type,
				'tmp_name' => $temp_file,
				'error'    => 0,
				'size'     => filesize( $temp_file ),
			);

			//print_r($file);

			$overrides = array(
				// Tells WordPress to not look for the POST form
				// fields that would normally be present as
				// we downloaded the file from a remote server, so there
				// will be no form fields
				// Default is true
				'test_form' => false,

				// Setting this to false lets WordPress allow empty files, not recommended
				// Default is true
				'test_size' => true,
			);

			// Move the temporary file into the uploads directory
			$results = wp_handle_sideload( $file, $overrides );

			// unlink the temp file
			@unlink($temp_file);

			if ( ! empty( $results['error'] ) ) {
				// Insert any error handling here
				return $results;
			} else {

//				$filename  = $results['file']; // Full path to the file
//				$local_url = $results['url'];  // URL to the file in the uploads dir
//				$type      = $results['type']; // MIME type of the file

				// Perform any actions here based in the above results

				return $results;
			}

		}else{
			return $temp_file; // WP-error
		}
	}


	/**
	 * Delete an attachment by id.
	 *
	 * @param $id
	 * @param $post_id
	 *
	 * @return bool|false|int
	 */
	public static function delete_attachment($id, $post_id){
		global $wpdb;
		$attachment = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . GEODIR_ATTACHMENT_TABLE . " WHERE id = %d AND post_id = %d", $id, $post_id));

		// check we have an attachment
		if(!$attachment){return false;}

		// unlink the image
		if(isset($attachment->file) && $attachment->file){
			$wp_upload_dir = wp_upload_dir();
			$file_path = $wp_upload_dir['basedir'] . $attachment->file;
			@wp_delete_file( $file_path );
		}

		// remove from DB
		$result = $wpdb->query($wpdb->prepare("DELETE FROM " . GEODIR_ATTACHMENT_TABLE . " WHERE id = %d AND post_id = %d", $id, $post_id));
		return $result;
	}

	/**
	 * Get attachments by type.
	 * 
	 * @param $post_id
	 * @param string $type
	 * @param string $limit
	 * @param string $revision_id
	 *
	 * @return array|null|object
	 */
	public static function get_attachments_by_type($post_id,$type = 'post_images',$limit = '',$revision_id =''){
		global $wpdb;
		$limit_sql = '';
		$sql_args = array();
		$sql_args[] = $type;
		$sql_args[] = $post_id;
		
		if($limit){
			$limit_sql = ' LIMIT %d ';
			$limit = absint($limit);
		}
		if($revision_id ){
			$sql_args[] = $revision_id;
			if($limit){$sql_args[] = $limit;}
			return $wpdb->get_results($wpdb->prepare("SELECT * FROM " . GEODIR_ATTACHMENT_TABLE . " WHERE type = %s AND post_id IN (%d,%d)  ORDER BY menu_order $limit_sql",$sql_args));
		}else{
			if($limit){$sql_args[] = $limit;}
			return $wpdb->get_results($wpdb->prepare("SELECT * FROM " . GEODIR_ATTACHMENT_TABLE . " WHERE type = %s AND post_id = %d ORDER BY menu_order $limit_sql",$sql_args));
		}
	}

	/**
	 * Get the post_images of the post.
	 *
	 * @param $post_id
	 * @param string $limit
	 * @param string $revision_id
	 *
	 * @return array|null|object
	 */
	public static function get_post_images($post_id,$limit = '',$revision_id = ''){
		return self::get_attachments_by_type($post_id,'post_images',$limit,$revision_id );
	}


	/**
	 * Get the edit string for files per field.
	 *
	 * @param $post_id
	 * @param $field
	 * @param string $revision_id
	 *
	 * @return string
	 */
	public static function get_field_edit_string($post_id,$field,$revision_id = ''){
		$files = self::get_attachments_by_type($post_id,$field,'',$revision_id );

		if(!empty($files)){
			$wp_upload_dir = wp_upload_dir();
			$files_arr = array();
			foreach( $files as $file ){
				if($file->menu_order=="-1"){return;}
				$is_approved = isset($file->is_approved) && $file->is_approved ? '' : '|0';
				if($file->menu_order=="-1"){$is_approved = "|-1";}
				$files_arr[] = $wp_upload_dir['baseurl'].$file->file."|".$file->ID."|".$file->title."|".$file->caption . $is_approved;
			}
			return implode(",",$files_arr);
		}else{
			return '';
		}
	}

	/**
	 * @param $post_id
	 *
	 * @return false|int
	 * @todo we need to remove the images from the folders.
	 */
	public static function delete_files($post_id,$field=''){
		global $wpdb;
		$result = '';
		if($field=='all'){
			$result = $wpdb->query($wpdb->prepare("DELETE FROM " . GEODIR_ATTACHMENT_TABLE . " WHERE post_id = %d", $post_id));
			delete_post_thumbnail( $post_id);
		}elseif($field){
			$result = $wpdb->query($wpdb->prepare("DELETE FROM " . GEODIR_ATTACHMENT_TABLE . " WHERE post_id = %d AND type = %s", $post_id,$field));
			delete_post_thumbnail( $post_id);
		}

		return $result;
	}

	/**
	 * Set a temp upload directory for post attachments before they are saved.
	 *
	 * @since 2.0.0
	 * @param array $upload Array of upload directory data with keys of 'path','url', 'subdir, 'basedir', and 'error'.
	 *
	 * @return array Returns upload directory details as an array.
	 */
	public static function temp_upload_dir( $upload ) {
		$upload['subdir'] = "/geodir_temp";
		$upload['path']   = $upload['basedir'] . $upload['subdir'];
		$upload['url']    = $upload['baseurl'] . $upload['subdir'];

		return $upload;
	}

	/**
	 * Update the revision images IDs to the parent post on save.
	 * 
	 * @param $post_id
	 * @param $revision_id
	 */
	public static function revision_to_parent($post_id,$revision_id){
		if(!empty($post_id) && !empty($revision_id)){
			global $wpdb;
			$result = $wpdb->update(
				GEODIR_ATTACHMENT_TABLE,
				array(
					'post_id' => $post_id
				),
				array('post_id' => $revision_id),
				array(
					'%d'
				)
			);
		}
	}
}