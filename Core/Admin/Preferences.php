<?php
namespace PressForward\Core\Admin;

use PressForward\Core\Admin\PFTemplater;

use Intraxia\Jaxion\Contract\Core\HasActions as HasActions;

class Preferences implements HasActions {

	protected $basename;

	function __construct( $basename, PFTemplater $templates ) {
		$this->basename  = $basename;
		$this->templates = $templates;
		// return true;
	}


	public function add_plugin_admin_menu() {
		add_submenu_page(
			PF_MENU_SLUG,
			__( 'Preferences', 'pf' ), // @todo sprintf
			__( 'Preferences', 'pf' ),
			'edit_posts',
			PF_SLUG . '-options',
			array( $this, 'display_options_builder' )
		);
	}

	public function action_hooks() {
		return array(
			array(
				'hook'     => 'admin_menu',
				'method'   => 'add_plugin_admin_menu',
				'priority' => 15,
			),
		);
	}

	public function display_options_builder() {
		if ( isset( $_GET['tab'] ) ) {
			$tab = $_GET['tab'];
		} else {
			$tab = 'user'; }
		$vars = array(
			'current'            => $tab,
			'user_ID'            => true,
			'page_title'         => __( 'PressForward Preferences', 'pf' ),
			'page_slug'          => 'settings',
			'settings_tab_group' => '', // $this->prefrences_tab($tab)
			'tabs'               => $this->tabs(),
		);
		echo $this->templates->get_view( array( 'settings', 'settings-page' ), $vars );

		return;
	}


	/**
	 * Echoes the URL of the admin page
	 *
	 * @since 1.7
	 */
	function pf_admin_url() {
		echo pf_get_admin_url();
	}
	/**
	 * Returns the URL of the admin page
	 *
	 * @return string
	 */
	function pf_get_admin_url() {
		return add_query_arg( 'page', PF_SLUG . '-options', admin_url( 'admin.php' ) );
	}

	function pf_get_user_role_select( $option, $default ) {
		global $wp_roles;
		$roles   = $wp_roles->get_names();
		$enabled = get_option( $option, $default );
		// $roleObj = pf_get_role_by_capability($enabled, true, true);
		// $enabled_role = $roleObj->name;
		foreach ( $roles as $slug => $role ) {
			$defining_capability = pressforward( 'controller.users' )->pf_get_defining_capability_by_role( $slug );
			?><option value="<?php echo $defining_capability; ?>" <?php selected( $enabled, $defining_capability ); ?>><?php _e( $role, PF_SLUG ); ?></option>
			<?php
		}
	}

	/**
	 * Echoes the Nominate This bookmarklet link
	 *
	 * @since 1.7
	 */
	function pf_shortcut_link() {
		echo pf_get_shortcut_link();
	}

	public function prefrences_tab( $tab ) {
		return $this->templates->settings_tab_group( $tab, 'settings' );
	}

	public function tabs() {
		return $this->templates->permitted_tabs( 'settings' );
	}

}
