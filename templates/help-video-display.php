<?php
/**
 * This page is called by wp-ajax to show the videos selected.
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Video display. This will be called by ajax and will display the catagory selected.
 * This is included in the l7-admin-help-videos.php in the l7hv_sort_videos funstions.
 */
$category = sanitize_text_field( $_POST['category'] );
$my_query = [];


/**
 * If the post is 'All' then set the query to include all
 * the custom taxonomies.  This returns all the categories.
 */
if ( 'All' == $category ){

	/**
	 * Get all the posts in the custom taxonomy l7wvideo
	 */
	$my_query = $l7whv_function->l7whv_get_terms_query_result( 'l7wvideo', 'l7_help_video', true, $category );
}
else {
	
	/**
	 * Get all the posts in the custom $category
	 */
	$my_query = $l7whv_function->l7whv_get_terms_query_result( 'l7wvideo', 'l7_help_video', false, $category );
}

/**
 * Begin the loop to display videos.
 * Could there be a better way?
 */
ob_start();
if ( $my_query->have_posts() ) {
	?>
	<div class="row">
		<div class="col-md-8">
<?php
		while ( $my_query->have_posts() ) : $my_query->the_post(); ?>
    		<div class="row">	
    			<div class="col-md-4">
		    <?php global $post;
				$meta_youtube = get_post_meta( $post->ID,'help_video_post_youtube', true );
				$meta_vimeo = get_post_meta( $post->ID,'help_video_post_vimeo', true );
				$meta_url = get_post_meta( $post->ID,'help_video_post', true );

				// If the video url is entered and not youtube then use it.
			if ( '' != $meta_url  &&  '' == $meta_youtube ) {
				?>
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
					<a href="<?php echo esc_attr( $meta_url ) ?>" target="_blank"><h4 style="margin-bottom:2px;"><?php the_title(); ?></h4></a>
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
$content .= ob_get_contents();
ob_end_clean();
echo $content;
?>