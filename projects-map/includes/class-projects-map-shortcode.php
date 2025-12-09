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
            'width' => get_option('projects_map_width', '100%'),
            'height' => get_option('projects_map_height', '500px'),
            'new_tab' => get_option('projects_map_new_tab', 0)
        ), $atts);

        $width = esc_attr($atts['width']);
        $height = esc_attr($atts['height']);
        $new_tab = intval($atts['new_tab']);

        ob_start();
        ?>
        <div id="projects-map" data-new-tab="<?php echo $new_tab; ?>" style="width: <?php echo $width; ?>; height: <?php echo $height; ?>;"></div>
        <?php
        return ob_get_clean();
    }
}
?>