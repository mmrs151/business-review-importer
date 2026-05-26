=== Business Review Importer ===
Contributors: psyntaxlabs
Tags: reviews, testimonials, trustpilot, carousel, grid, wall, shortcode, gutenberg, responsive
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 2026.05.26
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Lightweight, responsive customer reviews displayed as a carousel, grid, list, or wall — via shortcode or Gutenberg block.

== Description ==

Business Review Importer lets you import and display Trustpilot customer reviews on your WordPress site in beautiful, responsive layouts — without embedding Trustpilot's JavaScript, slowing down your pages, or requiring an API key.

**Why use it?** Most review widgets load third-party scripts that hurt page speed and reliability. This plugin stores reviews locally on your server, so they render instantly with zero external requests. Your site stays fast, and your visitors see a seamless, branded review experience.

**How it works:**
1. **Configure** your Trustpilot profile URL in the settings.
2. **Import** reviews from the page source, JSON, or plain text (Pro), or add them one by one.
3. **Display** them anywhere with a shortcode or the Gutenberg block.

== Features ==

**Free** (always available):
* Vertical or horizontal list layout
* Up to 3 reviews displayed
* Add reviews one by one from the WordPress admin
* Shortcode and Gutenberg block support

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

1. Noticeboard Layout
2. Grid/Wall Layout
3. List Layout
4. Carousel Layout
5. Settings Page

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/` or search for "Business Review Importer" in the WordPress plugin directory.
2. Activate the plugin.
3. Go to **Reviews > Settings & Import** and configure your Trustpilot profile details.
4. Import reviews or add them manually, then place the shortcode or block on any page.

== Shortcode ==

`[business_reviews]`

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

`[business_reviews count="3" layout="list"]`
`[business_reviews count="3" layout="list" orientation="horizontal"]`
`[business_reviews count="6" layout="carousel" carousel_visible="2"]` (Pro)
`[business_reviews count="12" layout="grid" grid_rows="3" grid_columns="4"]` (Pro)
`[business_reviews count="0" layout="wall" wall_style="noticeboard"]` (Pro)

== Upgrade ==

The Pro version unlocks carousel, grid, wall layouts, Easy Browser Import, unlimited reviews, card links, and more. Start your **30-day free trial** today with no payment required. After the trial, it's just $1/month. [Learn more about the Pro features](#features).

== Frequently Asked Questions ==

= How do I import reviews? =

Pro users can paste Trustpilot page source, JSON arrays, or plain review text directly in the Easy Browser Import section under **Reviews > Settings & Import**. Free users can add reviews one by one under **Reviews > Add New**.

= Do I need a Trustpilot account? =

No. The plugin stores and displays reviews locally. You need a Trustpilot profile for the reviews to exist, but the plugin does not require any Trustpilot API key, API subscription, or paid plan.

= Do I need a Trustpilot API key? =

No. Reviews are extracted from the public Trustpilot page source (Pro) or entered manually. No API key or Trustpilot Business subscription is required.

= Does this affect page speed? =

No. Reviews are stored locally in your WordPress database and rendered without any external JavaScript requests. Unlike embedded Trustpilot widgets, this plugin adds zero third-party HTTP requests, so your Lighthouse and Core Web Vitals scores are unaffected.

= Can I use it without Trustpilot? =

Yes. You can add reviews manually from the WordPress admin — just fill in the reviewer name, rating, and body text. The plugin works as a standalone testimonial manager even without a Trustpilot profile.

= How are reviews linked to Trustpilot? =

Pro only: each review card links to its individual Trustpilot review page. The summary section and business name also link to your Trustpilot profile.

= Can I customize the colours? =

Pro users can set a card background colour from **Reviews > Settings & Import**.

== Changelog ==

= 2026.05.26 =
* Removed WP_FS__DEV_MODE for production-ready Freemius integration
* Replaced serialize() with wp_json_encode() in cache key generation
* Added wp_unslash() sanitization on review rating input
* Removed trustpilot_reviews shortcode alias
* Cleaned up screenshots and refined readme

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
