<?php
/**
 * Uninstall handler.
 *
 * @package BusinessReviewImporter
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'bri_settings' );
delete_option( 'bri_cache_bust' );

global $wpdb;

$prefix = $wpdb->esc_like( '_transient_bri_reviews_' ) . '%';
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
		$prefix
	)
);

$prefix_timeout = $wpdb->esc_like( '_transient_timeout_bri_reviews_' ) . '%';
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
		$prefix_timeout
	)
);

$reviews = get_posts(
	array(
		'post_type'      => 'bri_review',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	)
);

foreach ( $reviews as $review_id ) {
	wp_delete_post( $review_id, true );
}

delete_option( 'truspilot_review_settings' );
delete_option( 'truspilot_review_cache_bust' );

global $wpdb;

$prefix = $wpdb->esc_like( '_transient_truspilot_reviews_' ) . '%';
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		$prefix,
		$wpdb->esc_like( '_transient_timeout_truspilot_reviews_' ) . '%'
	)
);

$review_ids = get_posts(
	array(
		'post_type'      => 'truspilot_review',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	)
);

foreach ( $review_ids as $review_id ) {
	wp_delete_post( $review_id, true );
}
