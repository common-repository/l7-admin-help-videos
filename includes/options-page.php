<?php
if ( ! defined( 'ABSPATH' ) ){
	exit;
}

/**
 * Display the settings form on the settings page.
 */
class AdminHelpSettings {
	private $admin_help_video_functions;
	/**
	 * Hooks into admin_menu to add_options_page for the settings options
	 * for customizing the login screen
	 */
	public function __construct() {
		include_once( 'functions.php' );
		if ( ! isset( $this->admin_help_video_functions) ){ 
			$this->admin_help_video_functions = new AdminHelpVideoFunctions;
		}

		add_action( 'admin_menu', array( $this, 'jsm_generic_admin_settings_menu_item' ), 9 );

		/**
		 * Register the settings for the settings page hook.
		 */
		add_action( 'admin_init', array( $this, 'l7whv_register_settings' ) );
	}

	/**
	 * The add_options_page() called by the add_action() hook in the constructor. Registers the jsm_custom_login_settings function
	 * to display settings.
	 * @return void
	 */
	public function jsm_generic_admin_settings_menu_item(){
		$url = explode( '/', plugin_basename( __FILE__ ) );
		$plugin_name = $url[0];
		$plugin_page_url = 'help-videos';
		if ( true == $this->user_role_view_control() ){
			add_menu_page( 'l7 Help Videos', 'Help', 'read' , $plugin_page_url , array( $this, 'help_videos_settings_page' ), '', 1 );
			add_submenu_page( $plugin_page_url, 'View Videos', 'View Videos', 'read', 'admin.php?page=help-videos' );
			add_submenu_page( $plugin_page_url, 'Add Video', 'Add Video', 'activate_plugins', 'post-new.php?post_type=l7_help_video' );
			remove_submenu_page( $plugin_page_url, $plugin_page_url );
		}

		/**
		 * Add the settings page.  But put the setting option in the Help main menu
		 */
		$url = explode( '/', plugin_basename( __FILE__ ) );
		$plugin_name = $url[0];
		$plugin_page_url = $plugin_name . '/includes/options-page';
		add_options_page( 'Help Video Settings', 'Help Video Settings', 'manage_options' , $plugin_page_url , array( $this, 'l7whv_help_video_settings' ), '' );
	}

	/**
	 * Register the settings
	 */
	public function l7whv_register_settings(){
		register_setting( 'l7whv_help_video_options', 'l7whv_help_video_options', array( $this, 'l7whv_video_sanitization' ) );

		/**
		 * Header Settings
		 */
		add_settings_section( 'l7whv_header_options', 'Header Settings', array( $this, 'l7whv_header_settings_desc' ), 'l7whv_header_options_group' );
		add_settings_field( 'l7whv_header_title',  'Page Title', array( $this, 'l7whv_help_video_title' ), 'l7whv_header_options_group', 'l7whv_header_options', array( 'label_for' => 'header_title' ) );

		/**
		 * Users who can view the help videos.
		 * Permissions settings.
		 */
		add_settings_section( 'l7whv_user_permissions', 'User View Permissions', array( $this, 'l7whv_header_user_roles' ), 'l7whv_user_roles_group' );
		add_settings_field( 'l7whv_user_roles',  'Users who can view videos', array( $this, 'l7whv_user_roles' ), 'l7whv_user_roles_group', 'l7whv_user_permissions', array( 'label_for' => 'user_roles' ) );
	}

	/**
	 * Default settings function
	 */
	public function l7hv_default_settings( $key ) {
		$options = get_option( 'l7whv_help_video_options' );

		$defaults = array(
			'header_title'     	=> 'Instructional Videos',
			'editor'			=> '1',
			'author'			=> '1',
			'contributor'		=> '1',
			'subscriber'		=> '1',
		);

		$options_defaults = wp_parse_args( $options, $defaults );

		if ( isset( $options_defaults[$key] ) ) {
			return $options_defaults[$key];
		}
		else {
			return false;
		}
	}

	/**
	 * Field for the title.
	 */
	public function l7whv_help_video_title(){
		$output = "<div class='input-group'><span class='input-group-addon'><i></i></span><input id='header_title' name='l7whv_help_video_options[header_title]' type='text' value='" . esc_attr( $this->l7hv_default_settings( 'header_title' ) ) . "' class='form-control' style='width:150%;'/></div>";
		echo $output;
	}

	/**
	 * Fields for user roles.
	 * @return [type] [description]
	 */
	public function l7whv_user_roles(){
		$options = get_option( 'l7whv_help_video_options' );

		$editor = $this->l7hv_default_settings( 'editor' );
		if ( '1' == $editor ){
			$checked_editor = 'checked';
		}

		$author = $this->l7hv_default_settings( 'author' );
		if ( '1' == $author ){
			$checked_author = 'checked';
		}

		$contributor = $this->l7hv_default_settings( 'contributor' );
		if ( '1' == $contributor ){
			$checked_cont = 'checked';
		}

		$subscriber = $this->l7hv_default_settings( 'subscriber' );
		if ( '1' == $subscriber ){
			$checked_sub = 'checked';
		}
		$output = "<label class='checkbox-inline'><input type='checkbox' value='1' name='l7whv_help_video_options[subscriber]'" . $checked_sub . '>Subscriber</label>';
		$output .= "<label class='checkbox-inline'><input type='checkbox' value='1' name='l7whv_help_video_options[contributor]'" . $checked_cont . '>Contributor</label>';
		$output .= "<label class='checkbox-inline'><input type='checkbox' value='1' name='l7whv_help_video_options[author]'" . $checked_author . '>Author</label>';
		$output .= "<label class='checkbox-inline'><input type='checkbox' value='1' name='l7whv_help_video_options[editor]'" . $checked_editor . '>Editor</label>';
		echo $output;
	}

	/**
	 * Description for the header settings section
	 */
	public function l7whv_header_settings_desc(){
		echo 'Settings for the top of the video page.';
	}

	/**
	 * Description for the permission settings section
	 */
	public function l7whv_header_user_roles(){
		echo 'Set the role of user who can view the videos.';
	}

	/**
	 * Sanitisation function for the setting fields.
	 * @param  array $input array of the settings fields.
	 * @return [type]        [description]
	 */
	public function l7whv_video_sanitization( $input ){
		$arg_array['header_title'] = esc_html( $input['header_title'] );
		$arg_array['editor'] = esc_html( $input['editor'] );
		$arg_array['author'] = esc_html( $input['author'] );
		$arg_array['contributor'] = esc_html( $input['contributor'] );
		$arg_array['subscriber'] = esc_html( $input['subscriber'] );
		return $arg_array;
	}

	/**
	 * Displays the settings for the setting page.  Called by the add_options_page
	 */
	public function l7whv_help_video_settings(){
		$content = '';
		ob_start();
		?>
		<div class="wrap">
			<div class="row">
				<div class="col-md-8">
					<form action="options.php" method="post" >
					<?php
						settings_fields( 'l7whv_help_video_options' );
						do_settings_sections( 'l7whv_header_options_group' );
						do_settings_sections( 'l7whv_user_roles_group' );
					?>
						<input name="Submit" class="button button-primary" type="submit" value="Save Changes" />
					</form>
				</div>
			</div>
			<div class="row">
			 	<div class="col-md-12" style="text-align:center;">
				 Comments? - <a href="https://layer7web.com">Layer 7 Web</a>
				</div>
			</div>
		</div>
		<?php add_thickbox();
		$content .= ob_get_contents();
		ob_end_clean();
		echo $content;
	}

	/**
	 * Displays the page that shows the videos.
	 * @return html
	 */
	public function help_videos_settings_page(){
		ob_start();
		?>
		<div class="wrap">
			<div class="row" style="border-bottom:1px solid #999;">
				<div class="col-md-8 text-left">
					<h3 style="display:inline-block;"><?php echo esc_html( $this->l7hv_default_settings( 'header_title' ) ); ?></h3>
				</div>
				<div class="col-md-4 text-right">
				</div>
			</div>
			<div class="row">
				<div class="col-md-1">
					<div class="dropdown help-header">
					  <button class="btn btn-default btn-xs dropdown-toggle" type="button" id="dropdownMenu1" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true">
					    Category
					    <span class="caret"></span>
					  </button>
					  <ul class="dropdown-menu" aria-labelledby="dropdownMenu1">
					  	<?php

						$categories = array();
						$categories = $this->admin_help_video_functions->wp_list_categories_for_post_type( 'l7_help_video' );
						echo "<li><a href='#'>All</a></li>";
						foreach ( $categories as $category ) {
							echo "<li><a href='#'>" . esc_attr( $category ) . '</a></li>';
						}
						?>
					  </ul>
					</div>
				</div>
				<div class="col-md-4 text-left cur-cat-js">
					Displaying: All
				</div>
			</div>
			<hr>
			<div id="video-display-js" class="row">
				
				<?php
				$my_query2 = null;
				$my_query2 = $this->admin_help_video_functions->l7whv_get_terms_query_result( 'l7wvideo', 'l7_help_video', true );

				if ( $my_query2->have_posts() ) {
					?>
					<div class="row">
				    	<div class="col-md-8">
				    <?php
				  	while ( $my_query2->have_posts() ) : $my_query2->the_post(); ?>
					    <div class="row">	
					    	<div class="col-md-4">
							    <?php global $post;
									$meta_youtube = get_post_meta( $post->ID,'help_video_post_youtube', true );
									$meta_vimeo = get_post_meta( $post->ID,'help_video_post_vimeo', true );
									$meta_url = get_post_meta( $post->ID,'help_video_post', true );

									// If the video url is entered and not youtube then use it.
									if ( '' != $meta_url  &&  '' == $meta_youtube ) { ?>
											<div class="video-div-js" style="text-align:center;">
												<video width="200" controls>
												  <source src="<?php echo esc_attr( $meta_url ); ?>" type="video/mov">
												  <source src="<?php echo esc_attr( $meta_url ); ?>" type="video/ogg">
												  Your browser does not support HTML5 video.
												</video>
											</div>
									<?php
									}

									// If youtube is entered use the youtube embeded url
									if ( '' != $meta_youtube && '' == $meta_url ) {
										?>
											<div class="video-div-js" style="text-align:center;">
												<iframe width='200' src=' <?php echo 'https://www.youtube.com/embed/' . esc_attr( $meta_youtube ) ?> '></iframe>	
											</div>
										<?php
									}

									// If vimeo is entered use the vimeo embeded url
									if ( '' != $meta_vimeo && ( '' == $meta_url && '' == $meta_youtube ) ) {
										?>
											<div class="video-div-js" style="text-align:center;">
												<iframe src="https://player.vimeo.com/video/<?php echo esc_attr( $meta_vimeo ); ?>" width="200" frameborder="0" title="Vimeo Player" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>
											</div>
										<?php
									}

									// If both video types are entered use the youtube one
									if ( '' != $meta_youtube && '' != $meta_url ) {
							 			?>
											<div class="video-div-js" style="text-align:center;">
												<iframe width='200' src=' <?php echo 'https://www.youtube.com/embed/' . esc_attr( $meta_youtube ) ?> '></iframe>	
											</div>
										<?php
									}
							?>
							</div>
							<div class="col-md-8 text-left">
									<?php

									// If it is youtube than use the youtube embeded url, otherwise use the full variable url
									if ( '' != $meta_youtube && '' == $meta_url ){
										?>
										<a href="<?php echo 'https://www.youtube.com/embed/' . esc_attr( $meta_youtube ) ?>" target="_blank"><h4 style="margin-bottom:2px;"><?php the_title(); ?></h4></a>
										<?php
									}
									// If it is vimeo and the url is empty and youtube is empty
									elseif ( '' != $meta_vimeo && ( '' == $meta_url && '' == $meta_url ) ){
										?>
										<a href="https://vimeo.com/<?php echo esc_attr( $meta_vimeo ); ?>" target="_blank"><h4 style="margin-bottom:2px;"><?php the_title(); ?></h4></a>
										<?php
									}
									else {
										?>
										<a href="<?php echo esc_attr( $meta_url ); ?>" target="_blank"><h4 style="margin-bottom:2px;"><?php the_title(); ?></h4></a>
										<?php
									}
									?>
									<p><small>Added by: <?php the_author(); ?></small><br />
									<small>On: <?php echo get_the_date(); ?></small></p>
									<?php the_content(); ?>
							</div>
						</div>
						<?php 
				  endwhile;
				  ?>
				  		</div>
						<div class="col-md-4">
						</div>
					</div>
				<?php
				}
				else {
					?>
					<div class="row">
						<div class="col-md-8 text-center">
							<h5>Sorry, there are no videos to show.</h5>
						</div>
					</div>
					<?php
				}
				wp_reset_query();  // Restore global post data stomped by the_post().
			?>
		</div>
		<?php
		$content = ob_get_contents();
		ob_end_clean();
		echo $content;
	}
	/**
	 * Users should only see the help menu videos when they have the proper role.
	 * @return [type] [description]
	 */
	private function user_role_view_control(){
		$options = get_option( 'l7whv_help_video_options' );
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		if ( ( '1' == $options['editor'] ) &&  ( ( current_user_can( 'moderate_comments' ) &&  ( ! current_user_can( 'manage_options' ) ) ) ) ) {
			 return true;
		}

		if ( ( '1' == $options['author'] ) && ( ( current_user_can( 'edit_published_posts' ) && ( ! current_user_can( 'moderate_comments' ) ) ) ) ) {
			return true;
		}

		if ( ( '1' == $options['contributor'] ) && ( ( current_user_can( 'edit_posts' ) && ( ! current_user_can( 'edit_published_posts' ) ) ) ) ) {
			return true;
		}

		if ( ( '1' == $options['subscriber'] ) && ( ( current_user_can( 'read' ) && ( ! current_user_can( 'edit_posts' ) ) ) ) ) {
			return true;
		}
	}
}