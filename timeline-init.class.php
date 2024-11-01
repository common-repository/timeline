<?php

class timeline_init {

	public function __construct($case = false) {

		switch($case) {
			case 'activate' :
				$this->timeline_activate();
			break;

			case 'deactivate' :
				$this->timeline_deactivate();
			break;

			default:
				wp_die('Invalid Access');
			break;
		}
	}

	public function on_activate() {
		new timeline_init('activate');
	}
	
	public function on_deactivate() {
		new timeline_init('deactivate');
	}

	private function timeline_activate() {
		global $wpdb, $timeline_db_version;

		add_option('timeline_db_version', $timeline_db_version);

		add_option('timeline_options', array('date_format'=>'%Y-%m-%d', 'per_page' => '10'));

		$role = get_role('administrator');

		if(!$role->has_cap('manage_timeline_events')) { $role->add_cap('manage_timeline_events'); }

	}

	private function timeline_deactivate() {
		// do nothing
	}
}

?>