<?php
/**
 * SR_Admin Class
 *
 * Admin moderation interface for ratings
 *
 * @package Spezialist_Ratings
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * SR_Admin Class
 */
class SR_Admin {

    /**
     * Single instance
     *
     * @var SR_Admin
     */
    protected static $_instance = null;

    /**
     * Main Instance
     *
     * @return SR_Admin
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
        add_action( 'wp_ajax_sr_moderate_rating', array( $this, 'handle_moderation' ) );
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        $counts = SR_Ratings::get_counts_by_status();
        $pending_count = $counts['pending'];

        $menu_title = __( 'Bewertungen', 'spezialist-ratings' );
        if ( $pending_count > 0 ) {
            $menu_title .= ' <span class="awaiting-mod">' . $pending_count . '</span>';
        }

        add_submenu_page(
            'edit.php?post_type=spezialist',
            __( 'Bewertungen verwalten', 'spezialist-ratings' ),
            $menu_title,
            'manage_options',
            'sr-ratings',
            array( $this, 'render_admin_page' )
        );
    }

    /**
     * Handle moderation AJAX request
     */
    public function handle_moderation() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'sr_moderate_rating' ) ) {
            wp_send_json_error( array( 'message' => __( 'Sicherheitsprüfung fehlgeschlagen.', 'spezialist-ratings' ) ) );
        }

        // Check permissions
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Keine Berechtigung.', 'spezialist-ratings' ) ) );
        }

        $rating_id = isset( $_POST['rating_id'] ) ? intval( $_POST['rating_id'] ) : 0;
        $action = isset( $_POST['mod_action'] ) ? sanitize_text_field( $_POST['mod_action'] ) : '';

        if ( ! $rating_id || ! in_array( $action, array( 'approve', 'reject', 'delete' ), true ) ) {
            wp_send_json_error( array( 'message' => __( 'Ungültige Anfrage.', 'spezialist-ratings' ) ) );
        }

        $admin_id = get_current_user_id();

        if ( $action === 'delete' ) {
            $result = SR_Ratings::delete( $rating_id );
        } else {
            $status = $action === 'approve' ? 'approved' : 'rejected';
            $result = SR_Ratings::update_status( $rating_id, $status, $admin_id );
        }

        if ( $result ) {
            $counts = SR_Ratings::get_counts_by_status();
            wp_send_json_success( array(
                'message' => __( 'Bewertung wurde aktualisiert.', 'spezialist-ratings' ),
                'counts'  => $counts,
            ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Fehler beim Aktualisieren.', 'spezialist-ratings' ) ) );
        }
    }

    /**
     * Render admin page
     */
    public function render_admin_page() {
        // Handle legacy GET-based actions
        if ( isset( $_GET['action'] ) && isset( $_GET['rating_id'] ) && isset( $_GET['_wpnonce'] ) ) {
            if ( wp_verify_nonce( $_GET['_wpnonce'], 'sr_mod_action' ) ) {
                $rating_id = intval( $_GET['rating_id'] );
                $action = sanitize_text_field( $_GET['action'] );
                $admin_id = get_current_user_id();

                if ( $action === 'delete' ) {
                    SR_Ratings::delete( $rating_id );
                } elseif ( in_array( $action, array( 'approve', 'reject' ), true ) ) {
                    $status = $action === 'approve' ? 'approved' : 'rejected';
                    SR_Ratings::update_status( $rating_id, $status, $admin_id );
                }
            }
        }

        // Get counts
        $counts = SR_Ratings::get_counts_by_status();

        // Get current filter
        $status_filter = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';

        // Get ratings
        $ratings = SR_Ratings::get_all_ratings( $status_filter );

        ?>
        <div class="wrap sr-admin-page">
            <h1 class="wp-heading-inline">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" style="vertical-align: middle; margin-right: 8px;">
                    <path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z" fill="#F59E0B"/>
                </svg>
                <?php _e( 'Bewertungen verwalten', 'spezialist-ratings' ); ?>
            </h1>

            <!-- Statistics Cards -->
            <div class="sr-admin-stats">
                <div class="sr-stat-card sr-stat-pending">
                    <div class="sr-stat-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z" fill="#f59e0b"/></svg>
                    </div>
                    <div class="sr-stat-content">
                        <span class="sr-stat-number"><?php echo esc_html( $counts['pending'] ); ?></span>
                        <span class="sr-stat-label"><?php _e( 'Ausstehend', 'spezialist-ratings' ); ?></span>
                    </div>
                </div>
                <div class="sr-stat-card sr-stat-approved">
                    <div class="sr-stat-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" fill="#059669"/></svg>
                    </div>
                    <div class="sr-stat-content">
                        <span class="sr-stat-number"><?php echo esc_html( $counts['approved'] ); ?></span>
                        <span class="sr-stat-label"><?php _e( 'Genehmigt', 'spezialist-ratings' ); ?></span>
                    </div>
                </div>
                <div class="sr-stat-card sr-stat-rejected">
                    <div class="sr-stat-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M12 2C6.47 2 2 6.47 2 12s4.47 10 10 10 10-4.47 10-10S17.53 2 12 2zm5 13.59L15.59 17 12 13.41 8.41 17 7 15.59 10.59 12 7 8.41 8.41 7 12 10.59 15.59 7 17 8.41 13.41 12 17 15.59z" fill="#dc2626"/></svg>
                    </div>
                    <div class="sr-stat-content">
                        <span class="sr-stat-number"><?php echo esc_html( $counts['rejected'] ); ?></span>
                        <span class="sr-stat-label"><?php _e( 'Abgelehnt', 'spezialist-ratings' ); ?></span>
                    </div>
                </div>
                <div class="sr-stat-card sr-stat-total">
                    <div class="sr-stat-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z" fill="#6366f1"/></svg>
                    </div>
                    <div class="sr-stat-content">
                        <span class="sr-stat-number"><?php echo esc_html( $counts['total'] ); ?></span>
                        <span class="sr-stat-label"><?php _e( 'Gesamt', 'spezialist-ratings' ); ?></span>
                    </div>
                </div>
            </div>

            <!-- Filter Buttons -->
            <div class="sr-admin-filters">
                <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=spezialist&page=sr-ratings' ) ); ?>"
                   class="button <?php echo ! $status_filter ? 'button-primary' : ''; ?>">
                    <?php _e( 'Alle', 'spezialist-ratings' ); ?>
                </a>
                <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=spezialist&page=sr-ratings&status=pending' ) ); ?>"
                   class="button <?php echo $status_filter === 'pending' ? 'button-primary' : ''; ?>">
                    <?php _e( 'Ausstehend', 'spezialist-ratings' ); ?> (<?php echo esc_html( $counts['pending'] ); ?>)
                </a>
                <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=spezialist&page=sr-ratings&status=approved' ) ); ?>"
                   class="button <?php echo $status_filter === 'approved' ? 'button-primary' : ''; ?>">
                    <?php _e( 'Genehmigt', 'spezialist-ratings' ); ?>
                </a>
                <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=spezialist&page=sr-ratings&status=rejected' ) ); ?>"
                   class="button <?php echo $status_filter === 'rejected' ? 'button-primary' : ''; ?>">
                    <?php _e( 'Abgelehnt', 'spezialist-ratings' ); ?>
                </a>
            </div>

            <!-- Ratings Table -->
            <table class="wp-list-table widefat fixed striped sr-ratings-table">
                <thead>
                    <tr>
                        <th style="width: 20%;"><?php _e( 'Eintrag', 'spezialist-ratings' ); ?></th>
                        <th style="width: 15%;"><?php _e( 'Nutzer', 'spezialist-ratings' ); ?></th>
                        <th style="width: 10%;"><?php _e( 'Bewertung', 'spezialist-ratings' ); ?></th>
                        <th style="width: 25%;"><?php _e( 'Kommentar', 'spezialist-ratings' ); ?></th>
                        <th style="width: 10%;"><?php _e( 'Status', 'spezialist-ratings' ); ?></th>
                        <th style="width: 10%;"><?php _e( 'Datum', 'spezialist-ratings' ); ?></th>
                        <th style="width: 10%;"><?php _e( 'Aktionen', 'spezialist-ratings' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $ratings ) ) : ?>
                        <tr>
                            <td colspan="7" class="sr-empty-state">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z" fill="#E5E7EB"/></svg>
                                <p><?php _e( 'Keine Bewertungen gefunden.', 'spezialist-ratings' ); ?></p>
                            </td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ( $ratings as $rating ) :
                            $post = get_post( $rating->post_id );
                            $user = get_user_by( 'id', $rating->user_id );
                        ?>
                            <tr class="sr-rating-row sr-status-<?php echo esc_attr( $rating->status ); ?>" data-rating-id="<?php echo esc_attr( $rating->id ); ?>">
                                <td>
                                    <?php if ( $post ) : ?>
                                        <strong>
                                            <a href="<?php echo esc_url( get_permalink( $rating->post_id ) ); ?>" target="_blank">
                                                <?php echo esc_html( $post->post_title ); ?>
                                            </a>
                                        </strong>
                                        <div class="row-actions">
                                            <span><a href="<?php echo esc_url( get_edit_post_link( $rating->post_id ) ); ?>"><?php _e( 'Bearbeiten', 'spezialist-ratings' ); ?></a> | </span>
                                            <span><a href="<?php echo esc_url( get_permalink( $rating->post_id ) . '?tab=bewertungen' ); ?>" target="_blank"><?php _e( 'Bewertungen ansehen', 'spezialist-ratings' ); ?></a></span>
                                        </div>
                                    <?php else : ?>
                                        <span class="sr-deleted"><?php _e( 'Eintrag gelöscht', 'spezialist-ratings' ); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ( $user ) : ?>
                                        <div class="sr-user-info">
                                            <?php echo get_avatar( $user->ID, 32 ); ?>
                                            <div>
                                                <strong><?php echo esc_html( $user->display_name ); ?></strong>
                                                <br><small><a href="mailto:<?php echo esc_attr( $user->user_email ); ?>"><?php echo esc_html( $user->user_email ); ?></a></small>
                                            </div>
                                        </div>
                                    <?php else : ?>
                                        <span class="sr-deleted"><?php _e( 'Nutzer gelöscht', 'spezialist-ratings' ); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="sr-rating-stars">
                                        <?php echo SR_Ratings::render_stars( $rating->rating, 14 ); ?>
                                        <span class="sr-rating-number"><?php echo esc_html( $rating->rating ); ?>/5</span>
                                    </div>
                                </td>
                                <td>
                                    <?php if ( $rating->comment ) : ?>
                                        <div class="sr-comment-preview" title="<?php echo esc_attr( $rating->comment ); ?>">
                                            <?php echo esc_html( wp_trim_words( $rating->comment, 15 ) ); ?>
                                        </div>
                                    <?php else : ?>
                                        <span class="sr-no-comment"><?php _e( 'Kein Kommentar', 'spezialist-ratings' ); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $status_labels = array(
                                        'pending'  => '<span class="sr-status sr-status-pending">' . __( 'Ausstehend', 'spezialist-ratings' ) . '</span>',
                                        'approved' => '<span class="sr-status sr-status-approved">' . __( 'Genehmigt', 'spezialist-ratings' ) . '</span>',
                                        'rejected' => '<span class="sr-status sr-status-rejected">' . __( 'Abgelehnt', 'spezialist-ratings' ) . '</span>',
                                    );
                                    echo $status_labels[ $rating->status ] ?? esc_html( $rating->status );
                                    ?>
                                </td>
                                <td>
                                    <span title="<?php echo esc_attr( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $rating->created_at ) ) ); ?>">
                                        <?php echo esc_html( human_time_diff( strtotime( $rating->created_at ), current_time( 'timestamp' ) ) ); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="sr-action-buttons">
                                        <?php if ( $rating->status === 'pending' ) : ?>
                                            <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'edit.php?post_type=spezialist&page=sr-ratings&action=approve&rating_id=' . $rating->id ), 'sr_mod_action' ) ); ?>"
                                               class="button button-small button-primary"
                                               title="<?php esc_attr_e( 'Genehmigen', 'spezialist-ratings' ); ?>">
                                                <span class="dashicons dashicons-yes"></span>
                                            </a>
                                            <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'edit.php?post_type=spezialist&page=sr-ratings&action=reject&rating_id=' . $rating->id ), 'sr_mod_action' ) ); ?>"
                                               class="button button-small"
                                               title="<?php esc_attr_e( 'Ablehnen', 'spezialist-ratings' ); ?>">
                                                <span class="dashicons dashicons-no-alt"></span>
                                            </a>
                                        <?php elseif ( $rating->status === 'rejected' ) : ?>
                                            <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'edit.php?post_type=spezialist&page=sr-ratings&action=approve&rating_id=' . $rating->id ), 'sr_mod_action' ) ); ?>"
                                               class="button button-small"
                                               title="<?php esc_attr_e( 'Doch genehmigen', 'spezialist-ratings' ); ?>">
                                                <span class="dashicons dashicons-yes"></span>
                                            </a>
                                        <?php endif; ?>
                                        <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'edit.php?post_type=spezialist&page=sr-ratings&action=delete&rating_id=' . $rating->id ), 'sr_mod_action' ) ); ?>"
                                           class="button button-small sr-delete-btn"
                                           title="<?php esc_attr_e( 'Löschen', 'spezialist-ratings' ); ?>"
                                           onclick="return confirm('<?php esc_attr_e( 'Bewertung wirklich löschen?', 'spezialist-ratings' ); ?>');">
                                            <span class="dashicons dashicons-trash"></span>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <style>
            /* Admin Page Styles */
            .sr-admin-page { max-width: 1400px; }

            /* Stats Cards */
            .sr-admin-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin: 20px 0; }
            .sr-stat-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; display: flex; align-items: center; gap: 12px; }
            .sr-stat-icon { width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; }
            .sr-stat-pending .sr-stat-icon { background: #fef3c7; }
            .sr-stat-approved .sr-stat-icon { background: #d1fae5; }
            .sr-stat-rejected .sr-stat-icon { background: #fee2e2; }
            .sr-stat-total .sr-stat-icon { background: #e0e7ff; }
            .sr-stat-number { display: block; font-size: 24px; font-weight: 700; color: #1f2937; }
            .sr-stat-label { font-size: 12px; color: #6b7280; }

            /* Filters */
            .sr-admin-filters { margin-bottom: 16px; display: flex; gap: 8px; }

            /* Table Styles */
            .sr-ratings-table td { vertical-align: middle; }
            .sr-user-info { display: flex; align-items: center; gap: 8px; }
            .sr-user-info img { border-radius: 50%; }
            .sr-rating-stars { display: flex; align-items: center; gap: 4px; }
            .sr-rating-number { font-weight: 600; color: #1f2937; }
            .sr-comment-preview { max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
            .sr-no-comment { color: #9ca3af; font-style: italic; }
            .sr-deleted { color: #9ca3af; font-style: italic; }

            /* Status Badges */
            .sr-status { display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 12px; font-weight: 500; }
            .sr-status-pending { background: #fef3c7; color: #92400e; }
            .sr-status-approved { background: #d1fae5; color: #065f46; }
            .sr-status-rejected { background: #fee2e2; color: #991b1b; }

            /* Action Buttons */
            .sr-action-buttons { display: flex; gap: 4px; }
            .sr-action-buttons .button { padding: 0 6px; min-width: 30px; }
            .sr-action-buttons .dashicons { font-size: 16px; width: 16px; height: 16px; vertical-align: middle; }
            .sr-delete-btn { color: #dc2626 !important; }
            .sr-delete-btn:hover { background: #fee2e2 !important; }

            /* Empty State */
            .sr-empty-state { text-align: center; padding: 40px !important; color: #6b7280; }
            .sr-empty-state svg { margin-bottom: 12px; }

            /* Row Highlights */
            .sr-status-pending { background-color: #fffbeb !important; }
        </style>
        <?php
    }
}
