<?php
namespace PressForward\Core\Providers;

use Intraxia\Jaxion\Contract\Core\Container as Container;
use Intraxia\Jaxion\Assets\Register as Assets;
use Intraxia\Jaxion\Assets\ServiceProvider as ServiceProvider;

class AssetsProvider extends ServiceProvider {

	/**
	 * {@inheritDoc}
	 *
	 * @param Container $container
	 */
	public function register( Container $container ) {
		$this->container = $container;
		// var_dump($this->container); die();
		$register = $this->container->fetch(
			'assets'
		);

		$this->add_assets( $register );
	}

	protected function add_assets( Assets $assets ) {
		$slug = 'pf';
		$url   = $this->container->fetch( 'url' );
		$assets->set_debug( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG );

		$provider = $this;

		$assets->register_style(
			array(
				'type'      => 'admin',
				'condition' => function( $hook ) use ( $provider ) {
					return true;
				},
				'handle'    => $slug . '-alert-styles',
				'src'       => 'assets/css/alert-styles',
			)
		);

		$assets->register_style(
			array(
				'type'      => 'admin',
				'condition' => ( function( $hook ) use ( $provider ) {
					$exclusions = array( 'pf-options' );
					//return true;
									return $provider->check_hook_for_pressforward_string( $hook, $exclusions );
				} ),
				'handle'    => $slug . '-reset-style',
				'src'       => 'assets/css/reset',
			)
		);

		$assets->register_style(
			array(
				'type'      => 'admin',
				'condition' => function( $hook ) use ( $provider ) {
					$exclusions = array( 'pf-options' );
					return $provider->check_hook_for_pressforward_string( $hook, $exclusions );
				},
				'handle'    => $slug . '-bootstrap-style',
				'src'       => 'Libraries/twitter-bootstrap/css/bootstrap',
				'deps'      => array( $slug . '-reset-style' )
			)
		);

		$assets->register_style(
			array(
				'type'      => 'admin',
				'condition' => function( $hook ) use ( $provider ) {
					$exclusions = array( 'pf-options' );
					return $provider->check_hook_for_pressforward_string( $hook, $exclusions );
				},
				'handle'    => $slug . '-bootstrap-responsive-style',
				'src'       => 'Libraries/twitter-bootstrap/css/bootstrap-responsive',
				'deps'      => array( $slug . '-bootstrap-style' )
			)
		);

		$assets->register_style(
			array(
				'type'      => 'admin',
				'condition' => function( $hook ) use ( $provider ) {
					$exclusions = array( 'pf-options' );
					return $provider->check_hook_for_pressforward_string( $hook, $exclusions );
				},
				'handle'    => $slug . '-style',
				'src'       => 'assets/css/pressforward',
				'deps'      => array( 'thickbox', $slug . '-reset-style' ),
			)
		);

		$assets->register_style(
			array(
				'type'      => 'admin',
				'condition' => function( $hook ) use ( $provider ) {
					$exclusions = array();
					return $provider->check_hook_for_pressforward_string( $hook, $exclusions );
				},
				'handle'    => $slug . '-settings-style',
				'src'       => 'assets/css/pf-settings'
			)
		);

		$assets->register_style(
			array(
				'type'      => 'admin',
				'condition' => function( $hook ) use ( $provider ) {
					return pressforward( 'controller.template_factory' )->is_a_pf_page();
				},
				'handle'    => $slug . '-subscribed-styles',
				'src'       => 'assets/css/pf-subscribed',
				'deps'      => array( $slug . '-style' )
			)
		);

		// Scripts
		$assets->register_script(
			array(
				'type'      => 'admin',
				'condition' => function( $hook ) use ( $provider ) {
					$inclusions = array( 'edit.php' );
					return $provider->check_hook_for_pressforward_string( $hook, array(), $inclusions, true );
				},
				'handle'    => 'pf',
				'src'       => 'assets/js/pf',
				'deps'      => array( 'jquery' ),
			)
		);

		$assets->register_script(
			array(
				'type'      => 'admin',
				'condition' => function( $hook ) use ( $provider ) {
					$inclusions = array( 'edit.php' );
					return $provider->check_hook_for_pressforward_string( $hook, array(), $inclusions, true );
				},
				'handle'    => 'pf-api',
				'src'       => 'assets/js/pf-api',
				'deps'      => array( 'jquery', 'wp-api', 'pf' ),
			)
		);

		// Scripts
		/**
		* $assets->register_script( array(
		*   'type'  => 'admin',
		*   'condition' => function( $hook ) use ( $provider ) {
		*       $inclusions = array( 'edit.php' );
		*       return $provider->check_hook_for_pressforward_string( $hook, array(), $inclusions, true );
		*   },
		*   'handle'    => 'stats-app',
		*   'src'       => 'assets/stats-app/dist/app',
		*   'deps'      => array( 'pf', 'jquery', 'wp-api' ),
		* ) );
		*/

		$assets->register_script(
			array(
				'type'      => 'admin',
				'condition' => function( $hook ) use ( $provider ) {
					return $provider->check_hook_for_pressforward_string( $hook );
				},
				'handle'    => $slug . '-heartbeat',
				'src'       => 'assets/js/pf-heartbeat',
				'deps'      => array( 'jquery', 'heartbeat', 'jquery-ui-progressbar', 'pf' ),
			)
		);

		$assets->register_script(
			array(
				'type'      => 'admin',
				'condition' => function( $hook ) use ( $provider ) {
					$exclusions = array( 'toplevel_page_pf-menu' );
					return $provider->check_hook_for_pressforward_string( $hook, $exclusions );
				},
				'handle'    => $slug . '-settings-tools',
				'src'       => 'assets/js/settings-tools',
				'deps'      => array( 'pf' ),
			)
		);

		$metrics_config = get_option( 'pf_metrics_config', array() );
		if ( ! isset( $metrics_config['checked'] ) ) {
			$metrics_config['checked']          = false;
			$metrics_config['basic']            = false;
			$metrics_config['detailed']         = false;
			$metrics_config['checkin_complete'] = false;
		} else {
			if ( ! isset( $metrics_config['basic'] ) ) {
				$metrics_config['basic'] = false;
			}
			if ( ! isset( $metrics_config['detailed'] ) ) {
				$metrics_config['detailed'] = false;
			}
			if ( ! isset( $metrics_config['checkin_complete'] ) ) {
				$metrics_config['checkin_complete'] = false;
			}
			foreach ( $metrics_config as $key => $value ) {
				if ( 'yes' === $value || true === $value || 1 === $value || '1' === $value ) {
					$metrics_config[ $key ] = true;
				} else {
					$metrics_config[ $key ] = false;
				}
			}
			if ( true !== $metrics_config['checkin_complete'] ) {
				$assets->register_script(
					array(
						'type'      => 'admin',
						'condition' => function( $hook ) use ( $provider ) {
							// $exclusions = array( 'toplevel_page_pf-menu' );
							// return $provider->check_hook_for_pressforward_string( $hook, $exclusions );
							return true;
						},
						'handle'    => $slug . '-checkin',
						'src'       => 'assets/js/checkin',
						'deps'      => array( 'jquery', 'pf' ),
						'localize'  => array(
							'name' => 'pf_checkin',
							'data' => array(
								'active' => $metrics_config['basic'],
							),
						),
					)
				);
			}

			$assets->register_script(
				array(
					'type'      => 'admin',
					'condition' => function( $hook ) use ( $provider ) {
						return pressforward( 'controller.template_factory' )->is_a_pf_page();
					},
					'handle'    => $slug . '-analytics',
					'src'       => 'assets/js/analytics',
					'deps'      => array( 'jquery', 'pf' ),
					'localize'  => array(
						'name' => 'pf_analytics',
						'data' => array(
							'active' => $metrics_config['detailed'],
						),
					),
				)
			);

		}

		$assets->register_script(
			array(
				'type'      => 'admin',
				'condition' => function( $hook ) use ( $provider ) {
					return $provider->check_hook_for_pressforward_string( $hook );
				},
				'handle'    => $slug . '-tinysort',
				'src'       => 'Libraries/jquery-tinysort/jquery.tinysort',
				'deps'      => array( 'pf' ),
			)
		);

		$assets->register_script(
			array(
				'type'      => 'admin',
				'condition' => function( $hook ) use ( $provider ) {
									return $provider->check_hook_for_pressforward_string( $hook );
				},
				'handle'    => $slug . '-twitter-bootstrap',
				'src'       => 'Libraries/twitter-bootstrap/js/bootstrap',
				'deps'      => array( 'pf' ),
			)
		);

		$assets->register_script(
			array(
				'type'      => 'admin',
				'condition' => function( $hook ) use ( $provider ) {
									$exclusions = array( 'toplevel_page_pf-menu' );
									return $provider->check_hook_for_pressforward_string( $hook, $exclusions );
				},
				'handle'    => $slug . '-tools',
				'src'       => 'assets/js/tools-imp',
				'deps'      => array( 'pf', $slug . '-twitter-bootstrap' ),
			)
		);

		$assets->register_script(
			array(
				'type'      => 'admin',
				'condition' => function( $hook ) use ( $provider ) {
									return $provider->check_hook_for_pressforward_string( $hook );
				},
				'handle'    => $slug . '-jq-fullscreen',
				'src'       => 'Libraries/jquery-fullscreen/jquery.fullscreen',
				'deps'      => array( 'pf' ),
			)
		);

		$assets->register_script(
			array(
				'type'      => 'admin',
				'condition' => function( $hook ) use ( $provider ) {
					return $provider->check_hook_for_pressforward_string( $hook );
				},
				'handle'    => $slug . '-sort-imp',
				'src'       => 'assets/js/sort-imp',
				'deps'      => array( 'pf', $slug . '-tinysort', $slug . '-twitter-bootstrap', $slug . '-jq-fullscreen' ),
			)
		);

		$assets->register_script(
			array(
				'type'      => 'admin',
				'condition' => function( $hook ) use ( $provider ) {
					return $provider->check_hook_for_pressforward_string( $hook );
				},
				'handle'    => $slug . '-views',
				'src'       => 'assets/js/views',
				'deps'      => array( 'pf', $slug . '-twitter-bootstrap', 'jquery-ui-core', 'jquery-effects-slide' ),
			)
		);

		$assets->register_script(
			array(
				'type'      => 'admin',
				'condition' => function( $hook ) use ( $provider ) {
					return $provider->check_hook_for_pressforward_string( $hook );
				},
				'handle'    => $slug . '-readability-imp',
				'src'       => 'assets/js/readability-imp',
				'deps'      => array( $slug . '-twitter-bootstrap', 'pf', $slug . '-views' ),
			)
		);

		$assets->register_script(
			array(
				'type'      => 'admin',
				'condition' => function( $hook ) use ( $provider ) {
					return $provider->check_hook_for_pressforward_string( $hook );
				},
				'handle'    => $slug . '-nomination-imp',
				'src'       => 'assets/js/nomination-imp',
				'deps'      => array( 'pf' ),
			)
		);

		$assets->register_script(
			array(
				'type'      => 'admin',
				'condition' => function( $hook ) use ( $provider ) {
					return $provider->check_hook_for_pressforward_string( $hook );
				},
				'handle'    => $slug . '-infiniscroll',
				'src'       => 'Libraries/jquery.infinitescroll',
				'deps'      => array( 'pf', $slug . '-views', $slug . '-readability-imp', 'jquery' ),
			)
		);

		$assets->register_script(
			array(
				'type'      => 'admin',
				'condition' => function( $hook ) use ( $provider ) {
					return $provider->check_hook_for_pressforward_string( $hook );
				},
				'handle'    => $slug . '-relationships',
				'src'       => 'assets/js/relationships',
				'deps'      => array( 'pf' ),
			)
		);

		$assets->register_script(
			array(
				'type'      => 'admin',
				'condition' => function( $hook ) use ( $provider ) {
					if ( 'false' == get_user_option( 'pf_user_scroll_switch', pressforward( 'controller.template_factory' )->user_id() ) ) {

						return false;
					}
									return $provider->check_hook_for_pressforward_string( $hook );
				},
				'handle'    => $slug . '-scrollimp',
				'src'       => 'assets/js/scroll-imp',
				'deps'      => array( $slug . '-infiniscroll', 'pf-relationships', $slug . '-views', 'jquery' ),
			)
		);

		$assets->register_script(
			array(
				'type'      => 'admin',
				'condition' => function( $hook ) use ( $provider ) {
					$exclusions = array( 'toplevel_page_pf-menu' );
					return $provider->check_hook_for_pressforward_string( $hook, $exclusions );
				},
				'handle'    => $slug . '-media-query-imp',
				'src'       => 'assets/js/media-query-imp',
				'deps'      => array( 'pf', 'thickbox', 'media-upload' ),
			)
		);

		$assets->register_script(
			array(
				'type'      => 'admin',
				'condition' => function( $hook ) use ( $provider ) {
					$exclusions = array( 'toplevel_page_pf-menu' );
					$inclusions = array( 'edit.php' );
					return $provider->check_hook_for_pressforward_string( $hook, $exclusions, $inclusions );
				},
				'handle'    => $slug . '-quick-edit',
				'src'       => 'assets/js/quick-edit',
				'deps'      => array( 'pf' ),
			)
		);

		$assets->register_script(
			array(
				'type'      => 'admin',
				'condition' => function( $hook ) use ( $provider ) {
					$exclusions = array( 'toplevel_page_pf-menu' );
					return $provider->check_hook_for_pressforward_string( $hook, $exclusions );
				},
				'handle'    => $slug . '-settings-tools',
				'src'       => 'assets/js/settings-tools',
				'deps'      => array( 'pf' ),
			)
		);

		$assets->register_script(
			array(
				'type'      => 'admin',
				'condition' => function( $hook ) use ( $provider ) {
					$exclusions = array( 'toplevel_page_pf-menu' );
					return $provider->check_hook_for_pressforward_string( $hook, $exclusions );
				},
				'handle'    => 'feed_control_script',
				'src'       => 'assets/js/feeds_control',
				'deps'      => array( 'pf', $slug . '-settings-tools', $slug . '-twitter-bootstrap' ),
			)
		);

		$assets->register_script(
			array(
				'type'      => 'admin',
				'condition' => function( $hook ) use ( $provider ) {
					// $exclusions = array('toplevel_page_pf-menu');
									return $provider->check_hook_for_pressforward_string( $hook );
				},
				'handle'    => $slug . '-tools',
				'src'       => 'assets/js/tools-imp',
				'deps'      => array( 'pf' ),
			)
		);

		$assets->register_script(
			array(
				'type'      => 'admin',
				'condition' => function( $hook ) use ( $provider ) {
					$inclusions = array( 'pressforward_page_pf-review' );
					return $provider->check_hook_for_pressforward_string( $hook, array(), $inclusions );
				},
				'handle'    => $slug . '-send-to-draft-imp',
				'src'       => 'assets/js/send-to-draft-imp',
				'deps'      => array( 'pf' ),
			)
		);

		$assets->register_script(
			array(
				'type'      => 'admin',
				'condition' => function( $hook ) use ( $provider ) {
					$inclusions = array( 'pressforward_page_pf-review' );
					return $provider->check_hook_for_pressforward_string( $hook, array(), $inclusions );
				},
				'handle'    => $slug . '-archive-nom-imp',
				'src'       => 'assets/js/nom-archive-imp',
				'deps'      => array( 'pf' ),
			)
		);

		$assets->register_script(
			array(
				'type'      => 'admin',
				'condition' => function( $hook ) use ( $provider ) {
					return $provider->check_hook_for_pressforward_string( $hook );
				},
				'handle'    => $slug . '-add-nom-imp',
				'src'       => 'assets/js/add-nom-imp',
				'deps'      => array( 'pf' ),
			)
		);
		// var_dump($assets); die();
	}

	public function check_hook_for_pressforward_string( $hook, $exclusions = array(), $inclusions = array(), $all = false ) {

		 $position_test_one = strpos( $hook, 'pressforward' );
		 $position_test_two = strpos( $hook, 'pf' );
		// $position_test_two = strpos($hook, 'edit.php');
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
