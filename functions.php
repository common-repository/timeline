<?php
class dstimeline {
	function setDisplayDateFormat($format,$month,$year){
		$formatedDate = '';
		switch($format) {
			case 'YYYY - M':
				$formatedDate = $year.' - '.$month;
				break;
			case 'M - YYYY':
				$formatedDate = $month.' - '.$year;
				break;
			case 'YYYY / M':
				$formatedDate = $year.' / '.$month;
				break;
			case 'M / YYYY':
				$formatedDate = $month.' / '.$year;
				break;
			case 'YYYY - Month':
				$monthName = date("F", mktime(0, 0, 0, $month, 10));
				$formatedDate = $year.' - '.$monthName;
				break;
			case 'Month - YYYY':
				$monthName = date("F", mktime(0, 0, 0, $month, 10));
				$formatedDate = $monthName.' - '.$year;
				break;
		}
		return $formatedDate;
	}
	
	function showShortCode($atts){
		
		global $wpdb;
		extract(shortcode_atts(array('show_year' => 1, 'filter_type' => 'not-filtered'), $atts));
		$filter_type == 'not-filtered' ? $filter = '' : ($filter_type == '' ? $filter = " AND ts.slug IS NULL" : $filter = " AND ts.slug='".$filter_type."'");
		$options = get_option('timeline_options');
		$options_start_year = $options['start_year'];
		
		$query = "SELECT p.ID, p.post_title AS event_name, p.post_content AS event_description, pm.meta_value
					FROM ".$wpdb->prefix."posts p 
					INNER JOIN ".$wpdb->prefix."postmeta pm
					ON p.ID = pm.post_id
					WHERE p.post_type = 'timeline' AND pm.meta_key = '_date'
					ORDER BY pm.meta_value ASC LIMIT 0,1";
		
		$all_events = $wpdb->get_results($query);
		if (!empty($all_events)) {
			$start_year = $all_events['0']->meta_value;
		}
				
		$query_end = "
				SELECT p.ID, p.post_title AS event_name, p.post_content AS event_description, pm.meta_value
					FROM ".$wpdb->prefix."posts p 
					INNER JOIN ".$wpdb->prefix."postmeta pm
					ON p.ID = pm.post_id
					WHERE p.post_type = 'timeline' AND pm.meta_key = '_date'
					ORDER BY pm.meta_value DESC LIMIT 0,1";
		$all_the_events = $wpdb->get_results($query_end);
		
		
		if (!empty($all_the_events)) {
			$end_year = $all_the_events['0']->meta_value;
		}
		
		if ($options_start_year == '') {
			$start_year = $start_year;
		} else if($options_start_year > $start_year && $options_start_year < $end_year) {
			$start_year = $options_start_year;
		} else if($options_start_year == $end_year) {
			$start_year = $options_start_year;
		} else if($options_start_year > $start_year) {
			$start_year = $start_year;
		} else if($options_start_year < $start_year) {
			$start_year = $start_year;
		} else {
			$start_year = $options_start_year;
		}
	
		if($start_year > $end_year) {
			
			$timeline_text = "No events present.";
		
		} else {
			$range = 5;
			if($start_year!= '' && $end_year!= '') {
				$diff = $end_year - $start_year;
				if($range > $diff) {
					$range = $diff;
				}
				$years = range($start_year, $end_year, $range);
			}
			
			if(!empty($years)) {
				$last = end($years);
				
				if ($last < $end_year) {
					array_push($years, $end_year);
				}
				
				if($start_year == $end_year) {
					$recent = $start_year;
				} else {
					$recent = $end_year;
				}
				end($years);
				$last_second = prev($years);
				
				/* if last year is already in range of previous year */
				if($recent < ($last_second + 4) || $recent == ($last_second + 4)) {
					$recent = '';
				}
				 
				$timeline_text = '';
				
				$count = 0;
				$timeline_text .= '<div class="tdih_list_wrap">';
				$timeline_text .= '<div class="tdih_list_head">';
				$timeline_text .= '<ul class="tdih-scroller">';
				if(!empty($years)) {
					foreach($years as $key => $year) {
						if(count($years)-1 == $key){
							if($recent!= '') {
								$timeline_text .= '<li class="tdih_list_head_item" data-target-id="'.$year.'"><a href="javascript:void(0);">'.$recent.'</a></li>';
							}
							
						}else{
							$timeline_text .= '<li class="tdih_list_head_item" data-target-id="'.$year.'"><a href="javascript:void(0);">'.$year.'</a></li>';
						}
					}	
				}
				$timeline_text .= '</ul>';
				$timeline_text .= '</div>';
				$timeline_text .= '<div class="tdih-scroller-content">';
				$timeline_text .= '<div class="tdih_list_content">';
				
				$blockColor = explode(',',str_replace(" ","",$options['bg_color']));
				//echo "<pre>";print_r($blockColor);exit;
				
					foreach($years as $key => $year) {
						
						$next_year = $year+ 4;
						
						$query = "
						SELECT p.ID, p.post_title AS event_name, p.post_content AS event_description, pm.*
						FROM ".$wpdb->prefix."posts p 
						INNER JOIN ".$wpdb->prefix."postmeta pm
						ON p.ID = pm.post_id
						WHERE p.post_type = 'timeline' AND pm.meta_key = '_date' 
						AND  pm.meta_value 
						BETWEEN ".$year." AND ".$next_year." 
						ORDER BY pm.meta_value ASC";
						
						$events = $wpdb->get_results($query);
						$event_type = '';
						if (!empty($events)) {
							if($count == 0){
								$timeline_text .= '<div class="year" id="'.$year.'" style="background-color:'.$blockColor[$count].'; width:451px;">';
							} else {
								$timeline_text .= '<div class="year" id="'.$year.'" style="background-color:'.$blockColor[$count].'; width:451px;">';
							}
							$timeline_text .= '<ul class="tdih_list">';
							foreach ($events as $e => $values) {
								
								$timeline_text .= '<li class="'.($e%2==0?'even':'odd').' tdih_item_'.$e.'" style="border-bottom:1px dotted #79838A; ">';
								$timeline_text .= '<div class="textwrap">';
								$featured = get_post_meta($events[$e]->ID, '_thumbnail_id');
								$imgPath = wp_get_attachment_image($featured[0],'large');
								$imgSrc = preg_replace('/<img [^>]*src=[\'"]([^\'"]+)[\'"][^>]*>/','\\1',$imgPath);
								
								$thumbImgPath = wp_get_attachment_image($featured[0],array(25,25),'1',$attr);
								$thumbImgSrc = preg_replace('/<img [^>]*src=[\'"]([^\'"]+)[\'"][^>]*>/','\\1',$thumbImgPath);
								
								$video = get_post_meta($events[$e]->ID, '_video');
								$event_year = $events[$e]->meta_value;
								
								/* settings for date format to display */
								$options_date_format = $options['date_format'];
								
								/* settings for enable events link */
								$timeline_detail = $options['timeline_detail'];
								
								$event_month = get_post_meta( $events[$e]->ID, '_month', true );
								
								/* format date to display */
								$formattedDate = display_format_date($options_date_format, $event_month, $event_year );
								
								$timeline_text .= '<div class="ttime">';
								$timeline_text .= '<span class="tdih_year">'.$formattedDate.'</span>  ';
								
								$timeline_text .= '</div>';
								$timeline_text .= '<div class="ttext">';
								
								/* settings for enable events link */
								if($timeline_detail == 'Yes') {
									$timeline_text .= '<span class="tdih_title"><a href="'.get_permalink( $events[$e]->ID ).'">' . $events[$e]->event_name.'</a></span>';
								} else {
									$timeline_text .= '<span class="tdih_title">' . $events[$e]->event_name.'</span>';
								}
								
								$timeline_text .= '<br>
								<style>
								.gallery{padding:0px; margin:0px;}
								.gallery li a img{padding-top:7px;}
								</style>
								<ul class="gallery clearfix"></ul>
								<ul class="gallery clearfix">';
								if($imgSrc!= '') {
									$timeline_text .= '<li>
											<a href="'.$imgSrc.'" rel="prettyPhoto">
												<img src="'.$thumbImgSrc.'" width="40" height="40" alt="" title="View Image">
											</a>
										</li>';
								}
								if($video['0']!= '') {
									$timeline_text .= '	<li>
											<a href="'.$video['0'].'" rel="prettyPhoto">
												<img src="'.plugins_url('timeline/images/PlayIcon.png').'" width="40" height="40" alt="" title="View Video">
											</a>
										</li>';
								}
								$timeline_text .= '</ul>';
								
								$timeline_text .= '</div>';
								$timeline_text .= '</li>';
							}
							$timeline_text .= '</ul>';
							
							$timeline_text .= '</div>';
						} else {
							$timeline_text .= '<div class="year" id="'.$year.'">';
							$timeline_text .= '<ul class="tdih_list">';
							$timeline_text .= "<li class='even tdih_item_0'><div class='textwrap-empty'>No Record found!</div></li>";
							$timeline_text .= '</ul>';
							$timeline_text .= '</div>';
						}
						$count++;
					}
				
				$timeline_text .= '</div>';
				$timeline_text .= '</div>';
				$timeline_text .= '<a href="javascript:void(0);" class="tdih-prev"></a>';
				$timeline_text .= '<a href="javascript:void(0);" class="tdih-next"></a>';
				$timeline_text .= '</div>';
				
				$timeline_text .= '
				<script type="text/javascript" charset="utf-8">
				jQuery.noConflict();
				jQuery(document).ready(function(){
					jQuery("area[rel^=\'prettyPhoto\']").prettyPhoto();
					
					jQuery(".gallery:first a[rel^=\'prettyPhoto\']").prettyPhoto({animation_speed:\'normal\',theme:\'light_square\',slideshow:3000, autoplay_slideshow: true});
					jQuery(".gallery:gt(0) a[rel^=\'prettyPhoto\']").prettyPhoto({animation_speed:\'fast\',slideshow:10000, hideflash: true});
			
					jQuery("#custom_content a[rel^=\'prettyPhoto\']:first").prettyPhoto({
						custom_markup: \'<div id="map_canvas" style="width:260px; height:265px"></div>\',
						changepicturecallback: function(){ initialize(); }
					});
	
					jQuery("#custom_content a[rel^=\'prettyPhoto\']:last").prettyPhoto({
						custom_markup: \'<div id="bsap_1259344" class="bsarocks bsap_d49a0984d0f377271ccbf01a33f2b6d6"></div><div id="bsap_1237859" class="bsarocks bsap_d49a0984d0f377271ccbf01a33f2b6d6" style="height:260px"></div><div id="bsap_1251710" class="bsarocks bsap_d49a0984d0f377271ccbf01a33f2b6d6"></div>\',
						changepicturecallback: function(){ _bsap.exec(); }
					});
				});
				</script>';
				
			} else {
				$timeline_text = "No events present in timeline.";
			}
		}
	
		return $timeline_text;
	}
	
	function timeline_settings(){
		global $wpdb;

		require_once(plugin_dir_path(__FILE__).'/timeline-list-table.class.php');
	
		$EventListTable = new TIMELINE_List_Table();
	
		$EventListTable->prepare_items();
	
		if ($EventListTable->show_main_section) {
	
			?>
				<div id="tdih" class="wrap">
					<div id="tdih_icon" class="icon32"></div>
					<h2><?php _e('Timeline', 'timeline'); ?><?php if (!empty($_REQUEST['s'])) { printf('<span class="subtitle">'.__('Search results for &#8220;%s&#8221;', 'timeline').'</span>', esc_html(stripslashes($_REQUEST['s']))); } ?></h2>
					<div id="ajax-response"></div>
					<div id="col-right">
						<div class="col-wrap">
							<div class="form-wrap">
								<form class="search-events" method="post">
									<input type="hidden" name="action" value="search" />
									<?php $EventListTable->search_box(__('Search Events', 'timeline'), 'event_date' ); ?>
								</form>
								<form id="events-filter" method="post">
									<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
									<?php $EventListTable->display() ?>
								</form>
							</div>
						</div>
					</div>
					<div id="col-left">
						<div class="col-wrap">
							<div class="form-wrap">
								<h3><?php _e('Add New Event', 'timeline'); ?></h3>
								<form id="addevent" method="post" class="validate">
									<input type="hidden" name="action" value="add" />
									<input type="hidden" name="page" value="<?php echo $_REQUEST['page']; ?>" />
									<?php wp_nonce_field('timeline'); ?>
									<div class="form-field form-required">
										<label for="event_date"><?php _e('Event Year', 'timeline'); ?></label>
										<input type="text" name="event_date" id="event_date" value="" required="required" placeholder="<?php echo timeline_date(); ?>" maxlength="4" />
										<p><?php printf(__('The date the event occured (enter date in %s format).', 'timeline'), timeline_date()); ?></p>
									</div>
									<div class="form-field form-required">
										<label for="event_month"><?php _e('Event Month', 'timeline'); ?></label>
										<select name="event_month" id="event_month" style="width:95%;" required="required">
											<option value="">Please Select</option>
											<option value="01">Jan</option>
											<option value="02">Feb</option>
											<option value="03">Mar</option>
											<option value="04">Apr</option>
											<option value="05">May</option>
											<option value="06">Jun</option>
											<option value="07">Jul</option>
											<option value="08">Aug</option>
											<option value="09">Sep</option>
											<option value="10">Oct</option>
											<option value="11">Nov</option>
											<option value="12">Dec</option>
										</select>
									</div>
									<div class="form-field form-required">
										<label for="event_name"><?php _e('Event Title', 'timeline'); ?></label>
										<textarea id="event_name" name="event_name" rows="3" cols="20" required="required" placeholder="Title of the event"></textarea>
										<p><?php _e('The name of the event.', 'timeline'); ?></p>
									</div>
									
									<div class="form-required">
										<label for="event_description"><?php _e('Event Description', 'timeline'); ?></label>
										<?php wp_editor('','event_description',array( 'textarea_rows' => '12' )); ?>
										<p><?php _e('The description of the event.', 'timeline'); ?></p>
									</div>
									<div class="form-field">
										<label for="event_date"><?php _e('Event Video', 'timeline'); ?></label>
										<input type="text" name="event_video" id="event_video" value="" />
										<p><?php printf(__('Enter Youtube Video URL.', 'timeline'), timeline_date()); ?></p>
									</div>
									<div class="form-field">
										<a href="#" class="button add_feature_image">Add Event Image</a><br /><br />
										<input type="hidden" id="attachment_id" name="attachment_id" class="upload">
										<span id="feature_image_holder" class="feature_image_holder"></span>
									</div>
									<p class="submit">
										<input type="submit" name="submit" class="button button-primary " value="<?php _e('Add New Event', 'timeline'); ?>" />
									</p>
								</form>
							</div>
						</div>
					</div>
				</div>
			<?php
		}
	}
	
	function setting_timeline_admin_init(){
		register_setting( 'timeline_options', 'timeline_options', 'timeline_options_validate' );
		add_settings_section('timeline_main', 'Timeline Settings', 'timeline_section_text', 'timeline');
		add_settings_field('start_year', 'Start Year', 'timeline_start_year', 'timeline', 'timeline_main');
		add_settings_field('per_page', 'Number of Events Per Page (Admin Section List)', 'timeline_per_page', 'timeline', 'timeline_main');
		add_settings_field('date_format', 'Date Format', 'timeline_date_format', 'timeline', 'timeline_main');
		add_settings_field('bg_color', 'Front End Timeline Range Background', 'timeline_bgcolor', 'timeline', 'timeline_main');
		add_settings_field('detail', 'Enable Events Link On Frontend (Click Event to view its detail)', 'timeline_detail', 'timeline', 'timeline_main');
	}
	
	function setting_timeline_section_text() {
		echo '<p>'.__('Options for the Timeline administration page.','timeline').'</p>';
	}
	
	function setting_timeline_start_year() {
		$options = get_option('timeline_options');
		echo "<input type='text' id='timeline_start_year' name='timeline_options[start_year]' value='".$options['start_year']."'  />";
	}
	
	function setting_timeline_bgcolor() {
		$options = get_option('timeline_options');
		echo "<textarea id='timeline_bg_color' name='timeline_options[bg_color]' row='3' style='width:400px;'>".$options['bg_color']."</textarea>";
		echo "<br>Please enter color code exactly like below<br>#F7E3AB, #D6E3BC, #B8CCE4, #CCC0D9";
	}
	
	function setting_timeline_per_page() {
		$options = get_option('timeline_options');
		$items = array(10, 20, 30, 40, 50, 100);
		echo "<select id='timeline_per_page' name='timeline_options[per_page]'>";
		foreach($items as $item) {
			$selected = ($options['per_page']==$item) ? 'selected="selected"' : '';
			echo "<option value='$item' $selected>$item</option>";
		}
		echo "</select>";
	}
	
	function setting_timeline_date_format() {
		$options = get_option('timeline_options');
		$items = array('YYYY - M','M - YYYY','YYYY / M','M / YYYY','YYYY - Month','Month - YYYY');
		echo "<select id='timeline_date_format' name='timeline_options[date_format]'>";
		foreach($items as $item) {
			$selected = ($options['date_format']==$item) ? 'selected="selected"' : '';
			echo "<option value='$item' $selected>$item</option>";
		}
		echo "</select>";
	}
	
	function setting_timeline_detail() {
		$options = get_option('timeline_options');
		$choices = array('No', 'Yes');
		echo "<select id='timeline_date_format' name='timeline_options[timeline_detail]'>";
		foreach($choices as $choice) {
			$selected = ($options['timeline_detail']== $choice) ? 'selected="selected"' : '';
			echo "<option value='$choice' $selected>$choice</option>";
		}
		echo "</select>";
		echo "<br>Please <a target='_blank' href='options-permalink.php'>re-save permalinks</a> if you want to enable events link";
	}
		
	function setting_load_timeline_styles() {
		wp_register_style('timeline', plugin_dir_url(__FILE__).'css/timeline.css');
		wp_enqueue_style('timeline');
		wp_register_style('timeline2', plugin_dir_url(__FILE__).'css/prettyPhoto.css');
		wp_enqueue_style('timeline2');
		wp_enqueue_script('timeline-scripts',  plugin_dir_url(__FILE__). 'js/timeline.js', array('jquery','media-upload','thickbox'));  		   
		wp_enqueue_script('timeline-scripts2',  plugin_dir_url(__FILE__). 'js/jquery.prettyPhoto.js', array('jquery','media-upload','thickbox'));  		   
		wp_enqueue_media();
		//enqueue scripts
		wp_enqueue_script('thickbox');   
		wp_enqueue_script('media-models');
		wp_enqueue_script('media-upload');
	}
}

?>