<?php
/**
 * Parser class for extracting reviews from Trustpilot page HTML.
 *
 * @package TruspilotReview
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Extracts review data from Trustpilot __NEXT_DATA__ and JSON-LD payloads.
 */
final class Truspilot_Review_Parser {

	/**
	 * Parse public Trustpilot HTML for reviews.
	 *
	 * @param string $html HTML.
	 * @return array
	 */
	public function parse_public_reviews_html( $html ) {
		if ( ! is_string( $html ) || '' === $html ) {
			return array();
		}

		$reviews = array();

		if ( preg_match( '#<script[^>]+id=["\']__NEXT_DATA__["\'][^>]*>(.*?)</script>#is', $html, $match ) ) {
			$decoded = json_decode( html_entity_decode( trim( $match[1] ), ENT_QUOTES, 'UTF-8' ), true );
			if ( is_array( $decoded ) ) {
				$reviews = $this->collect_next_data_reviews( $decoded );
			}
		}

		if ( ! empty( $reviews ) ) {
			return $reviews;
		}

		if ( preg_match_all( '#<script[^>]+type=["\']application/ld\+json["\'][^>]*>(.*?)</script>#is', $html, $matches ) ) {
			foreach ( $matches[1] as $json ) {
				$decoded = json_decode( html_entity_decode( trim( $json ), ENT_QUOTES, 'UTF-8' ), true );
				if ( is_array( $decoded ) ) {
					$reviews = array_merge( $reviews, $this->collect_schema_reviews( $decoded ) );
				}
			}
		}

		return $reviews;
	}

	/**
	 * Collect reviews from Trustpilot Next.js payload.
	 *
	 * @param array $node Payload node.
	 * @return array
	 */
	private function collect_next_data_reviews( array $node ) {
		$stack   = array( $node );
		$reviews = array();

		while ( $stack ) {
			$current = array_pop( $stack );

			if ( ! is_array( $current ) ) {
				continue;
			}

			if ( isset( $current['text'], $current['rating'] ) || isset( $current['content'], $current['rating'] ) ) {
				$review = $this->normalize_scraped_review(
					array(
						'title'   => isset( $current['title'] ) ? $current['title'] : '',
						'body'    => isset( $current['text'] ) ? $current['text'] : $current['content'],
						'author'  => isset( $current['consumer']['displayName'] ) ? $current['consumer']['displayName'] : '',
						'rating'  => $current['rating'],
						'date'    => isset( $current['dates']['publishedDate'] ) ? $current['dates']['publishedDate'] : '',
						'country' => isset( $current['consumer']['countryCode'] ) ? $current['consumer']['countryCode'] : '',
					)
				);

				if ( $review ) {
					$reviews[] = $review;
				}
			}

			foreach ( $current as $value ) {
				if ( is_array( $value ) ) {
					$stack[] = $value;
				}
			}
		}

		return $reviews;
	}

	/**
	 * Collect reviews from schema.org JSON-LD.
	 *
	 * @param array $node Schema node.
	 * @return array
	 */
	private function collect_schema_reviews( array $node ) {
		$reviews = array();

		if ( isset( $node['@graph'] ) && is_array( $node['@graph'] ) ) {
			foreach ( $node['@graph'] as $graph_node ) {
				if ( is_array( $graph_node ) ) {
					$reviews = array_merge( $reviews, $this->collect_schema_reviews( $graph_node ) );
				}
			}
		}

		if ( isset( $node['review'] ) && is_array( $node['review'] ) ) {
			foreach ( $node['review'] as $review_node ) {
				if ( is_array( $review_node ) ) {
					$reviews = array_merge( $reviews, $this->collect_schema_reviews( $review_node ) );
				}
			}
		}

		$type = isset( $node['@type'] ) ? $node['@type'] : '';
		$type = is_array( $type ) ? implode( ' ', array_map( 'strval', $type ) ) : (string) $type;

		if ( false !== stripos( $type, 'Review' ) ) {
			$review = $this->normalize_scraped_review(
				array(
					'title'  => isset( $node['name'] ) ? $node['name'] : '',
					'body'   => isset( $node['reviewBody'] ) ? $node['reviewBody'] : ( isset( $node['description'] ) ? $node['description'] : '' ),
					'author' => isset( $node['author']['name'] ) ? $node['author']['name'] : '',
					'rating' => isset( $node['reviewRating']['ratingValue'] ) ? $node['reviewRating']['ratingValue'] : 5,
					'date'   => isset( $node['datePublished'] ) ? $node['datePublished'] : '',
				)
			);

			if ( $review ) {
				$reviews[] = $review;
			}
		}

		return $reviews;
	}

	/**
	 * Normalize a scraped review.
	 *
	 * @param array $review Raw scraped review.
	 * @return array|null
	 */
	private function normalize_scraped_review( array $review ) {
		$body = isset( $review['body'] ) ? trim( wp_strip_all_tags( (string) $review['body'] ) ) : '';

		if ( '' === $body ) {
			return null;
		}

		$date = isset( $review['date'] ) ? sanitize_text_field( $review['date'] ) : '';
		if ( $date && strtotime( $date ) ) {
			$date = gmdate( 'Y-m-d', strtotime( $date ) );
		}

		return array(
			'title'      => isset( $review['title'] ) ? sanitize_text_field( $review['title'] ) : '',
			'body'       => sanitize_textarea_field( $body ),
			'author'     => isset( $review['author'] ) ? sanitize_text_field( $review['author'] ) : '',
			'rating'     => isset( $review['rating'] ) ? min( 5, max( 1, (float) $review['rating'] ) ) : 5,
			'date'       => $date,
			'source_url' => '',
			'country'    => isset( $review['country'] ) ? sanitize_text_field( $review['country'] ) : '',
			'verified'   => true,
			'featured'   => false,
		);
	}
}
