<?php
/**
 * Plugin Name: Spezialist URL Checker
 * Plugin URI: https://spezialist-fuer.de
 * Description: Prüft Website-URLs aller Spezialist-Einträge auf Erreichbarkeit
 * Version: 1.0.0
 * Author: Spezialist-Für.de
 * Author URI: https://spezialist-fuer.de
 * Text Domain: spezialist-url-checker
 * Domain Path: /languages
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin constants
define( 'SUC_VERSION', '1.0.0' );
define( 'SUC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SUC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Main plugin class
 */
final class Spezialist_URL_Checker {

    /**
     * Single instance
     */
    private static $instance = null;

    /**
     * Get instance
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
    }

    /**
     * Load required files
     */
    private function load_dependencies() {
        require_once SUC_PLUGIN_DIR . 'includes/class-suc-admin.php';
    }
}

/**
 * Initialize plugin
 */
function spezialist_url_checker_init() {
    Spezialist_URL_Checker::get_instance();
}
add_action( 'plugins_loaded', 'spezialist_url_checker_init' );
