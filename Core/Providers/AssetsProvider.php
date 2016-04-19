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
        //var_dump($this->container); die();
		$register = $this->container->fetch(
			'assets'
		);

		$this->add_assets( $register );
	}

    protected function add_assets( Assets $assets ){
        //$this->container =
        $slug = $this->container->fetch( 'slug' );
		$url  = $this->container->fetch( 'url' );
		$debug = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG;
		if ( $debug ) {
			$assets->set_debug( true );
		}

        $provider = $this;

		$assets->register_style(array(
			'type'	=>	'admin',
			'condition'	=> (function($hook) use ($provider){
								$exclusions = array('pf-options');
								//return true;
								return $provider->check_hook_for_pressforward_string($hook, $exclusions);
							}),
			'handle'	=> $slug.'-reset-style',
			'src'		=> 'assets/css/reset'
		));

		$assets->register_style(array(
			'type'	=>	'admin',
			'condition'	=> function($hook) use ($provider){
								$exclusions = array('pf-options');
								return $provider->check_hook_for_pressforward_string($hook, $exclusions);
							},
			'handle'	=> $slug.'-bootstrap-style',
			'src'		=> 'lib/twitter-bootstrap/css/bootstrap'
		));

		$assets->register_style(array(
			'type'	=>	'admin',
			'condition'	=> function($hook) use ($provider){
								$exclusions = array('pf-options');
								return $provider->check_hook_for_pressforward_string($hook, $exclusions);
							},
			'handle'	=> $slug.'-bootstrap-responsive-style',
			'src'		=> 'lib/twitter-bootstrap/css/bootstrap-responsive'
		));

		$assets->register_style( array(
			'type'	=>	'admin',
			'condition'	=> function($hook) use ($provider){
								$exclusions = array('pf-options');
								return $provider->check_hook_for_pressforward_string($hook, $exclusions);
							},
			'handle'	=>	$slug.'-style',
			'src'		=>	'assets/css/pressforward',
			'deps'		=>	array( $slug . '-bootstrap-style', $slug . '-bootstrap-responsive-style' )
		) );

		$assets->register_style(array(
			'type'	=>	'admin',
			'condition'	=> function($hook) use ($provider){
								$exclusions = array();
								return $provider->check_hook_for_pressforward_string($hook, $exclusions);
							},
			'handle'	=> $slug.'-settings-style',
			'src'		=> 'assets/css/pf-settings'
		));

		$assets->register_style(array(
			'type'	=>	'admin',
			'condition'	=> function($hook) use ($provider){
								return true;
							},
			'handle'	=> $slug.'-subscribed-styles',
			'src'		=> 'assets/css/pf-subscribed'
		));

		$assets->register_style(array(
			'type'	=>	'admin',
			'condition'	=> function($hook) use ($provider){
								return true;
							},
			'handle'	=> $slug.'-alert-styles',
			'src'		=> 'assets/css/alert-styles'
		));

		# Scripts


		$assets->register_script(
			array(
				'type'	=>	'admin',
				'condition'	=> function(){ return true; },
				'handle'	=>	$slug.'-jq-fullscreen',
				'src'		=>	'lib/jquery-fullscreen/jquery.fullscreen',
				'deps'		=>	array( 'jquery' )
			)
		);

		$assets->register_script(
			array(
				'type'	=>	'admin',
				'condition'	=> function(){ return true; },
				'handle'	=>	$slug.'-twitter-bootstrap',
				'src'		=>	'lib/twitter-bootstrap/js/bootstrap',
				'deps'		=>	array( 'jquery' )
			)
		);

		$assets->register_script(
			array(
				'type'	=>	'admin',
				'condition'	=> function(){ return true; },
				'handle'	=>	$slug.'-tools',
				'src'		=>	'assets/js/tools-imp',
				'deps'		=>	array( 'jquery' )
			)
		);

		$assets->register_script( array(
			'type'	=>	'admin',
			'condition'	=> function(){ return true; },
			'handle'	=>	$slug.'-settings-tools',
			'src'		=>	'assets/js/settings-tools',
			'deps'		=>	array( 'jquery' )
		) );

		$assets->register_script( array(
			'type'	=>	'admin',
			'condition'	=> function(){ return true; },
			'handle'	=>	$slug.'-views',
			'src'		=>	'assets/js/views',
			'deps'		=>	array( PF_SLUG . '-twitter-bootstrap', 'jquery-ui-core', 'jquery-effects-slide'  )
		) );

		$assets->register_script( array(
			'type'	=>	'admin',
			'condition'	=> function(){ return true; },
			'handle'	=>	$slug.'-readability-imp',
			'src'		=>	'assets/js/readability-imp',
			'deps'		=>	array( PF_SLUG . '-twitter-bootstrap', 'jquery', PF_SLUG . '-views' )
		) );

		$assets->register_script( array(
			'type'	=>	'admin',
			'condition'	=> function(){ return true; },
			'handle'	=>	$slug.'-infiniscroll',
			'src'		=>	'lib/jquery.infinitescroll',
			'deps'		=>	array( 'jquery', PF_SLUG . '-views', PF_SLUG . '-readability-imp', 'jquery' )
		) );

		$assets->register_script( array(
			'type'	=>	'admin',
			'condition'	=> function(){ return true; },
			'handle'	=>	$slug.'-scrollimp',
			'src'		=>	'assets/js/scroll-imp',
			'deps'		=>	array( PF_SLUG . '-infiniscroll', 'pf-relationships', PF_SLUG . '-views' )
		) );

		$assets->register_script( array(
			'type'	=>	'admin',
			'condition'	=> function(){ return true; },
			'handle'	=>	$slug.'-relationships',
			'src'		=>	'assets/js/relationships',
			'deps'		=>	array( 'jquery' )
		) );

		$assets->register_script( array(
			'type'	=>	'admin',
			'condition'	=> function(){ return true; },
			'handle'	=>	$slug.'-tinysort',
			'src'		=>	'lib/jquery-tinysort/jquery.tinysort',
			'deps'		=>	array( 'jquery' )
		) );

		$assets->register_script( array(
			'type'	=>	'admin',
			'condition'	=> function(){ return true; },
			'handle'	=>	$slug.'-media-query-imp',
			'src'		=>	'assets/js/media-query-imp',
			'deps'		=>	 array( 'jquery', 'thickbox', 'media-upload' )
		) );

		$assets->register_script( array(
			'type'	=>	'admin',
			'condition'	=> function(){ return true; },
			'handle'	=>	$slug.'-sort-imp',
			'src'		=>	'assets/js/sort-imp',
			'deps'		=>	array( PF_SLUG . '-tinysort', PF_SLUG . '-twitter-bootstrap', PF_SLUG . '-jq-fullscreen' )
		) );

		$assets->register_script( array(
			'type'	=>	'admin',
			'condition'	=> function(){ return true; },
			'handle'	=>	$slug.'-quick-edit',
			'src'		=>	'assets/js/quick-edit',
			'deps'		=>	array( 'jquery' )
		) );

		$assets->register_script( array(
			'type'	=>	'admin',
			'condition'	=> function(){ return true; },
			'handle'	=>	$slug.'-settings-tools',
			'src'		=>	'assets/js/settings-tools',
			'deps'		=>	array( 'jquery' )
		) );

		$assets->register_script( array(
			'type'	=>	'admin',
			'condition'	=> function(){ return true; },
			'handle'	=>	$slug.'-tools',
			'src'		=>	'assets/js/tools-imp',
			'deps'		=>	array( 'jquery' )
		) );

		$assets->register_script( array(
			'type'	=>	'admin',
			'condition'	=> function(){ return true; },
			'handle'	=>	$slug.'-heartbeat',
			'src'		=>	'assets/js/pf-heartbeat',
			'deps'		=>	array( 'heartbeat', 'jquery-ui-progressbar', 'jquery' )
		) );

		$assets->register_script( array(
			'type'	=>	'admin',
			'condition'	=> function(){ return true; },
			'handle'	=>	$slug.'-send-to-draft-imp',
			'src'		=>	'assets/js/send-to-draft-imp',
			'deps'		=>	array( 'jquery' )
		) );

		$assets->register_script( array(
			'type'	=>	'admin',
			'condition'	=> function(){ return true; },
			'handle'	=>	$slug.'-archive-nom-imp',
			'src'		=>	'assets/js/nom-archive-imp',
			'deps'		=>	array( 'jquery' )
		) );

		$assets->register_script( array(
			'type'	=>	'admin',
			'condition'	=> function(){ return true; },
			'handle'	=>	$slug.'-add-nom-imp',
			'src'		=>	'assets/js/add-nom-imp',
			'deps'		=>	array( 'jquery' )
		) );
		//var_dump($assets); die();
	}

	public function check_hook_for_pressforward_string($hook, $exclusions = array()){

         $position_test_one = strpos($hook, 'pressforward');
         $position_test_two = strpos($hook, 'pf');
		 if ( ( false === $position_test_one ) && ( false === $position_test_two ) ){ return false; }

		 if (!empty($exclusions)){
		 		 foreach ($exclusions as $exclusion){
		 		 	if (false !== strpos($hook, $exclusion)){
		 		 		return false;
		 		 	}
		 		 }
		 }

		 return true;
	}

}
