<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Mapfolio_Admin {

    /** @var array Page hook suffixes returned by add_menu_page()/add_submenu_page(), keyed by page. */
    private $hooks = array();

    /** @var Mapfolio_Locations_Table|null */
    private $locations_table = null;

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('admin_notices', array($this, 'show_notices'));
        add_filter('removable_query_args', array($this, 'removable_query_args'));
        add_filter('set_screen_option_mapfolio_locations_per_page', array($this, 'set_per_page_option'), 10, 3);
        add_filter('plugin_action_links_' . plugin_basename(MAPFOLIO_PLUGIN_FILE), array($this, 'plugin_action_links'));

        foreach (array('save_map', 'delete_map', 'save_location', 'delete_location', 'save_category', 'delete_category') as $action) {
            add_action('admin_post_mapfolio_' . $action, array($this, 'handle_' . $action));
        }
    }

    // ==================== MENU, ASSETS & NOTICES ====================

    public function add_admin_menu() {
        $cap = mapfolio_capability();

        $this->hooks['maps'] = add_menu_page(
            __('Maps', 'mapfolio'),
            'Mapfolio',
            $cap,
            'mapfolio',
            array($this, 'maps_page'),
            'dashicons-location-alt',
            20
        );
        add_submenu_page('mapfolio', __('Maps', 'mapfolio'), __('Maps', 'mapfolio'), $cap, 'mapfolio', array($this, 'maps_page'));
        $this->hooks['locations']  = add_submenu_page('mapfolio', __('Locations', 'mapfolio'), __('Locations', 'mapfolio'), $cap, 'mapfolio-locations', array($this, 'locations_page'));
        $this->hooks['categories'] = add_submenu_page('mapfolio', __('Categories', 'mapfolio'), __('Categories', 'mapfolio'), $cap, 'mapfolio-categories', array($this, 'categories_page'));
        $this->hooks['settings']   = add_submenu_page('mapfolio', __('Mapfolio Settings', 'mapfolio'), __('Settings', 'mapfolio'), 'manage_options', 'mapfolio-settings', array($this, 'settings_page'));
        $this->hooks['help']       = add_submenu_page('mapfolio', __('Mapfolio Help', 'mapfolio'), __('Help', 'mapfolio'), $cap, 'mapfolio-help', array($this, 'help_page'));

        add_action('load-' . $this->hooks['locations'], array($this, 'load_locations_page'));
    }

    public function enqueue_assets($hook) {
        if (!in_array($hook, $this->hooks, true)) {
            return;
        }

        wp_enqueue_style('mapfolio-admin', MAPFOLIO_PLUGIN_URL . 'assets/css/admin.css', array(), MAPFOLIO_VERSION);
        $deps = array('jquery');

        // Location form: media uploader + map picker
        if ($hook === $this->hooks['locations'] && $this->is_form_view()) {
            wp_enqueue_media();
            mapfolio_register_frontend_assets();
            wp_enqueue_style('mapfolio-leaflet');
            $deps[] = 'mapfolio-leaflet';
        }

        wp_enqueue_script('mapfolio-admin', MAPFOLIO_PLUGIN_URL . 'assets/js/admin.js', $deps, MAPFOLIO_VERSION, true);
        wp_localize_script('mapfolio-admin', 'mapfolioAdmin', array(
            'language' => substr(get_user_locale(), 0, 2),
            'strings'  => array(
                'choose_image'   => __('Choose image', 'mapfolio'),
                'use_image'      => __('Use this image', 'mapfolio'),
                'copied'         => __('Copied!', 'mapfolio'),
                'searching'      => __('Searching…', 'mapfolio'),
                'search_empty'   => __('No results found.', 'mapfolio'),
                'search_error'   => __('The search failed. Please try again or set the position on the map.', 'mapfolio'),
            ),
        ));
    }

    /**
     * Notices shown after a redirect, keyed by the "mapfolio_notice" query arg.
     */
    public function show_notices() {
        if (empty($_GET['page']) || strpos($_GET['page'], 'mapfolio') !== 0 || empty($_GET['mapfolio_notice'])) {
            return;
        }

        $count    = isset($_GET['mapfolio_count']) ? absint($_GET['mapfolio_count']) : 0;
        $messages = array(
            'map_saved'         => __('Map saved.', 'mapfolio'),
            'map_deleted'       => __('Map deleted. Its locations have been moved to the default map.', 'mapfolio'),
            'location_saved'    => __('Location saved.', 'mapfolio'),
            'location_deleted'  => __('Location deleted.', 'mapfolio'),
            /* translators: %d: number of deleted locations */
            'locations_deleted' => sprintf(_n('%d location deleted.', '%d locations deleted.', $count, 'mapfolio'), $count),
            'category_saved'    => __('Category saved.', 'mapfolio'),
            'category_deleted'  => __('Category deleted. Its locations have been moved to the default category.', 'mapfolio'),
        );

        $code = sanitize_key($_GET['mapfolio_notice']);
        if (isset($messages[$code])) {
            printf('<div class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html($messages[$code]));
        }
    }

    public function removable_query_args($args) {
        $args[] = 'mapfolio_notice';
        $args[] = 'mapfolio_count';
        return $args;
    }

    public function plugin_action_links($links) {
        array_unshift($links, sprintf('<a href="%s">%s</a>', esc_url(admin_url('admin.php?page=mapfolio-settings')), esc_html__('Settings', 'mapfolio')));
        return $links;
    }

    public function set_per_page_option($status, $option, $value) {
        return max(1, min(999, (int) $value));
    }

    // ==================== HELPERS ====================

    private function page_url($page, $args = array()) {
        return add_query_arg($args, admin_url('admin.php?page=' . $page));
    }

    private function redirect($page, $args = array()) {
        wp_safe_redirect($this->page_url($page, $args));
        exit;
    }

    /**
     * Whether the current page shows the add/edit form instead of the list.
     */
    private function is_form_view() {
        return isset($_GET['action']) && in_array($_GET['action'], array('new', 'edit'), true);
    }

    private function requested_id() {
        return isset($_GET['id']) ? absint($_GET['id']) : 0;
    }

    private function check_request($nonce_action, $capability = null) {
        if (!current_user_can($capability ? $capability : mapfolio_capability())) {
            wp_die(esc_html__('Sorry, you are not allowed to do that.', 'mapfolio'), 403);
        }
        check_admin_referer($nonce_action);
    }

    private function fail($message) {
        wp_die(esc_html($message), esc_html__('Error', 'mapfolio'), array('back_link' => true));
    }

    private function post_text($key) {
        return isset($_POST[$key]) ? sanitize_text_field(wp_unslash($_POST[$key])) : '';
    }

    private function post_textarea($key) {
        return isset($_POST[$key]) ? sanitize_textarea_field(wp_unslash($_POST[$key])) : '';
    }

    private function post_url($key) {
        return isset($_POST[$key]) ? esc_url_raw(wp_unslash($_POST[$key]), array('http', 'https')) : '';
    }

    private function delete_link($action, $id, $confirm) {
        $url = wp_nonce_url(admin_url('admin-post.php?action=mapfolio_' . $action . '&id=' . $id), 'mapfolio_' . $action . '_' . $id);
        return sprintf(
            '<a href="%s" class="submitdelete mapfolio-confirm" data-confirm="%s">%s</a>',
            esc_url($url),
            esc_attr($confirm),
            esc_html__('Delete', 'mapfolio')
        );
    }

    private function color_swatch($color) {
        return '<span class="mapfolio-swatch" style="background-color: ' . esc_attr($color ? $color : MAPFOLIO_DEFAULT_COLOR) . ';"></span>';
    }

    private function shortcode_for($map) {
        return '[mapfolio map="' . $map['slug'] . '"]';
    }

    // ==================== MAPS ====================

    public function maps_page() {
        if ($this->is_form_view()) {
            $this->map_form();
            return;
        }

        $maps   = Mapfolio_DB::get_all_maps();
        $counts = Mapfolio_DB::get_location_counts_by_map();
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e('Maps', 'mapfolio'); ?></h1>
            <a href="<?php echo esc_url($this->page_url('mapfolio', array('action' => 'new'))); ?>" class="page-title-action"><?php esc_html_e('Add new map', 'mapfolio'); ?></a>
            <hr class="wp-header-end">

            <p><?php esc_html_e('Each map shows its own set of locations. Copy a map\'s shortcode and paste it into any page or post.', 'mapfolio'); ?></p>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col" class="column-primary"><?php esc_html_e('Title', 'mapfolio'); ?></th>
                        <th scope="col"><?php esc_html_e('Shortcode', 'mapfolio'); ?></th>
                        <th scope="col" class="mapfolio-col-count"><?php esc_html_e('Locations', 'mapfolio'); ?></th>
                        <th scope="col"><?php esc_html_e('Description', 'mapfolio'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($maps as $map):
                        $id         = (int) $map['id'];
                        $count      = isset($counts[$id]) ? (int) $counts[$id] : 0;
                        $edit_url   = $this->page_url('mapfolio', array('action' => 'edit', 'id' => $id));
                        $is_default = ($id === Mapfolio_DB::DEFAULT_MAP_ID);
                    ?>
                        <tr>
                            <td class="column-primary">
                                <strong>
                                    <a class="row-title" href="<?php echo esc_url($edit_url); ?>"><?php echo esc_html($map['title']); ?></a>
                                    <?php if ($is_default): ?> — <span class="post-state"><?php esc_html_e('Default', 'mapfolio'); ?></span><?php endif; ?>
                                </strong>
                                <div class="row-actions">
                                    <span class="edit"><a href="<?php echo esc_url($edit_url); ?>"><?php esc_html_e('Edit', 'mapfolio'); ?></a> | </span>
                                    <span><a href="<?php echo esc_url($this->page_url('mapfolio-locations', array('map_id' => $id))); ?>"><?php esc_html_e('View locations', 'mapfolio'); ?></a> | </span>
                                    <span><a href="<?php echo esc_url($this->page_url('mapfolio-locations', array('action' => 'new', 'map_id' => $id))); ?>"><?php esc_html_e('Add location', 'mapfolio'); ?></a></span>
                                    <?php if (!$is_default): ?>
                                        <span class="trash"> | <?php echo $this->delete_link('delete_map', $id, __('Delete this map? Its locations will not be deleted: they will be moved to the default map.', 'mapfolio')); ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><?php $this->shortcode_field($map); ?></td>
                            <td class="mapfolio-col-count"><a href="<?php echo esc_url($this->page_url('mapfolio-locations', array('map_id' => $id))); ?>"><?php echo $count; ?></a></td>
                            <td><?php echo esc_html(wp_trim_words($map['description'], 15)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    private function shortcode_field($map) {
        ?>
        <span class="mapfolio-shortcode">
            <input type="text" readonly value="<?php echo esc_attr($this->shortcode_for($map)); ?>" class="code" onfocus="this.select();" aria-label="<?php esc_attr_e('Shortcode', 'mapfolio'); ?>">
            <button type="button" class="button button-small mapfolio-copy"><?php esc_html_e('Copy', 'mapfolio'); ?></button>
        </span>
        <?php
    }

    private function map_form() {
        $id = $this->requested_id();
        if ($id) {
            $map = Mapfolio_DB::get_map($id);
            if (!$map) {
                wp_die(esc_html__('Map not found.', 'mapfolio'));
            }
        } else {
            $map = array('id' => 0, 'title' => '', 'slug' => '', 'description' => '', 'settings' => '');
        }

        $ms         = Mapfolio_DB::get_map_settings($map);
        $ms_get     = function($key) use ($ms) {
            return isset($ms[$key]) ? (string) $ms[$key] : '';
        };
        $styles     = mapfolio_get_map_styles();
        $yes_no     = function($value) {
            return $value ? __('Yes', 'mapfolio') : __('No', 'mapfolio');
        };
        /* translators: %s: value of the global setting */
        $global_tpl = __('Global default (%s)', 'mapfolio');
        ?>
        <div class="wrap">
            <h1><?php echo $id ? esc_html__('Edit map', 'mapfolio') : esc_html__('Add new map', 'mapfolio'); ?></h1>
            <p><a href="<?php echo esc_url($this->page_url('mapfolio')); ?>">&larr; <?php esc_html_e('Back to maps', 'mapfolio'); ?></a></p>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('mapfolio_save_map_' . $id); ?>
                <input type="hidden" name="action" value="mapfolio_save_map">
                <input type="hidden" name="id" value="<?php echo (int) $id; ?>">

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="title"><?php esc_html_e('Title', 'mapfolio'); ?></label></th>
                        <td><input type="text" name="title" id="title" value="<?php echo esc_attr($map['title']); ?>" required class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="slug"><?php esc_html_e('Slug', 'mapfolio'); ?></label></th>
                        <td>
                            <input type="text" name="slug" id="slug" value="<?php echo esc_attr($map['slug']); ?>" class="regular-text code" pattern="[a-z0-9\-]+" data-mapfolio-slug-source="<?php echo $id ? '' : '#title'; ?>">
                            <p class="description"><?php esc_html_e('Used in the shortcode to identify the map. Only lowercase letters, numbers and hyphens. Leave it empty to generate it from the title.', 'mapfolio'); ?></p>
                        </td>
                    </tr>
                    <?php if ($id): ?>
                    <tr>
                        <th scope="row"><?php esc_html_e('Shortcode', 'mapfolio'); ?></th>
                        <td><?php $this->shortcode_field($map); ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <th scope="row"><label for="description"><?php esc_html_e('Description', 'mapfolio'); ?></label></th>
                        <td>
                            <textarea name="description" id="description" rows="3" class="large-text"><?php echo esc_textarea($map['description']); ?></textarea>
                            <p class="description"><?php esc_html_e('Internal note, not shown on the website.', 'mapfolio'); ?></p>
                        </td>
                    </tr>
                </table>

                <h2><?php esc_html_e('Map settings', 'mapfolio'); ?></h2>
                <p><?php esc_html_e('Override the global settings for this map only. Options left on "Global default" use the value from Mapfolio → Settings.', 'mapfolio'); ?></p>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="ms_map_style"><?php esc_html_e('Map style', 'mapfolio'); ?></label></th>
                        <td>
                            <select name="ms_map_style" id="ms_map_style">
                                <?php $global_style = mapfolio_get_setting('style'); ?>
                                <option value=""><?php echo esc_html(sprintf($global_tpl, isset($styles[$global_style]) ? $styles[$global_style] : $global_style)); ?></option>
                                <?php foreach ($styles as $key => $label): ?>
                                    <option value="<?php echo esc_attr($key); ?>" <?php selected($ms_get('map_style'), $key); ?>><?php echo esc_html($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Zoom levels', 'mapfolio'); ?></th>
                        <td>
                            <div class="mapfolio-inline-fields">
                                <label><?php esc_html_e('Minimum', 'mapfolio'); ?>
                                    <input type="number" name="ms_min_zoom" value="<?php echo esc_attr($ms_get('min_zoom')); ?>" min="1" max="18" placeholder="<?php echo (int) mapfolio_get_setting('min_zoom'); ?>" class="small-text">
                                </label>
                                <label><?php esc_html_e('Maximum', 'mapfolio'); ?>
                                    <input type="number" name="ms_max_zoom" value="<?php echo esc_attr($ms_get('max_zoom')); ?>" min="1" max="18" placeholder="<?php echo (int) mapfolio_get_setting('max_zoom'); ?>" class="small-text">
                                </label>
                                <label><?php esc_html_e('Initial', 'mapfolio'); ?>
                                    <input type="number" name="ms_default_zoom" value="<?php echo esc_attr($ms_get('default_zoom')); ?>" min="1" max="18" placeholder="<?php echo (int) mapfolio_get_setting('default_zoom'); ?>" class="small-text">
                                </label>
                            </div>
                            <p class="description"><?php esc_html_e('From 1 (whole world) to 18 (street level). Leave empty to use the global value, shown in grey.', 'mapfolio'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ms_show_legend"><?php esc_html_e('Show legend', 'mapfolio'); ?></label></th>
                        <td>
                            <select name="ms_show_legend" id="ms_show_legend">
                                <option value=""><?php echo esc_html(sprintf($global_tpl, $yes_no(mapfolio_get_setting('show_legend')))); ?></option>
                                <option value="1" <?php selected($ms_get('show_legend'), '1'); ?>><?php esc_html_e('Yes', 'mapfolio'); ?></option>
                                <option value="0" <?php selected($ms_get('show_legend'), '0'); ?>><?php esc_html_e('No', 'mapfolio'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ms_legend_filter"><?php esc_html_e('Filter by clicking the legend', 'mapfolio'); ?></label></th>
                        <td>
                            <select name="ms_legend_filter" id="ms_legend_filter">
                                <option value=""><?php echo esc_html(sprintf($global_tpl, $yes_no(mapfolio_get_setting('legend_filter')))); ?></option>
                                <option value="1" <?php selected($ms_get('legend_filter'), '1'); ?>><?php esc_html_e('Yes', 'mapfolio'); ?></option>
                                <option value="0" <?php selected($ms_get('legend_filter'), '0'); ?>><?php esc_html_e('No', 'mapfolio'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ms_legend_bg"><?php esc_html_e('Legend background color', 'mapfolio'); ?></label></th>
                        <td><?php $this->color_input('ms_legend_bg', $ms_get('legend_bg'), sprintf($global_tpl, mapfolio_get_setting('legend_bg') ? mapfolio_get_setting('legend_bg') : __('transparent', 'mapfolio'))); ?></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="ms_new_tab"><?php esc_html_e('Open links in a new tab', 'mapfolio'); ?></label></th>
                        <td>
                            <select name="ms_new_tab" id="ms_new_tab">
                                <option value=""><?php echo esc_html(sprintf($global_tpl, $yes_no(mapfolio_get_setting('new_tab')))); ?></option>
                                <option value="1" <?php selected($ms_get('new_tab'), '1'); ?>><?php esc_html_e('Yes', 'mapfolio'); ?></option>
                                <option value="0" <?php selected($ms_get('new_tab'), '0'); ?>><?php esc_html_e('No', 'mapfolio'); ?></option>
                            </select>
                        </td>
                    </tr>
                </table>

                <?php submit_button($id ? __('Update map', 'mapfolio') : __('Add map', 'mapfolio')); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Native color picker synced with a free text field (which also accepts rgba() and named colors).
     */
    private function color_input($name, $value, $placeholder = '', $hex_only = false) {
        $picker_value = preg_match('/^#[0-9a-f]{6}$/i', $value) ? $value : '#ffffff';
        printf(
            '<span class="mapfolio-color-field"><input type="color" value="%1$s" data-mapfolio-color-target="%2$s" aria-hidden="true" tabindex="-1"><input type="text" name="%2$s" id="%2$s" value="%3$s" placeholder="%4$s" class="%5$s"%6$s></span>',
            esc_attr($picker_value),
            esc_attr($name),
            esc_attr($value),
            esc_attr($placeholder),
            $hex_only ? 'small-text code' : 'regular-text code',
            $hex_only ? ' pattern="#[0-9A-Fa-f]{6}"' : ''
        );
    }

    private function collect_map_settings() {
        $settings = array();
        foreach (array('new_tab', 'show_legend', 'legend_filter') as $key) {
            if (isset($_POST['ms_' . $key]) && $_POST['ms_' . $key] !== '') {
                $settings[$key] = $_POST['ms_' . $key] ? 1 : 0;
            }
        }
        $legend_bg = mapfolio_sanitize_css_color($this->post_text('ms_legend_bg'));
        if ($legend_bg !== '') {
            $settings['legend_bg'] = $legend_bg;
        }
        $style = $this->post_text('ms_map_style');
        if (array_key_exists($style, mapfolio_get_map_styles())) {
            $settings['map_style'] = $style;
        }
        foreach (array('min_zoom', 'max_zoom', 'default_zoom') as $key) {
            if (isset($_POST['ms_' . $key]) && $_POST['ms_' . $key] !== '') {
                $settings[$key] = mapfolio_sanitize_zoom($_POST['ms_' . $key]);
            }
        }
        return $settings;
    }

    public function handle_save_map() {
        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;
        $this->check_request('mapfolio_save_map_' . $id);

        if ($id && !Mapfolio_DB::get_map($id)) {
            $this->fail(__('Map not found.', 'mapfolio'));
        }

        $title = $this->post_text('title');
        if ($title === '') {
            $this->fail(__('The title is required.', 'mapfolio'));
        }

        $slug = sanitize_title($this->post_text('slug'));
        if ($slug === '') {
            $slug = sanitize_title($title);
        }
        if ($slug === '') {
            $slug = 'map';
        }

        $data = array(
            'title'       => $title,
            'slug'        => Mapfolio_DB::unique_map_slug($slug, $id),
            'description' => $this->post_textarea('description'),
            'settings'    => wp_json_encode($this->collect_map_settings()),
        );

        if ($id) {
            Mapfolio_DB::update_map($id, $data);
        } else {
            Mapfolio_DB::insert_map($data);
        }
        $this->redirect('mapfolio', array('mapfolio_notice' => 'map_saved'));
    }

    public function handle_delete_map() {
        $id = $this->requested_id();
        $this->check_request('mapfolio_delete_map_' . $id);

        if ($id === Mapfolio_DB::DEFAULT_MAP_ID) {
            $this->fail(__('The default map cannot be deleted.', 'mapfolio'));
        }
        Mapfolio_DB::delete_map($id);
        $this->redirect('mapfolio', array('mapfolio_notice' => 'map_deleted'));
    }

    // ==================== LOCATIONS ====================

    /**
     * Runs before the locations page is rendered: sets up the list table and handles bulk actions.
     */
    public function load_locations_page() {
        if ($this->is_form_view()) {
            return;
        }
        require_once MAPFOLIO_PLUGIN_DIR . 'includes/class-mapfolio-locations-table.php';
        add_screen_option('per_page', array(
            'label'   => __('Locations per page', 'mapfolio'),
            'default' => 20,
            'option'  => 'mapfolio_locations_per_page',
        ));
        $this->locations_table = new Mapfolio_Locations_Table();
        $this->locations_table->process_bulk_action();
    }

    public function locations_page() {
        if ($this->is_form_view()) {
            $this->location_form();
            return;
        }

        $table = $this->locations_table;
        $table->prepare_items();
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e('Locations', 'mapfolio'); ?></h1>
            <?php
            $new_args = array('action' => 'new');
            if (!empty($_GET['map_id'])) {
                $new_args['map_id'] = absint($_GET['map_id']);
            }
            ?>
            <a href="<?php echo esc_url($this->page_url('mapfolio-locations', $new_args)); ?>" class="page-title-action"><?php esc_html_e('Add new location', 'mapfolio'); ?></a>
            <?php if (isset($_GET['s']) && $_GET['s'] !== ''): ?>
                <span class="subtitle">
                    <?php
                    /* translators: %s: search query */
                    printf(esc_html__('Search results for: %s', 'mapfolio'), '<strong>' . esc_html(wp_unslash($_GET['s'])) . '</strong>');
                    ?>
                </span>
            <?php endif; ?>
            <hr class="wp-header-end">

            <form method="get">
                <input type="hidden" name="page" value="mapfolio-locations">
                <?php
                $table->search_box(__('Search locations', 'mapfolio'), 'mapfolio-location');
                $table->display();
                ?>
            </form>
        </div>
        <?php
    }

    private function location_form() {
        $id = $this->requested_id();
        if ($id) {
            $location = Mapfolio_DB::get_location($id);
            if (!$location) {
                wp_die(esc_html__('Location not found.', 'mapfolio'));
            }
        } else {
            $location = array(
                'id'          => 0,
                'map_id'      => isset($_GET['map_id']) ? absint($_GET['map_id']) : Mapfolio_DB::DEFAULT_MAP_ID,
                'category_id' => Mapfolio_DB::DEFAULT_CATEGORY_ID,
                'country'     => '',
                'lat'         => '',
                'lng'         => '',
                'title'       => '',
                'description' => '',
                'image'       => '',
                'url'         => '',
                'link_text'   => '',
            );
        }

        $maps       = Mapfolio_DB::get_all_maps();
        $categories = Mapfolio_DB::get_all_categories();
        ?>
        <div class="wrap">
            <h1><?php echo $id ? esc_html__('Edit location', 'mapfolio') : esc_html__('Add new location', 'mapfolio'); ?></h1>
            <p><a href="<?php echo esc_url($this->page_url('mapfolio-locations')); ?>">&larr; <?php esc_html_e('Back to locations', 'mapfolio'); ?></a></p>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="mapfolio-location-form">
                <?php wp_nonce_field('mapfolio_save_location_' . $id); ?>
                <input type="hidden" name="action" value="mapfolio_save_location">
                <input type="hidden" name="id" value="<?php echo (int) $id; ?>">

                <h2><?php esc_html_e('Content', 'mapfolio'); ?></h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="title"><?php esc_html_e('Title', 'mapfolio'); ?></label></th>
                        <td><input type="text" name="title" id="title" value="<?php echo esc_attr($location['title']); ?>" required class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="map_id"><?php esc_html_e('Map', 'mapfolio'); ?></label></th>
                        <td>
                            <select name="map_id" id="map_id">
                                <?php foreach ($maps as $map): ?>
                                    <option value="<?php echo (int) $map['id']; ?>" <?php selected((int) $location['map_id'], (int) $map['id']); ?>><?php echo esc_html($map['title']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="category_id"><?php esc_html_e('Category', 'mapfolio'); ?></label></th>
                        <td>
                            <select name="category_id" id="category_id">
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo (int) $category['id']; ?>" <?php selected((int) $location['category_id'], (int) $category['id']); ?>><?php echo esc_html($category['title']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">
                                <?php esc_html_e('The category sets the marker color.', 'mapfolio'); ?>
                                <a href="<?php echo esc_url($this->page_url('mapfolio-categories')); ?>"><?php esc_html_e('Manage categories', 'mapfolio'); ?></a>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="description"><?php esc_html_e('Description', 'mapfolio'); ?></label></th>
                        <td><textarea name="description" id="description" rows="4" class="large-text"><?php echo esc_textarea($location['description']); ?></textarea></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="image"><?php esc_html_e('Image', 'mapfolio'); ?></label></th>
                        <td>
                            <div class="mapfolio-image-field">
                                <input type="url" name="image" id="image" value="<?php echo esc_attr($location['image']); ?>" class="regular-text code" placeholder="https://">
                                <button type="button" class="button" data-mapfolio-media-target="#image"><?php esc_html_e('Select image', 'mapfolio'); ?></button>
                            </div>
                            <img src="<?php echo esc_url($location['image']); ?>" alt="" class="mapfolio-image-preview"<?php echo $location['image'] ? '' : ' hidden'; ?>>
                            <p class="description"><?php esc_html_e('Shown at the top of the popup. SVG images (logos) are displayed at a fixed height.', 'mapfolio'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="url"><?php esc_html_e('Link', 'mapfolio'); ?></label></th>
                        <td><input type="url" name="url" id="url" value="<?php echo esc_attr($location['url']); ?>" class="regular-text code" placeholder="https://"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="link_text"><?php esc_html_e('Link text', 'mapfolio'); ?></label></th>
                        <td>
                            <input type="text" name="link_text" id="link_text" value="<?php echo esc_attr($location['link_text']); ?>" class="regular-text" placeholder="<?php esc_attr_e('Read more', 'mapfolio'); ?>">
                            <p class="description"><?php esc_html_e('Leave empty to use "Read more".', 'mapfolio'); ?></p>
                        </td>
                    </tr>
                </table>

                <h2><?php esc_html_e('Position', 'mapfolio'); ?></h2>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="mapfolio-geocode-query"><?php esc_html_e('Find a place', 'mapfolio'); ?></label></th>
                        <td>
                            <div class="mapfolio-geocode">
                                <input type="search" id="mapfolio-geocode-query" class="regular-text" placeholder="<?php esc_attr_e('Address, city or place', 'mapfolio'); ?>">
                                <button type="button" class="button" id="mapfolio-geocode-button"><?php esc_html_e('Search', 'mapfolio'); ?></button>
                                <span class="mapfolio-geocode-status" aria-live="polite"></span>
                            </div>
                            <div id="mapfolio-location-picker" class="mapfolio-location-picker"></div>
                            <p class="description"><?php esc_html_e('Search for a place, click on the map or drag the marker to set the position. You can also type the coordinates.', 'mapfolio'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Coordinates', 'mapfolio'); ?></th>
                        <td>
                            <div class="mapfolio-inline-fields">
                                <label><?php esc_html_e('Latitude', 'mapfolio'); ?>
                                    <input type="number" step="any" min="-90" max="90" name="lat" id="lat" value="<?php echo $location['lat'] === '' ? '' : (float) $location['lat']; ?>" required>
                                </label>
                                <label><?php esc_html_e('Longitude', 'mapfolio'); ?>
                                    <input type="number" step="any" min="-180" max="180" name="lng" id="lng" value="<?php echo $location['lng'] === '' ? '' : (float) $location['lng']; ?>" required>
                                </label>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="country"><?php esc_html_e('Country', 'mapfolio'); ?></label></th>
                        <td>
                            <input type="text" name="country" id="country" value="<?php echo esc_attr($location['country']); ?>" class="regular-text">
                            <p class="description"><?php esc_html_e('Optional. Only used to search and sort locations in the admin.', 'mapfolio'); ?></p>
                        </td>
                    </tr>
                </table>

                <?php submit_button($id ? __('Update location', 'mapfolio') : __('Add location', 'mapfolio')); ?>
            </form>
        </div>
        <?php
    }

    public function handle_save_location() {
        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;
        $this->check_request('mapfolio_save_location_' . $id);

        if ($id && !Mapfolio_DB::get_location($id)) {
            $this->fail(__('Location not found.', 'mapfolio'));
        }

        $title = $this->post_text('title');
        if ($title === '') {
            $this->fail(__('The title is required.', 'mapfolio'));
        }

        $lat = isset($_POST['lat']) ? wp_unslash($_POST['lat']) : '';
        $lng = isset($_POST['lng']) ? wp_unslash($_POST['lng']) : '';
        if (!is_numeric($lat) || !is_numeric($lng) || abs($lat) > 90 || abs($lng) > 180) {
            $this->fail(__('Please enter a valid latitude (-90 to 90) and longitude (-180 to 180).', 'mapfolio'));
        }

        $map_id      = isset($_POST['map_id']) ? absint($_POST['map_id']) : 0;
        $category_id = isset($_POST['category_id']) ? absint($_POST['category_id']) : 0;

        $data = array(
            'map_id'      => Mapfolio_DB::get_map($map_id) ? $map_id : Mapfolio_DB::DEFAULT_MAP_ID,
            'category_id' => Mapfolio_DB::get_category($category_id) ? $category_id : Mapfolio_DB::DEFAULT_CATEGORY_ID,
            'title'       => $title,
            'description' => $this->post_textarea('description'),
            'image'       => $this->post_url('image'),
            'url'         => $this->post_url('url'),
            'link_text'   => $this->post_text('link_text'),
            'lat'         => (float) $lat,
            'lng'         => (float) $lng,
            'country'     => $this->post_text('country'),
        );

        if ($id) {
            Mapfolio_DB::update_location($id, $data);
        } else {
            Mapfolio_DB::insert_location($data);
        }
        $this->redirect('mapfolio-locations', array('mapfolio_notice' => 'location_saved'));
    }

    public function handle_delete_location() {
        $id = $this->requested_id();
        $this->check_request('mapfolio_delete_location_' . $id);
        Mapfolio_DB::delete_location($id);
        $this->redirect('mapfolio-locations', array('mapfolio_notice' => 'location_deleted'));
    }

    // ==================== CATEGORIES ====================

    public function categories_page() {
        if ($this->is_form_view()) {
            $this->category_form();
            return;
        }

        $categories = Mapfolio_DB::get_all_categories();
        $counts     = Mapfolio_DB::get_location_counts_by_category();
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e('Categories', 'mapfolio'); ?></h1>
            <a href="<?php echo esc_url($this->page_url('mapfolio-categories', array('action' => 'new'))); ?>" class="page-title-action"><?php esc_html_e('Add new category', 'mapfolio'); ?></a>
            <hr class="wp-header-end">

            <p><?php esc_html_e('Categories are shared by all maps. They set the marker color and are listed in the legend below each map, in the order shown here.', 'mapfolio'); ?></p>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col" class="column-primary"><?php esc_html_e('Title', 'mapfolio'); ?></th>
                        <th scope="col"><?php esc_html_e('Color', 'mapfolio'); ?></th>
                        <th scope="col" class="mapfolio-col-count"><?php esc_html_e('Locations', 'mapfolio'); ?></th>
                        <th scope="col" class="mapfolio-col-count"><?php esc_html_e('Order', 'mapfolio'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $category):
                        $id         = (int) $category['id'];
                        $count      = isset($counts[$id]) ? (int) $counts[$id] : 0;
                        $edit_url   = $this->page_url('mapfolio-categories', array('action' => 'edit', 'id' => $id));
                        $is_default = ($id === Mapfolio_DB::DEFAULT_CATEGORY_ID);
                    ?>
                        <tr>
                            <td class="column-primary">
                                <strong>
                                    <a class="row-title" href="<?php echo esc_url($edit_url); ?>"><?php echo esc_html($category['title']); ?></a>
                                    <?php if ($is_default): ?> — <span class="post-state"><?php esc_html_e('Default', 'mapfolio'); ?></span><?php endif; ?>
                                </strong>
                                <div class="row-actions">
                                    <span class="edit"><a href="<?php echo esc_url($edit_url); ?>"><?php esc_html_e('Edit', 'mapfolio'); ?></a></span>
                                    <?php if (!$is_default): ?>
                                        <span class="trash"> | <?php echo $this->delete_link('delete_category', $id, __('Delete this category? Its locations will not be deleted: they will be moved to the default category.', 'mapfolio')); ?></span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><?php echo $this->color_swatch($category['color']); ?> <code><?php echo esc_html($category['color']); ?></code></td>
                            <td class="mapfolio-col-count"><a href="<?php echo esc_url($this->page_url('mapfolio-locations', array('category_id' => $id))); ?>"><?php echo $count; ?></a></td>
                            <td class="mapfolio-col-count"><?php echo (int) $category['sort_order']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    private function category_form() {
        $id = $this->requested_id();
        if ($id) {
            $category = Mapfolio_DB::get_category($id);
            if (!$category) {
                wp_die(esc_html__('Category not found.', 'mapfolio'));
            }
        } else {
            $category = array('id' => 0, 'title' => '', 'color' => MAPFOLIO_DEFAULT_COLOR, 'sort_order' => 0);
        }
        ?>
        <div class="wrap">
            <h1><?php echo $id ? esc_html__('Edit category', 'mapfolio') : esc_html__('Add new category', 'mapfolio'); ?></h1>
            <p><a href="<?php echo esc_url($this->page_url('mapfolio-categories')); ?>">&larr; <?php esc_html_e('Back to categories', 'mapfolio'); ?></a></p>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <?php wp_nonce_field('mapfolio_save_category_' . $id); ?>
                <input type="hidden" name="action" value="mapfolio_save_category">
                <input type="hidden" name="id" value="<?php echo (int) $id; ?>">
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="title"><?php esc_html_e('Title', 'mapfolio'); ?></label></th>
                        <td><input type="text" name="title" id="title" value="<?php echo esc_attr($category['title']); ?>" required class="regular-text"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="color"><?php esc_html_e('Color', 'mapfolio'); ?></label></th>
                        <td><input type="color" name="color" id="color" value="<?php echo esc_attr($category['color']); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="sort_order"><?php esc_html_e('Order', 'mapfolio'); ?></label></th>
                        <td>
                            <input type="number" name="sort_order" id="sort_order" value="<?php echo (int) $category['sort_order']; ?>" min="0" class="small-text">
                            <p class="description"><?php esc_html_e('Position in the legend. Lower numbers come first; categories with the same number are sorted alphabetically.', 'mapfolio'); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button($id ? __('Update category', 'mapfolio') : __('Add category', 'mapfolio')); ?>
            </form>
        </div>
        <?php
    }

    public function handle_save_category() {
        $id = isset($_POST['id']) ? absint($_POST['id']) : 0;
        $this->check_request('mapfolio_save_category_' . $id);

        if ($id && !Mapfolio_DB::get_category($id)) {
            $this->fail(__('Category not found.', 'mapfolio'));
        }

        $title = $this->post_text('title');
        if ($title === '') {
            $this->fail(__('The title is required.', 'mapfolio'));
        }

        $color = isset($_POST['color']) ? sanitize_hex_color(wp_unslash($_POST['color'])) : '';
        $data  = array(
            'title'      => $title,
            'color'      => $color ? $color : MAPFOLIO_DEFAULT_COLOR,
            'sort_order' => isset($_POST['sort_order']) ? absint($_POST['sort_order']) : 0,
        );

        if ($id) {
            Mapfolio_DB::update_category($id, $data);
        } else {
            Mapfolio_DB::insert_category($data);
        }
        $this->redirect('mapfolio-categories', array('mapfolio_notice' => 'category_saved'));
    }

    public function handle_delete_category() {
        $id = $this->requested_id();
        $this->check_request('mapfolio_delete_category_' . $id);

        if ($id === Mapfolio_DB::DEFAULT_CATEGORY_ID) {
            $this->fail(__('The default category cannot be deleted.', 'mapfolio'));
        }
        Mapfolio_DB::delete_category($id);
        $this->redirect('mapfolio-categories', array('mapfolio_notice' => 'category_deleted'));
    }

    // ==================== SETTINGS ====================

    public function register_settings() {
        $defaults   = mapfolio_get_default_settings();
        $sanitizers = array(
            'height'              => function($value) { return mapfolio_sanitize_css_size($value, '500px'); },
            'height_mobile'       => function($value) {
                $value = mapfolio_sanitize_css_size($value, '350px');
                return $value === 'fit' ? '350px' : $value;
            },
            'new_tab'             => array($this, 'sanitize_checkbox'),
            'style'               => function($value) { return array_key_exists($value, mapfolio_get_map_styles()) ? $value : 'openstreetmap'; },
            'carto_api_key'       => 'sanitize_text_field',
            'show_legend'         => array($this, 'sanitize_checkbox'),
            'legend_bg'           => 'mapfolio_sanitize_css_color',
            'legend_filter'       => array($this, 'sanitize_checkbox'),
            'cluster_color_small' => function($value) { return sanitize_hex_color($value) ? sanitize_hex_color($value) : '#29B246'; },
            'cluster_color_large' => function($value) { return sanitize_hex_color($value) ? sanitize_hex_color($value) : '#E67E22'; },
            'min_zoom'            => 'mapfolio_sanitize_zoom',
            'max_zoom'            => array($this, 'sanitize_max_zoom'),
            'default_zoom'        => 'mapfolio_sanitize_zoom',
        );
        foreach ($sanitizers as $key => $callback) {
            register_setting('mapfolio_settings', 'mapfolio_' . $key, array(
                'sanitize_callback' => $callback,
                'default'           => $defaults[$key],
            ));
        }

        $page = 'mapfolio-settings';
        add_settings_section('mapfolio_display', __('Map', 'mapfolio'), null, $page);
        add_settings_section('mapfolio_zoom', __('Zoom', 'mapfolio'), null, $page);
        add_settings_section('mapfolio_legend', __('Legend', 'mapfolio'), null, $page);
        add_settings_section('mapfolio_clusters', __('Clusters', 'mapfolio'), function() {
            echo '<p>' . esc_html__('When locations are close together they are grouped in a numbered circle (cluster) until you zoom in.', 'mapfolio') . '</p>';
        }, $page);

        $fields = array(
            'height' => array('mapfolio_display', __('Height', 'mapfolio'), 'text', array(
                'description' => __('Examples: 500px, 70vh. Use "fit" to fill the height of the parent element (which must have a fixed height) with the legend below the map.', 'mapfolio'),
            )),
            'height_mobile' => array('mapfolio_display', __('Height on mobile and tablet', 'mapfolio'), 'text', array(
                'description' => __('Applied on screens up to 957px wide. Examples: 350px, 60vh.', 'mapfolio'),
            )),
            'style' => array('mapfolio_display', __('Map style', 'mapfolio'), 'select', array(
                'options'     => mapfolio_get_map_styles(),
                'description' => __('The minimal styles hide terrain, rivers and mountains so the markers stand out.', 'mapfolio'),
            )),
            'carto_api_key' => array('mapfolio_display', __('CARTO API key', 'mapfolio'), 'text', array(
                'description' => sprintf(
                    /* translators: %s: link to the CARTO API key page */
                    __('Only needed for the CARTO styles. Since August 2026 CARTO requires a key for its basemaps; without one the map shows an "API KEY REQUIRED" watermark. Get a free key at %s, or choose another style.', 'mapfolio'),
                    '<a href="https://carto.com/basemaps/apikey/" target="_blank" rel="noopener noreferrer">carto.com/basemaps/apikey</a>'
                ),
            )),
            'new_tab' => array('mapfolio_display', __('Links', 'mapfolio'), 'checkbox', array(
                'label' => __('Open the links of the popups in a new tab', 'mapfolio'),
            )),
            'min_zoom' => array('mapfolio_zoom', __('Minimum zoom', 'mapfolio'), 'number', array(
                'description' => __('How far out visitors can zoom. 1 shows the whole world.', 'mapfolio'),
            )),
            'max_zoom' => array('mapfolio_zoom', __('Maximum zoom', 'mapfolio'), 'number', array(
                'description' => __('How far in visitors can zoom. 18 is street level.', 'mapfolio'),
            )),
            'default_zoom' => array('mapfolio_zoom', __('Initial zoom', 'mapfolio'), 'number', array(
                'description' => __('The map fits all its locations on load, but never zooms in further than this level.', 'mapfolio'),
            )),
            'show_legend' => array('mapfolio_legend', __('Legend', 'mapfolio'), 'checkbox', array(
                'label' => __('Show the list of categories below the map', 'mapfolio'),
            )),
            'legend_filter' => array('mapfolio_legend', __('Filter', 'mapfolio'), 'checkbox', array(
                'label' => __('Let visitors show or hide categories by clicking them in the legend', 'mapfolio'),
            )),
            'legend_bg' => array('mapfolio_legend', __('Background color', 'mapfolio'), 'color', array(
                'placeholder' => __('transparent', 'mapfolio'),
                'description' => __('Leave empty for a transparent background. Also accepts rgba() and color names.', 'mapfolio'),
            )),
            'cluster_color_small' => array('mapfolio_clusters', __('Color (2–9 locations)', 'mapfolio'), 'color', array('hex_only' => true)),
            'cluster_color_large' => array('mapfolio_clusters', __('Color (10 or more locations)', 'mapfolio'), 'color', array('hex_only' => true)),
        );
        foreach ($fields as $key => $field) {
            list($section, $label, $type, $args) = $field;
            add_settings_field('mapfolio_' . $key, $label, array($this, 'render_setting_field'), $page, $section, array_merge($args, array(
                'key'       => $key,
                'type'      => $type,
                'label_for' => $type === 'checkbox' ? null : 'mapfolio_' . $key,
            )));
        }
    }

    public function sanitize_checkbox($value) {
        return $value ? 1 : 0;
    }

    public function sanitize_max_zoom($value) {
        $max = mapfolio_sanitize_zoom($value);
        $min = isset($_POST['mapfolio_min_zoom']) ? mapfolio_sanitize_zoom($_POST['mapfolio_min_zoom']) : (int) mapfolio_get_setting('min_zoom');
        if ($max < $min) {
            add_settings_error('mapfolio_max_zoom', 'mapfolio_max_zoom', __('The maximum zoom cannot be lower than the minimum zoom. It has been set to the minimum.', 'mapfolio'));
            $max = $min;
        }
        return $max;
    }

    public function render_setting_field($args) {
        $name  = 'mapfolio_' . $args['key'];
        $value = mapfolio_get_setting($args['key']);

        switch ($args['type']) {
            case 'checkbox':
                printf(
                    '<label><input type="checkbox" name="%s" value="1"%s> %s</label>',
                    esc_attr($name),
                    checked(1, (int) $value, false),
                    esc_html($args['label'])
                );
                break;
            case 'select':
                echo '<select name="' . esc_attr($name) . '" id="' . esc_attr($name) . '">';
                foreach ($args['options'] as $key => $label) {
                    echo '<option value="' . esc_attr($key) . '"' . selected($value, $key, false) . '>' . esc_html($label) . '</option>';
                }
                echo '</select>';
                break;
            case 'number':
                printf('<input type="number" name="%1$s" id="%1$s" value="%2$d" min="1" max="18" class="small-text">', esc_attr($name), (int) $value);
                break;
            case 'color':
                $this->color_input($name, $value, isset($args['placeholder']) ? $args['placeholder'] : '', !empty($args['hex_only']));
                break;
            default:
                printf('<input type="text" name="%1$s" id="%1$s" value="%2$s" class="regular-text" autocomplete="off">', esc_attr($name), esc_attr($value));
        }

        if (!empty($args['description'])) {
            echo '<p class="description">' . wp_kses($args['description'], array('a' => array('href' => true, 'target' => true, 'rel' => true))) . '</p>';
        }
    }

    public function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Mapfolio Settings', 'mapfolio'); ?></h1>
            <?php settings_errors(); ?>
            <p><?php esc_html_e('Default values for all maps. Each map can override some of them in its own settings, and each shortcode can override them again with attributes.', 'mapfolio'); ?></p>
            <form method="post" action="options.php">
                <?php
                settings_fields('mapfolio_settings');
                do_settings_sections('mapfolio-settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    // ==================== HELP ====================

    public function help_page() {
        $attributes = array(
            'map'           => __('Slug or ID of the map. Without it, the default map is shown.', 'mapfolio'),
            'height'        => __('Height of the map, e.g. 500px, 70vh, or fit to fill the parent element.', 'mapfolio'),
            'mobile_height' => __('Height on screens up to 957px wide, e.g. 350px or 60vh.', 'mapfolio'),
            'map_style'     => __('Map style: openstreetmap, cartodb-positron, cartodb-voyager or esri-gray.', 'mapfolio'),
            'min_zoom'      => __('Minimum zoom level (1–18).', 'mapfolio'),
            'max_zoom'      => __('Maximum zoom level (1–18).', 'mapfolio'),
            'default_zoom'  => __('Initial zoom level (1–18).', 'mapfolio'),
            'show_legend'   => __('Show the category legend: 1 (yes) or 0 (no).', 'mapfolio'),
            'legend_bg'     => __('Legend background color, e.g. #ffffff or rgba(255,255,255,0.9).', 'mapfolio'),
            'legend_filter' => __('Let visitors filter the map by clicking the legend: 1 (yes) or 0 (no).', 'mapfolio'),
            'new_tab'       => __('Open the links in a new tab: 1 (yes) or 0 (no).', 'mapfolio'),
            'debug'         => __('Log debugging information to the browser console: 1 (yes) or 0 (no).', 'mapfolio'),
        );
        ?>
        <div class="wrap mapfolio-help">
            <h1><?php esc_html_e('Mapfolio Help', 'mapfolio'); ?></h1>

            <h2><?php esc_html_e('Getting started', 'mapfolio'); ?></h2>
            <ol>
                <li><?php printf(
                    /* translators: %s: link to the Maps page */
                    esc_html__('Go to %s. A default map is already created; you can rename it or add more maps.', 'mapfolio'),
                    '<a href="' . esc_url($this->page_url('mapfolio')) . '">' . esc_html__('Maps', 'mapfolio') . '</a>'
                ); ?></li>
                <li><?php printf(
                    /* translators: %s: link to the Categories page */
                    esc_html__('In %s, create the categories you need and choose their colors.', 'mapfolio'),
                    '<a href="' . esc_url($this->page_url('mapfolio-categories')) . '">' . esc_html__('Categories', 'mapfolio') . '</a>'
                ); ?></li>
                <li><?php printf(
                    /* translators: %s: link to the Locations page */
                    esc_html__('In %s, add your locations. Search for a place or click on the map to set each position.', 'mapfolio'),
                    '<a href="' . esc_url($this->page_url('mapfolio-locations')) . '">' . esc_html__('Locations', 'mapfolio') . '</a>'
                ); ?></li>
                <li><?php esc_html_e('Copy the shortcode of the map from the Maps list and paste it into any page or post.', 'mapfolio'); ?></li>
            </ol>

            <h2><?php esc_html_e('Shortcode', 'mapfolio'); ?></h2>
            <p><code>[mapfolio map="main-map"]</code></p>
            <p><?php esc_html_e('Optional attributes:', 'mapfolio'); ?></p>
            <table class="widefat striped mapfolio-help-table">
                <tbody>
                    <?php foreach ($attributes as $attribute => $description): ?>
                        <tr>
                            <td><code><?php echo esc_html($attribute); ?></code></td>
                            <td><?php echo esc_html($description); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p><?php esc_html_e('Example:', 'mapfolio'); ?> <code>[mapfolio map="main-map" height="600px" mobile_height="400px" map_style="cartodb-positron" show_legend="0"]</code></p>

            <h2><?php esc_html_e('Which setting wins?', 'mapfolio'); ?></h2>
            <ol>
                <li><strong><?php esc_html_e('Shortcode attribute', 'mapfolio'); ?></strong> — <?php esc_html_e('applies only to that shortcode.', 'mapfolio'); ?></li>
                <li><strong><?php esc_html_e('Map settings', 'mapfolio'); ?></strong> — <?php esc_html_e('apply everywhere that map is shown.', 'mapfolio'); ?></li>
                <li><strong><?php esc_html_e('Global settings', 'mapfolio'); ?></strong> — <?php esc_html_e('apply to all maps.', 'mapfolio'); ?></li>
            </ol>
            <p><?php esc_html_e('The height can only be set in the shortcode or in the global settings, because it depends on where the map is placed.', 'mapfolio'); ?></p>

            <h2><?php esc_html_e('Something does not work?', 'mapfolio'); ?></h2>
            <p><?php esc_html_e('Add debug="1" to the shortcode and open the browser console: Mapfolio will log what it loads and any errors it finds.', 'mapfolio'); ?></p>
            <p><?php printf(
                /* translators: %s: link to the GitHub repository */
                esc_html__('Documentation, bug reports and suggestions: %s', 'mapfolio'),
                '<a href="https://github.com/oskratch/wordpress-plugin-mapfolio" target="_blank" rel="noopener noreferrer">GitHub</a>'
            ); ?></p>
        </div>
        <?php
    }
}
