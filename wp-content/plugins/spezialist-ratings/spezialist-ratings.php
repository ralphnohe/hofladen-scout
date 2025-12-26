<?php
/**
 * Plugin Name: Spezialist Ratings
 * Plugin URI: https://spezialist-fuer.de
 * Description: Rating and review system for Spezialist Directory listings
 * Version: 1.0.0
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Author: Spezialist-Für.de
 * License: GPL v2 or later
 * Text Domain: spezialist-ratings
 * Domain Path: /languages
 *
 * @package Spezialist_Ratings
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'SR_VERSION', '1.0.0' );
define( 'SR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SR_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main Spezialist Ratings Class
 */
final class Spezialist_Ratings {

    /**
     * Single instance
     *
     * @var Spezialist_Ratings
     */
    protected static $_instance = null;

    /**
     * Main Instance
     *
     * @return Spezialist_Ratings
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
        $this->check_dependencies();
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Check for required dependencies
     */
    private function check_dependencies() {
        add_action( 'admin_init', function() {
            if ( ! is_plugin_active( 'spezialist-directory/spezialist-directory.php' ) ) {
                add_action( 'admin_notices', array( $this, 'dependency_notice' ) );
                deactivate_plugins( SR_PLUGIN_BASENAME );
            }
        } );
    }

    /**
     * Show dependency notice
     */
    public function dependency_notice() {
        ?>
        <div class="notice notice-error">
            <p><strong>Spezialist Ratings</strong> <?php _e( 'benötigt das Plugin "Spezialist Directory" um zu funktionieren.', 'spezialist-ratings' ); ?></p>
        </div>
        <?php
    }

    /**
     * Include required files
     */
    private function includes() {
        require_once SR_PLUGIN_DIR . 'includes/class-sr-ratings.php';
        require_once SR_PLUGIN_DIR . 'includes/class-sr-ajax.php';
        require_once SR_PLUGIN_DIR . 'includes/class-sr-display.php';
        require_once SR_PLUGIN_DIR . 'includes/class-sr-admin.php';
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Activation hook
        register_activation_hook( __FILE__, array( $this, 'activate' ) );

        // Initialize classes
        add_action( 'init', array( $this, 'init_classes' ) );

        // Enqueue scripts and styles
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
    }

    /**
     * Plugin activation
     */
    public function activate() {
        SR_Ratings::create_tables();
        flush_rewrite_rules();
    }

    /**
     * Initialize classes
     */
    public function init_classes() {
        SR_Ratings::instance();
        SR_Ajax::instance();
        SR_Display::instance();
        SR_Admin::instance();

        // Run database upgrade check
        SR_Ratings::maybe_upgrade_table();
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        // Only load on pages with spezialist content
        $is_taxonomy_archive = is_tax( 'spezialist_category' ) || is_tax( 'spezialist_location' ) || is_tax( 'spezialist_tag' );

        // Check for pages using the spezialist_listings shortcode
        global $post;
        $has_listings_shortcode = is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'spezialist_listings' );
        $is_listings_page = is_page( array( 'hoflaeden-finden', 'spezialisten-finden' ) );

        if ( is_singular( 'spezialist' ) || is_front_page() || is_home() || is_post_type_archive( 'spezialist' ) || $is_taxonomy_archive || $has_listings_shortcode || $is_listings_page ) {
            wp_enqueue_style(
                'sr-styles',
                SR_PLUGIN_URL . 'assets/css/sr-styles.css',
                array(),
                SR_VERSION
            );

            wp_enqueue_script(
                'sr-ratings',
                SR_PLUGIN_URL . 'assets/js/sr-ratings.js',
                array( 'jquery' ),
                SR_VERSION,
                true
            );

            // Localize script
            wp_localize_script( 'sr-ratings', 'srRatings', array(
                'ajaxurl'      => admin_url( 'admin-ajax.php' ),
                'nonce'        => wp_create_nonce( 'sr_submit_rating' ),
                'isLoggedIn'   => is_user_logged_in(),
                'loginUrl'     => function_exists( 'sd_get_page_url' ) ? sd_get_page_url( 'anmelden/' ) : wp_login_url(),
                'strings'      => array(
                    'selectRating'    => __( 'Bitte wählen Sie eine Bewertung (1-5 Sterne).', 'spezialist-ratings' ),
                    'submitting'      => __( 'Wird gesendet...', 'spezialist-ratings' ),
                    'error'           => __( 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es erneut.', 'spezialist-ratings' ),
                    'thankYou'        => __( 'Vielen Dank für Ihre Bewertung!', 'spezialist-ratings' ),
                    'pendingApproval' => __( 'Ihre Bewertung wird nach Prüfung veröffentlicht.', 'spezialist-ratings' ),
                ),
            ) );
        }
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets( $hook ) {
        if ( strpos( $hook, 'sr-ratings' ) !== false ) {
            wp_enqueue_style(
                'sr-admin-styles',
                SR_PLUGIN_URL . 'assets/css/sr-admin.css',
                array(),
                SR_VERSION
            );
        }
    }
}

/**
 * Initialize the plugin
 */
function spezialist_ratings() {
    return Spezialist_Ratings::instance();
}

// Start the plugin
add_action( 'plugins_loaded', 'spezialist_ratings' );

// Register activation hook at top level (must be outside class)
register_activation_hook( __FILE__, 'sr_activate_plugin' );

/**
 * Plugin activation callback
 */
function sr_activate_plugin() {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-sr-ratings.php';
    SR_Ratings::create_tables();
    flush_rewrite_rules();
}
