<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Projects_Map_DB {

    public static function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'projects_map';
        $categories_table = $wpdb->prefix . 'projects_map_categories';
        $charset_collate = $wpdb->get_charset_collate();

        // Categories table
        $sql_categories = "CREATE TABLE $categories_table (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            color varchar(20) DEFAULT '#0080c0' NOT NULL,
            sort_order int(11) DEFAULT 0 NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        // Projects table (modified)
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            country varchar(100) NOT NULL,
            lat decimal(10,8) NOT NULL,
            lng decimal(11,8) NOT NULL,
            title varchar(255) NOT NULL,
            description text NOT NULL,
            image varchar(500) DEFAULT '' NOT NULL,
            url varchar(500) DEFAULT '' NOT NULL,
            category_id mediumint(9) DEFAULT 0 NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_categories);
        dbDelta($sql);

        // Insert default category if not exists
        $default_category = $wpdb->get_var("SELECT id FROM $categories_table LIMIT 1");
        if (!$default_category) {
            $wpdb->insert($categories_table, array(
                'title' => 'Default',
                'color' => '#0080c0',
                'sort_order' => 0
            ));
        }
    }

    public static function get_all_projects() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'projects_map';
        $categories_table = $wpdb->prefix . 'projects_map_categories';
        $results = $wpdb->get_results("
            SELECT p.*, c.title as category_title, c.color as category_color
            FROM $table_name p
            LEFT JOIN $categories_table c ON p.category_id = c.id
        ", ARRAY_A);
        return $results;
    }

    public static function insert_project($data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'projects_map';
        $wpdb->insert($table_name, $data);
        return $wpdb->insert_id;
    }

    public static function update_project($id, $data) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'projects_map';
        $wpdb->update($table_name, $data, array('id' => $id));
    }

    public static function delete_project($id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'projects_map';
        $wpdb->delete($table_name, array('id' => $id));
    }

    public static function get_project($id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'projects_map';
        $categories_table = $wpdb->prefix . 'projects_map_categories';
        return $wpdb->get_row($wpdb->prepare("
            SELECT p.*, c.title as category_title, c.color as category_color
            FROM $table_name p
            LEFT JOIN $categories_table c ON p.category_id = c.id
            WHERE p.id = %d
        ", $id), ARRAY_A);
    }

    // Category methods
    public static function get_all_categories() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'projects_map_categories';
        $results = $wpdb->get_results("SELECT * FROM $table_name ORDER BY sort_order ASC, title ASC", ARRAY_A);
        return $results;
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

    public static function get_category($id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'projects_map_categories';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id), ARRAY_A);
    }
}
?>