<?php
/**
 * Template for reset/refresh tools tab.
 *
 * @package PressForward
 */

?>

<p>
	<?php
		esc_html_e( 'The following options are advanced tools for admins attempting to test, debug, or execute advanced functionality. They are capable of breaking your retrieval process.', 'pressforward' );
	?>
</p>

<div id="responses"></div>

<p>
	<button type="submit" class="cleanfeeds btn btn-small" id="cleanfeeds" value="<?php esc_attr_e( 'Clean Up', 'pressforward' ); ?>"><?php esc_html_e( 'Clean Up', 'pressforward' ); ?></button>
	<?php
		esc_html_e( ' the feed items. You can press this button to manually initiate the process of selecting feed items more than 2 months old. There is a chance of initiating this process simultaneously with a process triggered automatically every 30 minutes. If this occurs, error messages will likely appear in your server logs.', 'pressforward' );
	?>
</p>

<?php
do_action( 'pf_tools' );
