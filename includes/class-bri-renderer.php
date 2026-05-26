<?php
/**
 * Renderer class for displaying reviews as carousel, grid, or wall.
 *
 * @package BusinessReviewImporter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders business reviews as carousel, grid, list, or wall.
 */
final class BRI_Renderer {

	const POST_TYPE     = 'bri_review';
	const MAX_REVIEWS   = 48;
	const DEFAULT_COUNT = 0;

	/**
	 * Plugin instance for accessing shared helpers.
	 *
	 * @var BRI_Plugin
	 */
	private $plugin;

	/**
	 * Constructor.
	 *
	 * @param BRI_Plugin $plugin Main plugin instance.
	 */
	public function __construct( BRI_Plugin $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Render shortcode.
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function shortcode( $atts ) {
		$default_title = $this->plugin->get_settings()['default_title'];
		$atts = shortcode_atts(
			array(
				'count'            => self::DEFAULT_COUNT,
				'layout'           => 'carousel',
				'autoplay'         => 'true',
				'interval'         => 5500,
				'full_page'        => 'false',
				'title'            => $default_title ?: __( 'Parent Reviews', 'business-review-importer' ),
				'min_rating'       => 1,
				'featured_first'   => 'true',
				'grid_rows'        => 3,
				'grid_columns'     => 3,
				'wall_style'       => 'standard',
				'carousel_visible' => 1,
				'orientation'      => 'vertical',
			),
			(array) $atts,
			'business_reviews'
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
		$default_title = $this->plugin->get_settings()['default_title'];

		return $this->render_reviews(
			array(
				'count'            => isset( $attributes['count'] ) ? $attributes['count'] : self::DEFAULT_COUNT,
				'layout'           => isset( $attributes['layout'] ) ? $attributes['layout'] : 'carousel',
				'autoplay'         => ! empty( $attributes['autoplay'] ) ? 'true' : 'false',
				'interval'         => isset( $attributes['interval'] ) ? $attributes['interval'] : 5500,
				'full_page'        => ! empty( $attributes['fullPage'] ) ? 'true' : 'false',
				'title'            => isset( $attributes['title'] ) ? $attributes['title'] : ( $default_title ?: __( 'Parent Reviews', 'business-review-importer' ) ),
				'min_rating'       => isset( $attributes['minRating'] ) ? $attributes['minRating'] : 1,
				'featured_first'   => ! empty( $attributes['featuredFirst'] ) ? 'true' : 'false',
				'grid_rows'        => isset( $attributes['gridRows'] ) ? $attributes['gridRows'] : 3,
				'grid_columns'     => isset( $attributes['gridColumns'] ) ? $attributes['gridColumns'] : 3,
				'wall_style'       => isset( $attributes['wallStyle'] ) ? $attributes['wallStyle'] : 'standard',
				'carousel_visible' => isset( $attributes['carouselVisible'] ) ? $attributes['carouselVisible'] : 1,
				'orientation'      => isset( $attributes['orientation'] ) ? $attributes['orientation'] : 'vertical',
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
		$count          = isset( $raw_atts['count'] ) && '' !== $raw_atts['count'] ? min( self::MAX_REVIEWS, max( -1, absint( $raw_atts['count'] ) ) ) : 0;
		$layout         = $this->sanitize_choice( isset( $raw_atts['layout'] ) ? $raw_atts['layout'] : 'carousel', array( 'carousel', 'grid', 'list', 'wall' ), 'carousel' );
		$autoplay       = $this->to_bool( isset( $raw_atts['autoplay'] ) ? $raw_atts['autoplay'] : 'true' );
		$full_page      = $this->to_bool( isset( $raw_atts['full_page'] ) ? $raw_atts['full_page'] : 'false' );
		$featured_first = $this->to_bool( isset( $raw_atts['featured_first'] ) ? $raw_atts['featured_first'] : 'true' );
		$interval       = min( 20000, max( 2500, absint( $raw_atts['interval'] ?? 5500 ) ) );
		$min_rating     = min( 5, max( 1, (float) ( $raw_atts['min_rating'] ?? 1 ) ) );
		$title          = isset( $raw_atts['title'] ) ? sanitize_text_field( wp_unslash( $raw_atts['title'] ) ) : '';
		$grid_rows      = isset( $raw_atts['grid_rows'] ) ? min( 6, max( 1, absint( $raw_atts['grid_rows'] ) ) ) : 3;
		$grid_columns   = isset( $raw_atts['grid_columns'] ) ? min( 6, max( 1, absint( $raw_atts['grid_columns'] ) ) ) : 3;
		$wall_style     = isset( $raw_atts['wall_style'] ) ? $this->sanitize_choice( $raw_atts['wall_style'], array( 'standard', 'noticeboard' ), 'standard' ) : 'standard';
		$carousel_visible = isset( $raw_atts['carousel_visible'] ) ? min( 6, max( 1, absint( $raw_atts['carousel_visible'] ) ) ) : 1;
		$orientation    = isset( $raw_atts['orientation'] ) ? $this->sanitize_choice( $raw_atts['orientation'], array( 'vertical', 'horizontal' ), 'vertical' ) : 'vertical';
		$settings       = $this->plugin->get_settings();

		if ( ! bri_fs()->can_use_premium_code() ) {
			$layout         = 'list';
			$count          = $count > 0 ? min( $count, 3 ) : 3;
			$autoplay       = false;
			$full_page      = false;
			$featured_first = false;
			$min_rating     = 1;
			$grid_rows      = 3;
			$grid_columns   = 3;
			$wall_style     = 'standard';
			$carousel_visible = 1;
		}
		$evaluate_url   = $this->get_evaluate_url( $settings );
		$card_bg        = ! empty( $settings['card_background'] ) ? $settings['card_background'] : '';

		$show_all_featured = ( 0 === $count && $featured_first );
		$show_empty        = ( 0 === $count && ! $featured_first );

		if ( $show_empty ) {
			$reviews      = array();
			$review_count = 0;
		} else {
			$data         = $this->get_local_reviews( $count, $min_rating, $featured_first, $show_all_featured );
			$reviews      = $data['reviews'];
			$review_count = count( $reviews );
		}

		if ( 0 === $review_count && ! $show_empty ) {
			return current_user_can( 'edit_posts' ) ? '<p class="bri-notice">' . esc_html__( 'Add or import local reviews to display this block.', 'business-review-importer' ) . '</p>' : '';
		}

		$grid_cells = ( 'grid' === $layout ) ? $grid_rows * $grid_columns : 0;

		wp_enqueue_style( 'bri-frontend' );
		wp_enqueue_script( 'bri-frontend' );

		$classes = array(
			'business-review-importer',
			'bri-' . $layout,
			$full_page ? 'bri-full' : '',
		);
		if ( 'grid' === $layout ) {
			$classes[] = 'bri-grid-cols-' . $grid_columns;
		}
		if ( 'wall' === $layout ) {
			$classes[] = 'bri-wall-' . $wall_style;
		}
		if ( 'list' === $layout ) {
			$classes[] = 'bri-list-' . $orientation;
		}

		$section_style = '';
		if ( $card_bg ) {
			$section_style .= '--bri-card-bg:' . esc_attr( $card_bg ) . ';';
		}
		if ( 'carousel' === $layout && $carousel_visible > 1 ) {
			$section_style .= '--bri-visible:' . (string) $carousel_visible . ';';
		}

		ob_start();
		?>
		<section
			class="<?php echo esc_attr( trim( implode( ' ', array_filter( $classes ) ) ) ); ?>"
			<?php echo $section_style ? 'style="' . esc_attr( $section_style ) . '"' : ''; ?>
			data-bri
			data-layout="<?php echo esc_attr( $layout ); ?>"
			data-autoplay="<?php echo esc_attr( $autoplay ? 'true' : 'false' ); ?>"
			data-interval="<?php echo esc_attr( (string) $interval ); ?>"
			<?php if ( 'carousel' === $layout ) : ?>
			data-visible="<?php echo esc_attr( (string) $carousel_visible ); ?>"
			<?php endif; ?>
		>
			<div class="bri-header">
				<div>
					<?php if ( $title ) : ?>
						<h2 class="bri-title"><?php echo esc_html( $title ); ?></h2>
					<?php endif; ?>
					<?php if ( ! empty( $settings['business_name'] ) ) : ?>
						<?php if ( ! empty( $settings['public_url'] ) ) : ?>
							<a class="bri-business" href="<?php echo esc_url( $settings['public_url'] ); ?>" rel="nofollow noopener" target="_blank"><?php echo esc_html( $settings['business_name'] ); ?></a>
						<?php else : ?>
							<p class="bri-business"><?php echo esc_html( $settings['business_name'] ); ?></p>
						<?php endif; ?>
					<?php endif; ?>
				</div>
				<?php if ( ! empty( $settings['public_url'] ) ) : ?>
					<a class="bri-source" href="<?php echo esc_url( $settings['public_url'] ); ?>" rel="nofollow noopener" target="_blank">
						<?php echo esc_html__( 'View on Trustpilot', 'business-review-importer' ); ?>
					</a>
				<?php endif; ?>
			</div>

			<?php if ( $this->has_profile_summary( $settings ) ) : ?>
				<?php if ( ! empty( $settings['public_url'] ) ) : ?>
				<a class="bri-summary" href="<?php echo esc_url( $settings['public_url'] ); ?>" rel="nofollow noopener" target="_blank">
				<?php else : ?>
				<div class="bri-summary">
				<?php endif; ?>
					<div class="bri-score">
						<strong><?php echo esc_html( $settings['rating_label'] ? $settings['rating_label'] : __( 'Rated', 'business-review-importer' ) ); ?></strong>
						<span><?php echo esc_html( $settings['trust_score'] ? number_format_i18n( (float) $settings['trust_score'], 1 ) : number_format_i18n( (float) $settings['star_rating'], 1 ) ); ?> / 5</span>
					</div>
					<div class="bri-rating" aria-label="<?php echo esc_attr__( 'Business star rating', 'business-review-importer' ); ?>">
						<?php echo wp_kses_post( $this->render_stars( $settings['star_rating'] ? $settings['star_rating'] : $settings['trust_score'] ) ); ?>
					</div>
					<?php if ( ! empty( $settings['total_reviews'] ) ) : ?>
						<span class="bri-count"><?php echo esc_html( sprintf( /* translators: %s: number of reviews */ _n( '%s review', '%s reviews', (int) $settings['total_reviews'], 'business-review-importer' ), number_format_i18n( (int) $settings['total_reviews'] ) ) ); ?></span>
					<?php endif; ?>
				<?php if ( ! empty( $settings['public_url'] ) ) : ?>
				</a>
				<?php else : ?>
				</div>
				<?php endif; ?>
			<?php endif; ?>

			<div class="bri-viewport">
				<div class="bri-track">
					<?php if ( $show_empty && 'grid' !== $layout ) : ?>
						<article class="bri-card bri-card--empty">
							<div class="bri-empty">
								<?php if ( $evaluate_url ) : ?>
									<a href="<?php echo esc_url( $evaluate_url ); ?>" rel="nofollow noopener" target="_blank"><?php esc_html_e( "Add yours here", 'business-review-importer' ); ?></a>
								<?php else : ?>
									<p><?php esc_html_e( "Add yours here", 'business-review-importer' ); ?></p>
								<?php endif; ?>
							</div>
						</article>
					<?php elseif ( $show_empty && 'grid' === $layout ) : ?>
						<?php for ( $i = 0; $i < $grid_cells; $i++ ) : ?>
							<article class="bri-card bri-card--empty">
								<div class="bri-empty">
									<?php if ( $evaluate_url ) : ?>
										<a href="<?php echo esc_url( $evaluate_url ); ?>" rel="nofollow noopener" target="_blank"><?php esc_html_e( "Add yours here", 'business-review-importer' ); ?></a>
									<?php else : ?>
										<p><?php esc_html_e( "Add yours here", 'business-review-importer' ); ?></p>
									<?php endif; ?>
								</div>
							</article>
						<?php endfor; ?>
					<?php else : ?>
						<?php foreach ( $reviews as $index => $review ) : ?>
							<?php
							$review_url = ! empty( $review['source_url'] ) ? $review['source_url'] : ( ! empty( $settings['public_url'] ) ? $settings['public_url'] : '' );
							?>
							<article class="bri-card<?php echo $review_url ? ' bri-card--linked' : ''; ?>" data-bri-slide="<?php echo esc_attr( (string) $index ); ?>">
								<?php if ( $review_url ) : ?>
								<a href="<?php echo esc_url( $review_url ); ?>" rel="nofollow noopener" target="_blank" class="bri-card-link">
								<?php endif; ?>
								<div class="bri-rating" aria-label="<?php echo esc_attr( sprintf( /* translators: %s: star rating number */ __( '%s out of 5 stars', 'business-review-importer' ), $review['rating'] ) ); ?>">
									<?php echo wp_kses_post( $this->render_stars( $review['rating'] ) ); ?>
								</div>
								<?php if ( ! empty( $review['title'] ) ) : ?>
									<h3 class="bri-review-title"><?php echo esc_html( $review['title'] ); ?></h3>
								<?php endif; ?>
								<p class="bri-body"><?php echo esc_html( $review['body'] ); ?></p>
								<?php if ( ! empty( $review['verified'] ) || ! empty( $review['country'] ) ) : ?>
									<div class="bri-badges">
										<?php if ( ! empty( $review['verified'] ) ) : ?>
											<span><?php echo esc_html__( 'Verified', 'business-review-importer' ); ?></span>
										<?php endif; ?>
										<?php if ( ! empty( $review['country'] ) ) : ?>
											<span><?php echo esc_html( $review['country'] ); ?></span>
										<?php endif; ?>
									</div>
								<?php endif; ?>
								<footer class="bri-meta">
									<span><?php echo esc_html( $review['author'] ); ?></span>
									<?php if ( ! empty( $review['date'] ) ) : ?>
										<time datetime="<?php echo esc_attr( $review['date'] ); ?>"><?php echo esc_html( $this->format_review_date( $review['date'] ) ); ?></time>
									<?php endif; ?>
								</footer>
								<?php if ( $review_url ) : ?>
								</a>
								<?php endif; ?>
							</article>
						<?php endforeach; ?>
						<?php if ( $grid_cells > 0 && $review_count < $grid_cells ) : ?>
							<?php for ( $i = $review_count; $i < $grid_cells; $i++ ) : ?>
								<article class="bri-card bri-card--empty">
									<div class="bri-empty">
										<?php if ( $evaluate_url ) : ?>
											<a href="<?php echo esc_url( $evaluate_url ); ?>" rel="nofollow noopener" target="_blank"><?php esc_html_e( "Add yours here", 'business-review-importer' ); ?></a>
										<?php else : ?>
											<p><?php esc_html_e( "Add yours here", 'business-review-importer' ); ?></p>
										<?php endif; ?>
									</div>
								</article>
							<?php endfor; ?>
						<?php endif; ?>
					<?php endif; ?>
				</div>
			</div>

			<?php if ( 'carousel' === $layout && $review_count > 1 && ! $show_empty ) : ?>
				<div class="bri-controls" aria-label="<?php echo esc_attr__( 'Review carousel controls', 'business-review-importer' ); ?>">
					<button class="bri-button" type="button" data-bri-prev aria-label="<?php echo esc_attr__( 'Previous review', 'business-review-importer' ); ?>">&lsaquo;</button>
					<div class="bri-dots" data-bri-dots></div>
					<button class="bri-button" type="button" data-bri-next aria-label="<?php echo esc_attr__( 'Next review', 'business-review-importer' ); ?>">&rsaquo;</button>
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
	private function get_local_reviews( $count, $min_rating, $featured_first, $all_featured = false ) {
		$cache_bust = (int) get_option( 'bri_cache_bust', 1 );
		$cache_key  = 'bri_r_' . $cache_bust . '_' . md5( serialize( array( $count, $min_rating, $featured_first, $all_featured ) ) );
		$cached     = wp_cache_get( $cache_key, 'bri_review' );

		if ( false !== $cached ) {
			return $cached;
		}

		$meta_query = array(
			array(
				'key'     => '_bri_rating',
				'value'   => $min_rating,
				'type'    => 'NUMERIC',
				'compare' => '>=',
			),
		);

		$args = array(
			'post_type'      => self::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => $all_featured ? -1 : ( 0 === $count ? 1 : $count ),
			'meta_query'     => $meta_query, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'orderby'        => $featured_first ? array(
				'meta_value_num' => 'DESC',
				'menu_order'     => 'ASC',
				'date'           => 'DESC',
			) : array(
				'menu_order' => 'ASC',
				'date'       => 'DESC',
			),
		);

		if ( $all_featured ) {
			$args['meta_query'][] = array(
				'key'   => '_bri_featured',
				'value' => '1',
			);
			unset( $args['meta_key'] );
		} elseif ( $featured_first ) {
			$args['meta_key'] = '_bri_featured'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		}

		$query   = new WP_Query( $args );
		$reviews = array();

		foreach ( $query->posts as $post ) {
			$meta      = $this->plugin->get_review_meta( $post->ID );
			$body      = $meta['short_excerpt'] ? $meta['short_excerpt'] : wp_strip_all_tags( $post->post_content );
			$reviews[] = array(
				'title'      => get_the_title( $post ),
				'body'       => wp_trim_words( sanitize_textarea_field( $body ), 44, '...' ),
				'author'     => $meta['author'] ? $meta['author'] : __( 'Trustpilot reviewer', 'business-review-importer' ),
				'date'       => $meta['date'],
				'rating'     => $meta['rating'],
				'country'    => $meta['country'],
				'verified'   => $meta['verified'],
				'source_url' => $meta['source_url'],
			);
		}

		wp_reset_postdata();

		$result = array( 'reviews' => $reviews );
		wp_cache_set( $cache_key, $result, 'bri_review', 3600 );

		return $result;
	}

	/**
	 * Flush the review query cache.
	 *
	 * @param int $post_id Post ID.
	 */
	public function flush_review_cache( $post_id ) {
		if ( self::POST_TYPE === get_post_type( $post_id ) ) {
			update_option( 'bri_cache_bust', (int) get_option( 'bri_cache_bust', 1 ) + 1 );
		}
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
			$html .= '<span class="bri-star' . ( $i <= $full ? ' is-filled' : '' ) . '">&#9733;</span>';
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
	 * Get the Trustpilot evaluate URL from business domain.
	 *
	 * @param array $settings Plugin settings.
	 * @return string
	 */
	private function get_evaluate_url( array $settings ) {
		if ( ! empty( $settings['public_url'] ) ) {
			$evaluate = str_replace( '/review/', '/evaluate/', $settings['public_url'] );
			if ( $evaluate !== $settings['public_url'] ) {
				return esc_url_raw( $evaluate );
			}
		}
		if ( ! empty( $settings['business_domain'] ) ) {
			$domain = $this->clean_domain( $settings['business_domain'] );
			if ( $domain ) {
				return 'https://uk.trustpilot.com/evaluate/' . sanitize_text_field( $domain );
			}
		}
		return '';
	}

	/**
	 * Strip protocol and path from a domain string.
	 *
	 * @param string $raw Raw domain or URL.
	 * @return string
	 */
	private function clean_domain( $raw ) {
		$domain = trim( (string) $raw, "/ \t\n\r\0\x0B" );
		$host  = wp_parse_url( $domain, PHP_URL_HOST );
		if ( $host ) {
			return $host;
		}
		return $domain;
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
