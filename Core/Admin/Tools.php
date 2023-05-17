<?php
/**
 * Tools admin panel.
 *
 * @package PressForward
 */

namespace PressForward\Core\Admin;

use Intraxia\Jaxion\Contract\Core\HasActions;
use Intraxia\Jaxion\Contract\Core\HasFilters;
use PressForward\Core\Admin\PFTemplater;

/**
 * Tools admin panel.
 */
class Tools implements HasActions, HasFilters {
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
	 * @param string                               $basename  Basename.
	 * @param \PressForward\Core\Admin\PFTemplater $templates PFTemplater object.
	 */
	public function __construct( $basename, PFTemplater $templates ) {
		$this->basename  = $basename;
		$this->templates = $templates;
		add_filter( 'pf_tabs_pf-tools', array( $this, 'set_permitted_tools_tabs' ) );
	}

	/**
	 * Sets up Tools admin panel.
	 */
	public function add_plugin_admin_menu() {
		$this->templates->add_submenu_page(
			PF_MENU_SLUG,
			__( 'Tools', 'pressforward' ),
			__( 'Tools', 'pressforward' ),
			array( 'pf_menu_tools_access', 'contributor' ),
			PF_SLUG . '-tools',
			array( $this, 'display_tools_builder' )
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function filter_hooks() {
		return array(
			array(
				'hook'     => 'pf_tabs_pf-tools',
				'method'   => 'set_permitted_tools_tabs',
				'priority' => 10,
				'args'     => 1,
			),
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
				'priority' => 16,
			),
			array(
				'hook'     => 'admin_init',
				'method'   => 'nom_this_endpoint',
				'priority' => 2,
			),
		);
	}

	/**
	 * Display callback for Tools admin panel.
	 */
	public function display_tools_builder() {
		if ( isset( $_GET['tab'] ) ) {
			$tab = sanitize_text_field( wp_unslash( $_GET['tab'] ) );
		} else {
			$tab = 'nominate-this';
		}

		$vars = array(
			'current'            => $tab,
			'user_ID'            => true,
			'page_title'         => __( 'PressForward Tools', 'pressforward' ),
			'page_slug'          => 'pf-tools',
			'no_save_button'     => true,
			'settings_tab_group' => '',
			'tabs'               => $this->tabs(),
		);

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->templates->get_view( array( 'settings', 'settings-page' ), $vars );
	}

	/**
	 * Sets up tabs for Tools panel.
	 *
	 * @param array $permitted_tabs Tabs array.
	 * @return array
	 */
	public function set_permitted_tools_tabs( $permitted_tabs ) {
		$permitted_tabs['nominate-this'] = array(
			'title' => __( 'Bookmarklet', 'pressforward' ),
			'cap'   => $this->templates->users->pf_get_defining_capability_by_role( 'contributor' ),
		);

		$permitted_tabs['reset-refresh'] = array(
			'title' => __( 'Debug and Refresh', 'pressforward' ),
			'cap'   => $this->templates->users->pf_get_defining_capability_by_role( 'administrator' ),
		);

		$permitted_tabs['retrieval-status'] = array(
			'title' => __( 'Retrieval Status', 'pressforward' ),
			'cap'   => $this->templates->users->pf_get_defining_capability_by_role( 'contributor' ),
		);

		$permitted_tabs['stats'] = array(
			'title' => __( 'Statistics', 'pressforward' ),
			'cap'   => $this->templates->users->pf_get_defining_capability_by_role( 'contributor' ),
		);

		return $permitted_tabs;
	}

	/**
	 * Gets settings tabs for Tools panel.
	 *
	 * @param string $tab Tab name.
	 * @return string
	 */
	public function tab_group( $tab ) {
		return $this->templates->settings_tab_group( $tab, 'pf-tools' );
	}

	/**
	 * Gets tabs for Tools panel.
	 *
	 * @return array
	 */
	public function tabs() {
		return $this->templates->permitted_tabs( 'pf-tools' );
	}

	/**
	 * Sets up Nominate This endpoint.
	 */
	public function nom_this_endpoint() {
		\start_pf_nom_this();
	}
}
