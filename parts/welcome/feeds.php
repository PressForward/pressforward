<?php
/**
 * 'Feeds' meta box on PressForward landing page.
 *
 * @since 5.7.0
 *
 * @package PressForward
 */

$last_24_hours = pressforward( 'utility.retrieval' )->get_feed_item_count( false, time() - DAY_IN_SECONDS );
$last_7_days   = pressforward( 'utility.retrieval' )->get_feed_item_count( false, time() - ( 7 * DAY_IN_SECONDS ) );
$last_30_days  = pressforward( 'utility.retrieval' )->get_feed_item_count( false, time() - ( 30 * DAY_IN_SECONDS ) );

$subscribed_feed_count = pressforward( 'utility.retrieval' )->get_feed_count();
$alert_feed_count      = pressforward( 'admin.subscribed_feeds' )->alertbox->alert_count();

$single_pf_svg = '<svg xmlns="http://www.w3.org/2000/svg" height="48px" viewBox="0 -960 960 960" width="48px" fill="#5f6368"><path d="M194.96-120Q164-120 142-142.04q-22-22.05-22-53Q120-226 142.04-248q22.05-22 53-22Q226-270 248-247.96q22 22.05 22 53Q270-164 247.96-142q-22.05 22-53 22ZM710-120q0-123-46-229.5T537-537q-81-81-187.58-127Q242.85-710 120-710v-90q142 0 265 53t216 146q93 93 146 216t53 265h-90Zm-258 0q0-70-25.8-131.48Q400.4-312.96 355-360q-45-47-105.03-73.5Q189.95-460 120-460v-90q89 0 165.5 33.5t133.64 92.42q57.15 58.93 90 137Q542-209 542-120h-90Z"/></svg>';

?>

<div class="pf-welcome-block-header">
	<div class="pf-welcome-block-icon">
		<?php echo $single_pf_svg; // phpcs:ignore ?>
	</div>

	<div>
		<h3><?php esc_html_e( 'Feeds', 'pressforward' ); ?></h3>
		<p><?php esc_html_e( 'PressForward regularly collects items from your subscribed feeds. Review incoming feed items to select items for promotion.', 'pressforward' ); ?></p>
	</div>
</div>

<div class="pf-welcome-blocks">
	<div class="pf-welcome-block pf-welcome-block-recent-feed-items">
		<div class="pf-welcome-block-row">
			<div class="pf-welcome-block-content">
				<h3><?php esc_html_e( 'New incoming feed items in the last&hellip;', 'pressforward' ); ?></h3>

				<ul class="pf-welcome-list">
					<li>
						<?php
						printf(
							// translators: %s: number of items.
							esc_html__( '24 hours: %s', 'pressforward' ),
							'<span class="pf-welcome-block-number">' . esc_html( number_format_i18n( $last_24_hours ) ) . '</span>'
						);
						?>
					</li>

					<li>
						<?php
						printf(
							// translators: %s: number of items.
							esc_html__( '7 days: %s', 'pressforward' ),
							'<span class="pf-welcome-block-number">' . esc_html( number_format_i18n( $last_7_days ) ) . '</span>'
						);
						?>
					</li>

					<li>
						<?php
						printf(
							// translators: %s: number of items.
							esc_html__( '30 days: %s', 'pressforward' ),
							'<span class="pf-welcome-block-number">' . esc_html( number_format_i18n( $last_30_days ) ) . '</span>'
						);
						?>
					</li>
				</ul>

				<div class="pf-welcome-block-action-links">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=pf-all-content' ) ); ?>"><?php esc_html_e( 'All Incoming Feed Items', 'pressforward' ); ?></a>
				</div>
			</div>
		</div>
	</div>

	<div class="pf-welcome-block pf-welcome-block-recent-nomination-counts">
		<h3><?php esc_html_e( 'Subscribed Feeds', 'pressforward' ); ?></h3>

		<p>
			<?php
			printf(
				// translators: %s: number of feeds.
				esc_html( _n( 'You are subscribed to %s feed.', 'You are subscribed to %s feeds.', $subscribed_feed_count, 'pressforward' ) ),
				'<span class="pf-welcome-block-number">' . esc_html( number_format_i18n( $subscribed_feed_count ) ) . '</span>'
			);
			?>
		</p>

		<p>
			<?php
			printf(
				// translators: %s: number of alerts.
				esc_html( _n( '%s feed has an alert and may need your attention', '%s feeds have alerts and may need your attention.', $alert_feed_count, 'pressforward' ) ),
				'<span class="pf-welcome-block-number">' . esc_html( number_format_i18n( $alert_feed_count ) ) . '</span>'
			);
			?>
		</p>

		<div class="pf-welcome-block-action-links">
			<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=pf_feed' ) ); ?>"><?php esc_html_e( 'Manage Feeds', 'pressforward' ); ?></a>

			<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=pf_feed&post_status=alert_specimen' ) ); ?>"><?php esc_html_e( 'Feeds with Alerts', 'pressforward' ); ?></a>

			<a href="<?php echo esc_url( admin_url( 'admin.php?page=pf-feeder' ) ); ?>"><?php esc_html_e( 'Add New Feed', 'pressforward' ); ?></a>
		</div>
	</div>
</div>
