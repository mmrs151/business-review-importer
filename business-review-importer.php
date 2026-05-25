<?php
/**
 * Plugin Name: Business Review Importer
 * Description: Import and display customer reviews from Trustpilot, Google, Feefo, Reviews.io, and more. Carousel, grid, list, and wall layouts.
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

		return $bri_fs;
	}

	bri_fs();
	do_action( 'bri_fs_loaded' );
}

require_once BRI_DIR . 'includes/class-bri-parser.php';
require_once BRI_DIR . 'includes/class-bri-scraper.php';
require_once BRI_DIR . 'includes/class-bri-importer.php';
require_once BRI_DIR . 'includes/class-bri-renderer.php';
require_once BRI_DIR . 'includes/class-bri-admin.php';
require_once BRI_DIR . 'includes/class-bri-plugin.php';

add_action( 'plugins_loaded', array( 'BRI_Plugin', 'instance' ) );
