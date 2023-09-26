<?php
/**
 * Base module class.
 *
 * @package PressForward
 */

/**
 * Base class for PressForward RSS modules
 */
class PF_Module {
	/**
	 * Module ID.
	 *
	 * @access public
	 * @var string
	 */
	public $id;

	/**
	 * Module dir path.
	 *
	 * @access public
	 * @var string
	 */
	public $module_dir;

	/**
	 * Module URL.
	 *
	 * @access public
	 * @var string
	 */
	public $module_url;

	/**
	 * Feed type
	 *
	 * @access public
	 * @var string
	 */
	public $feed_type;

	/**
	 * Initializes module.
	 */
	public function start() {
		$this->setup_hooks();
	}

	/**
	 * Sets up hooks.
	 */
	public function setup_hooks() {
		// Once modules are registered, set up some basic module info.
		add_action( 'pf_setup_modules', array( $this, 'setup_module_info' ) );
		add_action( 'admin_init', array( $this, 'module_setup' ) );

		// Set up the admin panels and save methods.
		add_action( 'pf_admin_op_page', array( $this, 'admin_op_page' ) );
		add_action( 'pf_admin_op_page_save', array( $this, 'admin_op_page_save' ) );
	}

	/**
	 * Determine some helpful info about this module.
	 *
	 * Sets the module ID based on the key used to register the module in
	 * the $pf global.
	 *
	 * Also sets up the module_dir and module_url for use throughout.
	 */
	public function setup_module_info() {
		$pf = pressforward( 'modules' );

		// Determine the ID by checking which module this class belongs to.
		$module_class = get_class( $this );
		foreach ( $pf->modules as $module_id => $module ) {
			if ( is_a( $module, $module_class ) ) {
				$this->id = $module_id;
				break;
			}
		}

		// If we've found an id, use it to create some paths.
		if ( $this->id ) {
			$this->module_dir = trailingslashit( PF_ROOT . '/modules/' . $this->id );
			$this->module_url = trailingslashit( PF_URL . 'modules/' . $this->id );
		}

		$module_id = $this->id;

		$enabled = get_option( PF_SLUG . '_' . $module_id . '_enable' );
		$enabled = 'no' === $enabled ? 'no' : 'yes';

		if ( 'yes' === $enabled ) {
			// Run at 20 to make sure the core menu is loaded first.
			add_action( 'admin_menu', array( $this, 'setup_admin_menus' ), 20 );

			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_styles' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'wp_enqueue_scripts' ) );
			add_action( 'wp_enqueue_styles', array( $this, 'wp_enqueue_styles' ) );

			if ( ! empty( $this->feed_type ) ) {
				if ( 'rss' === $this->feed_type ) {
					$feed_type = 'primary_feed_type';
				} else {
					$feed_type = $this->feed_type;
				}

				add_action( 'pf_do_pf-add-feeds_tab_' . $feed_type, array( $this, 'add_to_feeder' ) );
				add_filter( 'pf_tabs_pf-add-feeds', array( $this, 'set_permitted_feeds_tabs' ) );
			}

			add_filter( 'dash_widget_bar', array( $this, 'add_dash_widgets_filter' ) );
		}

		if ( method_exists( $this, 'post_setup_module_info' ) ) {
			$this->post_setup_module_info();
		}
	}

	/**
	 * Returns default settings for the module.
	 *
	 * @return array
	 */
	public function get_default_settings() {
		$mod_settings = array(
			'name'        => $this->id . ' Module',
			'slug'        => $this->id,
			'description' => __( 'This module needs to overwrite the setup_module function and give a description.', 'pressforward' ),
			'thumbnail'   => '',
			'options'     => '',
		);

		return $mod_settings;
	}

	/**
	 * Sets up module for admin.
	 *
	 * @return void
	 */
	public function module_setup() {
		$settings = get_option( $this->get_option_name() );
		if ( $settings ) {
			return;
		}

		update_option( $this->get_option_name(), $this->get_default_settings() );
	}

	/**
	 * Gets the option name for this module's settings.
	 *
	 * @return string
	 */
	public function get_option_name() {
		return PF_SLUG . '_' . $this->id . '_settings';
	}

	/**
	 * Option page for the module.
	 */
	public function admin_op_page() {
		// Module enable option code originated in https://github.com/boonebgorges/participad.
		$modsetup  = get_option( PF_SLUG . '_' . $this->id . '_settings' );
		$module_id = $this->id;

		$enabled = get_option( PF_SLUG . '_' . $module_id . '_enable' );
		$enabled = 'no' === $enabled ? 'no' : 'yes';

		?>

		<table class="form-table">
			<tr>
				<th scope="row">
					<?php /* translators: module name */ ?>
					<label for="pressforward-dashboard-enable"><?php echo esc_html( sprintf( __( 'Enable %s', 'pressforward' ), $modsetup['name'] ) ); ?></label>
				</th>

				<td>
					<select id="<?php esc_attr( PF_SLUG . '_' . $module_id . '_enable' ); ?>" name="<?php echo esc_attr( PF_SLUG . '_' . $module_id . '_enable' ); ?>">
						<option value="yes" <?php selected( $enabled, 'yes' ); ?>><?php esc_html_e( 'Yes', 'pressforward' ); ?></option>
						<option value="no" <?php selected( $enabled, 'no' ); ?>><?php esc_html_e( 'No', 'pressforward' ); ?></option>
					</select>
				</td>
			</tr>
		</table>
		<p><?php echo esc_html( $modsetup['description'] ); ?></p>
		<hr />

		<?php
	}

	/**
	 * Handles saves from the module admin page.
	 */
	public function admin_op_page_save() {
		$module_id = $this->id;
		$enabled   = isset( $_POST[ PF_SLUG . '_' . $module_id . '_enable' ] ) && 'no' === $_POST[ PF_SLUG . '_' . $module_id . '_enable' ] ? 'no' : 'yes';
		update_option( PF_SLUG . '_' . $module_id . '_enable', $enabled );
	}

	/**
	 * Sets up admin menu items for modules.
	 *
	 * @param array $admin_menus Admin menus.
	 */
	public function setup_admin_menus( $admin_menus ) {
		$module_id = $this->id;

		$enabled = get_option( PF_SLUG . '_' . $module_id . '_enable' );
		$enabled = 'no' === $enabled ? 'no' : 'yes';

		if ( 'yes' === $enabled ) {
			foreach ( (array) $admin_menus as $admin_menu ) {
				$defaults = array(
					'page_title' => '',
					'menu_title' => '',
					'cap'        => 'edit_posts',
					'slug'       => '',
					'callback'   => '',
				);

				$r = wp_parse_args( $admin_menu, $defaults );

				// add_submenu_page() will fail if any arguments aren't passed.
				if ( empty( $r['page_title'] ) || empty( $r['menu_title'] ) || empty( $r['cap'] ) || empty( $r['slug'] ) || empty( $r['callback'] ) ) {
					continue;
				}

				add_submenu_page( PF_MENU_SLUG, $r['page_title'], $r['menu_title'], $r['cap'], $r['slug'], $r['callback'] );
			}
		}
	}

	/**
	 * Fetch and return a formatted data object - optional.
	 *
	 * @param mixed $the_feed Feed.
	 * @return array
	 */
	public function get_data_object( $the_feed ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		return array();
	}

	/**
	 * Set up dashboard widgets.
	 *
	 * @return array
	 */
	public function pf_add_dash_widgets() {
		$array = array();
		return $array;
	}

	/**
	 * Set up dashboard widget filter.
	 *
	 * @param array $filter_inc_array Filter array.
	 * @return array
	 */
	public function add_dash_widgets_filter( $filter_inc_array ) {
		$client_widgets = $this->pf_add_dash_widgets();
		$all_widgets    = array_merge( $filter_inc_array, $client_widgets );

		return $all_widgets;
	}

	/**
	 * Enqueue admin scripts.
	 */
	public function admin_enqueue_scripts() {}

	/**
	 * Enqueue admin styles.
	 */
	public function admin_enqueue_styles() {}

	/**
	 * Enqueue frontend scripts.
	 */
	public function wp_enqueue_scripts() {}

	/**
	 * Enqueue frontend styles.
	 */
	public function wp_enqueue_styles() {}

	/**
	 * Add to feeder.
	 */
	public function add_to_feeder() {}
}
