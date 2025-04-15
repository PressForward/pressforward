<?php
/**
 * Google Scholar module.
 *
 * @package PressForward
 */

use PressForward\Interfaces\FeedSource;

/**
 * PF_Google_Scholar class.
 */
class PF_Google_Scholar extends PF_Module implements FeedSource {
	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->feed_type = 'google-scholar';
		parent::start();
	}

	/**
	 * Gets feed items.
	 *
	 * @param \PressForward\Core\Models\Feed $feed Feed object.
	 * @return array
	 */
	public function get_feed_items( \PressForward\Core\Models\Feed $feed ) {
		$fetched = $this->fetch( $feed );
		return $fetched;
	}

	/**
	 * Fetches data from URL.
	 *
	 * @param \PressForward\Core\Models\Feed $feed Feed object.
	 * @return array|\WP_Error
	 */
	public function fetch( $feed ) {
		$url = $feed->get( 'remote_feed_url' );

		// Get the remote HTML content using WordPress functions.
		$response = wp_remote_get(
			$url,
			[
				'timeout'    => 30,
				'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.110 Safari/537.36',
			]
		);

		// Check for errors.
		if ( is_wp_error( $response ) ) {
			return [
				'success' => false,
				'message' => $response->get_error_message(),
				'entries' => [],
			];
		}

		// Get the body content.
		$html = wp_remote_retrieve_body( $response );

		// Check if we got content.
		if ( empty( $html ) ) {
			return [
				'success' => false,
				'message' => 'No HTML content retrieved',
				'entries' => [],
			];
		}

		// Load HTML into DOMDocument.
		$doc = new DOMDocument();

		// Suppress errors for malformed HTML.
		libxml_use_internal_errors( true );
		$doc->loadHTML( $html );
		libxml_clear_errors();

		// Create a DOMXPath object for querying the DOM.
		$xpath = new DOMXPath( $doc );

		// Find all entries with class gs_ri (main result container).
		$entries_nodes = $xpath->query( '//div[@class="gs_ri"]' );

		$entries = [];

		foreach ( $entries_nodes as $entry_node ) {
			$entry = [];

			// Get the title and link (within h3.gs_rt).
			$title_node = $xpath->query( './/h3[@class="gs_rt"]', $entry_node )->item( 0 );
			if ( $title_node ) {
				$link_node = $xpath->query( './/a', $title_node )->item( 0 );
				if ( $link_node ) {
					// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					$entry['title'] = trim( $link_node->textContent );
					$entry['link']  = $link_node->getAttribute( 'href' );
				} else {
					// Sometimes title has no link.
					// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
					$entry['title'] = trim( $title_node->textContent );
					$entry['link']  = '';
				}
			}

			// Get author, publication venue, year (within div.gs_a).
			$author_pub_node = $xpath->query( './/div[@class="gs_a"]', $entry_node )->item( 0 );
			if ( $author_pub_node ) {
				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
				$author_pub_text = trim( $author_pub_node->textContent );

				$entry['author_publication_info'] = $author_pub_text;

				// Try to extract year - often in format YYYY.
				if ( preg_match( '/\b(19|20)\d{2}\b/', $author_pub_text, $matches ) ) {
					$entry['year'] = $matches[0];
				}

				// Try to extract authors (usually before first dash).
				$parts = explode( '-', $author_pub_text, 2 );
				if ( count( $parts ) > 0 ) {
					$entry['authors'] = trim( $parts[0] );

					// If we have publication info (after first dash).
					if ( count( $parts ) > 1 ) {
						$entry['publication'] = trim( $parts[1] );
					}
				}
			}

			// Get snippet/abstract (within div.gs_rs).
			$snippet_node = $xpath->query( './/div[@class="gs_rs"]', $entry_node )->item( 0 );
			if ( $snippet_node ) {
				$entry['snippet'] = trim( $snippet_node->textContent );
			}

			// Get citation info (within div.gs_fl).
			$cite_node = $xpath->query( './/div[@class="gs_fl"]/a[contains(text(), "Cited by")]', $entry_node )->item( 0 );
			if ( $cite_node ) {
				$cite_text = $cite_node->textContent;
				if ( preg_match( '/Cited by (\d+)/', $cite_text, $matches ) ) {
					$entry['cited_by_count'] = intval( $matches[1] );
					$entry['cited_by_link']  = $cite_node->getAttribute( 'href' );

					// Google Scholar relative URLs need to be made absolute.
					if ( substr( $entry['cited_by_link'], 0, 4 ) !== 'http' ) {
						$entry['cited_by_link'] = 'https://scholar.google.com' . $entry['cited_by_link'];
					}
				}
			}

			$entries[] = $entry;
		}

		return [
			'success' => true,
			'message' => 'Fetched successfully',
			'entries' => $entries,
		];
	}

	/**
	 * Performs a health check on the feed.
	 *
	 * @param \PressForward\Core\Models\Feed $feed        Feed object.
	 * @param bool                           $is_new_feed Whether the feed is new.
	 */
	public function health_check( \PressForward\Core\Models\Feed $feed, $is_new_feed = false ) {
		// Implement the logic to perform a health check on the feed.
	}
}
