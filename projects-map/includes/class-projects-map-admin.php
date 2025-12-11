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
        add_action('admin_post_add_project', array($this, 'handle_add_project'));
        add_action('admin_post_edit_project', array($this, 'handle_edit_project'));
        add_action('admin_post_delete_project', array($this, 'handle_delete_project'));
    }

    public function add_admin_menu() {
        add_menu_page(
            __('Projects Map', 'projects-map'),
            __('Projects Map', 'projects-map'),
            'edit_posts',
            'projects-map',
            array($this, 'admin_page'),
            'dashicons-location-alt',
            20
        );
        add_submenu_page(
            'projects-map',
            __('All Projects', 'projects-map'),
            __('All Projects', 'projects-map'),
            'edit_posts',
            'projects-map',
            array($this, 'admin_page')
        );
        add_submenu_page(
            'projects-map',
            __('Add New Project', 'projects-map'),
            __('Add New Project', 'projects-map'),
            'edit_posts',
            'projects-map-add',
            array($this, 'add_project_page')
        );
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
        register_setting('projects_map_settings', 'projects_map_new_tab', array('sanitize_callback' => 'absint'));
        register_setting('projects_map_settings', 'projects_map_max_zoom', array('sanitize_callback' => 'absint'));
        register_setting('projects_map_settings', 'projects_map_min_zoom', array('sanitize_callback' => 'absint'));
        register_setting('projects_map_settings', 'projects_map_default_zoom', array('sanitize_callback' => 'absint'));

        add_settings_section('projects_map_general', __('General Settings', 'projects-map'), null, 'projects-map-settings');

        add_settings_field('projects_map_height', __('Map Height', 'projects-map'), array($this, 'height_field'), 'projects-map-settings', 'projects_map_general');
        add_settings_field('projects_map_new_tab', __('Open links in new tab', 'projects-map'), array($this, 'new_tab_field'), 'projects-map-settings', 'projects_map_general');
        add_settings_field('projects_map_max_zoom', __('Maximum Zoom Level', 'projects-map'), array($this, 'max_zoom_field'), 'projects-map-settings', 'projects_map_general');
        add_settings_field('projects_map_min_zoom', __('Minimum Zoom Level', 'projects-map'), array($this, 'min_zoom_field'), 'projects-map-settings', 'projects_map_general');
        add_settings_field('projects_map_default_zoom', __('Default Zoom Level', 'projects-map'), array($this, 'default_zoom_field'), 'projects-map-settings', 'projects_map_general');
    }

    public function height_field() {
        $value = get_option('projects_map_height', '500px');
        echo '<input type="text" name="projects_map_height" value="' . esc_attr($value) . '" placeholder="500px">';
        echo '<p class="description">' . __('You can use % or px, e.g., 100% or 500px', 'projects-map') . '</p>';
    }

    public function new_tab_field() {
        $value = get_option('projects_map_new_tab', 0);
        echo '<input type="checkbox" name="projects_map_new_tab" value="1"' . checked(1, $value, false) . '>';
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
            <h1><?php _e('Projects Map Settings', 'projects-map'); ?></h1>
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

    public function admin_page() {
        $projects = Projects_Map_DB::get_all_projects();
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e('Projects Map', 'projects-map'); ?></h1>
            <a href="<?php echo admin_url('admin.php?page=projects-map-add'); ?>" class="page-title-action"><?php _e('Add New Project', 'projects-map'); ?></a>
            <hr class="wp-header-end">
            <h2 class="screen-reader-text"><?php _e('Filter projects list', 'projects-map'); ?></h2>
            <ul class="subsubsub">
                <li class="all"><a href="#" class="current"><?php _e('All', 'projects-map'); ?> <span class="count">(<?php echo count($projects); ?>)</span></a></li>
            </ul>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width:30px;"></th>
                        <th><?php _e('ID', 'projects-map'); ?></th>
                        <th><?php _e('Country', 'projects-map'); ?></th>
                        <th><?php _e('Title', 'projects-map'); ?></th>
                        <th><?php _e('Description', 'projects-map'); ?></th>
                        <th><?php _e('Actions', 'projects-map'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($projects as $project): ?>
                        <tr>
                            <td><div style="width:20px; height:20px; background-color:<?php echo esc_attr($project['color'] ?? 'blue'); ?>; border:1px solid #ccc;"></div></td>
                            <td><?php echo esc_html($project['id']); ?></td>
                            <td><?php echo esc_html($project['country']); ?></td>
                            <td><?php echo esc_html($project['title']); ?></td>
                            <td><?php echo esc_html(substr($project['description'], 0, 50)); ?>...</td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=projects-map-add&id=' . $project['id']); ?>"><?php _e('Edit', 'projects-map'); ?></a> |
                                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display:inline;">
                                    <?php wp_nonce_field('delete_project_' . $project['id']); ?>
                                    <input type="hidden" name="action" value="delete_project">
                                    <input type="hidden" name="id" value="<?php echo $project['id']; ?>">
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

    public function add_project_page() {
        if (isset($_GET['id'])) {
            $project = Projects_Map_DB::get_project($_GET['id']);
            if (!$project) {
                wp_die(__('Project not found', 'projects-map'));
            }
            if (!isset($project['color'])) $project['color'] = 'blue';
        } else {
            $project = array('id' => '', 'country' => '', 'lat' => '', 'lng' => '', 'title' => '', 'description' => '', 'image' => '', 'url' => '', 'color' => 'blue');
        }
        ?>
        <div class="wrap">
            <h1><?php echo isset($_GET['id']) ? __('Edit Project', 'projects-map') : __('Add New Project', 'projects-map'); ?></h1>
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
                <?php wp_nonce_field(isset($_GET['id']) ? 'edit_project_' . $_GET['id'] : 'add_project'); ?>
                <input type="hidden" name="action" value="<?php echo isset($_GET['id']) ? 'edit_project' : 'add_project'; ?>">
                <?php if (isset($_GET['id'])): ?>
                    <input type="hidden" name="id" value="<?php echo $_GET['id']; ?>">
                <?php endif; ?>
                <table class="form-table">
                    <tr>
                        <th><label for="country"><?php _e('Country', 'projects-map'); ?></label></th>
                        <td><input type="text" name="country" id="country" value="<?php echo esc_attr($project['country']); ?>" required class="projects-map-input"></td>
                    </tr>
                    <tr>
                        <th><label for="lat"><?php _e('Latitude', 'projects-map'); ?></label></th>
                        <td><input type="number" step="any" name="lat" id="lat" value="<?php echo esc_attr($project['lat']); ?>" required class="projects-map-input"></td>
                    </tr>
                    <tr>
                        <th><label for="lng"><?php _e('Longitude', 'projects-map'); ?></label></th>
                        <td><input type="number" step="any" name="lng" id="lng" value="<?php echo esc_attr($project['lng']); ?>" required class="projects-map-input"></td>
                    </tr>
                    <tr>
                        <th><label for="title"><?php _e('Title', 'projects-map'); ?></label></th>
                        <td><input type="text" name="title" id="title" value="<?php echo esc_attr($project['title']); ?>" required class="projects-map-input"></td>
                    </tr>
                    <tr>
                        <th><label for="description"><?php _e('Description', 'projects-map'); ?></label></th>
                        <td><textarea name="description" id="description" rows="5" required class="projects-map-input"><?php echo esc_textarea($project['description']); ?></textarea></td>
                    </tr>
                    <tr>
                        <th><label for="image"><?php _e('Image', 'projects-map'); ?></label></th>
                        <td>
                            <input type="text" name="image" id="image" value="<?php echo esc_attr($project['image']); ?>" class="projects-map-input">
                            <button type="button" class="button" id="upload_image_button"><?php _e('Upload Image', 'projects-map'); ?></button>
                            <?php if ($project['image']): ?>
                                <br><img src="<?php echo esc_url($project['image']); ?>" style="max-width:100px; margin-top:5px;">
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="url"><?php _e('Project URL', 'projects-map'); ?></label></th>
                        <td><input type="url" name="url" id="url" value="<?php echo esc_attr($project['url']); ?>" class="projects-map-input"></td>
                    </tr>
                    <tr>
                        <th><label for="color"><?php _e('Marker Color', 'projects-map'); ?></label></th>
                        <td><input type="color" name="color" id="color" value="<?php echo esc_attr($project['color']); ?>" class="projects-map-input"></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function info_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Projects Map Info', 'projects-map'); ?></h1>
            <p><?php _e('To display the projects map on your site, use the following shortcode:', 'projects-map'); ?></p>
            <code>[projects_map]</code>
            <h2><?php _e('Shortcode Attributes', 'projects-map'); ?></h2>
            <ul>
                <li><code>height</code>: <?php _e('Map height (default: 500px)', 'projects-map'); ?></li>
                <li><code>new_tab</code>: <?php _e('Open links in new tab (0 or 1, default: 0)', 'projects-map'); ?></li>
                <li><code>debug</code>: <?php _e('Enable debug mode (0 or 1, default: 0)', 'projects-map'); ?></li>
            </ul>
            <p><?php _e('Example: [projects_map height="600px" new_tab="1" debug="1"]', 'projects-map'); ?></p>
            <h2><?php _e('Debug Mode', 'projects-map'); ?></h2>
            <p><?php _e('When debug mode is enabled, detailed information is logged to the browser console to help troubleshoot issues:', 'projects-map'); ?></p>
            <ul>
                <li><?php _e('jQuery loading status', 'projects-map'); ?></li>
                <li><?php _e('Map container existence', 'projects-map'); ?></li>
                <li><?php _e('Project data and count', 'projects-map'); ?></li>
                <li><?php _e('Marker creation process', 'projects-map'); ?></li>
                <li><?php _e('JavaScript errors', 'projects-map'); ?></li>
            </ul>
            <h2><?php _e('Instructions', 'projects-map'); ?></h2>
            <ol>
                <li><?php _e('Add projects from the "All Projects" menu.', 'projects-map'); ?></li>
                <li><?php _e('Each project needs a country, latitude, longitude, title, description, and optionally an image and URL.', 'projects-map'); ?></li>
                <li><?php _e('Insert the shortcode [projects_map] into any page or post to display the map.', 'projects-map'); ?></li>
                <li><?php _e('The map uses clustering: zoom out to see project counts per region, zoom in to see individual projects.', 'projects-map'); ?></li>
            </ol>
        </div>
        <?php
    }

    public function handle_add_project() {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'add_project') || !current_user_can('edit_posts')) {
            wp_die(__('Security check failed', 'projects-map'));
        }
        $data = array(
            'country' => sanitize_text_field($_POST['country']),
            'lat' => floatval($_POST['lat']),
            'lng' => floatval($_POST['lng']),
            'title' => sanitize_text_field($_POST['title']),
            'description' => sanitize_textarea_field($_POST['description']),
            'image' => esc_url_raw($_POST['image']),
            'url' => esc_url_raw($_POST['url']),
            'color' => sanitize_hex_color($_POST['color']) ?: 'blue'
        );
        Projects_Map_DB::insert_project($data);
        wp_redirect(admin_url('admin.php?page=projects-map'));
        exit;
    }

    public function handle_edit_project() {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'edit_project_' . $_POST['id']) || !current_user_can('edit_posts')) {
            wp_die(__('Security check failed', 'projects-map'));
        }
        $data = array(
            'country' => sanitize_text_field($_POST['country']),
            'lat' => floatval($_POST['lat']),
            'lng' => floatval($_POST['lng']),
            'title' => sanitize_text_field($_POST['title']),
            'description' => sanitize_textarea_field($_POST['description']),
            'image' => esc_url_raw($_POST['image']),
            'url' => esc_url_raw($_POST['url']),
            'color' => sanitize_hex_color($_POST['color']) ?: 'blue'
        );
        Projects_Map_DB::update_project($_POST['id'], $data);
        wp_redirect(admin_url('admin.php?page=projects-map'));
        exit;
    }

    public function handle_delete_project() {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'delete_project_' . $_POST['id']) || !current_user_can('edit_posts')) {
            wp_die(__('Security check failed', 'projects-map'));
        }
        Projects_Map_DB::delete_project($_POST['id']);
        wp_redirect(admin_url('admin.php?page=projects-map'));
        exit;
    }
}
?>