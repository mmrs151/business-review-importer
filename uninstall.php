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
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
		$prefix
	)
);

$prefix_timeout = $wpdb->esc_like( '_transient_timeout_bri_reviews_' ) . '%';
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
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
