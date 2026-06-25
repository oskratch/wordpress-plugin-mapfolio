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
        // null = not explicitly passed; allows 3-level resolution: shortcode → map → global
        $atts = shortcode_atts(array(
            'map'          => '',
            'height'       => null,
            'mobile_height'=> null,
            'new_tab'      => null,
            'show_legend'  => null,
            'legend_bg'    => null,
            'map_style'    => null,
            'min_zoom'     => null,
            'max_zoom'     => null,
            'default_zoom' => null,
            'debug'        => 0,
        ), $atts);

        // Resolve map record
        $map_id = null;
        $map    = null;
        if (!empty($atts['map'])) {
            $map = Projects_Map_DB::get_map($atts['map']);
            if ($map) $map_id = $map['id'];
        } else {
            $maps = Projects_Map_DB::get_all_maps();
            if (!empty($maps)) {
                $map_id = $maps[0]['id'];
                $map    = $maps[0];
            }
        }

        if (!$map_id) {
            return '<p>' . __('No map found. Please create a map first.', 'projects-map') . '</p>';
        }

        // Map-level settings (JSON stored in maps.settings column)
        $ms = array();
        if (!empty($map['settings'])) {
            $decoded = json_decode($map['settings'], true);
            if (is_array($decoded)) $ms = $decoded;
        }

        // Resolve: shortcode attr → map setting → global default
        $height        = $atts['height']        ?? $ms['height']        ?? get_option('projects_map_height', '500px');
        $mobile_height = $atts['mobile_height'] ?? $ms['mobile_height'] ?? get_option('projects_map_height_mobile', '350px');
        $new_tab       = $atts['new_tab']       ?? $ms['new_tab']       ?? get_option('projects_map_new_tab', 0);
        $show_legend   = $atts['show_legend']   ?? $ms['show_legend']   ?? get_option('projects_map_show_legend', 1);
        $legend_bg     = $atts['legend_bg']     ?? $ms['legend_bg']     ?? get_option('projects_map_legend_bg', '');
        $map_style     = $atts['map_style']     ?? $ms['map_style']     ?? get_option('projects_map_style', 'openstreetmap');
        $min_zoom      = $atts['min_zoom']      ?? $ms['min_zoom']      ?? get_option('projects_map_min_zoom', 1);
        $max_zoom      = $atts['max_zoom']      ?? $ms['max_zoom']      ?? get_option('projects_map_max_zoom', 10);
        $default_zoom  = $atts['default_zoom']  ?? $ms['default_zoom']  ?? get_option('projects_map_default_zoom', 2);
        $debug         = intval($atts['debug']);

        // Sanitize
        $height        = esc_attr($height);
        $mobile_height = esc_attr($mobile_height);
        $new_tab       = intval($new_tab);
        $show_legend   = intval($show_legend);
        $legend_bg     = sanitize_text_field($legend_bg);
        $map_style     = sanitize_text_field($map_style);
        $min_zoom      = intval($min_zoom);
        $max_zoom      = intval($max_zoom);
        $default_zoom  = max($min_zoom, min($max_zoom, intval($default_zoom)));

        $categories       = Projects_Map_DB::get_categories_with_projects($map_id);
        $map_container_id = 'projects-map-' . $map_id . '-' . uniqid();

        ob_start();
        ?>
        <div id="<?php echo $map_container_id; ?>"
             class="projects-map-container"
             data-map-id="<?php echo $map_id; ?>"
             data-new-tab="<?php echo $new_tab; ?>"
             data-debug="<?php echo $debug; ?>"
             data-map-style="<?php echo esc_attr($map_style); ?>"
             data-min-zoom="<?php echo $min_zoom; ?>"
             data-max-zoom="<?php echo $max_zoom; ?>"
             data-default-zoom="<?php echo $default_zoom; ?>"
             style="width: 100%; height: <?php echo $height; ?>;"></div>
        <style>
            @media (max-width: 957px) {
                #<?php echo esc_attr($map_container_id); ?> {
                    height: <?php echo $mobile_height; ?> !important;
                }
            }
        </style>
        <?php if ($show_legend && !empty($categories)):
            $legend_style = '';
            if ($legend_bg) {
                $legend_style .= 'background-color: ' . esc_attr($legend_bg) . '; padding: 10px;';
            }
        ?>
            <div class="projects-map-legend" style="<?php echo $legend_style; ?>">
                <ul style="list-style: none; margin: 0; padding: 0; display: flex; flex-wrap: wrap; gap: 15px;">
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