<?php
/**
 * 'Nominations Awaiting Review' meta box on PressForward landing page.
 *
 * @since 5.7.0
 *
 * @package PressForward
 */

$total_unpromoted_items = pressforward( 'utility.forward_tools' )->get_nominated_count( true );
$total_since_last_visit = pressforward( 'utility.forward_tools' )->get_nominated_count( true, pressforward( 'admin.menu' )->get_welcome_panel_last_visit_time() );

$last_24_hours = pressforward( 'utility.forward_tools' )->get_nominated_count( false, time() - DAY_IN_SECONDS );
$last_7_days   = pressforward( 'utility.forward_tools' )->get_nominated_count( false, time() - ( 7 * DAY_IN_SECONDS ) );
$last_30_days  = pressforward( 'utility.forward_tools' )->get_nominated_count( false, time() - ( 30 * DAY_IN_SECONDS ) );

$single_pf_svg = '<svg width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
  <polygon points="0,0 100,50 0,100" />
</svg>';

?>

<div class="pf-welcome-block-header">
	<div class="pf-welcome-block-icon">
		<?php echo $single_pf_svg; // phpcs:ignore ?>
	</div>

	<div>
		<h3><?php esc_html_e( 'Nominations', 'pressforward' ); ?></h3>
		<p><?php esc_html_e( 'Nominated items come either through the "Nominate This" bookmarklet, or are selected by your editorial team from incoming feed items. Review your pending nominations and select items to promote.', 'pressforward' ); ?></p>
	</div>
</div>

<div class="pf-welcome-blocks">
	<div class="pf-welcome-block pf-welcome-block-total-nominations">
		<div class="pf-welcome-block-row">
			<div class="pf-welcome-block-content">
				<div class="pf-welcome-block-nomination-count">
					<?php
					printf(
						// translators: %s: number of nominations.
						esc_html( _n( '%s nomination is awaiting review', '%s nominations are awaiting review', $total_unpromoted_items, 'pressforward' ) ),
						'<span class="pf-welcome-block-number">' . esc_html( number_format_i18n( $total_unpromoted_items ) ) . '</span>'
					);
					?>
				</div>

				<div class="pf-welcome-block-action-links">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=pf-review' ) ); ?>"><?php esc_html_e( 'Review Nominations', 'pressforward' ); ?></a>
				</div>
			</div>
		</div>
	</div>

	<div class="pf-welcome-block pf-welcome-block-recent-nomination-counts">
		<h3><?php esc_html_e( 'New nominations in the last&hellip;', 'pressforward' ); ?></h3>

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

		<p>
			<?php
			printf(
				// translators: %s: number of items.
				esc_html( _n( 'Since your last visit to this Welcome panel, %s new item has been nominated', 'Since your last visit to this Welcome panel, %s new items have been nominated.', $total_since_last_visit, 'pressforward' ) ),
				'<span class="pf-welcome-block-number">' . esc_html( number_format_i18n( $total_since_last_visit ) ) . '</span>'
			);
			?>
		</p>
	</div>
</div>
