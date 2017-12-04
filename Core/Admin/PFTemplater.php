<?php
namespace PressForward\Core\Admin;

use PressForward\Interfaces\Templates as Templates;
use PressForward\Interfaces\SystemUsers as SystemUsers;
class PFTemplater {

	public function __construct( Templates $template_factory, SystemUsers $users ) {
		$this->factory = $template_factory;
		$this->parts = $this->factory->build_path( array( PF_ROOT, 'parts' ), false );
		$this->users = $users;
	}

	/**
	 * Get a given view (if it exists)
	 *
	 * @param string $view      The slug of the view
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
		// if (WP_DEBUG){ var_dump( $view_file ); }
		if ( ! file_exists( $view_file ) ) {
			if ( PF_DEBUG ) { pf_log( $view_file, true, false, true ); }
			return ' ';
		}
		extract( $vars, EXTR_SKIP );
		ob_start();
		include $view_file;
		return ob_get_clean();
	}

	public function the_view_for( $view, $vars = array() ) {
		echo $this->get_view( $view, $vars );
	}

	public function nominate_this( $context ) {
		if ( $this->users->current_user_can( 'edit_posts' ) ) :

			$have_you_seen = $this->users->get_user_option( 'have_you_seen_nominate_this' );
			if ( ('as_paragraph' == $context) || ('as_feed' == $context) || (empty( $have_you_seen )) ) {
					$vars = array(
						'context'	=> $context,
					);
					echo $this->get_view( 'nominate-this', $vars );
			} else {
				return;
			}
		endif;

		return;
	}


	public function permitted_tabs( $slug = 'settings' ) {
		if ( 'settings' == $slug ) {
			$permitted_tabs = array(
						'user' => array(
											'title' => __( 'User Options', 'pf' ),
											'cap'  => get_option( 'pf_menu_all_content_access', $this->users->pf_get_defining_capability_by_role( 'contributor' ) ),
										),
						'site' => array(
											'title' => __( 'Site Options', 'pf' ),
											'cap'  => get_option( 'pf_menu_preferences_access', $this->users->pf_get_defining_capability_by_role( 'administrator' ) ),
										),
						'user-control' => array(
											'title' => __( 'User Control', 'pf' ),
											'cap'  => get_option( 'pf_menu_preferences_access', $this->users->pf_get_defining_capability_by_role( 'administrator' ) ),
										),
						'modules' => array(
											'title' => __( 'Module Control', 'pf' ),
											'cap'  => get_option( 'pf_menu_preferences_access', $this->users->pf_get_defining_capability_by_role( 'administrator' ) ),
										),
					);
			$permitted_tabs = apply_filters( 'pf_settings_tabs', $permitted_tabs );
		} else {
			$permitted_tabs = array();
			$permitted_tabs = apply_filters( 'pf_tabs_' . $slug, $permitted_tabs );
		}
		return $permitted_tabs;
	}

	public function the_settings_page() {
		if ( isset( $_GET['tab'] ) ) { $tab = $_GET['tab'];
		} else { $tab = 'user'; }
		$user_ID = get_current_user_id();
		$vars = array(
				'current'		=> $tab,
				'user_ID'		=> $user_ID,
				'page_title'	=> __( 'PressForward Preferences', 'pf' ),
				'page_slug'		=> 'settings',
			);
		return $this->get_view( $this->factory->build_path( array( 'settings', 'settings-page' ), false ), $vars );
	}

	public function settings_tab_group( $current, $page_slug = 'settings' ) {
		// var_dump($page_slug); die();
		$tabs = $this->permitted_tabs( $page_slug );
		// var_dump($page_slug); die();
		ob_start();
		foreach ( $tabs as $tab => $tab_meta ) {
			// var_dump( 'pf_do_'.$page_slug.'_tab_'.$tab ); //die();
			if ( current_user_can( $tab_meta['cap'] ) ) {
				if ( $current == $tab ) { $class = 'pftab tab active';
				} else { $class = 'pftab tab'; }
				?>
				<div id="<?php echo $tab; ?>" class="<?php echo $class; ?>">
	            <h2><?php echo $tab_meta['title']; ?></h2>
		            <?php
						// like: pf_do_pf-add-feeds_tab_primary_feed_type
					if ( has_action( 'pf_do_' . $page_slug . '_tab_' . $tab ) || ! array_key_exists( $tab, $tabs ) ) {
						// var_dump('pf_do_'.$page_slug.'_tab_'.$tab); die();
						// var_dump( 'pf_do_'.$page_slug.'_tab_'.$tab );
						do_action( 'pf_do_' . $page_slug . '_tab_' . $tab );
					} else {
						// var_dump( 'pf_do_'.$page_slug.'_tab_'.$tab );
						// var_dump('pf_do_'.$page_slug.'_tab_'.$tab); //die();
						echo $this->the_settings_tab( $tab, $page_slug );
					}
					?>
				</div>
				<?php
			}
		}

		return ob_get_clean();
	}


	public function the_settings_tab( $tab, $page_slug = 'settings' ) {
		$permitted_tabs = $this->permitted_tabs( $page_slug );
		if ( array_key_exists( $tab, $permitted_tabs ) ) { $tab = $tab;
		} else { return ''; }
		$vars = array(
				'current'		=> $tab,
			);
		// var_dump('<pre>');
		// var_dump(debug_backtrace());
		// var_dump($page_slug.' - '.$tab); die();
		return $this->get_view( array( $page_slug, 'tab-' . $tab ), $vars );
	}

	public function add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function = '' ) {
		if ( is_array( $capability ) ) {
			$capability = $this->users->user_level( $capability[0], $capability[1] );
		}
		$this->factory->add_submenu_page(
			$parent_slug,
			$page_title,
			$menu_title,
			$capability,
			$menu_slug,
			$function
		);
	}

	public function add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function = '', $icon_url = '', $position = null ) {
		if ( is_array( $capability ) ) {
			$capability = $this->users->user_level( $capability[0], $capability[1] );
		}
		$this->factory->add_menu_page(
			$page_title,
			$menu_title,
			$capability,
			$menu_slug,
			$function,
			$icon_url,
			$position
		);
	}

	public function the_side_menu() {
		$user_ID = get_current_user_id();
		$pf_user_menu_set = get_user_option( 'pf_user_menu_set', $user_ID );
		if ( 'true' == $pf_user_menu_set ) {
			$screen = $this->factory->the_screen;
			$vars = array(
					'slug'		=> $screen['id'],
					'version'	=> 0,
					'deck'		=> false,
				);
			return $this->get_view( 'side-menu', $vars );
		}

		return;

	}

	public function search_template() {
		?>
			<form id="feeds-search" method="post" action="<?php echo basename( $_SERVER['PHP_SELF'] ) . '?' . $_SERVER['QUERY_STRING'] . '&action=post'; ?>">
					<label for="search-terms">Search</label>
				<input type="text" name="search-terms" id="search-terms" placeholder="Enter search terms">
				<input type="submit" class="btn btn-small" value="Search">
			</form>
		<?php
	}

	public function nav_bar( $page = 'pf-menu' ) {
		?>
		<div class="display">
			<div class="pf-btns pull-left btn-toolbar">
				<?php if ( 'pf-review' != $page ) { ?>
					<div class="dropdown pf-view-dropdown btn-group" role="group">
					  <button class="btn btn-default btn-small dropdown-toggle" type="button" id="dropdownMenu1" data-toggle="dropdown" aria-expanded="true">
						<?php _e( 'View', 'pf' ); ?>
						<span class="caret"></span>
					  </button>
						<ul class="dropdown-menu" role="menu" aria-labelledby="dropdownMenu1">
						<?php
							$view_check = get_user_meta( pressforward( 'controller.template_factory' )->user_id(), 'pf_user_read_state', true );
						if ( 'golist' == $view_check ) {
							$this->dropdown_option( __( 'Grid', 'pf' ), 'gogrid', 'pf-top-menu-selection display-state' );
							$this->dropdown_option( __( 'List', 'pf' ), 'golist', 'pf-top-menu-selection unset display-state' );
						} else {
							$this->dropdown_option( __( 'Grid', 'pf' ), 'gogrid', 'pf-top-menu-selection unset display-state' );
							$this->dropdown_option( __( 'List', 'pf' ), 'golist', 'pf-top-menu-selection display-state' );
						}
							$pf_user_scroll_switch = get_user_option( 'pf_user_scroll_switch', pressforward( 'controller.template_factory' )->user_id() );
							// empty or true
						if ( 'false' == $pf_user_scroll_switch ) {
							$this->dropdown_option( __( 'Infinite Scroll (Reloads Page)', 'pf' ), 'goinfinite', 'pf-top-menu-selection scroll-toggler' );
						} else {
							$this->dropdown_option( __( 'Paginate (Reloads Page)', 'pf' ), 'gopaged', 'pf-top-menu-selection scroll-toggler' );
						}

						?>
						 </ul>
					</div>
				<?php } ?>
				<div class="dropdown pf-filter-dropdown btn-group" role="group">
				  <button class="btn btn-default dropdown-toggle btn-small" type="button" id="dropdownMenu2" data-toggle="dropdown" aria-expanded="true">
					<?php _e( 'Filter', 'pf' ); ?>
					<span class="caret"></span>
				  </button>
				  <ul class="dropdown-menu" role="menu" aria-labelledby="dropdownMenu2">
					<?php
					if ( 'pf-review' != $page ) {
						$this->dropdown_option( __( 'Reset filter', 'pf' ), 'showNormal' );
						$this->dropdown_option( __( 'My starred', 'pf' ), 'showMyStarred' );
						$this->dropdown_option( __( 'Show hidden', 'pf' ), 'showMyHidden' );
						$this->dropdown_option( __( 'My nominations', 'pf' ), 'showMyNominations' );
						$this->dropdown_option( __( 'Unread', 'pf' ), 'showUnread' );
						$this->dropdown_option( __( 'Drafted', 'pf' ), 'showDrafted' );
					} else {
						if ( isset( $_POST['search-terms'] ) || isset( $_GET['by'] ) || isset( $_GET['pf-see'] ) || isset( $_GET['reveal'] ) ) {
							$this->dropdown_option( __( 'Reset filter', 'pf' ), 'showNormalNominations' );
						}
						$this->dropdown_option( __( 'My starred', 'pf' ), 'sortstarredonly', 'starredonly', null, null, null, get_admin_url( null, 'admin.php?page=pf-review&pf-see=starred-only' ) );
						//$this->dropdown_option( __( 'Toggle visibility of archived', 'pf' ), 'showarchived' );
						$this->dropdown_option( __( 'Only archived', 'pf' ), 'showarchiveonly', null, null, null, null, get_admin_url( null, 'admin.php?page=pf-review&pf-see=archive-only' ) );
						$this->dropdown_option( __( 'Unread', 'pf' ), 'showUnreadOnly', null, null, null, null, get_admin_url( null, 'admin.php?page=pf-review&pf-see=unread-only' ) );
						$this->dropdown_option( __( 'Drafted', 'pf' ), 'showDrafted', null, null, null, null, get_admin_url( null, 'admin.php?page=pf-review&pf-see=drafted-only' ) );

					}
					?>
				  </ul>
				</div>
				<div class="dropdown pf-sort-dropdown btn-group" role="group">
				  <button class="btn btn-default dropdown-toggle btn-small" type="button" id="dropdownMenu3" data-toggle="dropdown" aria-expanded="true">
					<?php _e( 'Sort', 'pf' ); ?>
					<span class="caret"></span>
				  </button>
				  <ul class="dropdown-menu" role="menu" aria-labelledby="dropdownMenu3">
					<?php
						$this->dropdown_option( __( 'Reset', 'pf' ), 'sort-reset' );
						$this->dropdown_option( __( 'Date of item', 'pf' ), 'sortbyitemdate' );
						$this->dropdown_option( __( 'Date retrieved', 'pf' ), 'sortbyfeedindate' );
					if ( 'pf-review' == $page ) {
						$this->dropdown_option( __( 'Date nominated', 'pf' ), 'sortbynomdate' );
						$this->dropdown_option( __( 'Nominations received', 'pf' ), 'sortbynomcount' );
					}
					?>
					<?php // <li role="presentation"><a role="menuitem" tabindex="-1" href="#">Feed name</a></li> ?>
				  </ul>
				</div>
				<div class="btn-group" role="group">
					<a href="https://pressforwardadmin.gitbooks.io/pressforward-documentation/content/" target="_blank" id="pf-help" class="btn btn-small"><?php _e( 'Need help?', 'pf' ); ?></a>
				</div>
			</div>

			<div class="pull-right text-right">
			<!-- or http://thenounproject.com/noun/list/#icon-No9479? -->
				<?php
				if ( function_exists( 'the_alert_box' ) ) {
										add_filter( 'ab_alert_specimens_post_types', array( $this, 'alert_filterer' ) );
										add_filter( 'ab_alert_safe', array( $this, 'alert_safe_filterer' ) );
										$alerts = pressforward( 'library.alertbox' )->get_specimens();
										remove_filter( 'ab_alert_safe', array( $this, 'alert_safe_filterer' ) );
										remove_filter( 'ab_alert_specimens_post_types', array( $this, 'alert_filterer' ) );
				}

				if ( 'pf-review' == $page ) {
					echo '<button type="submit" class="delete btn btn-danger btn-small pull-left" id="archivenoms" value="' . __( 'Archive all', 'pf' ) . '" >' . __( 'Archive all', 'pf' ) . '</button>';
				}

					$user_ID = get_current_user_id();
					$pf_user_menu_set = get_user_option( 'pf_user_menu_set', $user_ID );
				if ( 'true' == $pf_user_menu_set ) {
					if ( ! empty( $alerts ) && (0 != $alerts->post_count) ) {
						echo '<a class="btn btn-small btn-warning" id="gomenu" href="#">' . __( 'Menu', 'pf' ) . ' <i class="icon-tasks"></i> (!)</a>';
					} else {
						echo '<a class="btn btn-small" id="gomenu" href="#">' . __( 'Menu', 'pf' ) . ' <i class="icon-tasks"></i></a>';
					}
				}
					echo '<a class="btn btn-small" id="gofolders" href="#">' . __( 'Folders', 'pf' ) . '</a>';
				?>

			</div>
		</div><!-- End btn-group -->
		<?php
	}

	public function dropdown_option( $string, $id, $class = 'pf-top-menu-selection', $form_id = '', $schema_action = '', $schema_class = '', $href = '', $target = '' ) {

		$option = '<li role="presentation"><a role="menuitem" id="';
		$option .= $id;
		$option .= '" tabindex="-1" class="';
		$option .= $class;
		$option .= '"';

		$option .= ' href="';
		if ( ! empty( $href ) ) {
			$option .= $href;
		} else {
			$option .= '#';
		}
		$option .= '"';

		if ( ! empty( $target ) ) {
			$option .= ' target="' . $target . '"';
		}

		if ( ! empty( $form_id ) ) {
			$option .= ' data-form="' . $form_id . '" ';
		}

		if ( ! empty( $schema_action ) ) {
			$option .= ' pf-schema="' . $schema_action . '" ';
		}

		if ( ! empty( $schema_class ) ) {
			$option .= ' pf-schema-class="' . $schema_class . '" ';
		}

		$option .= '>';
		$option .= $string;
		$option .= '</a></li>';

		echo $option;

	}

	/**
	 * Essentially the PF 'loop' template.
	 * $item = the each of the foreach
	 * $c = count.
	 * $format = format changes, to be used later or by plugins.
	 **/
	public function form_of_an_item( $item, $c, $format = 'standard', $metadata = array() ) {
		$current_user = wp_get_current_user();
		if ( '' !== get_option( 'timezone_string' ) ) {
			// Allows plugins to introduce their own item format output.
			date_default_timezone_set( get_option( 'timezone_string' ) );
		}
		if ( has_action( 'pf_output_items' ) ) {
			do_action( 'pf_output_items', $item, $c, $format );
			return;
		}
		$itemTagsArray = explode( ',', $item['item_tags'] );
		$itemTagClassesString = '';
				$user_id = $current_user->ID;
		foreach ( $itemTagsArray as $itemTag ) { $itemTagClassesString .= pf_slugger( $itemTag, true, false, true );
			$itemTagClassesString .= ' '; }

		if ( $format === 'nomination' ) {
			$feed_item_id = $metadata['item_id'];
			$id_for_comments = $metadata['pf_item_post_id']; // orig item post ID

			$readStat = pf_get_relationship_value( 'read', $metadata['nom_id'], wp_get_current_user()->ID );
			if ( ! $readStat ) { $readClass = '';
			} else { $readClass = 'article-read'; }
			if ( ! isset( $metadata['nom_id'] ) || empty( $metadata['nom_id'] ) ) { $metadata['nom_id'] = md5( $item['item_title'] ); }
			if ( empty( $id_for_comments ) ) { $id_for_comments = $metadata['nom_id']; }
			if ( empty( $metadata['item_id'] ) ) { $metadata['item_id'] = md5( $item['item_title'] ); }
		} else {
			$feed_item_id = $item['item_id'];
			$id_for_comments = $item['post_id']; // orig item post ID
		}
				// $archive_status = pf_get_relationship_value( 'archive', $id_for_comments, wp_get_current_user()->ID );
				$archive_status = pressforward( 'controller.metas' )->get_post_pf_meta( $id_for_comments, 'pf_archive', true );
		if ( isset( $_GET['pf-see'] ) ) { } else { $_GET['pf-see'] = false; }
		if ( $archive_status == 1 && ('archive-only' != $_GET['pf-see']) ) {
			$archived_status_string = 'archived';
			$dependent_style = 'display:none;';
		} elseif ( ($format === 'nomination') && (1 == pressforward( 'controller.metas' )->get_post_pf_meta( $metadata['nom_id'], 'pf_archive', true ))  && ('archive-only' != $_GET['pf-see']) ) {
			$archived_status_string = 'archived';
			$dependent_style = 'display:none;';
		} else {
			$dependent_style = '';
			$archived_status_string = 'not-archived';
		}
		if ( $format === 'nomination' ) {
			// $item = array_merge($metadata, $item);
			// var_dump($item);
			echo '<article class="feed-item entry nom-container ' . $archived_status_string . ' ' . get_pf_nom_class_tags( array( $metadata['submitters'], $metadata['nom_id'], $metadata['item_author'], $metadata['item_tags'], $metadata['item_id'] ) ) . ' ' . $readClass . '" id="' . $metadata['nom_id'] . '" style="' . $dependent_style . '" tabindex="' . $c . '" pf-post-id="' . $metadata['nom_id'] . '" pf-item-post-id="' . $id_for_comments . '" pf-feed-item-id="' . $metadata['item_id'] . '" pf-schema="read" pf-schema-class="article-read">';
			?> <a style="display:none;" name="modal-<?php echo $metadata['item_id']; ?>"></a> <?php
		} else {
			$id_for_comments = $item['post_id'];
			$readStat = pf_get_relationship_value( 'read', $id_for_comments, $user_id );
			if ( ! $readStat ) { $readClass = '';
			} else { $readClass = 'article-read'; }
			echo '<article class="feed-item entry ' . pf_slugger( get_the_source_title( $id_for_comments ), true, false, true ) . ' ' . $itemTagClassesString . ' ' . $readClass . '" id="' . $item['item_id'] . '" tabindex="' . $c . '" pf-post-id="' . $item['post_id'] . '" pf-feed-item-id="' . $item['item_id'] . '" pf-item-post-id="' . $id_for_comments . '" style="' . $dependent_style . '" >';
			?> <a style="display:none;" name="modal-<?php echo $item['item_id']; ?>"></a> <?php
		}

		if ( empty( $readStat ) ) {
			$readStat = pf_get_relationship_value( 'read', $id_for_comments, $user_id );
		}
			echo '<div class="box-controls">';
		if ( current_user_can( 'manage_options' ) ) {
			if ( $format === 'nomination' ) {
				echo '<i class="icon-remove pf-item-remove" pf-post-id="' . $metadata['nom_id'] . '" title="Delete"></i>';
			} else {
				echo '<i class="icon-remove pf-item-remove" pf-post-id="' . $id_for_comments . '" title="Delete"></i>';
			}
		}
		if ( $format != 'nomination' ) {
				$archiveStat = pf_get_relationship_value( 'archive', $id_for_comments, $user_id );
				$extra_classes = '';
			if ( $archiveStat ) { $extra_classes .= ' schema-active relationship-button-active'; }
				echo '<i class="icon-eye-close hide-item pf-item-archive schema-archive schema-switchable schema-actor' . $extra_classes . '" pf-schema-class="relationship-button-active" pf-item-post-id="' . $id_for_comments . '" title="Hide" pf-schema="archive"></i>';
		}
		if ( ! $readStat ) { $readClass = '';
		} else { $readClass = 'marked-read'; }

			echo '<i class="icon-ok-sign schema-read schema-actor schema-switchable ' . $readClass . '" pf-item-post-id="' . $id_for_comments . '" pf-schema="read" pf-schema-class="marked-read" title="Mark as Read"></i>';

			echo '</div>';
			?>
			<header> <?php
				echo '<h1 class="item_title"><a href="#modal-' . $item['item_id'] . '" class="item-expander schema-actor" role="button" data-toggle="modal" data-backdrop="false" pf-schema="read" pf-schema-targets="schema-read">' . self::display_a( $item['item_title'], 'title' ) . '</a></h1>';
				echo '<p class="source_title">' . self::display_a( get_the_source_title( $id_for_comments ), 'source' ) . '</p>';
			if ( $format === 'nomination' ) {
				?>
					<div class="sortable-hidden-meta" style="display:none;">
						<?php
						_e( 'UNIX timestamp from source RSS', 'pf' );
						echo ': <span class="sortable_source_timestamp sortableitemdate">' . $metadata['timestamp_item_posted'] . '</span><br />';

						_e( 'UNIX timestamp last modified', 'pf' );
						echo ': <span class="sortable_mod_timestamp">' . $metadata['timestamp_nom_last_modified'] . '</span><br />';

						_e( 'UNIX timestamp date nominated', 'pf' );
						echo ': <span class="sortable_nom_timestamp">' . $metadata['timestamp_unix_date_nomed'] . '</span><br />';

						_e( 'Slug for origin site', 'pf' );
						echo ': <span class="sortable_origin_link_slug">' . $metadata['source_slug'] . '</span><br />';

						// Add an action here for others to provide additional sortables.
						echo '</div>';
			}
									// Let's build an info box!
									// http://nicolasgallagher.com/pure-css-speech-bubbles/
									// $urlArray = parse_url($item['item_link']);
									$sourceLink = pressforward( 'schema.feed_item' )->get_source_link( $id_for_comments );
									$url_array = parse_url( $sourceLink );
			if ( ! $url_array || empty( $url_array['host'] ) ) {
				pf_log( 'Could not find the source link for ' . $id_for_comments . ' Got: ' . $sourceLink );
				$sourceLink = 'Source URL not found.';
			} else {
				$sourceLink = 'http://' . $url_array['host'];
			}
									// http://nicolasgallagher.com/pure-css-speech-bubbles/demo/
									$ibox = '<div class="feed-item-info-box" id="info-box-' . $item['item_id'] . '">';
										$ibox .= '
										' . __( 'Feed', 'pf' ) . ': <span class="feed_title">' . get_the_source_title( $id_for_comments ) . '</span><br />
										' . __( 'Posted', 'pf' ) . ': <span class="feed_posted">' . date( 'M j, Y; g:ia' , strtotime( $item['item_date'] ) ) . '</span><br />
										' . __( 'Retrieved', 'pf' ) . ': <span class="item_meta item_meta_added_date">' . date( 'M j, Y; g:ia' , strtotime( $item['item_added_date'] ) ) . '</span><br />
										' . __( 'Authors', 'pf' ) . ': <span class="item_authors">' . $item['item_author'] . '</span><br />
										' . __( 'Origin', 'pf' ) . ': <span class="source_name"><a target ="_blank" href="' . $sourceLink . '">' . $sourceLink . '</a></span><br />
										' . __( 'Original Item', 'pf' ) . ': <span class="source_link"><a href="' . $item['item_link'] . '" class="item_url" target ="_blank">' . $item['item_title'] . '</a></span><br />
										' . __( 'Tags', 'pf' ) . ': <span class="item_tags">' . $item['item_tags'] . '</span><br />
										' . __( 'Times repeated in source', 'pf' ) . ': <span class="feed_repeat sortable_sources_repeat">' . $item['source_repeat'] . '</span><br />
										';
			if ( $format === 'nomination' ) {

				$ibox .= __( 'Number of nominations received', 'pf' )
				. ': <span class="sortable_nom_count">' . $metadata['nom_count'] . '</span><br />'
				. __( 'First submitted by', 'pf' )
				. ': <span class="first_submitter">' . $metadata['submitters'] . '</span><br />'
				. __( 'Nominated on', 'pf' )
				. ': <span class="nominated_on">' . date( 'M j, Y; g:ia' , strtotime( $metadata['date_nominated'] ) ) . '</span><br />'
				. __( 'Nominated by', 'pf' )
				. ': <span class="nominated_by">' . get_the_nominating_users() . '</span><br />';
			}

										$draft_id = pf_is_drafted( $feed_item_id );
			if ( false != $draft_id && (current_user_can( 'edit_post', $draft_id )) ) {
				// http://codex.wordpress.org/Function_Reference/edit_post_link
				$edit_url = get_edit_post_link( $draft_id );
				$ibox .= '<br /><a class="edit_draft_from_info_box" href="' . $edit_url . '">' . __( 'Edit the draft based on this post.', 'pf' ) . '</a><br/>';
			}

									$ibox .= '</div>';
									echo $ibox;
													?>
									<script type="text/javascript">

											var pop_title_<?php echo $item['item_id'] ?> = '';
											var pop_html_<?php echo $item['item_id'] ?> = jQuery('#<?php echo 'info-box-' . $item['item_id']; ?>');


									</script>
									<?php
									$this->form_of_actions_btns( $item, $c, false, $format, $metadata, $id_for_comments );
				?>
			</header>
			<?php
						// echo '<a name="' . $c . '" style="display:none;"></a>';
			?>
			<div class="content">
				<?php
				if ( ($item['item_feat_img'] != '') && ($format != 'nomination') ) {
					echo '<div style="float:left; margin-right: 10px; margin-bottom: 10px;"><img src="' . $item['item_feat_img'] . '"></div>';
				}

				?> <div style="display:none;"> <?php
					echo '<div class="item_meta item_meta_date">Published on ' . $item['item_date'] . ' by <span class="item-authorship">' . $item['item_author'] . '</span>.</div>';
					echo 'Unix timestamp for item date:<span class="sortableitemdate">' . strtotime( $item['item_date'] ) . '</span> and for added to feed date <span class="sortablerssdate">' . strtotime( $item['item_added_date'] ) . '</span>.';
				?> </div> <?php

				echo '<div class="item_excerpt" id="excerpt' . $c . '">';
if ( $format === 'nomination' ) {
	echo'<p>' . pf_noms_excerpt( $item['item_content'] ) . '</p>';
} else {
	echo'<p>' . self::display_a( pf_feed_excerpt( $item['item_content'] ), 'graf' ) . '</p>';
}
					echo '</div>';

				?>
			</div><!-- End content -->
			<footer>
				<p class="pubdate"><?php echo date( 'F j, Y; g:i a' , strtotime( $item['item_date'] ) ); ?></p>
			</footer>
			<?php
				// Allows plugins to introduce their own item format output.
			if ( has_action( 'pf_output_modal' ) ) {
				do_action( 'pf_output_modal', $item, $c, $format );

			} else {
			?>
			<!-- Begin Modal -->
			<div id="modal-<?php echo $item['item_id']; ?>" class="modal hide fade pfmodal" tabindex="-1" role="dialog" aria-labelledby="modal-<?php echo $item['item_id']; ?>-label" aria-hidden="true" pf-item-id="<?php echo $item['item_id']; ?>" pf-post-id="<?php echo $item['post_id']; ?>" pf-readability-status="<?php echo $item['readable_status']; ?>">
		  <div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">x</button>
			<div class="modal-mobile-nav pull-right hidden-desktop">
				<div class="mobile-goPrev pull-left">

				</div>
				<div class="mobile-goNext pull-right">

				</div>
			</div>
			<h3 id="modal-<?php echo $item['item_id']; ?>-label" class="modal_item_title"><?php echo $item['item_title']; ?></h3>
			<?php
				echo '<em>' . __( 'Source', 'pf' ) . ': ' . get_the_source_title( $id_for_comments ) . '</em> | ';
				echo __( 'Author', 'pf' ) . ': ' . get_the_item_author( $id_for_comments );
			?>
		  </div>
		  <div class="row-fluid modal-body-row">
			  <div class="modal-body span9" id="modal-body-<?php echo $item['item_id']; ?>">
				<div class="readability-wait"></div><div class="main-text"><?php
				$contentObj = pressforward( 'library.htmlchecker' );
				$text = $contentObj->closetags( $item['item_content'] );
				$text = apply_filters( 'the_content', $text );
				// global $wp_embed;
				// $wp_embed->autoembed($text);
				$embed = $this->show_embed( $id_for_comments );
				if ( false != $embed ) {
					echo $embed;
				}
				print_r( $text );

				?>
				</div>
			  </div>
			  <div class="modal-sidebar span3 hidden-tablet">
				<div class="goPrev modal-side-item row-fluid">

				</div>
				<div class="modal-comments modal-side-item row-fluid">

				</div>
				<div class="goNext modal-side-item row-fluid">

				</div>
			  </div>
			  </div>
			  <div class="modal-footer">
				<div class="row-fluid">
				<div class="pull-left original-link">
				<a target="_blank" href="<?php echo $item['item_link']; ?>"><?php _e( 'Read Original', 'pf' ); ?></a>
					<?php
					// if ($format != 'nomination'){
					?>
					| <a class="modal-readability-reset" target="#readable" href="<?php echo $item['item_link']; ?>" pf-item-id="<?php echo $item['item_id']; ?>" pf-post-id="<?php echo $item['post_id']; ?>" pf-modal-id="#modal-<?php echo $item['item_id']; ?>"><?php  _e( 'Reset Readability', 'pf' ); ?></a>
						<?php
						// }
					?>
				</div>
				<div class="pull-right"><?php
				$this->form_of_actions_btns( $item, $c, true, $format, $metadata, $id_for_comments );
				?></div><?php
			?>
			</div>
			<div class="item-tags pull-left row-fluid">
			<?php
				echo '<strong>' . __( 'Item Tags', 'pf' ) . '</strong>: ' . $item['item_tags'];
			?>
			</div>
		  </div>
			</div>
			<!-- End Modal -->
		</article><!-- End article -->
		<?php
			}
	}

	/**
	 * Prep an item element for display based on position and element.
	 * Establishes the rules for item display.
	 * Position should be title, source, graf.
	 **/

	public function display_a( $string, $position = 'source', $page = 'list' ) {
		$title_ln_length = 30;
		$title_lns = 3;

		$source_ln_length = 48;
		$source_lns = 2;

		$graf_ln_length = 44;
		$graf_lns = 4;

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

		$cut = substr( $string, 0, $max + 1 );
		$final_cut = substr( $cut, 0, -4 );
		if ( strlen( $cut ) < $max ) {
			$cut = substr( $string, 0, $max );
			return $cut;
		} else {
			$cut = $final_cut . ' ...';
			return $cut;
		}

	}

	public function tweet_intent( $id ) {

		$url = 'https://twitter.com/intent/tweet?';
		$url .= 'text=' . urlencode( get_the_title( $id ) );
		$url .= '&url=' . urlencode( get_the_item_link( $id ) );
		$url .= '&via=' . urlencode( 'pressfwd' );
		return $url;

	}

	public function form_of_actions_btns( $item, $c, $modal = false, $format = 'standard', $metadata = array(), $id_for_comments ) {
			$item_id = 0;
			$user = wp_get_current_user();
			$user_id = $user->ID;
		if ( $format == 'nomination' ) {
			$item_id = $metadata['item_id'];
		} else {
			$item_id = $item['item_id'];
		}
			?>

				<div class="actions pf-btns <?php if ( $modal ) { echo 'modal-btns ';
} else { echo ' article-btns '; } ?>">
					<?php
					$infoPop = 'top';
					$infoModalClass = ' modal-popover';
					if ( $modal == false ) {
						// $infoPop = 'bottom';
						$infoModalClass = '';
						if ( $format === 'nomination' ) {
							?><form name="form-<?php echo $metadata['item_id']; ?>" pf-form="<?php echo $metadata['item_id']; ?>"><?php
							pf_prep_item_for_submit( $metadata );
							wp_nonce_field( 'nomination', PF_SLUG . '_nomination_nonce', false );
						} else {
							echo '<form name="form-' . $item['item_id'] . '">'
							. '<div class="nominate-result-' . $item['item_id'] . '">'
							. '<img class="loading-' . $item['item_id'] . '" src="' . PF_URL . 'assets/images/ajax-loader.gif" alt="' . __( 'Loading', 'pf' ) . '..." style="display: none" />'
							. '</div>';
							pf_prep_item_for_submit( $item );
							wp_nonce_field( 'nomination', PF_SLUG . '_nomination_nonce', false );
						}
						echo '</form>';
					}
					// Perhaps use http://twitter.github.com/bootstrap/javascript.html#popovers instead?
					echo '<button class="btn btn-small itemInfobutton" data-toggle="tooltip" title="' . __( 'Info', 'pf' ) . '" id="info-' . $item['item_id'] . '-' . $infoPop . '" data-placement="' . $infoPop . '" data-class="info-box-popover' . $infoModalClass . '" data-title="" data-target="' . $item['item_id'] . '"><i class="icon-info-sign"></i></button>';

					if ( pf_is_item_starred_for_user( $id_for_comments, $user_id ) ) {
						echo '<!-- item_id selected = ' . $item_id . ' -->';
						echo '<button class="btn btn-small star-item btn-warning" data-toggle="tooltip" title="' . __( 'Star', 'pf' ) . '"><i class="icon-star"></i></button>';
					} else {
						echo '<button class="btn btn-small star-item" data-toggle="tooltip" title="' . __( 'Star', 'pf' ) . '"><i class="icon-star"></i></button>';
					}

					// <a href="#" type="submit"  class="PleasePushMe"><i class="icon-plus"></i> Nominate</a>
					if ( has_action( 'pf_comment_action_button' ) ) {
						$commentModalCall = '#modal-comments-' . $item['item_id'];
						$commentSet = array( 'id' => $id_for_comments, 'modal_state' => $modal );
						// echo $id_for_comments;
						do_action( 'pf_comment_action_button', $commentSet );

					}
					if ( $format === 'nomination' ) {

						$nom_count_classes = 'btn btn-small nom-count';
						$metadata['nom_count'] = get_the_nomination_count();
						if ( $metadata['nom_count'] > 0 ) {
							$nom_count_classes .= ' btn-info';
						}

						echo '<a class="' . $nom_count_classes . '" data-toggle="tooltip" title="' . __( 'Nomination Count', 'pf' ) . '" form="' . $metadata['nom_id'] . '">' . $metadata['nom_count'] . '<i class="icon-play"></i></button></a>';
						$archive_status = '';
						if ( 1 == pressforward( 'controller.metas' )->get_post_pf_meta( $metadata['nom_id'], 'pf_archive', true ) ) {
							$archive_status = 'btn-warning';
						}
						echo '<a class="btn btn-small nom-to-archive schema-switchable schema-actor ' . $archive_status . '" pf-schema="archive" pf-schema-class="archived" pf-schema-class="btn-warning" data-toggle="tooltip" title="' . __( 'Archive', 'pf' ) . '" form="' . $metadata['nom_id'] . '"><img src="' . PF_URL . 'assets/images/archive.png" /></button></a>';
						$draft_status = '';
						if ( ( 1 == pf_get_relationship_value( 'draft', $metadata['nom_id'], $user_id ) ) || ( 1 == pf_get_relationship_value( 'draft', $id_for_comments, $user_id ) ) ) {
							$draft_status = 'btn-success';
						}
						echo '<a href="#nominate" class="btn btn-small nom-to-draft schema-actor ' . $draft_status . '" pf-schema="draft" pf-schema-class="btn-success" form="' . $metadata['item_id'] . '" data-original-title="' . __( 'Draft', 'pf' ) . '"><img src="' . PF_URL . 'assets/images/pressforward-licon.png" /></a>';

					} else {
						// var_dump(pf_get_relationship('nominate', $id_for_comments, $user_id));
						if ( ( 1 == pf_get_relationship_value( 'nominate', $id_for_comments, $user_id ) ) || ( 1 == pf_get_relationship_value( 'draft', $id_for_comments, $user_id ) ) ) {
							echo '<button class="btn btn-small nominate-now btn-success schema-actor schema-switchable" pf-schema="nominate" pf-schema-class="btn-success" form="' . $item['item_id'] . '" data-original-title="' . __( 'Nominated', 'pf' ) . '"><img src="' . PF_URL . 'assets/images/pressforward-single-licon.png" /></button>';
							// Add option here for admin-level users to send items direct to draft.
						} else {
							echo '<button class="btn btn-small nominate-now schema-actor schema-switchable" pf-schema="nominate" pf-schema-class="btn-success" form="' . $item['item_id'] . '" data-original-title="' . __( 'Nominate', 'pf' ) . '"><img src="' . PF_URL . 'assets/images/pressforward-single-licon.png" /></button>';
							// Add option here for admin-level users to send items direct to draft.
						}
					}

					$amplify_group_classes = 'dropdown btn-group amplify-group';
					$amplify_id = 'amplify-' . $item['item_id'];

					if ( $modal ) {
						$amplify_group_classes .= ' dropup';
						$amplify_id .= '-modal';
					}
					?>
					<div class="<?php echo $amplify_group_classes; ?>" role="group">
						<button type="button" class="btn btn-default btn-small dropdown-toggle pf-amplify" data-toggle="dropdown" aria-expanded="true" id="<?php echo $amplify_id; ?>"><i class="icon-bullhorn"></i><span class="caret"></button>
						<ul class="dropdown-menu dropdown-menu-right" role="menu" aria-labelledby="amplify-<?php echo $item['item_id']; ?>">
							<?php
							if ( current_user_can( 'edit_others_posts' ) && 'nomination' != $format ) {
								$send_to_draft_classes = 'amplify-option amplify-draft schema-actor';

								if ( 1 == pf_get_relationship_value( 'draft', $id_for_comments, $user_id ) ) {
									$send_to_draft_classes .= ' btn-success';
								}

								self::dropdown_option( __( 'Send to ', 'pf' ) . ucwords( get_option( PF_SLUG . '_draft_post_status', 'draft' ) ), 'amplify-draft-' . $item['item_id'], $send_to_draft_classes, $item['item_id'], 'draft', 'btn-success' );

							?>
								<li class="divider"></li>
							<?php
							}
								$tweet_intent = self::tweet_intent( $id_for_comments );
								self::dropdown_option( __( 'Tweet', 'pf' ), 'amplify-tweet-' . $item['item_id'], 'amplify-option', $item['item_id'], '', '', $tweet_intent, '_blank' );
								// self::dropdown_option(__('Facebook', 'pf'), "amplify-facebook-".$item['item_id'], 'amplify-option', $item['item_id'] );
								// self::dropdown_option(__('Instapaper', 'pf'), "amplify-instapaper-".$item['item_id'], 'amplify-option', $item['item_id'] );
								// self::dropdown_option(__('Tumblr', 'pf'), "amplify-tumblr-".$item['item_id'], 'amplify-option', $item['item_id'] );
								do_action( 'pf_amplify_buttons' );
							?>
						 </ul>
					</div>

					<?php
					if ( $modal === true ) {
						?><button class="btn btn-small" data-dismiss="modal" aria-hidden="true">Close</button><?php
					}
					?>
				</div>

		<?php

		if ( has_action( 'pf_comment_action_modal' ) ) {
				$commentModalCall = '#modal-comments-' . $item['item_id'];
				$commentSet = array( 'id' => $id_for_comments, 'modal_state' => $modal );
				// echo $id_for_comments;
				do_action( 'pf_comment_action_modal', $commentSet );

		}

	}

	public function show_embed( $id_for_comments ) {
		$item_link = pressforward( 'controller.metas' )->get_post_pf_meta( $id_for_comments, 'item_link' );
		return pressforward( 'controller.readability' )->get_embed( $item_link );
	}

}
