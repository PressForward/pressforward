<?php
/**
 * Template for Import OPML tab.
 *
 * @package PressForward
 */

// Check to see whether OPML uploads are allowed.
$opml_is_allowed    = false;
$allowed_mime_types = get_allowed_mime_types();
foreach ( $allowed_mime_types as $ext => $mime ) {
	$exts = explode( '|', $ext );
	if ( in_array( 'opml', $exts, true ) ) {
		$opml_is_allowed = true;
	}
}

?>

<p>
	<?php echo wp_kses_post( __( '<a href="http://opml.org/">OPML</a> is a file format that allows you to import and export RSS feed subscriptions. <a href="https://en.wikipedia.org/wiki/Opml">Learn more about OPML.</a>', 'pressforward' ) ); ?>
</p>

<p>
	<?php esc_html_e( 'Use this tool to import an OPML file from a URL or upload an OPML file from your computer. Please note that large OPML files may take some time to process', 'pressforward' ); ?>
</p>

<form method="post" action="options.php" enctype="multipart/form-data">

<div class="pf_feeder_input_box">
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row">
				<?php esc_html_e( 'Import from URL', 'pressforward' ); ?>
			</th>

			<td>
				<label class="screen-reader-text" for="pf_feedlist-opml_url]"><?php esc_html_e( 'Enter the URL of the OPML file:', 'pressforward' ); ?></label>
				<input id="pf_feedlist-opml_url" class="pf_opml_file_upload_field regular-text" type="text" name="pf_feedlist[opml]" value="" placeholder="<?php esc_attr_e( 'OPML URL', 'pressforward' ); ?>" />
				<input type="submit" class="button-secondary" value="<?php esc_html_e( 'Import', 'pressforward' ); ?>" />
			</td>
		</tr>

		<?php if ( $opml_is_allowed ) : ?>
			<tr>
				<th scope="row">
					<?php esc_html_e( 'Upload OPML file', 'pressforward' ); ?>
				</th>

				<td>
					<label class="screen-reader-text" for="pf_feedlist-opml_upload"><?php esc_html_e( 'Upload an OPML file:', 'pressforward' ); ?></label>
					<input type="file" id="pf_feedlist-opml_upload" name="opml-upload" accept=".xml, .opml" />
					<input type="submit" class="button-secondary" value="<?php esc_html_e( 'Import', 'pressforward' ); ?>" />
				</td>
			</tr>
		<?php endif; ?>
	</table>

</div>

<?php settings_fields( 'pf_feedlist_group' ); ?>
</form>
