<?php

class OPML_Maker {

	function __construct( $OPML_obj ) {
		if ( 'OPML_Object' != get_class( $OPML_obj ) ) {
			return false;
		} else {
			$this->obj = $OPML_obj;
		}
		$this->force_safe = true;
	}

	function force_safe( $force = true ) {
		if ( $force ) {
			$this->force_safe = true;
			return true;
		}
	}

	function assemble_tag( $tag, $obj, $self_closing = false, $filter = false ) {
		if ( empty( $obj ) ) {
			return '';
		}
		$s = "<$tag";
		foreach ( $obj as $property => $value ) {
			if ( ! empty( $filter ) && in_array( $property, $filter ) ) {
				continue;
			}
			if ( $this->force_safe ) {
				$s .= ' ' . esc_attr( $property ) . '="' . esc_attr( $value ) . '"';
			} else {
				$s .= ' ' . $property . '="' . $value . '"';
			}
		}
		if ( $self_closing ) {
			$s .= '/>';
		} else {
			$s .= '>';
		}
		$s .= "\n";
		return $s;
	}

	public function template( $title = 'Blogroll' ) {
		ob_start();
		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		?>
		<opml version="2.0">
		    <head>
		        <title><?php echo $title; ?></title>
		        	<expansionState></expansionState>
					<linkPublicUrl><?php // @todo ?></linkPublicUrl>
					<lastCursor>1</lastCursor>
		    </head>

		    <body>
		    	<?php
		    		$c = 0;
				foreach ( $this->obj->folders as $folder ) {
					if ( $c > 0 ) {
						echo "\n\t\t\t";
					} else {

					}
					echo $this->assemble_tag( 'outline', $folder );
						// var_dump($folder);
						$feeds = $this->obj->get_feeds_by_folder( $folder->slug );
						// var_dump($feeds);
					if ( ! empty( $feeds ) ) {
						foreach ( $feeds as $feed ) {
							// var_dump($feed);
							echo "\t\t\t\t" . $this->assemble_tag( 'outline',$feed,true,array( 'folder', 'feedUrl' ) );
						}
					}
		    			echo "\t\t\t" . '</outline>';
		    			$c++;
				}
		    		echo "\n";
		    		$folderless_count = 0;
				$folderless_feeds = $this->obj->get_feeds_without_folder();
				if ( !empty( $folderless_feeds ) ){
					foreach ( $folderless_feeds as $feed ) {
						if ( $c > 0 ) {
							echo "\t\t\t";
						}
						echo $this->assemble_tag( 'outline',$feed,true,array( 'folder', 'feedUrl' ) );
						$c++;
					}
				}
		    		echo "\n";
		    	?>
		    </body>
		</opml>
		<?php
		// get OPML from buffer and save to file
		$opml = ob_get_clean();
		$this->file_contents = $opml;
		return $opml;
	}

	public function make_as_file( $filepath = false ) {
		if ( ! $filepath ) {
			file_put_contents( plugin_dir_path( __FILE__ ) . 'blogroll.opml', $this->file_contents );
		} else {
			file_put_contents( $filepath, $this->file_contents );
		}
	}

}
