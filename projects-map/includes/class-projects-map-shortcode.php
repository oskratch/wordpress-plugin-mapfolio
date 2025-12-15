<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Projects_Map_Shortcode {

    public function __construct() {
        add_shortcode('projects_map', array($this, 'render_map'));
    }

    public function render_map($atts) {
        $atts = shortcode_atts(array(
            'height' => get_option('projects_map_height', '500px'),
            'new_tab' => get_option('projects_map_new_tab', 0),
            'debug' => 0
        ), $atts);

        $width = '100%';
        $height = esc_attr($atts['height']);
        $new_tab = intval($atts['new_tab']);
        $debug = intval($atts['debug']);

        // Get categories with projects
        $categories = Projects_Map_DB::get_categories_with_projects();

        ob_start();
        ?>
        <div id="projects-map" data-new-tab="<?php echo $new_tab; ?>" data-debug="<?php echo $debug; ?>" style="width: <?php echo $width; ?>; height: <?php echo $height; ?>;"></div>
        <?php if (!empty($categories)): ?>
            <div class="projects-map-legend" style="margin-top: 20px;">
                <ul style="list-style: none; padding: 0; display: flex; flex-wrap: wrap; gap: 15px;">
                    <?php foreach ($categories as $category): ?>
                        <li style="display: flex; align-items: center; font-size: 14px; margin-right: 10px;">
                            <span style="display: inline-block; width: 20px; height: 20px; background-color: <?php echo esc_attr($category['color']); ?>; margin-right: 8px; border-radius: 50%;"></span>
                            <span><?php echo esc_html($category['title']); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <?php
        return ob_get_clean();
    }
}
?>