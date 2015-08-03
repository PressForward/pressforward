<?php
class Tabbed_Page {

	public static function init() {
		static $instance;

		if ( ! is_a( $instance, 'Tabbed_Page' ) ) {
			$instance = new self();
		}

		return $instance;
	}

	function __construct() {

	}

	public function the_settings_page(){
		if ( isset ( $_GET['tab'] ) ) $tab = $_GET['tab']; else $tab = 'user';
		$user_ID = get_current_user_id();
		$vars = array(
				'current'		=> $tab,
				'user_ID'		=> $user_ID
			);
		echo $this->get_view($this->build_path(array('settings','settings-page'), false), $vars);

		return;
	}

	public function settings_tab_group($current){
		$tabs = $this->permitted_tabs();
		foreach ($tabs as $tab=>$tab_meta){
			if (current_user_can($tab_meta['cap'])){
				if ($current == $tab) $class = 'pftab tab active'; else $class = 'pftab tab';
				?>
				<div id="<?php echo $tab; ?>" class="<?php echo $class; ?>">
	            <h2><?php echo $tab_meta['title']; ?></h2>
		            <?php
		            	if (has_action('pf_do_settings_tab_'.$tab) && !array_key_exists($tab, $tabs)){
		            		do_action('pf_do_settings_tab_'.$tab);
		            	} else {
							$this->the_settings_tab($tab);
						}
					?>
				</div>
				<?php
			}
		}

		return;
	}


	public function the_settings_tab($tab){
		$permitted_tabs = $this->permitted_tabs();
		if ( array_key_exists($tab, $permitted_tabs) ) $tab = $tab; else return '';
		$vars = array(
				'current'		=> $tab
			);
		#var_dump($tab);
		echo $this->get_view($this->build_path(array('settings','tab-'.$tab), false), $vars);

		return;
	}

}