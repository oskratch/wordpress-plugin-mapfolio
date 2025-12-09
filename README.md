# Projects Map

![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)
![MySQL](https://img.shields.io/badge/MySQL-5.6%2B-orange.svg)
![License](https://img.shields.io/badge/License-GPL%20v2-green.svg)

A WordPress plugin to display a world map with project locations. Allows creating project entries from the admin panel with fields: country, title, brief description, image, and URL. Displays a map using a shortcode, with clustering for countries and individual points on zoom.

## Features

- Admin menu to manage project entries
- Custom database table for projects
- Shortcode to display the interactive map
- Clustering: shows project count per country when zoomed out, individual points when zoomed in
- Hover tooltips with project details and "see more" link
- Translatable (English and Spanish support)

## Installation

1. Upload the `projects-map` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Use the shortcode `[projects_map]` to display the map on any page or post.

## Usage

### Adding Projects

1. Go to the WordPress admin dashboard.
2. Navigate to "Projects Map" > "Add New Project".
3. Fill in the fields: Country, Title, Description, Image URL, and Project URL.
4. Save the project.

### Displaying the Map

Insert the shortcode `[projects_map]` into any page or post content.

## License

This plugin is licensed under the GPL v2 or later.

## Support

For support and questions, please contact the development team.