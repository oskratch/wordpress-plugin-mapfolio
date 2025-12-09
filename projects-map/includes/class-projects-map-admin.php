<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Projects_Map_Admin {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_post_add_project', array($this, 'handle_add_project'));
        add_action('admin_post_edit_project', array($this, 'handle_edit_project'));
        add_action('admin_post_delete_project', array($this, 'handle_delete_project'));
    }

    public function add_admin_menu() {
        add_menu_page(
            __('Projects Map', 'projects-map'),
            __('Projects Map', 'projects-map'),
            'manage_options',
            'projects-map',
            array($this, 'admin_page'),
            'dashicons-location-alt',
            20
        );
        add_submenu_page(
            'projects-map',
            __('All Projects', 'projects-map'),
            __('All Projects', 'projects-map'),
            'manage_options',
            'projects-map',
            array($this, 'admin_page')
        );
        add_submenu_page(
            'projects-map',
            __('Add New Project', 'projects-map'),
            __('Add New Project', 'projects-map'),
            'manage_options',
            'projects-map-add',
            array($this, 'add_project_page')
        );
        add_submenu_page(
            'projects-map',
            __('Info', 'projects-map'),
            __('Info', 'projects-map'),
            'manage_options',
            'projects-map-info',
            array($this, 'info_page')
        );
    }

    public function admin_page() {
        $projects = Projects_Map_DB::get_all_projects();
        ?>
        <div class="wrap">
            <h1><?php _e('Projects Map', 'projects-map'); ?></h1>
            <a href="<?php echo admin_url('admin.php?page=projects-map-add'); ?>" class="page-title-action"><?php _e('Add New Project', 'projects-map'); ?></a>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
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
                                    <button type="submit" onclick="return confirm('<?php _e('Are you sure?', 'projects-map'); ?>')"><?php _e('Delete', 'projects-map'); ?></button>
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
        } else {
            $project = array('id' => '', 'country' => '', 'lat' => '', 'lng' => '', 'title' => '', 'description' => '', 'image' => '', 'url' => '');
        }
        ?>
        <div class="wrap">
            <h1><?php echo isset($_GET['id']) ? __('Edit Project', 'projects-map') : __('Add New Project', 'projects-map'); ?></h1>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <?php wp_nonce_field(isset($_GET['id']) ? 'edit_project_' . $_GET['id'] : 'add_project'); ?>
                <input type="hidden" name="action" value="<?php echo isset($_GET['id']) ? 'edit_project' : 'add_project'; ?>">
                <?php if (isset($_GET['id'])): ?>
                    <input type="hidden" name="id" value="<?php echo $_GET['id']; ?>">
                <?php endif; ?>
                <table class="form-table">
                    <tr>
                        <th><label for="country"><?php _e('Country', 'projects-map'); ?></label></th>
                        <td><input type="text" name="country" id="country" value="<?php echo esc_attr($project['country']); ?>" required></td>
                    </tr>
                    <tr>
                        <th><label for="lat"><?php _e('Latitude', 'projects-map'); ?></label></th>
                        <td><input type="number" step="any" name="lat" id="lat" value="<?php echo esc_attr($project['lat']); ?>" required></td>
                    </tr>
                    <tr>
                        <th><label for="lng"><?php _e('Longitude', 'projects-map'); ?></label></th>
                        <td><input type="number" step="any" name="lng" id="lng" value="<?php echo esc_attr($project['lng']); ?>" required></td>
                    </tr>
                    <tr>
                        <th><label for="title"><?php _e('Title', 'projects-map'); ?></label></th>
                        <td><input type="text" name="title" id="title" value="<?php echo esc_attr($project['title']); ?>" required></td>
                    </tr>
                    <tr>
                        <th><label for="description"><?php _e('Description', 'projects-map'); ?></label></th>
                        <td><textarea name="description" id="description" rows="5" required><?php echo esc_textarea($project['description']); ?></textarea></td>
                    </tr>
                    <tr>
                        <th><label for="image"><?php _e('Image URL', 'projects-map'); ?></label></th>
                        <td><input type="url" name="image" id="image" value="<?php echo esc_attr($project['image']); ?>"></td>
                    </tr>
                    <tr>
                        <th><label for="url"><?php _e('Project URL', 'projects-map'); ?></label></th>
                        <td><input type="url" name="url" id="url" value="<?php echo esc_attr($project['url']); ?>"></td>
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
        if (!wp_verify_nonce($_POST['_wpnonce'], 'add_project') || !current_user_can('manage_options')) {
            wp_die(__('Security check failed', 'projects-map'));
        }
        $data = array(
            'country' => sanitize_text_field($_POST['country']),
            'lat' => floatval($_POST['lat']),
            'lng' => floatval($_POST['lng']),
            'title' => sanitize_text_field($_POST['title']),
            'description' => sanitize_textarea_field($_POST['description']),
            'image' => esc_url_raw($_POST['image']),
            'url' => esc_url_raw($_POST['url'])
        );
        Projects_Map_DB::insert_project($data);
        wp_redirect(admin_url('admin.php?page=projects-map'));
        exit;
    }

    public function handle_edit_project() {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'edit_project_' . $_POST['id']) || !current_user_can('manage_options')) {
            wp_die(__('Security check failed', 'projects-map'));
        }
        $data = array(
            'country' => sanitize_text_field($_POST['country']),
            'lat' => floatval($_POST['lat']),
            'lng' => floatval($_POST['lng']),
            'title' => sanitize_text_field($_POST['title']),
            'description' => sanitize_textarea_field($_POST['description']),
            'image' => esc_url_raw($_POST['image']),
            'url' => esc_url_raw($_POST['url'])
        );
        Projects_Map_DB::update_project($_POST['id'], $data);
        wp_redirect(admin_url('admin.php?page=projects-map'));
        exit;
    }

    public function handle_delete_project() {
        if (!wp_verify_nonce($_POST['_wpnonce'], 'delete_project_' . $_POST['id']) || !current_user_can('manage_options')) {
            wp_die(__('Security check failed', 'projects-map'));
        }
        Projects_Map_DB::delete_project($_POST['id']);
        wp_redirect(admin_url('admin.php?page=projects-map'));
        exit;
    }
}
?>