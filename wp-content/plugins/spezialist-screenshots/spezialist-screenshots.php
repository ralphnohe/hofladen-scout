<?php
/**
 * Plugin Name: Spezialist Screenshots
 * Plugin URI: https://spezialist-fuer.de
 * Description: Erstellt automatisch Website-Screenshots für Spezialist-Einträge und setzt sie als Featured Images.
 * Version: 1.1.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: Spezialist-Für.de
 * License: GPL v2 or later
 * Text Domain: spezialist-screenshots
 */

if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('SS_VERSION', '1.1.0');
define('SS_PLUGIN_FILE', __FILE__);
define('SS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SS_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main Plugin Class
 */
final class Spezialist_Screenshots {

    /**
     * Plugin version
     */
    const VERSION = '1.1.0';

    /**
     * Single instance
     */
    protected static $_instance = null;

    /**
     * Get instance
     */
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor
     */
    public function __construct() {
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Include required files
     */
    private function includes() {
        require_once SS_PLUGIN_DIR . 'includes/class-ss-admin.php';
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('plugins_loaded', array($this, 'on_plugins_loaded'));
        add_action('admin_notices', array($this, 'check_dependencies'));
    }

    /**
     * On plugins loaded
     */
    public function on_plugins_loaded() {
        // Initialize admin
        if (is_admin()) {
            SS_Admin::instance();
        }
    }

    /**
     * Get the API key
     */
    public static function get_api_key() {
        return get_option('ss_api_key', '');
    }

    /**
     * Check if API is configured
     */
    public static function is_api_configured() {
        $api_key = self::get_api_key();
        return !empty($api_key);
    }

    /**
     * Check dependencies and show admin notices
     */
    public function check_dependencies() {
        // Only show on our admin page
        $screen = get_current_screen();
        if (!$screen || $screen->id !== 'tools_page_spezialist-screenshots') {
            return;
        }

        if (!self::is_api_configured()) {
            echo '<div class="notice notice-warning"><p>';
            echo '<strong>Spezialist Screenshots:</strong> ';
            echo 'Bitte geben Sie Ihren Screenshot API Key in den Einstellungen ein.';
            echo '</p></div>';
        }

        if (!extension_loaded('gd')) {
            echo '<div class="notice notice-error"><p>';
            echo '<strong>Spezialist Screenshots:</strong> ';
            echo 'Die PHP GD Extension ist nicht verfügbar. Diese wird für die Bildverarbeitung benötigt.';
            echo '</p></div>';
        }
    }
}

/**
 * Returns the main instance
 */
function SS() {
    return Spezialist_Screenshots::instance();
}

// Initialize
SS();
