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
            'map' => '', // Map ID or slug
            'height' => get_option('projects_map_height', '500px'),
            'mobile_height' => get_option('projects_map_height_mobile', '350px'),
            'new_tab' => get_option('projects_map_new_tab', 0),
            'show_legend' => get_option('projects_map_show_legend', 1),
            'legend_bg' => get_option('projects_map_legend_bg', ''),
            'debug' => 0
        ), $atts);

        // Get map ID
        $map_id = null;
        if (!empty($atts['map'])) {
            $map = Projects_Map_DB::get_map($atts['map']);
            if ($map) {
                $map_id = $map['id'];
            }
        } else {
            // Get first map if no map specified
            $maps = Projects_Map_DB::get_all_maps();
            if (!empty($maps)) {
                $map_id = $maps[0]['id'];
            }
        }

        // If no map found, return empty
        if (!$map_id) {
            return '<p>' . __('No map found. Please create a map first.', 'projects-map') . '</p>';
        }

        $width = '100%';
        $height = esc_attr($atts['height']);
        $mobile_height = esc_attr($atts['mobile_height']);
        $new_tab = intval($atts['new_tab']);
        $show_legend = intval($atts['show_legend']);
        $legend_bg = sanitize_text_field($atts['legend_bg']);
        $debug = intval($atts['debug']);

        // Get categories with locations for this map
        $categories = Projects_Map_DB::get_categories_with_projects($map_id);

        // Generate unique map ID for this instance
        $map_container_id = 'projects-map-' . $map_id . '-' . uniqid();

        ob_start();
        ?>
        <div id="<?php echo $map_container_id; ?>" class="projects-map-container" data-map-id="<?php echo $map_id; ?>" data-new-tab="<?php echo $new_tab; ?>" data-debug="<?php echo $debug; ?>" style="width: <?php echo $width; ?>; height: <?php echo $height; ?>;"></div>
        <style>
            @media (max-width: 957px) {
                #<?php echo esc_attr($map_container_id); ?> {
                    height: <?php echo $mobile_height; ?> !important;
                }
            }
        </style>
        <?php if ($show_legend && !empty($categories)):
            $legend_style = 'margin-top: 20px;';
            if ($legend_bg) {
                $legend_style .= ' background-color: ' . esc_attr($legend_bg) . '; padding: 10px;';
            }
        ?>
            <div class="projects-map-legend" style="<?php echo $legend_style; ?>">
                <ul style="list-style: none; padding: 0; display: flex; flex-wrap: wrap; gap: 15px;">
                    <?php foreach ($categories as $category): ?>
                        <li style="display: flex; align-items: center; font-size: 14px; margin-right: 10px;">
                            <span style="display: inline-block; width: 20px; height: 20px; background-color: <?php echo esc_attr($category['color']); ?>; margin-right: 8px; border-radius: 50%;"></span>
                            <span><?php echo esc_html($category['title']); ?><?php if (!empty($category['project_count'])): ?> (<?php echo intval($category['project_count']); ?>)<?php endif; ?></span>
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