jQuery(document).ready(function($) {

	/**
	 * Ajax on selection of the category. Display videos in that category
	 * and update the category header with the catagory dispayed.
	 */
	$( document ).on('click', '.dropdown-menu li a', function(e) {
		var cat = $( this ).text();
		var data = {
			'action': 'sort_video',
			'category': cat
		};
		jQuery.post(ajaxurl, data, function(response) {
			$("#video-display-js").html(response);
			$(".cur-cat-js").html("<em>Displaying: " + cat + "</em>");
		});
	});
});