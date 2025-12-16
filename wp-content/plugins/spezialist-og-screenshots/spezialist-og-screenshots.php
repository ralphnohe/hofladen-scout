<?php
/**
 * Plugin Name: Spezialist OG Screenshots
 * Plugin URI: https://spezialist-fuer.de
 * Description: Erstellt automatisch OG-Screenshots für alle Seiten und setzt sie als og:image/twitter:image.
 * Version: 1.0.0
 * Author: Spezialist-Für.de
 * Author URI: https://spezialist-fuer.de
 * Text Domain: spezialist-og-screenshots
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 *
 * @package Spezialist_OG_Screenshots
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'OGS_VERSION', '1.0.0' );
define( 'OGS_PLUGIN_FILE', __FILE__ );
define( 'OGS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'OGS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'OGS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main Plugin Class
 *
 * @since 1.0.0
 */
final class Spezialist_OG_Screenshots {

    /**
     * Single instance
     *
     * @var Spezialist_OG_Screenshots
     */
    protected static $_instance = null;

    /**
     * Main Instance
     *
     * Ensures only one instance of the plugin is loaded.
     *
     * @return Spezialist_OG_Screenshots
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
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
        require_once OGS_PLUGIN_DIR . 'includes/class-ogs-discovery.php';
        require_once OGS_PLUGIN_DIR . 'includes/class-ogs-screenshot.php';
        require_once OGS_PLUGIN_DIR . 'includes/class-ogs-integration.php';
        require_once OGS_PLUGIN_DIR . 'includes/class-ogs-admin.php';
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action( 'plugins_loaded', array( $this, 'on_plugins_loaded' ) );
        add_action( 'admin_notices', array( $this, 'check_dependencies' ) );
    }

    /**
     * Actions to run when plugins are loaded
     */
    public function on_plugins_loaded() {
        // Initialize classes
        OGS_Discovery::instance();
        OGS_Screenshot::instance();
        OGS_Integration::instance();

        if ( is_admin() ) {
            OGS_Admin::instance();
        }
    }

    /**
     * Check dependencies and show admin notices
     */
    public function check_dependencies() {
        // Check if API key is configured
        if ( ! self::is_api_configured() ) {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>Spezialist OG Screenshots:</strong> ';
            echo 'Screenshot API ist nicht konfiguriert. Bitte konfigurieren Sie den API-Key im ';
            echo '<a href="' . esc_url( admin_url( 'tools.php?page=spezialist-screenshots' ) ) . '">Screenshot Generator</a> Plugin.';
            echo '</p></div>';
        }

        // Check GD library
        if ( ! function_exists( 'imagecreatefrompng' ) || ! function_exists( 'imagewebp' ) ) {
            echo '<div class="notice notice-error">';
            echo '<p><strong>Spezialist OG Screenshots:</strong> ';
            echo 'PHP GD Library ist nicht verfügbar. Bitte installieren Sie die GD Extension.';
            echo '</p></div>';
        }
    }

    /**
     * Get the API key from the screenshot plugin
     *
     * @return string
     */
    public static function get_api_key() {
        return get_option( 'ss_api_key', '' );
    }

    /**
     * Check if API is configured
     *
     * @return bool
     */
    public static function is_api_configured() {
        return ! empty( self::get_api_key() );
    }

    /**
     * Check if GD library is available
     *
     * @return bool
     */
    public static function is_gd_available() {
        return function_exists( 'imagecreatefrompng' ) && function_exists( 'imagewebp' );
    }
}

/**
 * Returns the main instance of the plugin
 *
 * @return Spezialist_OG_Screenshots
 */
function OGS() {
    return Spezialist_OG_Screenshots::instance();
}

// Initialize
OGS();
