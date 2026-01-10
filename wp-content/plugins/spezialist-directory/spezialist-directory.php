<?php
/**
 * Plugin Name: Spezialist Directory
 * Plugin URI: https://spezialist-für.de
 * Description: Umfassendes Verzeichnis-Plugin für Spezialisten mit Custom Post Type, Frontend-Submissions, User Dashboard, Claim-System und Stripe Integration
 * Version: 1.0.0
 * Author: Spezialist Directory Team
 * Author URI: https://spezialist-für.de
 * Text Domain: spezialist-directory
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
 * Main Spezialist Directory Class
 *
 * @since 1.0.0
 */
final class Spezialist_Directory {

    /**
     * Plugin version
     *
     * @var string
     */
    const VERSION = '1.0.8';

    /**
     * Single instance of the class
     *
     * @var Spezialist_Directory
     */
    protected static $_instance = null;

    /**
     * Main Instance
     *
     * Ensures only one instance of Spezialist_Directory is loaded or can be loaded
     *
     * @return Spezialist_Directory - Main instance
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
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Define constants
     */
    private function define_constants() {
        $this->define( 'SD_PLUGIN_FILE', __FILE__ );
        $this->define( 'SD_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
        $this->define( 'SD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
        $this->define( 'SD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
        $this->define( 'SD_VERSION', self::VERSION );
    }

    /**
     * Define constant if not already set
     *
     * @param string $name
     * @param string|bool $value
     */
    private function define( $name, $value ) {
        if ( ! defined( $name ) ) {
            define( $name, $value );
        }
    }

    /**
     * Include required core files
     */
    public function includes() {
        // Load Stripe PHP SDK if available
        if ( file_exists( SD_PLUGIN_DIR . 'vendor/autoload.php' ) ) {
            require_once SD_PLUGIN_DIR . 'vendor/autoload.php';
        }

        // Core classes
        require_once SD_PLUGIN_DIR . 'includes/class-email-templates.php';
        require_once SD_PLUGIN_DIR . 'includes/class-cpt-spezialist.php';
        require_once SD_PLUGIN_DIR . 'includes/class-taxonomies.php';
        require_once SD_PLUGIN_DIR . 'includes/class-meta-boxes.php';
        require_once SD_PLUGIN_DIR . 'includes/class-user-submissions.php';
        require_once SD_PLUGIN_DIR . 'includes/class-claim-system.php';
        require_once SD_PLUGIN_DIR . 'includes/class-user-dashboard.php';
        require_once SD_PLUGIN_DIR . 'includes/class-stripe-integration.php';
        require_once SD_PLUGIN_DIR . 'includes/class-premium-features.php';
        require_once SD_PLUGIN_DIR . 'includes/class-ajax-filter.php';
        require_once SD_PLUGIN_DIR . 'includes/class-login-register.php';
        require_once SD_PLUGIN_DIR . 'includes/class-business-hours.php';
        require_once SD_PLUGIN_DIR . 'includes/class-analytics.php';
        require_once SD_PLUGIN_DIR . 'includes/class-leads.php';
    }

    /**
     * Hook into WordPress
     */
    private function init_hooks() {
        // Initialize plugin
        add_action( 'init', array( $this, 'init' ), 0 );

        // Load plugin textdomain
        add_action( 'init', array( $this, 'load_textdomain' ) );

        // Security: Add security headers
        add_action( 'send_headers', array( $this, 'add_security_headers' ) );

        // Enqueue assets
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

        // Fix front page pagination
        add_filter( 'redirect_canonical', array( $this, 'fix_front_page_pagination' ), 10, 2 );
        add_action( 'pre_get_posts', array( $this, 'set_front_page_pagination' ) );

        // Activation & Deactivation
        register_activation_hook( __FILE__, array( $this, 'activate' ) );
        register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );

        // Non-admin user restrictions
        add_action( 'admin_init', array( $this, 'block_wp_admin_for_non_admins' ) );
        add_action( 'after_setup_theme', array( $this, 'hide_admin_bar_for_non_admins' ) );
    }

    /**
     * Add security headers to prevent common attacks
     *
     * @since 1.0.0
     */
    public function add_security_headers() {
        // Prevent MIME type sniffing
        header( 'X-Content-Type-Options: nosniff' );

        // Prevent clickjacking
        header( 'X-Frame-Options: SAMEORIGIN' );

        // Enable XSS filtering (legacy browsers)
        header( 'X-XSS-Protection: 1; mode=block' );

        // Referrer policy
        header( 'Referrer-Policy: strict-origin-when-cross-origin' );

        // Permissions policy (disable potentially dangerous browser features)
        header( 'Permissions-Policy: geolocation=(self), microphone=(), camera=()' );
    }

    /**
     * Initialize plugin
     */
    public function init() {
        // Initialize and register taxonomies FIRST
        $taxonomies = SD_Taxonomies::instance();
        $taxonomies->register_taxonomies();

        // Then register custom post type
        $cpt = SD_CPT_Spezialist::instance();
        $cpt->register_post_type();

        // Explicitly associate taxonomies with post type for extra safety
        register_taxonomy_for_object_type( 'spezialist_category', 'hofladen' );
        register_taxonomy_for_object_type( 'spezialist_location', 'hofladen' );
        register_taxonomy_for_object_type( 'spezialist_tag', 'hofladen' );

        // Initialize other components
        SD_Meta_Boxes::instance();
        SD_User_Submissions::instance();
        SD_Claim_System::instance();
        SD_User_Dashboard::instance();
        SD_Stripe_Integration::instance();
        SD_Premium_Features::instance();
        SD_Ajax_Filter::instance();
        SD_Login_Register::instance();
        SD_Analytics::instance();
        SD_Leads::instance();

        // Fire action after plugin is initialized
        do_action( 'spezialist_directory_init' );
    }

    /**
     * Load plugin textdomain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'spezialist-directory',
            false,
            dirname( SD_PLUGIN_BASENAME ) . '/languages'
        );
    }

    /**
     * Fix front page pagination redirect
     *
     * Prevents WordPress from redirecting /page/2/ to 404 on static front page
     *
     * @param string $redirect_url The redirect URL
     * @param string $requested_url The requested URL
     * @return string|false
     */
    public function fix_front_page_pagination( $redirect_url, $requested_url ) {
        // Only apply to front page with pagination
        if ( is_front_page() && is_page() && get_query_var( 'page' ) > 0 ) {
            return false; // Disable redirect
        }
        return $redirect_url;
    }

    /**
     * Set front page pagination query var
     *
     * Ensures the 'page' query var is properly available on static front page
     *
     * @param WP_Query $query
     */
    public function set_front_page_pagination( $query ) {
        if ( $query->is_main_query() && $query->is_front_page() && ! is_admin() ) {
            // Get paged value from URL
            $page = get_query_var( 'page' );
            if ( $page > 0 ) {
                $query->set( 'page', $page );
            }
        }
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        // Get post content safely
        $post = get_post();
        $post_content = $post ? $post->post_content : '';

        // Check if we're on a spezialist taxonomy archive
        $is_taxonomy_archive = is_tax( 'spezialist_category' ) ||
                               is_tax( 'spezialist_location' ) ||
                               is_tax( 'spezialist_tag' );

        // Only enqueue on relevant pages
        if ( ! is_singular( 'hofladen' ) && ! has_shortcode( $post_content, 'spezialist_listings' )
             && ! has_shortcode( $post_content, 'spezialist_submit' )
             && ! has_shortcode( $post_content, 'spezialist_dashboard' )
             && ! has_shortcode( $post_content, 'spezialist_login' )
             && ! has_shortcode( $post_content, 'spezialist_favorites' )
             && ! $is_taxonomy_archive
             && ! is_page_template( 'page-kartensuche.php' )
             && ! is_front_page() ) {
            return;
        }

        // Enqueue CSS
        wp_enqueue_style(
            'spezialist-directory-frontend',
            SD_PLUGIN_URL . 'assets/css/frontend-styles.css',
            array(),
            SD_VERSION
        );

        // Enqueue Leaflet CSS and JS for pages with maps (listings page + single spezialist + kartensuche + submission form + dashboard)
        $needs_leaflet = is_singular( 'hofladen' ) ||
                         has_shortcode( $post_content, 'spezialist_listings' ) ||
                         has_shortcode( $post_content, 'spezialist_submit' ) ||
                         has_shortcode( $post_content, 'spezialist_dashboard' ) ||
                         is_front_page() ||
                         is_page_template( 'page-kartensuche.php' );

        if ( $needs_leaflet ) {
            wp_enqueue_style(
                'leaflet',
                'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
                array(),
                '1.9.4'
            );
            wp_enqueue_script(
                'leaflet',
                'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
                array(),
                '1.9.4',
                true
            );
        }

        // Enqueue minimal JavaScript
        wp_enqueue_script(
            'spezialist-directory-frontend',
            SD_PLUGIN_URL . 'assets/js/minimal-interactions.js',
            array( 'jquery' ),
            SD_VERSION,
            true
        );

        // Localize script for AJAX
        wp_localize_script( 'spezialist-directory-frontend', 'sdAjax', array(
            'ajaxurl'          => admin_url( 'admin-ajax.php' ),
            'nonce'            => wp_create_nonce( 'sd_ajax_nonce' ),
            'filterNonce'      => wp_create_nonce( 'sd_filter_nonce' ),
            'analyticsNonce'   => wp_create_nonce( 'sd_analytics_nonce' ), // Security fix: Add analytics nonce
            'deleteNonce'      => wp_create_nonce( 'sd_delete_listing' ),
            'editNonce'        => wp_create_nonce( 'sd_get_listing_data' ),
            'updateNonce'      => wp_create_nonce( 'sd_update_listing' ),
            'profileNonce'     => wp_create_nonce( 'sd_update_profile' ),
            'togglePauseNonce' => wp_create_nonce( 'sd_toggle_pause' ),
            'duplicateNonce'   => wp_create_nonce( 'sd_duplicate_listing' ),
            'checkoutNonce'    => wp_create_nonce( 'sd_create_checkout' ),
            'cancelSubNonce'   => wp_create_nonce( 'sd_cancel_subscription' ),
            'billingNonce'     => wp_create_nonce( 'sd_billing_portal' ),
            'quoteNonce'       => wp_create_nonce( 'sd_quote_request' ),
            'leadsNonce'       => wp_create_nonce( 'sd_leads_nonce' ),
            'checkPremiumNonce' => wp_create_nonce( 'sd_check_premium' ),
            'stripeKey'        => SD_Stripe_Integration::instance()->get_publishable_key(),
            'dashboardUrl'     => sd_get_page_url( 'mein-dashboard' ),
            'isFrontPage'      => is_front_page(),
        ) );
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets( $hook ) {
        // Only on spezialist post type edit screens
        global $post_type;
        if ( 'hofladen' !== $post_type ) {
            return;
        }

        wp_enqueue_style(
            'spezialist-directory-admin',
            SD_PLUGIN_URL . 'assets/css/admin-styles.css',
            array(),
            SD_VERSION
        );
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Initialize core components for activation
        require_once SD_PLUGIN_DIR . 'includes/class-cpt-spezialist.php';
        require_once SD_PLUGIN_DIR . 'includes/class-taxonomies.php';

        // Register taxonomies first
        $taxonomies = SD_Taxonomies::instance();
        $taxonomies->register_taxonomies();

        // Then register custom post type
        $cpt = SD_CPT_Spezialist::instance();
        $cpt->register_post_type();

        // Explicitly associate taxonomies with post type
        register_taxonomy_for_object_type( 'spezialist_category', 'hofladen' );
        register_taxonomy_for_object_type( 'spezialist_location', 'hofladen' );
        register_taxonomy_for_object_type( 'spezialist_tag', 'hofladen' );

        // Flush rewrite rules
        flush_rewrite_rules();

        // Set default options
        $this->set_default_options();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Set default plugin options
     */
    private function set_default_options() {
        $defaults = array(
            'stripe_test_mode' => true,
            'stripe_test_publishable_key' => '',
            'stripe_test_secret_key' => '',
            'stripe_live_publishable_key' => '',
            'stripe_live_secret_key' => '',
            'premium_monthly_price_id' => '',
            'premium_yearly_price_id' => '',
            'require_approval' => true,
            'allow_guest_submissions' => false,
            'listings_per_page' => 12,
        );

        foreach ( $defaults as $key => $value ) {
            if ( false === get_option( 'sd_' . $key ) ) {
                add_option( 'sd_' . $key, $value );
            }
        }
    }

    /**
     * Get plugin option
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get_option( $key, $default = false ) {
        return get_option( 'sd_' . $key, $default );
    }

    /**
     * Update plugin option
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public static function update_option( $key, $value ) {
        return update_option( 'sd_' . $key, $value );
    }

    /**
     * Redirect non-admin users away from wp-admin to user dashboard
     *
     * @since 1.1.0
     */
    public function block_wp_admin_for_non_admins() {
        // Allow AJAX requests
        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            return;
        }

        // Only block if user is logged in but not an admin
        if ( is_user_logged_in() && ! current_user_can( 'manage_options' ) ) {
            wp_redirect( sd_get_page_url( 'mein-dashboard/' ) );
            exit;
        }
    }

    /**
     * Hide the admin bar for non-admin users
     *
     * @since 1.1.0
     */
    public function hide_admin_bar_for_non_admins() {
        if ( is_user_logged_in() && ! current_user_can( 'manage_options' ) ) {
            show_admin_bar( false );
        }
    }
}

/**
 * Returns the main instance of Spezialist_Directory
 *
 * @return Spezialist_Directory
 */
function SD() {
    return Spezialist_Directory::instance();
}

// Initialize plugin
SD();

/**
 * Get URL for a page with proper permalink structure
 * Handles PHP built-in server and different permalink configurations
 *
 * @param string $slug The page slug (with or without leading slash)
 * @return string Full URL with proper prefix
 */
function sd_get_page_url( $slug ) {
    $permalink_structure = get_option( 'permalink_structure' );
    if ( strpos( $permalink_structure, 'index.php' ) !== false ) {
        return home_url( '/index.php/' . ltrim( $slug, '/' ) );
    }
    return home_url( '/' . ltrim( $slug, '/' ) );
}
