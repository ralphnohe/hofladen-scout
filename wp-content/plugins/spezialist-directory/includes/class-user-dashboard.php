<?php
/**
 * User Dashboard
 *
 * Handles the user dashboard for managing specialist listings
 *
 * @package Spezialist_Directory
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * SD_User_Dashboard Class
 */
class SD_User_Dashboard {

    /**
     * Single instance
     *
     * @var SD_User_Dashboard
     */
    protected static $_instance = null;

    /**
     * Main Instance
     *
     * @return SD_User_Dashboard
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
        add_shortcode( 'spezialist_dashboard', array( $this, 'render_dashboard' ) );
        add_action( 'wp_ajax_sd_delete_listing', array( $this, 'handle_delete_listing' ) );
        add_action( 'wp_ajax_sd_update_listing', array( $this, 'handle_update_listing' ) );
        add_action( 'wp_ajax_sd_get_listing_data', array( $this, 'handle_get_listing_data' ) );
        add_action( 'wp_ajax_sd_update_user_profile', array( $this, 'handle_update_user_profile' ) );
        add_action( 'wp_ajax_sd_toggle_pause_listing', array( $this, 'handle_toggle_pause_listing' ) );
        add_action( 'wp_ajax_sd_duplicate_listing', array( $this, 'handle_duplicate_listing' ) );
    }

    /**
     * Render dashboard shortcode
     *
     * @param array $atts
     * @return string
     */
    public function render_dashboard( $atts ) {
        // Check if user is logged in
        if ( ! is_user_logged_in() ) {
            $login_url = sd_get_page_url( 'anmelden/?redirect_to=' . urlencode( get_permalink() ) );
            return '<div class="sd-notice sd-notice-warning">' .
                   '<p>' . __( 'Du musst angemeldet sein, um auf dein Dashboard zuzugreifen.', 'spezialist-directory' ) . '</p>' .
                   '<p><a href="' . esc_url( $login_url ) . '" class="sd-button">' . __( 'Jetzt anmelden', 'spezialist-directory' ) . '</a></p>' .
                   '</div>';
        }

        ob_start();
        include SD_PLUGIN_DIR . 'templates/user-dashboard.php';
        return ob_get_clean();
    }

    /**
     * Get user's listings (listings where user is the author)
     *
     * @param int $user_id
     * @param int $paged Current page number
     * @param int $per_page Posts per page (-1 for all)
     * @return WP_Query
     */
    public static function get_user_listings( $user_id = null, $paged = 1, $per_page = 20 ) {
        if ( null === $user_id ) {
            $user_id = get_current_user_id();
        }

        $args = array(
            'post_type'      => 'hofladen',
            'author'         => $user_id,
            'posts_per_page' => $per_page,
            'paged'          => $paged,
            'post_status'    => array( 'publish', 'pending', 'draft' ),
            'orderby'        => 'date',
            'order'          => 'DESC',
        );

        return new WP_Query( $args );
    }

    /**
     * Get user's claimed listings (listings claimed by user via claim system)
     *
     * @param int $user_id
     * @param int $paged Current page number
     * @param int $per_page Posts per page (-1 for all)
     * @return WP_Query
     */
    public static function get_user_claimed_listings( $user_id = null, $paged = 1, $per_page = 20 ) {
        if ( null === $user_id ) {
            $user_id = get_current_user_id();
        }

        $args = array(
            'post_type'      => 'hofladen',
            'posts_per_page' => $per_page,
            'paged'          => $paged,
            'post_status'    => array( 'publish', 'pending', 'draft' ),
            'orderby'        => 'date',
            'order'          => 'DESC',
            'meta_query'     => array(
                array(
                    'key'     => '_sd_claimed_by',
                    'value'   => $user_id,
                    'compare' => '=',
                    'type'    => 'NUMERIC',
                ),
            ),
        );

        return new WP_Query( $args );
    }

    /**
     * Get total count of user's listings without loading all posts
     *
     * @param int $user_id
     * @return int
     */
    public static function get_user_listings_count( $user_id = null ) {
        if ( null === $user_id ) {
            $user_id = get_current_user_id();
        }

        $args = array(
            'post_type'      => 'hofladen',
            'author'         => $user_id,
            'posts_per_page' => 1,
            'post_status'    => array( 'publish', 'pending', 'draft' ),
            'fields'         => 'ids',
        );

        $query = new WP_Query( $args );
        return $query->found_posts;
    }

    /**
     * Get total count of user's claimed listings without loading all posts
     *
     * @param int $user_id
     * @return int
     */
    public static function get_user_claimed_listings_count( $user_id = null ) {
        if ( null === $user_id ) {
            $user_id = get_current_user_id();
        }

        $args = array(
            'post_type'      => 'hofladen',
            'posts_per_page' => 1,
            'post_status'    => array( 'publish', 'pending', 'draft' ),
            'fields'         => 'ids',
            'meta_query'     => array(
                array(
                    'key'     => '_sd_claimed_by',
                    'value'   => $user_id,
                    'compare' => '=',
                    'type'    => 'NUMERIC',
                ),
            ),
        );

        $query = new WP_Query( $args );
        return $query->found_posts;
    }

    /**
     * Check if user can edit a listing (owner or claimed)
     *
     * @param int $post_id
     * @param int $user_id
     * @return bool
     */
    public static function user_can_edit_listing( $post_id, $user_id = null ) {
        if ( null === $user_id ) {
            $user_id = get_current_user_id();
        }

        // Admins can edit anything
        if ( current_user_can( 'manage_options' ) ) {
            return true;
        }

        $post = get_post( $post_id );
        if ( ! $post || 'hofladen' !== $post->post_type ) {
            return false;
        }

        // Check if user is the author
        if ( intval( $post->post_author ) === $user_id ) {
            return true;
        }

        // Check if user has claimed this listing
        $claimed_by = get_post_meta( $post_id, '_sd_claimed_by', true );
        if ( intval( $claimed_by ) === $user_id ) {
            return true;
        }

        return false;
    }

    /**
     * Handle delete listing
     */
    public function handle_delete_listing() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'sd_delete_listing' ) ) {
            wp_send_json_error( array(
                'message' => __( 'Sicherheitsprüfung fehlgeschlagen.', 'spezialist-directory' )
            ) );
        }

        // Check if user is logged in
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array(
                'message' => __( 'Du musst angemeldet sein.', 'spezialist-directory' )
            ) );
        }

        $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;

        if ( ! $post_id ) {
            wp_send_json_error( array(
                'message' => __( 'Ungültiger Eintrag.', 'spezialist-directory' )
            ) );
        }

        // Check if user can edit this listing (owner or claimed)
        if ( ! self::user_can_edit_listing( $post_id ) ) {
            wp_send_json_error( array(
                'message' => __( 'Du hast keine Berechtigung, diesen Eintrag zu löschen.', 'spezialist-directory' )
            ) );
        }

        // Delete post
        $result = wp_delete_post( $post_id, true );

        if ( $result ) {
            wp_send_json_success( array(
                'message' => __( 'Eintrag erfolgreich gelöscht.', 'spezialist-directory' )
            ) );
        } else {
            wp_send_json_error( array(
                'message' => __( 'Fehler beim Löschen des Eintrags.', 'spezialist-directory' )
            ) );
        }
    }

    /**
     * Handle update listing
     */
    public function handle_update_listing() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'sd_update_listing' ) ) {
            wp_send_json_error( array(
                'message' => __( 'Sicherheitsprüfung fehlgeschlagen.', 'spezialist-directory' )
            ) );
        }

        // Check if user is logged in
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array(
                'message' => __( 'Du musst angemeldet sein.', 'spezialist-directory' )
            ) );
        }

        $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;

        if ( ! $post_id ) {
            wp_send_json_error( array(
                'message' => __( 'Ungültiger Eintrag.', 'spezialist-directory' )
            ) );
        }

        // Check if user can edit this listing (owner or claimed)
        if ( ! self::user_can_edit_listing( $post_id ) ) {
            wp_send_json_error( array(
                'message' => __( 'Du hast keine Berechtigung, diesen Eintrag zu bearbeiten.', 'spezialist-directory' )
            ) );
        }

        $post = get_post( $post_id );

        // Validate required fields
        $errors = array();

        if ( empty( $_POST['title'] ) ) {
            $errors[] = __( 'Name ist erforderlich.', 'spezialist-directory' );
        }

        if ( empty( $_POST['description'] ) ) {
            $errors[] = __( 'Beschreibung ist erforderlich.', 'spezialist-directory' );
        }

        if ( ! empty( $errors ) ) {
            wp_send_json_error( array(
                'message' => implode( '<br>', $errors )
            ) );
        }

        // Update post
        $post_data = array(
            'ID'           => $post_id,
            'post_title'   => sanitize_text_field( $_POST['title'] ),
            'post_content' => wp_kses_post( $_POST['description'] ),
        );

        // If admin approval required, set to pending on update
        if ( Spezialist_Directory::get_option( 'require_approval', true ) && 'publish' === $post->post_status ) {
            // Keep published posts published
        } elseif ( Spezialist_Directory::get_option( 'require_approval', true ) ) {
            $post_data['post_status'] = 'pending';
        }

        $result = wp_update_post( $post_data );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array(
                'message' => __( 'Fehler beim Aktualisieren des Eintrags.', 'spezialist-directory' )
            ) );
        }

        // Update meta data
        $this->update_listing_meta( $post_id, $_POST );

        // Update taxonomies
        if ( ! empty( $_POST['category'] ) ) {
            $categories = array_map( 'intval', (array) $_POST['category'] );
            wp_set_object_terms( $post_id, $categories, 'spezialist_category' );
        }

        if ( ! empty( $_POST['location'] ) ) {
            $locations = array_map( 'intval', (array) $_POST['location'] );
            wp_set_object_terms( $post_id, $locations, 'spezialist_location' );

            // Synchronisiere _sd_neighborhood vom ersten Location-Term
            $first_location = get_term( $locations[0], 'spezialist_location' );
            if ( $first_location && ! is_wp_error( $first_location ) ) {
                update_post_meta( $post_id, '_sd_neighborhood', $first_location->name );
            }
        }

        // Handle gallery updates (Premium only)
        $is_premium = get_post_meta( $post_id, '_sd_is_premium', true );
        if ( $is_premium ) {
            $this->handle_gallery_update( $post_id );
            $this->handle_video_update( $post_id );
        }

        // Handle profile image upload
        $this->handle_profile_image_update( $post_id );

        wp_send_json_success( array(
            'message' => __( 'Eintrag erfolgreich aktualisiert.', 'spezialist-directory' )
        ) );
    }

    /**
     * Handle gallery update (add new images, remove deleted ones)
     *
     * @param int $post_id
     */
    private function handle_gallery_update( $post_id ) {
        // Get current gallery IDs
        $gallery_ids = get_post_meta( $post_id, '_sd_gallery_images', true );
        if ( ! is_array( $gallery_ids ) ) {
            $gallery_ids = array();
        }

        // Handle removed images
        if ( ! empty( $_POST['removed_gallery_ids'] ) ) {
            $removed_ids = array_map( 'intval', (array) $_POST['removed_gallery_ids'] );
            foreach ( $removed_ids as $removed_id ) {
                // Remove from gallery array
                $key = array_search( $removed_id, $gallery_ids );
                if ( $key !== false ) {
                    unset( $gallery_ids[ $key ] );
                }
                // Delete attachment
                wp_delete_attachment( $removed_id, true );
            }
            $gallery_ids = array_values( $gallery_ids ); // Re-index
        }

        // Handle new image uploads
        if ( ! empty( $_FILES['gallery']['name'][0] ) ) {
            require_once( ABSPATH . 'wp-admin/includes/image.php' );
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
            require_once( ABSPATH . 'wp-admin/includes/media.php' );

            $max_images = 10;
            $current_count = count( $gallery_ids );
            $files = $_FILES['gallery'];
            $file_count = count( $files['name'] );

            for ( $i = 0; $i < $file_count; $i++ ) {
                // Check max limit
                if ( $current_count >= $max_images ) {
                    break;
                }

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
                    $current_count++;
                }
            }

            // Cleanup temp file reference
            unset( $_FILES['gallery_upload'] );
        }

        // Update gallery meta
        update_post_meta( $post_id, '_sd_gallery_images', $gallery_ids );
    }

    /**
     * Handle profile image update
     *
     * @param int $post_id
     */
    private function handle_profile_image_update( $post_id ) {
        // Check if a new profile image was uploaded
        if ( empty( $_FILES['profile_image']['name'] ) ) {
            return;
        }

        require_once( ABSPATH . 'wp-admin/includes/image.php' );
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
        require_once( ABSPATH . 'wp-admin/includes/media.php' );

        // Delete old featured image if exists
        $old_thumbnail_id = get_post_thumbnail_id( $post_id );
        if ( $old_thumbnail_id ) {
            wp_delete_attachment( $old_thumbnail_id, true );
        }

        // Upload new image
        $attachment_id = media_handle_upload( 'profile_image', $post_id );

        if ( ! is_wp_error( $attachment_id ) ) {
            set_post_thumbnail( $post_id, $attachment_id );
        }
    }

    /**
     * Handle video update (add new video, remove deleted one)
     *
     * @param int $post_id
     */
    private function handle_video_update( $post_id ) {
        // Handle video removal
        if ( ! empty( $_POST['remove_video'] ) && $_POST['remove_video'] === '1' ) {
            $video_id = get_post_meta( $post_id, '_sd_video', true );
            if ( $video_id ) {
                wp_delete_attachment( $video_id, true );
                delete_post_meta( $post_id, '_sd_video' );
            }
        }

        // Handle new video upload
        if ( empty( $_FILES['video']['name'] ) ) {
            return;
        }

        require_once( ABSPATH . 'wp-admin/includes/image.php' );
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
        require_once( ABSPATH . 'wp-admin/includes/media.php' );

        // Delete old video if exists
        $old_video_id = get_post_meta( $post_id, '_sd_video', true );
        if ( $old_video_id ) {
            wp_delete_attachment( $old_video_id, true );
        }

        // Upload new video
        $attachment_id = media_handle_upload( 'video', $post_id );

        if ( ! is_wp_error( $attachment_id ) ) {
            update_post_meta( $post_id, '_sd_video', $attachment_id );
        }
    }

    /**
     * Update listing meta data
     *
     * @param int $post_id
     * @param array $data
     */
    private function update_listing_meta( $post_id, $data ) {
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
            if ( isset( $data[ $field ] ) ) {
                $value = $data[ $field ];

                // Convert username to full URL for social media fields (fallback if JS didn't do it)
                if ( ! empty( $value ) && isset( $social_prefixes[ $field ] ) && strpos( $value, '://' ) === false ) {
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

        // Handle coordinates from location picker
        $manual_lat = isset( $data['latitude'] ) ? floatval( $data['latitude'] ) : 0;
        $manual_lng = isset( $data['longitude'] ) ? floatval( $data['longitude'] ) : 0;

        if ( $manual_lat && $manual_lng ) {
            update_post_meta( $post_id, '_sd_latitude', $manual_lat );
            update_post_meta( $post_id, '_sd_longitude', $manual_lng );
        }
    }

    /**
     * Get listing data for editing
     *
     * @param int $post_id
     * @return array|false
     */
    public static function get_listing_data( $post_id ) {
        $post = get_post( $post_id );

        if ( ! $post || 'hofladen' !== $post->post_type ) {
            return false;
        }

        // Check if user can edit this listing (owner, claimed, or admin)
        if ( ! self::user_can_edit_listing( $post_id ) ) {
            return false;
        }

        $data = array(
            'id'          => $post->ID,
            'title'       => $post->post_title,
            'description' => $post->post_content,
            'status'      => $post->post_status,
        );

        // Meta fields
        $meta_fields = array(
            'phone', 'email', 'website', 'address', 'zip', 'city',
            'facebook', 'twitter', 'instagram', 'linkedin', 'youtube', 'xing',
            'is_premium', 'premium_until', 'latitude', 'longitude'
        );

        foreach ( $meta_fields as $field ) {
            $data[ $field ] = get_post_meta( $post->ID, '_sd_' . $field, true );
        }

        // Business hours
        $business_hours = get_post_meta( $post->ID, '_sd_business_hours', true );
        $data['business_hours'] = is_array( $business_hours ) ? $business_hours : array();

        // Services
        $services = get_post_meta( $post->ID, '_sd_services', true );
        $data['services'] = is_array( $services ) ? $services : array();

        // Taxonomies
        $categories = wp_get_object_terms( $post->ID, 'spezialist_category', array( 'fields' => 'ids' ) );
        $data['categories'] = ! is_wp_error( $categories ) ? $categories : array();

        $locations = wp_get_object_terms( $post->ID, 'spezialist_location', array( 'fields' => 'ids' ) );
        $data['locations'] = ! is_wp_error( $locations ) ? $locations : array();

        // Featured image (profile image)
        $data['thumbnail_url'] = get_the_post_thumbnail_url( $post->ID, 'medium' );
        $data['featured_image'] = get_the_post_thumbnail_url( $post->ID, 'medium' );

        // Video (Premium only)
        $video_id = get_post_meta( $post->ID, '_sd_video', true );
        $data['video_url'] = $video_id ? wp_get_attachment_url( $video_id ) : '';

        // Gallery images (Premium only)
        $gallery_ids = get_post_meta( $post->ID, '_sd_gallery_images', true );
        $gallery_images = array();
        if ( $data['is_premium'] && is_array( $gallery_ids ) && ! empty( $gallery_ids ) ) {
            foreach ( $gallery_ids as $attachment_id ) {
                $thumbnail = wp_get_attachment_image_url( $attachment_id, 'thumbnail' );
                if ( $thumbnail ) {
                    $gallery_images[] = array(
                        'id'        => $attachment_id,
                        'thumbnail' => $thumbnail,
                    );
                }
            }
        }
        $data['gallery_images'] = $gallery_images;

        return $data;
    }

    /**
     * Handle AJAX request to get listing data for editing
     */
    public function handle_get_listing_data() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'sd_get_listing_data' ) ) {
            wp_send_json_error( array(
                'message' => __( 'Sicherheitsprüfung fehlgeschlagen.', 'spezialist-directory' )
            ) );
        }

        // Check if user is logged in
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array(
                'message' => __( 'Du musst angemeldet sein.', 'spezialist-directory' )
            ) );
        }

        $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;

        if ( ! $post_id ) {
            wp_send_json_error( array(
                'message' => __( 'Ungültiger Eintrag.', 'spezialist-directory' )
            ) );
        }

        $listing_data = self::get_listing_data( $post_id );

        if ( ! $listing_data ) {
            wp_send_json_error( array(
                'message' => __( 'Du hast keine Berechtigung, diesen Eintrag zu bearbeiten.', 'spezialist-directory' )
            ) );
        }

        // Get taxonomy options for dropdowns
        $categories = get_terms( array(
            'taxonomy'   => 'spezialist_category',
            'hide_empty' => false,
        ) );

        $locations = get_terms( array(
            'taxonomy'   => 'spezialist_location',
            'hide_empty' => false,
        ) );

        $listing_data['all_categories'] = ! is_wp_error( $categories ) ? array_map( function( $term ) {
            return array(
                'id'   => $term->term_id,
                'name' => $term->name,
            );
        }, $categories ) : array();

        $listing_data['all_locations'] = ! is_wp_error( $locations ) ? array_map( function( $term ) {
            return array(
                'id'   => $term->term_id,
                'name' => $term->name,
            );
        }, $locations ) : array();

        wp_send_json_success( $listing_data );
    }

    /**
     * Handle AJAX request to update user profile
     *
     * @since 1.1.0
     */
    public function handle_update_user_profile() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'sd_update_profile' ) ) {
            wp_send_json_error( array(
                'message' => __( 'Sicherheitsprüfung fehlgeschlagen.', 'spezialist-directory' )
            ) );
        }

        // Check if user is logged in
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array(
                'message' => __( 'Du musst angemeldet sein.', 'spezialist-directory' )
            ) );
        }

        $user_id = get_current_user_id();
        $current_user = wp_get_current_user();

        // Sanitize input
        $first_name = isset( $_POST['first_name'] ) ? sanitize_text_field( $_POST['first_name'] ) : '';
        $last_name = isset( $_POST['last_name'] ) ? sanitize_text_field( $_POST['last_name'] ) : '';
        $email = isset( $_POST['email'] ) ? sanitize_email( $_POST['email'] ) : '';

        // Validate required fields
        if ( empty( $first_name ) ) {
            wp_send_json_error( array(
                'message' => __( 'Vorname ist erforderlich.', 'spezialist-directory' )
            ) );
        }

        if ( empty( $email ) ) {
            wp_send_json_error( array(
                'message' => __( 'E-Mail ist erforderlich.', 'spezialist-directory' )
            ) );
        }

        // Validate email format
        if ( ! is_email( $email ) ) {
            wp_send_json_error( array(
                'message' => __( 'Bitte gib eine gültige E-Mail-Adresse ein.', 'spezialist-directory' )
            ) );
        }

        // Check email uniqueness (if changed)
        if ( $email !== $current_user->user_email && email_exists( $email ) ) {
            wp_send_json_error( array(
                'message' => __( 'Diese E-Mail-Adresse wird bereits von einem anderen Benutzer verwendet.', 'spezialist-directory' )
            ) );
        }

        // Build display name
        $display_name = trim( $first_name . ' ' . $last_name );
        if ( empty( $display_name ) ) {
            $display_name = $first_name;
        }

        // Update user
        $user_data = array(
            'ID'           => $user_id,
            'first_name'   => $first_name,
            'last_name'    => $last_name,
            'display_name' => $display_name,
            'user_email'   => $email,
        );

        $result = wp_update_user( $user_data );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( array(
                'message' => $result->get_error_message()
            ) );
        }

        wp_send_json_success( array(
            'message'      => __( 'Profil erfolgreich aktualisiert.', 'spezialist-directory' ),
            'display_name' => $display_name,
            'email'        => $email,
        ) );
    }

    /**
     * Handle AJAX request to toggle pause status on a listing
     *
     * @since 1.2.0
     */
    public function handle_toggle_pause_listing() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'sd_toggle_pause' ) ) {
            wp_send_json_error( array(
                'message' => __( 'Sicherheitsprüfung fehlgeschlagen.', 'spezialist-directory' )
            ) );
        }

        // Check if user is logged in
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array(
                'message' => __( 'Du musst angemeldet sein.', 'spezialist-directory' )
            ) );
        }

        $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;

        if ( ! $post_id ) {
            wp_send_json_error( array(
                'message' => __( 'Ungültiger Eintrag.', 'spezialist-directory' )
            ) );
        }

        // Check if user can edit this listing
        if ( ! self::user_can_edit_listing( $post_id ) ) {
            wp_send_json_error( array(
                'message' => __( 'Du hast keine Berechtigung, diesen Eintrag zu pausieren.', 'spezialist-directory' )
            ) );
        }

        // Toggle pause status
        $is_paused = get_post_meta( $post_id, '_sd_paused', true );
        $new_status = $is_paused ? '' : '1';

        update_post_meta( $post_id, '_sd_paused', $new_status );

        // Store timestamp of when listing was paused/unpaused
        if ( $new_status ) {
            update_post_meta( $post_id, '_sd_paused_at', current_time( 'mysql' ) );
        } else {
            delete_post_meta( $post_id, '_sd_paused_at' );
        }

        $message = $new_status
            ? __( 'Eintrag pausiert. Er ist jetzt in der Suche nicht mehr sichtbar.', 'spezialist-directory' )
            : __( 'Eintrag wieder aktiviert. Er ist jetzt in der Suche sichtbar.', 'spezialist-directory' );

        wp_send_json_success( array(
            'message'   => $message,
            'is_paused' => (bool) $new_status,
        ) );
    }

    /**
     * Check if a listing is paused
     *
     * @param int $post_id
     * @return bool
     */
    public static function is_listing_paused( $post_id ) {
        return (bool) get_post_meta( $post_id, '_sd_paused', true );
    }

    /**
     * Handle AJAX request to duplicate a listing
     *
     * @since 1.2.0
     */
    public function handle_duplicate_listing() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'sd_duplicate_listing' ) ) {
            wp_send_json_error( array(
                'message' => __( 'Sicherheitsprüfung fehlgeschlagen.', 'spezialist-directory' )
            ) );
        }

        // Check if user is logged in
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( array(
                'message' => __( 'Du musst angemeldet sein.', 'spezialist-directory' )
            ) );
        }

        $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;

        if ( ! $post_id ) {
            wp_send_json_error( array(
                'message' => __( 'Ungültiger Eintrag.', 'spezialist-directory' )
            ) );
        }

        // Check if user can edit this listing
        if ( ! self::user_can_edit_listing( $post_id ) ) {
            wp_send_json_error( array(
                'message' => __( 'Du hast keine Berechtigung, diesen Eintrag zu duplizieren.', 'spezialist-directory' )
            ) );
        }

        // Get original post
        $original_post = get_post( $post_id );

        if ( ! $original_post || 'hofladen' !== $original_post->post_type ) {
            wp_send_json_error( array(
                'message' => __( 'Eintrag nicht gefunden.', 'spezialist-directory' )
            ) );
        }

        // Create new post with (Kopie) suffix
        $new_post_data = array(
            'post_title'   => $original_post->post_title . ' ' . __( '(Kopie)', 'spezialist-directory' ),
            'post_content' => $original_post->post_content,
            'post_status'  => 'draft', // Always create as draft
            'post_type'    => 'hofladen',
            'post_author'  => get_current_user_id(),
        );

        $new_post_id = wp_insert_post( $new_post_data );

        if ( is_wp_error( $new_post_id ) ) {
            wp_send_json_error( array(
                'message' => __( 'Fehler beim Erstellen der Kopie.', 'spezialist-directory' )
            ) );
        }

        // Copy all meta fields (except certain ones)
        $meta_keys_to_skip = array(
            '_sd_claimed_by',
            '_sd_claimed_date',
            '_sd_claim_message',
            '_sd_is_premium',
            '_sd_premium_until',
            '_sd_stripe_subscription_id',
            '_sd_stripe_customer_id',
            '_sd_subscription_plan',
            '_sd_subscription_cancel_at_period_end',
            '_sd_paused',
            '_sd_paused_at',
        );

        $original_meta = get_post_meta( $post_id );

        foreach ( $original_meta as $meta_key => $meta_values ) {
            // Skip certain meta keys
            if ( in_array( $meta_key, $meta_keys_to_skip, true ) ) {
                continue;
            }

            // Skip WordPress internal meta
            if ( strpos( $meta_key, '_wp_' ) === 0 ) {
                continue;
            }

            // Copy each meta value
            foreach ( $meta_values as $meta_value ) {
                add_post_meta( $new_post_id, $meta_key, maybe_unserialize( $meta_value ) );
            }
        }

        // Copy taxonomies
        $taxonomies = array( 'spezialist_category', 'spezialist_location' );

        foreach ( $taxonomies as $taxonomy ) {
            $terms = wp_get_object_terms( $post_id, $taxonomy, array( 'fields' => 'ids' ) );
            if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
                wp_set_object_terms( $new_post_id, $terms, $taxonomy );
            }
        }

        // Copy featured image
        $thumbnail_id = get_post_thumbnail_id( $post_id );
        if ( $thumbnail_id ) {
            set_post_thumbnail( $new_post_id, $thumbnail_id );
        }

        wp_send_json_success( array(
            'message'     => __( 'Eintrag erfolgreich dupliziert. Die Kopie wurde als Entwurf gespeichert.', 'spezialist-directory' ),
            'new_post_id' => $new_post_id,
            'new_title'   => $new_post_data['post_title'],
        ) );
    }
}
