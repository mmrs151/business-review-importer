<?php
/**
 * Admin class for settings, import UI, meta boxes, and columns.
 *
 * @package TruspilotReview
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles all WordPress admin functionality for the plugin.
 */
final class Truspilot_Review_Admin {

	const OPTION_NAME = 'truspilot_review_settings';
	const POST_TYPE   = 'truspilot_review';

	/**
	 * Plugin instance.
	 *
	 * @var Truspilot_Review_Plugin
	 */
	private $plugin;

	/**
	 * Importer instance.
	 *
	 * @var Truspilot_Review_Importer
	 */
	private $importer;

	/**
	 * Scraper instance.
	 *
	 * @var Truspilot_Review_Scraper
	 */
	private $scraper;

	/**
	 * Constructor.
	 *
	 * @param Truspilot_Review_Plugin   $plugin   Main plugin instance.
	 * @param Truspilot_Review_Importer $importer Importer instance.
	 * @param Truspilot_Review_Scraper  $scraper  Scraper instance.
	 */
	public function __construct(
		Truspilot_Review_Plugin $plugin,
		Truspilot_Review_Importer $importer,
		Truspilot_Review_Scraper $scraper
	) {
		$this->plugin   = $plugin;
		$this->importer = $importer;
		$this->scraper  = $scraper;
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
		$settings = $this->plugin->get_settings();
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
		$settings = $this->plugin->get_settings();
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
			$url = $this->plugin->get_settings()['public_url'];
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
		$fields = $this->plugin->get_review_meta( $post->ID );
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
			$this->importer->trash_existing();
		}

		$raw      = isset( $_POST['truspilot_import_text'] ) ? wp_unslash( $_POST['truspilot_import_text'] ) : '';
		$reviews  = $this->importer->parse_import_text( $raw );
		$imported = 0;

		foreach ( $reviews as $review ) {
			if ( $this->importer->insert_review( $review ) ) {
				++$imported;
			}
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'post_type'          => self::POST_TYPE,
					'page'               => 'truspilot-review',
					'truspilot_imported' => $imported,
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
			$this->importer->trash_existing();
		}

		$result   = $this->scraper->scrape( $url, $max_pages );
		$imported = 0;

		foreach ( $result['reviews'] as $review ) {
			if ( $this->importer->insert_review( $review ) ) {
				++$imported;
			}
		}

		if ( 0 === $imported && ! empty( $result['error'] ) ) {
			$this->redirect_to_settings( array( 'truspilot_error' => $result['error'] ) );
		}

		$this->redirect_to_settings( array( 'truspilot_scraped' => $imported ) );
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
		$meta = $this->plugin->get_review_meta( $post_id );

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
}
