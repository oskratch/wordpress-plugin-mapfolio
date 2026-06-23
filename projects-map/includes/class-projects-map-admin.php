<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Projects_Map_Admin {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        // Maps actions
        add_action('admin_post_add_map', array($this, 'handle_add_map'));
        add_action('admin_post_edit_map', array($this, 'handle_edit_map'));
        add_action('admin_post_delete_map', array($this, 'handle_delete_map'));
        // Locations actions (formerly projects)
        add_action('admin_post_add_location', array($this, 'handle_add_location'));
        add_action('admin_post_edit_location', array($this, 'handle_edit_location'));
        add_action('admin_post_delete_location', array($this, 'handle_delete_location'));
        // Backward compatibility - old project actions
        add_action('admin_post_add_project', array($this, 'handle_add_project'));
        add_action('admin_post_edit_project', array($this, 'handle_edit_project'));
        add_action('admin_post_delete_project', array($this, 'handle_delete_project'));
        // Category actions
        add_action('admin_post_add_category', array($this, 'handle_add_category'));
        add_action('admin_post_edit_category', array($this, 'handle_edit_category'));
        add_action('admin_post_delete_category', array($this, 'handle_delete_category'));
    }

    public function add_admin_menu() {
        add_menu_page(
            __('Maps & Locations', 'projects-map'),
            __('Maps & Locations', 'projects-map'),
            'edit_posts',
            'projects-map',
            array($this, 'maps_page'),
            'dashicons-location-alt',
            20
        );
        // Maps submenu
        add_submenu_page(
            'projects-map',
            __('All Maps', 'projects-map'),
            __('All Maps', 'projects-map'),
            'edit_posts',
            'projects-map',
            array($this, 'maps_page')
        );
        add_submenu_page(
            'projects-map',
            __('Add New Map', 'projects-map'),
            __('Add New Map', 'projects-map'),
            'edit_posts',
            'projects-map-add-map',
            array($this, 'add_map_page')
        );
        // Locations submenu
        add_submenu_page(
            'projects-map',
            __('All Locations', 'projects-map'),
            __('All Locations', 'projects-map'),
            'edit_posts',
            'projects-map-locations',
            array($this, 'locations_page')
        );
        add_submenu_page(
            'projects-map',
            __('Add New Location', 'projects-map'),
            __('Add New Location', 'projects-map'),
            'edit_posts',
            'projects-map-add-location',
            array($this, 'add_location_page')
        );
        // Categories submenu
        add_submenu_page(
            'projects-map',
            __('Categories', 'projects-map'),
            __('Categories', 'projects-map'),
            'edit_posts',
            'projects-map-categories',
            array($this, 'categories_page')
        );
        add_submenu_page(
            'projects-map',
            __('Add New Category', 'projects-map'),
            __('Add New Category', 'projects-map'),
            'edit_posts',
            'projects-map-add-category',
            array($this, 'add_category_page')
        );
        // Settings and Info
        add_submenu_page(
            'projects-map',
            __('Settings', 'projects-map'),
            __('Settings', 'projects-map'),
            'manage_options',
            'projects-map-settings',
            array($this, 'settings_page')
        );
        add_submenu_page(
            'projects-map',
            __('Info', 'projects-map'),
            __('Info', 'projects-map'),
            'edit_posts',
            'projects-map-info',
            array($this, 'info_page')
        );
    }

    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'projects-map-add') !== false) {
            wp_enqueue_media();
            wp_enqueue_script('projects-map-admin-js', PROJECTS_MAP_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), PROJECTS_MAP_VERSION, true);
            wp_localize_script('projects-map-admin-js', 'projectsMapAdmin', array(
                'choose_image' => __('Choose Image', 'projects-map')
            ));
        }
    }

    public function register_settings() {
        register_setting('projects_map_settings', 'projects_map_height', array('sanitize_callback' => 'sanitize_text_field'));
        register_setting('projects_map_settings', 'projects_map_height_mobile', array('sanitize_callback' => 'sanitize_text_field'));
        register_setting('projects_map_settings', 'projects_map_new_tab', array('sanitize_callback' => 'absint'));
        register_setting('projects_map_settings', 'projects_map_max_zoom', array('sanitize_callback' => 'absint'));
        register_setting('projects_map_settings', 'projects_map_min_zoom', array('sanitize_callback' => 'absint'));
        register_setting('projects_map_settings', 'projects_map_default_zoom', array('sanitize_callback' => 'absint'));
        register_setting('projects_map_settings', 'projects_map_style', array('sanitize_callback' => 'sanitize_text_field'));
        register_setting('projects_map_settings', 'projects_map_show_legend', array('sanitize_callback' => 'absint'));
        register_setting('projects_map_settings', 'projects_map_legend_bg', array('sanitize_callback' => 'sanitize_text_field'));

        add_settings_section('projects_map_display', __('Map Display', 'projects-map'), null, 'projects-map-settings');
        add_settings_section('projects_map_legend_section', __('Legend', 'projects-map'), null, 'projects-map-settings');
        add_settings_section('projects_map_zoom_section', __('Zoom Levels', 'projects-map'), null, 'projects-map-settings');

        add_settings_field('projects_map_height', __('Map Height', 'projects-map'), array($this, 'height_field'), 'projects-map-settings', 'projects_map_display');
        add_settings_field('projects_map_height_mobile', __('Map Height (Mobile/Tablet)', 'projects-map'), array($this, 'height_mobile_field'), 'projects-map-settings', 'projects_map_display');
        add_settings_field('projects_map_new_tab', __('Open links in new tab', 'projects-map'), array($this, 'new_tab_field'), 'projects-map-settings', 'projects_map_display');
        add_settings_field('projects_map_style', __('Map Style', 'projects-map'), array($this, 'map_style_field'), 'projects-map-settings', 'projects_map_display');
        add_settings_field('projects_map_show_legend', __('Show legend below map', 'projects-map'), array($this, 'show_legend_field'), 'projects-map-settings', 'projects_map_legend_section');
        add_settings_field('projects_map_legend_bg', __('Legend background color', 'projects-map'), array($this, 'legend_bg_field'), 'projects-map-settings', 'projects_map_legend_section');
        add_settings_field('projects_map_max_zoom', __('Maximum Zoom Level', 'projects-map'), array($this, 'max_zoom_field'), 'projects-map-settings', 'projects_map_zoom_section');
        add_settings_field('projects_map_min_zoom', __('Minimum Zoom Level', 'projects-map'), array($this, 'min_zoom_field'), 'projects-map-settings', 'projects_map_zoom_section');
        add_settings_field('projects_map_default_zoom', __('Default Zoom Level', 'projects-map'), array($this, 'default_zoom_field'), 'projects-map-settings', 'projects_map_zoom_section');
    }

    public function height_field() {
        $value = get_option('projects_map_height', '500px');
        echo '<input type="text" name="projects_map_height" value="' . esc_attr($value) . '" placeholder="500px">';
        echo '<p class="description">' . __('Desktop height. You can use % or px, e.g., 100% or 500px', 'projects-map') . '</p>';
    }

    public function height_mobile_field() {
        $value = get_option('projects_map_height_mobile', '350px');
        echo '<input type="text" name="projects_map_height_mobile" value="' . esc_attr($value) . '" placeholder="350px">';
        echo '<p class="description">' . __('Mobile/Tablet height (applied below 957px). You can use % or px, e.g., 70vh or 350px', 'projects-map') . '</p>';
    }

    public function new_tab_field() {
        $value = get_option('projects_map_new_tab', 0);
        echo '<input type="checkbox" name="projects_map_new_tab" value="1"' . checked(1, $value, false) . '>';
    }

    public function map_style_field() {
        $value = get_option('projects_map_style', 'openstreetmap');
        $styles = array(
            'openstreetmap' => __('OpenStreetMap (default, detailed)', 'projects-map'),
            'cartodb-positron' => __('CartoDB Positron (clean, minimal)', 'projects-map'),
            'cartodb-voyager' => __('CartoDB Voyager (clean with colors)', 'projects-map'),
            'esri-gray' => __('ESRI Gray Canvas (neutral gray)', 'projects-map')
        );
        echo '<select name="projects_map_style">';
        foreach ($styles as $key => $label) {
            echo '<option value="' . esc_attr($key) . '"' . selected($value, $key, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . __('Choose the map style. Minimalist options hide terrain details, rivers, and mountains.', 'projects-map') . '</p>';
    }

    public function show_legend_field() {
        $value = get_option('projects_map_show_legend', 1);
        echo '<input type="checkbox" name="projects_map_show_legend" value="1"' . checked(1, $value, false) . '>';
        echo '<p class="description">' . __('Display the category legend below the map', 'projects-map') . '</p>';
    }

    public function legend_bg_field() {
        $value = get_option('projects_map_legend_bg', '');
        $is_hex = preg_match('/^#[0-9A-Fa-f]{6}$/', $value);
        $picker_value = $is_hex ? $value : '#ffffff';
        echo '<input type="color" id="projects_map_legend_bg_picker" value="' . esc_attr($picker_value) . '" style="vertical-align: middle; margin-right: 6px;">';
        echo '<input type="text" name="projects_map_legend_bg" id="projects_map_legend_bg" value="' . esc_attr($value) . '" placeholder="' . esc_attr__('transparent', 'projects-map') . '" style="width: 140px; vertical-align: middle;">';
        echo '<p class="description">' . __('Leave empty for transparent. The color picker sets hex values; for rgba or named colors, type directly in the text field. Can be overridden per shortcode with legend_bg="..."', 'projects-map') . '</p>';
        echo '<script>document.getElementById("projects_map_legend_bg_picker").addEventListener("input", function() { document.getElementById("projects_map_legend_bg").value = this.value; });</script>';
    }

    public function max_zoom_field() {
        $value = get_option('projects_map_max_zoom', 10);
        echo '<input type="number" name="projects_map_max_zoom" value="' . esc_attr($value) . '" min="1" max="18">';
        echo '<p class="description">' . __('Maximum zoom level allowed on the map (1-18)', 'projects-map') . '</p>';
    }

    public function min_zoom_field() {
        $value = get_option('projects_map_min_zoom', 1);
        echo '<input type="number" name="projects_map_min_zoom" value="' . esc_attr($value) . '" min="1" max="18">';
        echo '<p class="description">' . __('Minimum zoom level allowed on the map (1-18)', 'projects-map') . '</p>';
    }

    public function default_zoom_field() {
        $value = get_option('projects_map_default_zoom', 2);
        echo '<input type="number" name="projects_map_default_zoom" value="' . esc_attr($value) . '" min="1" max="18">';
        echo '<p class="description">' . __('Initial zoom level when the map loads (1-18)', 'projects-map') . '</p>';
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Maps & Locations Settings', 'projects-map'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('projects_map_settings');
                do_settings_sections('projects-map-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    // ==================== MAPS MANAGEMENT ====================

    public function maps_page() {
        $maps = Projects_Map_DB::get_all_maps();
        $location_counts = Projects_Map_DB::get_location_counts_by_map();
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e('All Maps', 'projects-map'); ?></h1>
            <a href="<?php echo admin_url('admin.php?page=projects-map-add-map'); ?>" class="page-title-action"><?php _e('Add New Map', 'projects-map'); ?></a>
            <hr class="wp-header-end">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Title', 'projects-map'); ?></th>
                        <th><?php _e('Slug', 'projects-map'); ?></th>
                        <th><?php _e('Description', 'projects-map'); ?></th>
                        <th><?php _e('Locations', 'projects-map'); ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($maps as $map):
                        $location_count = isset($location_counts[$map['id']]) ? $location_counts[$map['id']] : 0;
                    ?>
                        <tr>
                            <td><strong><?php echo esc_html($map['title']); ?></strong></td>
                            <td><?php echo esc_html($map['slug']); ?></td>
                            <td><?php echo esc_html(wp_trim_words($map['description'], 10)); ?></td>
                            <td><?php echo $location_count; ?></td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=projects-map-add-map&id=' . $map['id']); ?>"><?php _e('Edit', 'projects-map'); ?></a> |
                                <a href="<?php echo admin_url('admin.php?page=projects-map-locations&map_id=' . $map['id']); ?>"><?php _e('View Locations', 'projects-map'); ?></a> |
                                <a href="<?php echo admin_url('admin.php?page=projects-map-add-location&map_id=' . $map['id']); ?>"><?php _e('Add Location', 'projects-map'); ?></a>
                                <?php if ($map['id'] != 1): ?>
                                 |
                                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display:inline;">
                                    <?php wp_nonce_field('delete_map_' . $map['id']); ?>
                                    <input type="hidden" name="action" value="delete_map">
                                    <input type="hidden" name="id" value="<?php echo $map['id']; ?>">
                                    <a href="#" onclick="if(confirm('<?php _e('Are you sure? This will not delete locations, but they will be moved to the default map.', 'projects-map'); ?>')) { this.parentNode.submit(); return false; }" style="color:red;"><?php _e('Delete', 'projects-map'); ?></a>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function add_map_page() {
        if (isset($_GET['id'])) {
            $map = Projects_Map_DB::get_map($_GET['id']);
            if (!$map) {
                wp_die(__('Map not found', 'projects-map'));
            }
        } else {
            $map = array('id' => '', 'title' => '', 'slug' => '', 'description' => '');
        }
        ?>
        <div class="wrap">
            <h1><?php echo isset($_GET['id']) ? __('Edit Map', 'projects-map') : __('Add New Map', 'projects-map'); ?></h1>
            <style>
                .projects-map-input {
                    width: 100%;
                }
                @media (min-width: 768px) {
                    .projects-map-input {
                        width: 50%;
                    }
                }
            </style>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <?php wp_nonce_field(isset($_GET['id']) ? 'edit_map_' . $_GET['id'] : 'add_map'); ?>
                <input type="hidden" name="action" value="<?php echo isset($_GET['id']) ? 'edit_map' : 'add_map'; ?>">
                <?php if (isset($_GET['id'])): ?>
                    <input type="hidden" name="id" value="<?php echo $_GET['id']; ?>">
                <?php endif; ?>
                <table class="form-table">
                    <tr>
                        <th><label for="title"><?php _e('Title', 'projects-map'); ?></label></th>
                        <td><input type="text" name="title" id="title" value="<?php echo esc_attr($map['title']); ?>" required class="projects-map-input"></td>
                    </tr>
                    <tr>
                        <th><label for="slug"><?php _e('Slug', 'projects-map'); ?></label></th>
                        <td>
                            <input type="text" name="slug" id="slug" value="<?php echo esc_attr($map['slug']); ?>" class="projects-map-input" pattern="[a-z0-9-]+" placeholder="<?php _e('Auto-generated from title', 'projects-map'); ?>">
                            <p class="description"><?php _e('URL-friendly version of the title. Leave empty to auto-generate. Only lowercase letters, numbers, and hyphens allowed.', 'projects-map'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="description"><?php _e('Description', 'projects-map'); ?></label></th>
                        <td><textarea name="description" id="description" rows="5" class="projects-map-input"><?php echo esc_textarea($map['description']); ?></textarea></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <script>
        jQuery(document).ready(function($) {
            var titleField = $('#title');
            var slugField = $('#slug');
            var isEditMode = <?php echo isset($_GET['id']) ? 'true' : 'false'; ?>;
            
            if (!isEditMode) {
                titleField.on('input', function() {
                    if (slugField.val() === '' || slugField.data('auto-generated')) {
                        var slug = $(this).val()
                            .toLowerCase()
                            .replace(/[^a-z0-9\s-]/g, '')
                            .replace(/\s+/g, '-')
                            .replace(/-+/g, '-')
                            .replace(/^-+|-+$/g, '');
                        slugField.val(slug).data('auto-generated', true);
                    }
                });
                
                slugField.on('input', function() {
                    if ($(this).val() !== '') {
                        $(this).data('auto-generated', false);
                    }
                });
            }
        });
        </script>
        <?php
    }

    public function handle_add_map() {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'add_map') || !current_user_can('edit_posts')) {
            wp_die(__('Security check failed', 'projects-map'));
        }
        
        $title = sanitize_text_field(wp_unslash($_POST['title']));
        $slug = isset($_POST['slug']) && $_POST['slug'] !== '' 
            ? sanitize_title(wp_unslash($_POST['slug'])) 
            : sanitize_title($title);
        
        $data = array(
            'title' => $title,
            'slug' => $slug,
            'description' => sanitize_textarea_field(wp_unslash($_POST['description']))
        );
        
        Projects_Map_DB::insert_map($data);
        wp_redirect(admin_url('admin.php?page=projects-map'));
        exit;
    }

    public function handle_edit_map() {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'edit_map_' . $_POST['id']) || !current_user_can('edit_posts')) {
            wp_die(__('Security check failed', 'projects-map'));
        }
        
        $title = sanitize_text_field(wp_unslash($_POST['title']));
        $slug = isset($_POST['slug']) && $_POST['slug'] !== '' 
            ? sanitize_title(wp_unslash($_POST['slug'])) 
            : sanitize_title($title);
        
        $data = array(
            'title' => $title,
            'slug' => $slug,
            'description' => sanitize_textarea_field(wp_unslash($_POST['description']))
        );
        
        Projects_Map_DB::update_map($_POST['id'], $data);
        wp_redirect(admin_url('admin.php?page=projects-map'));
        exit;
    }

    public function handle_delete_map() {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'delete_map_' . $_POST['id']) || !current_user_can('edit_posts')) {
            wp_die(__('Security check failed', 'projects-map'));
        }
        
        // Don't allow deleting the default map (id=1)
        if ($_POST['id'] == 1) {
            wp_die(__('Cannot delete the default map', 'projects-map'));
        }
        
        // Move all locations from this map to the default map (id=1) before deleting
        global $wpdb;
        $locations_table = $wpdb->prefix . 'projects_map_locations';
        $wpdb->update($locations_table, array('map_id' => 1), array('map_id' => $_POST['id']));
        
        Projects_Map_DB::delete_map($_POST['id']);
        wp_redirect(admin_url('admin.php?page=projects-map'));
        exit;
    }

    // ==================== LOCATIONS MANAGEMENT ====================

    public function locations_page() {
        $map_filter = isset($_GET['map_id']) ? intval($_GET['map_id']) : null;
        $locations = Projects_Map_DB::get_all_locations($map_filter);
        $maps = Projects_Map_DB::get_all_maps();
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e('All Locations', 'projects-map'); ?></h1>
            <a href="<?php echo admin_url('admin.php?page=projects-map-add-location'); ?>" class="page-title-action"><?php _e('Add New Location', 'projects-map'); ?></a>
            <hr class="wp-header-end">
            
            <div class="tablenav top">
                <div class="alignleft actions">
                    <label for="filter-map" class="screen-reader-text"><?php _e('Filter by map', 'projects-map'); ?></label>
                    <select name="map_id" id="filter-map" onchange="window.location.href='<?php echo admin_url('admin.php?page=projects-map-locations'); ?>&map_id=' + this.value;">
                        <option value=""><?php _e('All Maps', 'projects-map'); ?></option>
                        <?php foreach ($maps as $map): ?>
                            <option value="<?php echo $map['id']; ?>" <?php selected($map_filter, $map['id']); ?>><?php echo esc_html($map['title']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($map_filter): ?>
                        <a href="<?php echo admin_url('admin.php?page=projects-map-locations'); ?>" class="button"><?php _e('Clear Filter', 'projects-map'); ?></a>
                    <?php endif; ?>
                </div>
            </div>
            
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Map', 'projects-map'); ?></th>
                        <th><?php _e('Category', 'projects-map'); ?></th>
                        <th><?php _e('Title', 'projects-map'); ?></th>
                        <th><?php _e('Country', 'projects-map'); ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($locations as $location): 
                        $map = Projects_Map_DB::get_map($location['map_id']);
                    ?>
                        <tr>
                            <td><?php echo esc_html($map['title'] ?? 'Default'); ?></td>
                            <td><div style="width:20px; height:20px; background-color:<?php echo esc_attr($location['category_color'] ?? '#0080c0'); ?>; border:1px solid #ccc; display:inline-block; margin-right:5px;"></div><?php echo esc_html($location['category_title'] ?? 'Default'); ?></td>
                            <td><strong><?php echo esc_html($location['title']); ?></strong></td>
                            <td><?php echo esc_html($location['country']); ?></td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=projects-map-add-location&id=' . $location['id']); ?>"><?php _e('Edit', 'projects-map'); ?></a> |
                                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display:inline;">
                                    <?php wp_nonce_field('delete_location_' . $location['id']); ?>
                                    <input type="hidden" name="action" value="delete_location">
                                    <input type="hidden" name="id" value="<?php echo $location['id']; ?>">
                                    <a href="#" onclick="if(confirm('<?php _e('Are you sure?', 'projects-map'); ?>')) { this.parentNode.submit(); return false; }" style="color:red;"><?php _e('Delete', 'projects-map'); ?></a>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function add_location_page() {
        if (isset($_GET['id'])) {
            $location = Projects_Map_DB::get_location($_GET['id']);
            if (!$location) {
                wp_die(__('Location not found', 'projects-map'));
            }
            if (!isset($location['map_id'])) $location['map_id'] = 1;
        } else {
            // Pre-select map from URL parameter if provided
            $default_map_id = isset($_GET['map_id']) ? intval($_GET['map_id']) : 1;
            $location = array('id' => '', 'map_id' => $default_map_id, 'country' => '', 'lat' => '', 'lng' => '', 'title' => '', 'description' => '', 'image' => '', 'url' => '', 'link_text' => '', 'category_id' => 1);
        }
        
        $maps = Projects_Map_DB::get_all_maps();
        ?>
        <div class="wrap">
            <h1><?php echo isset($_GET['id']) ? __('Edit Location', 'projects-map') : __('Add New Location', 'projects-map'); ?></h1>
            <style>
                .projects-map-input {
                    width: 100%;
                }
                @media (min-width: 768px) {
                    .projects-map-input {
                        width: 50%;
                    }
                }
            </style>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <?php wp_nonce_field(isset($_GET['id']) ? 'edit_location_' . $_GET['id'] : 'add_location'); ?>
                <input type="hidden" name="action" value="<?php echo isset($_GET['id']) ? 'edit_location' : 'add_location'; ?>">
                <?php if (isset($_GET['id'])): ?>
                    <input type="hidden" name="id" value="<?php echo $_GET['id']; ?>">
                <?php endif; ?>
                <table class="form-table">
                    <tr>
                        <th><label for="map_id"><?php _e('Map', 'projects-map'); ?></label></th>
                        <td>
                            <select name="map_id" id="map_id" required class="projects-map-input">
                                <?php foreach ($maps as $map): ?>
                                    <option value="<?php echo esc_attr($map['id']); ?>" <?php selected($location['map_id'], $map['id']); ?>><?php echo esc_html($map['title']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="country"><?php _e('Country', 'projects-map'); ?></label></th>
                        <td><input type="text" name="country" id="country" value="<?php echo esc_attr($location['country']); ?>" required class="projects-map-input"></td>
                    </tr>
                    <tr>
                        <th><label for="lat"><?php _e('Latitude', 'projects-map'); ?></label></th>
                        <td><input type="number" step="any" name="lat" id="lat" value="<?php echo esc_attr($location['lat']); ?>" required class="projects-map-input"></td>
                    </tr>
                    <tr>
                        <th><label for="lng"><?php _e('Longitude', 'projects-map'); ?></label></th>
                        <td><input type="number" step="any" name="lng" id="lng" value="<?php echo esc_attr($location['lng']); ?>" required class="projects-map-input"></td>
                    </tr>
                    <tr>
                        <th><label for="title"><?php _e('Title', 'projects-map'); ?></label></th>
                        <td><input type="text" name="title" id="title" value="<?php echo esc_attr($location['title']); ?>" required class="projects-map-input"></td>
                    </tr>
                    <tr>
                        <th><label for="description"><?php _e('Description', 'projects-map'); ?></label></th>
                        <td><textarea name="description" id="description" rows="5" required class="projects-map-input"><?php echo esc_textarea($location['description']); ?></textarea></td>
                    </tr>
                    <tr>
                        <th><label for="image"><?php _e('Image', 'projects-map'); ?></label></th>
                        <td>
                            <input type="text" name="image" id="image" value="<?php echo esc_attr($location['image']); ?>" class="projects-map-input">
                            <button type="button" class="button" id="upload_image_button"><?php _e('Upload Image', 'projects-map'); ?></button>
                            <?php if ($location['image']): ?>
                                <br><img src="<?php echo esc_url($location['image']); ?>" style="max-width:100px; margin-top:5px;">
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="url"><?php _e('Location URL', 'projects-map'); ?></label></th>
                        <td><input type="url" name="url" id="url" value="<?php echo esc_attr($location['url']); ?>" class="projects-map-input"></td>
                    </tr>
                    <tr>
                        <th><label for="link_text"><?php _e('Link Text', 'projects-map'); ?></label></th>
                        <td>
                            <input type="text" name="link_text" id="link_text" value="<?php echo esc_attr($location['link_text']); ?>" class="projects-map-input">
                            <small><?php _e('Custom text for the link. If empty, defaults to "Read more".', 'projects-map'); ?></small>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="category_id"><?php _e('Category', 'projects-map'); ?></label></th>
                        <td>
                            <select name="category_id" id="category_id" required class="projects-map-input">
                                <?php
                                $categories = Projects_Map_DB::get_all_categories();
                                foreach ($categories as $category) {
                                    $selected = ($location['category_id'] == $category['id']) ? 'selected' : '';
                                    echo '<option value="' . esc_attr($category['id']) . '" ' . $selected . '>' . esc_html($category['title']) . ' (' . esc_attr($category['color']) . ')</option>';
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function handle_add_location() {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'add_location') || !current_user_can('edit_posts')) {
            wp_die(__('Security check failed', 'projects-map'));
        }
        $data = array(
            'map_id' => intval($_POST['map_id']),
            'country' => sanitize_text_field(wp_unslash($_POST['country'])),
            'lat' => floatval($_POST['lat']),
            'lng' => floatval($_POST['lng']),
            'title' => sanitize_text_field(wp_unslash($_POST['title'])),
            'description' => sanitize_textarea_field(wp_unslash($_POST['description'])),
            'image' => esc_url_raw(wp_unslash($_POST['image'])),
            'url' => esc_url_raw(wp_unslash($_POST['url'])),
            'link_text' => sanitize_text_field(wp_unslash($_POST['link_text'])),
            'category_id' => intval($_POST['category_id'])
        );
        Projects_Map_DB::insert_location($data);
        wp_redirect(admin_url('admin.php?page=projects-map-locations'));
        exit;
    }

    public function handle_edit_location() {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'edit_location_' . $_POST['id']) || !current_user_can('edit_posts')) {
            wp_die(__('Security check failed', 'projects-map'));
        }
        $data = array(
            'map_id' => intval($_POST['map_id']),
            'country' => sanitize_text_field(wp_unslash($_POST['country'])),
            'lat' => floatval($_POST['lat']),
            'lng' => floatval($_POST['lng']),
            'title' => sanitize_text_field(wp_unslash($_POST['title'])),
            'description' => sanitize_textarea_field(wp_unslash($_POST['description'])),
            'image' => esc_url_raw(wp_unslash($_POST['image'])),
            'url' => esc_url_raw(wp_unslash($_POST['url'])),
            'link_text' => sanitize_text_field(wp_unslash($_POST['link_text'])),
            'category_id' => intval($_POST['category_id'])
        );
        Projects_Map_DB::update_location($_POST['id'], $data);
        wp_redirect(admin_url('admin.php?page=projects-map-locations'));
        exit;
    }

    public function handle_delete_location() {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'delete_location_' . $_POST['id']) || !current_user_can('edit_posts')) {
            wp_die(__('Security check failed', 'projects-map'));
        }
        Projects_Map_DB::delete_location($_POST['id']);
        wp_redirect(admin_url('admin.php?page=projects-map-locations'));
        exit;
    }

    // ==================== BACKWARD COMPATIBILITY ====================

    public function admin_page() {
        // Redirect to locations page for backward compatibility
        wp_redirect(admin_url('admin.php?page=projects-map-locations'));
        exit;
    }

    public function add_project_page() {
        // Redirect to add location page for backward compatibility
        if (isset($_GET['id'])) {
            wp_redirect(admin_url('admin.php?page=projects-map-add-location&id=' . $_GET['id']));
        } else {
            wp_redirect(admin_url('admin.php?page=projects-map-add-location'));
        }
        exit;
    }

    public function handle_add_project() {
        // Call the new location handler for backward compatibility
        $_POST['_wpnonce'] = wp_create_nonce('add_location');
        if (!isset($_POST['map_id'])) {
            $_POST['map_id'] = 1; // Default map
        }
        $this->handle_add_location();
    }

    public function handle_edit_project() {
        // Call the new location handler for backward compatibility
        $_POST['_wpnonce'] = wp_create_nonce('edit_location_' . $_POST['id']);
        if (!isset($_POST['map_id'])) {
            $_POST['map_id'] = 1; // Default map
        }
        $this->handle_edit_location();
    }

    public function handle_delete_project() {
        // Call the new location handler for backward compatibility
        $_POST['_wpnonce'] = wp_create_nonce('delete_location_' . $_POST['id']);
        $this->handle_delete_location();
    }

    // ==================== CATEGORIES MANAGEMENT ====================

    public function categories_page() {
        $categories = Projects_Map_DB::get_all_categories();
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e('Categories', 'projects-map'); ?></h1>
            <a href="<?php echo admin_url('admin.php?page=projects-map-add-category'); ?>" class="page-title-action"><?php _e('Add New Category', 'projects-map'); ?></a>
            <hr class="wp-header-end">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Title', 'projects-map'); ?></th>
                        <th><?php _e('Color', 'projects-map'); ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $category): ?>
                        <tr>
                            <td><?php echo esc_html($category['title']); ?></td>
                            <td><div style="width:20px; height:20px; background-color:<?php echo esc_attr($category['color']); ?>; border:1px solid #ccc;"></div></td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=projects-map-add-category&id=' . $category['id']); ?>"><?php _e('Edit', 'projects-map'); ?></a> |
                                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display:inline;">
                                    <?php wp_nonce_field('delete_category_' . $category['id']); ?>
                                    <input type="hidden" name="action" value="delete_category">
                                    <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                                    <a href="#" onclick="if(confirm('<?php _e('Are you sure?', 'projects-map'); ?>')) { this.parentNode.submit(); return false; }" style="color:red;"><?php _e('Delete', 'projects-map'); ?></a>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function add_category_page() {
        if (isset($_GET['id'])) {
            $category = Projects_Map_DB::get_category($_GET['id']);
            if (!$category) {
                wp_die(__('Category not found', 'projects-map'));
            }
        } else {
            $category = array('id' => '', 'title' => '', 'color' => '#0080c0', 'sort_order' => 0);
        }
        ?>
        <div class="wrap">
            <h1><?php echo isset($_GET['id']) ? __('Edit Category', 'projects-map') : __('Add New Category', 'projects-map'); ?></h1>
            <style>
                .projects-map-input {
                    width: 100%;
                }
                @media (min-width: 768px) {
                    .projects-map-input {
                        width: 50%;
                    }
                }
            </style>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <?php wp_nonce_field(isset($_GET['id']) ? 'edit_category_' . $_GET['id'] : 'add_category'); ?>
                <input type="hidden" name="action" value="<?php echo isset($_GET['id']) ? 'edit_category' : 'add_category'; ?>">
                <?php if (isset($_GET['id'])): ?>
                    <input type="hidden" name="id" value="<?php echo $_GET['id']; ?>">
                <?php endif; ?>
                <table class="form-table">
                    <tr>
                        <th><label for="title"><?php _e('Title', 'projects-map'); ?></label></th>
                        <td><input type="text" name="title" id="title" value="<?php echo esc_attr($category['title']); ?>" required class="projects-map-input"></td>
                    </tr>
                    <tr>
                        <th><label for="color"><?php _e('Color', 'projects-map'); ?></label></th>
                        <td>
                            <input type="color" name="color" id="color" value="<?php echo esc_attr($category['color']); ?>" required>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="sort_order"><?php _e('Sort Order', 'projects-map'); ?></label></th>
                        <td><input type="number" name="sort_order" id="sort_order" value="<?php echo esc_attr($category['sort_order']); ?>" min="0" class="projects-map-input" style="width: 100px;"></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function handle_add_category() {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'add_category') || !current_user_can('edit_posts')) {
            wp_die(__('Security check failed', 'projects-map'));
        }
        $data = array(
            'title' => sanitize_text_field($_POST['title']),
            'color' => sanitize_hex_color($_POST['color']) ?: '#0080c0',
            'sort_order' => intval($_POST['sort_order'])
        );
        Projects_Map_DB::insert_category($data);
        wp_redirect(admin_url('admin.php?page=projects-map-categories'));
        exit;
    }

    public function handle_edit_category() {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'edit_category_' . $_POST['id']) || !current_user_can('edit_posts')) {
            wp_die(__('Security check failed', 'projects-map'));
        }
        $data = array(
            'title' => sanitize_text_field($_POST['title']),
            'color' => sanitize_hex_color($_POST['color']) ?: '#0080c0',
            'sort_order' => intval($_POST['sort_order'])
        );
        Projects_Map_DB::update_category($_POST['id'], $data);
        wp_redirect(admin_url('admin.php?page=projects-map-categories'));
        exit;
    }

    public function handle_delete_category() {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'delete_category_' . $_POST['id']) || !current_user_can('edit_posts')) {
            wp_die(__('Security check failed', 'projects-map'));
        }
        Projects_Map_DB::delete_category($_POST['id']);
        wp_redirect(admin_url('admin.php?page=projects-map-categories'));
        exit;
    }

    // ==================== INFO PAGE ====================

    public function info_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Maps & Locations Info', 'projects-map'); ?></h1>
            <p><?php _e('To display a map on your site, use the following shortcode:', 'projects-map'); ?></p>
            <code>[projects_map map="your-map-slug"]</code>
            <h2><?php _e('Shortcode Attributes', 'projects-map'); ?></h2>
            <ul>
                <li><code>map</code>: <?php _e('Map slug or ID to display (required)', 'projects-map'); ?></li>
                <li><code>height</code>: <?php _e('Map height, e.g. 500px or 100% (default: global setting)', 'projects-map'); ?></li>
                <li><code>mobile_height</code>: <?php _e('Map height on screens below 957px, e.g. 350px or 70vh (default: global setting)', 'projects-map'); ?></li>
                <li><code>new_tab</code>: <?php _e('Open links in new tab (0 or 1, default: global setting)', 'projects-map'); ?></li>
                <li><code>show_legend</code>: <?php _e('Show category legend below map (0 or 1, default: global setting)', 'projects-map'); ?></li>
                <li><code>legend_bg</code>: <?php _e('Background color for the legend block, e.g. #ffffff or rgba(255,255,255,0.9) (default: transparent)', 'projects-map'); ?></li>
                <li><code>debug</code>: <?php _e('Enable debug mode, logs details to browser console (0 or 1, default: 0)', 'projects-map'); ?></li>
            </ul>
            <p><?php _e('Example: [projects_map map="main-map" height="600px" mobile_height="360px" new_tab="1" show_legend="1" legend_bg="#f5f5f5"]', 'projects-map'); ?></p>
            <h2><?php _e('Debug Mode', 'projects-map'); ?></h2>
            <p><?php _e('When debug mode is enabled, detailed information is logged to the browser console to help troubleshoot issues:', 'projects-map'); ?></p>
            <ul>
                <li><?php _e('jQuery loading status', 'projects-map'); ?></li>
                <li><?php _e('Map container existence', 'projects-map'); ?></li>
                <li><?php _e('Location data and count', 'projects-map'); ?></li>
                <li><?php _e('Marker creation process', 'projects-map'); ?></li>
                <li><?php _e('JavaScript errors', 'projects-map'); ?></li>
            </ul>
            <h2><?php _e('Instructions', 'projects-map'); ?></h2>
            <ol>
                <li><?php _e('Create maps from the "All Maps" menu to organize your locations.', 'projects-map'); ?></li>
                <li><?php _e('Add locations from the "All Locations" menu.', 'projects-map'); ?></li>
                <li><?php _e('Each location needs a map, country, latitude, longitude, title, description, and optionally an image and URL.', 'projects-map'); ?></li>
                <li><?php _e('Insert the shortcode [projects_map] into any page or post to display the map.', 'projects-map'); ?></li>
                <li><?php _e('Use the map parameter to display a specific map: [projects_map map="main-map"]', 'projects-map'); ?></li>
                <li><?php _e('The map uses clustering: zoom out to see location counts per region, zoom in to see individual locations.', 'projects-map'); ?></li>
            </ol>
        </div>
        <?php
    }
}
?>