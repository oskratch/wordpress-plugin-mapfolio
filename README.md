# Projects Map

![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)
![MySQL](https://img.shields.io/badge/MySQL-5.6%2B-orange.svg)
![License](https://img.shields.io/badge/License-GPL%20v2-green.svg)

A WordPress plugin to display a world map with project locations. Allows creating project entries from the admin panel with fields: country, title, brief description, image, and URL. Displays a map using a shortcode, with clustering for countries and individual points on zoom.

## Features

- Admin menu to manage project entries
- Custom database table for projects
- Settings page for map configuration (width, height, link behavior)
- Shortcode to display the interactive map with customizable attributes
- Clustering: shows project count per country when zoomed out, individual points when zoomed in
- Custom marker colors for each project
- Media uploader for project images
- Hover tooltips with project details and "see more" link
- Responsive admin forms
- Translatable (English and Spanish support)

## Installation

1. Upload the `projects-map` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Use the shortcode `[projects_map]` to display the map on any page or post.

## Usage

### Adding Projects

1. Go to the WordPress admin dashboard.
2. Navigate to "Projects Map" > "Add New Project".
3. Fill in the fields: Country, Latitude, Longitude, Title, Description, Image (upload or URL), Project URL, and Marker Color.
4. Save the project.

### Configuring Settings

1. Go to "Projects Map" > "Settings".
2. Set Map Height (e.g., 500px).
3. Choose whether links open in new tab.

### Displaying the Map

Insert the shortcode `[projects_map]` into any page or post content.

#### Shortcode Attributes

You can customize the map with attributes:

- `height`: Map height, e.g., `[projects_map height="400px"]`
- `new_tab`: Open links in new tab (0 or 1), e.g., `[projects_map new_tab="1"]`

Example: `[projects_map height="600px" new_tab="1"]`

## License

This plugin is licensed under the GPL v2 or later.

## Support

For support and questions, please contact the development team.