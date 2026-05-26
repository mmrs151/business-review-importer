<?php
/**
 * Plugin Name: Business Review Importer
 * Description: Import and display Trustpilot customer reviews in carousel, grid, list, and wall layouts.
 * Version: 2026.05.25
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Author: PsyntaxLabs
 * Author URI: https://psyntaxlabs.com/
 * Text Domain: business-review-importer
 * Requires at least: 6.0
 * Requires PHP: 7.4
 *
 * @package BusinessReviewImporter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BRI_VERSION', '2026.05.25' );
define( 'BRI_FILE', __FILE__ );
define( 'BRI_DIR', plugin_dir_path( __FILE__ ) );
define( 'BRI_URL', plugin_dir_url( __FILE__ ) );

/**
 * Purge stale Freemius cached data on activation.
 */
function bri_activation_cleanup() {
	bri_purge_freemius_cache();
}
register_activation_hook( __FILE__, 'bri_activation_cleanup' );

/**
 * Purge stale Freemius cache on version change (covers updates).
 */
function bri_check_version() {
	$stored = get_option( 'bri_version', '' );
	if ( $stored !== BRI_VERSION ) {
		bri_purge_freemius_cache();
		update_option( 'bri_version', BRI_VERSION );
	}
}
add_action( 'admin_init', 'bri_check_version' );

/**
 * Purge stale Freemius cached data.
 */
function bri_purge_freemius_cache() {
	global $wpdb;
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
			$wpdb->esc_like( 'fs_' ) . '%',
			$wpdb->esc_like( '_transient_fs_' ) . '%'
		)
	);
}

define( 'WP_FS__DEV_MODE', true );
define( 'WP_FS__SKIP_EMAIL_ACTIVATION', true );
define( 'WP_FS__business-review-importer_SECRET_KEY', 'sk_Xs(C;+k#3{J#R{2sN&p(JPta7*O5G' );

if ( ! function_exists( 'bri_fs' ) ) {
	/**
	 * Freemius SDK helper.
	 *
	 * @return Freemius
	 */
	function bri_fs() {
		global $bri_fs;

		if ( ! isset( $bri_fs ) ) {
			require_once BRI_DIR . 'vendor/freemius/wordpress-sdk/start.php';

			$bri_fs = fs_dynamic_init(
				array(
					'id'                  => '30507',
					'slug'                => 'business-review-importer',
					'premium_slug'        => 'business-review-importer',
					'type'                => 'plugin',
					'public_key'          => 'pk_77f99941e85b3400e46d84503a0ed',
					'is_premium'          => true,
					'has_premium_version' => true,
					'has_addons'          => false,
					'has_paid_plans'      => true,
					'is_org_compliant'    => false,
					'trial'               => array(
						'days'               => 30,
						'is_require_payment' => false,
					),
					'menu'                => array(
						'slug'    => 'business-review-importer',
						'support' => false,
					),
					'after_uninstall'     => true,
				)
			);
		}

		return $bri_fs;
	}

	add_action( 'plugins_loaded', 'bri_fs_init' );
}

/**
 * Initialize Freemius SDK on plugins_loaded.
 */
function bri_fs_init() {
	bri_fs();
	bri_fs()->add_action( 'after_uninstall', 'bri_fs_uninstall_cleanup' );
	do_action( 'bri_fs_loaded' );
}

/**
 * Freemius after-uninstall cleanup.
 */
function bri_fs_uninstall_cleanup() {
	delete_option( 'bri_settings' );
	delete_option( 'bri_cache_bust' );

	global $wpdb;

	$prefix = $wpdb->esc_like( '_transient_bri_reviews_' ) . '%';
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
			$prefix
		)
	);

	$prefix_timeout = $wpdb->esc_like( '_transient_timeout_bri_reviews_' ) . '%';
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
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
}

require_once BRI_DIR . 'includes/class-bri-parser.php';
require_once BRI_DIR . 'includes/class-bri-scraper.php';
require_once BRI_DIR . 'includes/class-bri-importer.php';
require_once BRI_DIR . 'includes/class-bri-renderer.php';
require_once BRI_DIR . 'includes/class-bri-admin.php';
require_once BRI_DIR . 'includes/class-bri-plugin.php';

add_action( 'plugins_loaded', array( 'BRI_Plugin', 'instance' ) );
