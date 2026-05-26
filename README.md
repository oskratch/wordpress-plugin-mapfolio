# Projects Map

![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)
![MySQL](https://img.shields.io/badge/MySQL-5.6%2B-orange.svg)
![License](https://img.shields.io/badge/License-GPL%20v2-green.svg)

A WordPress plugin to display a world map with project locations. Allows creating project entries from the admin panel with fields: country, title, brief description, image, and URL. Displays a map using a shortcode, with clustering for countries and individual points on zoom.

## Features

- **Admin menu** to manage project entries and categories
- **Custom database tables** for projects and categories
- **Category system** with custom colors and sorting
- **Map style selection**: Choose from multiple map styles (OpenStreetMap, CartoDB Positron, CartoDB Voyager, ESRI Gray Canvas)
- **Settings page** for map configuration (height, map style, link behavior, legend visibility, zoom levels)
- **Shortcode** to display the interactive map with customizable attributes
- **Clustering**: shows project count per country when zoomed out, individual points when zoomed in
- **Category-based marker colors** with automatic legend
- **Media uploader** for project images (supports SVG with proper sizing)
- **Interactive popups** with project details and "see more" link
- **Mobile responsive** with optimized popup sizing and touch-friendly close buttons
- **Customizable legend** (can be shown/hidden globally or per-page)
- **Responsive admin forms**
- **Translatable** (English and Spanish support)

## Installation

1. Upload the `projects-map` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Use the shortcode `[projects_map]` to display the map on any page or post.

## Usage

### Managing Categories

1. Go to "Projects Map" > "Categories".
2. View existing categories or click "Add New Category".
3. Set the category title, choose a color, and define the sort order.
4. Save the category.

Categories are used to organize projects and determine marker colors on the map. A legend with all active categories is automatically displayed below the map (can be disabled in settings).

### Adding Projects

1. Go to the WordPress admin dashboard.
2. Navigate to "Projects Map" > "Add New Project".
3. Fill in the fields:
   - **Category**: Select from existing categories (determines marker color)
   - **Country**: Project location country
   - **Latitude & Longitude**: Exact coordinates for the map marker
   - **Title**: Project name
   - **Description**: Brief project description
   - **Image**: Upload or enter URL (supports JPG, PNG, SVG)
   - **Project URL**: Link to project details
   - **Link Text**: Custom text for the link (optional, defaults to "Read more")
4. Save the project.

### Configuring Settings

1. Go to "Projects Map" > "Settings".
2. Configure the following options:
   - **Map Height**: Default height for the map (e.g., 500px, 100%)
   - **Open links in new tab**: Enable to open project links in a new browser tab
   - **Map Style**: Choose between different map styles:
     - **OpenStreetMap** (default, detailed with terrain)
     - **CartoDB Positron** (clean, minimal, no terrain details)
     - **CartoDB Voyager** (clean with subtle colors)
     - **ESRI Gray Canvas** (neutral gray, minimalist)
   - **Show legend below map**: Display or hide the category legend
   - **Maximum Zoom Level**: Limit how far users can zoom in (1-18)
   - **Minimum Zoom Level**: Limit how far users can zoom out (1-18)
   - **Default Zoom Level**: Initial zoom when the map loads (1-18)

### Displaying the Map

Insert the shortcode `[projects_map]` into any page or post content.

#### Shortcode Attributes

You can customize the map with attributes:

- `height`: Map height, e.g., `[projects_map height="400px"]`
- `new_tab`: Open links in new tab (0 or 1), e.g., `[projects_map new_tab="1"]`
- `show_legend`: Display legend below map (0 or 1), e.g., `[projects_map show_legend="0"]`
- `debug`: Enable debug mode (0 or 1), e.g., `[projects_map debug="1"]`

**Examples:**

```
[projects_map]
[projects_map height="600px" new_tab="1"]
[projects_map height="400px" show_legend="0"]
[projects_map height="100%" new_tab="1" show_legend="1" debug="0"]
```

**Note:** Shortcode attributes override global settings for that specific instance of the map.

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