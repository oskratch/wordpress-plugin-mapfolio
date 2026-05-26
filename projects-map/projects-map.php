<?php
/**
 * Plugin Name: Projects Map
 * Description: A WordPress plugin to display a world map with project locations. Manage projects from admin and display via shortcode.
 * Version: 1.0.3
 * Author: Òscar Periche
 * License: GPL v2 or later
 * Text Domain: projects-map
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define constants
define('PROJECTS_MAP_VERSION', '1.0.5');
define('PROJECTS_MAP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PROJECTS_MAP_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include files
require_once PROJECTS_MAP_PLUGIN_DIR . 'includes/class-projects-map-db.php';
require_once PROJECTS_MAP_PLUGIN_DIR . 'includes/class-projects-map-admin.php';
require_once PROJECTS_MAP_PLUGIN_DIR . 'includes/class-projects-map-shortcode.php';

// Activation hook
register_activation_hook(__FILE__, 'projects_map_activate');
function projects_map_activate() {
    Projects_Map_DB::create_table();
    update_option('projects_map_version', PROJECTS_MAP_VERSION);
}

// Upgrade hook
add_action('plugins_loaded', 'projects_map_upgrade_check');
function projects_map_upgrade_check() {
    $current_version = get_option('projects_map_version', '1.0.0');
    if (version_compare($current_version, PROJECTS_MAP_VERSION, '<')) {
        Projects_Map_DB::create_table();
        // Add sort_order column to categories if not exists
        projects_map_add_sort_order_column();
        // Add link_text column to projects if not exists
        projects_map_add_link_text_column();
        // Migrate existing color data to categories
        projects_map_migrate_colors_to_categories();
        update_option('projects_map_version', PROJECTS_MAP_VERSION);
    }
}

function projects_map_migrate_colors_to_categories() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'projects_map';
    $categories_table = $wpdb->prefix . 'projects_map_categories';

    // Check if color column exists
    $column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$table_name} LIKE 'color'");
    if (empty($column_exists)) {
        return; // Already migrated
    }

    // Get projects with old color column
    $projects = $wpdb->get_results("SELECT id, color FROM $table_name WHERE color IS NOT NULL AND color != ''", ARRAY_A);

    foreach ($projects as $project) {
        // Find or create category by color
        $category = $wpdb->get_row($wpdb->prepare("SELECT id FROM $categories_table WHERE color = %s", $project['color']), ARRAY_A);
        if (!$category) {
            // Create new category
            $wpdb->insert($categories_table, array(
                'title' => 'Migrated - ' . $project['color'],
                'color' => $project['color'],
                'sort_order' => 0
            ));
            $category_id = $wpdb->insert_id;
        } else {
            $category_id = $category['id'];
        }
        // Update project
        $wpdb->update($table_name, array('category_id' => $category_id), array('id' => $project['id']));
    }

    // Drop old color column
    $wpdb->query("ALTER TABLE {$table_name} DROP COLUMN color");
}

function projects_map_add_sort_order_column() {
    global $wpdb;
    $categories_table = $wpdb->prefix . 'projects_map_categories';
    $column_exists = $wpdb->get_results("SHOW COLUMNS FROM {$categories_table} LIKE 'sort_order'");
    if (empty($column_exists)) {
        $wpdb->query("ALTER TABLE {$categories_table} ADD sort_order int(11) DEFAULT 0 NOT NULL");
    }
}

function projects_map_add_link_text_column() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'projects_map';
    $column_exists = $wpdb->get_var("SHOW COLUMNS FROM {$table_name} WHERE Field = 'link_text'");
    if (!$column_exists) {
        $wpdb->query("ALTER TABLE {$table_name} ADD COLUMN link_text varchar(255) DEFAULT '' NOT NULL");
    }
}

// Deactivation hook
register_deactivation_hook(__FILE__, 'projects_map_deactivate');
function projects_map_deactivate() {
    // Cleanup if needed
}

// Load textdomain
add_action('plugins_loaded', 'projects_map_load_textdomain');
function projects_map_load_textdomain() {
    load_plugin_textdomain('projects-map', false, dirname(plugin_basename(__FILE__)) . '/languages/');
}

// Initialize classes
new Projects_Map_Admin();
new Projects_Map_Shortcode();

// Enqueue scripts and styles
add_action('wp_enqueue_scripts', 'projects_map_enqueue_scripts');
function projects_map_enqueue_scripts() {
    if (has_shortcode(get_post()->post_content, 'projects_map')) {
        wp_enqueue_style('leaflet-css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css');
        wp_enqueue_script('leaflet-js', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', array(), null, true);
        wp_enqueue_style('leaflet-markercluster-css', 'https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css');
        wp_enqueue_style('leaflet-markercluster-default-css', 'https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css');
        wp_enqueue_script('leaflet-markercluster-js', 'https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js', array('leaflet-js'), null, true);
        wp_enqueue_script('projects-map-js', PROJECTS_MAP_PLUGIN_URL . 'assets/js/projects-map.js', array('jquery', 'leaflet-js', 'leaflet-markercluster-js'), PROJECTS_MAP_VERSION, true);
        wp_enqueue_style('projects-map-css', PROJECTS_MAP_PLUGIN_URL . 'assets/css/projects-map.css', array(), PROJECTS_MAP_VERSION);
        
        // Localize script with data
        $projects = Projects_Map_DB::get_all_projects();
        // Strip slashes from text fields for proper display in JS
        foreach ($projects as &$project) {
            $project['title'] = stripslashes($project['title']);
            $project['description'] = stripslashes($project['description']);
            if (isset($project['link_text'])) {
                $project['link_text'] = stripslashes($project['link_text']);
            }
        }
        $min_zoom = get_option('projects_map_min_zoom', 1);
        $max_zoom = get_option('projects_map_max_zoom', 10);
        $default_zoom = get_option('projects_map_default_zoom', 2);
        $default_zoom = max($min_zoom, min($max_zoom, $default_zoom));
        $map_style = get_option('projects_map_style', 'openstreetmap');
        wp_localize_script('projects-map-js', 'projectsMapData', array(
            'projects' => $projects,
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('projects_map_nonce'),
            'max_zoom' => $max_zoom,
            'min_zoom' => $min_zoom,
            'default_zoom' => $default_zoom,
            'map_style' => $map_style,
            'strings' => array(
                'read_more' => __('Read more', 'projects-map')
            )
        ));
    }
}
?>