# Mapfolio

![WordPress](https://img.shields.io/badge/WordPress-5.5%2B-blue.svg)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)
![License](https://img.shields.io/badge/License-GPL%20v2%2B-green.svg)

Interactive maps for WordPress with your own locations: projects, stores, offices, events… Create as many maps as you need, group locations in color-coded categories and embed them anywhere with a shortcode.

Built on [Leaflet](https://leafletjs.com/) and [OpenStreetMap](https://www.openstreetmap.org/). Leaflet ships inside the plugin, so there's no CDN. No build step, and no external accounts required.

## Features

- **Multiple maps.** Each map has its own locations, and its own shortcode.
- **Easy positioning.** Search for an address or place, click on the map or drag the marker. No need to look up coordinates.
- **Color-coded categories.** Categories are shared by all maps. They set the marker color and appear in an automatic legend below the map, with the number of locations in each one.
- **Clickable legend.** Visitors can show or hide each category on the map by clicking it in the legend.
- **Rich popups.** Title, description, image (SVG logos supported) and a link with custom text.
- **Clustering.** Nearby locations are grouped in numbered circles until you zoom in. The cluster colors are configurable.
- **Four map styles.** OpenStreetMap, CARTO Positron, CARTO Voyager and Esri Gray Canvas.
- **Three levels of settings.** Global defaults, per-map overrides and per-shortcode attributes.
- **Responsive.** Separate height for mobile and tablet, and popups sized to fit small screens.
- **Comfortable admin.** Sortable and searchable location list with filters, pagination and bulk delete.
- **Translated.** English, Spanish and Catalan.

## Requirements

- WordPress 5.5 or later
- PHP 7.4 or later

## Installation

1. Download `mapfolio-x.y.z.zip` from the [latest release](https://github.com/oskratch/wordpress-plugin-mapfolio/releases/latest).
2. In WordPress, go to **Plugins → Add New → Upload Plugin**, choose the zip and click **Install Now**.
3. Activate **Mapfolio**.

Installing from a clone of this repository instead? Copy the `mapfolio` folder inside it, not the repository itself, to `wp-content/plugins/`.

On activation the plugin creates its database tables, a default map and a default category.

## Quick start

1. Go to **Mapfolio → Categories** and create the categories you need (e.g. *Offices*, *Stores*), each with its own color.
2. Go to **Mapfolio → Locations → Add new location**. Write a title, choose a category, and set the position by searching for a place or clicking on the map.
3. Go to **Mapfolio → Maps**, copy the shortcode of the map and paste it into any page or post:

```
[mapfolio map="main-map"]
```

Need several maps, for example one per country or per type of project? Create them in **Mapfolio → Maps** and pick the map when adding each location.

## Shortcode

```
[mapfolio map="main-map"]
```

`map` accepts the map slug or its numeric ID. Without it, the default map is shown.

All the other attributes are optional and override the map and global settings for that shortcode only:

| Attribute | Description | Example |
|---|---|---|
| `map` | Map slug or ID | `map="our-stores"` |
| `height` | Map height (`px`, `%`, `vh`, `em`, `rem`) or `fit` (see below) | `height="600px"` |
| `mobile_height` | Height on screens up to 957px wide | `mobile_height="350px"` |
| `map_style` | `openstreetmap`, `cartodb-positron`, `cartodb-voyager` or `esri-gray` | `map_style="cartodb-positron"` |
| `min_zoom` | Minimum zoom level (1–18) | `min_zoom="2"` |
| `max_zoom` | Maximum zoom level (1–18) | `max_zoom="12"` |
| `default_zoom` | Initial zoom level (1–18) | `default_zoom="4"` |
| `show_legend` | Show the category legend (`1`/`0`) | `show_legend="0"` |
| `legend_bg` | Legend background color | `legend_bg="rgba(255,255,255,0.9)"` |
| `legend_filter` | Let visitors show/hide categories by clicking the legend (`1`/`0`) | `legend_filter="0"` |
| `new_tab` | Open popup links in a new tab (`1`/`0`) | `new_tab="1"` |
| `debug` | Log debugging information to the browser console (`1`/`0`) | `debug="1"` |

Examples:

```
[mapfolio map="our-stores" height="600px" mobile_height="400px"]
[mapfolio map="projects" map_style="cartodb-positron" show_legend="0"]
[mapfolio map="city-guide" min_zoom="10" max_zoom="16" default_zoom="12"]
```

On load, the map zooms to fit all its locations, but never further in than the initial zoom level. This keeps a map with a single location from zooming in to street level.

### Filling the parent element: `height="fit"`

`height="fit"` makes the map fill the height of its parent element, with the legend below it inside that same space. The parent element needs a defined height, set by your theme, page builder or custom CSS. On mobile the `mobile_height` value is used instead, because parent elements rarely have a fixed height there.

`height="100%"` is not the same thing. The map fills the parent, but the legend is pushed out of view.

## Settings

Each value is resolved in this order, and the first one set wins:

| Priority | Level | Where |
|---|---|---|
| 1 | Shortcode attribute | The shortcode itself |
| 2 | Map settings | **Mapfolio → Maps → Edit**, under *Map settings* |
| 3 | Global settings | **Mapfolio → Settings** |

The height is the exception: it can only be set in the shortcode or the global settings, because it depends on where the map is placed rather than on which map it is.

**Global settings** (**Mapfolio → Settings**):

- **Map:** height, height on mobile and tablet, map style, CARTO API key, and whether to open links in a new tab.
- **Zoom:** minimum, maximum and initial zoom level.
- **Legend:** whether to show it, whether it can be clicked to filter the map, and its background color.
- **Clusters:** color for groups of 2–9 locations and for groups of 10 or more.

**Map settings** can override the map style, the zoom levels, the legend (visibility, filtering and background) and the link target.

### CARTO API key

Since August 2026, CARTO requires an API key for its basemaps. Without one, the *CARTO Positron* and *CARTO Voyager* styles show an "API KEY REQUIRED" watermark. You can get a free key at [carto.com/basemaps/apikey](https://carto.com/basemaps/apikey/) and paste it in **Mapfolio → Settings**, or use one of the other styles instead.

## Privacy

Mapfolio doesn't use cookies or tracking, and all its scripts and styles (Leaflet included) are served from your own site. Two features do contact third-party services, so consider mentioning them in your privacy policy:

- **Map tiles** (the map images) are loaded from OpenStreetMap, CARTO or Esri, depending on the map style. These providers receive the visitor's IP address, as with any externally hosted image.
- **Place search in the admin** sends the text you type to [OpenStreetMap Nominatim](https://nominatim.org/) when you click *Search*. This only happens in the admin, never on the public site.

## Translations

Mapfolio ships with English, Spanish (`es_ES`) and Catalan (`ca`). To add a language, translate `mapfolio/languages/mapfolio.pot` with a tool such as [Poedit](https://poedit.net/). Save the result as `mapfolio-{locale}.po`/`.mo` in the same folder. Pull requests with new translations are welcome.

## Hooks

- `mapfolio_capability` (filter): the capability required to manage maps, locations and categories. The default is `edit_posts`. The global settings always require `manage_options`.

```php
add_filter('mapfolio_capability', function () {
    return 'manage_options';
});
```

## Data

Mapfolio stores its data in three tables: `{prefix}mapfolio_maps`, `{prefix}mapfolio_locations` and `{prefix}mapfolio_categories`. Global settings are WordPress options prefixed with `mapfolio_`. Deactivating or deleting the plugin does not remove any data.

The default map and the default category can't be deleted. When you delete another map or category, its locations are moved to the default one rather than lost.

## Roadmap

**1.1**

- Import and export of locations (CSV)

**Later**

- Search box on the frontend map
- Restricting the visible area of a map
- Several categories per location

Suggestions and bug reports are welcome in [Issues](https://github.com/oskratch/wordpress-plugin-mapfolio/issues).

## Changelog

### 1.0.0

First public release.

## Releasing

1. Update the version in `mapfolio/mapfolio.php` (the `Version` header and `MAPFOLIO_VERSION`) and add an entry to the changelog.
2. Commit, then create and push an annotated tag with the same version:

   ```
   git tag -a v1.0.1 -m "Mapfolio 1.0.1"
   git push origin v1.0.1
   ```

3. On GitHub, draft a new release from that tag and publish it.

When the release is published, a GitHub Action checks that the tag matches the plugin version, builds `mapfolio-1.0.1.zip` with only the plugin folder, and attaches it to the release.

## Credits

- [Leaflet](https://leafletjs.com/) 1.9.4 (BSD 2-Clause) and [Leaflet.markercluster](https://github.com/Leaflet/Leaflet.markercluster) 1.5.3 (MIT). They are bundled in `mapfolio/assets/vendor/` with their licenses.
- Map data © [OpenStreetMap](https://www.openstreetmap.org/copyright) contributors. Tiles by OpenStreetMap, [CARTO](https://carto.com/attributions) and Esri.
- Place search by [Nominatim](https://nominatim.org/)

## License

[GPL v2 or later](LICENSE).
