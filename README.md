# Maps & Locations

![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)
![MySQL](https://img.shields.io/badge/MySQL-5.6%2B-orange.svg)
![License](https://img.shields.io/badge/License-GPL%20v2-green.svg)

A professional WordPress plugin to create and display interactive maps with custom locations. Create multiple maps, manage locations (projects, stores, offices, restaurants, etc.) from the admin panel, and display them anywhere with a shortcode.

## Features

- **Multiple Maps**: Create unlimited maps, each with its own set of locations
- **Per-map settings**: Override global defaults (style, zoom, legend, links) per map
- **Universal Location System**: Add any type of location with title, description, image, URL and category
- **Category system** with custom colors, sort order, and automatic legend (shared across all maps)
- **Map style selection**: OpenStreetMap, CartoDB Positron, CartoDB Voyager, ESRI Gray Canvas
- **Clustering**: shows location count per region when zoomed out, individual markers when zoomed in
- **Flexible shortcode** with per-instance attribute overrides
- **Media uploader** for location images (JPG, PNG, SVG)
- **Mobile responsive** with optimized popup sizing and touch-friendly controls
- **Translatable** (English and Spanish)

## Installation

1. Upload the `projects-map` folder to `/wp-content/plugins/`.
2. Activate the plugin from the WordPress Plugins menu.
3. Go to **Maps & Locations** in the admin sidebar.
4. Create a map, add categories and locations.
5. Use `[projects_map map="your-map-slug"]` in any page or post.

## Settings Hierarchy

Settings are resolved in this order — first match wins:

| Priority | Level | Where to configure |
|---|---|---|
| 1 (highest) | Shortcode attribute | In the shortcode itself |
| 2 | Per-map setting | Map edit form → *Per-Map Settings* section |
| 3 (lowest) | Global setting | *Maps & Locations → Settings* |

This means you can set sensible global defaults, override specific maps that need different behaviour, and override a single instance of a map with a shortcode attribute — all without touching the others.

> **Note:** `height` and `mobile_height` are shortcode + global only. They depend on where the map is placed in a layout, not on which map it is.

## Usage

### Creating and Configuring Maps

1. Go to **Maps & Locations → All Maps** and click **Add New Map**.
2. Fill in the title, slug (auto-generated), and optional description.
3. Under **Per-Map Settings**, override any global default for this map:
   - **Open links in new tab**
   - **Show legend**
   - **Legend background color**
   - **Map style**
   - **Zoom levels** (min, max, default)
4. Leave any field as "Global default" to inherit from Settings.

### Managing Categories

1. Go to **Maps & Locations → Categories**.
2. Create categories with a title, color (visual picker), and sort order.
3. Categories are shared across all maps and determine marker colors.
4. The legend shows only categories that have at least one location on the displayed map, with the location count in parentheses (e.g., *Retail (12)*).

### Adding Locations

1. Go to **Maps & Locations → All Locations** and click **Add New Location**.
2. Required fields: map, category, country, latitude, longitude, title, description.
3. Optional: image (JPG/PNG/SVG via media uploader or URL), URL, custom link text.

### Global Settings

Go to **Maps & Locations → Settings** to configure global defaults, organised in three sections:

**Map Display**
- **Map Height** — Desktop height. Accepts `px`, `%`, `vh`, or `fit` to fill the parent container. Default: `500px`. See [Fit Container Mode](#fit-container-mode).
- **Map Height (Mobile/Tablet)** — Applied below 957px. Default: `350px`
- **Open links in new tab** — Default link behaviour
- **Map Style** — Tile layer for all maps

**Legend**
- **Show legend below map** — Global legend visibility
- **Legend background color** — Accepts any CSS color (`#ffffff`, `rgba(255,255,255,0.9)`, etc.). Leave empty for transparent.

**Zoom Levels**
- **Minimum / Maximum Zoom** — Allowed zoom range (1–18)
- **Default Zoom** — Initial zoom on load (clamped to min/max)

### Displaying Maps

Insert a shortcode in any page or post:

```
[projects_map map="your-map-slug"]
```

You can use either the map **slug** or numeric **ID** as the `map` value.

#### All Shortcode Attributes

| Attribute | Description | Example |
|---|---|---|
| `map` | Map slug or ID | `map="our-stores"` |
| `height` | Desktop map height (`px`, `%`, `vh`, or `fit`) | `height="600px"` or `height="fit"` |
| `mobile_height` | Height on screens ≤ 957px | `mobile_height="320px"` |
| `new_tab` | Open links in new tab (0/1) | `new_tab="1"` |
| `show_legend` | Show category legend (0/1) | `show_legend="0"` |
| `legend_bg` | Legend background color | `legend_bg="#ffffff"` |
| `map_style` | Tile style: `openstreetmap`, `cartodb-positron`, `cartodb-voyager`, `esri-gray` | `map_style="cartodb-positron"` |
| `min_zoom` | Minimum zoom level (1–18) | `min_zoom="2"` |
| `max_zoom` | Maximum zoom level (1–18) | `max_zoom="12"` |
| `default_zoom` | Initial zoom on load (1–18) | `default_zoom="4"` |
| `debug` | Log debug info to console (0/1) | `debug="1"` |

Shortcode attributes always take precedence over per-map settings and global settings.

**Examples:**

```
[projects_map map="1"]
[projects_map map="our-stores"]
[projects_map map="1" height="600px" new_tab="1"]
[projects_map map="1" height="600px" mobile_height="340px"]
[projects_map map="project-locations" show_legend="0"]
[projects_map map="city-guide" map_style="cartodb-positron" min_zoom="10" max_zoom="16" default_zoom="12"]
[projects_map map="1" legend_bg="rgba(255,255,255,0.9)" show_legend="1"]
[projects_map map="1" height="fit" mobile_height="400px"]
```

### Fit Container Mode

Use `height="fit"` to make the map fill the full height of its parent container, with the legend displayed below the map within that same space.

The plugin wraps the map and legend in a `flex-column` container set to `height: 100%`. The map expands to fill all available space; the legend takes its natural height at the bottom.

**Requirements:**
- The parent element must have a defined height (set via your theme, page builder, or custom CSS). If the parent has no height, `height: 100%` has nothing to fill.
- Use `mobile_height` to set a fixed fallback height on screens ≤ 957px (e.g. `mobile_height="400px"`), since parent containers often don't have a fixed height on mobile.

> **Note:** `height="100%"` is *not* equivalent — it makes the map fill the parent but pushes the legend out of view. Use `fit` instead.

### Mobile Optimisation

- Popups automatically adjust to screen width to prevent overflow
- Touch-friendly close button (larger, with background) on screens ≤ 767px
- Auto-panning keeps popups visible when opened
- SVG images (logos/icons) are displayed at a consistent fixed height

### Debug Mode

Add `debug="1"` to the shortcode to log detailed information to the browser console:

- jQuery and Leaflet loading status
- Map container detection
- Location data loaded via AJAX
- Marker creation per location
- JavaScript errors

## License

GPL v2 or later.
