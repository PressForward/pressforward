<?php
/**
 * OPML maker.
 *
 * @package PressForward
 */

/**
 * OPML_Maker class.
 */
class OPML_Maker {
	/**
	 * OPML file contents.
	 *
	 * @access public
	 * @var string
	 */
	public $file_contents = '';

	/**
	 * Whether to force safe mode.
	 *
	 * @access public
	 * @var bool
	 */
	public $force_safe = true;

	/**
	 * OPML object.
	 *
	 * @access public
	 * @var OPML_Object
	 */
	public $obj;

	/**
	 * Constructor.
	 *
	 * @param object $opml_obj OPML_Object object.
	 */
	public function __construct( $opml_obj ) {
		if ( 'OPML_Object' !== get_class( $opml_obj ) ) {
			return;
		} else {
			$this->obj = $opml_obj;
		}

		$this->force_safe = true;
	}

	/**
	 * Sets the "force safe" attribute.
	 *
	 * @param bool $force True to force.
	 */
	public function force_safe( $force = true ) {
		if ( $force ) {
			$this->force_safe = true;
			return true;
		}
	}

	/**
	 * Builds a tag.
	 *
	 * @param string      $tag              Tag name.
	 * @param object|bool $obj              Data object.
	 * @param bool        $self_closing     Whether the tag is self-closing.
	 * @param array       $filter Optional. Array of properties that are skipped when building attributes.
	 * @return string
	 */
	public function assemble_tag( $tag, $obj, $self_closing = false, $filter = [] ) {
		if ( empty( $obj ) ) {
			return '';
		}

		$s = "<$tag";

		if ( is_iterable( $obj ) ) {
			foreach ( $obj as $property => $value ) {
				if ( ! empty( $filter ) && in_array( $property, $filter, true ) ) {
					continue;
				}

				if ( $this->force_safe ) {
					$s .= ' ' . esc_attr( $property ) . '="' . esc_attr( $value ) . '"';
				} else {
					$s .= ' ' . $property . '="' . $value . '"';
				}
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

	/**
	 * Top-level generator for OPML file.
	 *
	 * @param string $title Defaults to 'Blogroll'.
	 */
	public function template( $title = 'Blogroll' ) {
		ob_start();
		echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
		?>
		<opml version="2.0">
			<head>
				<title><?php echo esc_html( $title ); ?></title>
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
					}

					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo $this->assemble_tag( 'outline', $folder );

					$feeds = $this->obj->get_feeds_by_folder( $folder->slug );
					if ( ! empty( $feeds ) ) {
						foreach ( $feeds as $feed ) {
							// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							echo "\t\t\t\t" . $this->assemble_tag( 'outline', $feed, true, array( 'folder', 'feedUrl' ) );
						}
					}

					echo "\t\t\t" . '</outline>';
					++$c;
				}

				echo "\n";

				$folderless_count = 0;
				$folderless_feeds = $this->obj->get_feeds_without_folder();
				if ( ! empty( $folderless_feeds ) ) {
					foreach ( $folderless_feeds as $feed ) {
						if ( $c > 0 ) {
							echo "\t\t\t";
						}

						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						echo $this->assemble_tag( 'outline', $feed, true, array( 'folder', 'feedUrl' ) );
						++$c;
					}
				}

				echo "\n";
				?>
			</body>
		</opml>

		<?php

		// Get OPML from buffer and save to file.
		$opml = ob_get_clean();

		$this->file_contents = $opml;

		return $opml;
	}

	/**
	 * Makes a file from the OPML template.
	 *
	 * @todo Use WP filesystem.
	 *
	 * @param string $filepath Target file path. Defaults to blogroll.opml in the plugin directory.
	 */
	public function make_as_file( $filepath = '' ) {
		// phpcs:disable WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		if ( ! $filepath ) {
			file_put_contents( plugin_dir_path( __FILE__ ) . 'blogroll.opml', $this->file_contents );
		} else {
			file_put_contents( $filepath, $this->file_contents );
		}
		// phpcs:enable WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
	}
}
