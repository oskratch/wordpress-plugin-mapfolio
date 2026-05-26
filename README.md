# Projects Map

![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)
![MySQL](https://img.shields.io/badge/MySQL-5.6%2B-orange.svg)
![License](https://img.shields.io/badge/License-GPL%20v2-green.svg)

A professional WordPress plugin to create and display interactive maps with custom locations. Create multiple maps and manage locations (projects, stores, offices, restaurants, etc.) from the admin panel. Perfect for businesses with multiple locations, project portfolios, store locators, and more.

## Features

- **Multiple Maps**: Create unlimited maps, each with its own set of locations
- **Universal Location System**: Add any type of location - projects, stores, offices, restaurants, etc.
- **Admin menu** to manage maps, locations, and categories
- **Custom database tables** for maps, locations, and categories
- **Category system** with custom colors and sorting (shared across all maps)
- **Map style selection**: Choose from multiple map styles (OpenStreetMap, CartoDB Positron, CartoDB Voyager, ESRI Gray Canvas)
- **Settings page** for global map configuration (height, map style, link behavior, legend visibility, zoom levels)
- **Flexible shortcode** to display specific maps with customizable attributes
- **Clustering**: shows location count per country when zoomed out, individual points when zoomed in
- **Category-based marker colors** with automatic legend
- **Media uploader** for location images (supports JPG, PNG, SVG with proper sizing)
- **Interactive popups** with location details and "see more" link
- **Mobile responsive** with optimized popup sizing and touch-friendly close buttons
- **Customizable legend** (can be shown/hidden globally or per-page)
- **Responsive admin forms**
- **Translatable** (English and Spanish support)

## Installation

1. Upload the `projects-map` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Create your first map and add locations.
4. Use the shortcode `[projects_map map="1"]` to display your map on any page or post.

## Usage

### Creating Maps

1. Go to "Projects Map" > "All Maps".
2. Click "Add New Map".
3. Enter:
   - **Title**: Name of your map (e.g., "Our Stores", "Project Locations")
   - **Slug**: Auto-generated URL-friendly identifier
   - **Description**: Optional description of what this map shows
4. Save the map.

You can create multiple maps for different purposes (e.g., one map for stores, another for projects).

### Managing Categories

1. Go to "Projects Map" > "Categories".
2. View existing categories or click "Add New Category".
3. Set the category title, choose a color, and define the sort order.
4. Save the category.

Categories are shared across all maps and used to organize locations and determine marker colors. A legend with all active categories is automatically displayed below the map (can be disabled in settings).

### Adding Locations

1. Go to "Projects Map" > "All Locations".
2. Click "Add New Location".
3. Fill in the fields:
   - **Map**: Select which map this location belongs to
   - **Category**: Select from existing categories (determines marker color)
   - **Country**: Location country
   - **Latitude & Longitude**: Exact coordinates for the map marker
   - **Title**: Location name
   - **Description**: Brief description
   - **Image**: Upload or enter URL (supports JPG, PNG, SVG)
   - **URL**: Link to more details
   - **Link Text**: Custom text for the link (optional, defaults to "Read more")
4. Save the location.

### Configuring Settings

1. Go to "Projects Map" > "Settings".
2. Configure the following global options:
   - **Map Height**: Default height for all maps (e.g., 500px, 100%)
   - **Open links in new tab**: Enable to open location links in a new browser tab
   - **Map Style**: Choose between different map styles:
     - **OpenStreetMap** (default, detailed with terrain)
     - **CartoDB Positron** (clean, minimal, no terrain details) ✨ Recommended for clean look
     - **CartoDB Voyager** (clean with subtle colors)
     - **ESRI Gray Canvas** (neutral gray, minimalist)
   - **Show legend below map**: Display or hide the category legend
   - **Maximum Zoom Level**: Limit how far users can zoom in (1-18)
   - **Minimum Zoom Level**: Limit how far users can zoom out (1-18)
   - **Default Zoom Level**: Initial zoom when the map loads (1-18)

### Displaying Maps

Insert the shortcode into any page or post content. You must specify which map to display.

#### Shortcode Attributes

You can customize the map with attributes:

- `map`: **Required** - Map ID or slug to display, e.g., `[projects_map map="1"]` or `[projects_map map="our-stores"]`
- `height`: Map height, e.g., `[projects_map map="1" height="400px"]`
- `new_tab`: Open links in new tab (0 or 1), e.g., `[projects_map map="1" new_tab="1"]`
- `show_legend`: Display legend below map (0 or 1), e.g., `[projects_map map="1" show_legend="0"]`
- `debug`: Enable debug mode (0 or 1), e.g., `[projects_map map="1" debug="1"]`

**Examples:**

```
[projects_map map="1"]
[projects_map map="our-stores"]
[projects_map map="1" height="600px" new_tab="1"]
[projects_map map="project-locations" height="400px" show_legend="0"]
[projects_map map="1" height="100%" new_tab="1" show_legend="1"]
```

**Note:** 
- If no `map` parameter is specified, the first map in your database will be displayed.
- Shortcode attributes override global settings for that specific instance of the map.
- You can use either the map ID (number) or slug (text) as the `map` parameter value.

### Mobile Optimization

The plugin includes responsive design optimizations for mobile devices:

- **Adaptive popup sizing**: Popups automatically adjust to screen width to prevent overflow
- **Touch-friendly close buttons**: Larger, more visible close button on mobile devices
- **Optimized padding**: Reduced margins and padding for better mobile viewing
- **SVG image scaling**: Logo and icon images properly scaled for mobile screens
- **Auto-panning**: Map automatically adjusts to keep popups visible when opened

### Debug Mode

When debug mode is enabled (`debug="1"`), the plugin outputs detailed information to the browser console to help troubleshoot issues:

- jQuery loading status
- Map container existence
- Project data and count
- Marker creation process
- JavaScript errors

This is useful for diagnosing problems with map display or marker rendering.

## License

This plugin is licensed under the GPL v2 or later.

## Support

For support and questions, please contact the development team.