<?php
/**
 * Scraper class for fetching reviews from Trustpilot public pages.
 *
 * @package TruspilotReview
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fetches and parses reviews from Trustpilot public profile pages.
 */
final class Truspilot_Review_Scraper {

	/**
	 * HTML parser instance.
	 *
	 * @var Truspilot_Review_Parser
	 */
	private $parser;

	/**
	 * Constructor.
	 *
	 * @param Truspilot_Review_Parser $parser HTML parser.
	 */
	public function __construct( Truspilot_Review_Parser $parser ) {
		$this->parser = $parser;
	}

	/**
	 * Scrape public Trustpilot pages and normalize reviews.
	 *
	 * @param string $base_url  Trustpilot profile URL.
	 * @param int    $max_pages Maximum pages.
	 * @return array
	 */
	public function scrape( $base_url, $max_pages ) {
		$reviews = array();
		$seen    = array();

		for ( $page = 1; $page <= $max_pages; $page++ ) {
			$url      = add_query_arg( 'page', $page, $base_url );
			$response = wp_safe_remote_get(
				$url,
				array(
					'timeout'     => 20,
					'redirection' => 3,
					'user-agent'  => 'Mozilla/5.0 (compatible; TruspilotReviewBlocks/' . TRUSPILOT_REVIEW_VERSION . '; ' . home_url( '/' ) . ')',
					'headers'     => array(
						'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
						'Accept-Language' => 'en-US,en;q=0.9',
					),
				)
			);

			if ( is_wp_error( $response ) ) {
				return array(
					'reviews' => $reviews,
					'error'   => 'request_failed',
				);
			}

			$code = wp_remote_retrieve_response_code( $response );
			$body = wp_remote_retrieve_body( $response );

			if ( 200 !== $code ) {
				return array(
					'reviews' => $reviews,
					'error'   => false !== stripos( $body, 'Verifying your connection' ) ? 'trustpilot_blocked' : 'http_error',
				);
			}

			if ( false !== stripos( $body, 'Verifying your connection' ) ) {
				return array(
					'reviews' => $reviews,
					'error'   => 'trustpilot_blocked',
				);
			}

			$page_reviews = $this->parser->parse_public_reviews_html( $body );

			if ( empty( $page_reviews ) ) {
				break;
			}

			foreach ( $page_reviews as $review ) {
				$hash = md5( strtolower( wp_strip_all_tags( $review['body'] ) ) );
				if ( isset( $seen[ $hash ] ) ) {
					continue;
				}
				$seen[ $hash ] = true;
				$reviews[]     = $review;
			}

			if ( $page < $max_pages ) {
				sleep( 1 );
			}
		}

		return array(
			'reviews' => $reviews,
			'error'   => empty( $reviews ) ? 'no_reviews' : '',
		);
	}
}
