<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

define('MAPFOLIO_DEFAULT_COLOR', '#0080c0');

/**
 * Available tile styles (key => label).
 */
function mapfolio_get_map_styles() {
    return array(
        'openstreetmap'    => __('OpenStreetMap (detailed)', 'mapfolio'),
        'cartodb-positron' => __('CARTO Positron (clean, minimal)', 'mapfolio'),
        'cartodb-voyager'  => __('CARTO Voyager (clean, with colors)', 'mapfolio'),
        'esri-gray'        => __('Esri Gray Canvas (neutral gray)', 'mapfolio'),
    );
}

/**
 * Global settings with their defaults. Every option is stored as "mapfolio_{key}".
 */
function mapfolio_get_default_settings() {
    return array(
        'height'               => '500px',
        'height_mobile'        => '350px',
        'new_tab'              => 0,
        'style'                => 'openstreetmap',
        'carto_api_key'        => '',
        'show_legend'          => 1,
        'legend_bg'            => '',
        'legend_filter'        => 1,
        'cluster_color_small'  => '#29B246',
        'cluster_color_large'  => '#E67E22',
        'min_zoom'             => 1,
        'max_zoom'             => 10,
        'default_zoom'         => 2,
    );
}

/**
 * Read a global setting, falling back to its default.
 */
function mapfolio_get_setting($key) {
    $defaults = mapfolio_get_default_settings();
    $default  = isset($defaults[$key]) ? $defaults[$key] : '';
    return get_option('mapfolio_' . $key, $default);
}

/**
 * Capability required to manage maps, locations and categories.
 */
function mapfolio_capability() {
    return apply_filters('mapfolio_capability', 'edit_posts');
}

/**
 * Sanitize a CSS length for the map height. Accepts numbers with px, %, vh, svh, dvh, em or rem,
 * and the keyword "fit". A unitless number is treated as pixels. Returns $fallback when invalid.
 */
function mapfolio_sanitize_css_size($value, $fallback = '') {
    $value = strtolower(trim((string) $value));
    if ($value === 'fit') {
        return $value;
    }
    if (preg_match('/^\d+(\.\d+)?$/', $value)) {
        return $value . 'px';
    }
    if (preg_match('/^\d+(\.\d+)?(px|%|vh|svh|dvh|em|rem)$/', $value)) {
        return $value;
    }
    return $fallback;
}

/**
 * Sanitize a CSS color: hex, rgb()/rgba()/hsl()/hsla(), a named color or "transparent".
 * Returns an empty string when invalid.
 */
function mapfolio_sanitize_css_color($value) {
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }
    if (preg_match('/^#([0-9a-f]{3}|[0-9a-f]{4}|[0-9a-f]{6}|[0-9a-f]{8})$/i', $value)) {
        return $value;
    }
    if (preg_match('/^(rgb|rgba|hsl|hsla)\(\s*[\d.%\s,\/]+\)$/i', $value)) {
        return $value;
    }
    if (preg_match('/^[a-z]+$/i', $value)) {
        return strtolower($value);
    }
    return '';
}

/**
 * Clamp a zoom level to the range supported by the tile providers.
 */
function mapfolio_sanitize_zoom($value) {
    return max(1, min(18, intval($value)));
}

/**
 * Register Leaflet, MarkerCluster and the plugin's own frontend assets.
 */
function mapfolio_register_frontend_assets() {
    if (wp_script_is('mapfolio', 'registered')) {
        return;
    }

    // Bundled with the plugin (no third-party CDN): see assets/vendor/
    $leaflet = MAPFOLIO_PLUGIN_URL . 'assets/vendor/leaflet/';
    $cluster = MAPFOLIO_PLUGIN_URL . 'assets/vendor/leaflet.markercluster/';

    wp_register_style('mapfolio-leaflet', $leaflet . 'leaflet.css', array(), MAPFOLIO_LEAFLET_VERSION);
    wp_register_script('mapfolio-leaflet', $leaflet . 'leaflet.js', array(), MAPFOLIO_LEAFLET_VERSION, true);
    wp_register_style('mapfolio-markercluster', $cluster . 'MarkerCluster.css', array('mapfolio-leaflet'), MAPFOLIO_MARKERCLUSTER_VERSION);
    wp_register_style('mapfolio-markercluster-default', $cluster . 'MarkerCluster.Default.css', array('mapfolio-markercluster'), MAPFOLIO_MARKERCLUSTER_VERSION);
    wp_register_script('mapfolio-markercluster', $cluster . 'leaflet.markercluster.js', array('mapfolio-leaflet'), MAPFOLIO_MARKERCLUSTER_VERSION, true);

    wp_register_style('mapfolio', MAPFOLIO_PLUGIN_URL . 'assets/css/mapfolio.css', array('mapfolio-leaflet', 'mapfolio-markercluster-default'), MAPFOLIO_VERSION);
    wp_register_script('mapfolio', MAPFOLIO_PLUGIN_URL . 'assets/js/mapfolio.js', array('jquery', 'mapfolio-leaflet', 'mapfolio-markercluster'), MAPFOLIO_VERSION, true);

    wp_localize_script('mapfolio', 'mapfolioGlobal', array(
        'ajax_url'            => admin_url('admin-ajax.php'),
        'carto_api_key'       => mapfolio_get_setting('carto_api_key'),
        'cluster_color_small' => mapfolio_get_setting('cluster_color_small'),
        'cluster_color_large' => mapfolio_get_setting('cluster_color_large'),
        'strings'             => array(
            'read_more' => __('Read more', 'mapfolio'),
        ),
    ));
}

function mapfolio_enqueue_frontend_assets() {
    mapfolio_register_frontend_assets();
    wp_enqueue_style('mapfolio');
    wp_enqueue_script('mapfolio');
}
