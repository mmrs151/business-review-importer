# Truspilot Review Blocks

A lightweight WordPress plugin for displaying locally saved Trustpilot-style reviews as a responsive carousel, grid, or full-page review wall.

## Features

- Local `Truspilot Review` custom post type for safe review storage.
- Business profile settings for TrustScore, rating label, profile URL, and review count.
- Easy browser import for pasted Trustpilot page source, copied public-profile review text, or JSON arrays.
- Optional public-profile scraper that paginates Trustpilot pages and saves discovered reviews locally.
- Shortcode aliases: `[truspilot_reviews]` and `[trustpilot_reviews]`.
- Dynamic Gutenberg block with the same display controls.
- Auto-rotating carousel with pause on hover/focus and reduced-motion support.
- Responsive grid and full-page wall layouts for desktop and mobile.
- Rating filter and featured-review prioritization.
- Security-first handling: capability checks, nonces, Settings API, post meta sanitization, escaped output, and no frontend remote fetching.

## Setup

1. Go to **Truspilot Reviews > Settings & Import**.
2. Save the business profile summary from the public Trustpilot profile.
3. Use **Easy Browser Import**, **Automatic Public Scrape**, or add reviews manually under **Truspilot Reviews > Add Review**.
4. Place a shortcode or block on any page.

## Easy Browser Import

When server scraping is blocked, use your normal browser instead:

1. Open the Trustpilot profile page in your browser.
2. Choose **View Page Source**.
3. Select all and copy the source.
4. Paste it into **Truspilot Reviews > Settings & Import > Easy Browser Import**.
5. Click **Extract & Import Reviews**.

The plugin extracts reviews from `__NEXT_DATA__` or JSON-LD and stores them locally. You can repeat this for additional Trustpilot pages if needed.

## Automatic Scrape

The scraper follows the same public-page approach used by open-source projects such as `irfanalidv/trustpilot_scraper`: it requests `?page=1`, `?page=2`, and so on, reads Trustpilot's public `__NEXT_DATA__` or JSON-LD payload, normalizes reviews, deduplicates them, and saves them as local WordPress reviews.

This is intentionally an admin-only manual action. Trustpilot may still return browser verification to some servers, in which case the plugin shows a clear admin error and leaves the frontend untouched.

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
[truspilot_reviews count="5" layout="carousel" autoplay="true" interval="6000"]
[truspilot_reviews count="12" layout="grid" min_rating="4" featured_first="true"]
[truspilot_reviews count="24" layout="wall" full_page="true"]
```

## Shortcode Attributes

- `count`: Number of reviews to show, from `1` to `48`.
- `layout`: `carousel`, `grid`, or `wall`.
- `autoplay`: `true` or `false`.
- `interval`: Carousel rotation interval in milliseconds, from `2500` to `20000`.
- `full_page`: `true` expands the review section to full page width and height.
- `title`: Section heading.
- `min_rating`: Minimum review rating, from `1` to `5`.
- `featured_first`: `true` shows featured reviews before standard reviews.

## Notes

The plugin intentionally renders from local WordPress content rather than scraping Trustpilot on the frontend. That keeps pages fast, avoids third-party proxy dependencies, and prevents visitor-facing failures when Trustpilot challenge-gates server-side requests.
