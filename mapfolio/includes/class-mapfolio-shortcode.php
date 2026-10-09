<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class Mapfolio_Shortcode {

    public function __construct() {
        add_shortcode('mapfolio', array($this, 'render_map'));
    }

    public function render_map($atts) {
        // null = not passed, so the value falls back to the per-map setting and then to the global one
        $atts = shortcode_atts(array(
            'map'           => '',
            'height'        => null,
            'mobile_height' => null,
            'new_tab'       => null,
            'show_legend'   => null,
            'legend_bg'     => null,
            'legend_filter' => null,
            'map_style'     => null,
            'min_zoom'      => null,
            'max_zoom'      => null,
            'default_zoom'  => null,
            'debug'         => 0,
        ), $atts, 'mapfolio');

        $map = Mapfolio_DB::get_map($atts['map'] !== '' ? $atts['map'] : Mapfolio_DB::DEFAULT_MAP_ID);
        if (!$map) {
            // Only tell editors; visitors get nothing rather than an error message
            if (current_user_can(mapfolio_capability())) {
                /* translators: %s: value of the "map" shortcode attribute */
                return '<p>' . esc_html(sprintf(__('Mapfolio: map "%s" not found.', 'mapfolio'), $atts['map'])) . '</p>';
            }
            return '';
        }

        // Resolve: shortcode attribute → per-map setting → global setting.
        // Height is shortcode + global only: it depends on the page layout, not on the map.
        $ms      = Mapfolio_DB::get_map_settings($map);
        $resolve = function($attr, $setting_key, $option_key) use ($atts, $ms) {
            if ($atts[$attr] !== null) {
                return $atts[$attr];
            }
            if ($setting_key && isset($ms[$setting_key])) {
                return $ms[$setting_key];
            }
            return mapfolio_get_setting($option_key);
        };

        $height        = mapfolio_sanitize_css_size($resolve('height', null, 'height'), '500px');
        $mobile_height = mapfolio_sanitize_css_size($resolve('mobile_height', null, 'height_mobile'), '350px');
        $new_tab       = (int) (bool) $resolve('new_tab', 'new_tab', 'new_tab');
        $show_legend   = (int) (bool) $resolve('show_legend', 'show_legend', 'show_legend');
        $legend_bg     = mapfolio_sanitize_css_color($resolve('legend_bg', 'legend_bg', 'legend_bg'));
        $legend_filter = (int) (bool) $resolve('legend_filter', 'legend_filter', 'legend_filter');
        $map_style     = $resolve('map_style', 'map_style', 'style');
        $min_zoom      = mapfolio_sanitize_zoom($resolve('min_zoom', 'min_zoom', 'min_zoom'));
        $max_zoom      = max($min_zoom, mapfolio_sanitize_zoom($resolve('max_zoom', 'max_zoom', 'max_zoom')));
        $default_zoom  = max($min_zoom, min($max_zoom, mapfolio_sanitize_zoom($resolve('default_zoom', 'default_zoom', 'default_zoom'))));
        $debug         = (int) (bool) $atts['debug'];

        if (!array_key_exists($map_style, mapfolio_get_map_styles())) {
            $map_style = 'openstreetmap';
        }
        if ($mobile_height === 'fit') {
            $mobile_height = '350px';
        }

        // Also covers page builders that store shortcodes outside post_content
        mapfolio_enqueue_frontend_assets();

        $categories    = $show_legend ? Mapfolio_DB::get_categories_for_map($map['id']) : array();
        $container_id  = 'mapfolio-' . $map['id'] . '-' . wp_unique_id();
        $fit_container = ($height === 'fit');
        $wrapper_id    = $container_id . '-wrapper';

        ob_start();
        if ($fit_container): ?>
        <div id="<?php echo esc_attr($wrapper_id); ?>" class="mapfolio-fit-wrapper">
        <?php endif; ?>
        <div id="<?php echo esc_attr($container_id); ?>"
             class="mapfolio-container<?php echo $fit_container ? ' mapfolio-fit' : ''; ?>"
             data-map-id="<?php echo (int) $map['id']; ?>"
             data-new-tab="<?php echo $new_tab; ?>"
             data-debug="<?php echo $debug; ?>"
             data-map-style="<?php echo esc_attr($map_style); ?>"
             data-min-zoom="<?php echo $min_zoom; ?>"
             data-max-zoom="<?php echo $max_zoom; ?>"
             data-default-zoom="<?php echo $default_zoom; ?>"
             <?php if (!$fit_container): ?>style="height: <?php echo esc_attr($height); ?>;"<?php endif; ?>></div>
        <style>
            @media (max-width: 957px) {
                #<?php echo esc_attr($fit_container ? $wrapper_id : $container_id); ?> {
                    height: <?php echo esc_attr($mobile_height); ?> !important;
                }
            }
        </style>
        <?php if (!empty($categories)): ?>
            <div class="mapfolio-legend<?php echo $legend_filter ? ' mapfolio-legend-filter' : ''; ?>" data-map-container="<?php echo esc_attr($container_id); ?>"<?php echo $legend_bg ? ' style="background-color: ' . esc_attr($legend_bg) . ';"' : ''; ?>>
                <ul>
                    <?php foreach ($categories as $category):
                        $swatch = '<span class="mapfolio-legend-swatch" style="background-color: ' . esc_attr($category['color']) . '; border-color: ' . esc_attr($category['color']) . ';"></span>';
                        $label  = '<span class="mapfolio-legend-label">' . esc_html($category['title']) . ' (' . (int) $category['location_count'] . ')</span>';
                    ?>
                        <li>
                            <?php if ($legend_filter): ?>
                                <button type="button" class="mapfolio-legend-item" data-category="<?php echo (int) $category['id']; ?>" aria-pressed="true" title="<?php esc_attr_e('Show or hide this category on the map', 'mapfolio'); ?>"><?php echo $swatch . $label; ?></button>
                            <?php else: ?>
                                <span class="mapfolio-legend-item"><?php echo $swatch . $label; ?></span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <?php if ($fit_container): ?>
        </div>
        <?php endif;
        return ob_get_clean();
    }
}
