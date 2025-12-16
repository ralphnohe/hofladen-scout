<?php
/**
 * Plugin Name: Spezialist SEO
 * Plugin URI: https://spezialist-für.de
 * Description: SEO-Optimierung für das Spezialist Directory - Meta Tags, Open Graph, Twitter Cards und Schema.org JSON-LD
 * Version: 1.0.0
 * Author: Spezialist Directory Team
 * Author URI: https://spezialist-für.de
 * Text Domain: spezialist-seo
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Main Spezialist SEO Class
 *
 * @since 1.0.0
 */
final class Spezialist_SEO {

    /**
     * Plugin version
     *
     * @var string
     */
    const VERSION = '1.0.0';

    /**
     * Single instance of the class
     *
     * @var Spezialist_SEO
     */
    protected static $_instance = null;

    /**
     * Placeholder image URL
     *
     * @var string
     */
    public $placeholder_image = '';

    /**
     * Logo URL
     *
     * @var string
     */
    public $logo_url = '';

    /**
     * Main Instance
     *
     * @return Spezialist_SEO
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
        $this->define_constants();
        $this->set_assets();

        // Check dependencies before initializing
        add_action( 'plugins_loaded', array( $this, 'check_dependencies' ) );
    }

    /**
     * Define constants
     */
    private function define_constants() {
        define( 'SDSEO_PLUGIN_FILE', __FILE__ );
        define( 'SDSEO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
        define( 'SDSEO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
        define( 'SDSEO_VERSION', self::VERSION );
    }

    /**
     * Set asset URLs
     */
    private function set_assets() {
        $upload_dir = wp_upload_dir();
        $this->placeholder_image = $upload_dir['baseurl'] . '/2025/11/placeholder.webp';
        $this->logo_url = home_url( '/favicon-spf.png' );
    }

    /**
     * Check if dependencies are met
     */
    public function check_dependencies() {
        // Check if Spezialist Directory plugin is active
        if ( ! class_exists( 'Spezialist_Directory' ) ) {
            add_action( 'admin_notices', array( $this, 'dependency_notice' ) );
            return;
        }

        // Check if another SEO plugin is active
        if ( $this->is_seo_plugin_active() ) {
            add_action( 'admin_notices', array( $this, 'seo_conflict_notice' ) );
            return;
        }

        // All good, initialize the plugin
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Check if a major SEO plugin is active
     *
     * @return bool
     */
    private function is_seo_plugin_active() {
        // Yoast SEO
        if ( defined( 'WPSEO_VERSION' ) ) {
            return true;
        }
        // RankMath
        if ( class_exists( 'RankMath' ) ) {
            return true;
        }
        // All in One SEO
        if ( class_exists( 'AIOSEO\\Plugin\\AIOSEO' ) ) {
            return true;
        }
        // SEOPress
        if ( defined( 'SEOPRESS_VERSION' ) ) {
            return true;
        }
        return false;
    }

    /**
     * Display dependency notice
     */
    public function dependency_notice() {
        ?>
        <div class="notice notice-error">
            <p><?php esc_html_e( 'Spezialist SEO benötigt das Spezialist Directory Plugin. Bitte aktivieren Sie zuerst das Spezialist Directory Plugin.', 'spezialist-seo' ); ?></p>
        </div>
        <?php
    }

    /**
     * Display SEO conflict notice
     */
    public function seo_conflict_notice() {
        ?>
        <div class="notice notice-warning">
            <p><?php esc_html_e( 'Spezialist SEO wurde deaktiviert, da ein anderes SEO-Plugin (Yoast, RankMath, etc.) aktiv ist.', 'spezialist-seo' ); ?></p>
        </div>
        <?php
    }

    /**
     * Include required files
     */
    public function includes() {
        require_once SDSEO_PLUGIN_DIR . 'includes/class-seo-meta-tags.php';
        require_once SDSEO_PLUGIN_DIR . 'includes/class-seo-open-graph.php';
        require_once SDSEO_PLUGIN_DIR . 'includes/class-seo-twitter-cards.php';
        require_once SDSEO_PLUGIN_DIR . 'includes/class-seo-schema.php';
        require_once SDSEO_PLUGIN_DIR . 'includes/class-seo-breadcrumbs.php';
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action( 'init', array( $this, 'init' ), 20 );
    }

    /**
     * Initialize plugin components
     */
    public function init() {
        // Initialize SEO components
        SDSEO_Meta_Tags::instance();
        SDSEO_Open_Graph::instance();
        SDSEO_Twitter_Cards::instance();
        SDSEO_Schema::instance();

        // Fire action after plugin is initialized
        do_action( 'spezialist_seo_init' );
    }

    /**
     * Get placeholder image URL
     *
     * @return string
     */
    public function get_placeholder_image() {
        return $this->placeholder_image;
    }

    /**
     * Get logo URL
     *
     * @return string
     */
    public function get_logo_url() {
        return $this->logo_url;
    }

    /**
     * Helper: Get specialist category names
     *
     * @param int $post_id
     * @return array
     */
    public static function get_specialist_categories( $post_id ) {
        $terms = wp_get_object_terms( $post_id, 'spezialist_category', array( 'fields' => 'names' ) );
        if ( is_wp_error( $terms ) || empty( $terms ) ) {
            return array();
        }
        return $terms;
    }

    /**
     * Helper: Get specialist location names
     *
     * @param int $post_id
     * @return array
     */
    public static function get_specialist_locations( $post_id ) {
        $terms = wp_get_object_terms( $post_id, 'spezialist_location', array( 'fields' => 'names' ) );
        if ( is_wp_error( $terms ) || empty( $terms ) ) {
            return array();
        }
        return $terms;
    }

    /**
     * Helper: Truncate text to specified length
     *
     * @param string $text
     * @param int $length
     * @param string $suffix
     * @return string
     */
    public static function truncate_text( $text, $length = 155, $suffix = '...' ) {
        $text = wp_strip_all_tags( $text );
        $text = preg_replace( '/\s+/', ' ', $text );
        $text = trim( $text );

        if ( mb_strlen( $text ) <= $length ) {
            return $text;
        }

        $text = mb_substr( $text, 0, $length - mb_strlen( $suffix ) );
        $text = preg_replace( '/\s+\S*$/', '', $text );

        return $text . $suffix;
    }

    /**
     * Helper: Check if current page is directory listing
     *
     * @return bool
     */
    public static function is_directory_page() {
        global $post;

        if ( is_front_page() ) {
            return true;
        }

        if ( $post && has_shortcode( $post->post_content ?? '', 'spezialist_listings' ) ) {
            return true;
        }

        return false;
    }

    /**
     * Helper: Check if current page should be noindexed
     *
     * @return bool
     */
    public static function should_noindex() {
        // Dashboard and submission pages
        global $post;
        if ( $post ) {
            if ( has_shortcode( $post->post_content ?? '', 'spezialist_dashboard' ) ) {
                return true;
            }
            if ( has_shortcode( $post->post_content ?? '', 'spezialist_submit' ) ) {
                return true;
            }
        }

        // Filtered search results
        if ( isset( $_GET['sd_search'] ) || isset( $_GET['sd_category'] ) || isset( $_GET['sd_location'] ) ) {
            return true;
        }

        // Pagination beyond page 1
        $paged = get_query_var( 'paged' ) ?: get_query_var( 'page' );
        if ( $paged > 1 ) {
            return true;
        }

        return false;
    }
}

/**
 * Returns the main instance of Spezialist_SEO
 *
 * @return Spezialist_SEO
 */
function SDSEO() {
    return Spezialist_SEO::instance();
}

// Initialize plugin
SDSEO();
