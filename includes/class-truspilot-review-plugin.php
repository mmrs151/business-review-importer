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
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'init', array( $this, 'register_assets' ) );
		add_action( 'init', array( $this, 'register_shortcodes' ) );
		add_action( 'init', array( $this, 'register_block' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_menu', array( $this, 'register_admin_page' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_review_meta_boxes' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( $this, 'save_review_meta' ), 10, 2 );
		add_action( 'admin_post_truspilot_import_reviews', array( $this, 'handle_import_reviews' ) );
		add_action( 'admin_post_truspilot_scrape_reviews', array( $this, 'handle_scrape_reviews' ) );
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( $this, 'review_columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( $this, 'render_review_column' ), 10, 2 );
		add_action( 'save_post_' . self::POST_TYPE, array( $this, 'flush_review_cache' ) );
		add_action( 'trashed_post', array( $this, 'flush_review_cache' ) );
		add_action( 'deleted_post', array( $this, 'flush_review_cache' ) );
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

		wp_register_script(
			'truspilot-review-block',
			TRUSPILOT_REVIEW_URL . 'block/index.js',
			array( 'wp-blocks', 'wp-element', 'wp-components', 'wp-block-editor', 'wp-server-side-render' ),
			TRUSPILOT_REVIEW_VERSION,
			true
		);
	}

	/**
	 * Register shortcode aliases.
	 */
	public function register_shortcodes() {
		add_shortcode( 'truspilot_reviews', array( $this, 'shortcode' ) );
		add_shortcode( 'trustpilot_reviews', array( $this, 'shortcode' ) );
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
				'render_callback' => array( $this, 'render_block' ),
			)
		);
	}

	/**
	 * Register plugin settings.
	 */
	public function register_settings() {
		register_setting(
			'truspilot_review_settings',
			self::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => $this->default_settings(),
			)
		);

		add_settings_section(
			'truspilot_review_profile',
			__( 'Business Profile', 'truspilot-review' ),
			function () {
				echo '<p>' . esc_html__( 'Save the public profile summary once, then display locally managed reviews anywhere with a shortcode or block.', 'truspilot-review' ) . '</p>';
			},
			'truspilot-review'
		);

		$fields = array(
			'business_name'   => __( 'Business name', 'truspilot-review' ),
			'business_domain' => __( 'Business domain', 'truspilot-review' ),
			'public_url'      => __( 'Trustpilot profile URL', 'truspilot-review' ),
			'trust_score'     => __( 'TrustScore', 'truspilot-review' ),
			'star_rating'     => __( 'Star rating', 'truspilot-review' ),
			'total_reviews'   => __( 'Total reviews', 'truspilot-review' ),
			'rating_label'    => __( 'Rating label', 'truspilot-review' ),
		);

		foreach ( $fields as $field => $label ) {
			add_settings_field(
				$field,
				$label,
				array( $this, 'render_settings_field' ),
				'truspilot-review',
				'truspilot_review_profile',
				array( 'field' => $field )
			);
		}
	}

	/**
	 * Register admin settings page.
	 */
	public function register_admin_page() {
		add_submenu_page(
			'edit.php?post_type=' . self::POST_TYPE,
			__( 'Review Settings', 'truspilot-review' ),
			__( 'Settings & Import', 'truspilot-review' ),
			'manage_options',
			'truspilot-review',
			array( $this, 'render_admin_page' )
		);
	}

	/**
	 * Render admin page.
	 */
	public function render_admin_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to manage these settings.', 'truspilot-review' ) );
		}

		$imported = isset( $_GET['truspilot_imported'] ) ? absint( $_GET['truspilot_imported'] ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$scraped  = isset( $_GET['truspilot_scraped'] ) ? absint( $_GET['truspilot_scraped'] ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$error    = isset( $_GET['truspilot_error'] ) ? sanitize_key( wp_unslash( $_GET['truspilot_error'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$settings = $this->get_settings();
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Truspilot Review Blocks', 'truspilot-review' ); ?></h1>
			<?php if ( null !== $imported ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php echo esc_html( sprintf( _n( '%d review imported.', '%d reviews imported.', $imported, 'truspilot-review' ), $imported ) ); ?></p>
				</div>
			<?php endif; ?>
			<?php if ( null !== $scraped ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php echo esc_html( sprintf( _n( '%d review scraped and saved locally.', '%d reviews scraped and saved locally.', $scraped, 'truspilot-review' ), $scraped ) ); ?></p>
				</div>
			<?php endif; ?>
			<?php if ( $error ) : ?>
				<div class="notice notice-error is-dismissible">
					<p><?php echo esc_html( $this->get_admin_error_message( $error ) ); ?></p>
				</div>
			<?php endif; ?>

			<form action="options.php" method="post">
				<?php
				settings_fields( 'truspilot_review_settings' );
				do_settings_sections( 'truspilot-review' );
				submit_button();
				?>
			</form>

			<hr />
			<h2><?php echo esc_html__( 'Automatic Public Scrape', 'truspilot-review' ); ?></h2>
			<p><?php echo esc_html__( 'Optionally scrape reviews from the saved public Trustpilot profile URL and save them locally. This follows the public-page pagination approach used by open-source Trustpilot scrapers, but Trustpilot may block server requests with browser verification.', 'truspilot-review' ); ?></p>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
				<input type="hidden" name="action" value="truspilot_scrape_reviews" />
				<?php wp_nonce_field( 'truspilot_scrape_reviews', 'truspilot_scrape_nonce' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th><label for="truspilot_scrape_url"><?php echo esc_html__( 'Profile URL', 'truspilot-review' ); ?></label></th>
						<td>
							<input type="url" class="regular-text" id="truspilot_scrape_url" name="truspilot_scrape_url" value="<?php echo esc_attr( $settings['public_url'] ); ?>" placeholder="https://uk.trustpilot.com/review/example.com" required />
						</td>
					</tr>
					<tr>
						<th><label for="truspilot_scrape_pages"><?php echo esc_html__( 'Maximum pages', 'truspilot-review' ); ?></label></th>
						<td>
							<input type="number" id="truspilot_scrape_pages" name="truspilot_scrape_pages" min="1" max="50" value="5" />
							<p class="description"><?php echo esc_html__( 'Each page is fetched with a short delay. Keep this modest to avoid aggressive requests.', 'truspilot-review' ); ?></p>
						</td>
					</tr>
				</table>
				<p>
					<label>
						<input type="checkbox" name="truspilot_clear_existing" value="1" />
						<?php echo esc_html__( 'Move existing local reviews to trash before scraping', 'truspilot-review' ); ?>
					</label>
				</p>
				<?php submit_button( __( 'Scrape & Save Reviews', 'truspilot-review' ), 'secondary' ); ?>
			</form>

			<hr />
			<h2><?php echo esc_html__( 'Easy Browser Import', 'truspilot-review' ); ?></h2>
			<p><?php echo esc_html__( 'Open the Trustpilot profile in your browser, view the page source, copy all, and paste it here. The plugin will extract reviews from the public page data and save them locally.', 'truspilot-review' ); ?></p>
			<ol>
				<li><?php echo esc_html__( 'Open the Trustpilot review profile while logged into your normal browser session.', 'truspilot-review' ); ?></li>
				<li><?php echo esc_html__( 'Use View Page Source, then select all and copy.', 'truspilot-review' ); ?></li>
				<li><?php echo esc_html__( 'Paste the source below and import. Plain review text and JSON arrays still work too.', 'truspilot-review' ); ?></li>
			</ol>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
				<input type="hidden" name="action" value="truspilot_import_reviews" />
				<?php wp_nonce_field( 'truspilot_import_reviews', 'truspilot_import_nonce' ); ?>
				<textarea name="truspilot_import_text" rows="14" class="large-text code" placeholder="<?php echo esc_attr__( 'Paste full Trustpilot page source, a JSON review array, or copied review text here.', 'truspilot-review' ); ?>"></textarea>
				<p>
					<label>
						<input type="checkbox" name="truspilot_clear_existing" value="1" />
						<?php echo esc_html__( 'Move existing local reviews to trash before importing', 'truspilot-review' ); ?>
					</label>
				</p>
				<?php submit_button( __( 'Extract & Import Reviews', 'truspilot-review' ) ); ?>
			</form>

			<h2><?php echo esc_html__( 'Shortcodes', 'truspilot-review' ); ?></h2>
			<p><code>[truspilot_reviews count="3" layout="carousel" autoplay="true"]</code></p>
			<p><code>[truspilot_reviews count="12" layout="grid" min_rating="4" featured_first="true"]</code></p>
			<p><code>[truspilot_reviews count="24" layout="wall" full_page="true"]</code></p>
		</div>
		<?php
	}

	/**
	 * Render one settings field.
	 *
	 * @param array $args Field args.
	 */
	public function render_settings_field( $args ) {
		$field    = isset( $args['field'] ) ? sanitize_key( $args['field'] ) : '';
		$settings = $this->get_settings();
		$value    = isset( $settings[ $field ] ) ? $settings[ $field ] : '';
		$type     = in_array( $field, array( 'trust_score', 'star_rating', 'total_reviews' ), true ) ? 'number' : 'text';
		$step     = in_array( $field, array( 'trust_score', 'star_rating' ), true ) ? '0.1' : '1';
		?>
		<input
			type="<?php echo esc_attr( $type ); ?>"
			name="<?php echo esc_attr( self::OPTION_NAME ); ?>[<?php echo esc_attr( $field ); ?>]"
			value="<?php echo esc_attr( (string) $value ); ?>"
			class="regular-text"
			<?php echo 'number' === $type ? 'step="' . esc_attr( $step ) . '"' : ''; ?>
		/>
		<?php if ( 'public_url' === $field ) : ?>
			<p class="description"><?php echo esc_html__( 'Example: https://uk.trustpilot.com/review/example.com', 'truspilot-review' ); ?></p>
		<?php endif; ?>
		<?php
	}

	/**
	 * Sanitize settings.
	 *
	 * @param array $input Raw settings.
	 * @return array
	 */
	public function sanitize_settings( $input ) {
		$input = is_array( $input ) ? $input : array();
		$url   = isset( $input['public_url'] ) ? esc_url_raw( wp_unslash( $input['public_url'] ) ) : '';

		if ( $url && ! $this->is_allowed_trustpilot_url( $url ) ) {
			add_settings_error( self::OPTION_NAME, 'truspilot_review_invalid_url', __( 'Please enter a valid public Trustpilot review URL.', 'truspilot-review' ), 'error' );
			$url = $this->get_settings()['public_url'];
		}

		return array(
			'business_name'   => isset( $input['business_name'] ) ? sanitize_text_field( wp_unslash( $input['business_name'] ) ) : '',
			'business_domain' => isset( $input['business_domain'] ) ? sanitize_text_field( wp_unslash( $input['business_domain'] ) ) : '',
			'public_url'      => $url,
			'trust_score'     => isset( $input['trust_score'] ) ? min( 5, max( 0, (float) $input['trust_score'] ) ) : 0,
			'star_rating'     => isset( $input['star_rating'] ) ? min( 5, max( 0, (float) $input['star_rating'] ) ) : 0,
			'total_reviews'   => isset( $input['total_reviews'] ) ? absint( $input['total_reviews'] ) : 0,
			'rating_label'    => isset( $input['rating_label'] ) ? sanitize_text_field( wp_unslash( $input['rating_label'] ) ) : '',
		);
	}

	/**
	 * Add review meta boxes.
	 */
	public function add_review_meta_boxes() {
		add_meta_box(
			'truspilot_review_details',
			__( 'Review Details', 'truspilot-review' ),
			array( $this, 'render_review_meta_box' ),
			self::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Render review meta box.
	 *
	 * @param WP_Post $post Post.
	 */
	public function render_review_meta_box( $post ) {
		wp_nonce_field( 'truspilot_save_review_meta', 'truspilot_review_meta_nonce' );
		$fields = $this->get_review_meta( $post->ID );
		?>
		<table class="form-table" role="presentation">
			<tr>
				<th><label for="truspilot_reviewer_name"><?php echo esc_html__( 'Reviewer name', 'truspilot-review' ); ?></label></th>
				<td><input class="regular-text" id="truspilot_reviewer_name" name="truspilot_reviewer_name" value="<?php echo esc_attr( $fields['author'] ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="truspilot_rating"><?php echo esc_html__( 'Rating', 'truspilot-review' ); ?></label></th>
				<td><input type="number" min="1" max="5" step="0.1" id="truspilot_rating" name="truspilot_rating" value="<?php echo esc_attr( (string) $fields['rating'] ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="truspilot_review_date"><?php echo esc_html__( 'Review date', 'truspilot-review' ); ?></label></th>
				<td><input type="date" id="truspilot_review_date" name="truspilot_review_date" value="<?php echo esc_attr( $fields['date'] ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="truspilot_source_url"><?php echo esc_html__( 'Source URL', 'truspilot-review' ); ?></label></th>
				<td><input type="url" class="regular-text" id="truspilot_source_url" name="truspilot_source_url" value="<?php echo esc_url( $fields['source_url'] ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="truspilot_country"><?php echo esc_html__( 'Country', 'truspilot-review' ); ?></label></th>
				<td><input class="regular-text" id="truspilot_country" name="truspilot_country" value="<?php echo esc_attr( $fields['country'] ); ?>" /></td>
			</tr>
			<tr>
				<th><label for="truspilot_short_excerpt"><?php echo esc_html__( 'Short excerpt', 'truspilot-review' ); ?></label></th>
				<td><textarea class="large-text" rows="2" id="truspilot_short_excerpt" name="truspilot_short_excerpt"><?php echo esc_textarea( $fields['short_excerpt'] ); ?></textarea></td>
			</tr>
			<tr>
				<th><?php echo esc_html__( 'Display flags', 'truspilot-review' ); ?></th>
				<td>
					<label><input type="checkbox" name="truspilot_featured" value="1" <?php checked( $fields['featured'] ); ?> /> <?php echo esc_html__( 'Featured', 'truspilot-review' ); ?></label>
					&nbsp;&nbsp;
					<label><input type="checkbox" name="truspilot_verified" value="1" <?php checked( $fields['verified'] ); ?> /> <?php echo esc_html__( 'Verified', 'truspilot-review' ); ?></label>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Save review meta.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post Post.
	 */
	public function save_review_meta( $post_id, $post ) {
		if ( ! isset( $_POST['truspilot_review_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['truspilot_review_meta_nonce'] ) ), 'truspilot_save_review_meta' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) || self::POST_TYPE !== $post->post_type ) {
			return;
		}

		$meta = array(
			'_truspilot_author'        => isset( $_POST['truspilot_reviewer_name'] ) ? sanitize_text_field( wp_unslash( $_POST['truspilot_reviewer_name'] ) ) : '',
			'_truspilot_rating'        => isset( $_POST['truspilot_rating'] ) ? min( 5, max( 1, (float) $_POST['truspilot_rating'] ) ) : 5,
			'_truspilot_date'          => isset( $_POST['truspilot_review_date'] ) ? sanitize_text_field( wp_unslash( $_POST['truspilot_review_date'] ) ) : '',
			'_truspilot_source_url'    => isset( $_POST['truspilot_source_url'] ) ? esc_url_raw( wp_unslash( $_POST['truspilot_source_url'] ) ) : '',
			'_truspilot_country'       => isset( $_POST['truspilot_country'] ) ? sanitize_text_field( wp_unslash( $_POST['truspilot_country'] ) ) : '',
			'_truspilot_short_excerpt' => isset( $_POST['truspilot_short_excerpt'] ) ? sanitize_textarea_field( wp_unslash( $_POST['truspilot_short_excerpt'] ) ) : '',
			'_truspilot_featured'      => isset( $_POST['truspilot_featured'] ) ? 1 : 0,
			'_truspilot_verified'      => isset( $_POST['truspilot_verified'] ) ? 1 : 0,
		);

		foreach ( $meta as $key => $value ) {
			update_post_meta( $post_id, $key, $value );
		}
	}

	/**
	 * Handle pasted review import.
	 */
	public function handle_import_reviews() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to import reviews.', 'truspilot-review' ) );
		}

		if ( ! isset( $_POST['truspilot_import_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['truspilot_import_nonce'] ) ), 'truspilot_import_reviews' ) ) {
			wp_die( esc_html__( 'Import security check failed.', 'truspilot-review' ) );
		}

		if ( ! empty( $_POST['truspilot_clear_existing'] ) ) {
			$this->trash_existing_reviews();
		}

		$raw      = isset( $_POST['truspilot_import_text'] ) ? wp_unslash( $_POST['truspilot_import_text'] ) : '';
		$reviews  = $this->parse_import_text( $raw );
		$imported = 0;

		foreach ( $reviews as $review ) {
			if ( $this->insert_imported_review( $review ) ) {
				$imported++;
			}
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'post_type'           => self::POST_TYPE,
					'page'                => 'truspilot-review',
					'truspilot_imported'  => $imported,
				),
				admin_url( 'edit.php' )
			)
		);
		exit;
	}

	/**
	 * Handle public Trustpilot scrape import.
	 */
	public function handle_scrape_reviews() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to scrape reviews.', 'truspilot-review' ) );
		}

		if ( ! isset( $_POST['truspilot_scrape_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['truspilot_scrape_nonce'] ) ), 'truspilot_scrape_reviews' ) ) {
			wp_die( esc_html__( 'Scrape security check failed.', 'truspilot-review' ) );
		}

		$url       = isset( $_POST['truspilot_scrape_url'] ) ? esc_url_raw( wp_unslash( $_POST['truspilot_scrape_url'] ) ) : '';
		$max_pages = isset( $_POST['truspilot_scrape_pages'] ) ? min( 50, max( 1, absint( $_POST['truspilot_scrape_pages'] ) ) ) : 5;

		if ( ! $url || ! $this->is_allowed_trustpilot_url( $url ) ) {
			$this->redirect_to_settings( array( 'truspilot_error' => 'invalid_url' ) );
		}

		if ( ! empty( $_POST['truspilot_clear_existing'] ) ) {
			$this->trash_existing_reviews();
		}

		$result   = $this->scrape_public_reviews( $url, $max_pages );
		$imported = 0;

		foreach ( $result['reviews'] as $review ) {
			if ( $this->insert_imported_review( $review ) ) {
				$imported++;
			}
		}

		if ( 0 === $imported && ! empty( $result['error'] ) ) {
			$this->redirect_to_settings( array( 'truspilot_error' => $result['error'] ) );
		}

		$this->redirect_to_settings( array( 'truspilot_scraped' => $imported ) );
	}

	/**
	 * Scrape public Trustpilot pages and normalize reviews.
	 *
	 * @param string $base_url Trustpilot profile URL.
	 * @param int    $max_pages Maximum pages.
	 * @return array
	 */
	private function scrape_public_reviews( $base_url, $max_pages ) {
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

			$page_reviews = $this->parse_public_reviews_html( $body );

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

	/**
	 * Parse public Trustpilot HTML.
	 *
	 * @param string $html HTML.
	 * @return array
	 */
	private function parse_public_reviews_html( $html ) {
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

	/**
	 * Render shortcode.
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'count'          => self::DEFAULT_COUNT,
				'layout'         => 'carousel',
				'autoplay'       => 'true',
				'interval'       => 5500,
				'full_page'      => 'false',
				'title'          => __( 'Customer reviews', 'truspilot-review' ),
				'min_rating'     => 1,
				'featured_first' => 'true',
			),
			(array) $atts,
			'truspilot_reviews'
		);

		return $this->render_reviews( $atts );
	}

	/**
	 * Render dynamic block.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public function render_block( $attributes ) {
		$attributes = is_array( $attributes ) ? $attributes : array();

		return $this->render_reviews(
			array(
				'count'          => isset( $attributes['count'] ) ? $attributes['count'] : self::DEFAULT_COUNT,
				'layout'         => isset( $attributes['layout'] ) ? $attributes['layout'] : 'carousel',
				'autoplay'       => ! empty( $attributes['autoplay'] ) ? 'true' : 'false',
				'interval'       => isset( $attributes['interval'] ) ? $attributes['interval'] : 5500,
				'full_page'      => ! empty( $attributes['fullPage'] ) ? 'true' : 'false',
				'title'          => isset( $attributes['title'] ) ? $attributes['title'] : __( 'Customer reviews', 'truspilot-review' ),
				'min_rating'     => isset( $attributes['minRating'] ) ? $attributes['minRating'] : 1,
				'featured_first' => ! empty( $attributes['featuredFirst'] ) ? 'true' : 'false',
			)
		);
	}

	/**
	 * Render review HTML.
	 *
	 * @param array $raw_atts Raw render attributes.
	 * @return string
	 */
	private function render_reviews( $raw_atts ) {
		$count          = min( self::MAX_REVIEWS, max( 1, absint( $raw_atts['count'] ) ) );
		$layout         = $this->sanitize_choice( isset( $raw_atts['layout'] ) ? $raw_atts['layout'] : 'carousel', array( 'carousel', 'grid', 'wall' ), 'carousel' );
		$autoplay       = $this->to_bool( isset( $raw_atts['autoplay'] ) ? $raw_atts['autoplay'] : 'true' );
		$full_page      = $this->to_bool( isset( $raw_atts['full_page'] ) ? $raw_atts['full_page'] : 'false' );
		$featured_first = $this->to_bool( isset( $raw_atts['featured_first'] ) ? $raw_atts['featured_first'] : 'true' );
		$interval       = min( 20000, max( 2500, absint( $raw_atts['interval'] ) ) );
		$min_rating     = min( 5, max( 1, (float) $raw_atts['min_rating'] ) );
		$title          = isset( $raw_atts['title'] ) ? sanitize_text_field( wp_unslash( $raw_atts['title'] ) ) : '';
		$data           = $this->get_local_reviews( $count, $min_rating, $featured_first );
		$settings       = $this->get_settings();

		if ( empty( $data['reviews'] ) ) {
			return current_user_can( 'edit_posts' ) ? '<p class="truspilot-review-notice">' . esc_html__( 'Add or import local reviews to display this block.', 'truspilot-review' ) . '</p>' : '';
		}

		wp_enqueue_style( 'truspilot-review-frontend' );
		wp_enqueue_script( 'truspilot-review-frontend' );

		$classes = array(
			'truspilot-review',
			'truspilot-review--' . $layout,
			$full_page ? 'truspilot-review--full' : '',
		);

		ob_start();
		?>
		<section
			class="<?php echo esc_attr( trim( implode( ' ', array_filter( $classes ) ) ) ); ?>"
			data-truspilot-review
			data-layout="<?php echo esc_attr( $layout ); ?>"
			data-autoplay="<?php echo esc_attr( $autoplay ? 'true' : 'false' ); ?>"
			data-interval="<?php echo esc_attr( (string) $interval ); ?>"
		>
			<div class="truspilot-review__header">
				<div>
					<?php if ( $title ) : ?>
						<h2 class="truspilot-review__title"><?php echo esc_html( $title ); ?></h2>
					<?php endif; ?>
					<?php if ( ! empty( $settings['business_name'] ) ) : ?>
						<p class="truspilot-review__business"><?php echo esc_html( $settings['business_name'] ); ?></p>
					<?php endif; ?>
				</div>
				<?php if ( ! empty( $settings['public_url'] ) ) : ?>
					<a class="truspilot-review__source" href="<?php echo esc_url( $settings['public_url'] ); ?>" rel="nofollow noopener" target="_blank">
						<?php echo esc_html__( 'View on Trustpilot', 'truspilot-review' ); ?>
					</a>
				<?php endif; ?>
			</div>

			<?php if ( $this->has_profile_summary( $settings ) ) : ?>
				<div class="truspilot-review__summary">
					<div class="truspilot-review__score">
						<strong><?php echo esc_html( $settings['rating_label'] ? $settings['rating_label'] : __( 'Rated', 'truspilot-review' ) ); ?></strong>
						<span><?php echo esc_html( $settings['trust_score'] ? number_format_i18n( (float) $settings['trust_score'], 1 ) : number_format_i18n( (float) $settings['star_rating'], 1 ) ); ?> / 5</span>
					</div>
					<div class="truspilot-review__rating" aria-label="<?php echo esc_attr__( 'Business star rating', 'truspilot-review' ); ?>">
						<?php echo wp_kses_post( $this->render_stars( $settings['star_rating'] ? $settings['star_rating'] : $settings['trust_score'] ) ); ?>
					</div>
					<?php if ( ! empty( $settings['total_reviews'] ) ) : ?>
						<span class="truspilot-review__count"><?php echo esc_html( sprintf( _n( '%s review', '%s reviews', (int) $settings['total_reviews'], 'truspilot-review' ), number_format_i18n( (int) $settings['total_reviews'] ) ) ); ?></span>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<div class="truspilot-review__viewport">
				<div class="truspilot-review__track">
					<?php foreach ( $data['reviews'] as $index => $review ) : ?>
						<article class="truspilot-review__card" data-truspilot-slide="<?php echo esc_attr( (string) $index ); ?>">
							<div class="truspilot-review__rating" aria-label="<?php echo esc_attr( sprintf( __( '%s out of 5 stars', 'truspilot-review' ), $review['rating'] ) ); ?>">
								<?php echo wp_kses_post( $this->render_stars( $review['rating'] ) ); ?>
							</div>
							<?php if ( ! empty( $review['title'] ) ) : ?>
								<h3 class="truspilot-review__review-title"><?php echo esc_html( $review['title'] ); ?></h3>
							<?php endif; ?>
							<p class="truspilot-review__body"><?php echo esc_html( $review['body'] ); ?></p>
							<?php if ( ! empty( $review['verified'] ) || ! empty( $review['country'] ) ) : ?>
								<div class="truspilot-review__badges">
									<?php if ( ! empty( $review['verified'] ) ) : ?>
										<span><?php echo esc_html__( 'Verified', 'truspilot-review' ); ?></span>
									<?php endif; ?>
									<?php if ( ! empty( $review['country'] ) ) : ?>
										<span><?php echo esc_html( $review['country'] ); ?></span>
									<?php endif; ?>
								</div>
							<?php endif; ?>
							<footer class="truspilot-review__meta">
								<span><?php echo esc_html( $review['author'] ); ?></span>
								<?php if ( ! empty( $review['date'] ) ) : ?>
									<time datetime="<?php echo esc_attr( $review['date'] ); ?>"><?php echo esc_html( $this->format_review_date( $review['date'] ) ); ?></time>
								<?php endif; ?>
							</footer>
						</article>
					<?php endforeach; ?>
				</div>
			</div>

			<?php if ( 'carousel' === $layout && count( $data['reviews'] ) > 1 ) : ?>
				<div class="truspilot-review__controls" aria-label="<?php echo esc_attr__( 'Review carousel controls', 'truspilot-review' ); ?>">
					<button class="truspilot-review__button" type="button" data-truspilot-prev aria-label="<?php echo esc_attr__( 'Previous review', 'truspilot-review' ); ?>">&lsaquo;</button>
					<div class="truspilot-review__dots" data-truspilot-dots></div>
					<button class="truspilot-review__button" type="button" data-truspilot-next aria-label="<?php echo esc_attr__( 'Next review', 'truspilot-review' ); ?>">&rsaquo;</button>
				</div>
			<?php endif; ?>
		</section>
		<?php

		return ob_get_clean();
	}

	/**
	 * Get local published reviews.
	 *
	 * @param int   $count Count.
	 * @param float $min_rating Minimum rating.
	 * @param bool  $featured_first Featured first.
	 * @return array
	 */
	private function get_local_reviews( $count, $min_rating, $featured_first ) {
		$cache_bust = (int) get_option( 'truspilot_review_cache_bust', 1 );
		$cache_key  = 'truspilot_r_' . $cache_bust . '_' . md5( serialize( array( $count, $min_rating, $featured_first ) ) );
		$cached     = wp_cache_get( $cache_key, 'truspilot_review' );

		if ( false !== $cached ) {
			return $cached;
		}

		$meta_query = array(
			array(
				'key'     => '_truspilot_rating',
				'value'   => $min_rating,
				'type'    => 'NUMERIC',
				'compare' => '>=',
			),
		);

		$args = array(
			'post_type'      => self::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => $count,
			'meta_query'     => $meta_query,
			'orderby'        => $featured_first ? array( 'meta_value_num' => 'DESC', 'menu_order' => 'ASC', 'date' => 'DESC' ) : array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
		);

		if ( $featured_first ) {
			$args['meta_key'] = '_truspilot_featured';
		}

		$query   = new WP_Query( $args );
		$reviews = array();

		foreach ( $query->posts as $post ) {
			$meta      = $this->get_review_meta( $post->ID );
			$body      = $meta['short_excerpt'] ? $meta['short_excerpt'] : wp_strip_all_tags( $post->post_content );
			$reviews[] = array(
				'title'    => get_the_title( $post ),
				'body'     => wp_trim_words( sanitize_textarea_field( $body ), 44, '...' ),
				'author'   => $meta['author'] ? $meta['author'] : __( 'Trustpilot reviewer', 'truspilot-review' ),
				'date'     => $meta['date'],
				'rating'   => $meta['rating'],
				'country'  => $meta['country'],
				'verified' => $meta['verified'],
			);
		}

		wp_reset_postdata();

		$result = array( 'reviews' => $reviews );
		wp_cache_set( $cache_key, $result, 'truspilot_review', 3600 );

		return $result;
	}

	/**
	 * Flush the review query cache.
	 *
	 * @param int $post_id Post ID.
	 */
	public function flush_review_cache( $post_id ) {
		if ( self::POST_TYPE === get_post_type( $post_id ) ) {
			update_option( 'truspilot_review_cache_bust', (int) get_option( 'truspilot_review_cache_bust', 1 ) + 1 );
		}
	}

	/**
	 * Parse pasted import text.
	 *
	 * @param string $raw Raw text.
	 * @return array
	 */
	private function parse_import_text( $raw ) {
		$raw = trim( (string) $raw );

		if ( '' === $raw ) {
			return array();
		}

		if ( $this->looks_like_html( $raw ) ) {
			return $this->parse_public_reviews_html( $raw );
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
			$body = $title;
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

	/**
	 * Insert imported review.
	 *
	 * @param array $review Review data.
	 * @return int|false
	 */
	private function insert_imported_review( array $review ) {
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
	private function trash_existing_reviews() {
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
	 * Custom review columns.
	 *
	 * @param array $columns Columns.
	 * @return array
	 */
	public function review_columns( $columns ) {
		$columns['truspilot_rating']   = __( 'Rating', 'truspilot-review' );
		$columns['truspilot_author']   = __( 'Reviewer', 'truspilot-review' );
		$columns['truspilot_featured'] = __( 'Featured', 'truspilot-review' );

		return $columns;
	}

	/**
	 * Render review column.
	 *
	 * @param string $column Column.
	 * @param int    $post_id Post ID.
	 */
	public function render_review_column( $column, $post_id ) {
		$meta = $this->get_review_meta( $post_id );

		if ( 'truspilot_rating' === $column ) {
			echo esc_html( $meta['rating'] . ' / 5' );
		}

		if ( 'truspilot_author' === $column ) {
			echo esc_html( $meta['author'] );
		}

		if ( 'truspilot_featured' === $column ) {
			echo esc_html( $meta['featured'] ? __( 'Yes', 'truspilot-review' ) : __( 'No', 'truspilot-review' ) );
		}
	}

	/**
	 * Redirect back to settings.
	 *
	 * @param array $args Query args.
	 */
	private function redirect_to_settings( array $args ) {
		wp_safe_redirect(
			add_query_arg(
				array_merge(
					array(
						'post_type' => self::POST_TYPE,
						'page'      => 'truspilot-review',
					),
					$args
				),
				admin_url( 'edit.php' )
			)
		);
		exit;
	}

	/**
	 * Get readable admin error.
	 *
	 * @param string $code Error code.
	 * @return string
	 */
	private function get_admin_error_message( $code ) {
		$messages = array(
			'invalid_url'        => __( 'Please enter a valid public Trustpilot profile URL.', 'truspilot-review' ),
			'request_failed'     => __( 'The scrape request failed before Trustpilot returned a page.', 'truspilot-review' ),
			'trustpilot_blocked' => __( 'Trustpilot returned a browser verification page, so no reviews could be scraped from this server.', 'truspilot-review' ),
			'http_error'         => __( 'Trustpilot returned an HTTP error while scraping reviews.', 'truspilot-review' ),
			'no_reviews'         => __( 'No reviews were found in the public page data.', 'truspilot-review' ),
		);

		return isset( $messages[ $code ] ) ? $messages[ $code ] : __( 'Reviews could not be scraped.', 'truspilot-review' );
	}

	/**
	 * Get review meta.
	 *
	 * @param int $post_id Post ID.
	 * @return array
	 */
	private function get_review_meta( $post_id ) {
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
	 * Render star chips.
	 *
	 * @param float $rating Rating.
	 * @return string
	 */
	private function render_stars( $rating ) {
		$full = (int) round( (float) $rating );
		$html = '';

		for ( $i = 1; $i <= 5; $i++ ) {
			$html .= '<span class="truspilot-review__star' . ( $i <= $full ? ' is-filled' : '' ) . '">&#9733;</span>';
		}

		return $html;
	}

	/**
	 * Format review date.
	 *
	 * @param string $date Date string.
	 * @return string
	 */
	private function format_review_date( $date ) {
		$timestamp = strtotime( $date );

		if ( ! $timestamp ) {
			return $date;
		}

		return date_i18n( get_option( 'date_format' ), $timestamp );
	}

	/**
	 * Check if profile summary has enough data.
	 *
	 * @param array $settings Settings.
	 * @return bool
	 */
	private function has_profile_summary( array $settings ) {
		return ! empty( $settings['trust_score'] ) || ! empty( $settings['star_rating'] ) || ! empty( $settings['total_reviews'] );
	}

	/**
	 * Get sanitized settings.
	 *
	 * @return array
	 */
	private function get_settings() {
		return wp_parse_args( get_option( self::OPTION_NAME, array() ), $this->default_settings() );
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

	/**
	 * Validate that a URL is a public Trustpilot URL.
	 *
	 * @param string $url URL.
	 * @return bool
	 */
	private function is_allowed_trustpilot_url( $url ) {
		$parts = wp_parse_url( $url );

		if ( empty( $parts['scheme'] ) || empty( $parts['host'] ) || ! in_array( strtolower( $parts['scheme'] ), array( 'http', 'https' ), true ) ) {
			return false;
		}

		$host = strtolower( $parts['host'] );

		return (bool) preg_match( '/(^|\.)trustpilot\.[a-z.]+$/', $host );
	}

	/**
	 * Sanitize an enumerated choice.
	 *
	 * @param string $value Raw value.
	 * @param array  $allowed Allowed values.
	 * @param string $fallback Fallback.
	 * @return string
	 */
	private function sanitize_choice( $value, array $allowed, $fallback ) {
		$value = sanitize_key( $value );

		return in_array( $value, $allowed, true ) ? $value : $fallback;
	}

	/**
	 * Convert mixed value to bool.
	 *
	 * @param mixed $value Raw value.
	 * @return bool
	 */
	private function to_bool( $value ) {
		return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
	}
}
