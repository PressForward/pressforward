<?php
/**
 * Template for reset/refresh tools tab.
 *
 * @package PressForward
 */

?>

<div id="responses"></div>

<p><?php esc_html_e( 'Use the button below to clean up feed items. Pressing the button will initiate the process of selecting feed items more than two months old. There is a chance of initiating this process simultaneously with a process triggered automatically every 30 minutes. If this occurs, error messages will likely appear in your server logs.', 'pressforward' ); ?></p>

<p>
	<button type="submit" class="cleanfeeds button button-secondary" id="cleanfeeds" value="<?php esc_attr_e( 'Clean Up', 'pressforward' ); ?>"><?php esc_html_e( 'Clean Up', 'pressforward' ); ?></button>
</p>

<?php
do_action( 'pf_tools' );
