<?php
/**
 * Plugin Name: l7 Admin Help Videos
 * Plugin URI: layer7web.com
 * Description: Provide help videos on the admin side to people using their Wordpress install.
 * Version: 1.1.1
 * Author: Jeffrey S. Mattson
 * Author URI: https://github.com/jeffreysmattson
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

/*
Copyright 2015 Jeffrey S. Mattson (email : plugin@layer7web.com)
This program is free software; you can redistribute it and/ or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.

See the GNU General Public License for more details. You should
have received a copy of the GNU General Public License along with this
program; if not, write to the Free Software Foundation, Inc.,
51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
*/

if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

if ( is_admin() ){
	include_once( plugin_dir_path( __FILE__ ) . 'includes/functions.php' );
	include_once( plugin_dir_path( __FILE__ ) . 'includes/options-page.php' );
}

if ( ! class_exists( 'AdminHelpVideos' )  ) {
	class AdminHelpVideos {

		/**
		 * Enqueue the scripts, add settings link to plugins page.
		 * Create custom post type for videos.
		 */
		public function __construct() {

			/**
			 * Activation hook with callback function to flush the rewrites to properly set up
			 * the custom post type.
			 */
			register_activation_hook( __FILE__, array( $this, 'l7hv_rewrite_flush' ) );

			/**
			 * Enqueue admin scripts.
			 */
			add_action( 'admin_enqueue_scripts', array( $this, 'jsm_custom_login_options_enqueue_scripts' ), 15 );

			/**
			 * Custom Post Type actions. Adds meta box for the video url and youtube video
			 * code. Hooks the save_custom_post function.
			 * Add custom taxonomies for help videos.
			 */
			add_action( 'init', array( $this, 'admin_help_video_cust_post' ) );
			add_action( 'add_meta_boxes', array( $this, 'admin_help_video_field' ) );
			add_action( 'save_post', array( $this, 'save_cust_post' ) );
			add_action( 'admin_menu', array( $this, 'remove_ex_box' ) );

			/**
			 * Register ajax to set up the filter by catecory option for the video display
			 */
			if ( is_admin() ) {
				add_action( 'wp_ajax_sort_video', array( $this, 'l7hv_sort_videos' ) );
			}

			/**
			 * Add the settings link hook
			 */
			$plugin = plugin_basename( __FILE__ );
			add_filter( "plugin_action_links_$plugin", array( $this, 'l7whv_settings_link' ) );
		}

		/**
		 * Takes an array of links for the plugin page and adds a new settings link to it
		 * It will display on the "installed plugins page"
		 * @param   $links 	Array of links for the plugins page.
		 * @return        	Returns the link array with the new link added.
		 */
		function l7whv_settings_link( $links ) {
			$url = explode( '/', plugin_basename( __FILE__ ) );
			$plugin_name = $url[0];
			$settings_link = '<a href="options-general.php?page=' . esc_attr( $plugin_name ) . '/includes/options-page">Settings</a>';
			array_unshift( $links, $settings_link );
			return $links;
		}

		/**
		 * Sort videos function. The function called by ajax when a category is selected from the 
		 * dropdown list.
		 * @return void
		 */
		public function l7hv_sort_videos(){		
			/**
			 * Include and create function object
			 */
			include_once( 'includes/functions.php' );
			if ( ! isset( $l7whv_function ) && class_exists( AdminHelpVideoFunctions ) ){
				$l7whv_function = new AdminHelpVideoFunctions;
			}
			include_once( 'templates/help-video-display.php' );
			wp_die();
		}

		/**
		 * Custom post type for the help videos.
		 * Also creates the custom taxonomy
		 * @return void 
		 */
		public function admin_help_video_cust_post() {
			register_post_type( 'l7_help_video',
				array(
					'labels' => array(
					'name' => 'Videos',
					'singular_name' => 'Video',
					'add_new_item' => 'Add Video',
					'all_items' => 'Edit Videos',
					'add_new'	=> 'Add Video',
					'edit_item' => 'Edit Video',
					'new_item' => 'New Video',
					'view_item' => 'View Video',
					'search_items' => 'Search Videos',
					'not_found'		=> 'No videos found',
					'not_found_in_trash' => 'No videos found in trash.',
				),
				'description' => 'Help videos created by admin for clients.',
				'public' => true,
				'has_archive' => true,
				'show_ui' => true,
				'show_in_menu' => 'help-videos',
				'supports'  => array(  'help_video_box', 'title', 'editor' ),
				'taxonomies' => array( 'l7wvideo' ),
				)
			);

			/**
			 * Create taxonomy for Help videos so they have there own 'Categories'
			 * These won't show up on any other categorie searches.  Just on the help video searches.
			 */
			$labels = array(
				'name'              => _x( 'Video Categories', 'taxonomy general name' ),
				'singular_name'     => _x( 'Video Category', 'taxonomy singular name' ),
				'search_items'      => __( 'Search Video Categories' ),
				'all_items'         => __( 'All Video Categories' ),
				'parent_item'       => __( 'Parent Video Category' ),
				'parent_item_colon' => __( 'Parent Video Category:' ),
				'edit_item'         => __( 'Edit Video Category' ),
				'update_item'       => __( 'Update Video Category' ),
				'add_new_item'      => __( 'Add New Video Category' ),
				'new_item_name'     => __( 'New Video Category Name' ),
				'menu_name'         => __( 'Video Categories' ),
			);

			$args = array(
				'hierarchical'      => true,
				'labels'            => $labels,
				'show_ui'           => true,
				'show_admin_column' => true,
				'query_var'         => true,
				'rewrite'           => array( 'slug' => 'l7wvideo' ),
			);

			register_taxonomy( 'l7wvideo', 'l7_help_video', $args );
			register_taxonomy_for_object_type( 'l7wvideo', 'l7_help_video' );
		}

		/**
		 * Meta box for help video data. Adds the meta box.
		 * @return none
		 */
		function admin_help_video_field() {
			add_meta_box(
				'help_video_box',
				'Add Video: Code/URL',
				array( $this, 'l7whv_box_content' ),
				'l7_help_video',
				'side',
				'high'
			);
		}

		/**
		 * Save the data from the custom post.
		 * @param  int $post_id the post id
		 * @return none
		 */
		function save_cust_post( $post_id ){

			// Check if our nonce is set.
			if ( ! isset( $_POST['l7whv_box_content_nonce'] ) ) {
				return $post_id;
			}

			$nonce = esc_html( $_POST['l7whv_box_content_nonce'] );

			// Verify that the nonce is valid.
			if ( ! wp_verify_nonce( $nonce, plugin_basename( __FILE__ ) ) ) {
				return $post_id;
			}

			/**
			 * If this is an autosave, our form has not been submitted,
			 * so we don't want to do anything.
			 */
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return $post_id;
			}

			// Check the user's permissions.
			if ( 'page' == $_POST['post_type'] ) {
				if ( ! current_user_can( 'edit_page', $post_id ) ){
					return $post_id;
				}
			} else {
				if ( ! current_user_can( 'edit_post', $post_id ) ){
					return $post_id;
				}
			}

			/* OK, its safe for us to save the data now. */

			// Sanitize the user input.
			$video_url = $_POST['help_video_post'];
			$youtube = $_POST['help_video_post_youtube'];
			$vimeo = $_POST['help_video_post_vimeo'];

			// Update the meta field.
			update_post_meta( $post_id, 'help_video_post', $video_url );
			update_post_meta( $post_id, 'help_video_post_youtube', $youtube );
			update_post_meta( $post_id, 'help_video_post_vimeo', $vimeo );
		}


		/**
		 * Content for the help video data meta box. Here is where the posts input form is created.
		 * @param  object $post all the details of the post in an object
		 * @return html   the contents of the meta box for display.
		 */
		public function l7whv_box_content( $post ) {
			wp_nonce_field( plugin_basename( __FILE__ ), 'l7whv_box_content_nonce' );

			// Use get_post_meta to retrieve an existing value from the database.
			$video_url = get_post_meta( $post->ID, 'help_video_post', true );
			$youtube = get_post_meta( $post->ID, 'help_video_post_youtube', true );
			$vimeo = get_post_meta( $post->ID, 'help_video_post_vimeo', true );

			// Start output buffering
			ob_start();
			?>

			<label for="help_video_url">Video URL</label><br />
			<input type="text" class="redux-opts-screenshot" id="_nectar_slider_image" name="help_video_post" placeholder="enter video url" value="<?php echo esc_attr( $video_url ) ?>"/>
			<button class='help-media-upload button-secondary' rel-id='_nectar_slider_image' type='button'>Upload</button>
			<button class='help-media-upload-media-remove button-secondary' rel-id='_nectar_slider_image' type='button' style='display:none;'>Remove</button><br /><br />
			<label for="help_video_post_youtube">YouTube Video Code</label><br />
			<input type="text" id="l7hv-youtube-code" name="help_video_post_youtube" placeholder="1VQJ5_NyDDs" value="<?php echo esc_attr( $youtube ) ?>"/><br /><br />
			<label for="help_video_post_vimeo">Vimeo Video Code</label><br />
			<input type="text" id="l7hv-vimeo-code" name="help_video_post_vimeo" placeholder="109104362" value="<?php echo esc_attr( $vimeo ) ?>"/>
			
			<?php
			$content = ob_get_contents();
			ob_end_clean();
			echo $content;
		}

		/**
		 * Remove the unwanted meta boxes on the custom post type edit page.
		 * @return none
		 */
		public function remove_ex_box(){
			remove_meta_box( 'authordiv','l7_help_video','normal' ); // Author Metabox
			remove_meta_box( 'commentstatusdiv','l7_help_video','normal' ); // Comments Status Metabox
			remove_meta_box( 'commentsdiv','l7_help_video','normal' ); // Comments Metabox
			remove_meta_box( 'postcustom','l7_help_video','normal' ); // Custom Fields Metabox
			remove_meta_box( 'postexcerpt','l7_help_video','normal' ); // Excerpt Metabox
			remove_meta_box( 'revisionsdiv','l7_help_video','normal' ); // Revisions Metabox
			remove_meta_box( 'slugdiv','l7_help_video','normal' ); // Slug Metabox
			remove_meta_box( 'trackbacksdiv','l7_help_video','normal' ); // Trackback Metabox
		}

		/**
		 * To flush the permalinks for the Custom Post on activation.
		 * @return [type] [description]
		 */
		public function l7hv_rewrite_flush() {
			$this->admin_help_video_cust_post();
			flush_rewrite_rules();
		}

		/**
		 * Enqueue the scripts neccessary for the admin side. 
		 * Called by the admin_enqueue_scripts hook.
		 * @return void
		 */
		public function jsm_custom_login_options_enqueue_scripts() {

			/**
			 * Register the neccesary files for Bootstrap. Enqueue them only on the 
			 * help-videos admin page.
			 */
			wp_register_script( 'l7whv-bootstrap-js', plugins_url( 'assets/js/bootstrap.min.js', __FILE__ ), array('jquery') );
			wp_register_style( 'l7whv-bootstrap-css' , plugins_url( 'assets/css/bootstrap.css', __FILE__ ) );

			/**
			 * Register the main.js file.
			 */
			wp_register_script( 'l7whv-main-js', plugins_url( 'assets/js/main.js', __FILE__ ), array('jquery') );

			/**
			 * For Font Awesome, Bootstrap on Settings page
			 */
			wp_register_style( 'l7whv-main-css' , plugins_url( 'assets/css/main.css', __FILE__ ) );

			/**
			 * Identify the page we are on and save it so we can use it to 
			 * determin whether we are on the display videos admin page. If we are we register
			 * the scripts below. Otherwise don't because it conflicts with the custom post page
			 * display and may conflict with other plugins.  We also use this to equeue scripts
			 * only on the pages that they are necessary.
			 */
			if ( strpos( $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], '=' ) ){
				$url_array = [];
				$url_array = explode( '=', $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
				$admin_page = $url_array[1];
			}
			else {
				$admin_page = '';
			}

			if ( 'help-videos' == $admin_page ) {
				wp_enqueue_script( 'l7whv-bootstrap-js' );
				wp_enqueue_style( 'l7whv-bootstrap-css' );
				wp_enqueue_style( 'l7whv-main-css' );
				wp_enqueue_script( 'l7whv-main-js' );
			}

			/**
			 * Enqueue bootstrap on the settings page only.
			 */
 			if ( false !== strpos( $admin_page, 'l7-admin-help-videos' ) ){
				wp_enqueue_script( 'l7whv-bootstrap-js' );
				wp_enqueue_style( 'l7whv-bootstrap-css' );
				wp_enqueue_style( 'l7whv-main-css' );
				wp_enqueue_script( 'l7whv-main-js' );
			}

			/**
			 * Upload Popup. Js that Displays the popup for video upload or choosing.
			 */
			wp_register_script( 'l7whv-upload-js', plugins_url( 'assets/js/l7whv-upload.js', __FILE__ ), array('jquery') );

			/**
			 * Only enqueue script on the post page.
			 */
			if ( 'l7_help_video' == $admin_page ) {
				wp_enqueue_script( 'l7whv-upload-js' );
			}
		}
	}
}

// Initialize Plugin Object
if ( class_exists( 'AdminHelpVideos' ) && ! isset( $admin_help_videos ) ) {
	$admin_help_videos = new AdminHelpVideos();
}

// Create Settings Page
if ( class_exists( 'AdminHelpSettings' ) && ! isset( $admin_help_settings ) ) {
	$admin_help_settings = new AdminHelpSettings();
}

// Create the Settings Function Object
if ( class_exists( 'AdminHelpVideoFunctions' ) && ! isset( $admin_help_video_functions ) ) {
	$admin_help_video_functions = new AdminHelpVideoFunctions();
}

