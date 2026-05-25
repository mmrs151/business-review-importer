=== Truspilot Review Blocks ===
Contributors: psyntaxlabs
Tags: reviews, testimonials, trustpilot, carousel, grid, wall, social proof
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 2026.05.25
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Display responsive customer reviews as a carousel, grid, list, or review wall using a shortcode or Gutenberg block.

== Description ==

Truspilot Review Blocks lets you import and display customer reviews on your WordPress site in beautiful, responsive layouts — without embedding Trustpilot's JavaScript or slowing down your pages.

== Features ==

**Free** (always available):
* Vertical or horizontal list layout
* Up to 3 reviews displayed
* Add reviews one by one from the WordPress admin

**Pro** (30-day free trial, then $1/month):
* Carousel with auto-rotation and multi-column view
* Uniform grid with configurable rows and columns
* Full-page wall layouts (standard scattered or noticeboard post-its)
* Horizontal list with scroll snapping
* Easy Browser Import — paste Trustpilot page source, JSON, or plain text
* Unlimited reviews
* Individual review deep-linking to Trustpilot
* Linked summary section and business name to Trustpilot profile
* Card background colour picker
* Rating filter and featured review prioritization

== Screenshots ==

1. Carousel layout with 3 visible cards, arrows, and dot navigation.
2. Grid layout with uniform square cards.
3. Wall noticeboard layout with post-it style tiles.
4. Settings & Import page.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/` or search for "Truspilot Review Blocks" in the WordPress plugin directory.
2. Activate the plugin.
3. Go to **Truspilot Reviews > Settings & Import** and configure your Trustpilot profile details.
4. Import reviews or add them manually, then place the shortcode or block on any page.

== Shortcode ==

`[truspilot_reviews]`

Use the `[truspilot_reviews]` shortcode to display reviews on any page or post.

=== Shortcode Attributes ===

* `count` — Number of reviews (0 = all featured, max 48). Free: max 3.
* `layout` — `carousel` (Pro), `grid` (Pro), `list`, `wall` (Pro). Free: only `list`.
* `orientation` — `vertical` (default) or `horizontal`. For list layout only.
* `autoplay` — `true` (Pro) or `false`.
* `interval` — Carousel rotation in ms (Pro).
* `carousel_visible` — Number of reviews visible at once in carousel (Pro).
* `full_page` — `true` expands to full page width (Pro).
* `title` — Section heading.
* `min_rating` — Minimum star rating to show (Pro).
* `featured_first` — Show featured first (Pro).
* `grid_rows` — Grid rows, 1–6 (Pro).
* `grid_columns` — Grid columns, 1–6 (Pro).
* `wall_style` — `standard` or `noticeboard` (Pro).

=== Examples ===

`[truspilot_reviews count="3" layout="list"]`
`[truspilot_reviews count="3" layout="list" orientation="horizontal"]`
`[truspilot_reviews count="6" layout="carousel" carousel_visible="2"]` (Pro)
`[truspilot_reviews count="12" layout="grid" grid_rows="3" grid_columns="4"]` (Pro)
`[truspilot_reviews count="0" layout="wall" wall_style="noticeboard"]` (Pro)

== Upgrade ==

The Pro version unlocks carousel, grid, wall layouts, Easy Browser Import, unlimited reviews, card links, and more. Start your 30-day free trial today.

== Frequently Asked Questions ==

= How do I import reviews? =

Pro users can paste Trustpilot page source, JSON arrays, or plain review text directly in the Easy Browser Import section. Free users can add reviews one by one under Truspilot Reviews > Add Review.

= Do I need a Trustpilot account? =

No. The plugin stores and displays reviews locally. You need a Trustpilot profile for the reviews to exist, but the plugin does not require any Trustpilot API key or paid plan.

= How are reviews linked to Trustpilot? =

Pro: each review card links to its individual Trustpilot review page. The summary section and business name link to your Trustpilot profile.

= Can I customize the colours? =

Pro users can set a card background colour from the plugin settings.

== Changelog ==

= 2026.05.25 =
* Carousel visible count option
* Evaluate URL derived from Trustpilot profile URL
* Individual review URL extraction and clickable cards
* Summary section and business name linked to Trustpilot profile
* Freemius licensing with 30-day free trial
* Free tier: list layout with vertical/horizontal orientation, max 3 reviews
* Easy Browser Import gated to Pro
* Various CSS fixes and improvements

= 2026.05.22 =
* List/wall layout split and count=0 logic
* Grid rows/columns and wall_style attribute
* Card background colour setting
* Dynamic default title
* Shortcode Help admin page

= 2026.05.21 =
* Initial public release
