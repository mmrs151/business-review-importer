<?php
/**
 * Importer class for parsing and inserting reviews from various sources.
 *
 * @package TruspilotReview
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles parsing of import text (HTML, JSON, plain text) and inserting reviews.
 */
final class Truspilot_Review_Importer {

	const POST_TYPE = 'truspilot_review';

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
	 * Parse pasted import text.
	 *
	 * @param string $raw Raw text.
	 * @return array
	 */
	public function parse_import_text( $raw ) {
		$raw = trim( (string) $raw );

		if ( '' === $raw ) {
			return array();
		}

		if ( $this->looks_like_html( $raw ) ) {
			return $this->parser->parse_public_reviews_html( $raw );
		}

		$json = json_decode( $raw, true );
		if ( is_array( $json ) ) {
			return $this->normalize_import_array( $json );
		}

		$blocks  = preg_split( "/\n\s*\n/", $raw );
		$reviews = array();

		foreach ( $blocks as $block ) {
			$review = $this->parse_import_block( $block );
			if ( $review ) {
				$reviews[] = $review;
			}
		}

		return $reviews;
	}

	/**
	 * Insert imported review.
	 *
	 * @param array $review Review data.
	 * @return int|false
	 */
	public function insert_review( array $review ) {
		$body = isset( $review['body'] ) ? trim( wp_strip_all_tags( (string) $review['body'] ) ) : '';
		if ( '' === $body ) {
			return false;
		}

		$hash = md5( strtolower( $body ) );
		$dupe = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => '_truspilot_import_hash',
				'meta_value'     => $hash,
			)
		);

		if ( ! empty( $dupe ) ) {
			return false;
		}

		$post_id = wp_insert_post(
			array(
				'post_type'    => self::POST_TYPE,
				'post_status'  => 'publish',
				'post_title'   => isset( $review['title'] ) && $review['title'] ? sanitize_text_field( $review['title'] ) : wp_trim_words( $body, 8, '' ),
				'post_content' => sanitize_textarea_field( $body ),
			),
			true
		);

		if ( is_wp_error( $post_id ) ) {
			return false;
		}

		$meta = array(
			'_truspilot_author'        => isset( $review['author'] ) ? sanitize_text_field( $review['author'] ) : '',
			'_truspilot_rating'        => isset( $review['rating'] ) ? min( 5, max( 1, (float) $review['rating'] ) ) : 5,
			'_truspilot_date'          => isset( $review['date'] ) ? sanitize_text_field( $review['date'] ) : '',
			'_truspilot_source_url'    => isset( $review['source_url'] ) ? esc_url_raw( $review['source_url'] ) : '',
			'_truspilot_country'       => isset( $review['country'] ) ? sanitize_text_field( $review['country'] ) : '',
			'_truspilot_short_excerpt' => '',
			'_truspilot_featured'      => ! empty( $review['featured'] ) ? 1 : 0,
			'_truspilot_verified'      => ! empty( $review['verified'] ) ? 1 : 0,
			'_truspilot_import_hash'   => $hash,
		);

		foreach ( $meta as $key => $value ) {
			update_post_meta( $post_id, $key, $value );
		}

		return $post_id;
	}

	/**
	 * Trash existing reviews.
	 */
	public function trash_existing() {
		$posts = get_posts(
			array(
				'post_type'      => self::POST_TYPE,
				'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);

		foreach ( $posts as $post_id ) {
			wp_trash_post( $post_id );
		}
	}

	/**
	 * Detect full or partial HTML imports.
	 *
	 * @param string $raw Raw import text.
	 * @return bool
	 */
	private function looks_like_html( $raw ) {
		return false !== stripos( $raw, '<html' )
			|| false !== stripos( $raw, '<script' )
			|| false !== stripos( $raw, '__NEXT_DATA__' )
			|| false !== stripos( $raw, 'application/ld+json' );
	}

	/**
	 * Normalize JSON import array.
	 *
	 * @param array $items Items.
	 * @return array
	 */
	private function normalize_import_array( array $items ) {
		$reviews = array();

		foreach ( $items as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$body = isset( $item['body'] ) ? $item['body'] : ( isset( $item['text'] ) ? $item['text'] : '' );
			if ( '' === trim( (string) $body ) ) {
				continue;
			}

			$reviews[] = array(
				'title'      => isset( $item['title'] ) ? $item['title'] : '',
				'body'       => $body,
				'author'     => isset( $item['author'] ) ? $item['author'] : ( isset( $item['reviewer_name'] ) ? $item['reviewer_name'] : '' ),
				'rating'     => isset( $item['rating'] ) ? $item['rating'] : 5,
				'date'       => isset( $item['date'] ) ? $item['date'] : '',
				'source_url' => isset( $item['source_url'] ) ? $item['source_url'] : '',
				'country'    => isset( $item['country'] ) ? $item['country'] : '',
				'verified'   => ! empty( $item['verified'] ),
				'featured'   => ! empty( $item['featured'] ),
			);
		}

		return $reviews;
	}

	/**
	 * Parse one plain-text review block.
	 *
	 * @param string $block Block.
	 * @return array|null
	 */
	private function parse_import_block( $block ) {
		$lines = array_values(
			array_filter(
				array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) $block ) ),
				function ( $line ) {
					return '' !== $line;
				}
			)
		);

		if ( count( $lines ) < 2 ) {
			return null;
		}

		$rating = 5;
		foreach ( $lines as $index => $line ) {
			if ( preg_match( '/([1-5](?:\.\d)?)\s*(?:out of 5|stars?|\/5)/i', $line, $match ) ) {
				$rating = (float) $match[1];
				unset( $lines[ $index ] );
				break;
			}
		}
		$lines = array_values( $lines );

		$date = '';
		foreach ( array_reverse( $lines, true ) as $index => $line ) {
			$timestamp = strtotime( $line );
			if ( $timestamp && $timestamp > strtotime( '2000-01-01' ) && $timestamp < strtotime( '+1 year' ) ) {
				$date = gmdate( 'Y-m-d', $timestamp );
				unset( $lines[ $index ] );
				break;
			}
		}
		$lines = array_values( $lines );

		$title  = array_shift( $lines );
		$author = count( $lines ) > 1 ? array_pop( $lines ) : '';
		$body   = implode( ' ', $lines );

		if ( '' === trim( $body ) ) {
			$body  = $title;
			$title = '';
		}

		return array(
			'title'      => $title,
			'body'       => $body,
			'author'     => $author,
			'rating'     => $rating,
			'date'       => $date,
			'source_url' => '',
			'country'    => '',
			'verified'   => false,
			'featured'   => false,
		);
	}
}
