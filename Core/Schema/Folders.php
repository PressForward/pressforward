<?php
/**
 * Feed Items data control.
 *
 * @package PressForward
 */

namespace PressForward\Core\Schema;

use Intraxia\Jaxion\Contract\Core\HasActions;
use Intraxia\Jaxion\Contract\Core\HasFilters;

/**
 * Folders data schema
 *
 * Folders are used to track and organize feeds. Here is where they are declared.
 */
class Folders implements HasActions, HasFilters {
	/**
	 * Post type.
	 *
	 * @access public
	 * @var string
	 */
	public $post_type;

	/**
	 * Tag taxonomy.
	 *
	 * @access public
	 * @var string
	 */
	public $tag_taxonomy;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->tag_taxonomy = 'pf_feed_category';
		$this->post_type    = 'pf_feed';
	}

	/**
	 * {@inheritdoc}
	 */
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

	/**
	 * {@inheritdoc}
	 */
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

	/**
	 * Registers the Folder taxonomy.
	 */
	public function register_feed_tag_taxonomy() {
		$labels = array(
			'name'          => __( 'Folders', 'pressforward' ),
			'singular_name' => __( 'Folder', 'pressforward' ),
			'all_items'     => __( 'All Folders', 'pressforward' ),
			'edit_item'     => __( 'Edit Folder', 'pressforward' ),
			'update_item'   => __( 'Update Folder', 'pressforward' ),
			'add_new_item'  => __( 'Add New Folder', 'pressforward' ),
			'new_item_name' => __( 'New Folder', 'pressforward' ),
			'search_items'  => __( 'Search Folders', 'pressforward' ),
		);

		register_taxonomy(
			$this->tag_taxonomy,
			'',
			apply_filters(
				'pf_register_feed_tag_taxonomy_args',
				array(
					'labels'                => $labels,
					'public'                => true,
					'show_admin_columns'    => true,
					'show_in_nav_menus'     => true,
					'show_ui'               => true,
					'show_admin_column'     => true,
					'show_in_menu'          => PF_MENU_SLUG,
					'hierarchical'          => true,
					'update_count_callback' => '_update_post_term_count',
					'rewrite'               => false,
					'show_in_rest'          => true,
					'rest_base'             => 'folders',
					'rest_controller_class' => 'PF_REST_Terms_Controller',
				)
			)
		);

		do_action( 'feed_folders_registered' );
	}

	/**
	 * Associate the folder taxonomy with feeds.
	 */
	public function register_folders_for_feeds() {
		register_taxonomy_for_object_type( $this->tag_taxonomy, $this->post_type );
	}

	/**
	 * Ensure that 'Feed Tags' stays underneath the PressForward top-level item.
	 *
	 * @param string $pf The $parent_file value passed to the 'parent_file' filter.
	 * @return string
	 */
	public function move_feed_tags_submenu( $pf ) {
		global $typenow, $pagenow;

		// Feed Tags edit page.
		// phpcs:ignore WordPress.PHP.YodaConditions.NotYoda
		if ( 'edit-tags.php' === $pagenow && ! empty( $_GET['taxonomy'] ) && $this->tag_taxonomy === sanitize_text_field( wp_unslash( $_GET['taxonomy'] ) ) ) {
			$pf = 'pf-menu';
		}

		// Edit Feed page.
		if ( 'post.php' === $pagenow && ! empty( $_GET['post'] ) ) {
			global $post;
			if ( $this->post_type === $post->post_type ) {
				$pf = 'pf-menu';
			}
		}

		return $pf;
	}

	/**
	 * Checks whether an ID corresponds to a pf_feed_category term.
	 *
	 * @param int $id ID.
	 * @return bool
	 */
	public function is_feed_term( $id ) {
		$termcheck = term_exists( (int) $id, $this->tag_taxonomy );
		if ( empty( $termcheck ) ) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Gets list of top-level feed folders.
	 *
	 * @return array
	 */
	public function get_top_feed_folders() {
		$terms = array( $this->tag_taxonomy );
		$cats  = get_terms(
			[
				'taxonomy'     => $terms,
				'parent'       => 0,
				'hide_empty'   => false,
				'hierarchical' => true,
			]
		);
		return $cats;
	}

	/**
	 * Gets child folders of a folder.
	 *
	 * @param \WP_Term $folder Term object.
	 * @return array
	 */
	public function get_child_folders( $folder ) {
		$children = get_term_children( $folder->term_id, $this->tag_taxonomy );
		$folders  = array();

		foreach ( $children as $child ) {
			$folders[ $child ] = $this->get_feed_folders( $child );
		}

		return $folders;
	}

	/**
	 * Gets feed folders.
	 *
	 * @param int|array|object|bool $ids Single folder ID, or array/object of IDs.
	 * @return array|false
	 */
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
			foreach ( $ids as $id ) {
				$folder_set[ $id ] = $this->get_feed_folders( $id );
			}
		} else {
			return false;
		}

		return $folder_set;
	}

	/**
	 * Gets a list of feeds without folders.
	 *
	 * @return array
	 */
	public function get_feeds_without_folders() {
		$q = new \WP_Query(
			array(
				'post_type'              => $this->post_type,
				'orderby'                => 'title',
				'order'                  => 'ASC',
				'nopaging'               => true,
				'update_post_meta_cache' => false,
				'tax_query'              => array(
					array(
						'taxonomy' => $this->tag_taxonomy,
						'operator' => 'NOT EXISTS',
					),
				),
			)
		);

		return wp_list_pluck( $q->posts, 'ID' );
	}

	/**
	 * Gets list of feeds without folders.
	 */
	public function the_feeds_without_folders() {
		$the_other_feeds = $this->get_feeds_without_folders();
		foreach ( $the_other_feeds as $a_feed_id ) {
			$this->the_feed( $a_feed_id );
		}
	}

	/**
	 * Generates markup for feed folders.
	 *
	 * @param array|bool $obj Optional. Folder tree.
	 */
	public function the_feed_folders( $obj = false ) {
		if ( ! $obj ) {
			$obj = $this->get_feed_folders();
		}

		?>
		<ul class="feed_folders">
			<?php
			foreach ( $obj as $folder ) {
				?>
				<li class="feed_folder" id="folder-<?php echo esc_attr( $folder['term_id'] ); ?>">
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

	/**
	 * Generates markup for the inside of a feed folder.
	 *
	 * @param array $folder  Term data.
	 * @param bool  $wrapped Whether to have a 'feed_folder' wrapper div.
	 */
	public function the_inside_of_folder( $folder, $wrapped = false ) {
		if ( $wrapped ) {
			?>
			<li class="feed_folder" id="folder-<?php echo esc_attr( $folder['term_id'] ); ?>">
			<?php
		}
		$this->the_folder( $folder );

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

	/**
	 * Generates markup for the folder name/link.
	 *
	 * @param array $folder  Term data.
	 */
	public function the_folder( $folder ) {
		if ( empty( $folder['term'] ) ) {
			return;
		}

		$term_obj = $folder['term'];

		?>

		<?php
			printf( '<a href="%s" class="folder" title="%s">%s</a>', esc_attr( $term_obj->term_id ), esc_attr( $term_obj->name ), esc_html( $term_obj->name ) );

		?>

		<?php
	}


	/**
	 * Generates markup for the folder name/link.
	 *
	 * @param mixed $feed Feed ID or object.
	 */
	public function the_feed( $feed ) {
		$feed_obj = get_post( $feed );
		?>
		<li class="feed" id="feed-<?php echo esc_attr( (string) $feed_obj->ID ); ?>">
		<?php

			printf( '<a href="%s" title="%s">%s</a>', esc_attr( (string) $feed_obj->ID ), esc_attr( $feed_obj->post_title ), esc_html( $feed_obj->post_title ) );

		?>
		</li>
		<?php
	}

	/**
	 * Generates markup for the feed-folders element.
	 */
	public function folderbox() {
		?>
		<div id="feed-folders">
			<h3><?php esc_html_e( 'Folders', 'pressforward' ); ?></h3>
			<?php $this->the_feed_folders(); ?>
			<div class="clear"></div>
		</div>
		<?php
	}
}
