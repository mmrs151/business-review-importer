# Truspilot Review Blocks

Display customer reviews as a responsive carousel, grid, list, or full-page review wall with a shortcode or Gutenberg block.

## Features

**Free** (always available):
- Vertical or horizontal list layout
- Up to 3 reviews
- Manual one-by-one review management

**Pro** (30-day free trial, then $1/month):
- Carousel with auto-rotation and visible count control
- Uniform grid with configurable rows and columns
- Full-page wall layouts (standard scattered or noticeboard post-its)
- Easy Browser Import (paste Trustpilot page source, JSON, or text)
- Unlimited reviews
- Individual review deep-linking to Trustpilot
- Linked summary section and business name to Trustpilot profile
- Card background colour picker
- Rating filter and featured review prioritization
- All current features

## Setup

1. Go to **Truspilot Reviews > Settings & Import**.
2. Save the business profile summary from the public Trustpilot profile.
3. Import reviews or add them manually under **Truspilot Reviews > Add Review**.
4. Place a shortcode or block on any page.

## Easy Browser Import (Pro)

Pro users can paste Trustpilot page source, JSON arrays, or plain review text to bulk import reviews.

## Shortcode Examples

```text
[truspilot_reviews count="3" layout="list"]
[truspilot_reviews count="3" layout="list" orientation="horizontal"]
[truspilot_reviews count="6" layout="carousel" carousel_visible="2"]       (Pro)
[truspilot_reviews count="12" layout="grid" grid_rows="3" grid_columns="4"] (Pro)
[truspilot_reviews count="0" layout="wall" wall_style="noticeboard"]       (Pro)
```

## Shortcode Attributes

- `count`: Number of reviews to show, from `0` to `48`. Free: max 3.
- `layout`: `carousel` (Pro), `grid` (Pro), `list`, `wall` (Pro). Free: `list` only.
- `orientation`: `vertical` or `horizontal` (list layout only).
- `autoplay`: `true` or `false` (Pro).
- `interval`: Carousel rotation interval in ms (Pro).
- `carousel_visible`: Visible reviews at once in carousel, 1–6 (Pro).
- `full_page`: `true` expands to full page width (Pro).
- `title`: Section heading.
- `min_rating`: Minimum review rating, 1–5 (Pro).
- `featured_first`: Show featured reviews first (Pro).
- `grid_rows`: Number of grid rows, 1–6 (Pro).
- `grid_columns`: Number of grid columns, 1–6 (Pro).
- `wall_style`: `standard` or `noticeboard` (Pro).

## Notes

- The plugin renders from local WordPress content — no Trustpilot JS on your pages, no ad-blocker hiding, no third-party dependencies.
- Free users get the list layout with up to 3 reviews. Upgrade for the full feature set.
- Pro includes a 30-day free trial. No payment needed to start.
