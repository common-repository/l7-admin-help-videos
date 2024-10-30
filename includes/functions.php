<?php

if ( ! defined( 'ABSPATH' ) ){
	exit;
}

/**
 *  Registers the video help settings.
 */
class AdminHelpVideoFunctions {

	/**
	 * Calls the hook admin_init to Register settings, add settings section 
	 * and add the settings field.
	 */
	public function __construct(){
		add_action( 'admin_init', array( $this, 'l7whv_init' ) );
		add_action( 'admin_head', array( $this, 'l7hv_custom_help' ) );
	}

	/**
	 * Register the settings, create settings section, add settings fields.
	 * @return void
	 */
	public function l7whv_init(){
		global $pagenow;
		register_setting( 'l7_admin_help_video_options', 'l7_admin_help_video_options' );
		add_settings_section( 'l7_admin_help_video_main', 'L7 Admin Help Videos', array( $this, 'l7whv_section_text' ), 'l7_admin_help_videos_settings' );
		add_settings_field( 'l7whv_upload_options',  '<i class="fa fa-upload"></i> Video Upload', array( $this, 'jsm_custom_login_logo_upload' ), 'l7_admin_help_videos_settings', 'l7_admin_help_video_main', array( 'label_for' => 'l7whv_upload_options' ) );
	}

	/**
	 * Add a help tab to the custom help video post.
	 * @return 
	 */
	public function l7hv_custom_help() {
		global $post_ID;
		$screen = get_current_screen();

		if ( isset( $_GET['post_type'] ) ) {
			$post_type = esc_html( $_GET['post_type'] );
		}
		else {
			$post_type = get_post_type( $post_ID );
		}

		if ( 'l7_help_video' == $post_type ) {
			$screen->add_help_tab( array(
			'id' => 'l7hv_custom_id', //unique id for the tab
			'title' => 'Custom Help', //unique visible title for the tab
			'content' => '<h3>Adding a help Video</h3><ul><li>Enter the title of your video.</li><li>Enter the description of the video in the text field.</li><li>Enter the url, upload a video, or enter the YouTube code in the Select Help Video section.<li>If you enter a url and a YouTube code, only the YouTube code will work.</li><li>Select the categories the video should be displayed in.</ul>', //actual help text
			));
		}
	}

	/**
	 * Get only the categories that contain the custom post type.
	 */
	public function wp_list_categories_for_post_type( $post_type, $args = '' ) {
		$include = array();
		$posts = get_terms( 'l7wvideo' );

		// Check ALL categories for posts of given post type
		foreach ( $posts as $tax ) {

			$include[] = $tax->name;
		}

		// return array custom taxonomy names
		return $include;
	}

	/**
	 * Explanations about the logo customizing section. Simple text description
	 * of the settings section jsm_custom_login_main
	 * @return html
	 */
	public function l7whv_section_text() {
		echo '<p>Upload or select help videos and documents here.</p>';
	}

	/**
	 * Returns the query results for a given taxonomy.  Used to get all the posts for a custom taxonomy group.
	 * It also can get the posts in a single term
	 * 
	 * @param  string  $taxonomy    [description]
	 * @param  string  $post_type   [description]
	 * @param  boolean $all_terms   [description]
	 * @param  string  $search_term [description]
	 * @return post array           [description]
	 */
	function l7whv_get_terms_query_result( $taxonomy, $post_type = '', $all_terms = false, $search_term = '' ){
		$args = [];
		if ( true == $all_terms ){
	
			/**
			 * Set up the arguments for the query selecting all of the 
			 * video posts.
			 * @var array
			 */
			$args = array(
				'post_type' => $post_type,
				'post_status' => 'publish',
				'posts_per_page' => 10,
				'caller_get_posts' => 1,
				);
		}

		/**
		 * If we only want the post for a given taxonomy
		 */
		else {

			/**
			 * Set up the arguments for the query. This selects all the videos for
			 * the taxonomy supplied.
			 * @var array
			 */
			$args = array(
				'post_type' => $post_type,
				'tax_query' => array(
					array(
						'taxonomy' 	=> 'l7wvideo',
						'field' 	=> 'slug',
						'terms'		=> array( $search_term ),
						), 
					),
				'post_status' => 'publish',
				'posts_per_page' => 10,
				'caller_get_posts' => 1,
			);
		}

		$my_query = null;
		$my_query = new WP_Query( $args );
		return $my_query;
	}
}
