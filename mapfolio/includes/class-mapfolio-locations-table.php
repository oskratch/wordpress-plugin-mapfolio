<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Locations list in the admin: search, filter by map/category, sorting, pagination and bulk delete.
 */
class Mapfolio_Locations_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct(array(
            'singular' => 'location',
            'plural'   => 'locations',
            'ajax'     => false,
        ));
    }

    public function get_columns() {
        return array(
            'cb'       => '<input type="checkbox">',
            'title'    => __('Title', 'mapfolio'),
            'category' => __('Category', 'mapfolio'),
            'map'      => __('Map', 'mapfolio'),
            'country'  => __('Country', 'mapfolio'),
        );
    }

    protected function get_sortable_columns() {
        return array(
            'title'    => array('title', false),
            'category' => array('category', false),
            'map'      => array('map', false),
            'country'  => array('country', false),
        );
    }

    protected function get_primary_column_name() {
        return 'title';
    }

    protected function get_bulk_actions() {
        return array('delete' => __('Delete', 'mapfolio'));
    }

    private function filters() {
        return array(
            'map_id'      => isset($_REQUEST['map_id']) ? absint($_REQUEST['map_id']) : 0,
            'category_id' => isset($_REQUEST['category_id']) ? absint($_REQUEST['category_id']) : 0,
            'search'      => isset($_REQUEST['s']) ? sanitize_text_field(wp_unslash($_REQUEST['s'])) : '',
        );
    }

    public function prepare_items() {
        $per_page = $this->get_items_per_page('mapfolio_locations_per_page', 20);
        $filters  = $this->filters();

        $this->items = Mapfolio_DB::get_locations(array_merge($filters, array(
            'orderby'  => isset($_GET['orderby']) ? sanitize_key($_GET['orderby']) : 'title',
            'order'    => isset($_GET['order']) ? sanitize_key($_GET['order']) : 'asc',
            'per_page' => $per_page,
            'page'     => $this->get_pagenum(),
        )));

        $this->set_pagination_args(array(
            'total_items' => Mapfolio_DB::count_locations($filters),
            'per_page'    => $per_page,
        ));
    }

    public function process_bulk_action() {
        if ($this->current_action() !== 'delete' || empty($_REQUEST['location'])) {
            // Filter/search submissions: drop the nonce and bulk fields from the URL, like core list screens do
            if (!empty($_GET['_wp_http_referer'])) {
                wp_safe_redirect(remove_query_arg(array('_wp_http_referer', '_wpnonce', 'action', 'action2', 'filter_action', 'location'), wp_unslash($_SERVER['REQUEST_URI'])));
                exit;
            }
            return;
        }
        check_admin_referer('bulk-locations');
        if (!current_user_can(mapfolio_capability())) {
            wp_die(esc_html__('Sorry, you are not allowed to do that.', 'mapfolio'), 403);
        }

        $ids = array_filter(array_map('absint', (array) $_REQUEST['location']));
        foreach ($ids as $id) {
            Mapfolio_DB::delete_location($id);
        }

        wp_safe_redirect(add_query_arg(array(
            'page'            => 'mapfolio-locations',
            'mapfolio_notice' => 'locations_deleted',
            'mapfolio_count'  => count($ids),
        ), admin_url('admin.php')));
        exit;
    }

    protected function extra_tablenav($which) {
        if ($which !== 'top') {
            return;
        }
        $filters = $this->filters();
        ?>
        <div class="alignleft actions">
            <label for="mapfolio-filter-map" class="screen-reader-text"><?php esc_html_e('Filter by map', 'mapfolio'); ?></label>
            <select name="map_id" id="mapfolio-filter-map">
                <option value=""><?php esc_html_e('All maps', 'mapfolio'); ?></option>
                <?php foreach (Mapfolio_DB::get_all_maps() as $map): ?>
                    <option value="<?php echo (int) $map['id']; ?>" <?php selected($filters['map_id'], (int) $map['id']); ?>><?php echo esc_html($map['title']); ?></option>
                <?php endforeach; ?>
            </select>
            <label for="mapfolio-filter-category" class="screen-reader-text"><?php esc_html_e('Filter by category', 'mapfolio'); ?></label>
            <select name="category_id" id="mapfolio-filter-category">
                <option value=""><?php esc_html_e('All categories', 'mapfolio'); ?></option>
                <?php foreach (Mapfolio_DB::get_all_categories() as $category): ?>
                    <option value="<?php echo (int) $category['id']; ?>" <?php selected($filters['category_id'], (int) $category['id']); ?>><?php echo esc_html($category['title']); ?></option>
                <?php endforeach; ?>
            </select>
            <?php submit_button(__('Filter', 'mapfolio'), '', 'filter_action', false); ?>
        </div>
        <?php
    }

    public function no_items() {
        esc_html_e('No locations found.', 'mapfolio');
    }

    protected function column_cb($item) {
        return sprintf(
            '<label class="screen-reader-text" for="mapfolio-location-%1$d">%2$s</label><input type="checkbox" name="location[]" id="mapfolio-location-%1$d" value="%1$d">',
            (int) $item['id'],
            /* translators: %s: location title */
            esc_html(sprintf(__('Select %s', 'mapfolio'), $item['title']))
        );
    }

    protected function column_title($item) {
        $id       = (int) $item['id'];
        $edit_url = admin_url('admin.php?page=mapfolio-locations&action=edit&id=' . $id);
        $del_url  = wp_nonce_url(admin_url('admin-post.php?action=mapfolio_delete_location&id=' . $id), 'mapfolio_delete_location_' . $id);

        $actions = array(
            'edit'  => sprintf('<a href="%s">%s</a>', esc_url($edit_url), esc_html__('Edit', 'mapfolio')),
            'trash' => sprintf(
                '<a href="%s" class="submitdelete mapfolio-confirm" data-confirm="%s">%s</a>',
                esc_url($del_url),
                esc_attr__('Delete this location?', 'mapfolio'),
                esc_html__('Delete', 'mapfolio')
            ),
        );

        return sprintf('<strong><a class="row-title" href="%s">%s</a></strong>', esc_url($edit_url), esc_html($item['title']))
            . $this->row_actions($actions);
    }

    protected function column_category($item) {
        $color = $item['category_color'] ? $item['category_color'] : MAPFOLIO_DEFAULT_COLOR;
        return '<span class="mapfolio-swatch" style="background-color: ' . esc_attr($color) . ';"></span> '
            . esc_html($item['category_title'] ? $item['category_title'] : '—');
    }

    protected function column_map($item) {
        return esc_html($item['map_title'] ? $item['map_title'] : '—');
    }

    protected function column_country($item) {
        return esc_html($item['country']);
    }
}
