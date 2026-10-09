<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Mapfolio_DB {

    // Created on activation; cannot be deleted. Orphaned records are moved to them.
    const DEFAULT_MAP_ID      = 1;
    const DEFAULT_CATEGORY_ID = 1;

    public static function maps_table() {
        global $wpdb;
        return $wpdb->prefix . 'mapfolio_maps';
    }

    public static function locations_table() {
        global $wpdb;
        return $wpdb->prefix . 'mapfolio_locations';
    }

    public static function categories_table() {
        global $wpdb;
        return $wpdb->prefix . 'mapfolio_categories';
    }

    public static function create_tables() {
        global $wpdb;
        $maps_table       = self::maps_table();
        $locations_table  = self::locations_table();
        $categories_table = self::categories_table();
        $charset_collate  = $wpdb->get_charset_collate();

        $sql_maps = "CREATE TABLE $maps_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            description text NOT NULL,
            settings text NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY slug (slug)
        ) $charset_collate;";

        $sql_categories = "CREATE TABLE $categories_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            color varchar(20) DEFAULT '" . MAPFOLIO_DEFAULT_COLOR . "' NOT NULL,
            sort_order int(11) DEFAULT 0 NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        $sql_locations = "CREATE TABLE $locations_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            map_id mediumint(9) DEFAULT 1 NOT NULL,
            country varchar(100) DEFAULT '' NOT NULL,
            lat decimal(10,8) NOT NULL,
            lng decimal(11,8) NOT NULL,
            title varchar(255) NOT NULL,
            description text NOT NULL,
            image varchar(500) DEFAULT '' NOT NULL,
            url varchar(500) DEFAULT '' NOT NULL,
            link_text varchar(255) DEFAULT '' NOT NULL,
            category_id mediumint(9) DEFAULT 1 NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY  (id),
            KEY map_id (map_id),
            KEY category_id (category_id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_maps);
        dbDelta($sql_categories);
        dbDelta($sql_locations);

        if (!$wpdb->get_var("SELECT id FROM $maps_table LIMIT 1")) {
            $wpdb->insert($maps_table, array(
                'id'          => self::DEFAULT_MAP_ID,
                'title'       => __('Main map', 'mapfolio'),
                'slug'        => 'main-map',
                'description' => '',
                'settings'    => '',
            ));
        }

        if (!$wpdb->get_var("SELECT id FROM $categories_table LIMIT 1")) {
            $wpdb->insert($categories_table, array(
                'id'         => self::DEFAULT_CATEGORY_ID,
                'title'      => __('General', 'mapfolio'),
                'color'      => MAPFOLIO_DEFAULT_COLOR,
                'sort_order' => 0,
            ));
        }
    }

    // ==================== MAPS ====================

    public static function get_all_maps() {
        global $wpdb;
        $table = self::maps_table();
        return $wpdb->get_results("SELECT * FROM $table ORDER BY id ASC", ARRAY_A);
    }

    /**
     * @param int|string $id_or_slug Numeric ID or slug.
     */
    public static function get_map($id_or_slug) {
        global $wpdb;
        $table = self::maps_table();
        if (is_numeric($id_or_slug)) {
            return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id_or_slug), ARRAY_A);
        }
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE slug = %s", $id_or_slug), ARRAY_A);
    }

    /**
     * Decoded per-map settings (empty array when none).
     */
    public static function get_map_settings($map) {
        if (empty($map['settings'])) {
            return array();
        }
        $settings = json_decode($map['settings'], true);
        return is_array($settings) ? $settings : array();
    }

    /**
     * Return $slug, or $slug-2, $slug-3… if it is already used by another map.
     */
    public static function unique_map_slug($slug, $exclude_id = 0) {
        global $wpdb;
        $table     = self::maps_table();
        $base      = $slug;
        $suffix    = 2;
        while ($wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE slug = %s AND id != %d", $slug, $exclude_id))) {
            $slug = $base . '-' . $suffix++;
        }
        return $slug;
    }

    public static function insert_map($data) {
        global $wpdb;
        $wpdb->insert(self::maps_table(), $data);
        return $wpdb->insert_id;
    }

    public static function update_map($id, $data) {
        global $wpdb;
        return $wpdb->update(self::maps_table(), $data, array('id' => $id));
    }

    /**
     * Delete a map. Its locations are moved to the default map, which cannot be deleted.
     */
    public static function delete_map($id) {
        global $wpdb;
        $id = (int) $id;
        if ($id === self::DEFAULT_MAP_ID) {
            return false;
        }
        $wpdb->update(self::locations_table(), array('map_id' => self::DEFAULT_MAP_ID), array('map_id' => $id));
        return $wpdb->delete(self::maps_table(), array('id' => $id));
    }

    /**
     * @return array map_id => number of locations
     */
    public static function get_location_counts_by_map() {
        global $wpdb;
        $table = self::locations_table();
        $rows  = $wpdb->get_results("SELECT map_id, COUNT(*) AS count FROM $table GROUP BY map_id", ARRAY_A);
        return wp_list_pluck($rows, 'count', 'map_id');
    }

    // ==================== LOCATIONS ====================

    /**
     * Query locations, joined with their map title and category title/color.
     *
     * @param array $args {
     *     @type int    $map_id      Filter by map.
     *     @type int    $category_id Filter by category.
     *     @type string $search      Search in title and country.
     *     @type string $orderby     title|country|map|category|created_at.
     *     @type string $order       ASC|DESC.
     *     @type int    $per_page    0 for no limit.
     *     @type int    $page        1-based page number.
     * }
     */
    public static function get_locations($args = array()) {
        global $wpdb;
        $args = wp_parse_args($args, array(
            'map_id'      => 0,
            'category_id' => 0,
            'search'      => '',
            'orderby'     => 'title',
            'order'       => 'ASC',
            'per_page'    => 0,
            'page'        => 1,
        ));

        $sql = "SELECT l.*, m.title AS map_title, c.title AS category_title, c.color AS category_color"
             . self::locations_from_where($args);

        $orderby_columns = array(
            'title'      => 'l.title',
            'country'    => 'l.country',
            'map'        => 'm.title',
            'category'   => 'c.title',
            'created_at' => 'l.created_at',
        );
        $orderby = isset($orderby_columns[$args['orderby']]) ? $orderby_columns[$args['orderby']] : 'l.title';
        $order   = strtoupper($args['order']) === 'DESC' ? 'DESC' : 'ASC';
        $sql    .= " ORDER BY $orderby $order, l.id ASC";

        if ($args['per_page'] > 0) {
            $sql .= $wpdb->prepare(' LIMIT %d OFFSET %d', $args['per_page'], ($args['page'] - 1) * $args['per_page']);
        }

        return $wpdb->get_results($sql, ARRAY_A);
    }

    /**
     * Number of locations matching the same filters as get_locations().
     */
    public static function count_locations($args = array()) {
        global $wpdb;
        $args = wp_parse_args($args, array('map_id' => 0, 'category_id' => 0, 'search' => ''));
        return (int) $wpdb->get_var('SELECT COUNT(*)' . self::locations_from_where($args));
    }

    private static function locations_from_where($args) {
        global $wpdb;
        $sql = ' FROM ' . self::locations_table() . ' l'
             . ' LEFT JOIN ' . self::maps_table() . ' m ON l.map_id = m.id'
             . ' LEFT JOIN ' . self::categories_table() . ' c ON l.category_id = c.id'
             . ' WHERE 1=1';

        if (!empty($args['map_id'])) {
            $sql .= $wpdb->prepare(' AND l.map_id = %d', $args['map_id']);
        }
        if (!empty($args['category_id'])) {
            $sql .= $wpdb->prepare(' AND l.category_id = %d', $args['category_id']);
        }
        if ($args['search'] !== '') {
            $like = '%' . $wpdb->esc_like($args['search']) . '%';
            $sql .= $wpdb->prepare(' AND (l.title LIKE %s OR l.country LIKE %s)', $like, $like);
        }
        return $sql;
    }

    public static function get_location($id) {
        global $wpdb;
        $table = self::locations_table();
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id), ARRAY_A);
    }

    public static function insert_location($data) {
        global $wpdb;
        $wpdb->insert(self::locations_table(), $data);
        return $wpdb->insert_id;
    }

    public static function update_location($id, $data) {
        global $wpdb;
        return $wpdb->update(self::locations_table(), $data, array('id' => $id));
    }

    public static function delete_location($id) {
        global $wpdb;
        return $wpdb->delete(self::locations_table(), array('id' => $id));
    }

    // ==================== CATEGORIES ====================

    public static function get_all_categories() {
        global $wpdb;
        $table = self::categories_table();
        return $wpdb->get_results("SELECT * FROM $table ORDER BY sort_order ASC, title ASC", ARRAY_A);
    }

    public static function get_category($id) {
        global $wpdb;
        $table = self::categories_table();
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id), ARRAY_A);
    }

    public static function insert_category($data) {
        global $wpdb;
        $wpdb->insert(self::categories_table(), $data);
        return $wpdb->insert_id;
    }

    public static function update_category($id, $data) {
        global $wpdb;
        return $wpdb->update(self::categories_table(), $data, array('id' => $id));
    }

    /**
     * Delete a category. Its locations are moved to the default category, which cannot be deleted.
     */
    public static function delete_category($id) {
        global $wpdb;
        $id = (int) $id;
        if ($id === self::DEFAULT_CATEGORY_ID) {
            return false;
        }
        $wpdb->update(self::locations_table(), array('category_id' => self::DEFAULT_CATEGORY_ID), array('category_id' => $id));
        return $wpdb->delete(self::categories_table(), array('id' => $id));
    }

    /**
     * @return array category_id => number of locations
     */
    public static function get_location_counts_by_category() {
        global $wpdb;
        $table = self::locations_table();
        $rows  = $wpdb->get_results("SELECT category_id, COUNT(*) AS count FROM $table GROUP BY category_id", ARRAY_A);
        return wp_list_pluck($rows, 'count', 'category_id');
    }

    /**
     * Categories that have at least one location on the given map, with a location_count column.
     */
    public static function get_categories_for_map($map_id) {
        global $wpdb;
        $categories_table = self::categories_table();
        $locations_table  = self::locations_table();
        return $wpdb->get_results($wpdb->prepare("
            SELECT c.*, COUNT(l.id) AS location_count
            FROM $categories_table c
            INNER JOIN $locations_table l ON c.id = l.category_id AND l.map_id = %d
            GROUP BY c.id
            ORDER BY c.sort_order ASC, c.title ASC
        ", $map_id), ARRAY_A);
    }
}
