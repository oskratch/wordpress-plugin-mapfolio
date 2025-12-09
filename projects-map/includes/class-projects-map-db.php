<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Projects_Map_DB {

    public static function create_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'projects_map';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            country varchar(100) NOT NULL,
            lat decimal(10,8) NOT NULL,
            lng decimal(11,8) NOT NULL,
            title varchar(255) NOT NULL,
            description text NOT NULL,
            image varchar(500) DEFAULT '' NOT NULL,
            url varchar(500) DEFAULT '' NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public static function get_all_projects() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'projects_map';
        $results = $wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);
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
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id), ARRAY_A);
    }
}
?>