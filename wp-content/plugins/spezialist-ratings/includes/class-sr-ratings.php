<?php
/**
 * SR_Ratings Class
 *
 * Core ratings functionality and database operations
 *
 * @package Spezialist_Ratings
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * SR_Ratings Class
 */
class SR_Ratings {

    /**
     * Single instance
     *
     * @var SR_Ratings
     */
    protected static $_instance = null;

    /**
     * Table name
     *
     * @var string
     */
    private static $table_name;

    /**
     * Main Instance
     *
     * @return SR_Ratings
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
        global $wpdb;
        self::$table_name = $wpdb->prefix . 'sr_ratings';
    }

    /**
     * Create database tables
     */
    public static function create_tables() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'sr_ratings';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            post_id BIGINT(20) UNSIGNED NOT NULL,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            rating TINYINT(1) UNSIGNED NOT NULL,
            comment TEXT DEFAULT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'approved',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            moderated_by BIGINT(20) UNSIGNED DEFAULT NULL,
            moderated_at DATETIME DEFAULT NULL,
            owner_response TEXT DEFAULT NULL,
            owner_response_at DATETIME DEFAULT NULL,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY user_id (user_id),
            KEY status (status),
            UNIQUE KEY user_post (user_id, post_id)
        ) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
    }

    /**
     * Add owner response columns to existing table (upgrade)
     */
    public static function maybe_upgrade_table() {
        global $wpdb;
        $table = self::get_table_name();

        // Check if columns exist
        $columns = $wpdb->get_col( "SHOW COLUMNS FROM $table" );

        if ( ! in_array( 'owner_response', $columns ) ) {
            $wpdb->query( "ALTER TABLE $table ADD COLUMN owner_response TEXT DEFAULT NULL" );
        }

        if ( ! in_array( 'owner_response_at', $columns ) ) {
            $wpdb->query( "ALTER TABLE $table ADD COLUMN owner_response_at DATETIME DEFAULT NULL" );
        }

        // Add media_id column for photo/video uploads
        if ( ! in_array( 'media_id', $columns ) ) {
            $wpdb->query( "ALTER TABLE $table ADD COLUMN media_id BIGINT(20) UNSIGNED DEFAULT NULL AFTER comment" );
        }
    }

    /**
     * Get table name
     *
     * @return string
     */
    public static function get_table_name() {
        global $wpdb;
        return $wpdb->prefix . 'sr_ratings';
    }

    /**
     * Get average rating for a post
     *
     * @param int $post_id
     * @return float
     */
    public static function get_average( $post_id ) {
        global $wpdb;
        $table = self::get_table_name();

        $average = $wpdb->get_var( $wpdb->prepare(
            "SELECT AVG(rating) FROM $table WHERE post_id = %d AND status = 'approved'",
            $post_id
        ) );

        return $average ? round( floatval( $average ), 1 ) : 0;
    }

    /**
     * Get rating count for a post
     *
     * @param int $post_id
     * @return int
     */
    public static function get_count( $post_id ) {
        global $wpdb;
        $table = self::get_table_name();

        $count = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE post_id = %d AND status = 'approved'",
            $post_id
        ) );

        return intval( $count );
    }

    /**
     * Get all approved ratings for a post
     *
     * @param int $post_id
     * @param int $limit Optional. Number of ratings to return. Default -1 (all).
     * @return array
     */
    public static function get_ratings( $post_id, $limit = -1 ) {
        global $wpdb;
        $table = self::get_table_name();

        $sql = $wpdb->prepare(
            "SELECT * FROM $table WHERE post_id = %d AND status = 'approved' ORDER BY created_at DESC",
            $post_id
        );

        if ( $limit > 0 ) {
            $sql .= $wpdb->prepare( " LIMIT %d", $limit );
        }

        return $wpdb->get_results( $sql );
    }

    /**
     * Get a user's rating for a specific post
     *
     * @param int $post_id
     * @param int $user_id
     * @return object|null
     */
    public static function get_user_rating( $post_id, $user_id ) {
        global $wpdb;
        $table = self::get_table_name();

        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $table WHERE post_id = %d AND user_id = %d",
            $post_id,
            $user_id
        ) );
    }

    /**
     * Check if user can rate a post
     *
     * Rules:
     * - User must be logged in
     * - User cannot be the post author (owner)
     * - User cannot have already rated this post
     *
     * @param int $post_id
     * @param int|null $user_id Optional. Defaults to current user.
     * @return array [ 'can_rate' => bool, 'reason' => string ]
     */
    public static function user_can_rate( $post_id, $user_id = null ) {
        if ( is_null( $user_id ) ) {
            $user_id = get_current_user_id();
        }

        // Not logged in
        if ( ! $user_id ) {
            return array(
                'can_rate' => false,
                'reason'   => 'not_logged_in',
            );
        }

        // Check if user is the post author (owner)
        $post = get_post( $post_id );
        if ( $post && intval( $post->post_author ) === intval( $user_id ) ) {
            return array(
                'can_rate' => false,
                'reason'   => 'is_owner',
            );
        }

        // Check if user already rated this post
        $existing = self::get_user_rating( $post_id, $user_id );
        if ( $existing ) {
            return array(
                'can_rate' => false,
                'reason'   => 'already_rated',
                'rating'   => $existing,
            );
        }

        return array(
            'can_rate' => true,
            'reason'   => '',
        );
    }

    /**
     * Submit a new rating
     *
     * @param int    $post_id
     * @param int    $user_id
     * @param int    $rating   1-5 stars
     * @param string $comment  Optional comment
     * @param int    $media_id Optional attachment ID for photo/video
     * @return array [ 'success' => bool, 'message' => string, 'data' => array ]
     */
    public static function submit( $post_id, $user_id, $rating, $comment = '', $media_id = 0 ) {
        global $wpdb;
        $table = self::get_table_name();

        // Validate rating
        $rating = intval( $rating );
        if ( $rating < 1 || $rating > 5 ) {
            return array(
                'success' => false,
                'message' => __( 'Ungültige Bewertung. Bitte wählen Sie 1-5 Sterne.', 'spezialist-ratings' ),
            );
        }

        // Check if user can rate
        $can_rate = self::user_can_rate( $post_id, $user_id );
        if ( ! $can_rate['can_rate'] ) {
            $messages = array(
                'not_logged_in' => __( 'Sie müssen angemeldet sein, um eine Bewertung abzugeben.', 'spezialist-ratings' ),
                'is_owner'      => __( 'Sie können Ihren eigenen Eintrag nicht bewerten.', 'spezialist-ratings' ),
                'already_rated' => __( 'Sie haben diesen Eintrag bereits bewertet.', 'spezialist-ratings' ),
            );
            return array(
                'success' => false,
                'message' => $messages[ $can_rate['reason'] ] ?? __( 'Bewertung nicht möglich.', 'spezialist-ratings' ),
            );
        }

        // Sanitize comment
        $comment = sanitize_textarea_field( $comment );

        // Sanitize media_id
        $media_id = intval( $media_id );

        // Determine status: auto-approve if no comment and no media, pending if has comment or media
        $status = ( empty( $comment ) && empty( $media_id ) ) ? 'approved' : 'pending';

        // Insert rating
        $result = $wpdb->insert(
            $table,
            array(
                'post_id'    => $post_id,
                'user_id'    => $user_id,
                'rating'     => $rating,
                'comment'    => $comment,
                'media_id'   => $media_id ? $media_id : null,
                'status'     => $status,
                'created_at' => current_time( 'mysql' ),
            ),
            array( '%d', '%d', '%d', '%s', '%d', '%s', '%s' )
        );

        if ( ! $result ) {
            return array(
                'success' => false,
                'message' => __( 'Fehler beim Speichern der Bewertung.', 'spezialist-ratings' ),
            );
        }

        // Send notification to admin if pending
        if ( $status === 'pending' ) {
            self::send_admin_notification( $post_id, $user_id, $rating, $comment, $media_id );
        }

        // Get updated stats
        $new_average = self::get_average( $post_id );
        $new_count = self::get_count( $post_id );

        return array(
            'success'         => true,
            'message'         => $status === 'approved'
                ? __( 'Vielen Dank für Ihre Bewertung!', 'spezialist-ratings' )
                : __( 'Vielen Dank! Ihre Bewertung wird nach Prüfung veröffentlicht.', 'spezialist-ratings' ),
            'status'          => $status,
            'needs_approval'  => $status === 'pending',
            'new_average'     => $new_average,
            'new_count'       => $new_count,
        );
    }

    /**
     * Send admin notification for pending review
     *
     * @param int    $post_id
     * @param int    $user_id
     * @param int    $rating
     * @param string $comment
     * @param int    $media_id Optional attachment ID
     */
    private static function send_admin_notification( $post_id, $user_id, $rating, $comment, $media_id = 0 ) {
        // Check if notification is enabled
        if ( class_exists( 'SD_Email_Templates' ) && ! SD_Email_Templates::is_enabled( 'sd_notify_admin_new_rating' ) ) {
            return;
        }

        $post = get_post( $post_id );
        $user = get_user_by( 'id', $user_id );
        $admin_email = class_exists( 'SD_Email_Templates' )
            ? SD_Email_Templates::get_admin_email()
            : get_option( 'admin_email' );

        if ( ! $post || ! $user ) {
            return;
        }

        $subject = sprintf(
            __( '[%s] Neue Bewertung wartet auf Freigabe', 'spezialist-ratings' ),
            get_bloginfo( 'name' )
        );

        $moderation_url = admin_url( 'edit.php?post_type=hofladen&page=sr-ratings&status=pending' );

        // Use HTML template if available
        if ( class_exists( 'SD_Email_Templates' ) ) {
            $html_message = SD_Email_Templates::template_admin_new_rating(
                $post->post_title,
                $user->display_name,
                $rating,
                $comment ?: '',
                $moderation_url
            );
            $headers = array( 'Content-Type: text/html; charset=UTF-8' );
            wp_mail( $admin_email, $subject, $html_message, $headers );
        } else {
            // Fallback to plain text
            $media_info = '';
            if ( $media_id ) {
                $media_url = wp_get_attachment_url( $media_id );
                $media_type = wp_attachment_is( 'video', $media_id ) ? 'Video' : 'Foto';
                $media_info = sprintf( "\n%s: %s", $media_type, $media_url );
            }

            $message = sprintf(
                __( "Neue Bewertung für: %s\n\nVon: %s (%s)\nBewertung: %d Sterne\n\nKommentar:\n%s%s\n\nZur Moderation:\n%s", 'spezialist-ratings' ),
                $post->post_title,
                $user->display_name,
                $user->user_email,
                $rating,
                $comment ?: '(kein Kommentar)',
                $media_info,
                $moderation_url
            );

            wp_mail( $admin_email, $subject, $message );
        }
    }

    /**
     * Update rating status (for admin moderation)
     *
     * @param int    $rating_id
     * @param string $status    'approved' or 'rejected'
     * @param int    $admin_id  Admin user ID
     * @return bool
     */
    public static function update_status( $rating_id, $status, $admin_id ) {
        global $wpdb;
        $table = self::get_table_name();

        if ( ! in_array( $status, array( 'approved', 'rejected' ), true ) ) {
            return false;
        }

        // Get rating data before update for notification
        $rating = self::get_by_id( $rating_id );

        $result = $wpdb->update(
            $table,
            array(
                'status'       => $status,
                'moderated_by' => $admin_id,
                'moderated_at' => current_time( 'mysql' ),
            ),
            array( 'id' => $rating_id ),
            array( '%s', '%d', '%s' ),
            array( '%d' )
        );

        // Send user notification
        if ( $result !== false && $rating ) {
            if ( $status === 'approved' ) {
                self::send_rating_approved_email( $rating );
            } elseif ( $status === 'rejected' ) {
                self::send_rating_rejected_email( $rating );
            }
        }

        return $result !== false;
    }

    /**
     * Send rating approved email to user
     *
     * @param object $rating Rating object
     */
    private static function send_rating_approved_email( $rating ) {
        // Check if notification is enabled and class exists
        if ( ! class_exists( 'SD_Email_Templates' ) || ! SD_Email_Templates::is_enabled( 'sd_notify_user_rating_approved' ) ) {
            return;
        }

        $user = get_user_by( 'id', $rating->user_id );
        $post = get_post( $rating->post_id );

        if ( ! $user || ! $post ) {
            return;
        }

        $subject = sprintf(
            __( '[%s] Deine Bewertung wurde veröffentlicht!', 'spezialist-ratings' ),
            get_bloginfo( 'name' )
        );

        $listing_url = get_permalink( $post->ID );
        $html_message = SD_Email_Templates::template_rating_approved(
            $user->display_name,
            $post->post_title,
            $rating->rating,
            $listing_url
        );

        $headers = array( 'Content-Type: text/html; charset=UTF-8' );
        wp_mail( $user->user_email, $subject, $html_message, $headers );
    }

    /**
     * Send rating rejected email to user
     *
     * @param object $rating Rating object
     */
    private static function send_rating_rejected_email( $rating ) {
        // Check if notification is enabled and class exists
        if ( ! class_exists( 'SD_Email_Templates' ) || ! SD_Email_Templates::is_enabled( 'sd_notify_user_rating_rejected' ) ) {
            return;
        }

        $user = get_user_by( 'id', $rating->user_id );
        $post = get_post( $rating->post_id );

        if ( ! $user || ! $post ) {
            return;
        }

        $subject = sprintf(
            __( '[%s] Deine Bewertung konnte nicht veröffentlicht werden', 'spezialist-ratings' ),
            get_bloginfo( 'name' )
        );

        $contact_url = home_url( '/kontakt/' );
        $html_message = SD_Email_Templates::template_rating_rejected(
            $user->display_name,
            $post->post_title,
            $contact_url
        );

        $headers = array( 'Content-Type: text/html; charset=UTF-8' );
        wp_mail( $user->user_email, $subject, $html_message, $headers );
    }

    /**
     * Delete a rating
     *
     * @param int $rating_id
     * @return bool
     */
    public static function delete( $rating_id ) {
        global $wpdb;
        $table = self::get_table_name();

        return $wpdb->delete( $table, array( 'id' => $rating_id ), array( '%d' ) ) !== false;
    }

    /**
     * Get all ratings (for admin)
     *
     * @param string $status Optional. Filter by status.
     * @param int    $limit  Optional. Limit results.
     * @return array
     */
    public static function get_all_ratings( $status = '', $limit = 100 ) {
        global $wpdb;
        $table = self::get_table_name();

        $sql = "SELECT * FROM $table";

        if ( $status ) {
            $sql .= $wpdb->prepare( " WHERE status = %s", $status );
        }

        $sql .= " ORDER BY created_at DESC";

        if ( $limit > 0 ) {
            $sql .= $wpdb->prepare( " LIMIT %d", $limit );
        }

        return $wpdb->get_results( $sql );
    }

    /**
     * Get rating counts by status
     *
     * @return array
     */
    public static function get_counts_by_status() {
        global $wpdb;
        $table = self::get_table_name();

        $results = $wpdb->get_results(
            "SELECT status, COUNT(*) as count FROM $table GROUP BY status"
        );

        $counts = array(
            'pending'  => 0,
            'approved' => 0,
            'rejected' => 0,
            'total'    => 0,
        );

        foreach ( $results as $row ) {
            $counts[ $row->status ] = intval( $row->count );
            $counts['total'] += intval( $row->count );
        }

        return $counts;
    }

    /**
     * Get post IDs that have a minimum average rating
     *
     * @param float $min_rating Minimum rating (e.g., 3.0, 4.0)
     * @return array Array of post IDs meeting the criteria
     */
    public static function get_posts_with_min_rating( $min_rating ) {
        global $wpdb;
        $table = self::get_table_name();

        $min_rating = floatval( $min_rating );
        if ( $min_rating < 1 || $min_rating > 5 ) {
            return array();
        }

        $results = $wpdb->get_col( $wpdb->prepare(
            "SELECT post_id FROM $table
             WHERE status = 'approved'
             GROUP BY post_id
             HAVING AVG(rating) >= %f",
            $min_rating
        ) );

        return array_map( 'intval', $results );
    }

    /**
     * Get all posts with their average ratings
     *
     * @return array Array of objects with post_id, avg_rating, rating_count
     */
    public static function get_all_post_ratings() {
        global $wpdb;
        $table = self::get_table_name();

        return $wpdb->get_results(
            "SELECT post_id, AVG(rating) as avg_rating, COUNT(*) as rating_count
             FROM $table
             WHERE status = 'approved'
             GROUP BY post_id"
        );
    }

    /**
     * Get recent reviews across all listings
     *
     * @param int $limit Number of reviews to return
     * @return array Array of review objects with user and post data
     */
    public static function get_recent_reviews( $limit = 10 ) {
        global $wpdb;
        $table = self::get_table_name();

        $results = $wpdb->get_results( $wpdb->prepare(
            "SELECT r.*,
                    p.post_title,
                    u.display_name as user_name
             FROM $table r
             LEFT JOIN {$wpdb->posts} p ON r.post_id = p.ID
             LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
             WHERE r.status = 'approved'
               AND r.comment IS NOT NULL
               AND r.comment != ''
               AND p.post_status = 'publish'
             ORDER BY r.created_at DESC
             LIMIT %d",
            $limit
        ) );

        return $results ? $results : array();
    }

    /**
     * Render stars HTML
     *
     * @param float $rating Rating value (0-5)
     * @param int   $size   SVG size in pixels
     * @return string
     */
    public static function render_stars( $rating, $size = 16 ) {
        $rating = floatval( $rating );
        $full_stars = floor( $rating );
        $has_half = ( $rating - $full_stars ) >= 0.5;
        $empty_stars = 5 - $full_stars - ( $has_half ? 1 : 0 );

        $html = '<div class="sr-stars">';

        // Full stars
        for ( $i = 0; $i < $full_stars; $i++ ) {
            $html .= '<svg class="sr-star sr-star-full" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="#F59E0B"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>';
        }

        // Half star
        if ( $has_half ) {
            $html .= '<svg class="sr-star sr-star-half" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24"><defs><linearGradient id="sr-half-grad"><stop offset="50%" stop-color="#F59E0B"/><stop offset="50%" stop-color="#D1D5DB"/></linearGradient></defs><path fill="url(#sr-half-grad)" d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>';
        }

        // Empty stars
        for ( $i = 0; $i < $empty_stars; $i++ ) {
            $html .= '<svg class="sr-star sr-star-empty" width="' . esc_attr( $size ) . '" height="' . esc_attr( $size ) . '" viewBox="0 0 24 24" fill="#D1D5DB"><path d="M12 17.27L18.18 21l-1.64-7.03L22 9.24l-7.19-.61L12 2 9.19 8.63 2 9.24l5.46 4.73L5.82 21z"/></svg>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Check if user can respond to a review
     *
     * @param int $rating_id
     * @param int|null $user_id Optional. Defaults to current user.
     * @return array [ 'can_respond' => bool, 'reason' => string ]
     */
    public static function user_can_respond( $rating_id, $user_id = null ) {
        global $wpdb;

        if ( is_null( $user_id ) ) {
            $user_id = get_current_user_id();
        }

        if ( ! $user_id ) {
            return array(
                'can_respond' => false,
                'reason'      => 'not_logged_in',
            );
        }

        $table = self::get_table_name();
        $rating = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $rating_id
        ) );

        if ( ! $rating ) {
            return array(
                'can_respond' => false,
                'reason'      => 'rating_not_found',
            );
        }

        // Check if user owns the listing
        $post = get_post( $rating->post_id );
        if ( ! $post ) {
            return array(
                'can_respond' => false,
                'reason'      => 'post_not_found',
            );
        }

        // Check if user is the post author OR has claimed the listing
        $is_owner = intval( $post->post_author ) === intval( $user_id );
        $claimed_by = get_post_meta( $rating->post_id, '_sd_claimed_by', true );
        $is_claimant = ! empty( $claimed_by ) && intval( $claimed_by ) === intval( $user_id );

        if ( ! $is_owner && ! $is_claimant ) {
            return array(
                'can_respond' => false,
                'reason'      => 'not_owner',
            );
        }

        // Check if already responded
        if ( ! empty( $rating->owner_response ) ) {
            return array(
                'can_respond' => false,
                'reason'      => 'already_responded',
            );
        }

        return array(
            'can_respond' => true,
            'reason'      => '',
            'rating'      => $rating,
        );
    }

    /**
     * Submit owner response to a review
     *
     * @param int    $rating_id
     * @param int    $user_id
     * @param string $response
     * @return array [ 'success' => bool, 'message' => string ]
     */
    public static function submit_response( $rating_id, $user_id, $response ) {
        global $wpdb;

        // Check permissions
        $can_respond = self::user_can_respond( $rating_id, $user_id );
        if ( ! $can_respond['can_respond'] ) {
            $messages = array(
                'not_logged_in'     => __( 'Sie müssen angemeldet sein.', 'spezialist-ratings' ),
                'rating_not_found'  => __( 'Bewertung nicht gefunden.', 'spezialist-ratings' ),
                'post_not_found'    => __( 'Eintrag nicht gefunden.', 'spezialist-ratings' ),
                'not_owner'         => __( 'Sie können nur auf Bewertungen Ihrer eigenen Einträge antworten.', 'spezialist-ratings' ),
                'already_responded' => __( 'Sie haben auf diese Bewertung bereits geantwortet.', 'spezialist-ratings' ),
            );
            return array(
                'success' => false,
                'message' => $messages[ $can_respond['reason'] ] ?? __( 'Antwort nicht möglich.', 'spezialist-ratings' ),
            );
        }

        // Sanitize response
        $response = sanitize_textarea_field( $response );
        if ( empty( $response ) ) {
            return array(
                'success' => false,
                'message' => __( 'Bitte geben Sie eine Antwort ein.', 'spezialist-ratings' ),
            );
        }

        // Save response
        $table = self::get_table_name();
        $result = $wpdb->update(
            $table,
            array(
                'owner_response'    => $response,
                'owner_response_at' => current_time( 'mysql' ),
            ),
            array( 'id' => $rating_id ),
            array( '%s', '%s' ),
            array( '%d' )
        );

        if ( $result === false ) {
            return array(
                'success' => false,
                'message' => __( 'Fehler beim Speichern der Antwort.', 'spezialist-ratings' ),
            );
        }

        return array(
            'success' => true,
            'message' => __( 'Ihre Antwort wurde gespeichert.', 'spezialist-ratings' ),
        );
    }

    /**
     * Get reviews for a specific listing that current user owns
     * (for dashboard review management)
     *
     * @param int $post_id
     * @param int $user_id
     * @return array
     */
    public static function get_owner_reviews( $post_id, $user_id ) {
        global $wpdb;

        // Verify ownership
        $post = get_post( $post_id );
        if ( ! $post ) {
            return array();
        }

        $is_owner = intval( $post->post_author ) === intval( $user_id );
        $claimed_by = get_post_meta( $post_id, '_sd_claimed_by', true );
        $is_claimant = ! empty( $claimed_by ) && intval( $claimed_by ) === intval( $user_id );

        if ( ! $is_owner && ! $is_claimant ) {
            return array();
        }

        $table = self::get_table_name();
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT r.*, u.display_name as user_name
             FROM $table r
             LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
             WHERE r.post_id = %d AND r.status = 'approved'
             ORDER BY r.created_at DESC",
            $post_id
        ) );
    }

    /**
     * Get all reviews for listings owned by a user
     *
     * @param int $user_id
     * @return array
     */
    public static function get_all_owner_reviews( $user_id ) {
        global $wpdb;
        $table = self::get_table_name();

        // Get all posts owned by user or claimed by user
        $owned_posts = $wpdb->get_col( $wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_author = %d AND post_type = 'hofladen' AND post_status = 'publish'",
            $user_id
        ) );

        $claimed_posts = $wpdb->get_col( $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_sd_claimed_by' AND meta_value = %d",
            $user_id
        ) );

        $all_posts = array_unique( array_merge( $owned_posts, $claimed_posts ) );

        if ( empty( $all_posts ) ) {
            return array();
        }

        $placeholders = implode( ',', array_fill( 0, count( $all_posts ), '%d' ) );

        return $wpdb->get_results( $wpdb->prepare(
            "SELECT r.*, u.display_name as user_name, p.post_title
             FROM $table r
             LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
             LEFT JOIN {$wpdb->posts} p ON r.post_id = p.ID
             WHERE r.post_id IN ($placeholders) AND r.status = 'approved'
             ORDER BY r.created_at DESC",
            ...$all_posts
        ) );
    }
}
