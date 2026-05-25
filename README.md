# Truspilot Review Blocks

A lightweight WordPress plugin for displaying locally saved Trustpilot-style reviews as a responsive carousel, grid, full-page review wall, or scrollable list.

## Features

- Local `Truspilot Review` custom post type for safe review storage.
- Business profile settings for TrustScore, rating label, profile URL, and review count.
- Easy browser import for pasted Trustpilot page source, copied public-profile review text, or JSON arrays.
- Shortcode aliases: `[truspilot_reviews]` and `[trustpilot_reviews]`.
- Dynamic Gutenberg block with the same display controls.
- Auto-rotating carousel with visible-count control, pause on hover/focus, and reduced-motion support.
- Multiple layouts: carousel, grid with row/column control, full-page wall (standard or noticeboard), and scrollable list.
- Rating filter, featured-review prioritization, and card background colour setting.
- Individual review deep-linking to Trustpilot and linked summary / business name.
- Security-first handling: capability checks, nonces, Settings API, post meta sanitization, escaped output, and no frontend remote fetching.

## Setup

1. Go to **Truspilot Reviews > Settings & Import**.
2. Save the business profile summary from the public Trustpilot profile.
3. Use **Easy Browser Import** or add reviews manually under **Truspilot Reviews > Add Review**.
4. Place a shortcode or block on any page.

## Easy Browser Import

When server scraping is blocked, use your normal browser instead:

1. Open the Trustpilot profile page in your browser.
2. Choose **View Page Source**.
3. Select all and copy the source.
4. Paste it into **Truspilot Reviews > Settings & Import > Easy Browser Import**.
5. Click **Extract & Import Reviews**.

The plugin extracts reviews from `__NEXT_DATA__` or JSON-LD and stores them locally. You can repeat this for additional Trustpilot pages if needed.

## Import Format

The importer also accepts plain text with one review per blank-line-separated block:

```text
5 stars
Excellent service
The team was fast, friendly, and helpful from start to finish.
Jane Smith
12 May 2026
```

JSON arrays are also supported:

```json
[
  {
    "title": "Excellent service",
    "body": "The team was fast, friendly, and helpful.",
    "author": "Jane Smith",
    "rating": 5,
    "date": "2026-05-12",
    "featured": true
  }
]
```

## Shortcode Examples

```text
[truspilot_reviews]
[truspilot_reviews count="5" layout="carousel" autoplay="true" interval="6000" carousel_visible="2"]
[truspilot_reviews count="12" layout="grid" min_rating="4" featured_first="true" grid_rows="3" grid_columns="4"]
[truspilot_reviews count="24" layout="list" full_page="true"]
[truspilot_reviews count="0" layout="wall" wall_style="noticeboard"]
```

## Shortcode Attributes

- `count`: Number of reviews to show, from `0` to `48`. `0` shows all featured reviews.
- `layout`: `carousel`, `grid`, `list`, or `wall`.
- `autoplay`: `true` or `false`.
- `interval`: Carousel rotation interval in milliseconds, from `2500` to `20000`.
- `carousel_visible`: Number of reviews visible at once in the carousel, from `1` to `6`.
- `full_page`: `true` expands the review section to full page width and height.
- `title`: Section heading.
- `min_rating`: Minimum review rating, from `1` to `5`.
- `featured_first`: `true` shows featured reviews before standard reviews.
- `grid_rows`: Number of grid rows, from `1` to `6`.
- `grid_columns`: Number of grid columns, from `1` to `6`.
- `wall_style`: `standard` or `noticeboard` (post-it note style).

## Notes

- Imported reviews are automatically marked as featured and given a link to their Trustpilot individual review page.
- The evaluate ("Add yours here") URL is derived from your Trustpilot profile URL.
- The summary section and business name link to your Trustpilot profile.
- The plugin intentionally renders from local WordPress content rather than scraping Trustpilot on the frontend. That keeps pages fast, avoids third-party proxy dependencies, and prevents visitor-facing failures when Trustpilot challenge-gates server-side requests.
