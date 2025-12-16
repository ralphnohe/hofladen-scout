<?php
/**
 * Claim System
 *
 * Handles claiming of specialist entries by users
 *
 * @package Spezialist_Directory
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * SD_Claim_System Class
 */
class SD_Claim_System {

    /**
     * Single instance
     *
     * @var SD_Claim_System
     */
    protected static $_instance = null;

    /**
     * Main Instance
     *
     * @return SD_Claim_System
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
        add_action( 'wp_ajax_sd_claim_listing', array( $this, 'handle_claim_request' ) );
        add_action( 'wp_ajax_nopriv_sd_claim_listing', array( $this, 'handle_claim_request' ) );
        add_action( 'admin_menu', array( $this, 'add_claims_menu' ) );
        add_filter( 'manage_spezialist_posts_columns', array( $this, 'add_claim_column' ) );
        add_action( 'manage_spezialist_posts_custom_column', array( $this, 'render_claim_column' ), 10, 2 );
    }

    /**
     * Handle claim request
     */
    public function handle_claim_request() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'sd_claim_listing' ) ) {
            wp_send_json_error( array(
                'message' => __( 'Sicherheitsprüfung fehlgeschlagen.', 'spezialist-directory' )
            ) );
        }

        // Check if user is logged in
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array(
                'message' => __( 'Du musst angemeldet sein, um einen Eintrag zu beanspruchen.', 'spezialist-directory' )
            ) );
        }

        $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;

        if ( ! $post_id || 'spezialist' !== get_post_type( $post_id ) ) {
            wp_send_json_error( array(
                'message' => __( 'Ungültiger Eintrag.', 'spezialist-directory' )
            ) );
        }

        // Check if already claimed
        $is_claimed = get_post_meta( $post_id, '_sd_is_claimed', true );

        if ( $is_claimed ) {
            wp_send_json_error( array(
                'message' => __( 'Dieser Eintrag wurde bereits beansprucht.', 'spezialist-directory' )
            ) );
        }

        // Check if user has pending claim for this listing
        $pending_claims = $this->get_pending_claims( $post_id );
        $user_id = get_current_user_id();

        foreach ( $pending_claims as $claim ) {
            if ( $claim->user_id === $user_id ) {
                wp_send_json_error( array(
                    'message' => __( 'Du hast bereits eine Anfrage für diesen Eintrag eingereicht.', 'spezialist-directory' )
                ) );
            }
        }

        // Get additional info
        $message = isset( $_POST['message'] ) ? sanitize_textarea_field( $_POST['message'] ) : '';
        $verification_docs = isset( $_POST['verification_docs'] ) ? sanitize_text_field( $_POST['verification_docs'] ) : '';

        // Create claim request
        global $wpdb;
        $table_name = $wpdb->prefix . 'sd_claims';

        // Create table if not exists
        $this->maybe_create_claims_table();

        $wpdb->insert(
            $table_name,
            array(
                'post_id'           => $post_id,
                'user_id'           => $user_id,
                'message'           => $message,
                'verification_docs' => $verification_docs,
                'status'            => 'pending',
                'created_at'        => current_time( 'mysql' ),
            ),
            array( '%d', '%d', '%s', '%s', '%s', '%s' )
        );

        // Send notification to admin
        $this->send_claim_notification( $post_id, $user_id, $message );

        wp_send_json_success( array(
            'message' => __( 'Deine Anfrage wurde eingereicht und wird überprüft.', 'spezialist-directory' )
        ) );
    }

    /**
     * Maybe create claims table
     */
    private function maybe_create_claims_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sd_claims';
        $charset_collate = $wpdb->get_charset_collate();

        if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) !== $table_name ) {
            $sql = "CREATE TABLE $table_name (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                post_id bigint(20) UNSIGNED NOT NULL,
                user_id bigint(20) UNSIGNED NOT NULL,
                message text,
                verification_docs text,
                status varchar(20) DEFAULT 'pending',
                rejection_reason text,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY post_id (post_id),
                KEY user_id (user_id),
                KEY status (status)
            ) $charset_collate;";

            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql );
        } else {
            // Migrate: Add rejection_reason column if it doesn't exist
            $this->maybe_add_rejection_reason_column();
        }
    }

    /**
     * Add rejection_reason column to existing table
     */
    private function maybe_add_rejection_reason_column() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sd_claims';

        // Check if column exists
        $column = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'rejection_reason'",
            DB_NAME,
            $table_name
        ) );

        if ( empty( $column ) ) {
            $wpdb->query( "ALTER TABLE $table_name ADD COLUMN rejection_reason text AFTER status" );
        }
    }

    /**
     * Get pending claims for a post
     *
     * @param int $post_id
     * @return array
     */
    private function get_pending_claims( $post_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sd_claims';

        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM $table_name WHERE post_id = %d AND status = 'pending'",
            $post_id
        ) );
    }

    /**
     * Check if a user has a pending claim for a specific post
     *
     * @param int $post_id
     * @param int $user_id
     * @return bool
     */
    public function user_has_pending_claim( $post_id, $user_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sd_claims';

        $count = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE post_id = %d AND user_id = %d AND status = 'pending'",
            $post_id,
            $user_id
        ) );

        return $count > 0;
    }

    /**
     * Send claim notification to admin with HTML template
     *
     * @param int    $post_id
     * @param int    $user_id
     * @param string $message
     */
    private function send_claim_notification( $post_id, $user_id, $message ) {
        $post = get_post( $post_id );
        $user = get_user_by( 'id', $user_id );
        $admin_email = get_option( 'admin_email' );

        if ( ! $post || ! $user ) {
            return;
        }

        $subject = sprintf(
            __( '[%s] Neue Claim-Anfrage', 'spezialist-directory' ),
            get_bloginfo( 'name' )
        );

        $site_name     = get_bloginfo( 'name' );
        $dashboard_url = admin_url( 'edit.php?post_type=spezialist&page=sd-claims' );
        $listing_url   = get_permalink( $post_id );
        $edit_url      = get_edit_post_link( $post_id, 'raw' );

        $html_message = $this->get_admin_notification_template(
            array(
                'user_name'     => $user->display_name,
                'user_email'    => $user->user_email,
                'post_title'    => $post->post_title,
                'message'       => $message,
                'dashboard_url' => $dashboard_url,
                'listing_url'   => $listing_url,
                'edit_url'      => $edit_url,
                'site_name'     => $site_name,
            )
        );

        $headers = array( 'Content-Type: text/html; charset=UTF-8' );

        wp_mail( $admin_email, $subject, $html_message, $headers );
    }

    /**
     * Get admin notification HTML template
     *
     * @param array $data Template data
     * @return string HTML email content
     */
    private function get_admin_notification_template( $data ) {
        $site_name = isset( $data['site_name'] ) ? $data['site_name'] : get_bloginfo( 'name' );

        $base_styles = '
            body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f5f5f5; }
            .email-wrapper { max-width: 600px; margin: 0 auto; padding: 20px; }
            .email-container { background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); }
            .email-header { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: #ffffff; padding: 24px 40px; text-align: center; }
            .email-header h1 { margin: 0; font-size: 22px; font-weight: 600; }
            .email-body { padding: 32px 40px; }
            .email-body p { margin: 0 0 16px 0; color: #4b5563; }
            .info-table { width: 100%; border-collapse: collapse; margin: 24px 0; }
            .info-table td { padding: 12px 16px; border-bottom: 1px solid #e5e7eb; }
            .info-table td:first-child { font-weight: 600; color: #374151; width: 30%; background: #f9fafb; }
            .info-table td:last-child { color: #4b5563; }
            .message-box { background: #fffbeb; border: 1px solid #fcd34d; border-radius: 8px; padding: 16px; margin: 20px 0; }
            .message-box strong { color: #92400e; display: block; margin-bottom: 8px; }
            .message-box p { margin: 0; color: #78350f; }
            .btn { display: inline-block; padding: 12px 24px; background: linear-gradient(135deg, #2563EB 0%, #1d4ed8 100%); color: #ffffff !important; text-decoration: none; border-radius: 8px; font-weight: 600; margin: 8px 4px; }
            .btn-success { background: linear-gradient(135deg, #059669 0%, #047857 100%); }
            .email-footer { background: #f9fafb; padding: 20px 40px; text-align: center; border-top: 1px solid #e5e7eb; }
            .email-footer p { margin: 0; font-size: 12px; color: #6b7280; }
        ';

        $html = '<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . esc_html( $site_name ) . '</title>
    <style>' . $base_styles . '</style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-container">
            <div class="email-header">
                <h1>' . __( 'Neue Claim-Anfrage', 'spezialist-directory' ) . '</h1>
            </div>
            <div class="email-body">
                <p>' . __( 'Ein Nutzer möchte einen Hofladen-Eintrag beanspruchen. Bitte überprüfe die Anfrage.', 'spezialist-directory' ) . '</p>

                <table class="info-table">
                    <tr>
                        <td>' . __( 'Eintrag', 'spezialist-directory' ) . '</td>
                        <td><strong>' . esc_html( $data['post_title'] ) . '</strong></td>
                    </tr>
                    <tr>
                        <td>' . __( 'Nutzer', 'spezialist-directory' ) . '</td>
                        <td>' . esc_html( $data['user_name'] ) . '</td>
                    </tr>
                    <tr>
                        <td>' . __( 'E-Mail', 'spezialist-directory' ) . '</td>
                        <td><a href="mailto:' . esc_attr( $data['user_email'] ) . '">' . esc_html( $data['user_email'] ) . '</a></td>
                    </tr>
                </table>';

        if ( ! empty( $data['message'] ) ) {
            $html .= '
                <div class="message-box">
                    <strong>' . __( 'Begründung des Nutzers:', 'spezialist-directory' ) . '</strong>
                    <p>' . nl2br( esc_html( $data['message'] ) ) . '</p>
                </div>';
        }

        $html .= '
                <p style="text-align: center; margin-top: 28px;">
                    <a href="' . esc_url( $data['dashboard_url'] ) . '" class="btn btn-success">' . __( 'Claim verwalten', 'spezialist-directory' ) . '</a>
                    <a href="' . esc_url( $data['listing_url'] ) . '" class="btn">' . __( 'Eintrag ansehen', 'spezialist-directory' ) . '</a>
                </p>
            </div>
            <div class="email-footer">
                <p>' . sprintf( __( 'Admin-Benachrichtigung von %s', 'spezialist-directory' ), esc_html( $site_name ) ) . '</p>
            </div>
        </div>
    </div>
</body>
</html>';

        return $html;
    }

    /**
     * Add claims menu to admin
     */
    public function add_claims_menu() {
        add_submenu_page(
            'edit.php?post_type=spezialist',
            __( 'Dashboard', 'spezialist-directory' ),
            __( 'Dashboard', 'spezialist-directory' ),
            'manage_options',
            'sd-claims',
            array( $this, 'render_claims_page' )
        );
    }

    /**
     * Render admin dashboard page
     */
    public function render_claims_page() {
        // Ensure table has rejection_reason column
        $this->maybe_add_rejection_reason_column();

        // Security fix: Handle ALL claim actions via POST only (no GET-based state changes)
        // Handle approve action via POST
        if ( isset( $_POST['sd_approve_claim'] ) && isset( $_POST['claim_id'] ) && isset( $_POST['_wpnonce'] ) ) {
            if ( wp_verify_nonce( $_POST['_wpnonce'], 'sd_approve_claim' ) ) {
                $this->handle_claim_action( intval( $_POST['claim_id'] ), 'approve', '' );
            }
        }

        // Handle reject action via POST with reason
        if ( isset( $_POST['sd_reject_claim'] ) && isset( $_POST['claim_id'] ) && isset( $_POST['_wpnonce'] ) ) {
            if ( wp_verify_nonce( $_POST['_wpnonce'], 'sd_reject_claim' ) ) {
                $rejection_reason = isset( $_POST['rejection_reason'] ) ? sanitize_textarea_field( $_POST['rejection_reason'] ) : '';
                $this->handle_claim_action( intval( $_POST['claim_id'] ), 'reject', $rejection_reason );
            }
        }

        // Handle revert action via POST with reason
        if ( isset( $_POST['sd_revert_claim'] ) && isset( $_POST['claim_id'] ) && isset( $_POST['_wpnonce'] ) ) {
            if ( wp_verify_nonce( $_POST['_wpnonce'], 'sd_revert_claim' ) ) {
                $revert_reason = isset( $_POST['revert_reason'] ) ? sanitize_textarea_field( $_POST['revert_reason'] ) : '';
                $this->handle_revert_claim( intval( $_POST['claim_id'] ), $revert_reason );
            }
        }

        // Handle approve listing action via POST
        if ( isset( $_POST['sd_approve_listing'] ) && isset( $_POST['post_id'] ) && isset( $_POST['_wpnonce'] ) ) {
            if ( wp_verify_nonce( $_POST['_wpnonce'], 'sd_approve_listing' ) ) {
                wp_update_post( array(
                    'ID'          => intval( $_POST['post_id'] ),
                    'post_status' => 'publish',
                ) );
                add_settings_error( 'sd_claims', 'listing_approved', __( 'Eintrag wurde veröffentlicht.', 'spezialist-directory' ), 'success' );
            }
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'sd_claims';

        // Get current tab
        $current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'claims';

        // Get statistics
        $pending_count = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name WHERE status = 'pending'" );
        $approved_count = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name WHERE status = 'approved'" );
        $rejected_count = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name WHERE status = 'rejected'" );
        $reverted_count = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name WHERE status = 'reverted'" );

        // Get pending listings count
        $pending_listings_count = wp_count_posts( 'spezialist' )->pending;
        $total_users = count_users();
        $subscriber_count = isset( $total_users['avail_roles']['subscriber'] ) ? $total_users['avail_roles']['subscriber'] : 0;

        // Get all claims with optional filter
        $status_filter = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';
        if ( $status_filter ) {
            $claims = $wpdb->get_results( $wpdb->prepare(
                "SELECT * FROM $table_name WHERE status = %s ORDER BY created_at DESC",
                $status_filter
            ) );
        } else {
            $claims = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY created_at DESC" );
        }

        // Get users for Users tab
        $users = get_users( array(
            'role'    => 'subscriber',
            'orderby' => 'registered',
            'order'   => 'DESC',
            'number'  => 50,
        ) );

        ?>
        <div class="wrap sd-admin-dashboard">
            <h1 class="sd-admin-title">
                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="vertical-align: middle; margin-right: 8px;">
                    <path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z" fill="#2563EB"/>
                </svg>
                <?php _e( 'Spezialist Directory Dashboard', 'spezialist-directory' ); ?>
            </h1>

            <!-- Statistics Cards -->
            <div class="sd-admin-stats">
                <div class="sd-stat-card sd-stat-pending">
                    <div class="sd-stat-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z" fill="#f59e0b"/></svg>
                    </div>
                    <div class="sd-stat-content">
                        <span class="sd-stat-number"><?php echo esc_html( $pending_count ); ?></span>
                        <span class="sd-stat-label"><?php _e( 'Ausstehende Claims', 'spezialist-directory' ); ?></span>
                    </div>
                </div>
                <div class="sd-stat-card sd-stat-approved">
                    <div class="sd-stat-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" fill="#059669"/></svg>
                    </div>
                    <div class="sd-stat-content">
                        <span class="sd-stat-number"><?php echo esc_html( $approved_count ); ?></span>
                        <span class="sd-stat-label"><?php _e( 'Genehmigte Claims', 'spezialist-directory' ); ?></span>
                    </div>
                </div>
                <div class="sd-stat-card sd-stat-rejected">
                    <div class="sd-stat-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M12 2C6.47 2 2 6.47 2 12s4.47 10 10 10 10-4.47 10-10S17.53 2 12 2zm5 13.59L15.59 17 12 13.41 8.41 17 7 15.59 10.59 12 7 8.41 8.41 7 12 10.59 15.59 7 17 8.41 13.41 12 17 15.59z" fill="#dc2626"/></svg>
                    </div>
                    <div class="sd-stat-content">
                        <span class="sd-stat-number"><?php echo esc_html( $rejected_count ); ?></span>
                        <span class="sd-stat-label"><?php _e( 'Abgelehnte Claims', 'spezialist-directory' ); ?></span>
                    </div>
                </div>
                <div class="sd-stat-card sd-stat-users">
                    <div class="sd-stat-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" fill="#6366f1"/></svg>
                    </div>
                    <div class="sd-stat-content">
                        <span class="sd-stat-number"><?php echo esc_html( $subscriber_count ); ?></span>
                        <span class="sd-stat-label"><?php _e( 'Registrierte Nutzer', 'spezialist-directory' ); ?></span>
                    </div>
                </div>
            </div>

            <!-- Tab Navigation -->
            <nav class="nav-tab-wrapper sd-admin-tabs">
                <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=spezialist&page=sd-claims&tab=claims' ) ); ?>"
                   class="nav-tab <?php echo $current_tab === 'claims' ? 'nav-tab-active' : ''; ?>">
                    <?php _e( 'Claim-Anfragen', 'spezialist-directory' ); ?>
                    <?php if ( $pending_count > 0 ) : ?>
                        <span class="sd-badge sd-badge-warning"><?php echo esc_html( $pending_count ); ?></span>
                    <?php endif; ?>
                </a>
                <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=spezialist&page=sd-claims&tab=users' ) ); ?>"
                   class="nav-tab <?php echo $current_tab === 'users' ? 'nav-tab-active' : ''; ?>">
                    <?php _e( 'Benutzer', 'spezialist-directory' ); ?>
                </a>
                <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=spezialist&page=sd-claims&tab=pending_listings' ) ); ?>"
                   class="nav-tab <?php echo $current_tab === 'pending_listings' ? 'nav-tab-active' : ''; ?>">
                    <?php _e( 'Ausstehende Einträge', 'spezialist-directory' ); ?>
                    <?php if ( $pending_listings_count > 0 ) : ?>
                        <span class="sd-badge sd-badge-warning"><?php echo esc_html( $pending_listings_count ); ?></span>
                    <?php endif; ?>
                </a>
            </nav>

            <!-- Tab Content -->
            <div class="sd-admin-content">
                <?php if ( $current_tab === 'claims' ) : ?>
                    <!-- Claims Tab -->
                    <div class="sd-admin-filters">
                        <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=spezialist&page=sd-claims&tab=claims' ) ); ?>"
                           class="button <?php echo ! $status_filter ? 'button-primary' : ''; ?>">
                            <?php _e( 'Alle', 'spezialist-directory' ); ?>
                        </a>
                        <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=spezialist&page=sd-claims&tab=claims&status=pending' ) ); ?>"
                           class="button <?php echo $status_filter === 'pending' ? 'button-primary' : ''; ?>">
                            <?php _e( 'Ausstehend', 'spezialist-directory' ); ?> (<?php echo esc_html( $pending_count ); ?>)
                        </a>
                        <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=spezialist&page=sd-claims&tab=claims&status=approved' ) ); ?>"
                           class="button <?php echo $status_filter === 'approved' ? 'button-primary' : ''; ?>">
                            <?php _e( 'Genehmigt', 'spezialist-directory' ); ?>
                        </a>
                        <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=spezialist&page=sd-claims&tab=claims&status=rejected' ) ); ?>"
                           class="button <?php echo $status_filter === 'rejected' ? 'button-primary' : ''; ?>">
                            <?php _e( 'Abgelehnt', 'spezialist-directory' ); ?>
                        </a>
                        <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=spezialist&page=sd-claims&tab=claims&status=reverted' ) ); ?>"
                           class="button <?php echo $status_filter === 'reverted' ? 'button-primary' : ''; ?>">
                            <?php _e( 'Widerrufen', 'spezialist-directory' ); ?> (<?php echo esc_html( $reverted_count ); ?>)
                        </a>
                    </div>

                    <table class="wp-list-table widefat fixed striped sd-claims-table">
                        <thead>
                            <tr>
                                <th style="width: 20%;"><?php _e( 'Eintrag', 'spezialist-directory' ); ?></th>
                                <th style="width: 20%;"><?php _e( 'Nutzer', 'spezialist-directory' ); ?></th>
                                <th style="width: 25%;"><?php _e( 'Begründung', 'spezialist-directory' ); ?></th>
                                <th style="width: 10%;"><?php _e( 'Status', 'spezialist-directory' ); ?></th>
                                <th style="width: 10%;"><?php _e( 'Datum', 'spezialist-directory' ); ?></th>
                                <th style="width: 15%;"><?php _e( 'Aktionen', 'spezialist-directory' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ( empty( $claims ) ) : ?>
                                <tr>
                                    <td colspan="6" class="sd-empty-state">
                                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z" fill="#9ca3af"/></svg>
                                        <p><?php _e( 'Keine Claim-Anfragen vorhanden.', 'spezialist-directory' ); ?></p>
                                    </td>
                                </tr>
                            <?php else : ?>
                                <?php foreach ( $claims as $claim ) :
                                    $post = get_post( $claim->post_id );
                                    $user = get_user_by( 'id', $claim->user_id );
                                ?>
                                    <tr class="sd-claim-row sd-claim-<?php echo esc_attr( $claim->status ); ?>">
                                        <td>
                                            <?php if ( $post ) : ?>
                                                <strong>
                                                    <a href="<?php echo esc_url( get_permalink( $claim->post_id ) ); ?>" target="_blank">
                                                        <?php echo esc_html( $post->post_title ); ?>
                                                    </a>
                                                </strong>
                                                <div class="row-actions">
                                                    <span><a href="<?php echo esc_url( get_edit_post_link( $claim->post_id ) ); ?>"><?php _e( 'Bearbeiten', 'spezialist-directory' ); ?></a> | </span>
                                                    <span><a href="<?php echo esc_url( get_permalink( $claim->post_id ) ); ?>" target="_blank"><?php _e( 'Ansehen', 'spezialist-directory' ); ?></a></span>
                                                </div>
                                            <?php else : ?>
                                                <span class="sd-deleted"><?php _e( 'Eintrag gelöscht', 'spezialist-directory' ); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ( $user ) : ?>
                                                <div class="sd-user-info">
                                                    <?php echo get_avatar( $user->ID, 32 ); ?>
                                                    <div>
                                                        <strong><?php echo esc_html( $user->display_name ); ?></strong>
                                                        <br><small><a href="mailto:<?php echo esc_attr( $user->user_email ); ?>"><?php echo esc_html( $user->user_email ); ?></a></small>
                                                    </div>
                                                </div>
                                            <?php else : ?>
                                                <span class="sd-deleted"><?php _e( 'Nutzer gelöscht', 'spezialist-directory' ); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="sd-claim-message"><?php echo esc_html( $claim->message ); ?></div>
                                            <?php if ( $claim->status === 'rejected' && ! empty( $claim->rejection_reason ) ) : ?>
                                                <div class="sd-rejection-reason">
                                                    <strong><?php _e( 'Ablehnungsgrund:', 'spezialist-directory' ); ?></strong>
                                                    <?php echo esc_html( $claim->rejection_reason ); ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $status_labels = array(
                                                'pending'  => '<span class="sd-status sd-status-pending">⏳ ' . __( 'Ausstehend', 'spezialist-directory' ) . '</span>',
                                                'approved' => '<span class="sd-status sd-status-approved">✓ ' . __( 'Genehmigt', 'spezialist-directory' ) . '</span>',
                                                'rejected' => '<span class="sd-status sd-status-rejected">✗ ' . __( 'Abgelehnt', 'spezialist-directory' ) . '</span>',
                                                'reverted' => '<span class="sd-status sd-status-reverted">↩ ' . __( 'Widerrufen', 'spezialist-directory' ) . '</span>',
                                            );
                                            // Security fix: Escape fallback value to prevent XSS
                                            echo isset( $status_labels[ $claim->status ] ) ? $status_labels[ $claim->status ] : esc_html( $claim->status );
                                            ?>
                                        </td>
                                        <td>
                                            <span title="<?php echo esc_attr( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $claim->created_at ) ) ); ?>">
                                                <?php echo esc_html( human_time_diff( strtotime( $claim->created_at ), current_time( 'timestamp' ) ) ); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ( 'pending' === $claim->status ) : ?>
                                                <div class="sd-action-buttons">
                                                    <!-- Security fix: Use POST form instead of GET link for approve action -->
                                                    <form method="post" action="" style="display: inline;">
                                                        <?php wp_nonce_field( 'sd_approve_claim' ); ?>
                                                        <input type="hidden" name="claim_id" value="<?php echo esc_attr( $claim->id ); ?>">
                                                        <input type="hidden" name="sd_approve_claim" value="1">
                                                        <button type="submit" class="button button-primary button-small"
                                                                onclick="return confirm('<?php esc_attr_e( 'Claim wirklich genehmigen? Der Nutzer wird zum Besitzer des Eintrags.', 'spezialist-directory' ); ?>');">
                                                            <?php _e( 'Genehmigen', 'spezialist-directory' ); ?>
                                                        </button>
                                                    </form>
                                                    <button type="button"
                                                            class="button button-small sd-reject-btn"
                                                            data-claim-id="<?php echo esc_attr( $claim->id ); ?>"
                                                            data-user-name="<?php echo esc_attr( $user ? $user->display_name : '' ); ?>"
                                                            data-post-title="<?php echo esc_attr( $post ? $post->post_title : '' ); ?>">
                                                        <?php _e( 'Ablehnen', 'spezialist-directory' ); ?>
                                                    </button>
                                                </div>
                                            <?php elseif ( 'approved' === $claim->status ) : ?>
                                                <div class="sd-action-buttons">
                                                    <button type="button"
                                                            class="button button-small sd-revert-btn"
                                                            data-claim-id="<?php echo esc_attr( $claim->id ); ?>"
                                                            data-user-name="<?php echo esc_attr( $user ? $user->display_name : '' ); ?>"
                                                            data-post-title="<?php echo esc_attr( $post ? $post->post_title : '' ); ?>">
                                                        <?php _e( 'Widerrufen', 'spezialist-directory' ); ?>
                                                    </button>
                                                </div>
                                            <?php else : ?>
                                                <span class="sd-action-done">—</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>

                <?php elseif ( $current_tab === 'users' ) : ?>
                    <!-- Users Tab -->
                    <table class="wp-list-table widefat fixed striped sd-users-table">
                        <thead>
                            <tr>
                                <th style="width: 5%;"><?php _e( 'ID', 'spezialist-directory' ); ?></th>
                                <th style="width: 25%;"><?php _e( 'Nutzer', 'spezialist-directory' ); ?></th>
                                <th style="width: 25%;"><?php _e( 'E-Mail', 'spezialist-directory' ); ?></th>
                                <th style="width: 15%;"><?php _e( 'Registriert', 'spezialist-directory' ); ?></th>
                                <th style="width: 15%;"><?php _e( 'Claims', 'spezialist-directory' ); ?></th>
                                <th style="width: 15%;"><?php _e( 'Aktionen', 'spezialist-directory' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ( empty( $users ) ) : ?>
                                <tr>
                                    <td colspan="6" class="sd-empty-state">
                                        <p><?php _e( 'Keine Nutzer gefunden.', 'spezialist-directory' ); ?></p>
                                    </td>
                                </tr>
                            <?php else : ?>
                                <?php foreach ( $users as $user ) :
                                    $user_claims = $wpdb->get_var( $wpdb->prepare(
                                        "SELECT COUNT(*) FROM $table_name WHERE user_id = %d",
                                        $user->ID
                                    ) );
                                    $user_approved = $wpdb->get_var( $wpdb->prepare(
                                        "SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND status = 'approved'",
                                        $user->ID
                                    ) );
                                ?>
                                    <tr>
                                        <td><?php echo esc_html( $user->ID ); ?></td>
                                        <td>
                                            <div class="sd-user-info">
                                                <?php echo get_avatar( $user->ID, 32 ); ?>
                                                <div>
                                                    <strong><?php echo esc_html( $user->display_name ); ?></strong>
                                                    <br><small>@<?php echo esc_html( $user->user_login ); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><a href="mailto:<?php echo esc_attr( $user->user_email ); ?>"><?php echo esc_html( $user->user_email ); ?></a></td>
                                        <td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $user->user_registered ) ) ); ?></td>
                                        <td>
                                            <?php if ( $user_claims > 0 ) : ?>
                                                <span class="sd-user-claims">
                                                    <?php echo esc_html( $user_approved ); ?>/<?php echo esc_html( $user_claims ); ?>
                                                    <?php _e( 'genehmigt', 'spezialist-directory' ); ?>
                                                </span>
                                            <?php else : ?>
                                                <span class="sd-no-claims"><?php _e( 'Keine Claims', 'spezialist-directory' ); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="<?php echo esc_url( admin_url( 'user-edit.php?user_id=' . $user->ID ) ); ?>" class="button button-small">
                                                <?php _e( 'Bearbeiten', 'spezialist-directory' ); ?>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>

                <?php elseif ( $current_tab === 'pending_listings' ) : ?>
                    <!-- Pending Listings Tab -->
                    <?php
                    $pending_listings = get_posts( array(
                        'post_type'      => 'spezialist',
                        'post_status'    => 'pending',
                        'posts_per_page' => 50,
                        'orderby'        => 'date',
                        'order'          => 'DESC',
                    ) );
                    ?>
                    <table class="wp-list-table widefat fixed striped sd-pending-listings-table">
                        <thead>
                            <tr>
                                <th style="width: 35%;"><?php _e( 'Eintrag', 'spezialist-directory' ); ?></th>
                                <th style="width: 20%;"><?php _e( 'Autor', 'spezialist-directory' ); ?></th>
                                <th style="width: 15%;"><?php _e( 'Kategorie', 'spezialist-directory' ); ?></th>
                                <th style="width: 15%;"><?php _e( 'Eingereicht', 'spezialist-directory' ); ?></th>
                                <th style="width: 15%;"><?php _e( 'Aktionen', 'spezialist-directory' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ( empty( $pending_listings ) ) : ?>
                                <tr>
                                    <td colspan="5" class="sd-empty-state">
                                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" fill="#059669"/></svg>
                                        <p><?php _e( 'Keine ausstehenden Einträge vorhanden.', 'spezialist-directory' ); ?></p>
                                    </td>
                                </tr>
                            <?php else : ?>
                                <?php foreach ( $pending_listings as $listing ) :
                                    $author = get_user_by( 'id', $listing->post_author );
                                    $categories = get_the_terms( $listing->ID, 'spezialist_category' );
                                    $category_name = $categories && ! is_wp_error( $categories ) ? $categories[0]->name : '—';
                                ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo esc_html( $listing->post_title ); ?></strong>
                                            <div class="row-actions">
                                                <span class="edit">
                                                    <a href="<?php echo esc_url( admin_url( 'post.php?post=' . $listing->ID . '&action=edit' ) ); ?>">
                                                        <?php _e( 'Bearbeiten', 'spezialist-directory' ); ?>
                                                    </a>
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ( $author ) : ?>
                                                <div class="sd-user-info">
                                                    <?php echo get_avatar( $author->ID, 32 ); ?>
                                                    <div>
                                                        <strong><?php echo esc_html( $author->display_name ); ?></strong>
                                                        <br><small><?php echo esc_html( $author->user_email ); ?></small>
                                                    </div>
                                                </div>
                                            <?php else : ?>
                                                <span class="sd-deleted"><?php _e( 'Unbekannt', 'spezialist-directory' ); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo esc_html( $category_name ); ?></td>
                                        <td>
                                            <span title="<?php echo esc_attr( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $listing->post_date ) ) ); ?>">
                                                <?php echo esc_html( human_time_diff( strtotime( $listing->post_date ), current_time( 'timestamp' ) ) ); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="sd-action-buttons">
                                                <form method="post" action="" style="display: inline;">
                                                    <?php wp_nonce_field( 'sd_approve_listing' ); ?>
                                                    <input type="hidden" name="post_id" value="<?php echo esc_attr( $listing->ID ); ?>">
                                                    <input type="hidden" name="sd_approve_listing" value="1">
                                                    <button type="submit" class="button button-primary button-small"
                                                            onclick="return confirm('<?php esc_attr_e( 'Eintrag wirklich veröffentlichen?', 'spezialist-directory' ); ?>');">
                                                        <?php _e( 'Genehmigen', 'spezialist-directory' ); ?>
                                                    </button>
                                                </form>
                                                <a href="<?php echo esc_url( get_preview_post_link( $listing->ID ) ); ?>"
                                                   class="button button-small" target="_blank">
                                                    <?php _e( 'Ansehen', 'spezialist-directory' ); ?>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Rejection Modal -->
        <div id="sd-reject-modal" class="sd-admin-modal" style="display: none;">
            <div class="sd-modal-backdrop"></div>
            <div class="sd-modal-content">
                <div class="sd-modal-header">
                    <h2><?php _e( 'Claim ablehnen', 'spezialist-directory' ); ?></h2>
                    <button type="button" class="sd-modal-close">&times;</button>
                </div>
                <form method="post" action="">
                    <?php wp_nonce_field( 'sd_reject_claim' ); ?>
                    <input type="hidden" name="claim_id" id="sd-reject-claim-id" value="">
                    <input type="hidden" name="sd_reject_claim" value="1">

                    <div class="sd-modal-body">
                        <p class="sd-modal-info">
                            <?php _e( 'Du lehnst den Claim von', 'spezialist-directory' ); ?>
                            <strong id="sd-reject-user-name"></strong>
                            <?php _e( 'für', 'spezialist-directory' ); ?>
                            <strong id="sd-reject-post-title"></strong>
                            <?php _e( 'ab.', 'spezialist-directory' ); ?>
                        </p>

                        <div class="sd-form-group">
                            <label for="sd-rejection-reason">
                                <?php _e( 'Ablehnungsgrund', 'spezialist-directory' ); ?>
                                <span class="required">*</span>
                            </label>
                            <textarea name="rejection_reason" id="sd-rejection-reason" rows="4" required
                                placeholder="<?php esc_attr_e( 'Bitte gib einen Grund für die Ablehnung an. Dieser wird dem Nutzer per E-Mail mitgeteilt.', 'spezialist-directory' ); ?>"></textarea>
                        </div>
                    </div>

                    <div class="sd-modal-footer">
                        <button type="button" class="button sd-modal-cancel"><?php _e( 'Abbrechen', 'spezialist-directory' ); ?></button>
                        <button type="submit" class="button button-primary"><?php _e( 'Claim ablehnen', 'spezialist-directory' ); ?></button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Revert Modal -->
        <div id="sd-revert-modal" class="sd-admin-modal" style="display: none;">
            <div class="sd-modal-backdrop"></div>
            <div class="sd-modal-content">
                <div class="sd-modal-header">
                    <h2><?php _e( 'Claim widerrufen', 'spezialist-directory' ); ?></h2>
                    <button type="button" class="sd-modal-close">&times;</button>
                </div>
                <form method="post" action="">
                    <?php wp_nonce_field( 'sd_revert_claim' ); ?>
                    <input type="hidden" name="claim_id" id="sd-revert-claim-id" value="">
                    <input type="hidden" name="sd_revert_claim" value="1">

                    <div class="sd-modal-body">
                        <p class="sd-modal-info">
                            <?php _e( 'Du widerrufst den Claim von', 'spezialist-directory' ); ?>
                            <strong id="sd-revert-user-name"></strong>
                            <?php _e( 'für', 'spezialist-directory' ); ?>
                            <strong id="sd-revert-post-title"></strong>.
                        </p>
                        <p class="sd-modal-warning" style="color: #b45309; margin-bottom: 12px;">
                            <?php _e( 'Der Eintrag wird dem Admin zurückgegeben und der Nutzer verliert alle Bearbeitungsrechte.', 'spezialist-directory' ); ?>
                        </p>

                        <div class="sd-form-group">
                            <label for="sd-revert-reason">
                                <?php _e( 'Grund für Widerruf', 'spezialist-directory' ); ?>
                                <span class="optional">(<?php _e( 'optional', 'spezialist-directory' ); ?>)</span>
                            </label>
                            <textarea name="revert_reason" id="sd-revert-reason" rows="4"
                                placeholder="<?php esc_attr_e( 'Optional: Gib einen Grund für den Widerruf an. Dieser wird dem Nutzer per E-Mail mitgeteilt.', 'spezialist-directory' ); ?>"></textarea>
                        </div>
                    </div>

                    <div class="sd-modal-footer">
                        <button type="button" class="button sd-modal-cancel"><?php _e( 'Abbrechen', 'spezialist-directory' ); ?></button>
                        <button type="submit" class="button button-primary" style="background: #b45309; border-color: #b45309;"><?php _e( 'Claim widerrufen', 'spezialist-directory' ); ?></button>
                    </div>
                </form>
            </div>
        </div>

        <style>
            /* Admin Dashboard Styles */
            .sd-admin-dashboard { max-width: 1400px; }
            .sd-admin-title { display: flex; align-items: center; margin-bottom: 20px; }

            /* Stats Cards */
            .sd-admin-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
            .sd-stat-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; display: flex; align-items: center; gap: 16px; }
            .sd-stat-icon { width: 48px; height: 48px; border-radius: 8px; display: flex; align-items: center; justify-content: center; }
            .sd-stat-pending .sd-stat-icon { background: #fef3c7; }
            .sd-stat-approved .sd-stat-icon { background: #d1fae5; }
            .sd-stat-rejected .sd-stat-icon { background: #fee2e2; }
            .sd-stat-users .sd-stat-icon { background: #e0e7ff; }
            .sd-stat-number { display: block; font-size: 28px; font-weight: 700; color: #1f2937; }
            .sd-stat-label { font-size: 13px; color: #6b7280; }

            /* Tabs */
            .sd-admin-tabs { margin-bottom: 0; border-bottom: 1px solid #ccc; }
            .sd-admin-tabs .nav-tab { padding: 10px 16px; }
            .sd-badge { display: inline-flex; align-items: center; justify-content: center; min-width: 20px; height: 20px; padding: 0 6px; border-radius: 10px; font-size: 11px; font-weight: 600; margin-left: 6px; }
            .sd-badge-warning { background: #f59e0b; color: #fff; }

            /* Content Area */
            .sd-admin-content { background: #fff; border: 1px solid #ccc; border-top: none; padding: 20px; }
            .sd-admin-filters { margin-bottom: 16px; display: flex; gap: 8px; }

            /* Table Styles */
            .sd-claims-table td, .sd-users-table td { vertical-align: middle; }
            .sd-user-info { display: flex; align-items: center; gap: 10px; }
            .sd-user-info img { border-radius: 50%; }
            .sd-claim-message { max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
            .sd-rejection-reason { margin-top: 8px; padding: 8px; background: #fef2f2; border-radius: 4px; font-size: 12px; color: #991b1b; }

            /* Status Badges */
            .sd-status { display: inline-flex; align-items: center; gap: 4px; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500; }
            .sd-status-pending { background: #fef3c7; color: #92400e; }
            .sd-status-approved { background: #d1fae5; color: #065f46; }
            .sd-status-rejected { background: #fee2e2; color: #991b1b; }
            .sd-status-reverted { background: #fef3c7; color: #b45309; }

            /* Action Buttons */
            .sd-action-buttons { display: flex; gap: 4px; }
            .sd-action-done { color: #9ca3af; }
            .sd-deleted { color: #9ca3af; font-style: italic; }

            /* Empty State */
            .sd-empty-state { text-align: center; padding: 40px !important; color: #6b7280; }
            .sd-empty-state svg { margin-bottom: 12px; opacity: 0.5; }

            /* User Claims */
            .sd-user-claims { color: #059669; font-weight: 500; }
            .sd-no-claims { color: #9ca3af; }

            /* Modal Styles */
            .sd-admin-modal { position: fixed; inset: 0; z-index: 100000; display: flex; align-items: center; justify-content: center; }
            .sd-modal-backdrop { position: absolute; inset: 0; background: rgba(0,0,0,0.5); }
            .sd-modal-content { position: relative; width: 100%; max-width: 500px; background: #fff; border-radius: 8px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }
            .sd-modal-header { display: flex; align-items: center; justify-content: space-between; padding: 16px 20px; border-bottom: 1px solid #e5e7eb; }
            .sd-modal-header h2 { margin: 0; font-size: 18px; }
            .sd-modal-close { background: none; border: none; font-size: 24px; cursor: pointer; color: #6b7280; }
            .sd-modal-body { padding: 20px; }
            .sd-modal-info { margin-bottom: 16px; padding: 12px; background: #f3f4f6; border-radius: 6px; }
            .sd-form-group label { display: block; margin-bottom: 6px; font-weight: 500; }
            .sd-form-group textarea { width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; resize: vertical; }
            .sd-modal-footer { display: flex; justify-content: flex-end; gap: 8px; padding: 16px 20px; border-top: 1px solid #e5e7eb; background: #f9fafb; border-radius: 0 0 8px 8px; }
        </style>

        <script>
        jQuery(document).ready(function($) {
            // Reject Modal
            $('.sd-reject-btn').on('click', function() {
                var claimId = $(this).data('claim-id');
                var userName = $(this).data('user-name');
                var postTitle = $(this).data('post-title');

                $('#sd-reject-claim-id').val(claimId);
                $('#sd-reject-user-name').text(userName);
                $('#sd-reject-post-title').text(postTitle);
                $('#sd-reject-modal').show();
            });

            // Revert Modal
            $('.sd-revert-btn').on('click', function() {
                var claimId = $(this).data('claim-id');
                var userName = $(this).data('user-name');
                var postTitle = $(this).data('post-title');

                $('#sd-revert-claim-id').val(claimId);
                $('#sd-revert-user-name').text(userName);
                $('#sd-revert-post-title').text(postTitle);
                $('#sd-revert-modal').show();
            });

            // Close Modal
            $('.sd-modal-close, .sd-modal-cancel, .sd-modal-backdrop').on('click', function() {
                $('#sd-reject-modal').hide();
                $('#sd-revert-modal').hide();
            });

            // Close on Escape
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    $('#sd-reject-modal').hide();
                    $('#sd-revert-modal').hide();
                }
            });
        });
        </script>
        <?php
    }

    /**
     * Handle claim action (approve/reject)
     *
     * @param int    $claim_id
     * @param string $action
     * @param string $rejection_reason Optional rejection reason for reject action
     */
    private function handle_claim_action( $claim_id, $action, $rejection_reason = '' ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sd_claims';

        $claim = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $claim_id
        ) );

        if ( ! $claim ) {
            return;
        }

        if ( 'approve' === $action ) {
            // Update claim status
            $wpdb->update(
                $table_name,
                array( 'status' => 'approved' ),
                array( 'id' => $claim_id ),
                array( '%s' ),
                array( '%d' )
            );

            // Update post meta
            update_post_meta( $claim->post_id, '_sd_is_claimed', '1' );
            update_post_meta( $claim->post_id, '_sd_claimed_by', $claim->user_id );
            update_post_meta( $claim->post_id, '_sd_claimed_date', current_time( 'mysql' ) );

            // Change post author
            wp_update_post( array(
                'ID'          => $claim->post_id,
                'post_author' => $claim->user_id,
            ) );

            // Send notification to user
            $this->send_claim_approved_notification( $claim );

            add_settings_error( 'sd_claims', 'claim_approved', __( 'Claim wurde genehmigt.', 'spezialist-directory' ), 'success' );

        } elseif ( 'reject' === $action ) {
            // Update claim status with rejection reason
            $wpdb->update(
                $table_name,
                array(
                    'status'           => 'rejected',
                    'rejection_reason' => $rejection_reason,
                ),
                array( 'id' => $claim_id ),
                array( '%s', '%s' ),
                array( '%d' )
            );

            // Send notification to user with rejection reason
            $this->send_claim_rejected_notification( $claim, $rejection_reason );

            add_settings_error( 'sd_claims', 'claim_rejected', __( 'Claim wurde abgelehnt.', 'spezialist-directory' ), 'success' );
        }

        settings_errors( 'sd_claims' );
    }

    /**
     * Handle reverting an approved claim
     *
     * @param int    $claim_id
     * @param string $revert_reason
     */
    private function handle_revert_claim( $claim_id, $revert_reason = '' ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sd_claims';

        $claim = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $claim_id
        ) );

        if ( ! $claim || 'approved' !== $claim->status ) {
            add_settings_error( 'sd_claims', 'claim_revert_failed', __( 'Claim konnte nicht widerrufen werden.', 'spezialist-directory' ), 'error' );
            return;
        }

        // Update claim status to reverted
        $wpdb->update(
            $table_name,
            array(
                'status'           => 'reverted',
                'rejection_reason' => $revert_reason,
            ),
            array( 'id' => $claim_id ),
            array( '%s', '%s' ),
            array( '%d' )
        );

        // Remove claim meta from post
        delete_post_meta( $claim->post_id, '_sd_is_claimed' );
        delete_post_meta( $claim->post_id, '_sd_claimed_by' );
        delete_post_meta( $claim->post_id, '_sd_claimed_date' );

        // Set post author to admin (ID 1)
        wp_update_post( array(
            'ID'          => $claim->post_id,
            'post_author' => 1,
        ) );

        // Send notification to user
        $this->send_claim_reverted_notification( $claim, $revert_reason );

        add_settings_error( 'sd_claims', 'claim_reverted', __( 'Claim wurde widerrufen.', 'spezialist-directory' ), 'success' );
        settings_errors( 'sd_claims' );
    }

    /**
     * Send claim reverted notification with HTML template
     *
     * @param object $claim
     * @param string $revert_reason
     */
    private function send_claim_reverted_notification( $claim, $revert_reason = '' ) {
        $post = get_post( $claim->post_id );
        $user = get_user_by( 'id', $claim->user_id );

        if ( ! $post || ! $user ) {
            return;
        }

        $subject = sprintf(
            __( '[%s] Ihr Claim wurde widerrufen', 'spezialist-directory' ),
            get_bloginfo( 'name' )
        );

        $site_name     = get_bloginfo( 'name' );
        $listing_url   = get_permalink( $claim->post_id );

        $html_message = $this->get_email_template(
            'reverted',
            array(
                'user_name'     => $user->display_name,
                'post_title'    => $post->post_title,
                'listing_url'   => $listing_url,
                'site_name'     => $site_name,
                'reason'        => $revert_reason,
            )
        );

        $headers = array( 'Content-Type: text/html; charset=UTF-8' );

        wp_mail( $user->user_email, $subject, $html_message, $headers );
    }

    /**
     * Send claim approved notification with HTML template
     *
     * @param object $claim
     */
    private function send_claim_approved_notification( $claim ) {
        $post = get_post( $claim->post_id );
        $user = get_user_by( 'id', $claim->user_id );

        if ( ! $post || ! $user ) {
            return;
        }

        $subject = sprintf(
            __( '[%s] Ihr Claim wurde genehmigt!', 'spezialist-directory' ),
            get_bloginfo( 'name' )
        );

        $dashboard_url = sd_get_page_url( 'mein-dashboard/' );
        $listing_url   = get_permalink( $claim->post_id );
        $site_name     = get_bloginfo( 'name' );

        $html_message = $this->get_email_template(
            'approved',
            array(
                'user_name'     => $user->display_name,
                'post_title'    => $post->post_title,
                'dashboard_url' => $dashboard_url,
                'listing_url'   => $listing_url,
                'site_name'     => $site_name,
            )
        );

        $headers = array( 'Content-Type: text/html; charset=UTF-8' );

        wp_mail( $user->user_email, $subject, $html_message, $headers );
    }

    /**
     * Send claim rejected notification with HTML template
     *
     * @param object $claim
     * @param string $rejection_reason
     */
    private function send_claim_rejected_notification( $claim, $rejection_reason = '' ) {
        $post = get_post( $claim->post_id );
        $user = get_user_by( 'id', $claim->user_id );

        if ( ! $post || ! $user ) {
            return;
        }

        $subject = sprintf(
            __( '[%s] Ihr Claim wurde abgelehnt', 'spezialist-directory' ),
            get_bloginfo( 'name' )
        );

        $site_name   = get_bloginfo( 'name' );
        $contact_url = home_url( '/kontakt/' );

        $html_message = $this->get_email_template(
            'rejected',
            array(
                'user_name'        => $user->display_name,
                'post_title'       => $post->post_title,
                'rejection_reason' => $rejection_reason,
                'contact_url'      => $contact_url,
                'site_name'        => $site_name,
            )
        );

        $headers = array( 'Content-Type: text/html; charset=UTF-8' );

        wp_mail( $user->user_email, $subject, $html_message, $headers );
    }

    /**
     * Get HTML email template
     *
     * @param string $type     Template type: 'approved', 'rejected', 'admin_notification'
     * @param array  $data     Template data
     * @return string          HTML email content
     */
    private function get_email_template( $type, $data ) {
        $site_name = isset( $data['site_name'] ) ? $data['site_name'] : get_bloginfo( 'name' );

        // Base styles for email
        $base_styles = '
            body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f5f5f5; }
            .email-wrapper { max-width: 600px; margin: 0 auto; padding: 20px; }
            .email-container { background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); }
            .email-header { background: linear-gradient(135deg, #2563EB 0%, #1d4ed8 100%); color: #ffffff; padding: 32px 40px; text-align: center; }
            .email-header h1 { margin: 0; font-size: 24px; font-weight: 600; }
            .email-header .icon { font-size: 48px; margin-bottom: 16px; }
            .email-body { padding: 40px; }
            .email-body p { margin: 0 0 16px 0; color: #4b5563; }
            .email-body .greeting { font-size: 18px; color: #1f2937; margin-bottom: 24px; }
            .highlight-box { background: #f0f9ff; border-left: 4px solid #2563EB; padding: 16px 20px; margin: 24px 0; border-radius: 0 8px 8px 0; }
            .highlight-box.success { background: #ecfdf5; border-color: #059669; }
            .highlight-box.warning { background: #fffbeb; border-color: #f59e0b; }
            .highlight-box.error { background: #fef2f2; border-color: #dc2626; }
            .highlight-box strong { color: #1f2937; display: block; margin-bottom: 4px; }
            .btn { display: inline-block; padding: 14px 28px; background: linear-gradient(135deg, #2563EB 0%, #1d4ed8 100%); color: #ffffff !important; text-decoration: none; border-radius: 8px; font-weight: 600; margin: 16px 0; }
            .btn-secondary { background: linear-gradient(135deg, #059669 0%, #047857 100%); }
            .email-footer { background: #f9fafb; padding: 24px 40px; text-align: center; border-top: 1px solid #e5e7eb; }
            .email-footer p { margin: 0; font-size: 13px; color: #6b7280; }
            .email-footer a { color: #2563EB; text-decoration: none; }
        ';

        $html = '<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . esc_html( $site_name ) . '</title>
    <style>' . $base_styles . '</style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-container">';

        switch ( $type ) {
            case 'approved':
                $html .= '
            <div class="email-header">
                <div class="icon">&#10003;</div>
                <h1>' . __( 'Claim genehmigt!', 'spezialist-directory' ) . '</h1>
            </div>
            <div class="email-body">
                <p class="greeting">' . sprintf( __( 'Hallo %s,', 'spezialist-directory' ), esc_html( $data['user_name'] ) ) . '</p>

                <p>' . __( 'Wir freuen uns, dir mitteilen zu können, dass dein Claim erfolgreich genehmigt wurde.', 'spezialist-directory' ) . '</p>

                <div class="highlight-box success">
                    <strong>' . __( 'Dein Eintrag:', 'spezialist-directory' ) . '</strong>
                    ' . esc_html( $data['post_title'] ) . '
                </div>

                <p>' . __( 'Du bist jetzt der verifizierte Inhaber dieses Eintrags und kannst:', 'spezialist-directory' ) . '</p>
                <ul>
                    <li>' . __( 'Alle Informationen bearbeiten und aktualisieren', 'spezialist-directory' ) . '</li>
                    <li>' . __( 'Kontaktdaten und Öffnungszeiten ändern', 'spezialist-directory' ) . '</li>
                    <li>' . __( 'Fotos und Beschreibungen anpassen', 'spezialist-directory' ) . '</li>
                </ul>

                <p style="text-align: center; margin-top: 32px;">
                    <a href="' . esc_url( $data['dashboard_url'] ) . '" class="btn">' . __( 'Zum Dashboard', 'spezialist-directory' ) . '</a>
                    <a href="' . esc_url( $data['listing_url'] ) . '" class="btn btn-secondary">' . __( 'Eintrag ansehen', 'spezialist-directory' ) . '</a>
                </p>
            </div>';
                break;

            case 'rejected':
                $html .= '
            <div class="email-header" style="background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%);">
                <div class="icon">&#10007;</div>
                <h1>' . __( 'Claim abgelehnt', 'spezialist-directory' ) . '</h1>
            </div>
            <div class="email-body">
                <p class="greeting">' . sprintf( __( 'Hallo %s,', 'spezialist-directory' ), esc_html( $data['user_name'] ) ) . '</p>

                <p>' . __( 'Leider müssen wir dir mitteilen, dass dein Claim nicht genehmigt werden konnte.', 'spezialist-directory' ) . '</p>

                <div class="highlight-box warning">
                    <strong>' . __( 'Angefragter Eintrag:', 'spezialist-directory' ) . '</strong>
                    ' . esc_html( $data['post_title'] ) . '
                </div>';

                if ( ! empty( $data['rejection_reason'] ) ) {
                    $html .= '
                <div class="highlight-box error">
                    <strong>' . __( 'Ablehnungsgrund:', 'spezialist-directory' ) . '</strong>
                    ' . esc_html( $data['rejection_reason'] ) . '
                </div>';
                }

                $html .= '
                <p>' . __( 'Mögliche Gründe für eine Ablehnung können sein:', 'spezialist-directory' ) . '</p>
                <ul>
                    <li>' . __( 'Fehlende oder unzureichende Verifizierungsnachweise', 'spezialist-directory' ) . '</li>
                    <li>' . __( 'Der Eintrag wurde bereits von jemand anderem beansprucht', 'spezialist-directory' ) . '</li>
                    <li>' . __( 'Die angegebenen Informationen konnten nicht überprüft werden', 'spezialist-directory' ) . '</li>
                </ul>

                <p>' . __( 'Wenn du Fragen hast oder weitere Informationen bereitstellen möchtest, kontaktiere uns bitte.', 'spezialist-directory' ) . '</p>

                <p style="text-align: center; margin-top: 32px;">
                    <a href="' . esc_url( $data['contact_url'] ) . '" class="btn">' . __( 'Kontakt aufnehmen', 'spezialist-directory' ) . '</a>
                </p>
            </div>';
                break;

            case 'reverted':
                $html .= '
            <div class="email-header" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);">
                <div class="icon">&#8634;</div>
                <h1>' . __( 'Claim widerrufen', 'spezialist-directory' ) . '</h1>
            </div>
            <div class="email-body">
                <p class="greeting">' . sprintf( __( 'Hallo %s,', 'spezialist-directory' ), esc_html( $data['user_name'] ) ) . '</p>

                <p>' . __( 'Wir möchten dich darüber informieren, dass dein Claim auf folgenden Eintrag widerrufen wurde:', 'spezialist-directory' ) . '</p>

                <div class="highlight-box warning">
                    <strong>' . __( 'Betroffener Eintrag:', 'spezialist-directory' ) . '</strong>
                    ' . esc_html( $data['post_title'] ) . '
                </div>';

                if ( ! empty( $data['reason'] ) ) {
                    $html .= '
                <div class="highlight-box error">
                    <strong>' . __( 'Begründung:', 'spezialist-directory' ) . '</strong>
                    ' . esc_html( $data['reason'] ) . '
                </div>';
                }

                $html .= '
                <p>' . __( 'Dies bedeutet:', 'spezialist-directory' ) . '</p>
                <ul>
                    <li>' . __( 'Du bist nicht mehr als Inhaber dieses Eintrags registriert', 'spezialist-directory' ) . '</li>
                    <li>' . __( 'Du kannst den Eintrag nicht mehr bearbeiten', 'spezialist-directory' ) . '</li>
                    <li>' . __( 'Anfragen zu diesem Eintrag werden nicht mehr an dich weitergeleitet', 'spezialist-directory' ) . '</li>
                </ul>

                <p>' . __( 'Wenn du Fragen hast oder der Meinung bist, dass dies ein Fehler ist, kontaktiere uns bitte.', 'spezialist-directory' ) . '</p>

                <p style="text-align: center; margin-top: 32px;">
                    <a href="' . esc_url( home_url( '/kontakt/' ) ) . '" class="btn">' . __( 'Kontakt aufnehmen', 'spezialist-directory' ) . '</a>
                </p>
            </div>';
                break;
        }

        $html .= '
            <div class="email-footer">
                <p>' . sprintf( __( 'Diese E-Mail wurde automatisch von %s versendet.', 'spezialist-directory' ), esc_html( $site_name ) ) . '</p>
                <p><a href="' . esc_url( home_url() ) . '">' . esc_html( $site_name ) . '</a></p>
            </div>
        </div>
    </div>
</body>
</html>';

        return $html;
    }

    /**
     * Add claim column to post list
     *
     * @param array $columns
     * @return array
     */
    public function add_claim_column( $columns ) {
        $columns['sd_claimed'] = __( 'Beansprucht', 'spezialist-directory' );
        return $columns;
    }

    /**
     * Render claim column
     *
     * @param string $column
     * @param int $post_id
     */
    public function render_claim_column( $column, $post_id ) {
        if ( 'sd_claimed' === $column ) {
            $is_claimed = get_post_meta( $post_id, '_sd_is_claimed', true );
            if ( $is_claimed ) {
                $claimed_by = get_post_meta( $post_id, '_sd_claimed_by', true );
                $user = get_user_by( 'id', $claimed_by );
                echo '<span style="color: #059669;">✓</span> ';
                if ( $user ) {
                    echo esc_html( $user->display_name );
                }
            } else {
                echo '<span style="color: #6b7280;">—</span>';
            }
        }
    }
}
