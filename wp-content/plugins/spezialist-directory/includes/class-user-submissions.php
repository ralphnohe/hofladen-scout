<?php
/**
 * User Submissions
 *
 * Handles frontend form submissions for new specialist entries
 *
 * @package Spezialist_Directory
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * SD_User_Submissions Class
 */
class SD_User_Submissions {

    /**
     * Single instance
     *
     * @var SD_User_Submissions
     */
    protected static $_instance = null;

    /**
     * Main Instance
     *
     * @return SD_User_Submissions
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
        add_shortcode( 'spezialist_submit', array( $this, 'render_submission_form' ) );
        add_action( 'wp_ajax_sd_submit_spezialist', array( $this, 'handle_submission' ) );
        add_action( 'wp_ajax_nopriv_sd_submit_spezialist', array( $this, 'handle_submission' ) );

        // Hook for listing status changes (for approval/rejection notifications)
        add_action( 'transition_post_status', array( $this, 'handle_listing_status_change' ), 10, 3 );
    }

    /**
     * Render submission form shortcode
     *
     * @param array $atts
     * @return string
     */
    public function render_submission_form( $atts ) {
        $atts = shortcode_atts( array(
            'redirect' => '',
        ), $atts, 'spezialist_submit' );

        // Check if user must be logged in
        $allow_guest = Spezialist_Directory::get_option( 'allow_guest_submissions', false );

        if ( ! $allow_guest && ! is_user_logged_in() ) {
            $login_url = sd_get_page_url( 'anmelden/?redirect_to=' . urlencode( get_permalink() ) );
            return '<div class="sd-notice sd-notice-warning">' .
                   '<p>' . __( 'Du musst angemeldet sein, um einen Hofladen-Eintrag einzureichen.', 'spezialist-directory' ) . '</p>' .
                   '<p><a href="' . esc_url( $login_url ) . '" class="sd-button">' . __( 'Jetzt anmelden', 'spezialist-directory' ) . '</a></p>' .
                   '</div>';
        }

        ob_start();
        include SD_PLUGIN_DIR . 'templates/submission-form.php';
        return ob_get_clean();
    }

    /**
     * Handle form submission via AJAX
     */
    public function handle_submission() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'sd_submit_spezialist' ) ) {
            wp_send_json_error( array(
                'message' => __( 'Sicherheitsprüfung fehlgeschlagen.', 'spezialist-directory' )
            ) );
        }

        // Check if user must be logged in
        $allow_guest = Spezialist_Directory::get_option( 'allow_guest_submissions', false );

        if ( ! $allow_guest && ! is_user_logged_in() ) {
            wp_send_json_error( array(
                'message' => __( 'Du musst angemeldet sein.', 'spezialist-directory' )
            ) );
        }

        // Validate required fields
        $errors = array();

        if ( empty( $_POST['title'] ) ) {
            $errors[] = __( 'Name ist erforderlich.', 'spezialist-directory' );
        }

        if ( empty( $_POST['description'] ) ) {
            $errors[] = __( 'Beschreibung ist erforderlich.', 'spezialist-directory' );
        }

        if ( empty( $_POST['email'] ) || ! is_email( $_POST['email'] ) ) {
            $errors[] = __( 'Gültige E-Mail-Adresse ist erforderlich.', 'spezialist-directory' );
        }

        if ( empty( $_POST['phone'] ) ) {
            $errors[] = __( 'Telefonnummer ist erforderlich.', 'spezialist-directory' );
        }

        if ( empty( $_POST['website'] ) ) {
            $errors[] = __( 'Website ist erforderlich.', 'spezialist-directory' );
        }

        if ( empty( $_POST['address'] ) ) {
            $errors[] = __( 'Straße & Hausnummer ist erforderlich.', 'spezialist-directory' );
        }

        if ( empty( $_POST['zip'] ) ) {
            $errors[] = __( 'Postleitzahl ist erforderlich.', 'spezialist-directory' );
        }

        if ( empty( $_POST['city'] ) ) {
            $errors[] = __( 'Stadt ist erforderlich.', 'spezialist-directory' );
        }

        if ( empty( $_POST['bundesland'] ) ) {
            $errors[] = __( 'Bundesland ist erforderlich.', 'spezialist-directory' );
        }

        // Tags sind optional - keine Validierung erforderlich

        if ( ! empty( $errors ) ) {
            wp_send_json_error( array(
                'message' => implode( '<br>', $errors )
            ) );
        }

        // Prepare post data
        $post_data = array(
            'post_title'   => sanitize_text_field( $_POST['title'] ),
            'post_content' => wp_kses_post( $_POST['description'] ),
            'post_type'    => 'hofladen',
            'post_status'  => Spezialist_Directory::get_option( 'require_approval', true ) ? 'pending' : 'publish',
            'post_author'  => is_user_logged_in() ? get_current_user_id() : 0,
        );

        // Insert post
        $post_id = wp_insert_post( $post_data );

        if ( is_wp_error( $post_id ) ) {
            wp_send_json_error( array(
                'message' => __( 'Fehler beim Erstellen des Eintrags.', 'spezialist-directory' )
            ) );
        }

        // Save meta data
        $this->save_submission_meta( $post_id, $_POST );

        // Handle tags (bestehende auswählen)
        if ( ! empty( $_POST['tags'] ) ) {
            $tags = array_map( 'intval', (array) $_POST['tags'] );
            wp_set_object_terms( $post_id, $tags, 'spezialist_tag' );
        }

        // Handle neue Tags (vom User eingegeben)
        if ( ! empty( $_POST['new_tags'] ) ) {
            $new_tags = array_filter( array_map( 'sanitize_text_field', explode( ',', $_POST['new_tags'] ) ) );
            foreach ( $new_tags as $tag_name ) {
                $tag_name = trim( $tag_name );
                if ( empty( $tag_name ) ) continue;

                // Prüfen ob Tag existiert, sonst erstellen
                $existing = term_exists( $tag_name, 'spezialist_tag' );
                if ( $existing ) {
                    wp_set_object_terms( $post_id, (int) $existing['term_id'], 'spezialist_tag', true );
                } else {
                    $new_term = wp_insert_term( $tag_name, 'spezialist_tag' );
                    if ( ! is_wp_error( $new_term ) ) {
                        wp_set_object_terms( $post_id, (int) $new_term['term_id'], 'spezialist_tag', true );
                    }
                }
            }
        }

        // Handle Bundesland (spezialist_category)
        if ( ! empty( $_POST['bundesland'] ) ) {
            $bundesland_id = intval( $_POST['bundesland'] );
            wp_set_object_terms( $post_id, $bundesland_id, 'spezialist_category', true );
        }

        // Handle image upload
        if ( ! empty( $_FILES['image']['name'] ) ) {
            $this->handle_image_upload( $post_id, $_FILES['image'] );
        }

        // Handle gallery uploads
        if ( ! empty( $_FILES['gallery']['name'][0] ) ) {
            $this->handle_gallery_upload( $post_id, $_FILES['gallery'] );
        }

        // Handle video upload
        if ( ! empty( $_FILES['video']['name'] ) ) {
            $this->handle_video_upload( $post_id, $_FILES['video'] );
        }

        // Send notification to admin
        $this->send_admin_notification( $post_id );

        // Send confirmation to user
        if ( is_user_logged_in() ) {
            $this->send_user_submission_confirmation( $post_id, get_current_user_id() );
        }

        // Mark as claimed if user is logged in
        if ( is_user_logged_in() ) {
            update_post_meta( $post_id, '_sd_is_claimed', '1' );
            update_post_meta( $post_id, '_sd_claimed_by', get_current_user_id() );
            update_post_meta( $post_id, '_sd_claimed_date', current_time( 'mysql' ) );
        }

        // Check for premium fields and set pending premium flag
        $has_premium = $this->has_premium_fields_filled( $_POST );
        $needs_checkout = false;
        $checkout_plan = isset( $_POST['premium_plan'] ) && in_array( $_POST['premium_plan'], array( 'monthly', 'yearly' ), true )
            ? sanitize_text_field( $_POST['premium_plan'] )
            : 'monthly';

        if ( $has_premium && is_user_logged_in() ) {
            update_post_meta( $post_id, '_sd_pending_premium', '1' );
            $needs_checkout = true;
        }

        $require_approval = Spezialist_Directory::get_option( 'require_approval', true );

        // Adjust message based on premium fields
        if ( $needs_checkout ) {
            $message = __( 'Dein Eintrag wurde erstellt. Bitte schließe jetzt die Premium-Zahlung ab.', 'spezialist-directory' );
        } elseif ( $require_approval ) {
            $message = __( 'Dein Eintrag wurde erfolgreich eingereicht und wartet auf Freigabe.', 'spezialist-directory' );
        } else {
            $message = __( 'Dein Eintrag wurde erfolgreich veröffentlicht!', 'spezialist-directory' );
        }

        // Determine redirect URL based on post status and login state
        if ( ! $require_approval ) {
            // Post is published immediately - redirect to the post
            $redirect_url = get_permalink( $post_id );
        } elseif ( is_user_logged_in() ) {
            if ( $needs_checkout ) {
                // Premium submission - redirect will be handled by JS after checkout
                $redirect_url = sd_get_page_url( 'mein-dashboard' ) . '?tab=listings&submission=premium_success&post_id=' . $post_id;
            } else {
                // Standard submission - redirect to dashboard
                $redirect_url = sd_get_page_url( 'mein-dashboard' ) . '?submission=success&post_id=' . $post_id;
            }
        } else {
            // Pending post + guest - stay on page (no redirect)
            $redirect_url = false;
        }

        wp_send_json_success( array(
            'message'        => $message,
            'post_id'        => $post_id,
            'redirect'       => $redirect_url,
            'needs_checkout' => $needs_checkout,
            'checkout_plan'  => $needs_checkout ? $checkout_plan : null,
        ) );
    }

    /**
     * Save submission meta data
     *
     * @param int $post_id
     * @param array $data
     */
    private function save_submission_meta( $post_id, $data ) {
        // Social media URL prefixes for username-to-URL conversion
        $social_prefixes = array(
            'facebook'  => 'https://facebook.com/',
            'twitter'   => 'https://x.com/',
            'instagram' => 'https://instagram.com/',
            'linkedin'  => 'https://linkedin.com/in/',
            'youtube'   => 'https://youtube.com/@',
            'xing'      => 'https://xing.com/profile/',
        );

        $meta_fields = array(
            'phone'     => 'sanitize_text_field',
            'email'     => 'sanitize_email',
            'website'   => 'esc_url_raw',
            'address'   => 'sanitize_text_field',
            'zip'       => 'sanitize_text_field',
            'city'      => 'sanitize_text_field',
            'facebook'  => 'esc_url_raw',
            'twitter'   => 'esc_url_raw',
            'instagram' => 'esc_url_raw',
            'linkedin'  => 'esc_url_raw',
            'youtube'   => 'esc_url_raw',
            'xing'      => 'esc_url_raw',
        );

        foreach ( $meta_fields as $field => $sanitize_callback ) {
            if ( isset( $data[ $field ] ) && ! empty( $data[ $field ] ) ) {
                $value = $data[ $field ];

                // Convert username to full URL for social media fields (fallback if JS didn't do it)
                if ( isset( $social_prefixes[ $field ] ) && strpos( $value, '://' ) === false ) {
                    // Remove @ prefix if present
                    $value = ltrim( $value, '@' );
                    $value = $social_prefixes[ $field ] . $value;
                }

                $value = call_user_func( $sanitize_callback, $value );
                update_post_meta( $post_id, '_sd_' . $field, $value );
            }
        }

        // Handle business hours
        if ( isset( $data['business_hours'] ) && is_array( $data['business_hours'] ) ) {
            $business_hours = array();
            $days = array( 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' );

            foreach ( $days as $day ) {
                if ( isset( $data['business_hours'][ $day ] ) ) {
                    $day_data = $data['business_hours'][ $day ];
                    $business_hours[ $day ] = array(
                        'open'       => isset( $day_data['open'] ) ? true : false,
                        'from'       => isset( $day_data['from'] ) ? sanitize_text_field( $day_data['from'] ) : '',
                        'to'         => isset( $day_data['to'] ) ? sanitize_text_field( $day_data['to'] ) : '',
                        'break_from' => isset( $day_data['break_from'] ) ? sanitize_text_field( $day_data['break_from'] ) : '',
                        'break_to'   => isset( $day_data['break_to'] ) ? sanitize_text_field( $day_data['break_to'] ) : '',
                    );
                }
            }

            update_post_meta( $post_id, '_sd_business_hours', $business_hours );
        }

        // Handle services
        if ( isset( $data['services'] ) && is_array( $data['services'] ) ) {
            $services = array_filter( array_map( 'sanitize_text_field', $data['services'] ) );
            $services = array_values( $services ); // Re-index array
            update_post_meta( $post_id, '_sd_services', $services );
        }
    }

    /**
     * Check if any premium fields are filled in the submission
     *
     * @param array $data Form data
     * @return bool True if at least one premium field is filled
     */
    private function has_premium_fields_filled( $data ) {
        // Check gallery (from $_FILES)
        if ( ! empty( $_FILES['gallery']['name'][0] ) ) {
            return true;
        }

        // Check video (from $_FILES)
        if ( ! empty( $_FILES['video']['name'] ) ) {
            return true;
        }

        // Check services
        if ( isset( $data['services'] ) && is_array( $data['services'] ) ) {
            $services = array_filter( $data['services'] );
            if ( ! empty( $services ) ) {
                return true;
            }
        }

        // Check business hours (at least one day marked as open)
        if ( isset( $data['business_hours'] ) && is_array( $data['business_hours'] ) ) {
            foreach ( $data['business_hours'] as $day_data ) {
                if ( isset( $day_data['open'] ) && $day_data['open'] ) {
                    return true;
                }
            }
        }

        // Check social media fields
        $social_fields = array( 'facebook', 'twitter', 'instagram', 'linkedin', 'youtube', 'xing' );
        foreach ( $social_fields as $field ) {
            if ( ! empty( $data[ $field ] ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Handle image upload
     *
     * @param int $post_id
     * @param array $file
     */
    private function handle_image_upload( $post_id, $file ) {
        require_once( ABSPATH . 'wp-admin/includes/image.php' );
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
        require_once( ABSPATH . 'wp-admin/includes/media.php' );

        $attachment_id = media_handle_upload( 'image', $post_id );

        if ( ! is_wp_error( $attachment_id ) ) {
            set_post_thumbnail( $post_id, $attachment_id );
        }
    }

    /**
     * Handle gallery upload (multiple images)
     *
     * @param int $post_id
     * @param array $files $_FILES['gallery'] array
     */
    private function handle_gallery_upload( $post_id, $files ) {
        require_once( ABSPATH . 'wp-admin/includes/image.php' );
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
        require_once( ABSPATH . 'wp-admin/includes/media.php' );

        $gallery_ids = array();
        $max_images = 10;

        // Reorganize $_FILES array for multiple files
        $file_count = count( $files['name'] );
        $file_count = min( $file_count, $max_images ); // Limit to max

        for ( $i = 0; $i < $file_count; $i++ ) {
            if ( empty( $files['name'][ $i ] ) ) {
                continue;
            }

            // Create single file array for upload handling
            $_FILES['gallery_upload'] = array(
                'name'     => $files['name'][ $i ],
                'type'     => $files['type'][ $i ],
                'tmp_name' => $files['tmp_name'][ $i ],
                'error'    => $files['error'][ $i ],
                'size'     => $files['size'][ $i ],
            );

            $attachment_id = media_handle_upload( 'gallery_upload', $post_id );

            if ( ! is_wp_error( $attachment_id ) ) {
                $gallery_ids[] = $attachment_id;
            }
        }

        // Cleanup temp file reference
        unset( $_FILES['gallery_upload'] );

        // Save gallery IDs to post meta
        if ( ! empty( $gallery_ids ) ) {
            update_post_meta( $post_id, '_sd_gallery_images', $gallery_ids );
        }
    }

    /**
     * Handle video upload (single video file)
     *
     * @param int $post_id
     * @param array $file $_FILES['video'] array
     */
    private function handle_video_upload( $post_id, $file ) {
        require_once( ABSPATH . 'wp-admin/includes/image.php' );
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
        require_once( ABSPATH . 'wp-admin/includes/media.php' );

        // Validate file size (max 10MB)
        $max_size = 10 * 1024 * 1024; // 10MB in bytes
        if ( $file['size'] > $max_size ) {
            return; // Skip if too large
        }

        // Validate file type
        $allowed_types = array( 'video/mp4', 'video/webm', 'video/quicktime' );
        if ( ! in_array( $file['type'], $allowed_types, true ) ) {
            return; // Skip if wrong type
        }

        $attachment_id = media_handle_upload( 'video', $post_id );

        if ( ! is_wp_error( $attachment_id ) ) {
            update_post_meta( $post_id, '_sd_video', $attachment_id );
        }
    }

    /**
     * Send notification to admin
     *
     * @param int $post_id
     */
    private function send_admin_notification( $post_id ) {
        // Check if notification is enabled
        if ( ! SD_Email_Templates::is_enabled( 'sd_notify_admin_new_listing' ) ) {
            return;
        }

        $post = get_post( $post_id );
        $admin_email = SD_Email_Templates::get_admin_email();

        $subject = sprintf(
            __( '[%s] Neuer Hofladen-Eintrag eingereicht', 'spezialist-directory' ),
            get_bloginfo( 'name' )
        );

        // Use HTML template
        $edit_url = admin_url( 'post.php?post=' . $post_id . '&action=edit' );
        $author = get_user_by( 'id', $post->post_author );
        $author_name = $author ? $author->display_name : __( 'Unbekannt', 'spezialist-directory' );
        $author_email = $author ? $author->user_email : '';
        $html_message = SD_Email_Templates::template_admin_new_listing( $post->post_title, $author_name, $author_email, $edit_url );

        $headers = array( 'Content-Type: text/html; charset=UTF-8' );
        wp_mail( $admin_email, $subject, $html_message, $headers );
    }

    /**
     * Get all categories for form
     *
     * @return array
     */
    public static function get_categories() {
        return get_terms( array(
            'taxonomy'   => 'spezialist_category',
            'hide_empty' => false,
        ) );
    }

    /**
     * Get all locations for form
     *
     * @return array
     */
    public static function get_locations() {
        return get_terms( array(
            'taxonomy'   => 'spezialist_location',
            'hide_empty' => false,
        ) );
    }

    /**
     * Get all tags for form
     *
     * @return array
     */
    public static function get_tags() {
        return get_terms( array(
            'taxonomy'   => 'spezialist_tag',
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ) );
    }

    /**
     * Get all Bundesländer for form
     *
     * @return array
     */
    public static function get_bundeslaender() {
        $bundesland_slugs = array(
            'baden-wuerttemberg',
            'bayern',
            'berlin',
            'brandenburg',
            'bremen',
            'hamburg',
            'hessen',
            'mecklenburg-vorpommern',
            'niedersachsen',
            'nordrhein-westfalen',
            'rheinland-pfalz',
            'saarland',
            'sachsen',
            'sachsen-anhalt',
            'schleswig-holstein',
            'thueringen',
        );

        return get_terms( array(
            'taxonomy'   => 'spezialist_category',
            'slug'       => $bundesland_slugs,
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        ) );
    }

    /**
     * Send confirmation email to user after submission
     *
     * @param int $post_id
     * @param int $user_id
     */
    private function send_user_submission_confirmation( $post_id, $user_id ) {
        // Check if notification is enabled
        if ( ! SD_Email_Templates::is_enabled( 'sd_notify_user_listing_submitted' ) ) {
            return;
        }

        $post = get_post( $post_id );
        $user = get_user_by( 'id', $user_id );

        if ( ! $post || ! $user ) {
            return;
        }

        $subject = sprintf(
            __( '[%s] Dein Hofladen-Eintrag wurde eingereicht', 'spezialist-directory' ),
            get_bloginfo( 'name' )
        );

        $dashboard_url = sd_get_page_url( 'mein-dashboard/' );
        $html_message = SD_Email_Templates::template_listing_submitted(
            $user->display_name,
            $post->post_title,
            $dashboard_url
        );

        $headers = array( 'Content-Type: text/html; charset=UTF-8' );
        wp_mail( $user->user_email, $subject, $html_message, $headers );
    }

    /**
     * Handle listing status changes for approval/rejection notifications
     *
     * @param string  $new_status New post status
     * @param string  $old_status Old post status
     * @param WP_Post $post       Post object
     */
    public function handle_listing_status_change( $new_status, $old_status, $post ) {
        // Only for hofladen post type
        if ( $post->post_type !== 'hofladen' ) {
            return;
        }

        // Skip if status didn't change
        if ( $new_status === $old_status ) {
            return;
        }

        // Listing approved: pending → publish
        if ( $old_status === 'pending' && $new_status === 'publish' ) {
            $this->send_listing_approved_email( $post );
        }

        // Listing rejected: pending → trash
        if ( $old_status === 'pending' && $new_status === 'trash' ) {
            $this->send_listing_rejected_email( $post );
        }
    }

    /**
     * Send listing approved email to user
     *
     * @param WP_Post $post
     */
    private function send_listing_approved_email( $post ) {
        // Check if notification is enabled
        if ( ! SD_Email_Templates::is_enabled( 'sd_notify_user_listing_approved' ) ) {
            return;
        }

        $author = get_user_by( 'id', $post->post_author );
        if ( ! $author ) {
            return;
        }

        $subject = sprintf(
            __( '[%s] Dein Hofladen-Eintrag wurde veröffentlicht!', 'spezialist-directory' ),
            get_bloginfo( 'name' )
        );

        $listing_url = get_permalink( $post->ID );
        $dashboard_url = sd_get_page_url( 'mein-dashboard/' );
        $html_message = SD_Email_Templates::template_listing_approved(
            $author->display_name,
            $post->post_title,
            $listing_url,
            $dashboard_url
        );

        $headers = array( 'Content-Type: text/html; charset=UTF-8' );
        wp_mail( $author->user_email, $subject, $html_message, $headers );
    }

    /**
     * Send listing rejected email to user
     *
     * @param WP_Post $post
     */
    private function send_listing_rejected_email( $post ) {
        // Check if notification is enabled
        if ( ! SD_Email_Templates::is_enabled( 'sd_notify_user_listing_rejected' ) ) {
            return;
        }

        $author = get_user_by( 'id', $post->post_author );
        if ( ! $author ) {
            return;
        }

        $subject = sprintf(
            __( '[%s] Dein Hofladen-Eintrag wurde abgelehnt', 'spezialist-directory' ),
            get_bloginfo( 'name' )
        );

        $contact_url = home_url( '/kontakt/' );
        $html_message = SD_Email_Templates::template_listing_rejected(
            $author->display_name,
            $post->post_title,
            $contact_url
        );

        $headers = array( 'Content-Type: text/html; charset=UTF-8' );
        wp_mail( $author->user_email, $subject, $html_message, $headers );
    }
}
