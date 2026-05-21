<?php
/**
 * Main plugin class.
 *
 * @package TruspilotReview
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers local reviews, settings, shortcode, block, and assets.
 */
final class Truspilot_Review_Plugin {
	const OPTION_NAME   = 'truspilot_review_settings';
	const POST_TYPE     = 'truspilot_review';
	const MAX_REVIEWS   = 48;
	const DEFAULT_COUNT = 3;

	/**
	 * HTML parser instance.
	 *
	 * @var Truspilot_Review_Parser
	 */
	private $parser;

	/**
	 * Scraper instance.
	 *
	 * @var Truspilot_Review_Scraper
	 */
	private $scraper;

	/**
	 * Importer instance.
	 *
	 * @var Truspilot_Review_Importer
	 */
	private $importer;

	/**
	 * Renderer instance.
	 *
	 * @var Truspilot_Review_Renderer
	 */
	private $renderer;

	/**
	 * Admin instance.
	 *
	 * @var Truspilot_Review_Admin
	 */
	private $admin;

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Get singleton instance.
	 *
	 * @return self
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Wire WordPress hooks.
	 */
	private function __construct() {
		$this->parser   = new Truspilot_Review_Parser();
		$this->scraper  = new Truspilot_Review_Scraper( $this->parser );
		$this->importer = new Truspilot_Review_Importer( $this->parser );
		$this->renderer = new Truspilot_Review_Renderer( $this );
		$this->admin    = new Truspilot_Review_Admin( $this, $this->importer, $this->scraper );

		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'init', array( $this, 'register_assets' ) );
		add_action( 'init', array( $this, 'register_shortcodes' ) );
		add_action( 'init', array( $this, 'register_block' ) );

		add_action( 'admin_init', array( $this->admin, 'register_settings' ) );
		add_action( 'admin_menu', array( $this->admin, 'register_admin_page' ) );
		add_action( 'add_meta_boxes', array( $this->admin, 'add_review_meta_boxes' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( $this->admin, 'save_review_meta' ), 10, 2 );
		add_action( 'admin_post_truspilot_import_reviews', array( $this->admin, 'handle_import_reviews' ) );
		add_action( 'admin_post_truspilot_scrape_reviews', array( $this->admin, 'handle_scrape_reviews' ) );
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( $this->admin, 'review_columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( $this->admin, 'render_review_column' ), 10, 2 );
		add_action( 'save_post_' . self::POST_TYPE, array( $this->renderer, 'flush_review_cache' ) );
		add_action( 'trashed_post', array( $this->renderer, 'flush_review_cache' ) );
		add_action( 'deleted_post', array( $this->renderer, 'flush_review_cache' ) );
	}

	/**
	 * Register local review post type.
	 */
	public function register_post_type() {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'          => array(
					'name'          => __( 'Truspilot Reviews', 'truspilot-review' ),
					'singular_name' => __( 'Truspilot Review', 'truspilot-review' ),
					'add_new_item'  => __( 'Add Review', 'truspilot-review' ),
					'edit_item'     => __( 'Edit Review', 'truspilot-review' ),
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => true,
				'menu_icon'       => 'dashicons-star-filled',
				'supports'        => array( 'title', 'editor', 'page-attributes' ),
				'capability_type' => 'post',
				'map_meta_cap'    => true,
				'has_archive'     => false,
				'rewrite'         => false,
				'show_in_rest'    => false,
			)
		);
	}

	/**
	 * Register front-end and editor assets.
	 */
	public function register_assets() {
		wp_register_style(
			'truspilot-review-frontend',
			TRUSPILOT_REVIEW_URL . 'assets/frontend.css',
			array(),
			TRUSPILOT_REVIEW_VERSION
		);

		wp_register_script(
			'truspilot-review-frontend',
			TRUSPILOT_REVIEW_URL . 'assets/frontend.js',
			array(),
			TRUSPILOT_REVIEW_VERSION,
			true
		);

		$block_asset_file = TRUSPILOT_REVIEW_DIR . 'build/index.asset.php';
		if ( file_exists( $block_asset_file ) ) {
			$block_asset = require $block_asset_file;
			wp_register_script(
				'truspilot-review-block',
				TRUSPILOT_REVIEW_URL . 'build/index.js',
				$block_asset['dependencies'],
				$block_asset['version'],
				true
			);
		} else {
			wp_register_script(
				'truspilot-review-block',
				TRUSPILOT_REVIEW_URL . 'block/index.js',
				array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-server-side-render' ),
				TRUSPILOT_REVIEW_VERSION,
				true
			);
		}
	}

	/**
	 * Register shortcode aliases.
	 */
	public function register_shortcodes() {
		add_shortcode( 'truspilot_reviews', array( $this->renderer, 'shortcode' ) );
		add_shortcode( 'trustpilot_reviews', array( $this->renderer, 'shortcode' ) );
	}

	/**
	 * Register the dynamic Gutenberg block.
	 */
	public function register_block() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		register_block_type(
			TRUSPILOT_REVIEW_DIR . 'block',
			array(
				'render_callback' => array( $this->renderer, 'render_block' ),
			)
		);
	}

	/**
	 * Get sanitized settings.
	 *
	 * @return array
	 */
	public function get_settings() {
		return wp_parse_args( get_option( self::OPTION_NAME, array() ), $this->default_settings() );
	}

	/**
	 * Get review meta.
	 *
	 * @param int $post_id Post ID.
	 * @return array
	 */
	public function get_review_meta( $post_id ) {
		return array(
			'author'        => sanitize_text_field( get_post_meta( $post_id, '_truspilot_author', true ) ),
			'rating'        => min( 5, max( 1, (float) get_post_meta( $post_id, '_truspilot_rating', true ) ) ),
			'date'          => sanitize_text_field( get_post_meta( $post_id, '_truspilot_date', true ) ),
			'source_url'    => esc_url_raw( get_post_meta( $post_id, '_truspilot_source_url', true ) ),
			'country'       => sanitize_text_field( get_post_meta( $post_id, '_truspilot_country', true ) ),
			'short_excerpt' => sanitize_textarea_field( get_post_meta( $post_id, '_truspilot_short_excerpt', true ) ),
			'featured'      => (bool) get_post_meta( $post_id, '_truspilot_featured', true ),
			'verified'      => (bool) get_post_meta( $post_id, '_truspilot_verified', true ),
		);
	}

	/**
	 * Default settings.
	 *
	 * @return array
	 */
	private function default_settings() {
		return array(
			'business_name'   => '',
			'business_domain' => '',
			'public_url'      => '',
			'trust_score'     => 0,
			'star_rating'     => 0,
			'total_reviews'   => 0,
			'rating_label'    => '',
		);
	}
}
