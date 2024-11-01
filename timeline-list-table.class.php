<?php
if(!class_exists('WP_List_Table')){ require_once(ABSPATH.'wp-admin/includes/class-wp-list-table.php'); }

class TIMELINE_List_Table extends WP_List_Table {

	public $show_main_section = true;

	private $date_format;

	private $date_description;

	private $per_page;

	public function __construct(){
		
		global $status, $page;

		$options = get_option('timeline_options');

		$this->date_format = $options['date_format'];

		$this->date_description = $this->timeline_date();

		$this->per_page = $options['per_page'];

		parent::__construct( array(
			'singular' => 'event',
			'plural'   => 'events',
			'ajax'     => true
		));
	}

	public function column_default($item, $column_name){  
		
		switch($column_name){
			case 'event_name':
				return $item->event_name;
			case 'event_date':
				return $item->event_date;
			case 'event_image':	
				$image = get_post_meta($item->ID,'_thumbnail_id');
				return wp_get_attachment_image( $image[0], array(80,80));
			default:
				return print_r($item, true);
		}
	}
	public function column_event_name($item){

		$actions = array(
			'edit'   => sprintf('<a href="?page=%s&action=%s&id=%s">Edit</a>', $_REQUEST['page'], 'edit', $item->ID),
			'delete' => sprintf('<a href="?page=%s&action=%s&id=%s">Delete</a>', $_REQUEST['page'], 'delete', $item->ID),
		);

		return sprintf('%1$s %2$s', $item->event_name, $this->row_actions($actions));
	}

	public function column_cb($item){
		return sprintf('<input type="checkbox" name="%1$s[]" value="%2$s" />', $this->_args['singular'], $item->ID);
	}

	public function get_columns(){
		$columns = array(
			'cb'         => '<input type="checkbox" />',
			'event_name' => 'Event Title',
			'event_date' => 'Event Year',
			'event_image' => 'Event Image',
		);
		return $columns;
	}

	public function get_sortable_columns() {

		$sortable_columns = array(
			'event_date' => array('event_date', false),
			'event_name' => array('event_name', false),
		);
		return $sortable_columns;
	}

	public function get_bulk_actions() {

		$actions = array(
			'bulk_delete' => 'Delete'
		);
		return $actions;
	}

	private function process_bulk_action() {
		
		global $wpdb;

		$this->show_main_section = true;

		switch($this->current_action()){

			case 'add':
				check_admin_referer('timeline');

				//$event_date = $this->date_reorder($_POST['event_date']);
				$event_year = $this->date_reorder($_POST['event_date']);
				$event_date = $event_year;
				$event_month = $_POST['event_month'];
				$event_video = $_POST['event_video'];
				$event_name = stripslashes($_POST['event_name']);
				$attach_id = $_POST['attachment_id'];
				$event_description = stripslashes($_POST['event_description']);

				//$error = $this->validate_event($event_date, $event_name);
				$error = $this->validate_event($event_year, $event_name, $event_month, $event_video);

				if ($error) {
					wp_die ($error, 'Error', array("back_link" => true));
				} else {
					$post = array(
						'comment_status' => 'closed',
						'ping_status'    => 'closed',
						'post_status'    => 'publish',
						'post_title'     => $event_name,
						'post_content'   => $event_description,
						'post_type'      => 'timeline',
					);
					$post_id = wp_insert_post($post);
					add_post_meta($post_id, '_thumbnail_id', $attach_id, true);
					add_post_meta($post_id, '_video', $event_video, true);
					add_post_meta($post_id, '_date', $event_date, true);
					add_post_meta($post_id, '_month', $event_month, true);
				}

			break;

			case 'edit':
				$id = (int) $_GET['id'];

				$event = $wpdb->get_row("SELECT ID, post_title AS event_name, post_content AS event_description FROM ".$wpdb->prefix."posts WHERE ID=".$id);
				$event_image = $wpdb->get_row("SELECT meta_value FROM ".$wpdb->prefix."postmeta WHERE meta_key = '_thumbnail_id' AND post_id = ".$event->ID);
				$event_date = $wpdb->get_row("SELECT meta_value FROM ".$wpdb->prefix."postmeta WHERE meta_key = '_date' AND post_id = ".$event->ID);
				$event_month = $wpdb->get_row("SELECT meta_value FROM ".$wpdb->prefix."postmeta WHERE meta_key = '_month' AND post_id = ".$event->ID);
				$event_video = $wpdb->get_row("SELECT meta_value FROM ".$wpdb->prefix."postmeta WHERE meta_key = '_video' AND post_id = ".$event->ID);
				$event_year =  $event_date->meta_value;
				$event_month =  $event_month->meta_value;
				?>
					<div id="tdih" class="wrap">
						<div id="tdih_icon" class="icon32"></div>
						<h2><?php _e('Timeline', 'timeline'); ?></h2>
						<div id="ajax-response"></div>
						<div class="form-wrap">
							<h3><?php _e('Edit Event', 'timeline'); ?></h3>
							<form id="editevent" method="post" class="validate">
								<input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
								<input type="hidden" name="action" value="update" />
								<input type="hidden" name="id" value="<?php echo $id; ?>" />
								<?php wp_nonce_field('timeline_edit'); ?>
								<div class="form-field form-required">
									<label for="event_date"><?php _e('Event Year', 'timeline'); ?></label>
									<input type="text" name="event_date" id="event_date" value="<?php echo $event_year; //echo $event->event_date; ?>" required="required" />
									<p><?php printf(__('The date the event occured (enter date in %s format).', 'timeline'), $this->date_description); ?></p>
								</div>
								<div class="form-field form-required">
									<label for="event_month"><?php _e('Event Month', 'timeline'); ?></label>
									<select name="event_month" id="event_month" style="width:95%;" required="required">
										<option value="">Please Select</option>
										<option value="01"<?php if($event_month=='01') echo ' selected="selected"'; ?>>Jan</option>
										<option value="02"<?php if($event_month=='02') echo ' selected="selected"'; ?>>Feb</option>
										<option value="03"<?php if($event_month=='03') echo ' selected="selected"'; ?>>Mar</option>
										<option value="04"<?php if($event_month=='04') echo ' selected="selected"'; ?>>Apr</option>
										<option value="05"<?php if($event_month=='05') echo ' selected="selected"'; ?>>May</option>
										<option value="06"<?php if($event_month=='06') echo ' selected="selected"'; ?>>Jun</option>
										<option value="07"<?php if($event_month=='07') echo ' selected="selected"'; ?>>Jul</option>
										<option value="08"<?php if($event_month=='08') echo ' selected="selected"'; ?>>Aug</option>
										<option value="09"<?php if($event_month=='09') echo ' selected="selected"'; ?>>Sep</option>
										<option value="10"<?php if($event_month=='10') echo ' selected="selected"'; ?>>Oct</option>
										<option value="11"<?php if($event_month=='11') echo ' selected="selected"'; ?>>Nov</option>
										<option value="12"<?php if($event_month=='12') echo ' selected="selected"'; ?>>Dec</option>
									</select>
									<?php /*?><input type="text" name="event_month" id="event_month" value="" required="required" placeholder="MM" />
									<p><?php printf(__('The date the event occured (enter date in MM format).', 'timeline')); ?></p><?php */?>
								</div>
								<div class="form-field form-required">
									<label for="event_name"><?php _e('Event Title', 'timeline'); ?></label>
									<textarea id="event_name" name="event_name" rows="3" cols="20" required="required"><?php echo esc_html($event->event_name); ?></textarea>
									<p><?php _e('The name of the event.', 'timeline'); ?></p>
								</div>
								<div class="form-required">
									<label for="event_description"><?php _e('Event Description', 'timeline'); ?></label>
									<?php 
									wp_editor($event->event_description, 'event_description'); 
									?>
									<p><?php _e('The description of the event.', 'timeline'); ?></p>
								</div>
								<div class="form-field form-required">
									<label for="event_video"><?php _e('Event Video', 'timeline'); ?></label>
									<input type="text" name="event_video" id="event_video" value="<?php echo $event_video->meta_value; ?>" />
									<p><?php printf(__('Enter Youtube Video URL.', 'timeline'), timeline_date()); ?></p>
								</div>
                                <div class="form-field">
									<span id="fimage"><?php if($event_image->meta_value!= '') { echo wp_get_attachment_image( $event_image->meta_value, array(80,80)); } ?></span>
									<?php if($event_image->meta_value!= '') { ?>
										<br />
										<a href="#" class="button" onclick="remove_image();">Remove Feature Image</a>
										<br />
									<?php } ?>
									<br />
									<a href="#" class="button add_feature_image">Edit Feature Image</a><br /><br />
									<input type="hidden" id="attachment_id" name="attachment_id" class="upload" value="<?php echo $event_image->meta_value; ?>">
									<span id="feature_image_holder" class="feature_image_holder"></span>
                                </div>
								<p class="submit">
									<input type="submit" name="submit" class="button button-primary " value="<?php _e('Save Changes', 'timeline'); ?>" />
								</p>
							</form>
						</div>
					</div>
					<script language="javascript">
					function remove_image(){
						document.getElementById('fimage').innerHTML = '';
						document.getElementById('attachment_id').value = '';
					}
					</script>
				<?php
				$this->show_main_section = false;
			break;
			case 'update':
				check_admin_referer('timeline_edit');

				$id = (int) $_POST['id'];
				//$event_date = $this->date_reorder($_POST['event_date']);
				$event_year = $this->date_reorder($_POST['event_date']);
				$event_date = $event_year;
				$event_month = $_POST['event_month'];
				$event_name = stripslashes($_POST['event_name']);
				$event_video = $_POST['event_video'];
				$attach_id = $_POST['attachment_id'];
				$event_description = stripslashes($_POST['event_description']);
				
				$error = $this->validate_event($event_year, $event_name, $event_month, $event_video);

				if ($error) {
					wp_die ($error, 'Error', array("back_link" => true));
				} else {
					$post = array(
						'ID' => $id,
						'post_title' => $event_name,
						'post_content' => $event_description,
					);
					$post_id = wp_update_post($post);
					update_post_meta($post_id, '_thumbnail_id', $attach_id);
					update_post_meta($post_id, '_video', $event_video);
					update_post_meta($post_id, '_date', $event_date);
					update_post_meta($post_id, '_month', $event_month);
				}
			break;

			case 'delete':
				$id = (int) $_GET['id'];
				$result = wp_delete_post($id, true);
			break;

			case 'bulk_delete':
				check_admin_referer('bulk-events');
				$ids = (array) $_POST['event'];

				foreach ($ids as $i => $value) {
					$result = wp_delete_post($ids[$i], true);
				}
			break;

			default:
			// nowt
			break;
		}
	}

	private function validate_event($event_year, $event_name, $event_month, $event_video ) {

		$error = false;

		if (empty($event_year)) {
			$error = '<h3>'. __('Missing Event Year', 'timeline') .'</h3><p>'.  __('You must enter a date for the event.', 'timeline') .'</p>';
		} else if (empty($event_name)) {
			$error = '<h3>'. __('Missing Event Name', 'timeline') .'</h3><p>'. __('You must enter a name for the event.', 'timeline') .'</p>';
		} else if (!$this->date_check($event_year)) {
			$error = '<h3>'. __('Invalid Event Year', 'timeline') .'</h3><p>'. $event_year.' '.sprintf(__('Please enter year in the format %s.', 'timeline'), $this->date_description) .'</p>';
		} else if ($this->check_youtube($event_video) == 0) {
			$error = '<h3>'. __('Invalid Event Video', 'timeline') .'</h3><p>'. $event_video.' '.sprintf(__('Please enter a valid youtube video', 'timeline'), $this->date_description) .'</p>';
		}

		return $error;
	}
	
	private function date_check($date) {

		if (preg_match("/^(\d{4})$/", $date, $matches)) {
			if (checkdate(12,31,$matches[0])) {
				return true;
			}
		}

		return false;
	}
	
	private function check_youtube($event_video){
		
		if($event_video!= ''){
			$rx = '~
			^(?:https?://)?              # Optional protocol
			(?:www\.)?                  # Optional subdomain
			(?:youtube\.com|youtu\.be)  # Mandatory domain name
			/watch\?v=([^&]+)           # URI with video id as capture group 1
			~x';
			
			$has_match = preg_match($rx, $event_video, $matches);
			return $has_match;
		} else {
			return '1';
		}
		
	}

	private function date_reorder($date) {

		switch ($this->date_format) {

			case '%m-%d-%Y':
				if (preg_match("/^(\d{2})-(\d{2})-(\d{4})$/", $date, $matches)) {
					return $matches[3].'-'.$matches[1].'-'.$matches[2];
				}
				break;

			case '%d-%m-%Y':
				if (preg_match("/^(\d{2})-(\d{2})-(\d{4})$/", $date, $matches)) {
					return $matches[3].'-'.$matches[2].'-'.$matches[1];
				}
				break;
		}

		return $date;
	}

	private function timeline_date() {

		switch ($this->date_format) {

		case '%Y':
					$format = 'YYYY';
					break;
		default:
					$format = 'YYYY';
		}

		return $format;
	}

	private function timeline_terms($id) {

		$terms = get_the_terms($id, 'event_type');

		$term_list = '';

				if ($terms != '') {
					foreach ($terms as $term) {
						$term_list .= $term->name . ', ';
					}
				} else {
					$term_list = __('none', 'timeline');
			}
			$term_list = trim($term_list, ', ');

		return $term_list;
	}

	public function pagination( $which ) {
		if ( empty( $this->_pagination_args ) )
			return;

		extract( $this->_pagination_args );

		$output = '<span class="displaying-num">' . sprintf( _n( '1 item', '%s items', $total_items ), number_format_i18n( $total_items ) ) . '</span>';

		$current = $this->get_pagenum();

		$current_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

		$current_url = remove_query_arg( array( 'hotkeys_highlight_last', 'hotkeys_highlight_first', 'action', 'id'), $current_url );

		$page_links = array();

		$disable_first = $disable_last = '';
		if ( $current == 1 )
			$disable_first = ' disabled';
		if ( $current == $total_pages )
			$disable_last = ' disabled';

		$page_links[] = sprintf( "<a class='%s' title='%s' href='%s'>%s</a>",
			'first-page' . $disable_first,
			esc_attr__( 'Go to the first page' ),
			esc_url( remove_query_arg( 'paged', $current_url ) ),
			'&laquo;'
		);

		$page_links[] = sprintf( "<a class='%s' title='%s' href='%s'>%s</a>",
			'prev-page' . $disable_first,
			esc_attr__( 'Go to the previous page' ),
			esc_url( add_query_arg( 'paged', max( 1, $current-1 ), $current_url ) ),
			'&lsaquo;'
		);

		if ( 'bottom' == $which )
			$html_current_page = $current;
		else
			$html_current_page = sprintf( "<input class='current-page' title='%s' type='text' name='%s' value='%s' size='%d' />",
				esc_attr__( 'Current page' ),
				esc_attr( 'paged' ),
				$current,
				strlen( $total_pages )
			);

		$html_total_pages = sprintf( "<span class='total-pages'>%s</span>", number_format_i18n( $total_pages ) );
		$page_links[] = '<span class="paging-input">' . sprintf( _x( '%1$s of %2$s', 'paging' ), $html_current_page, $html_total_pages ) . '</span>';

		$page_links[] = sprintf( "<a class='%s' title='%s' href='%s'>%s</a>",
			'next-page' . $disable_last,
			esc_attr__( 'Go to the next page' ),
			esc_url( add_query_arg( 'paged', min( $total_pages, $current+1 ), $current_url ) ),
			'&rsaquo;'
		);

		$page_links[] = sprintf( "<a class='%s' title='%s' href='%s'>%s</a>",
			'last-page' . $disable_last,
			esc_attr__( 'Go to the last page' ),
			esc_url( add_query_arg( 'paged', $total_pages, $current_url ) ),
			'&raquo;'
		);

		$output .= "\n<span class='pagination-links'>" . join( "\n", $page_links ) . '</span>';

		if ( $total_pages )
			$page_class = $total_pages < 2 ? ' one-page' : '';
		else
			$page_class = ' no-pages';

		$this->_pagination = "<div class='tablenav-pages{$page_class}'>$output</div>";

		echo $this->_pagination;
	}

	public function print_column_headers( $with_id = true ) {

		$screen = get_current_screen();

		list( $columns, $hidden, $sortable ) = $this->get_column_info();

		$current_url = ( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
		$current_url = remove_query_arg(array('paged', 'id', 'action'), $current_url);

		if ( isset( $_GET['orderby'] ) )
			$current_orderby = $_GET['orderby'];
		else
			$current_orderby = '';

		if ( isset( $_GET['order'] ) && 'desc' == $_GET['order'] )
			$current_order = 'desc';
		else
			$current_order = 'asc';

		foreach ( $columns as $column_key => $column_display_name ) {
			$class = array( 'manage-column', "column-$column_key" );

			$style = '';
			if ( in_array( $column_key, $hidden ) )
				$style = 'display:none;';

			$style = ' style="' . $style . '"';

			if ( 'cb' == $column_key )
				$class[] = 'check-column';
			elseif ( in_array( $column_key, array( 'posts', 'comments', 'links' ) ) )
				$class[] = 'num';

			if ( isset( $sortable[$column_key] ) ) {
				list( $orderby, $desc_first ) = $sortable[$column_key];

				if ( $current_orderby == $orderby ) {
					$order = 'asc' == $current_order ? 'desc' : 'asc';
					$class[] = 'sorted';
					$class[] = $current_order;
				} else {
					$order = $desc_first ? 'desc' : 'asc';
					$class[] = 'sortable';
					$class[] = $desc_first ? 'asc' : 'desc';
				}
				$column_display_name = '<a href="' . esc_url( add_query_arg( compact( 'orderby', 'order' ), $current_url ) ) . '"><span>' . $column_display_name . '</span><span class="sorting-indicator"></span></a>';
			}

			$id = $with_id ? "id='$column_key'" : '';

			if ( !empty( $class ) )
				$class = "class='" . join( ' ', $class ) . "'";

			echo "<th scope='col' $id $class $style>$column_display_name</th>";
		}
	}

	public function prepare_items() {
		global $wpdb;

		$per_page = $this->per_page;

		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array($columns, $hidden, $sortable);

		$this->process_bulk_action();

		$type = empty($_REQUEST['type']) ? '' : " AND ts.slug='".$_REQUEST['type']."'";

		$filter = (empty($_REQUEST['s'])) ? '' : "AND (p.post_title LIKE '%".like_escape($_REQUEST['s'])."%' OR p.post_content LIKE '%".like_escape($_REQUEST['s'])."%') ";

		$_REQUEST['orderby'] = empty($_REQUEST['orderby']) ? 'event_date' : $_REQUEST['orderby'];

		switch ($_REQUEST['orderby']) {
			case 'event_name':
				$orderby = 'ORDER BY p.post_title ';
				break;
			case 'event_date':
				$orderby = 'ORDER BY pm.meta_value ' ;
				break;
			default:
				$orderby = 'ORDER BY p.post_title ';
		}

		$order = empty($_REQUEST['order']) ? 'ASC' : $_REQUEST['order'];

		$sql = "SELECT p.ID, p.post_title AS event_name, p.post_content AS event_description, pm.meta_value as event_date FROM ".$wpdb->prefix."posts p LEFT JOIN ".$wpdb->prefix."postmeta pm ON p.ID = pm.post_id WHERE p.post_type = 'timeline' AND  pm.meta_key = '_date' ".$type.$filter.$orderby.$order;
		
		$events = $wpdb->get_results($sql);
		
		$current_page = $this->get_pagenum();

		$total_items = count($events);

		$events = array_slice($events, (($current_page - 1) * $per_page), $per_page);

		$this->items = $events;

		$this->set_pagination_args(array(
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ceil($total_items / $per_page)
		));
	}
}
?>