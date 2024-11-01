<?php
/*
Plugin Name: Timeline
Description: This is a timeline management plugin.
Author: Dotsquares
Version: 0.1
Author URI: http://dotsquares.com/
Plugin URI: http://dotsquares.com/plugins/timeline/
License: GPLv2 or later
*/
/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.


This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/
/* Activation, Deactivation and Uninstall */

global $timeline_db_version;

$timeline_db_version = "1.1";

require_once(plugin_dir_path(__FILE__).'timeline-init.class.php');

register_activation_hook(__FILE__, array('timeline_init', 'on_activate'));

register_deactivation_hook(__FILE__, array('timeline_init', 'on_deactivate'));

$timeline_current_db_version = get_option('timeline_db_version', 0);

define ( 'ET_URL', plugins_url('/',__FILE__) );

define ( 'ET_DIR', dirname(__FILE__).'/' );

if ($timeline_db_version != $timeline_current_db_version) {
	update_option('timeline_db_version', $timeline_db_version);
}

include("functions.php");
$DSTimeline = new dstimeline();

/* Add plugin CSS to the admin and site */
function load_timeline_styles() {
	global $DSTimeline;
	$DSTimeline->setting_load_timeline_styles();
}

add_action('admin_enqueue_scripts', 'load_timeline_styles');
add_action('wp_enqueue_scripts', 'load_timeline_styles');

/* Add historic event item to the Admin Bar "New" drop down */

function timeline_add_event_to_menu() {
	global $wp_admin_bar;

	if (!current_user_can('manage_timeline_events') || !is_admin_bar_showing()) { return; }

	$wp_admin_bar->add_node(array(
		'id'     => 'add-timeline-event',
		'parent' => 'new-content',
		'title'  => __('Timeline', 'timeline'),
		'href'   => admin_url('admin.php?page=timeline'),
		'meta'   => false));
}

add_action('admin_bar_menu', 'timeline_add_event_to_menu', 999);

/* Add historic events menu to the main admin menu */

function timeline_add_menu() {
	global $timeline_events;

	$timeline_events = add_object_page(__('Timeline', 'timeline'), __('Timeline', 'timeline'), 'manage_timeline_events', 'timeline', 'timeline_events', plugins_url('timeline/images/timeline.png'));
	add_action("load-$timeline_events", 'timeline_add_help_tab');
}

add_action('admin_menu', 'timeline_add_menu');

/* Highlight the correct top level menu */

function timeline_menu_correction($parent_file) {
	global $current_screen;

	$taxonomy = $current_screen->taxonomy;

	if ($taxonomy == 'event_type') { $parent_file = 'timeline'; }

	return $parent_file;
}

add_action('parent_file', 'timeline_menu_correction');


/* Options */

function timeline_options_menu() {
	add_options_page('Timeline Options', 'Timeline Settings', 'manage_options', 'tdih', 'timeline_options');
}

add_action('admin_menu', 'timeline_options_menu');

function timeline_options() {
	if (!current_user_can('manage_options'))  {
		wp_die(__('You do not have sufficient permissions to access this page.', 'tih'));
	}
		?>
	<div class="wrap">
		<div class="icon32" id="icon-options-general"></div>
		<h2><?php _e('Timeline Options', 'tidh'); ?></h2>
		<form action="options.php" method="post">
			<?php settings_fields('timeline_options'); ?>
			<?php do_settings_sections('timeline'); ?>
			<p class="submit"><input name="Submit" type="submit" class="button-primary" value="<?php esc_attr_e('Save Changes'); ?>" /></p>
		</form>
	</div>
<?php
}

function timeline_admin_init(){
	global $DSTimeline;
	$DSTimeline->setting_timeline_admin_init();
}

add_action('admin_init', 'timeline_admin_init');

function timeline_section_text() {
	global $DSTimeline;
	$DSTimeline->setting_timeline_section_text();
}

function timeline_start_year() {
	global $DSTimeline;
	$DSTimeline->setting_timeline_start_year();
}

function timeline_bgcolor() {
	global $DSTimeline;
	$DSTimeline->setting_timeline_bgcolor();
}

function timeline_per_page() {
	global $DSTimeline;
	$DSTimeline->setting_timeline_per_page();
}

function timeline_date_format() {
	global $DSTimeline;
	$DSTimeline->setting_timeline_date_format();
}

function timeline_detail() {
	global $DSTimeline;
	$DSTimeline->setting_timeline_detail();
}

function timeline_options_validate($input) {
	return $input;
}

/* Help */
function timeline_add_help_tab () {
		global $timeline_events;

		$screen = get_current_screen();

		if ($screen->id != $timeline_events) { return; }

		$screen->add_help_tab(array(
				'id'	=> 'timeline_overview',
				'title'	=> __('Overview'),
				'content'	=> '<p>'.__('This page provides the ability for you to add, edit and remove historic (or for that matter, future) events that you wish to display.', 'timeline').'</p>',
		));

		$screen->add_help_tab(array(
				'id'	=> 'timeline_date_format',
				'title'	=> __('Date Format'),
				'content'	=> '<p>'.sprintf(__('You must enter a full date in the format %s - for example the 20<sup>th</sup> November 1497 should be entered as %s.', 'pontnoir'), timeline_date(), timeline_date('example')).'</p>',
		));

		$screen->add_help_tab(array(
				'id'	=> 'timeline_names',
				'title'	=> __('Event Title'),
				'content'	=> '<p>'.__('You must enter title for the event - for example <em>John was born</em> or <em>Mary V died</em> or <em>Prof. Brian Cox played on the D:Ream hit single "Things Can Only Get Better"</em>.', 'timeline').'</p>',
		));
		$screen->add_help_tab(array(
				'id'	=> 'timeline_shortcode',
				'title'	=> __('Shortcode'),
				'content'	=> '<p>'.__('You can add a timeline shortcode [timeline] to any post or page to display the list of events.', 'timeline').'</p><p>',
		));
}

/* Main Page */
function timeline_events() {
	global $DSTimeline;
	$DSTimeline->timeline_settings();
}


function timeline_date($type = 'format') {
	$options = get_option('timeline_options');
	switch ($options['date_format']) {
	case '%Y':
				$format = 'YYYY';
				$example = '1497';
				break;
	default:
				$format = 'YYYY';
				$example = '1497';
	}
	$result = ($type == 'example' ? $example : $format);
	return $result;
}

function timeline_manage_event_type_event_column( $columns ) {
	unset( $columns['posts'] );
	$columns['events'] = __('Events', 'timeline');
	return $columns;
}
add_filter( 'manage_edit-event_type_columns', 'timeline_manage_event_type_event_column' );

/* Register Event Post Type */
function timeline_register_post_types() {
	$labels = array(
		'name' => _x('Events', 'post type general name', 'timeline'),
		'singular_name' => _x('Event', 'post type singular name', 'timeline'),
		'add_new' => _x('Add New', 'event', 'timeline'),
		'add_new_item' => __('Add New Event', 'timeline'),
		'edit_item' => __('Edit Event', 'timeline'),
		'new_item' => __('New Event', 'timeline'),
		'all_items' => __('All Events', 'timeline'),
		'view_item' => __('View Event', 'timeline'),
		'search_items' => __('Search Events', 'timeline'),
		'not_found' =>  __('No event found', 'timeline'),
		'not_found_in_trash' => __('No event found in Trash', 'timeline'),
		'parent_item_colon' => null,
		'menu_name' => __('Historic Events', 'timeline'), 
	);
	$args = array(
		'labels' => $labels,
		'public' => false,
	);
	register_post_type('timeline', $args);
}
add_action('init', 'timeline_register_post_types');

function display_format_date($format,$month,$year){
	global $DSTimeline;
	$formatedDate = $DSTimeline->setDisplayDateFormat($format,$month,$year);
	
	return $formatedDate;
}

/* Create shortcode Function for timeline display*/
function timeline_shortcode($atts) {
	global $wpdb, $DSTimeline;
	$timeline_text = $DSTimeline->showShortCode($atts);
	return $timeline_text;
}
add_shortcode('timeline', 'timeline_shortcode');

add_action('init', 'register_my_cpt_timeline');

function register_my_cpt_timeline() {
	register_post_type('timeline', array(
		'label' => 'Timeline',
		'description' => '',
		'public' => true,
		'show_ui' => true,
		'show_in_menu' => false,
		'capability_type' => 'post',
		'map_meta_cap' => true,
		'hierarchical' => false,
		'rewrite' => array('slug' => 'timeline', 'with_front' => true),
		'query_var' => true,
	) ); 
}
//Template fallback
add_action("template_redirect", 'my_theme_redirect');

function my_theme_redirect() {
    global $wp;
    $plugindir = dirname( __FILE__ );

    //A Specific Custom Post Type
    if ($wp->query_vars["post_type"] == 'timeline') {
        $templatefilename = 'single-timeline.php';
        if (file_exists(TEMPLATEPATH . '/' . $templatefilename)) {
            $return_template = TEMPLATEPATH . '/' . $templatefilename;
        } else {
            $return_template = $plugindir . '/template/' . $templatefilename;
        }
        do_theme_redirect($return_template);
    }
}

function do_theme_redirect($url) {
    global $post, $wp_query;
    if (have_posts()) {
        include($url);
        die();
    } else {
        $wp_query->is_404 = true;
    }
}
?>