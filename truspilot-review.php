<?php
/**
 * Plugin Name: Truspilot Review Blocks
 * Description: Display responsive Trustpilot reviews as a carousel, grid, list, or review wall using shortcode or block.
 * Version: 2026.05.25
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Author: PsyntaxLabs
 * Author URI: https://psyntaxlabs.com/
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

if ( ! function_exists( 'trp_fs' ) ) {
	/**
	 * Freemius SDK helper.
	 *
	 * @return Freemius
	 */
	function trp_fs() {
		global $trp_fs;

		if ( ! isset( $trp_fs ) ) {
			require_once TRUSPILOT_REVIEW_DIR . 'vendor/freemius/wordpress-sdk/start.php';

			$trp_fs = fs_dynamic_init(
				array(
					'id'             => '30507',
					'slug'           => 'free-trust-pilot-review',
					'type'           => 'plugin',
					'public_key'     => 'pk_77f99941e85b3400e46d84503a0ed',
					'is_premium'     => false,
					'has_addons'     => false,
					'has_paid_plans' => true,
					'is_org_compliant' => true,
					'trial'          => array(
						'days'               => 30,
						'is_require_payment' => false,
					),
					'menu'           => array(
						'account' => false,
						'support' => false,
					),
				)
			);
		}

		return $trp_fs;
	}

	trp_fs();
	do_action( 'trp_fs_loaded' );
}

add_action( 'plugins_loaded', array( 'Truspilot_Review_Plugin', 'instance' ) );
