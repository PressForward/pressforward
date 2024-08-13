<?php
/**
 * Admin template utilities.
 *
 * @package PressForward
 */

namespace PressForward\Core\Admin;

use PressForward\Interfaces\Templates;
use PressForward\Controllers\PFtoWPUsers as Users;

/**
 * Template class.
 */
class PFTemplater {
	/**
	 * Templates object.
	 *
	 * @access public
	 * @var \PressForward\Interfaces\Templates
	 */
	public $factory;

	/**
	 * Path.
	 *
	 * @access public
	 * @var string
	 */
	public $parts;

	/**
	 * PFtoWPUsers object.
	 *
	 * @access public
	 * @var \PressForward\Controllers\PFtoWPUsers
	 */
	public $users;

	/**
	 * Constructor.
	 *
	 * @param \PressForward\Interfaces\Templates    $template_factory Templates object.
	 * @param \PressForward\Controllers\PFtoWPUsers $users            PFtoWPUsers object.
	 */
	public function __construct( Templates $template_factory, Users $users ) {
		$this->factory = $template_factory;
		$this->parts   = $this->factory->build_path( array( PF_ROOT, 'parts' ), false );
		$this->users   = $users;
	}

	/**
	 * Get a given view (if it exists).
	 *
	 * @param string|array $view The slug of the view.
	 * @param array        $vars Variables passed to template.
	 * @return string
	 */
	public function get_view( $view, $vars = array() ) {
		if ( is_array( $view ) ) {
			$view = $this->factory->build_path( $view, false );
		}

		$view_file = $this->factory->build_path( array( $this->parts, $view . '.tpl.php' ), false );

		if ( isset( $vars['user_ID'] ) && ( true === $vars['user_ID'] ) ) {
			$vars['user_ID'] = $this->users->get_current_user_id();
		}

		if ( ! file_exists( $view_file ) ) {
			if ( defined( 'PF_DEBUG' ) && PF_DEBUG ) {
				pf_log( $view_file, true, false, true );
			}

			return ' ';
		}

		wp_enqueue_style( 'pf-settings-style' );
		wp_enqueue_script( 'pf-settings-tools' );

		// phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		extract( $vars, EXTR_SKIP );
		ob_start();
		include $view_file;
		return ob_get_clean();
	}

	/**
	 * Echoes a view.
	 *
	 * @param string $view View name.
	 * @param array  $vars Variables for use the get_view callback.
	 */
	public function the_view_for( $view, $vars = array() ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $this->get_view( $view, $vars );
	}

	/**
	 * Builds nominate this setting.
	 *
	 * @param string $context Context name.
	 */
	public function nominate_this( $context ) {
		if ( ! $this->users->current_user_can( 'edit_posts' ) ) {
			return;
		}

		$have_you_seen = $this->users->get_user_option( 'have_you_seen_nominate_this' );
		if ( ( 'as_paragraph' === $context ) || ( 'as_feed' === $context ) || ( empty( $have_you_seen ) ) ) {
			$vars = array(
				'context' => $context,
			);
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo $this->get_view( 'nominate-this', $vars );
		} else {
			return;
		}
	}

	/**
	 * Gets a list of permitted tabs for a given page.
	 *
	 * @param string $slug Page slug. Default 'settings'.
	 * @return array
	 */
	public function permitted_tabs( $slug = 'settings' ) {
		if ( 'settings' === $slug ) {
			$permitted_tabs = array(
				'user'         => array(
					'title' => __( 'User Options', 'pressforward' ),
					'cap'   => get_option( 'pf_menu_all_content_access', $this->users->pf_get_defining_capability_by_role( 'contributor' ) ),
				),
				'site'         => array(
					'title' => __( 'Site Options', 'pressforward' ),
					'cap'   => get_option( 'pf_menu_preferences_access', $this->users->pf_get_defining_capability_by_role( 'administrator' ) ),
				),
				'user-control' => array(
					'title' => __( 'User Control', 'pressforward' ),
					'cap'   => get_option( 'pf_menu_preferences_access', $this->users->pf_get_defining_capability_by_role( 'administrator' ) ),
				),
				'modules'      => array(
					'title' => __( 'Module Control', 'pressforward' ),
					'cap'   => get_option( 'pf_menu_preferences_access', $this->users->pf_get_defining_capability_by_role( 'administrator' ) ),
				),
			);
			$permitted_tabs = apply_filters( 'pf_settings_tabs', $permitted_tabs );
		} else {
			$permitted_tabs = array();
			$permitted_tabs = apply_filters( 'pf_tabs_' . $slug, $permitted_tabs );
		}

		return $permitted_tabs;
	}

	/**
	 * Callback to build settings page.
	 *
	 * Does not appear to be used.
	 *
	 * @return string
	 */
	public function the_settings_page() {
		if ( isset( $_GET['tab'] ) ) {
			$tab = sanitize_text_field( wp_unslash( $_GET['tab'] ) );
		} else {
			$tab = 'user';
		}

		$user_ID = get_current_user_id();
		$vars    = array(
			'current'    => $tab,
			'user_ID'    => $user_ID,
			'page_title' => __( 'PressForward Preferences', 'pressforward' ),
			'page_slug'  => 'settings',
		);
		return $this->get_view( $this->factory->build_path( array( 'settings', 'settings-page' ), false ), $vars );
	}

	/**
	 * Builds markup for a group of settings tabs.
	 *
	 * @param string $current   Currently selected tab.
	 * @param string $page_slug Page slug.
	 */
	public function settings_tab_group( $current, $page_slug = 'settings' ) {
		$tabs = $this->permitted_tabs( $page_slug );
		ob_start();
		foreach ( $tabs as $tab => $tab_meta ) {
			if ( current_user_can( $tab_meta['cap'] ) ) {
				if ( $current === $tab ) {
					$class_name = 'pftab tab active';
				} else {
					$class_name = 'pftab tab'; }
				?>
				<div id="<?php echo esc_attr( $tab ); ?>" class="<?php echo esc_attr( $class_name ); ?>">
				<h2 class="title"><?php echo esc_html( $tab_meta['title'] ); ?></h2>
					<?php
						// like: pf_do_pf-add-feeds_tab_primary_feed_type.
					if ( has_action( 'pf_do_' . $page_slug . '_tab_' . $tab ) || ! array_key_exists( $tab, $tabs ) ) {
						do_action( 'pf_do_' . $page_slug . '_tab_' . $tab );
					} else {
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						echo $this->the_settings_tab( $tab, $page_slug );
					}
					?>
				</div>
				<?php
			}
		}

		return ob_get_clean();
	}


	/**
	 * Builds markup for settings tab.
	 *
	 * @param string $tab       Tab key.
	 * @param string $page_slug Slug of page.
	 * @return string
	 */
	public function the_settings_tab( $tab, $page_slug = 'settings' ) {
		$permitted_tabs = $this->permitted_tabs( $page_slug );
		if ( array_key_exists( $tab, $permitted_tabs ) ) {
			$tab = $tab;
		} else {
			return ''; }
		$vars = array(
			'current' => $tab,
		);
		return $this->get_view( array( $page_slug, 'tab-' . $tab ), $vars );
	}

	/**
	 * Sets up the admin submenu page.
	 *
	 * @param string        $parent_slug  See add_submenu_page().
	 * @param string        $page_title   See add_submenu_page().
	 * @param string        $menu_title   See add_submenu_page().
	 * @param string|array  $capability   See add_submenu_page().
	 * @param string        $menu_slug    See add_submenu_page().
	 * @param callable|null $the_function See add_submenu_page().
	 */
	public function add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $the_function = null ) {
		if ( is_array( $capability ) ) {
			$capability = $this->users->user_level( $capability[0], $capability[1] );
		}

		$this->factory->add_submenu_page(
			$parent_slug,
			$page_title,
			$menu_title,
			$capability,
			$menu_slug,
			$the_function
		);
	}

	/**
	 * Sets up the admin menu page.
	 *
	 * @param string       $page_title   See add_menu_page().
	 * @param string       $menu_title   See add_menu_page().
	 * @param string|array $capability   See add_menu_page().
	 * @param string       $menu_slug    See add_menu_page().
	 * @param string       $the_function See add_menu_page().
	 * @param string       $icon_url     See add_menu_page().
	 * @param int          $position     See add_menu_page().
	 */
	public function add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $the_function = '', $icon_url = '', $position = null ) {
		if ( is_array( $capability ) ) {
			$capability = $this->users->user_level( $capability[0], $capability[1] );
		}

		$this->factory->add_menu_page(
			$page_title,
			$menu_title,
			$capability,
			$menu_slug,
			$the_function,
			$icon_url,
			$position
		);
	}

	/**
	 * Builds the markup for the side menu.
	 */
	public function the_side_menu() {
		$user_ID          = get_current_user_id();
		$pf_user_menu_set = get_user_option( 'pf_user_menu_set', $user_ID );
		if ( 'true' === $pf_user_menu_set ) {
			$screen = $this->factory->the_screen();
			$vars   = array(
				'slug'    => $screen['id'],
				'version' => 0,
				'deck'    => false,
			);
			return $this->get_view( 'side-menu', $vars );
		}
	}

	/**
	 * Builds the markup for the search template.
	 *
	 * @return void
	 */
	public function search_template() {
		$php_self     = isset( $_SERVER['PHP_SELF'] ) ? sanitize_text_field( wp_unslash( $_SERVER['PHP_SELF'] ) ) : '';
		$query_string = isset( $_SERVER['QUERY_STRING'] ) ? sanitize_text_field( wp_unslash( $_SERVER['QUERY_STRING'] ) ) : '';
		?>
			<form id="feeds-search" method="post" action="<?php echo esc_attr( basename( $php_self . '?' . $query_string . '&action=post' ) ); ?>">
					<label for="search-terms"><?php esc_html_e( 'Search', 'pressforward' ); ?></label>
				<input type="text" name="search-terms" id="search-terms" placeholder="<?php esc_attr_e( 'Enter search terms', 'pressforward' ); ?>">
				<input type="submit" class="btn btn-small" value="<?php esc_attr_e( 'Search', 'pressforward' ); ?>">
			</form>
		<?php
	}

	/**
	 * Builds the nav bar markup.
	 *
	 * @param string $page Page name.
	 */
	public function nav_bar( $page = 'pf-all-content' ) {
		?>
		<div class="display">
			<div class="pf-btns btn-toolbar">
				<div class="pf-btns-left">
					<?php if ( 'pf-review' !== $page ) { ?>
						<div class="dropdown pf-view-dropdown btn-group" role="group">
							<button class="btn btn-default btn-small dropdown-toggle" type="button" id="dropdownMenu1" data-toggle="dropdown" aria-expanded="true">
								<?php esc_html_e( 'View', 'pressforward' ); ?>
								<span class="caret"></span>
							</button>

							<ul class="dropdown-menu" role="menu" aria-labelledby="dropdownMenu1">
							<?php

							$view_check = get_user_meta( pressforward( 'controller.template_factory' )->user_id(), 'pf_user_read_state', true );

							if ( 'golist' === $view_check ) {
								$this->dropdown_option( __( 'Grid', 'pressforward' ), 'gogrid', 'pf-top-menu-selection display-state' );
								$this->dropdown_option( __( 'List', 'pressforward' ), 'golist', 'pf-top-menu-selection unset display-state' );
							} else {
								$this->dropdown_option( __( 'Grid', 'pressforward' ), 'gogrid', 'pf-top-menu-selection unset display-state' );
								$this->dropdown_option( __( 'List', 'pressforward' ), 'golist', 'pf-top-menu-selection display-state' );
							}

							$pf_user_scroll_switch = get_user_option( 'pf_user_scroll_switch', pressforward( 'controller.template_factory' )->user_id() );

							if ( 'false' === $pf_user_scroll_switch ) {
								$this->dropdown_option( __( 'Infinite Scroll (Reloads Page)', 'pressforward' ), 'goinfinite', 'pf-top-menu-selection scroll-toggler' );
							} else {
								$this->dropdown_option( __( 'Paginate (Reloads Page)', 'pressforward' ), 'gopaged', 'pf-top-menu-selection scroll-toggler' );
							}

							?>
							</ul>
						</div>
					<?php } ?>

					<div class="dropdown pf-filter-dropdown btn-group" role="group">
						<button class="btn btn-default dropdown-toggle btn-small" type="button" id="dropdownMenu2" data-toggle="dropdown" aria-expanded="true">
							<?php esc_html_e( 'Filter', 'pressforward' ); ?>
							<span class="caret"></span>
						</button>

						<ul class="dropdown-menu" role="menu" aria-labelledby="dropdownMenu2">
						<?php
						if ( 'pf-review' !== $page ) {
							$this->dropdown_option( __( 'Reset filter', 'pressforward' ), 'showNormal' );
							$this->dropdown_option( __( 'My starred', 'pressforward' ), 'showMyStarred' );
							$this->dropdown_option( __( 'Show hidden', 'pressforward' ), 'showMyHidden' );
							$this->dropdown_option( __( 'My nominations', 'pressforward' ), 'showMyNominations' );
							$this->dropdown_option( __( 'Unread', 'pressforward' ), 'showUnread' );
							$this->dropdown_option( __( 'Drafted', 'pressforward' ), 'showDrafted' );
						} else {
							if ( isset( $_POST['search-terms'] ) || isset( $_GET['by'] ) || isset( $_GET['pf-see'] ) || isset( $_GET['reveal'] ) ) {
								$this->dropdown_option( __( 'Reset filter', 'pressforward' ), 'showNormalNominations' );
							}
							$this->dropdown_option( __( 'My starred', 'pressforward' ), 'sortstarredonly', 'starredonly', '', '', '', get_admin_url( null, 'admin.php?page=pf-review&pf-see=starred-only' ) );
							$this->dropdown_option( __( 'Only archived', 'pressforward' ), 'showarchiveonly', '', '', '', '', get_admin_url( null, 'admin.php?page=pf-review&pf-see=archive-only' ) );
							$this->dropdown_option( __( 'Unread', 'pressforward' ), 'showUnreadOnly', '', '', '', '', get_admin_url( null, 'admin.php?page=pf-review&pf-see=unread-only' ) );
							$this->dropdown_option( __( 'Drafted', 'pressforward' ), 'showDrafted', '', '', '', '', get_admin_url( null, 'admin.php?page=pf-review&pf-see=drafted-only' ) );

						}
						?>
						</ul>
					</div>

					<div class="dropdown pf-sort-dropdown btn-group" role="group">
						<button class="btn btn-default dropdown-toggle btn-small" type="button" id="dropdownMenu3" data-toggle="dropdown" aria-expanded="true">
							<?php esc_html_e( 'Sort', 'pressforward' ); ?>
							<span class="caret"></span>
						</button>

						<ul class="dropdown-menu" role="menu" aria-labelledby="dropdownMenu3">
							<?php

							$sort_base_url = get_admin_url( null, 'admin.php?page=' . $page );
							if ( isset( $_GET['pf-see'] ) ) {
								$sort_base_url = add_query_arg( 'pf-see', sanitize_text_field( wp_unslash( $_GET['pf-see'] ) ), $sort_base_url );
							}

							$this->dropdown_option( __( 'Reset', 'pressforward' ), 'sort-reset', '', '', '', '', $sort_base_url );
							$this->dropdown_option( __( 'Date of item', 'pressforward' ), 'sortbyitemdate', '', '', '', '', add_query_arg( 'sort-by', 'item-date', $sort_base_url ) );
							$this->dropdown_option( __( 'Date retrieved', 'pressforward' ), 'sortbyfeedindate', '', '', '', '', add_query_arg( 'sort-by', 'feed-in-date', $sort_base_url ) );

							if ( 'pf-review' === $page ) {
								$this->dropdown_option( __( 'Date nominated', 'pressforward' ), 'sortbynomdate', '', '', '', '', add_query_arg( 'sort-by', 'nom-date', $sort_base_url ) );
								$this->dropdown_option( __( 'Nominations received', 'pressforward' ), 'sortbynomcount', '', '', '', '', add_query_arg( 'sort-by', 'nom-count', $sort_base_url ) );
							}
							?>
						</ul>
					</div>

					<div class="btn-group" role="group">
						<a href="https://pressforwardadmin.gitbooks.io/pressforward-documentation/content/" target="_blank" id="pf-help" class="btn btn-small"><?php esc_html_e( 'Need help?', 'pressforward' ); ?></a>
					</div>
				</div>

				<div class="pf-btns-right">
				<!-- or http://thenounproject.com/noun/list/#icon-No9479? -->
					<?php
					if ( function_exists( 'the_alert_box' ) ) {
						add_filter( 'ab_alert_specimens_post_types', array( $this, 'alert_filterer' ) );
						add_filter( 'ab_alert_safe', array( $this, 'alert_safe_filterer' ) );
						$alerts = pressforward( 'library.alertbox' )->get_specimens();
						remove_filter( 'ab_alert_safe', array( $this, 'alert_safe_filterer' ) );
						remove_filter( 'ab_alert_specimens_post_types', array( $this, 'alert_filterer' ) );
					}

					if ( 'pf-review' === $page ) {
						echo '<button type="submit" class="delete btn btn-danger btn-small float-left" id="archivenoms" value="' . esc_attr__( 'Archive all', 'pressforward' ) . '" >' . esc_attr__( 'Archive all', 'pressforward' ) . '</button>';
					}

					$user_ID          = get_current_user_id();
					$pf_user_menu_set = get_user_option( 'pf_user_menu_set', $user_ID );

					if ( 'true' === $pf_user_menu_set ) {
						if ( ! empty( $alerts ) && ( 0 !== $alerts->post_count ) ) {
							echo '<a class="btn btn-small btn-warning" id="gomenu" href="#">' . esc_html__( 'Menu', 'pressforward' ) . ' <i class="icon-tasks"></i> (!)</a>';
						} else {
							echo '<a class="btn btn-small" id="gomenu" href="#">' . esc_html__( 'Menu', 'pressforward' ) . ' <i class="icon-tasks"></i></a>';
						}
					}
						echo '<a class="btn btn-small" id="gofolders" href="#">' . esc_html__( 'Folders', 'pressforward' ) . '</a>';
					?>

				</div>
			</div>
		</div><!-- End btn-group -->
		<?php
	}

	/**
	 * Builds a dropdown option.
	 *
	 * @param string $the_string    Dropdown text.
	 * @param string $id            'id' attribute.
	 * @param string $class_name    Class name.
	 * @param string $form_id       'id' attribute of the form.
	 * @param string $schema_action Schema action attribute.
	 * @param string $schema_class  Schema class attribute.
	 * @param string $href          'href' class attribute.
	 * @param string $target        'target' attribute.
	 */
	public function dropdown_option( $the_string, $id, $class_name = 'pf-top-menu-selection', $form_id = '', $schema_action = '', $schema_class = '', $href = '', $target = '' ) {

		$option  = '<li role="presentation"><a role="menuitem" id="';
		$option .= $id;
		$option .= '" tabindex="-1" class="';
		$option .= $class_name;
		$option .= '"';

		$option .= ' href="';
		if ( ! empty( $href ) ) {
			$option .= esc_attr( $href );
		} else {
			$option .= '#';
		}
		$option .= '"';

		if ( ! empty( $target ) ) {
			$option .= ' target="' . esc_attr( $target ) . '"';
		}

		if ( ! empty( $form_id ) ) {
			$option .= ' data-form="' . esc_attr( $form_id ) . '" ';
		}

		if ( ! empty( $schema_action ) ) {
			$option .= ' pf-schema="' . esc_attr( $schema_action ) . '" ';
		}

		if ( ! empty( $schema_class ) ) {
			$option .= ' pf-schema-class="' . esc_attr( $schema_class ) . '" ';
		}

		$option .= '>';
		$option .= esc_html( $the_string );
		$option .= '</a></li>';

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $option;
	}

	/**
	 * Essentially the PF 'loop' template.
	 * $item = the each of the foreach
	 * $c = count.
	 * $format = format changes, to be used later or by plugins.
	 *
	 * @param array  $item Item info.
	 * @param int    $c         Count.
	 * @param string $format    Format.
	 * @param array  $metadata  Metadata.
	 */
	public function form_of_an_item( $item, $c, $format = 'standard', $metadata = array() ) {
		$current_user = wp_get_current_user();

		if ( '' !== get_option( 'timezone_string' ) ) {
			// Allows plugins to introduce their own item format output.
			// phpcs:ignore WordPress.DateTime.RestrictedFunctions
			date_default_timezone_set( get_option( 'timezone_string' ) );
		}

		if ( has_action( 'pf_output_items' ) ) {
			do_action( 'pf_output_items', $item, $c, $format );
			return;
		}

		$item_tags_array         = is_array( $item['item_tags'] ) ? $item['item_tags'] : explode( ',', $item['item_tags'] );
		$item_tag_classes_string = '';

		$user_id = $current_user->ID;

		foreach ( $item_tags_array as $item_tag ) {
			$item_tag_classes_string .= pf_slugger( $item_tag, true, false, true );
			$item_tag_classes_string .= ' ';
		}

		$modal_hash = $this->get_modal_hash( $item['item_id'] );

		$read_class = '';
		if ( 'nomination' === $format ) {
			$feed_item_id    = $metadata['item_id'];
			$id_for_comments = $metadata['pf_item_post_id']; // orig item post ID.

			$read_stat = pf_get_relationship_value( 'read', $metadata['nom_id'], wp_get_current_user()->ID );
			if ( $read_stat ) {
				$read_class = 'article-read';
			}

			if ( ! isset( $metadata['nom_id'] ) || empty( $metadata['nom_id'] ) ) {
				$metadata['nom_id'] = md5( $item['item_title'] ); }
			if ( empty( $id_for_comments ) ) {
				$id_for_comments = $metadata['nom_id']; }
			if ( empty( $metadata['item_id'] ) ) {
				$metadata['item_id'] = md5( $item['item_title'] ); }
		} else {
			$feed_item_id    = $item['item_id'];
			$id_for_comments = $item['post_id']; // orig item post ID.
		}

		$archive_status = pressforward( 'controller.metas' )->get_post_pf_meta( $id_for_comments, 'pf_archive', true );

		$pf_see = isset( $_GET['pf-see'] ) ? sanitize_text_field( wp_unslash( $_GET['pf-see'] ) ) : false;

		if ( 1 === (int) $archive_status && ( 'archive-only' !== $pf_see ) ) {
			$archived_status_string = 'archived';
			$dependent_style        = 'display:none;';
		} elseif ( ( 'nomination' === $format ) && ( 1 === pressforward( 'controller.metas' )->get_post_pf_meta( $metadata['nom_id'], 'pf_archive', true ) ) && ( 'archive-only' !== $pf_see ) ) {
			$archived_status_string = 'archived';
			$dependent_style        = 'display:none;';
		} else {
			$dependent_style        = '';
			$archived_status_string = 'not-archived';
		}

		if ( 'nomination' === $format ) {
			echo '<article class="feed-item entry nom-container ' . esc_attr( $archived_status_string ) . ' ' . esc_attr( get_pf_nom_class_tags( array( $metadata['submitters'], $metadata['nom_id'], $metadata['item_author'], $metadata['item_tags'], $metadata['item_id'] ) ) ) . ' ' . esc_attr( $read_class ) . '" id="' . esc_attr( $metadata['nom_id'] ) . '" style="' . esc_attr( $dependent_style ) . '" tabindex="' . esc_attr( (string) $c ) . '" pf-post-id="' . esc_attr( $metadata['nom_id'] ) . '" pf-item-post-id="' . esc_attr( $id_for_comments ) . '" pf-feed-item-id="' . esc_attr( $metadata['item_id'] ) . '" pf-schema="read" pf-schema-class="article-read">';
			?>
			<a style="display:none;" name="<?php echo esc_attr( $modal_hash ); ?>"></a>
			<?php
		} else {
			$id_for_comments = $item['post_id'];
			$read_stat       = pf_get_relationship_value( 'read', $id_for_comments, $user_id );
			if ( ! $read_stat ) {
				$read_class = '';
			} else {
				$read_class = 'article-read';
			}

			echo '<article class="feed-item entry ' . esc_attr( pf_slugger( get_the_source_title( $id_for_comments ), true, false, true ) ) . ' ' . esc_attr( $item_tag_classes_string ) . ' ' . esc_attr( $read_class ) . '" id="' . esc_attr( $item['item_id'] ) . '" tabindex="' . esc_attr( (string) $c ) . '" pf-post-id="' . esc_attr( $item['post_id'] ) . '" pf-feed-item-id="' . esc_attr( $item['item_id'] ) . '" pf-item-post-id="' . esc_attr( $id_for_comments ) . '" style="' . esc_attr( $dependent_style ) . '" >';
			?>
			<a style="display:none;" name="<?php echo esc_attr( $modal_hash ); ?>"></a>
			<?php
		}

		if ( empty( $read_stat ) ) {
			$read_stat = pf_get_relationship_value( 'read', $id_for_comments, $user_id );
		}

		echo '<div class="box-controls">';

		if ( current_user_can( 'manage_options' ) ) {
			if ( 'nomination' === $format ) {
				echo '<i class="icon-remove pf-item-remove" pf-post-id="' . esc_attr( $metadata['nom_id'] ) . '" title="' . esc_attr__( 'Delete', 'pressforward' ) . '"></i>';
			} else {
				echo '<i class="icon-remove pf-item-remove" pf-post-id="' . esc_attr( $id_for_comments ) . '" title="' . esc_attr__( 'Delete', 'pressforward' ) . '"></i>';
			}
		}

		if ( 'nomination' !== $format ) {
			$archive_stat  = pf_get_relationship_value( 'archive', $id_for_comments, $user_id );
			$extra_classes = '';

			if ( $archive_stat ) {
				$extra_classes .= ' schema-active relationship-button-active';
			}

			echo '<i class="icon-eye-close hide-item pf-item-archive schema-archive schema-switchable schema-actor' . esc_attr( $extra_classes ) . '" pf-schema-class="relationship-button-active" pf-item-post-id="' . esc_attr( $id_for_comments ) . '" title="' . esc_attr__( 'Hide', 'pressforward' ) . '" pf-schema="archive"></i>';
		}

		if ( ! $read_stat ) {
			$read_class = '';
		} else {
			$read_class = 'marked-read';
		}

		echo '<i class="icon-ok-sign schema-read schema-actor schema-switchable ' . esc_attr( $read_class ) . '" pf-item-post-id="' . esc_attr( $id_for_comments ) . '" pf-schema="read" pf-schema-class="marked-read" title="' . esc_attr__( 'Mark as Read', 'pressforward' ) . '"></i>';

		echo '</div>';

		?>

			<header>
				<?php
				echo '<h1 class="item_title"><a href="#' . esc_attr( $modal_hash ) . '" class="item-expander schema-actor" role="button" data-bs-target="#' . esc_attr( $modal_hash ) . '" data-toggle="modal" data-backdrop="false" pf-schema="read" pf-schema-targets="schema-read">' . esc_html( self::display_a( $item['item_title'], 'title' ) ) . '</a></h1>';
				echo '<p class="source_title">' . esc_html( self::display_a( get_the_source_title( $id_for_comments ), 'source' ) ) . '</p>';
				if ( 'nomination' === $format ) {
					?>
					<div class="sortable-hidden-meta" style="display:none;">
						<?php
						// translators: Unix timestamp from the item's source RSS feed.
						printf( esc_html__( 'UNIX timestamp from source RSS: %s', 'pressforward' ), '<span class="sortable_source_timestamp sortableitemdate">' . esc_html( $metadata['timestamp_item_posted'] ) . '</span>' ) . '<br />';

						// translators: Unix timestamp for the last modified date.
						printf( esc_html__( 'UNIX timestamp last modified: %s', 'pressforward' ), '<span class="sortable_mod_timestamp">' . esc_html( $metadata['timestamp_nom_last_modified'] ) . '</span>' ) . '<br />';

						// translators: Unix timestamp for the date nominated.
						printf( esc_html__( 'UNIX timestamp date nominated: %s', 'pressforward' ), '<span class="sortable_nom_timestamp">' . esc_html( $metadata['timestamp_unix_date_nomed'] ) . '</span>' ) . '<br />';

						// translators: Slug for the origin site.
						printf( esc_html__( 'Slug for origin site: %s', 'pressforward' ), '<span class="sortable_origin_link_slug">' . esc_html( $metadata['source_slug'] ) . '</span>' ) . '<br />';

						// Add an action here for others to provide additional sortables.
						?>
					</div>

					<?php
				}

				// Let's build an info box!
				// http://nicolasgallagher.com/pure-css-speech-bubbles/.
				$source_link = pressforward( 'schema.feed_item' )->get_source_link( $id_for_comments );

				$url_array = wp_parse_url( $source_link );

				if ( ! $url_array || empty( $url_array['host'] ) ) {
					pf_log( 'Could not find the source link for ' . $id_for_comments . ' Got: ' . $source_link );
					$source_link = __( 'Source URL not found.', 'pressforward' );
				} else {
					$source_link = 'http://' . $url_array['host'];
				}

				// http://nicolasgallagher.com/pure-css-speech-bubbles/demo/.
				$ibox = '<div class="feed-item-info-box" id="info-box-' . esc_attr( $item['item_id'] ) . '">';

				$ibox .= "\n";

				// translators: Feed name.
				$ibox .= sprintf( esc_html__( 'Feed: %s', 'pressforward' ), '<span class="feed_title">' . esc_html( get_the_source_title( $id_for_comments ) ) . '</span>' ) . '<br />';

				// translators: Posted date.
				$ibox .= sprintf( esc_html__( 'Posted: %s', 'pressforward' ), '<span class="feed_posted">' . esc_html( gmdate( 'M j, Y; g:ia', strtotime( $item['item_date'] ) ) ) . '</span>' ) . '<br />';

				// translators: Date retrieved.
				$ibox .= sprintf( esc_html__( 'Retrieved: %s', 'pressforward' ), '<span class="item_meta item_meta_added_date">' . esc_html( gmdate( 'M j, Y; g:ia', strtotime( $item['item_added_date'] ) ) ) . '</span>' ) . '<br />';

				// translators: Author names.
				$ibox .= sprintf( esc_html__( 'Authors: %s', 'pressforward' ), '<span class="item_authors">' . esc_html( $item['item_author'] ) . '</span>' ) . '<br />';

				// translators: Link to source publication.
				$ibox .= sprintf( esc_html__( 'Origin: %s', 'pressforward' ), '<span class="source_name"><a target ="_blank" href="' . esc_attr( $source_link ) . '">' . esc_html( $source_link ) . '</a></span>' ) . '<br />';

				// translators: Link to original item.
				$ibox .= sprintf( esc_html__( 'Original Item: %s', 'pressforward' ), '<span class="source_link"><a href="' . esc_attr( $item['item_link'] ) . '" class="item_url" target ="_blank">' . esc_html( $item['item_title'] ) . '</a></span>' ) . '<br />';

				// translators: Source item tags.
				$ibox .= sprintf( esc_html__( 'Tags: %s', 'pressforward' ), '<span class="item_tags">' . esc_html( implode( ',', $item_tags_array ) ) . '</span>' ) . '<br />';

				// translators: Number of times repeated in source.
				$ibox .= sprintf( esc_html__( 'Times repeated in source: %s', 'pressforward' ), '<span class="feed_repeat sortable_sources_repeat">' . esc_html( $item['source_repeat'] ) . '</span>' ) . '<br />';

				if ( 'nomination' === $format ) {
					// translators: Nominator count.
					$ibox .= sprintf( esc_html__( 'Number of nominations received: %s', 'pressforward' ), '<span class="sortable_nom_count">' . esc_html( $metadata['nom_count'] ) . '</span>' ) . '<br />';
					// translators: Name of first nominator.
					$ibox .= sprintf( esc_html__( 'First submitted by: %s', 'pressforward' ), '<span class="first_submitter">' . esc_html( $metadata['submitters'] ) . '</span>' ) . '<br />';

					// translators: Date and time of nomination.
					$ibox .= sprintf( esc_html__( 'Nominated on: %s', 'pressforward' ), '<span class="nominated_on">' . esc_html( gmdate( 'M j, Y; g:ia', strtotime( $metadata['date_nominated'] ) ) ) . '</span>' ) . '<br />';

					// translators: names of nominating users.
					$ibox .= sprintf( esc_html__( 'Nominated by: %s', 'pressforward' ), '<span class="nominated_by">' . esc_html( get_the_nominating_users() ) . '</span>' ) . '<br />';
				}

				$draft_id = pf_is_drafted( $feed_item_id );
				if ( ! $draft_id && ( current_user_can( 'edit_post', $draft_id ) ) ) {
					// http://codex.wordpress.org/Function_Reference/edit_post_link.
					$edit_url = get_edit_post_link( $draft_id );
					$ibox    .= '<br /><a class="edit_draft_from_info_box" href="' . esc_attr( $edit_url ) . '">' . esc_html__( 'Edit the draft based on this post.', 'pressforward' ) . '</a><br/>';
				}

				$ibox .= '</div>';

				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo $ibox;
				?>

				<script type="text/javascript">
					var pop_title_<?php echo esc_js( $item['item_id'] ); ?> = '';
					var pop_html_<?php echo esc_js( $item['item_id'] ); ?> = jQuery('#<?php echo esc_js( 'info-box-' . $item['item_id'] ); ?>');
				</script>

				<?php $this->form_of_actions_btns( $item, $c, false, $format, $metadata, $id_for_comments ); ?>
			</header>

			<div class="content">
				<?php
				if ( ( '' !== $item['item_feat_img'] ) && ( 'nomination' !== $format ) ) {
					echo '<div style="float:left; margin-right: 10px; margin-bottom: 10px;"><img src="' . esc_attr( $item['item_feat_img'] ) . '"></div>';
				}

				?>
				<div style="display:none;">
				<?php
					// translators: 1. Publication date; 2. Link to item author.
					echo '<div class="item_meta item_meta_date">' . sprintf( esc_html__( 'Published on %1$s by %2$s.', 'pressforward' ), esc_html( $item['item_date'] ), '<span class="item-authorship">' . esc_html( $item['item_author'] ) . '</span>' ) . '</div>';

					printf(
						// translators: 1. Unix timestamp for item date; 2. Unix timestamp for date added to feed.
						esc_html__( 'Unix timestamp for item date: %1$s. Unix timestamp for date added to feed: %2$s', 'pressforward' ),
						'<span class="sortableitemdate">' . esc_html( (string) strtotime( $item['item_date'] ) ) . '</span>',
						'<span class="sortablerssdate">' . esc_html( (string) strtotime( $item['item_added_date'] ) ) . '</span>.'
					);
				?>
				</div>
				<?php

				echo '<div class="item_excerpt" id="excerpt' . esc_attr( (string) $c ) . '">';
				if ( 'nomination' === $format ) {
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo '<p>' . pf_noms_excerpt( $item['item_content'] ) . '</p>';
				} else {
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo '<p>' . self::display_a( pf_feed_excerpt( $item['item_content'] ), 'graf' ) . '</p>';
				}
					echo '</div>';

				?>
			</div><!-- End content -->
			<footer>
				<p class="pubdate"><?php echo esc_html( gmdate( 'F j, Y; g:i a', strtotime( $item['item_date'] ) ) ); ?></p>
			</footer>
			<?php
				// Allows plugins to introduce their own item format output.
			if ( has_action( 'pf_output_modal' ) ) {
				do_action( 'pf_output_modal', $item, $c, $format );

			} else {
				?>
			<!-- Begin Modal -->
			<div id="<?php echo esc_attr( $modal_hash ); ?>" class="modal hide fade pfmodal" tabindex="-1" role="dialog" aria-labelledby="<?php echo esc_attr( $modal_hash ); ?>-label" aria-hidden="true" pf-item-id="<?php echo esc_attr( $item['item_id'] ); ?>" pf-post-id="<?php echo esc_attr( $item['post_id'] ); ?>" pf-readability-status="<?php echo esc_attr( $item['readable_status'] ); ?>">
				<div class="modal-dialog">
					<div class="modal-header">
						<div class="modal-header-left">
							<div class="modal-mobile-nav float-right d-none d-sm-block d-md-none">
								<div class="mobile-goPrev float-left"></div>
								<div class="mobile-goNext float-right"></div>
							</div>

							<h3 id="<?php echo esc_html( $modal_hash ); ?>-label" class="modal_item_title"><?php echo esc_html( $item['item_title'] ); ?></h3>
							<?php
								echo '<em>' . esc_html__( 'Source', 'pressforward' ) . ': ' . esc_html( get_the_source_title( $id_for_comments ) ) . '</em> | ';
								echo esc_html__( 'Author', 'pressforward' ) . ': ' . esc_html( get_the_item_author( $id_for_comments ) );
							?>
						</div>

						<button type="button" class="btn-close float-right" data-bs-dismiss="modal" aria-label="<?php esc_attr_e( 'Close', 'pressforward' ); ?>"></button>
					</div><!-- .modal-header -->

					<div class="row modal-body-row">
						<div class="modal-body single-item-modal-content col-9" id="modal-body-<?php echo esc_attr( $item['item_id'] ); ?>">
							<div class="readability-wait"></div>
							<div class="main-text">
								<?php
								$content_obj = pressforward( 'library.htmlchecker' );
								$text        = $content_obj->closetags( $item['item_content'] );
								$text        = apply_filters( 'the_content', $text );

								$embed = $this->show_embed( $id_for_comments );
								if ( $embed ) {
									// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									echo $embed;
								}
								// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								echo $text;
								?>
							</div>
						</div>

						<div class="modal-sidebar col-3 hidden-tablet">
							<div class="goPrev modal-side-item row-fluid"></div>
							<div class="modal-comments modal-side-item row-fluid"></div>
							<div class="goNext modal-side-item row-fluid"></div>
						</div>
					</div><!-- .modal-body-row -->

					<div class="modal-footer">
						<div class="footer-top">
							<div class="original-link">
								<a target="_blank" href="<?php echo esc_attr( $item['item_link'] ); ?>"><?php esc_html_e( 'Read Original', 'pressforward' ); ?></a> | <a class="modal-readability-reset" target="#readable" href="<?php echo esc_attr( $item['item_link'] ); ?>" pf-item-id="<?php echo esc_attr( $item['item_id'] ); ?>" pf-post-id="<?php echo esc_attr( $item['post_id'] ); ?>" pf-modal-id="#<?php echo esc_attr( $modal_hash ); ?>"><?php esc_html_e( 'Reset Readability', 'pressforward' ); ?></a>
							</div>

							<div class="footer-actions">
								<?php
								$this->form_of_actions_btns( $item, $c, true, $format, $metadata, $id_for_comments );
								?>
							</div>
						</div><!-- .row-fluid -->

						<div class="footer-bottom">
							<?php
								// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								echo '<strong>' . esc_attr__( 'Item Tags', 'pressforward' ) . '</strong>: ' . implode( ', ', $item_tags_array );
							?>
						</div>
					</div><!-- .modal-footer -->
				</div><!-- .modal-dialog -->
			</div><!-- .modal -->
			<!-- End Modal -->
			<!-- pf_output_additional_modals -->
				<?php
			}
			do_action( 'pf_output_additional_modals', $item, $c, $format );
			?>
		<!-- End pf_output_additional_modals -->
		</article>
		<!-- End article -->
		<?php
	}

	/**
	 * Prep an item element for display based on position and element.
	 * Establishes the rules for item display.
	 * Position should be title, source, graf.
	 *
	 * @param string $the_string String to prepare.
	 * @param string $position   Position. 'source', 'title', 'graf'.
	 * @param string $page       Not used.
	 * @return string
	 */
	public function display_a( $the_string, $position = 'source', $page = 'list' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$title_ln_length = 30;
		$title_lns       = 3;

		$source_ln_length = 48;
		$source_lns       = 2;

		$graf_ln_length = 44;
		$graf_lns       = 4;

		$max = 0;

		switch ( $position ) {
			case 'title':
				$max = $title_ln_length * $title_lns;
				break;
			case 'source':
				$max = $source_ln_length * $source_lns;
				break;
			case 'graf':
				$max = $graf_ln_length * $graf_lns;
				break;
		}

		$cut       = substr( $the_string, 0, $max + 1 );
		$final_cut = substr( $cut, 0, -4 );
		if ( strlen( $cut ) < $max ) {
			$cut = substr( $the_string, 0, $max );
			return $cut;
		} else {
			$cut = $final_cut . ' ...';
			return $cut;
		}
	}

	/**
	 * Gets the URL for a Twitter intent link.
	 *
	 * @param int $id ID of the local item.
	 * @return string
	 */
	public function tweet_intent( $id ) {
		$url  = 'https://twitter.com/intent/tweet?';
		$url .= 'text=' . rawurlencode( get_the_title( $id ) );
		$url .= '&url=' . rawurlencode( get_the_item_link( $id ) );
		$url .= '&via=' . rawurlencode( 'pressfwd' );
		return $url;
	}

	/**
	 * Generates markup for action buttons.
	 *
	 * @param array  $item            Item data.
	 * @param mixed  $c               Not used.
	 * @param bool   $modal           Whether we are in the modal.
	 * @param string $format          Format.
	 * @param array  $metadata        Metadata array.
	 * @param int    $id_for_comments Optional. Item ID.
	 */
	public function form_of_actions_btns( $item, $c, $modal = false, $format = 'standard', $metadata = array(), $id_for_comments = null ) {
		$item_id = 0;
		$user    = wp_get_current_user();
		$user_id = $user->ID;

		if ( 'nomination' === $format ) {
			$item_id = $metadata['item_id'];
		} else {
			$item_id = $item['item_id'];
		}

		$pf_url = defined( 'PF_URL' ) ? (string) PF_URL : '';

		$btns_classes = [ 'actions', 'pf-btns' ];

		if ( $modal ) {
			$btns_classes[] = 'modal-btns';
		} else {
			$btns_classes[] = 'article-btns';
		}

		?>
				<div class="<?php echo esc_attr( implode( ' ', $btns_classes ) ); ?>">
					<?php
					$info_pop         = 'top';
					$info_modal_class = ' modal-popover';
					if ( ! $modal ) {
						$info_modal_class = '';
						if ( 'nomination' === $format ) {
							?>
							<form name="form-<?php echo esc_attr( $metadata['item_id'] ); ?>" pf-form="<?php echo esc_attr( $metadata['item_id'] ); ?>">
							<?php
							pf_prep_item_for_submit( $metadata );
							wp_nonce_field( 'nomination', PF_SLUG . '_nomination_nonce', false );
						} else {
							echo '<form name="form-' . esc_attr( $item['item_id'] ) . '">'
							. '<div class="nominate-result-' . esc_attr( $item['item_id'] ) . '">'
							. '<img class="loading-' . esc_attr( $item['item_id'] ) . '" src="' . esc_attr( $pf_url ) . 'assets/images/ajax-loader.gif" alt="' . esc_attr__( 'Loading', 'pressforward' ) . '..." style="display: none" />'
							. '</div>';
							pf_prep_item_for_submit( $item );
							wp_nonce_field( 'nomination', PF_SLUG . '_nomination_nonce', false );
						}
						echo '</form>';
					}

					// Perhaps use http://twitter.github.com/bootstrap/javascript.html#popovers instead?
					echo '<button class="btn btn-small itemInfobutton" data-toggle="tooltip" title="' . esc_attr__( 'Info', 'pressforward' ) . '" id="info-' . esc_attr( $item['item_id'] ) . '-' . esc_attr( $info_pop ) . '" data-placement="' . esc_attr( $info_pop ) . '" data-class="info-box-popover' . esc_attr( $info_modal_class ) . '" data-title="" data-target="' . esc_attr( $item['item_id'] ) . '"><i class="icon-info-sign"></i></button>';

					if ( pf_is_item_starred_for_user( $id_for_comments, $user_id ) ) {
						echo '<!-- item_id selected = ' . esc_html( $item_id ) . ' -->';
						echo '<button class="btn btn-small star-item btn-warning" data-toggle="tooltip" title="' . esc_attr__( 'Star', 'pressforward' ) . '"><i class="icon-star"></i></button>';
					} else {
						echo '<button class="btn btn-small star-item" data-toggle="tooltip" title="' . esc_attr__( 'Star', 'pressforward' ) . '"><i class="icon-star"></i></button>';
					}

					if ( has_action( 'pf_comment_action_button' ) ) {
						$comment_modal_call = '#modal-comments-' . $item['item_id'];
						$comment_set        = array(
							'id'          => $id_for_comments,
							'modal_state' => $modal,
						);

						do_action( 'pf_comment_action_button', $comment_set );
					}

					if ( 'nomination' === $format ) {
						$nom_count_classes     = 'btn btn-small nom-count';
						$metadata['nom_count'] = get_the_nomination_count();
						if ( $metadata['nom_count'] > 0 ) {
							$nom_count_classes .= ' btn-info';
						}

						echo '<a class="' . esc_attr( $nom_count_classes ) . '" data-toggle="tooltip" title="' . esc_attr__( 'Nomination Count', 'pressforward' ) . '" form="' . esc_attr( $metadata['nom_id'] ) . '">' . esc_html( $metadata['nom_count'] ) . '<i class="icon-play"></i></button></a>';
						$archive_status = '';
						if ( 1 === pressforward( 'controller.metas' )->get_post_pf_meta( $metadata['nom_id'], 'pf_archive', true ) ) {
							$archive_status = 'btn-warning';
						}
						echo '<a class="btn btn-small nom-to-archive schema-switchable schema-actor ' . esc_attr( $archive_status ) . '" pf-schema="archive" pf-schema-class="archived" pf-schema-class="btn-warning" data-toggle="tooltip" title="' . esc_attr__( 'Archive', 'pressforward' ) . '" form="' . esc_attr( $metadata['nom_id'] ) . '"><img src="' . esc_attr( $pf_url ) . 'assets/images/archive.png" /></button></a>';
						$draft_status = '';
						if ( ( 1 === pf_get_relationship_value( 'draft', $metadata['nom_id'], $user_id ) ) || ( 1 === pf_get_relationship_value( 'draft', $id_for_comments, $user_id ) ) ) {
							$draft_status = 'btn-success';
						}
						echo '<a href="#nominate" class="btn btn-small nom-to-draft schema-actor ' . esc_attr( $draft_status ) . '" pf-schema="draft" pf-schema-class="btn-success" form="' . esc_attr( $metadata['item_id'] ) . '" data-original-title="' . esc_attr__( 'Draft', 'pressforward' ) . '"><img src="' . esc_attr( $pf_url ) . 'assets/images/pressforward-licon.png" /></a>';
						$meta_handling    = get_option( PF_SLUG . '_advanced_meta_handling', 'no' );
						$user_level_check = current_user_can( pressforward( 'controller.users' )->pf_get_defining_capability_by_role( 'administrator' ) );
						if ( 'yes' === $meta_handling && $user_level_check ) {
							echo '<a role="button" class="btn btn-small meta_form_modal-button" data-toggle="modal" href="#meta_form_modal_' . esc_attr( $item['post_id'] ) . '" data-post-id="' . esc_attr( $item['post_id'] ) . '" id="meta_form_modal_expander-' . esc_attr( $item['post_id'] ) . '" data-original-title="' . esc_attr__( 'Edit Metadata', 'pressforward' ) . '"><i class="icon-meta-form"></i></a>';
						}
					} elseif ( ( 1 === pf_get_relationship_value( 'nominate', $id_for_comments, $user_id ) ) || ( 1 === pf_get_relationship_value( 'draft', $id_for_comments, $user_id ) ) ) {
						echo '<button class="btn btn-small nominate-now btn-success schema-actor schema-switchable" pf-schema="nominate" pf-schema-class="btn-success" form="' . esc_attr( $item['item_id'] ) . '" data-original-title="' . esc_attr__( 'Nominated', 'pressforward' ) . '"><img src="' . esc_attr( $pf_url ) . 'assets/images/pressforward-single-licon.png" /></button>';
						// Add option here for admin-level users to send items direct to draft.
					} else {
						echo '<button class="btn btn-small nominate-now schema-actor schema-switchable" pf-schema="nominate" pf-schema-class="btn-success" form="' . esc_attr( $item['item_id'] ) . '" data-original-title="' . esc_attr__( 'Nominate', 'pressforward' ) . '"><img src="' . esc_attr( $pf_url ) . 'assets/images/pressforward-single-licon.png" /></button>';
						// Add option here for admin-level users to send items direct to draft.
					}

					$amplify_group_classes = 'dropdown btn-group amplify-group';
					$amplify_id            = 'amplify-' . $item['item_id'];

					if ( $modal ) {
						$amplify_group_classes .= ' dropup';
						$amplify_id            .= '-modal';
					}
					?>
					<div class="<?php echo esc_attr( $amplify_group_classes ); ?>" role="group">
						<button type="button" class="btn btn-default btn-small dropdown-toggle pf-amplify" data-toggle="dropdown" aria-expanded="true" id="<?php echo esc_attr( $amplify_id ); ?>"><i class="icon-bullhorn"></i><span class="caret"></button>
						<ul class="dropdown-menu dropdown-menu-right" role="menu" aria-labelledby="amplify-<?php echo esc_attr( $item['item_id'] ); ?>">
							<?php
							if ( current_user_can( 'edit_others_posts' ) && 'nomination' !== $format ) {
								$send_to_draft_classes = 'amplify-option amplify-draft schema-actor';

								if ( 1 === pf_get_relationship_value( 'draft', $id_for_comments, $user_id ) ) {
									$send_to_draft_classes .= ' btn-success';
								}

								self::dropdown_option( __( 'Send to ', 'pressforward' ) . ucwords( get_option( PF_SLUG . '_draft_post_status', 'draft' ) ), 'amplify-draft-' . $item['item_id'], $send_to_draft_classes, $item['item_id'], 'draft', 'btn-success' );

								?>
								<li class="divider"></li>
								<?php
							}
								$tweet_intent = self::tweet_intent( $id_for_comments );
								self::dropdown_option( __( 'Tweet', 'pressforward' ), 'amplify-tweet-' . $item['item_id'], 'amplify-option', $item['item_id'], '', '', $tweet_intent, '_blank' );
								do_action( 'pf_amplify_buttons' );
							?>
						</ul>
					</div>

					<?php
					if ( true === $modal ) {
						?>
						<button class="btn btn-small" data-bs-dismiss="modal" aria-hidden="true"><?php esc_html_e( 'Close', 'pressforward' ); ?></button>
						<?php
					}
					?>
				</div>
		<?php

		if ( has_action( 'pf_comment_action_modal' ) ) {
			$comment_modal_call = '#modal-comments-' . $item['item_id'];
			$comment_set        = array(
				'id'          => $id_for_comments,
				'modal_state' => $modal,
			);

			do_action( 'pf_comment_action_modal', $comment_set );
		}
	}

	/**
	 * Gets the embed content for an item.
	 *
	 * @param int $id_for_comments Post ID.
	 * @return string
	 */
	public function show_embed( $id_for_comments ) {
		$item_link = pressforward( 'controller.metas' )->get_post_pf_meta( $id_for_comments, 'item_link' );
		return pressforward( 'controller.readability' )->get_embed( $item_link );
	}

	/**
	 * Gets the modal URL for nomination or feed item.
	 *
	 * @param string $pf_item_id PF item ID.
	 * @return string
	 */
	public function get_modal_url( $pf_item_id ) {
		$base = add_query_arg(
			'page',
			'pf-review',
			admin_url( 'admin.php' )
		);

		return $base . '#' . $this->get_modal_hash( $pf_item_id );
	}

	/**
	 * Gets the hash value for a nomination or feed item modal URL.
	 *
	 * @param string $pf_item_id PF item ID.
	 * @return string
	 */
	public function get_modal_hash( $pf_item_id ) {
		return 'modal-' . $pf_item_id;
	}
}
