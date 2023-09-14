<?php
/**
 * Preferences.
 *
 * @package PressForward
 */

namespace PressForward\Core\Admin;

use PressForward\Core\Admin\PFTemplater;

/**
 * Preferences.
 */
class Preferences implements \Intraxia\Jaxion\Contract\Core\HasActions {
	/**
	 * Basename.
	 *
	 * @access protected
	 * @var string
	 */
	protected $basename;

	/**
	 * PFTemplater object.
	 *
	 * @access public
	 * @var \PressForward\Core\Admin\PFTemplater
	 */
	public $templates;

	/**
	 * Constructor.
	 *
	 * @param string                               $basename  Not used.
	 * @param \PressForward\Core\Admin\PFTemplater $templates PFTemplater object.
	 */
	public function __construct( $basename, PFTemplater $templates ) {
		$this->basename  = $basename;
		$this->templates = $templates;
	}

	/**
	 * Sets up admin menu for Preferences.
	 */
	public function add_plugin_admin_menu() {
		add_submenu_page(
			PF_MENU_SLUG,
			__( 'Preferences', 'pressforward' ),
			__( 'Preferences', 'pressforward' ),
			'edit_posts',
			PF_SLUG . '-options',
			array( $this, 'display_options_builder' )
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function action_hooks() {
		return array(
			array(
				'hook'     => 'admin_menu',
				'method'   => 'add_plugin_admin_menu',
				'priority' => 15,
			),
		);
	}

	/**
	 * Display callback for Preferences panel.
	 */
	public function display_options_builder() {
		if ( isset( $_GET['tab'] ) ) {
			$tab = sanitize_text_field( wp_unslash( $_GET['tab'] ) );
		} else {
			$tab = 'user';
		}

		$vars = array(
			'current'            => $tab,
			'user_ID'            => true,
			'page_title'         => __( 'PressForward Preferences', 'pressforward' ),
			'page_slug'          => 'settings',
			'settings_tab_group' => '',
			'tabs'               => $this->tabs(),
		);

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->templates->get_view( array( 'settings', 'settings-page' ), $vars );
	}

	/**
	 * Echoes the URL of the admin page
	 *
	 * @since 1.7
	 */
	public function pf_admin_url() {
		echo esc_url( pf_get_admin_url() );
	}

	/**
	 * Returns the URL of the admin page
	 *
	 * @return string
	 */
	public function pf_get_admin_url() {
		return add_query_arg( 'page', PF_SLUG . '-options', admin_url( 'admin.php' ) );
	}

	/**
	 * Builds dropdown option for user role dropdown.
	 *
	 * @param string $option        'option_name' in wp_options for this setting.
	 * @param string $default_value Default passed to get_option().
	 */
	public function pf_get_user_role_select( $option, $default_value ) {
		global $wp_roles;

		$roles   = $wp_roles->get_names();
		$enabled = get_option( $option, $default_value );

		foreach ( $roles as $slug => $role ) {
			$defining_capability = pressforward( 'controller.users' )->pf_get_defining_capability_by_role( $slug );
			?>
			<option value="<?php echo esc_attr( $defining_capability ); ?>" <?php selected( $enabled, $defining_capability ); ?>><?php echo esc_html( $role ); ?></option>
			<?php
		}
	}

	/**
	 * Echoes the Nominate This bookmarklet link
	 *
	 * @since 1.7
	 */
	public function pf_shortcut_link() {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo pf_get_shortcut_link();
	}

	/**
	 * Gets the settings tab group for this panel.
	 *
	 * @param string $tab Tab name.
	 * @return string
	 */
	public function prefrences_tab( $tab ) {
		return $this->templates->settings_tab_group( $tab, 'settings' );
	}

	/**
	 * Gets a list of tabs for this panel.
	 *
	 * @return array
	 */
	public function tabs() {
		return $this->templates->permitted_tabs( 'settings' );
	}
}
