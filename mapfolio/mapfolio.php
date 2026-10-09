<?php
/**
 * Plugin Name:       Mapfolio
 * Plugin URI:        https://github.com/oskratch/wordpress-plugin-mapfolio
 * Description:       Interactive maps with your own locations (projects, stores, offices…). Create several maps, group locations in color-coded categories and embed them anywhere with a shortcode.
 * Version:           1.0.0
 * Requires at least: 5.5
 * Requires PHP:      7.4
 * Author:            Òscar Periche
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       mapfolio
 * Domain Path:       /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

define('MAPFOLIO_VERSION', '1.0.0');
define('MAPFOLIO_PLUGIN_FILE', __FILE__);
define('MAPFOLIO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MAPFOLIO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('MAPFOLIO_LEAFLET_VERSION', '1.9.4');
define('MAPFOLIO_MARKERCLUSTER_VERSION', '1.5.3');

require_once MAPFOLIO_PLUGIN_DIR . 'includes/functions.php';
require_once MAPFOLIO_PLUGIN_DIR . 'includes/class-mapfolio-db.php';
require_once MAPFOLIO_PLUGIN_DIR . 'includes/class-mapfolio-admin.php';
require_once MAPFOLIO_PLUGIN_DIR . 'includes/class-mapfolio-shortcode.php';

// Activation: create tables and default records
register_activation_hook(__FILE__, 'mapfolio_activate');
function mapfolio_activate() {
    // Load translations so the default map/category get localized names
    mapfolio_load_textdomain();
    Mapfolio_DB::create_tables();
    update_option('mapfolio_version', MAPFOLIO_VERSION);
}

// Upgrade: re-run dbDelta whenever the stored version differs from the code
add_action('plugins_loaded', 'mapfolio_upgrade_check');
function mapfolio_upgrade_check() {
    if (get_option('mapfolio_version') !== MAPFOLIO_VERSION) {
        Mapfolio_DB::create_tables();
        update_option('mapfolio_version', MAPFOLIO_VERSION);
    }
}

add_action('init', 'mapfolio_load_textdomain');
function mapfolio_load_textdomain() {
    load_plugin_textdomain('mapfolio', false, dirname(plugin_basename(MAPFOLIO_PLUGIN_FILE)) . '/languages/');
}

if (is_admin()) {
    new Mapfolio_Admin();
}
new Mapfolio_Shortcode();

// Frontend assets
add_action('wp_enqueue_scripts', 'mapfolio_frontend_assets');
function mapfolio_frontend_assets() {
    mapfolio_register_frontend_assets();

    // Enqueue early (in <head>) when the shortcode is in the post content. Page builders that store
    // content elsewhere are covered by the shortcode itself, which enqueues on render.
    $post = get_post();
    if (is_singular() && $post && has_shortcode($post->post_content, 'mapfolio')) {
        mapfolio_enqueue_frontend_assets();
    }
}

// AJAX endpoint that feeds the locations of a map to the frontend.
// No nonce check on purpose: it only returns public data, and nonces break on cached pages.
add_action('wp_ajax_mapfolio_get_locations', 'mapfolio_ajax_get_locations');
add_action('wp_ajax_nopriv_mapfolio_get_locations', 'mapfolio_ajax_get_locations');
function mapfolio_ajax_get_locations() {
    $map_id = isset($_POST['map_id']) ? absint($_POST['map_id']) : 0;
    if (!$map_id || !Mapfolio_DB::get_map($map_id)) {
        wp_send_json_error(__('Map not found.', 'mapfolio'), 404);
    }

    $locations = array();
    foreach (Mapfolio_DB::get_locations(array('map_id' => $map_id, 'per_page' => 0)) as $location) {
        $locations[] = array(
            'id'          => (int) $location['id'],
            'lat'         => (float) $location['lat'],
            'lng'         => (float) $location['lng'],
            'title'       => $location['title'],
            'description' => $location['description'],
            'image'       => $location['image'],
            'url'         => $location['url'],
            'link_text'   => $location['link_text'],
            'category_id' => (int) $location['category_id'],
            'color'       => $location['category_color'] ? $location['category_color'] : MAPFOLIO_DEFAULT_COLOR,
        );
    }

    wp_send_json_success($locations);
}
