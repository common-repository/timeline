<?php
get_header();

?>
  <div class="site-main" id="main">
    <div class="main-content" id="main-content">
      <div class="content-area" id="primary">
        <div role="main" class="site-content" id="content">
          <article class="page type-page status-publish hentry">
            <div class="entry-content">
			<?php 
			while ( have_posts() ) : the_post();
				$event_year = get_post_meta( $post->ID, '_date' );
				$event_month = get_post_meta( $post->ID, '_month' );
				$monthName = date("F", mktime(0, 0, 0, $event_month['0'], 10));
				$featured = get_post_meta( $post->ID, '_thumbnail_id' );
				$video = get_post_meta( $post->ID, '_video' );
				$url = $video['0'];
					preg_match(
							'/[\\?\\&]v=([^\\?\\&]+)/',
							$url,
							$matches
						);
				$id = $matches[1];
				$width = '640';
				$height = '385';
				$timeline_text ='<div class="timeline-details">';
				$timeline_text .= '<h2>' . get_the_title($post->ID) . '</h2>';
				
				$options = get_option('timeline_options');
				$options_date_format = $options['date_format'];
				
				$timeline_text .= '<h3>' . display_format_date($options_date_format, $event_month['0'], $event_year['0']).'</h3>';
				
				if($video['0']!= '') {
					$timeline_text .='<div class="video">';
					$timeline_text .='<div class="videoWrapper">';
					$timeline_text .='<object width="' . $width . '" height="' . $height . '"><param name="movie" value="http://www.youtube.com/v/' . $id . '&amp;hl=en_US&amp;fs=1?rel=0"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/' . $id . '&amp;hl=en_US&amp;fs=1?rel=0" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="' . $width . '" height="' . $height . '"></embed></object>';
					$timeline_text .='</div>';
					$timeline_text .='</div>';
				}
				if(!empty($featured) && $featured[0]!= '') {
					$timeline_text .= '<div class="thumbnail">' . wp_get_attachment_image($featured[0],'large').'</div>';
				}
				$timeline_text .= '<div class="detail">' .get_the_content($post->ID) .'</div>';
				$timeline_text .= '<div class="clear"></div>';
				$timeline_text .='</div>';
				echo $timeline_text;
			endwhile;
			 ?> 
			</div>
          </article>
        </div>
      </div>
    </div>
  </div>
<?php
get_footer();
 ?>
