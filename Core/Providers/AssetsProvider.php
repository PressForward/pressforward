<?php
/**
 * Service provider for admin area.
 *
 * @package PressForward
 */

namespace PressForward\Core\Providers;

use Intraxia\Jaxion\Contract\Core\Container;

/**
 * AssetsProvider class.
 */
class AssetsProvider extends \Intraxia\Jaxion\Assets\ServiceProvider {
	/**
	 * {@inheritDoc}
	 *
	 * @param \Intraxia\Jaxion\Contract\Core\Container $container Container object.
	 */
	public function register( Container $container ) {
		$this->container = $container;
		$register        = $this->container->fetch(
			'assets'
		);

		$enqueue_hooks = [ 'admin_enqueue_scripts', 'wp_enqueue_scripts' ];
		foreach ( $enqueue_hooks as $enqueue_hook ) {
			add_action(
				$enqueue_hook,
				function () use ( $register ) {
					$this->add_assets( $register );
				},
				0
			);
		}

		add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_styles' ] );
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_block_editor_assets' ] );
	}

	/**
	 * Registers static assets.
	 *
	 * @param \Intraxia\Jaxion\Assets\Register $assets Assets object.
	 */
	protected function add_assets( \Intraxia\Jaxion\Assets\Register $assets ) {
		$url = $this->container->fetch( 'url' );

		$assets->set_debug( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG );

		$provider = $this;

		$assets->register_style(
			array(
				'type'   => 'admin',
				'handle' => 'pf-reset-style',
				'src'    => 'assets/css/reset',
			)
		);

		$assets->register_style(
			array(
				'type'   => 'admin',
				'handle' => 'pf-style',
				'src'    => 'assets/css/pressforward',
				'deps'   => [
					'thickbox',
					'pf-bootstrap',
					'pf-reader',
				],
			)
		);

		$assets->register_style(
			array(
				'type'   => 'admin',
				'handle' => 'pf-settings-style',
				'src'    => 'assets/css/pf-settings',
				'deps'   => [
					'pf-bootstrap',
				],
			)
		);

		$assets->register_style(
			array(
				'type'   => 'admin',
				'handle' => 'pf-subscribed-styles',
				'src'    => 'assets/css/pf-subscribed',
				'deps'   => [],
			)
		);

		$assets->register_style(
			array(
				'type'   => 'admin',
				'handle' => 'pf-nomination-success',
				'src'    => 'assets/css/nomination-success',
				'deps'   => [],
			)
		);

		$assets->register_style(
			array(
				'handle' => 'pf-blocks-frontend',
				'src'    => 'build/blocks-frontend',
				'deps'   => [],
			)
		);

		$assets->register_style(
			[
				'handle' => 'pf-welcome',
				'src'    => 'build/welcome',
				'deps'   => [],
			]
		);

		// Scripts.
		$assets->register_script(
			array(
				'type'   => 'admin',
				'handle' => 'pf',
				'src'    => 'assets/js/pf',
				'deps'   => [
					'jquery',
					'pf-popper',
					'pf-readability-imp',
					'pf-reader',
					'pf-sort-imp',
				],
			)
		);

		$assets->register_script(
			array(
				'type'   => 'admin',
				'handle' => 'pf-api',
				'src'    => 'assets/js/pf-api',
				'deps'   => array( 'jquery', 'wp-api', 'pf' ),
			)
		);

		$assets->register_script(
			array(
				'type'   => 'admin',
				'handle' => 'pf-settings-tools',
				'src'    => 'assets/js/settings-tools',
				'deps'   => array( 'pf' ),
			)
		);

		$assets->register_script(
			array(
				'type'   => 'admin',
				'handle' => 'pf-popper',
				'src'    => 'Libraries/popper',
				'deps'   => array(),
			)
		);

		$assets->register_script(
			array(
				'type'   => 'admin',
				'handle' => 'pf-tools',
				'src'    => 'assets/js/tools-imp',
				'deps'   => array( 'pf' ),
			)
		);

		$assets->register_script(
			array(
				'type'   => 'admin',
				'handle' => 'pf-jws',
				'src'    => 'assets/js/jws',
				'deps'   => array( 'pf' ),
			)
		);

		$assets->register_script(
			array(
				'type'   => 'admin',
				'handle' => 'pf-jwt',
				'src'    => 'assets/js/jwt',
				'deps'   => array( 'pf', 'pf-jws' ),
			)
		);

		$assets->register_script(
			array(
				'type'   => 'admin',
				'handle' => 'pf-jq-fullscreen',
				'src'    => 'Libraries/jquery-fullscreen/jquery.fullscreen',
				'deps'   => [
					'jquery',
				],
			)
		);

		$assets->register_script(
			array(
				'type'   => 'admin',
				'handle' => 'pf-sort-imp',
				'src'    => 'assets/js/sort-imp',
				'deps'   => [
					'pf-jq-fullscreen',
				],
			)
		);

		$assets->register_script(
			array(
				'type'   => 'admin',
				'handle' => 'pf-readability-imp',
				'src'    => 'assets/js/readability-imp',
				'deps'   => [
					'jquery',
				],
			)
		);

		$assets->register_script(
			array(
				'type'   => 'admin',
				'handle' => 'pf-nomination-imp',
				'src'    => 'assets/js/nomination-imp',
				'deps'   => [ 'jquery' ],
			)
		);

		$assets->register_script(
			array(
				'type'   => 'admin',
				'handle' => 'pf-relationships',
				'src'    => 'assets/js/relationships',
				'deps'   => array( 'pf' ),
			)
		);

		$assets->register_script(
			array(
				'type'   => 'admin',
				'handle' => 'pf-settings-tools',
				'src'    => 'assets/js/settings-tools',
				'deps'   => array( 'pf' ),
			)
		);

		$assets->register_script(
			array(
				'type'   => 'admin',
				'handle' => 'feed_control_script',
				'src'    => 'assets/js/feeds_control',
				'deps'   => array( 'pf', 'pf-settings-tools' ),
			)
		);

		$assets->register_script(
			array(
				'type'   => 'admin',
				'handle' => 'pf-tools',
				'src'    => 'assets/js/tools-imp',
				'deps'   => array( 'pf' ),
			)
		);

		$assets->register_script(
			array(
				'type'   => 'admin',
				'handle' => 'pf-send-to-draft-imp',
				'src'    => 'assets/js/send-to-draft-imp',
				'deps'   => array( 'pf' ),
			)
		);

		$assets->register_script(
			array(
				'type'   => 'admin',
				'handle' => 'pf-archive-nom-imp',
				'src'    => 'assets/js/nom-archive-imp',
				'deps'   => array( 'pf' ),
			)
		);

		$assets->register_script(
			array(
				'type'   => 'web',
				'handle' => 'pf-blocks-frontend',
				'src'    => 'build/blocks-frontend',
				'deps'   => [],
			)
		);
	}

	/**
	 * Registers scripts that are admin-only.
	 *
	 * @return void
	 */
	public function admin_enqueue_scripts() {
		$build_scripts = [
			'pf-add-feeds'     => 'add-feeds',
			'pf-edit-feeds'    => 'edit-feeds',
			'pf-nominate-this' => 'nominate-this',
			'pf-quick-edit'    => 'quick-edit',
			'pf-reader'        => 'reader',
			'pf-scroll'        => 'scroll',
		];

		// Some dependencies can't be detected by @wordpress/scripts.
		$extra_dependencies = [
			'pf-nominate-this' => [ 'wp-api' ],
			'pf-reader'        => [ 'pf-nomination-imp' ],
		];

		foreach ( $build_scripts as $script_handle => $script_file ) {
			$build_vars = require \PF_ROOT . '/build/' . $script_file . '.asset.php';

			$dependencies = $build_vars['dependencies'];
			if ( isset( $extra_dependencies[ $script_handle ] ) ) {
				$dependencies = array_merge( $dependencies, $extra_dependencies[ $script_handle ] );
			}

			wp_register_script(
				$script_handle,
				\PF_URL . '/build/' . $script_file . '.js',
				$dependencies,
				$build_vars['version'],
				true
			);
		}

		// Enqueuing that cannot happen inline.
		$screen = get_current_screen();

		if ( $screen && 'edit-pf_feed' === $screen->id ) {
			wp_enqueue_script( 'pf-quick-edit' );
			wp_enqueue_script( 'pf-edit-feeds' );
		}

		if ( $screen && 'pf_feed' === $screen->id ) {
			wp_enqueue_script( 'pf-edit-feeds' );
		}

		if ( $screen && 'nomination' === $screen->id ) {
			wp_enqueue_script( 'pf-nominate-this' );
		}
	}

	/**
	 * Registers styles that are admin-only.
	 *
	 * @return void
	 */
	public function admin_enqueue_styles() {
		$build_styles = [
			'pf-bootstrap'     => 'bootstrap',
			'pf-reader'        => 'reader',
			'pf-nominate-this' => 'nominate-this',
		];

		foreach ( $build_styles as $style_handle => $style_file ) {
			$build_vars = require \PF_ROOT . '/build/' . $style_file . '.asset.php';

			wp_register_style(
				$style_handle,
				\PF_URL . '/build/' . $style_file . '.css',
				[],
				$build_vars['version']
			);
		}
	}

	/**
	 * Registers and enqueues assets specific to the block editor.
	 *
	 * @since 5.6.0
	 *
	 * @return void
	 */
	public function enqueue_block_editor_assets() {
		$scripts = [
			'pf-block-editor-comments'      => 'block-editor-comments',
			'pf-blocks'                     => 'blocks',
			'pf-nominate-this-block-editor' => 'nominate-this-block-editor',
		];

		foreach ( $scripts as $script_handle => $script_file ) {
			$build_vars = require \PF_ROOT . '/build/' . $script_file . '.asset.php';

			wp_enqueue_script(
				$script_handle,
				\PF_URL . '/build/' . $script_file . '.js',
				$build_vars['dependencies'],
				$build_vars['version'],
				true
			);
		}

		wp_add_inline_script(
			'pf-nominate-this-block-editor',
			'window.pfNominateThisBlockEditor = ' . wp_json_encode(
				[
					'nominationSuccessUrl' => admin_url( 'admin.php?page=pf-nomination-success' ),
				]
			),
			'before'
		);

		wp_add_inline_script(
			'pf-blocks',
			'window.pfBlocks = ' . wp_json_encode(
				[
					'draftPostType'      => pressforward_draft_post_type(),
					'linkToSource'       => get_option( 'pf_link_to_source' ),
					'nominationPostType' => pressforward_nomination_post_type(),
				]
			),
			'before'
		);

		wp_add_inline_script(
			'pf-block-editor-comments',
			'window.pfBlockEditorComments = ' . wp_json_encode(
				[
					'apiBaseUrl'         => get_rest_url(),
					'nominationPostType' => pressforward_nomination_post_type(),
				]
			),
		);

		$styles = [
			'pf-block-editor-comments'      => 'block-editor-comments',
			'pf-blocks'                     => 'blocks',
			'pf-nominate-this-block-editor' => 'nominate-this-block-editor',
		];

		foreach ( $styles as $style_handle => $style_file ) {
			$build_vars = require \PF_ROOT . '/build/' . $style_file . '.asset.php';

			wp_enqueue_style(
				$style_handle,
				\PF_URL . '/build/' . $style_file . '.css',
				[],
				$build_vars['version']
			);
		}
	}

	/**
	 * Checks admin script enqueue hook for a PressForward-related substring.
	 *
	 * @param string $hook       Hook name.
	 * @param array  $exclusions List of excluded strings.
	 * @param array  $inclusions List of included strings.
	 * @param bool   $all        Whether to check all.
	 * @return bool
	 */
	public function check_hook_for_pressforward_string( $hook, $exclusions = array(), $inclusions = array(), $all = false ) {

		$position_test_one = strpos( $hook, 'pressforward' );
		$position_test_two = strpos( $hook, 'pf' );

		if ( empty( $inclusions ) && ( false === $position_test_one ) && ( false === $position_test_two ) ) {
			return false;
		}

		if ( ! empty( $exclusions ) ) {
			foreach ( $exclusions as $exclusion ) {
				if ( ! empty( $exclusion ) && ( false !== strpos( $hook, $exclusion ) ) ) {
					return false;
				}
			}
		}

		if ( $all && ( false !== $position_test_one ) || ( false !== $position_test_two ) ) {
			return true;
		}

		if ( ! empty( $inclusions ) ) {
			$include = false;
			foreach ( $inclusions as $inclusion ) {
				if ( false !== strpos( $hook, $inclusion ) ) {
					$include = true;
				}
			}
			return $include;
		}

		return true;
	}
}
