<?php
/**
 * Admin functionality for URL Checker
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Admin class
 */
class SUC_Admin {

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
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

        // AJAX handlers
        add_action( 'wp_ajax_suc_get_stats', array( $this, 'ajax_get_stats' ) );
        add_action( 'wp_ajax_suc_get_posts', array( $this, 'ajax_get_posts' ) );
        add_action( 'wp_ajax_suc_check_url', array( $this, 'ajax_check_url' ) );
        add_action( 'wp_ajax_suc_pause_post', array( $this, 'ajax_pause_post' ) );
        add_action( 'wp_ajax_suc_delete_post', array( $this, 'ajax_delete_post' ) );
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_management_page(
            'URL Checker',
            'URL Checker',
            'manage_options',
            'spezialist-url-checker',
            array( $this, 'render_admin_page' )
        );
    }

    /**
     * Enqueue assets
     */
    public function enqueue_assets( $hook ) {
        if ( 'tools_page_spezialist-url-checker' !== $hook ) {
            return;
        }

        wp_enqueue_style(
            'suc-admin',
            SUC_PLUGIN_URL . 'assets/css/suc-admin.css',
            array(),
            SUC_VERSION
        );

        wp_enqueue_script(
            'suc-admin',
            SUC_PLUGIN_URL . 'assets/js/suc-admin.js',
            array( 'jquery' ),
            SUC_VERSION,
            true
        );

        wp_localize_script( 'suc-admin', 'sucAdmin', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'suc_admin_nonce' ),
        ) );
    }

    /**
     * Render admin page
     */
    public function render_admin_page() {
        ?>
        <div class="wrap suc-admin-page">
            <h1>URL Checker</h1>
            <p class="description">Prüft alle Website-URLs der Spezialist-Einträge auf Erreichbarkeit.</p>

            <div class="suc-stats-grid">
                <div class="suc-stat-card">
                    <span class="suc-stat-number" id="suc-total-posts">0</span>
                    <span class="suc-stat-label">Gesamt Einträge</span>
                </div>
                <div class="suc-stat-card">
                    <span class="suc-stat-number" id="suc-with-url">0</span>
                    <span class="suc-stat-label">Mit Website</span>
                </div>
                <div class="suc-stat-card">
                    <span class="suc-stat-number" id="suc-checked">0</span>
                    <span class="suc-stat-label">Geprüft</span>
                </div>
                <div class="suc-stat-card">
                    <span class="suc-stat-number" id="suc-errors">0</span>
                    <span class="suc-stat-label">Fehler</span>
                </div>
            </div>

            <div class="suc-controls">
                <button id="suc-start" class="button button-primary">URL-Prüfung starten</button>
                <button id="suc-stop" class="button" disabled>Stoppen</button>
            </div>

            <div id="suc-progress-container" class="suc-progress-container" style="display: none;">
                <div class="suc-progress-bar-wrapper">
                    <div id="suc-progress-bar" class="suc-progress-bar"></div>
                </div>
                <div id="suc-progress-text" class="suc-progress-text">0 von 0 (0%)</div>
                <div id="suc-current-item" class="suc-current-item"></div>
            </div>

            <div id="suc-results-container" class="suc-results-container" style="display: none;">
                <h3>Fehlerhafte URLs (<span id="suc-error-count">0</span>)</h3>
                <table class="widefat suc-results-table">
                    <thead>
                        <tr>
                            <th>Titel</th>
                            <th>URL</th>
                            <th>Status</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody id="suc-results-body">
                        <!-- Dynamisch befüllt -->
                    </tbody>
                </table>
            </div>

            <div class="suc-log-container">
                <h3>Protokoll</h3>
                <div id="suc-log" class="suc-log"></div>
            </div>
        </div>
        <?php
    }

    /**
     * Get statistics
     */
    public function ajax_get_stats() {
        check_ajax_referer( 'suc_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Keine Berechtigung' );
        }

        global $wpdb;

        // Total posts
        $total = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s AND post_status = 'publish'",
            'hofladen'
        ) );

        // Posts with website
        $with_url = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(DISTINCT p.ID)
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_type = %s
             AND p.post_status = 'publish'
             AND pm.meta_key = '_sd_website'
             AND pm.meta_value != ''
             AND pm.meta_value IS NOT NULL",
            'hofladen'
        ) );

        wp_send_json_success( array(
            'total'    => (int) $total,
            'with_url' => (int) $with_url,
        ) );
    }

    /**
     * Get all posts with websites
     */
    public function ajax_get_posts() {
        check_ajax_referer( 'suc_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Keine Berechtigung' );
        }

        global $wpdb;

        $results = $wpdb->get_results( $wpdb->prepare(
            "SELECT p.ID, p.post_title, pm.meta_value as website
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_type = %s
             AND p.post_status = 'publish'
             AND pm.meta_key = '_sd_website'
             AND pm.meta_value != ''
             AND pm.meta_value IS NOT NULL
             ORDER BY p.post_title ASC",
            'hofladen'
        ) );

        $posts = array();
        foreach ( $results as $row ) {
            $posts[] = array(
                'id'      => (int) $row->ID,
                'title'   => $row->post_title,
                'website' => $row->website,
            );
        }

        wp_send_json_success( $posts );
    }

    /**
     * Check single URL
     */
    public function ajax_check_url() {
        check_ajax_referer( 'suc_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Keine Berechtigung' );
        }

        $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
        $url     = isset( $_POST['url'] ) ? esc_url_raw( $_POST['url'] ) : '';

        if ( ! $post_id || ! $url ) {
            wp_send_json_error( 'Ungültige Parameter' );
        }

        // Normalize URL
        if ( ! preg_match( '/^https?:\/\//', $url ) ) {
            $url = 'https://' . $url;
        }

        // HEAD request (faster than GET)
        $response = wp_remote_head( $url, array(
            'timeout'     => 10,
            'redirection' => 3,
            'sslverify'   => false,
            'user-agent'  => 'Mozilla/5.0 (compatible; Spezialist-URL-Checker/1.0)',
        ) );

        if ( is_wp_error( $response ) ) {
            wp_send_json_success( array(
                'post_id' => $post_id,
                'status'  => 0,
                'error'   => $response->get_error_message(),
            ) );
        }

        $status_code = wp_remote_retrieve_response_code( $response );

        wp_send_json_success( array(
            'post_id' => $post_id,
            'status'  => (int) $status_code,
            'error'   => null,
        ) );
    }

    /**
     * Pause post (set to draft)
     */
    public function ajax_pause_post() {
        check_ajax_referer( 'suc_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Keine Berechtigung' );
        }

        $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;

        if ( ! $post_id ) {
            wp_send_json_error( 'Ungültige Post-ID' );
        }

        $result = wp_update_post( array(
            'ID'          => $post_id,
            'post_status' => 'draft',
        ) );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( $result->get_error_message() );
        }

        wp_send_json_success( array(
            'post_id' => $post_id,
            'status'  => 'paused',
        ) );
    }

    /**
     * Delete post
     */
    public function ajax_delete_post() {
        check_ajax_referer( 'suc_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Keine Berechtigung' );
        }

        $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;

        if ( ! $post_id ) {
            wp_send_json_error( 'Ungültige Post-ID' );
        }

        // Move to trash (not permanent delete)
        $result = wp_trash_post( $post_id );

        if ( ! $result ) {
            wp_send_json_error( 'Löschen fehlgeschlagen' );
        }

        wp_send_json_success( array(
            'post_id' => $post_id,
            'status'  => 'deleted',
        ) );
    }
}

// Initialize
SUC_Admin::get_instance();
