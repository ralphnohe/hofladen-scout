<?php
/**
 * OG Screenshots Admin Class
 *
 * Handles admin interface and AJAX endpoints
 *
 * @package Spezialist_OG_Screenshots
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * OGS_Admin Class
 */
class OGS_Admin {

    /**
     * Single instance
     *
     * @var OGS_Admin
     */
    protected static $_instance = null;

    /**
     * Main Instance
     *
     * @return OGS_Admin
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
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

        // AJAX handlers
        add_action( 'wp_ajax_ogs_get_stats', array( $this, 'ajax_get_stats' ) );
        add_action( 'wp_ajax_ogs_get_items', array( $this, 'ajax_get_items' ) );
        add_action( 'wp_ajax_ogs_process_single', array( $this, 'ajax_process_single' ) );
        add_action( 'wp_ajax_ogs_delete_screenshot', array( $this, 'ajax_delete_screenshot' ) );
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_management_page(
            'OG Screenshot Generator',
            'OG Screenshots',
            'manage_options',
            'spezialist-og-screenshots',
            array( $this, 'render_admin_page' )
        );
    }

    /**
     * Enqueue admin assets
     *
     * @param string $hook Current admin page hook.
     */
    public function enqueue_assets( $hook ) {
        if ( 'tools_page_spezialist-og-screenshots' !== $hook ) {
            return;
        }

        wp_enqueue_style(
            'ogs-admin',
            OGS_PLUGIN_URL . 'assets/css/ogs-admin.css',
            array(),
            OGS_VERSION
        );

        wp_enqueue_script(
            'ogs-admin',
            OGS_PLUGIN_URL . 'assets/js/ogs-admin.js',
            array( 'jquery' ),
            OGS_VERSION,
            true
        );

        wp_localize_script(
            'ogs-admin',
            'ogsAdmin',
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( 'ogs_admin_nonce' ),
                'strings' => array(
                    'processing'  => 'Verarbeite...',
                    'success'     => 'Erfolgreich',
                    'error'       => 'Fehler',
                    'starting'    => 'Starte Verarbeitung...',
                    'completed'   => 'Verarbeitung abgeschlossen',
                    'stopped'     => 'Verarbeitung gestoppt',
                    'noItems'     => 'Keine Items zu verarbeiten',
                    'confirmStop' => 'Verarbeitung wirklich stoppen?',
                ),
            )
        );
    }

    /**
     * Render admin page
     */
    public function render_admin_page() {
        $is_api_configured = OGS()->is_api_configured();
        $is_gd_available   = OGS()->is_gd_available();
        $content_types     = OGS_Discovery::get_content_types();
        ?>
        <div class="wrap ogs-admin-wrap">
            <h1>OG Screenshot Generator</h1>
            <p class="description">Erstellt automatisch OG-Screenshots für alle Seiten und setzt sie als og:image/twitter:image.</p>

            <div class="ogs-admin-grid">
                <!-- System Status -->
                <div class="ogs-admin-card">
                    <h2>System-Status</h2>
                    <div class="ogs-status-list">
                        <div class="ogs-status-item">
                            <span class="ogs-status-indicator <?php echo $is_api_configured ? 'ogs-status-ok' : 'ogs-status-error'; ?>"></span>
                            <span class="ogs-status-label">Screenshot API</span>
                            <span class="ogs-status-value">
                                <?php if ( $is_api_configured ) : ?>
                                    Konfiguriert
                                <?php else : ?>
                                    <a href="<?php echo esc_url( admin_url( 'tools.php?page=spezialist-screenshots' ) ); ?>">Nicht konfiguriert</a>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="ogs-status-item">
                            <span class="ogs-status-indicator <?php echo $is_gd_available ? 'ogs-status-ok' : 'ogs-status-error'; ?>"></span>
                            <span class="ogs-status-label">PHP GD Library</span>
                            <span class="ogs-status-value"><?php echo $is_gd_available ? 'Verfügbar' : 'Nicht verfügbar'; ?></span>
                        </div>
                    </div>
                </div>

                <!-- Statistics -->
                <div class="ogs-admin-card ogs-admin-card-wide">
                    <h2>Statistiken</h2>
                    <div class="ogs-stats-grid" id="ogs-stats-grid">
                        <?php foreach ( $content_types as $type => $label ) : ?>
                            <div class="ogs-stat-card" data-type="<?php echo esc_attr( $type ); ?>">
                                <div class="ogs-stat-label"><?php echo esc_html( $label ); ?></div>
                                <div class="ogs-stat-value">
                                    <span class="ogs-stat-with">-</span> / <span class="ogs-stat-total">-</span>
                                </div>
                                <div class="ogs-stat-missing">
                                    <span class="ogs-stat-missing-count">-</span> fehlen
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <div class="ogs-stat-card ogs-stat-card-total">
                            <div class="ogs-stat-label">Gesamt</div>
                            <div class="ogs-stat-value">
                                <span class="ogs-stat-with" id="ogs-total-with">-</span> / <span class="ogs-stat-total" id="ogs-total-total">-</span>
                            </div>
                            <div class="ogs-stat-missing">
                                <span class="ogs-stat-missing-count" id="ogs-total-missing">-</span> fehlen
                            </div>
                        </div>
                    </div>
                    <button type="button" class="button" id="ogs-refresh-stats">Statistiken aktualisieren</button>
                </div>
            </div>

            <!-- Processing Controls -->
            <div class="ogs-admin-card ogs-admin-card-full">
                <h2>Screenshot-Verarbeitung</h2>

                <div class="ogs-controls">
                    <div class="ogs-control-group">
                        <label for="ogs-type-filter">Typ:</label>
                        <select id="ogs-type-filter">
                            <option value="all">Alle Typen</option>
                            <?php foreach ( $content_types as $type => $label ) : ?>
                                <option value="<?php echo esc_attr( $type ); ?>"><?php echo esc_html( $label ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="ogs-control-buttons">
                        <button type="button" class="button button-primary" id="ogs-start-processing" <?php echo ( ! $is_api_configured || ! $is_gd_available ) ? 'disabled' : ''; ?>>
                            Screenshots erstellen starten
                        </button>
                        <button type="button" class="button" id="ogs-stop-processing" disabled>
                            Stoppen
                        </button>
                    </div>
                </div>

                <!-- Progress -->
                <div class="ogs-progress-container" id="ogs-progress-container" style="display: none;">
                    <div class="ogs-progress-header">
                        <span class="ogs-progress-status" id="ogs-progress-status">Verarbeite...</span>
                        <span class="ogs-progress-count" id="ogs-progress-count">0 / 0</span>
                    </div>
                    <div class="ogs-progress-bar-container">
                        <div class="ogs-progress-bar" id="ogs-progress-bar" style="width: 0%;"></div>
                    </div>
                    <div class="ogs-progress-current" id="ogs-progress-current"></div>
                </div>

                <!-- Log -->
                <div class="ogs-log-container" id="ogs-log-container" style="display: none;">
                    <h3>Verarbeitungsprotokoll</h3>
                    <div class="ogs-log" id="ogs-log"></div>
                </div>

                <!-- Summary -->
                <div class="ogs-summary" id="ogs-summary" style="display: none;">
                    <h3>Zusammenfassung</h3>
                    <div class="ogs-summary-grid">
                        <div class="ogs-summary-item ogs-summary-success">
                            <span class="ogs-summary-count" id="ogs-summary-success">0</span>
                            <span class="ogs-summary-label">Erfolgreich</span>
                        </div>
                        <div class="ogs-summary-item ogs-summary-error">
                            <span class="ogs-summary-count" id="ogs-summary-error">0</span>
                            <span class="ogs-summary-label">Fehler</span>
                        </div>
                        <div class="ogs-summary-item ogs-summary-skipped">
                            <span class="ogs-summary-count" id="ogs-summary-skipped">0</span>
                            <span class="ogs-summary-label">Übersprungen</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX: Get statistics
     */
    public function ajax_get_stats() {
        check_ajax_referer( 'ogs_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Keine Berechtigung' );
        }

        $discovery = OGS_Discovery::instance();
        $stats     = $discovery->get_stats();
        $totals    = $discovery->get_total_stats();

        wp_send_json_success(
            array(
                'by_type' => $stats,
                'totals'  => $totals,
            )
        );
    }

    /**
     * AJAX: Get items to process
     */
    public function ajax_get_items() {
        check_ajax_referer( 'ogs_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Keine Berechtigung' );
        }

        $type = isset( $_POST['type'] ) && 'all' !== $_POST['type']
            ? sanitize_text_field( wp_unslash( $_POST['type'] ) )
            : null;

        $discovery = OGS_Discovery::instance();
        $items     = $discovery->get_items( $type, true ); // Only missing

        wp_send_json_success( $items );
    }

    /**
     * AJAX: Process single item
     */
    public function ajax_process_single() {
        check_ajax_referer( 'ogs_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Keine Berechtigung' );
        }

        $id          = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
        $entity_type = isset( $_POST['entity_type'] ) ? sanitize_text_field( wp_unslash( $_POST['entity_type'] ) ) : 'post';
        $url         = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '';

        if ( ! $id || ! $url ) {
            wp_send_json_error( 'Ungültige Parameter' );
        }

        $screenshot = OGS_Screenshot::instance();
        $result     = $screenshot->take_screenshot( $id, $entity_type, $url );

        wp_send_json_success( $result );
    }

    /**
     * AJAX: Delete screenshot
     */
    public function ajax_delete_screenshot() {
        check_ajax_referer( 'ogs_admin_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Keine Berechtigung' );
        }

        $id          = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
        $entity_type = isset( $_POST['entity_type'] ) ? sanitize_text_field( wp_unslash( $_POST['entity_type'] ) ) : 'post';

        if ( ! $id ) {
            wp_send_json_error( 'Ungültige Parameter' );
        }

        $screenshot = OGS_Screenshot::instance();
        $deleted    = $screenshot->delete_screenshot( $id, $entity_type );

        if ( $deleted ) {
            wp_send_json_success( array( 'message' => 'Screenshot gelöscht' ) );
        } else {
            wp_send_json_error( 'Screenshot nicht gefunden' );
        }
    }
}
