<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Projects_Map_DB {

    public static function create_table() {
        global $wpdb;
        $locations_table = $wpdb->prefix . 'projects_map_locations';
        $maps_table = $wpdb->prefix . 'projects_map_maps';
        $categories_table = $wpdb->prefix . 'projects_map_categories';
        $charset_collate = $wpdb->get_charset_collate();

        // Maps table
        $sql_maps = "CREATE TABLE $maps_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            description text DEFAULT '' NOT NULL,
            settings text DEFAULT '' NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug)
        ) $charset_collate;";

        // Categories table
        $sql_categories = "CREATE TABLE $categories_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            color varchar(20) DEFAULT '#0080c0' NOT NULL,
            sort_order int(11) DEFAULT 0 NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        // Locations table (formerly projects)
        $sql_locations = "CREATE TABLE $locations_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            map_id mediumint(9) DEFAULT 1 NOT NULL,
            country varchar(100) NOT NULL,
            lat decimal(10,8) NOT NULL,
            lng decimal(11,8) NOT NULL,
            title varchar(255) NOT NULL,
            description text NOT NULL,
            image varchar(500) DEFAULT '' NOT NULL,
            url varchar(500) DEFAULT '' NOT NULL,
            link_text varchar(255) DEFAULT '' NOT NULL,
            category_id mediumint(9) DEFAULT 0 NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id),
            KEY map_id (map_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_maps);
        dbDelta($sql_categories);
        dbDelta($sql_locations);

        // Insert default map if not exists
        $default_map = $wpdb->get_var("SELECT id FROM $maps_table LIMIT 1");
        if (!$default_map) {
            $wpdb->insert($maps_table, array(
                'title' => 'Main Map',
                'slug' => 'main-map',
                'description' => 'Default map',
                'settings' => ''
            ));
        }

        // Insert default category if not exists
        $default_category = $wpdb->get_var("SELECT id FROM $categories_table LIMIT 1");
        if (!$default_category) {
            $wpdb->insert($categories_table, array(
                'title' => 'Default',
                'color' => '#0080c0',
                'sort_order' => 0
            ));
        }

        // Migrate old projects table to locations if it exists
        $old_table = $wpdb->prefix . 'projects_map';
        if ($wpdb->get_var("SHOW TABLES LIKE '$old_table'") == $old_table && $old_table != $locations_table) {
            $wpdb->query("INSERT INTO $locations_table (country, lat, lng, title, description, image, url, link_text, category_id, created_at, map_id)
                SELECT country, lat, lng, title, description, image, url, link_text, category_id, created_at, 1
                FROM $old_table
                WHERE NOT EXISTS (SELECT 1 FROM $locations_table WHERE $locations_table.id = $old_table.id)");
        }
    }

    // Maps methods
    public static function get_all_maps() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'projects_map_maps';
        $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id ASC", ARRAY_A);
        return $results;
    }

    public static function get_map($id_or_slug) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'projects_map_maps';
        if (is_numeric($id_or_slug)) {
            return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id_or_slug), ARRAY_A);
        } else {
            return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE slug = %s", $id_or_slug), ARRAY_A);
        }
    }

    public static function insert_map($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'projects_map_maps';
        $wpdb->insert($table_name, $data);
        return $wpdb->insert_id;
    }

    public static function update_map($id, $data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'projects_map_maps';
        $wpdb->update($table_name, $data, array('id' => $id));
    }

    public static function delete_map($id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'projects_map_maps';
        $wpdb->delete($table_name, array('id' => $id));
    }

    // Locations methods (formerly projects)
    public static function get_all_locations($map_id = null) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'projects_map_locations';
        $categories_table = $wpdb->prefix . 'projects_map_categories';
        
        if ($map_id) {
            $results = $wpdb->get_results($wpdb->prepare("
                SELECT l.*, c.title as category_title, c.color as category_color
                FROM $table_name l
                LEFT JOIN $categories_table c ON l.category_id = c.id
                WHERE l.map_id = %d
            ", $map_id), ARRAY_A);
        } else {
            $results = $wpdb->get_results("
                SELECT l.*, c.title as category_title, c.color as category_color
                FROM $table_name l
                LEFT JOIN $categories_table c ON l.category_id = c.id
            ", ARRAY_A);
        }
        return $results;
    }

    public static function get_all_projects() {
        // Backward compatibility
        return self::get_all_locations();
    }

    public static function insert_location($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'projects_map_locations';
        $wpdb->insert($table_name, $data);
        return $wpdb->insert_id;
    }

    public static function insert_project($data) {
        // Backward compatibility
        return self::insert_location($data);
    }

    public static function update_location($id, $data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'projects_map_locations';
        $wpdb->update($table_name, $data, array('id' => $id));
    }

    public static function update_project($id, $data) {
        // Backward compatibility
        return self::update_location($id, $data);
    }

    public static function delete_location($id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'projects_map_locations';
        $wpdb->delete($table_name, array('id' => $id));
    }

    public static function delete_project($id) {
        // Backward compatibility
        return self::delete_location($id);
    }

    public static function get_location($id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'projects_map_locations';
        $categories_table = $wpdb->prefix . 'projects_map_categories';
        return $wpdb->get_row($wpdb->prepare("
            SELECT l.*, c.title as category_title, c.color as category_color
            FROM $table_name l
            LEFT JOIN $categories_table c ON l.category_id = c.id
            WHERE l.id = %d
        ", $id), ARRAY_A);
    }

    public static function get_project($id) {
        // Backward compatibility
        return self::get_location($id);
    }

    // Category methods
    public static function get_all_categories() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'projects_map_categories';
        $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY sort_order ASC, title ASC", ARRAY_A);
        return $results;
    }

    public static function get_category($id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'projects_map_categories';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id), ARRAY_A);
    }

    public static function insert_category($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'projects_map_categories';
        $wpdb->insert($table_name, $data);
        return $wpdb->insert_id;
    }

    public static function update_category($id, $data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'projects_map_categories';
        $wpdb->update($table_name, $data, array('id' => $id));
    }

    public static function delete_category($id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'projects_map_categories';
        $wpdb->delete($table_name, array('id' => $id));
    }

    public static function get_categories_with_projects($map_id = null) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'projects_map_locations';
        $categories_table = $wpdb->prefix . 'projects_map_categories';
        
        if ($map_id) {
            $results = $wpdb->get_results($wpdb->prepare("
                SELECT c.*, COUNT(l.id) as project_count
                FROM $categories_table c
                LEFT JOIN $table_name l ON c.id = l.category_id AND l.map_id = %d
                GROUP BY c.id
                HAVING project_count > 0
                ORDER BY c.sort_order ASC, c.title ASC
            ", $map_id), ARRAY_A);
        } else {
            $results = $wpdb->get_results("
                SELECT c.*, COUNT(l.id) as project_count
                FROM $categories_table c
                LEFT JOIN $table_name l ON c.id = l.category_id
                GROUP BY c.id
                HAVING project_count > 0
                ORDER BY c.sort_order ASC, c.title ASC
            ", ARRAY_A);
        }
        return $results;
    }
}
?>