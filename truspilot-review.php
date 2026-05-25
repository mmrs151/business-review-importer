<?php
/**
 * Plugin Name: Truspilot Review Blocks
 * Description: Display responsive Trustpilot reviews as a carousel, grid, or full-page review wall with a shortcode or block.
 * Version: 2026.05.25
 * Author: Truspilot Review Blocks
 * Text Domain: truspilot-review
 * Requires at least: 6.0
 * Requires PHP: 7.4
 *
 * @package TruspilotReview
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'TRUSPILOT_REVIEW_VERSION', '2026.05.25' );
define( 'TRUSPILOT_REVIEW_FILE', __FILE__ );
define( 'TRUSPILOT_REVIEW_DIR', plugin_dir_path( __FILE__ ) );
define( 'TRUSPILOT_REVIEW_URL', plugin_dir_url( __FILE__ ) );

require_once TRUSPILOT_REVIEW_DIR . 'includes/class-truspilot-review-parser.php';
require_once TRUSPILOT_REVIEW_DIR . 'includes/class-truspilot-review-scraper.php';
require_once TRUSPILOT_REVIEW_DIR . 'includes/class-truspilot-review-importer.php';
require_once TRUSPILOT_REVIEW_DIR . 'includes/class-truspilot-review-renderer.php';
require_once TRUSPILOT_REVIEW_DIR . 'includes/class-truspilot-review-admin.php';
require_once TRUSPILOT_REVIEW_DIR . 'includes/class-truspilot-review-plugin.php';

add_action( 'plugins_loaded', array( 'Truspilot_Review_Plugin', 'instance' ) );
