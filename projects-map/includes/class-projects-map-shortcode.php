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
        ob_start();
        ?>
        <div id="projects-map" style="height: 500px;"></div>
        <?php
        return ob_get_clean();
    }
}
?>