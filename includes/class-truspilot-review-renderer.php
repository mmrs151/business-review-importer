<?php
/**
 * Renderer class for displaying reviews as carousel, grid, or wall.
 *
 * @package TruspilotReview
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles shortcode, block, and frontend rendering of review cards.
 */
final class Truspilot_Review_Renderer {

	const POST_TYPE     = 'truspilot_review';
	const MAX_REVIEWS   = 48;
	const DEFAULT_COUNT = 0;

	/**
	 * Plugin instance for accessing shared helpers.
	 *
	 * @var Truspilot_Review_Plugin
	 */
	private $plugin;

	/**
	 * Constructor.
	 *
	 * @param Truspilot_Review_Plugin $plugin Main plugin instance.
	 */
	public function __construct( Truspilot_Review_Plugin $plugin ) {
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
				'title'            => $default_title ?: __( 'Parent Reviews', 'truspilot-review' ),
				'min_rating'       => 1,
				'featured_first'   => 'true',
				'grid_rows'        => 3,
				'grid_columns'     => 3,
				'wall_style'       => 'standard',
				'carousel_visible' => 1,
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
		$default_title = $this->plugin->get_settings()['default_title'];

		return $this->render_reviews(
			array(
				'count'            => isset( $attributes['count'] ) ? $attributes['count'] : self::DEFAULT_COUNT,
				'layout'           => isset( $attributes['layout'] ) ? $attributes['layout'] : 'carousel',
				'autoplay'         => ! empty( $attributes['autoplay'] ) ? 'true' : 'false',
				'interval'         => isset( $attributes['interval'] ) ? $attributes['interval'] : 5500,
				'full_page'        => ! empty( $attributes['fullPage'] ) ? 'true' : 'false',
				'title'            => isset( $attributes['title'] ) ? $attributes['title'] : ( $default_title ?: __( 'Parent Reviews', 'truspilot-review' ) ),
				'min_rating'       => isset( $attributes['minRating'] ) ? $attributes['minRating'] : 1,
				'featured_first'   => ! empty( $attributes['featuredFirst'] ) ? 'true' : 'false',
				'grid_rows'        => isset( $attributes['gridRows'] ) ? $attributes['gridRows'] : 3,
				'grid_columns'     => isset( $attributes['gridColumns'] ) ? $attributes['gridColumns'] : 3,
				'wall_style'       => isset( $attributes['wallStyle'] ) ? $attributes['wallStyle'] : 'standard',
				'carousel_visible' => isset( $attributes['carouselVisible'] ) ? $attributes['carouselVisible'] : 1,
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
		$settings       = $this->plugin->get_settings();
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
			return current_user_can( 'edit_posts' ) ? '<p class="truspilot-review-notice">' . esc_html__( 'Add or import local reviews to display this block.', 'truspilot-review' ) . '</p>' : '';
		}

		$grid_cells = ( 'grid' === $layout ) ? $grid_rows * $grid_columns : 0;

		wp_enqueue_style( 'truspilot-review-frontend' );
		wp_enqueue_script( 'truspilot-review-frontend' );

		$classes = array(
			'truspilot-review',
			'truspilot-review--' . $layout,
			$full_page ? 'truspilot-review--full' : '',
		);
		if ( 'grid' === $layout ) {
			$classes[] = 'truspilot-review--grid-cols-' . $grid_columns;
		}
		if ( 'wall' === $layout ) {
			$classes[] = 'truspilot-review--wall-' . $wall_style;
		}

		$section_style = '';
		if ( $card_bg ) {
			$section_style .= '--trp-card-bg:' . esc_attr( $card_bg ) . ';';
		}
		if ( 'carousel' === $layout && $carousel_visible > 1 ) {
			$section_style .= '--trp-visible:' . (string) $carousel_visible . ';';
		}

		ob_start();
		?>
		<section
			class="<?php echo esc_attr( trim( implode( ' ', array_filter( $classes ) ) ) ); ?>"
			<?php echo $section_style ? 'style="' . $section_style . '"' : ''; ?>
			data-truspilot-review
			data-layout="<?php echo esc_attr( $layout ); ?>"
			data-autoplay="<?php echo esc_attr( $autoplay ? 'true' : 'false' ); ?>"
			data-interval="<?php echo esc_attr( (string) $interval ); ?>"
			<?php if ( 'carousel' === $layout ) : ?>
			data-visible="<?php echo esc_attr( (string) $carousel_visible ); ?>"
			<?php endif; ?>
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
					<?php if ( $show_empty && 'grid' !== $layout ) : ?>
						<article class="truspilot-review__card truspilot-review__card--empty">
							<div class="truspilot-review__empty">
								<?php if ( $evaluate_url ) : ?>
									<a href="<?php echo esc_url( $evaluate_url ); ?>" rel="nofollow noopener" target="_blank"><?php esc_html_e( "Add yours here", 'truspilot-review' ); ?></a>
								<?php else : ?>
									<p><?php esc_html_e( "Add yours here", 'truspilot-review' ); ?></p>
								<?php endif; ?>
							</div>
						</article>
					<?php elseif ( $show_empty && 'grid' === $layout ) : ?>
						<?php for ( $i = 0; $i < $grid_cells; $i++ ) : ?>
							<article class="truspilot-review__card truspilot-review__card--empty">
								<div class="truspilot-review__empty">
									<?php if ( $evaluate_url ) : ?>
										<a href="<?php echo esc_url( $evaluate_url ); ?>" rel="nofollow noopener" target="_blank"><?php esc_html_e( "Add yours here", 'truspilot-review' ); ?></a>
									<?php else : ?>
										<p><?php esc_html_e( "Add yours here", 'truspilot-review' ); ?></p>
									<?php endif; ?>
								</div>
							</article>
						<?php endfor; ?>
					<?php else : ?>
						<?php foreach ( $reviews as $index => $review ) : ?>
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
						<?php if ( $grid_cells > 0 && $review_count < $grid_cells ) : ?>
							<?php for ( $i = $review_count; $i < $grid_cells; $i++ ) : ?>
								<article class="truspilot-review__card truspilot-review__card--empty">
									<div class="truspilot-review__empty">
										<?php if ( $evaluate_url ) : ?>
											<a href="<?php echo esc_url( $evaluate_url ); ?>" rel="nofollow noopener" target="_blank"><?php esc_html_e( "Add yours here", 'truspilot-review' ); ?></a>
										<?php else : ?>
											<p><?php esc_html_e( "Add yours here", 'truspilot-review' ); ?></p>
										<?php endif; ?>
									</div>
								</article>
							<?php endfor; ?>
						<?php endif; ?>
					<?php endif; ?>
				</div>
			</div>

			<?php if ( 'carousel' === $layout && $review_count > 1 && ! $show_empty ) : ?>
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
	private function get_local_reviews( $count, $min_rating, $featured_first, $all_featured = false ) {
		$cache_bust = (int) get_option( 'truspilot_review_cache_bust', 1 );
		$cache_key  = 'truspilot_r_' . $cache_bust . '_' . md5( serialize( array( $count, $min_rating, $featured_first, $all_featured ) ) );
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
			'posts_per_page' => $all_featured ? -1 : ( 0 === $count ? 1 : $count ),
			'meta_query'     => $meta_query,
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
				'key'   => '_truspilot_featured',
				'value' => '1',
			);
			unset( $args['meta_key'] );
		} elseif ( $featured_first ) {
			$args['meta_key'] = '_truspilot_featured';
		}

		$query   = new WP_Query( $args );
		$reviews = array();

		foreach ( $query->posts as $post ) {
			$meta      = $this->plugin->get_review_meta( $post->ID );
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
	 * Get the Trustpilot evaluate URL from business domain.
	 *
	 * @param array $settings Plugin settings.
	 * @return string
	 */
	private function get_evaluate_url( array $settings ) {
		if ( ! empty( $settings['business_domain'] ) ) {
			return 'https://uk.trustpilot.com/evaluate/' . sanitize_text_field( $settings['business_domain'] );
		}

		return '';
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
