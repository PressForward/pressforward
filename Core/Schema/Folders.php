<?php
namespace PressForward\Core\Schema;

use Intraxia\Jaxion\Contract\Core\HasActions;
use Intraxia\Jaxion\Contract\Core\HasFilters;

/**
 * Folders data schema
 *
 * Folders are used to track and organize feeds. Here is where they are declared.
 */

class Folders implements HasActions, HasFilters {
	// var $post_type;
	// var $tag_taxonomy;
	public function __construct() {
		$this->tag_taxonomy = 'pf_feed_category';
		$this->post_type    = 'pf_feed';
	}

	public function action_hooks() {
		return array(
			array(
				'hook'   => 'pf_feed_post_type_registered',
				'method' => 'register_feed_tag_taxonomy',
			),
			array(
				'hook'   => 'feed_folders_registered',
				'method' => 'register_folders_for_feeds',
			),
		);
	}

	public function filter_hooks() {
		$filters = array();
		if ( is_admin() ) {
			$admin_filters = array(
				array(
					'hook'     => 'parent_file',
					'method'   => 'move_feed_tags_submenu',
					'priority' => 10,
					'args'     => 1,
				),
			);
			$filters       = array_merge( $filters, $admin_filters );
		}
		return $filters;
	}

	public function register_feed_tag_taxonomy() {
		$labels = array(
			'name'          => __( 'Folders', 'pf' ),
			'singular_name' => __( 'Folder', 'pf' ),
			'all_items'     => __( 'All Folders', 'pf' ),
			'edit_item'     => __( 'Edit Folder', 'pf' ),
			'update_item'   => __( 'Update Folder', 'pf' ),
			'add_new_item'  => __( 'Add New Folder', 'pf' ),
			'new_item_name' => __( 'New Folder', 'pf' ),
			'search_items'  => __( 'Search Folders', 'pf' ),
		);

		register_taxonomy(
			$this->tag_taxonomy, '', apply_filters(
				'pf_register_feed_tag_taxonomy_args', array(
					'labels'                => $labels,
					'public'                => true,
					'show_admin_columns'    => true,
					'show_in_nav_menus'     => true,
					'show_ui'               => true,
					'show_admin_column'     => true,
					'show_in_menu'          => PF_MENU_SLUG,
					'hierarchical'          => true,
					'update_count_callback' => '_update_post_term_count',
					// 'show_in_menu' => PF_MENU_SLUG,
					'rewrite'               => false,
					'show_in_rest'          => true,
					'rest_base'             => 'folders',
					'rest_controller_class' => 'PF_REST_Terms_Controller',
				)
			)
		);

		do_action( 'feed_folders_registered' );
	}

	public function register_folders_for_feeds() {
		register_taxonomy_for_object_type( $this->tag_taxonomy, $this->post_type );
	}
	/**
	 * Ensure that 'Feed Tags' stays underneath the PressForward top-level item.
	 *
	 * @param string $pf The $parent_file value passed to the
	 *        'parent_file' filter
	 * @return string
	 */
	public function move_feed_tags_submenu( $pf ) {
		global $typenow, $pagenow;

		// Feed Tags edit page
		if ( 'edit-tags.php' === $pagenow && ! empty( $_GET['taxonomy'] ) && $this->tag_taxonomy === stripslashes( $_GET['taxonomy'] ) ) {
			$pf = 'pf-menu';
		}

		// Edit Feed page
		if ( 'post.php' === $pagenow && ! empty( $_GET['post'] ) ) {
			global $post;
			if ( $this->post_type === $post->post_type ) {
				$pf = 'pf-menu';
			}
		}

		return $pf;
	}

	public function is_feed_term( $id ) {
		// var_dump($id);
		$termcheck = term_exists( (int) $id, $this->tag_taxonomy );
		if ( empty( $termcheck ) ) {
			return false;
		} else {
			return true;
		}
	}

	public function get_top_feed_folders() {
		$terms = array( $this->tag_taxonomy );
		$cats  = get_terms(
			$terms,
			array(
				'parent'       => 0,
				'hide_empty'   => 0,
				'hierarchical' => 1,
			)
		);
		return $cats;
	}

	public function get_child_feed_folders( $ids = false ) {
		$children = array();
		if ( ! $ids ) {
			foreach ( $this->get_top_feed_folders() as $cat ) {
				$term_childs = get_term_children( $cat->term_id, $this->tag_taxonomy );
				if ( ! empty( $term_childs ) ) {
					$children[ $cat->term_id ] = get_term_children( $cat->term_id, $this->tag_taxonomy );
				} else {
					$children[ $cat->term_id ] = false;
				}
			}
		} elseif ( is_numeric( $ids ) || is_string( $ids ) ) {
			if ( ! $this->is_feed_term( $ids ) ) {
				var_dump( $ids . ' not a term in ' . $this->tag_taxonomy );
				return false;
			}
			$children_terms = get_term_children( $ids, $this->tag_taxonomy );
			// var_dump($children_terms);
			foreach ( $children_terms as $child ) {
				$children[ $child ] = $this->get_feed_folders( $child );
			}
		} elseif ( is_array( $ids ) ) {
			foreach ( $ids as $id ) {
				$children[ $id ] = $this->get_feed_folders( $id );
			}
		} elseif ( is_object( $ids ) ) {
			$children[ $ids->term_id ] = get_term_children( $ids->term_id, $this->tag_taxonomy );
		} else {
			return $ids;
		}
		return $children;
	}

	public function get_child_folders( $folder ) {
			$children = get_term_children( $folder->term_id, $this->tag_taxonomy );
			$folders  = array();
		foreach ( $children as $child ) {
			$folders[ $child ] = $this->get_feed_folders( $child );
		}
			return $folders;
	}

	public function get_feed_folders( $ids = false ) {
		$folder_set = array();
		if ( ! $ids ) {
			$top_folders = $this->get_top_feed_folders();
			foreach ( $top_folders as $folder ) {

				$folder_set[ $folder->term_id ] = array(
					'term'     => $folder,
					'term_id'  => $folder->term_id,
					'children' => array(
						'feeds'   => get_objects_in_term( $folder->term_id, $this->tag_taxonomy ),
						'folders' => $this->get_child_folders( $folder ),
					),
				);
			}
		} elseif ( is_numeric( $ids ) ) {
			$folder     = get_term( $ids, $this->tag_taxonomy );
			$folder_set = array(
				'term'     => $folder,
				'term_id'  => $folder->term_id,
				'children' => array(
					'feeds'   => get_objects_in_term( $folder->term_id, $this->tag_taxonomy ),
					'folders' => $this->get_child_folders( $folder ),
				),
			);
		} elseif ( is_array( $ids ) ) {
			// var_dump($ids); die();
			foreach ( $ids as $id ) {
				$folder_set[ $id ] = $this->get_feed_folders( $id );
			}
		} else {
			return false;
		}

		return $folder_set;

	}

	public function get_feeds_without_folders( $ids = true ) {
		$q      = new \WP_Query(
			array(
				'post_type' => $this->post_type,
				'fields'    => 'ids',
				'orderby'   => 'title',
				'order'     => 'ASC',
				'nopaging'  => true,
				'tax_query' => array(
					array(
						'taxonomy' => $this->tag_taxonomy,
						'operator' => 'NOT EXISTS',
					),
				),
			)
		);
		   $ids = $q->posts;
		   return $ids;

	}

	public function link_to_see_all_feeds_and_folders() {
		?>
		<li class="feed" id="the-whole-feed-list">
		<?php

			printf( '<a href="%s" title="%s">%s</a>', $feed_obj->ID, $feed_obj->post_title, $feed_obj->post_title );

		?>
		</li>
		<?php
	}

	public function the_feeds_without_folders() {
		global $wp_version;
		// var_dump((float)$wp_version);
		if ( 4.0 < (float) $wp_version ) {
			$the_other_feeds = $this->get_feeds_without_folders();
			foreach ( $the_other_feeds as $a_feed_id ) {
				$this->the_feed( $a_feed_id );
			}
		} else {
			_e( 'You must run version 4.0 of WordPress or higher to access the folders feature.', 'pf' );
		}
	}

	public function the_feed_folders( $obj = false ) {
		if ( ! $obj ) {
			$obj = $this->get_feed_folders();
		}
		?>
		<ul class="feed_folders">
				<?php
				// var_dump($obj);
				foreach ( $obj as $folder ) {
					?>
					<li class="feed_folder" id="folder-<?php echo $folder['term_id']; ?>">
					<?php
					$this->the_inside_of_folder( $folder );
					?>
					</li>
					<?php
				}

				$this->the_feeds_without_folders();
				?>
		</ul>
		<?php
	}

	public function the_inside_of_folder( $folder, $wrapped = false ) {
		if ( $wrapped ) {
			?>
			<li class="feed_folder" id="folder-<?php echo $folder['term_id']; ?>">
			<?php
		}
		$this->the_folder( $folder );

		// var_dump($folder);
		if ( ! empty( $folder['children']['folders'] ) ) {
			foreach ( $folder['children']['folders'] as $subfolder ) {
				?>
				<ul class="feed_inner_folders">
				<?php
				$this->the_inside_of_folder( $subfolder, true );
				?>
				</ul>
				<?php

			}
		}

		if ( ! empty( $folder['children']['feeds'] ) ) {
			?>
			<ul class="feed_inner_feeds">
			<?php
			foreach ( $folder['children']['feeds'] as $feed ) {
				?>
				<?php
				$this->the_feed( $feed );
				?>
				<?php
			}
			?>
			</ul>
			<?php
		}
		if ( $wrapped ) {
			?>
		</li>
			<?php
		}
	}

	public function the_folder( $folder ) {
		// var_dump($folder);
		if ( is_array( $folder ) ) {
			$term_obj = $folder['term'];
		} else {
			$term_obj = $folder;
		}
		?>

		<?php
			printf( '<a href="%s" class="folder" title="%s">%s</a>', $term_obj->term_id, $term_obj->name, $term_obj->name );

		?>

		<?php
	}

	public function the_feed( $feed ) {
		$feed_obj = get_post( $feed );
		?>
		<li class="feed" id="feed-<?php echo $feed_obj->ID; ?>">
		<?php

			printf( '<a href="%s" title="%s">%s</a>', $feed_obj->ID, $feed_obj->post_title, $feed_obj->post_title );

		?>
		</li>
		<?php
	}


	public function folderbox() {
		?>
			<div id="feed-folders">
					<?php
					printf( __( '<h3>Folders</h3>' ) );
					$this->the_feed_folders();
					?>
				<div class="clear"></div>
			</div>
		<?php
	}

}
