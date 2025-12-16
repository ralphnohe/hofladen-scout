<?php
/**
 * SR_Ajax Class
 *
 * Handles AJAX requests for rating submission
 *
 * @package Spezialist_Ratings
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * SR_Ajax Class
 */
class SR_Ajax {

    /**
     * Single instance
     *
     * @var SR_Ajax
     */
    protected static $_instance = null;

    /**
     * Main Instance
     *
     * @return SR_Ajax
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
        // Rating submission (logged in users only)
        add_action( 'wp_ajax_sr_submit_rating', array( $this, 'handle_submit_rating' ) );

        // Guest redirect handler (for non-logged-in users)
        add_action( 'wp_ajax_nopriv_sr_submit_rating', array( $this, 'handle_guest_submit' ) );

        // Owner response submission (logged in users only)
        add_action( 'wp_ajax_sr_submit_response', array( $this, 'handle_submit_response' ) );

        // Get reviews for owner dashboard
        add_action( 'wp_ajax_sr_get_owner_reviews', array( $this, 'handle_get_owner_reviews' ) );
    }

    /**
     * Handle rating submission
     */
    public function handle_submit_rating() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'sr_submit_rating' ) ) {
            wp_send_json_error( array(
                'message' => __( 'Sicherheitsprüfung fehlgeschlagen.', 'spezialist-ratings' ),
            ) );
        }

        // Get and validate post_id
        $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
        if ( ! $post_id || 'spezialist' !== get_post_type( $post_id ) ) {
            wp_send_json_error( array(
                'message' => __( 'Ungültiger Eintrag.', 'spezialist-ratings' ),
            ) );
        }

        // Get rating
        $rating = isset( $_POST['rating'] ) ? intval( $_POST['rating'] ) : 0;
        if ( $rating < 1 || $rating > 5 ) {
            wp_send_json_error( array(
                'message' => __( 'Bitte wählen Sie eine Bewertung (1-5 Sterne).', 'spezialist-ratings' ),
            ) );
        }

        // Get optional comment
        $comment = isset( $_POST['comment'] ) ? sanitize_textarea_field( $_POST['comment'] ) : '';

        // Get current user
        $user_id = get_current_user_id();

        // Handle media upload (photo or video)
        $media_id = 0;
        if ( ! empty( $_FILES['media'] ) && $_FILES['media']['error'] === UPLOAD_ERR_OK ) {
            require_once( ABSPATH . 'wp-admin/includes/image.php' );
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
            require_once( ABSPATH . 'wp-admin/includes/media.php' );

            // Validate file type and size (max 10 MB)
            $allowed_types = array( 'image/jpeg', 'image/png', 'image/webp', 'video/mp4', 'video/quicktime' );
            $file_type = $_FILES['media']['type'];
            $file_size = $_FILES['media']['size'];
            $max_size = 10 * 1024 * 1024; // 10 MB

            if ( in_array( $file_type, $allowed_types, true ) && $file_size <= $max_size ) {
                $attachment_id = media_handle_upload( 'media', $post_id );
                if ( ! is_wp_error( $attachment_id ) ) {
                    $media_id = $attachment_id;
                }
            }
        }

        // Submit rating
        $result = SR_Ratings::submit( $post_id, $user_id, $rating, $comment, $media_id );

        if ( $result['success'] ) {
            wp_send_json_success( $result );
        } else {
            wp_send_json_error( $result );
        }
    }

    /**
     * Handle guest (non-logged-in) rating attempt
     */
    public function handle_guest_submit() {
        wp_send_json_error( array(
            'message'     => __( 'Bitte melden Sie sich an, um eine Bewertung abzugeben.', 'spezialist-ratings' ),
            'need_login'  => true,
            'login_url'   => function_exists( 'sd_get_page_url' ) ? sd_get_page_url( 'anmelden/' ) : wp_login_url(),
        ) );
    }

    /**
     * Handle owner response submission
     */
    public function handle_submit_response() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'sr_submit_rating' ) ) {
            wp_send_json_error( array(
                'message' => __( 'Sicherheitsprüfung fehlgeschlagen.', 'spezialist-ratings' ),
            ) );
        }

        // Get rating ID
        $rating_id = isset( $_POST['rating_id'] ) ? intval( $_POST['rating_id'] ) : 0;
        if ( ! $rating_id ) {
            wp_send_json_error( array(
                'message' => __( 'Ungültige Bewertung.', 'spezialist-ratings' ),
            ) );
        }

        // Get response
        $response = isset( $_POST['response'] ) ? sanitize_textarea_field( $_POST['response'] ) : '';
        if ( empty( $response ) ) {
            wp_send_json_error( array(
                'message' => __( 'Bitte geben Sie eine Antwort ein.', 'spezialist-ratings' ),
            ) );
        }

        // Get current user
        $user_id = get_current_user_id();

        // Submit response
        $result = SR_Ratings::submit_response( $rating_id, $user_id, $response );

        if ( $result['success'] ) {
            wp_send_json_success( $result );
        } else {
            wp_send_json_error( $result );
        }
    }

    /**
     * Handle get owner reviews request
     */
    public function handle_get_owner_reviews() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'sr_submit_rating' ) ) {
            wp_send_json_error( array(
                'message' => __( 'Sicherheitsprüfung fehlgeschlagen.', 'spezialist-ratings' ),
            ) );
        }

        $user_id = get_current_user_id();
        if ( ! $user_id ) {
            wp_send_json_error( array(
                'message' => __( 'Sie müssen angemeldet sein.', 'spezialist-ratings' ),
            ) );
        }

        $reviews = SR_Ratings::get_all_owner_reviews( $user_id );

        // Format reviews for response
        $formatted = array();
        foreach ( $reviews as $review ) {
            $formatted[] = array(
                'id'              => $review->id,
                'post_id'         => $review->post_id,
                'post_title'      => $review->post_title,
                'user_name'       => $review->user_name,
                'rating'          => $review->rating,
                'comment'         => $review->comment,
                'created_at'      => date_i18n( get_option( 'date_format' ), strtotime( $review->created_at ) ),
                'owner_response'  => $review->owner_response,
                'response_date'   => $review->owner_response_at ? date_i18n( get_option( 'date_format' ), strtotime( $review->owner_response_at ) ) : null,
                'can_respond'     => empty( $review->owner_response ),
            );
        }

        wp_send_json_success( array(
            'reviews' => $formatted,
        ) );
    }
}
