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
	 * Constructor.
	 *
	 * @param Truspilot_Review_Plugin   $plugin   Main plugin instance.
	 * @param Truspilot_Review_Importer $importer Importer instance.
	 */
	public function __construct(
		Truspilot_Review_Plugin $plugin,
		Truspilot_Review_Importer $importer
	) {
		$this->plugin   = $plugin;
		$this->importer = $importer;
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
			'business_name'    => __( 'Business name', 'truspilot-review' ),
			'business_domain'  => __( 'Business domain', 'truspilot-review' ),
			'public_url'       => __( 'Trustpilot profile URL', 'truspilot-review' ),
			'trust_score'      => __( 'TrustScore', 'truspilot-review' ),
			'star_rating'      => __( 'Star rating', 'truspilot-review' ),
			'total_reviews'    => __( 'Total reviews', 'truspilot-review' ),
			'rating_label'     => __( 'Rating label', 'truspilot-review' ),
			'card_background'  => __( 'Card background', 'truspilot-review' ),
			'default_title'    => __( 'Default section title', 'truspilot-review' ),
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

		add_submenu_page(
			'edit.php?post_type=' . self::POST_TYPE,
			__( 'Shortcode Help', 'truspilot-review' ),
			__( 'Shortcode Help', 'truspilot-review' ),
			'edit_posts',
			'truspilot-review-help',
			array( $this, 'render_help_page' )
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
		$error    = isset( $_GET['truspilot_error'] ) ? sanitize_key( wp_unslash( $_GET['truspilot_error'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Truspilot Review Blocks', 'truspilot-review' ); ?></h1>
			<?php if ( null !== $imported ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php echo esc_html( sprintf( _n( '%d review imported.', '%d reviews imported.', $imported, 'truspilot-review' ), $imported ) ); ?></p>
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
			<?php if ( trp_fs()->can_use_premium_code() ) : ?>
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
			<?php else : ?>
			<div style="background:#eff6ff;border:1px solid #b8d4fe;border-radius:6px;padding:1.25rem 1.5rem;margin:1rem 0;">
				<h2 style="margin-top:0;"><?php echo esc_html__( 'Easy Browser Import', 'truspilot-review' ); ?></h2>
				<p style="font-size:1rem;"><?php echo esc_html__( 'Bulk import reviews from Trustpilot page source, JSON, or plain text.', 'truspilot-review' ); ?></p>
				<p><?php echo esc_html__( 'This feature is available in the Pro version. Upgrade to unlock:', 'truspilot-review' ); ?></p>
				<ul style="list-style:disc;padding-left:1.5rem;">
					<li><?php echo esc_html__( 'Easy Browser Import (paste page source / JSON / text)', 'truspilot-review' ); ?></li>
					<li><?php echo esc_html__( 'Carousel, Grid, Wall layouts', 'truspilot-review' ); ?></li>
					<li><?php echo esc_html__( 'Unlimited reviews', 'truspilot-review' ); ?></li>
					<li><?php echo esc_html__( 'Card links, linked summary, and more', 'truspilot-review' ); ?></li>
				</ul>
				<p><a class="button button-primary" href="<?php echo esc_url( trp_fs()->get_upgrade_url() ); ?>" target="_blank" rel="noopener"><?php echo esc_html__( 'Upgrade to Pro — 30-day free trial', 'truspilot-review' ); ?></a></p>
			</div>
			<?php endif; ?>

			<h2><?php echo esc_html__( 'Shortcodes', 'truspilot-review' ); ?></h2>
			<p><code>[truspilot_reviews count="3" layout="carousel" autoplay="true"]</code></p>
			<p><code>[truspilot_reviews count="12" layout="grid" min_rating="4" featured_first="true" grid_rows="3" grid_columns="4"]</code></p>
			<p><code>[truspilot_reviews count="24" layout="list" full_page="true"]</code></p>
			<p><code>[truspilot_reviews count="0" layout="wall" wall_style="noticeboard"]</code></p>
		</div>
		<?php
	}

	/**
	 * Render help page with shortcode docs.
	 */
	public function render_help_page() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'truspilot-review' ) );
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Truspilot Review — Shortcode & Block Help', 'truspilot-review' ); ?></h1>

			<h2><?php echo esc_html__( 'Shortcode', 'truspilot-review' ); ?></h2>
			<p><code>[truspilot_reviews]</code></p>
			<p><?php echo esc_html__( 'Place this shortcode on any page or post to display your locally managed reviews.', 'truspilot-review' ); ?></p>

			<h2><?php echo esc_html__( 'Attributes', 'truspilot-review' ); ?></h2>
			<table class="widefat fixed striped">
				<thead>
					<tr>
						<th><?php echo esc_html__( 'Attribute', 'truspilot-review' ); ?></th>
						<th><?php echo esc_html__( 'Default', 'truspilot-review' ); ?></th>
						<th><?php echo esc_html__( 'Options', 'truspilot-review' ); ?></th>
						<th><?php echo esc_html__( 'Description', 'truspilot-review' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><code>count</code></td>
						<td><code>0</code></td>
						<td><?php echo esc_html__( '0 – 48', 'truspilot-review' ); ?></td>
						<td><?php echo esc_html__( 'Number of reviews to display. 0 shows all featured reviews.', 'truspilot-review' ); ?></td>
					</tr>
					<tr>
						<td><code>layout</code></td>
						<td><code>carousel</code></td>
						<td><code>carousel</code>, <code>grid</code>, <code>list</code>, <code>wall</code></td>
						<td><?php echo esc_html__( 'Display layout.', 'truspilot-review' ); ?></td>
					</tr>
					<tr>
						<td><code>title</code></td>
						<td><code>Parent Reviews</code></td>
						<td><?php echo esc_html__( 'Any text', 'truspilot-review' ); ?></td>
						<td><?php echo esc_html__( 'Section heading.', 'truspilot-review' ); ?></td>
					</tr>
					<tr>
						<td><code>autoplay</code></td>
						<td><code>true</code></td>
						<td><code>true</code>, <code>false</code></td>
						<td><?php echo esc_html__( 'Auto-rotate carousel slides.', 'truspilot-review' ); ?></td>
					</tr>
					<tr>
						<td><code>interval</code></td>
						<td><code>5500</code></td>
						<td><?php echo esc_html__( '2500 – 20000', 'truspilot-review' ); ?></td>
						<td><?php echo esc_html__( 'Carousel rotation speed in milliseconds.', 'truspilot-review' ); ?></td>
					</tr>
					<tr>
						<td><code>full_page</code></td>
						<td><code>false</code></td>
						<td><code>true</code>, <code>false</code></td>
						<td><?php echo esc_html__( 'Expand the review section to full page width and height.', 'truspilot-review' ); ?></td>
					</tr>
					<tr>
						<td><code>min_rating</code></td>
						<td><code>1</code></td>
						<td><?php echo esc_html__( '1 – 5', 'truspilot-review' ); ?></td>
						<td><?php echo esc_html__( 'Minimum star rating to include (1 = all).', 'truspilot-review' ); ?></td>
					</tr>
					<tr>
						<td><code>featured_first</code></td>
						<td><code>true</code></td>
						<td><code>true</code>, <code>false</code></td>
						<td><?php echo esc_html__( 'Show featured reviews before standard reviews.', 'truspilot-review' ); ?></td>
					</tr>
					<tr>
						<td><code>grid_rows</code></td>
						<td><code>3</code></td>
						<td><?php echo esc_html__( '1 – 6', 'truspilot-review' ); ?></td>
						<td><?php echo esc_html__( 'Number of grid rows (grid layout only).', 'truspilot-review' ); ?></td>
					</tr>
					<tr>
						<td><code>grid_columns</code></td>
						<td><code>3</code></td>
						<td><?php echo esc_html__( '1 – 6', 'truspilot-review' ); ?></td>
						<td><?php echo esc_html__( 'Number of grid columns (grid layout only).', 'truspilot-review' ); ?></td>
					</tr>
					<tr>
						<td><code>wall_style</code></td>
						<td><code>standard</code></td>
						<td><code>standard</code>, <code>noticeboard</code></td>
						<td><?php echo esc_html__( 'Wall sub-style (wall layout only).', 'truspilot-review' ); ?></td>
					</tr>
				</tbody>
			</table>

			<h2><?php echo esc_html__( 'Layouts', 'truspilot-review' ); ?></h2>
			<dl>
				<dt><strong><?php echo esc_html__( 'Carousel', 'truspilot-review' ); ?></strong></dt>
				<dd><?php echo esc_html__( 'Slideshow with previous/next buttons and dot navigation. Use with autoplay and interval for automatic rotation.', 'truspilot-review' ); ?></dd>

				<dt><strong><?php echo esc_html__( 'Grid', 'truspilot-review' ); ?></strong></dt>
				<dd><?php echo esc_html__( 'Cards arranged in a uniform grid. Set grid_rows and grid_columns to control the dimensions. Each card keeps a square aspect ratio. Empty cells display an "Add yours here" link.', 'truspilot-review' ); ?></dd>

				<dt><strong><?php echo esc_html__( 'List', 'truspilot-review' ); ?></strong></dt>
				<dd><?php echo esc_html__( 'A scrollable vertical list of all reviews. Featured reviews appear first by default.', 'truspilot-review' ); ?></dd>

				<dt><strong><?php echo esc_html__( 'Wall', 'truspilot-review' ); ?></strong></dt>
				<dd>
					<?php echo esc_html__( 'Two styles:', 'truspilot-review' ); ?>
					<ul style="list-style: disc; padding-left: 2rem;">
						<li><strong><?php echo esc_html__( 'Standard', 'truspilot-review' ); ?></strong> — <?php echo esc_html__( 'Cards scattered across the page in a mood-board arrangement.', 'truspilot-review' ); ?></li>
						<li><strong><?php echo esc_html__( 'Noticeboard', 'truspilot-review' ); ?></strong> — <?php echo esc_html__( 'Post-it style tiles with thumbtack pins and subtle rotations.', 'truspilot-review' ); ?></li>
					</ul>
				</dd>
			</dl>

			<h2><?php echo esc_html__( 'Examples', 'truspilot-review' ); ?></h2>
			<table class="widefat fixed striped">
				<thead>
					<tr>
						<th><?php echo esc_html__( 'Description', 'truspilot-review' ); ?></th>
						<th><?php echo esc_html__( 'Shortcode', 'truspilot-review' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><?php echo esc_html__( 'Default carousel', 'truspilot-review' ); ?></td>
						<td><code>[truspilot_reviews]</code></td>
					</tr>
					<tr>
						<td><?php echo esc_html__( 'Carousel with custom speed', 'truspilot-review' ); ?></td>
						<td><code>[truspilot_reviews count="5" layout="carousel" autoplay="true" interval="6000"]</code></td>
					</tr>
					<tr>
						<td><?php echo esc_html__( 'Grid with sizing', 'truspilot-review' ); ?></td>
						<td><code>[truspilot_reviews count="12" layout="grid" grid_rows="3" grid_columns="4" min_rating="4"]</code></td>
					</tr>
					<tr>
						<td><?php echo esc_html__( 'Scrollable list', 'truspilot-review' ); ?></td>
						<td><code>[truspilot_reviews count="24" layout="list" full_page="true"]</code></td>
					</tr>
					<tr>
						<td><?php echo esc_html__( 'Wall — standard scattered', 'truspilot-review' ); ?></td>
						<td><code>[truspilot_reviews count="0" layout="wall" wall_style="standard"]</code></td>
					</tr>
					<tr>
						<td><?php echo esc_html__( 'Wall — noticeboard post-its', 'truspilot-review' ); ?></td>
						<td><code>[truspilot_reviews count="0" layout="wall" wall_style="noticeboard"]</code></td>
					</tr>
					<tr>
						<td><?php echo esc_html__( 'All featured reviews', 'truspilot-review' ); ?></td>
						<td><code>[truspilot_reviews count="0" featured_first="true"]</code></td>
					</tr>
					<tr>
						<td><?php echo esc_html__( 'Full page wall', 'truspilot-review' ); ?></td>
						<td><code>[truspilot_reviews count="0" layout="wall" full_page="true"]</code></td>
					</tr>
				</tbody>
			</table>

			<h2><?php echo esc_html__( 'Block (Gutenberg)', 'truspilot-review' ); ?></h2>
			<p><?php echo esc_html__( 'Search for the "Truspilot Reviews" block in the block inserter. The block provides the same settings as the shortcode through the inspector panel on the right.', 'truspilot-review' ); ?></p>
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

		if ( 'card_background' === $field ) :
			?>
			<input type="color" id="<?php echo esc_attr( $field ); ?>" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[<?php echo esc_attr( $field ); ?>]" value="<?php echo esc_attr( $value ? $value : '#ffffff' ); ?>" />
			<?php
		else :
			?>
			<input
				type="<?php echo esc_attr( $type ); ?>"
				name="<?php echo esc_attr( self::OPTION_NAME ); ?>[<?php echo esc_attr( $field ); ?>]"
				value="<?php echo esc_attr( (string) $value ); ?>"
				class="regular-text"
				<?php echo 'number' === $type ? 'step="' . esc_attr( $step ) . '"' : ''; ?>
			/>
			<?php
		endif;
		?>
		<?php if ( 'public_url' === $field ) : ?>
			<p class="description"><?php echo esc_html__( 'Example: https://uk.trustpilot.com/review/example.com', 'truspilot-review' ); ?></p>
		<?php endif; ?>
		<?php if ( 'card_background' === $field ) : ?>
			<p class="description"><?php echo esc_html__( 'Default card background color for all layouts.', 'truspilot-review' ); ?></p>
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
			'business_name'    => isset( $input['business_name'] ) ? sanitize_text_field( wp_unslash( $input['business_name'] ) ) : '',
			'business_domain'  => isset( $input['business_domain'] ) ? sanitize_text_field( wp_unslash( $input['business_domain'] ) ) : '',
			'public_url'       => $url,
			'trust_score'      => isset( $input['trust_score'] ) ? min( 5, max( 0, (float) $input['trust_score'] ) ) : 0,
			'star_rating'      => isset( $input['star_rating'] ) ? min( 5, max( 0, (float) $input['star_rating'] ) ) : 0,
			'total_reviews'    => isset( $input['total_reviews'] ) ? absint( $input['total_reviews'] ) : 0,
			'rating_label'     => isset( $input['rating_label'] ) ? sanitize_text_field( wp_unslash( $input['rating_label'] ) ) : '',
			'card_background'  => isset( $input['card_background'] ) ? sanitize_hex_color( wp_unslash( $input['card_background'] ) ) : '',
			'default_title'    => isset( $input['default_title'] ) ? sanitize_text_field( wp_unslash( $input['default_title'] ) ) : '',
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
	 * Get admin error message for the import flow.
	 *
	 * @param string $code Error code.
	 * @return string
	 */
	private function get_admin_error_message( $code ) {
		$messages = array(
			'invalid_url' => __( 'Please enter a valid public Trustpilot profile URL.', 'truspilot-review' ),
			'no_reviews'  => __( 'No reviews were found in the imported data.', 'truspilot-review' ),
		);

		return isset( $messages[ $code ] ) ? $messages[ $code ] : __( 'An unknown error occurred during import.', 'truspilot-review' );
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
