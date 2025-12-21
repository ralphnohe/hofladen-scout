<?php
/**
 * Meta Boxes
 *
 * Handles custom meta boxes and fields for Spezialist CPT
 *
 * @package Spezialist_Directory
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * SD_Meta_Boxes Class
 */
class SD_Meta_Boxes {

    /**
     * Single instance
     *
     * @var SD_Meta_Boxes
     */
    protected static $_instance = null;

    /**
     * Main Instance
     *
     * @return SD_Meta_Boxes
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
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        add_action( 'save_post', array( $this, 'save_meta_boxes' ), 10, 2 );
    }

    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        // Contact Information
        add_meta_box(
            'sd_contact_info',
            __( 'Kontaktinformationen', 'spezialist-directory' ),
            array( $this, 'render_contact_info_meta_box' ),
            'hofladen',
            'normal',
            'high'
        );

        // Business Hours
        add_meta_box(
            'sd_business_hours',
            __( 'Öffnungszeiten', 'spezialist-directory' ),
            array( $this, 'render_business_hours_meta_box' ),
            'hofladen',
            'normal',
            'default'
        );

        // Services/Offerings
        add_meta_box(
            'sd_services',
            __( 'Angebotene Leistungen', 'spezialist-directory' ),
            array( $this, 'render_services_meta_box' ),
            'hofladen',
            'normal',
            'default'
        );

        // Social Media
        add_meta_box(
            'sd_social_media',
            __( 'Social Media Links', 'spezialist-directory' ),
            array( $this, 'render_social_media_meta_box' ),
            'hofladen',
            'normal',
            'default'
        );

        // Premium Status
        add_meta_box(
            'sd_premium_status',
            __( 'Premium Status', 'spezialist-directory' ),
            array( $this, 'render_premium_status_meta_box' ),
            'hofladen',
            'side',
            'high'
        );

        // Claim Information (only for admins)
        if ( current_user_can( 'manage_options' ) ) {
            add_meta_box(
                'sd_claim_info',
                __( 'Claim Informationen', 'spezialist-directory' ),
                array( $this, 'render_claim_info_meta_box' ),
                'hofladen',
                'side',
                'default'
            );

            // Verification Status (only for admins)
            add_meta_box(
                'sd_verification_status',
                __( 'Verifizierung', 'spezialist-directory' ),
                array( $this, 'render_verification_status_meta_box' ),
                'hofladen',
                'side',
                'high'
            );
        }
    }

    /**
     * Render Contact Information Meta Box
     */
    public function render_contact_info_meta_box( $post ) {
        wp_nonce_field( 'sd_contact_info_nonce', 'sd_contact_info_nonce' );

        $phone = get_post_meta( $post->ID, '_sd_phone', true );
        $email = get_post_meta( $post->ID, '_sd_email', true );
        $website = get_post_meta( $post->ID, '_sd_website', true );
        $address = get_post_meta( $post->ID, '_sd_address', true );
        $zip = get_post_meta( $post->ID, '_sd_zip', true );
        $city = get_post_meta( $post->ID, '_sd_city', true );
        $latitude = get_post_meta( $post->ID, '_sd_latitude', true );
        $longitude = get_post_meta( $post->ID, '_sd_longitude', true );
        $place_id = get_post_meta( $post->ID, '_sd_place_id', true );
        $neighborhood = get_post_meta( $post->ID, '_sd_neighborhood', true );
        ?>
        <div class="sd-meta-box-fields">
            <p>
                <label for="sd_phone"><strong><?php _e( 'Telefon:', 'spezialist-directory' ); ?></strong></label><br>
                <input type="tel" id="sd_phone" name="sd_phone" value="<?php echo esc_attr( $phone ); ?>" class="widefat" placeholder="+49 123 456789">
            </p>
            <p>
                <label for="sd_email"><strong><?php _e( 'E-Mail:', 'spezialist-directory' ); ?></strong></label><br>
                <input type="email" id="sd_email" name="sd_email" value="<?php echo esc_attr( $email ); ?>" class="widefat" placeholder="mail@example.com">
            </p>
            <p>
                <label for="sd_website"><strong><?php _e( 'Website:', 'spezialist-directory' ); ?></strong></label><br>
                <input type="url" id="sd_website" name="sd_website" value="<?php echo esc_url( $website ); ?>" class="widefat" placeholder="https://example.com">
            </p>
            <p>
                <label for="sd_address"><strong><?php _e( 'Straße & Hausnummer:', 'spezialist-directory' ); ?></strong></label><br>
                <input type="text" id="sd_address" name="sd_address" value="<?php echo esc_attr( $address ); ?>" class="widefat" placeholder="Musterstraße 123">
            </p>
            <p>
                <label for="sd_zip"><strong><?php _e( 'Postleitzahl:', 'spezialist-directory' ); ?></strong></label><br>
                <input type="text" id="sd_zip" name="sd_zip" value="<?php echo esc_attr( $zip ); ?>" class="widefat" placeholder="12345">
            </p>
            <p>
                <label for="sd_city"><strong><?php _e( 'Stadt:', 'spezialist-directory' ); ?></strong></label><br>
                <input type="text" id="sd_city" name="sd_city" value="<?php echo esc_attr( $city ); ?>" class="widefat" placeholder="Berlin">
            </p>
            <hr style="margin: 15px 0;">
            <p>
                <label for="sd_latitude"><strong><?php _e( 'Breitengrad (Latitude):', 'spezialist-directory' ); ?></strong></label><br>
                <input type="text" id="sd_latitude" name="sd_latitude" value="<?php echo esc_attr( $latitude ); ?>" class="widefat" placeholder="52.520008">
            </p>
            <p>
                <label for="sd_longitude"><strong><?php _e( 'Längengrad (Longitude):', 'spezialist-directory' ); ?></strong></label><br>
                <input type="text" id="sd_longitude" name="sd_longitude" value="<?php echo esc_attr( $longitude ); ?>" class="widefat" placeholder="13.404954">
            </p>
            <p>
                <label for="sd_place_id"><strong><?php _e( 'Google Place ID:', 'spezialist-directory' ); ?></strong></label><br>
                <input type="text" id="sd_place_id" name="sd_place_id" value="<?php echo esc_attr( $place_id ); ?>" class="widefat" placeholder="ChIJAVkDPzdOqEcRcDteW0YgIQQ">
            </p>
            <p>
                <label for="sd_neighborhood"><strong><?php _e( 'Stadtteil:', 'spezialist-directory' ); ?></strong></label><br>
                <input type="text" id="sd_neighborhood" name="sd_neighborhood" value="<?php echo esc_attr( $neighborhood ); ?>" class="widefat" readonly style="background-color: #f0f0f0;">
                <span class="description"><?php _e( 'Automatisch vom Standort übernommen', 'spezialist-directory' ); ?></span>
            </p>
        </div>
        <?php
    }

    /**
     * Render Business Hours Meta Box
     */
    public function render_business_hours_meta_box( $post ) {
        wp_nonce_field( 'sd_business_hours_nonce', 'sd_business_hours_nonce' );

        $business_hours = get_post_meta( $post->ID, '_sd_business_hours', true );
        if ( ! is_array( $business_hours ) ) {
            $business_hours = array();
        }

        $days = array(
            'monday'    => __( 'Montag', 'spezialist-directory' ),
            'tuesday'   => __( 'Dienstag', 'spezialist-directory' ),
            'wednesday' => __( 'Mittwoch', 'spezialist-directory' ),
            'thursday'  => __( 'Donnerstag', 'spezialist-directory' ),
            'friday'    => __( 'Freitag', 'spezialist-directory' ),
            'saturday'  => __( 'Samstag', 'spezialist-directory' ),
            'sunday'    => __( 'Sonntag', 'spezialist-directory' ),
        );
        ?>
        <div class="sd-meta-box-fields">
            <p class="description"><?php _e( 'Geben Sie die Öffnungszeiten für jeden Tag ein. Lassen Sie Felder leer für geschlossene Tage.', 'spezialist-directory' ); ?></p>
            <table class="widefat" style="margin-top: 10px;">
                <thead>
                    <tr>
                        <th style="width: 120px;"><?php _e( 'Tag', 'spezialist-directory' ); ?></th>
                        <th style="width: 80px;"><?php _e( 'Geöffnet', 'spezialist-directory' ); ?></th>
                        <th><?php _e( 'Von', 'spezialist-directory' ); ?></th>
                        <th><?php _e( 'Bis', 'spezialist-directory' ); ?></th>
                        <th><?php _e( 'Pause von', 'spezialist-directory' ); ?></th>
                        <th><?php _e( 'Pause bis', 'spezialist-directory' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $days as $day_key => $day_label ) :
                        $day_data = isset( $business_hours[ $day_key ] ) ? $business_hours[ $day_key ] : array();
                        $is_open = isset( $day_data['open'] ) && $day_data['open'];
                        $open_from = isset( $day_data['from'] ) ? $day_data['from'] : '';
                        $open_to = isset( $day_data['to'] ) ? $day_data['to'] : '';
                        $break_from = isset( $day_data['break_from'] ) ? $day_data['break_from'] : '';
                        $break_to = isset( $day_data['break_to'] ) ? $day_data['break_to'] : '';
                    ?>
                        <tr>
                            <td><strong><?php echo esc_html( $day_label ); ?></strong></td>
                            <td>
                                <input type="checkbox"
                                       name="sd_business_hours[<?php echo esc_attr( $day_key ); ?>][open]"
                                       value="1"
                                       <?php checked( $is_open ); ?>
                                       class="sd-day-toggle">
                            </td>
                            <td>
                                <input type="time"
                                       name="sd_business_hours[<?php echo esc_attr( $day_key ); ?>][from]"
                                       value="<?php echo esc_attr( $open_from ); ?>"
                                       style="width: 100px;">
                            </td>
                            <td>
                                <input type="time"
                                       name="sd_business_hours[<?php echo esc_attr( $day_key ); ?>][to]"
                                       value="<?php echo esc_attr( $open_to ); ?>"
                                       style="width: 100px;">
                            </td>
                            <td>
                                <input type="time"
                                       name="sd_business_hours[<?php echo esc_attr( $day_key ); ?>][break_from]"
                                       value="<?php echo esc_attr( $break_from ); ?>"
                                       style="width: 100px;"
                                       placeholder="<?php esc_attr_e( 'Optional', 'spezialist-directory' ); ?>">
                            </td>
                            <td>
                                <input type="time"
                                       name="sd_business_hours[<?php echo esc_attr( $day_key ); ?>][break_to]"
                                       value="<?php echo esc_attr( $break_to ); ?>"
                                       style="width: 100px;"
                                       placeholder="<?php esc_attr_e( 'Optional', 'spezialist-directory' ); ?>">
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Render Services Meta Box
     */
    public function render_services_meta_box( $post ) {
        wp_nonce_field( 'sd_services_nonce', 'sd_services_nonce' );

        $services = get_post_meta( $post->ID, '_sd_services', true );
        if ( ! is_array( $services ) ) {
            $services = array();
        }
        ?>
        <div class="sd-meta-box-fields">
            <p class="description"><?php _e( 'Fügen Sie die angebotenen Dienstleistungen oder Produkte hinzu.', 'spezialist-directory' ); ?></p>

            <div id="sd-admin-services-list" style="margin-top: 10px;">
                <?php foreach ( $services as $index => $service ) : ?>
                    <div class="sd-admin-service-item" style="display: flex; gap: 8px; margin-bottom: 8px;">
                        <input type="text" name="sd_services[]" value="<?php echo esc_attr( $service ); ?>" class="widefat" style="flex: 1;">
                        <button type="button" class="button sd-remove-service-admin" style="color: #dc2626;">&times;</button>
                    </div>
                <?php endforeach; ?>
            </div>

            <div style="display: flex; gap: 8px; margin-top: 10px;">
                <input type="text" id="sd-admin-new-service" class="widefat" style="flex: 1;" placeholder="<?php esc_attr_e( 'Neue Leistung hinzufügen...', 'spezialist-directory' ); ?>">
                <button type="button" class="button button-secondary" id="sd-admin-add-service"><?php _e( 'Hinzufügen', 'spezialist-directory' ); ?></button>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Add new service
            $('#sd-admin-add-service').on('click', function() {
                var $input = $('#sd-admin-new-service');
                var value = $input.val().trim();
                if (value) {
                    var $item = $('<div class="sd-admin-service-item" style="display: flex; gap: 8px; margin-bottom: 8px;">' +
                        '<input type="text" name="sd_services[]" value="' + value.replace(/"/g, '&quot;') + '" class="widefat" style="flex: 1;">' +
                        '<button type="button" class="button sd-remove-service-admin" style="color: #dc2626;">&times;</button>' +
                        '</div>');
                    $('#sd-admin-services-list').append($item);
                    $input.val('').focus();
                }
            });

            // Add on Enter key
            $('#sd-admin-new-service').on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    $('#sd-admin-add-service').click();
                }
            });

            // Remove service
            $(document).on('click', '.sd-remove-service-admin', function() {
                $(this).closest('.sd-admin-service-item').remove();
            });
        });
        </script>
        <?php
    }

    /**
     * Render Social Media Meta Box
     */
    public function render_social_media_meta_box( $post ) {
        wp_nonce_field( 'sd_social_media_nonce', 'sd_social_media_nonce' );

        $facebook = get_post_meta( $post->ID, '_sd_facebook', true );
        $twitter = get_post_meta( $post->ID, '_sd_twitter', true );
        $instagram = get_post_meta( $post->ID, '_sd_instagram', true );
        $linkedin = get_post_meta( $post->ID, '_sd_linkedin', true );
        $youtube = get_post_meta( $post->ID, '_sd_youtube', true );
        $xing = get_post_meta( $post->ID, '_sd_xing', true );
        ?>
        <div class="sd-meta-box-fields">
            <p>
                <label for="sd_facebook"><strong><?php _e( 'Facebook:', 'spezialist-directory' ); ?></strong></label><br>
                <input type="url" id="sd_facebook" name="sd_facebook" value="<?php echo esc_url( $facebook ); ?>" class="widefat" placeholder="https://facebook.com/username">
            </p>
            <p>
                <label for="sd_twitter"><strong><?php _e( 'X (ehemals Twitter):', 'spezialist-directory' ); ?></strong></label><br>
                <input type="url" id="sd_twitter" name="sd_twitter" value="<?php echo esc_url( $twitter ); ?>" class="widefat" placeholder="https://x.com/username">
            </p>
            <p>
                <label for="sd_instagram"><strong><?php _e( 'Instagram:', 'spezialist-directory' ); ?></strong></label><br>
                <input type="url" id="sd_instagram" name="sd_instagram" value="<?php echo esc_url( $instagram ); ?>" class="widefat" placeholder="https://instagram.com/username">
            </p>
            <p>
                <label for="sd_linkedin"><strong><?php _e( 'LinkedIn:', 'spezialist-directory' ); ?></strong></label><br>
                <input type="url" id="sd_linkedin" name="sd_linkedin" value="<?php echo esc_url( $linkedin ); ?>" class="widefat" placeholder="https://linkedin.com/in/username">
            </p>
            <p>
                <label for="sd_youtube"><strong><?php _e( 'YouTube:', 'spezialist-directory' ); ?></strong></label><br>
                <input type="url" id="sd_youtube" name="sd_youtube" value="<?php echo esc_url( $youtube ); ?>" class="widefat" placeholder="https://youtube.com/@username">
            </p>
            <p>
                <label for="sd_xing"><strong><?php _e( 'XING:', 'spezialist-directory' ); ?></strong></label><br>
                <input type="url" id="sd_xing" name="sd_xing" value="<?php echo esc_url( $xing ); ?>" class="widefat" placeholder="https://xing.com/profile/username">
            </p>
        </div>
        <?php
    }

    /**
     * Render Premium Status Meta Box
     */
    public function render_premium_status_meta_box( $post ) {
        wp_nonce_field( 'sd_premium_status_nonce', 'sd_premium_status_nonce' );

        $is_premium = get_post_meta( $post->ID, '_sd_is_premium', true );
        $premium_until = get_post_meta( $post->ID, '_sd_premium_until', true );
        $stripe_subscription_id = get_post_meta( $post->ID, '_sd_stripe_subscription_id', true );
        ?>
        <div class="sd-meta-box-fields">
            <p>
                <label>
                    <input type="checkbox" name="sd_is_premium" value="1" <?php checked( $is_premium, '1' ); ?>>
                    <strong><?php _e( 'Premium Listing', 'spezialist-directory' ); ?></strong>
                </label>
            </p>
            <?php if ( $is_premium ) : ?>
                <p>
                    <strong><?php _e( 'Premium bis:', 'spezialist-directory' ); ?></strong><br>
                    <?php echo $premium_until ? esc_html( date_i18n( get_option( 'date_format' ), strtotime( $premium_until ) ) ) : __( 'Unbegrenzt', 'spezialist-directory' ); ?>
                </p>
                <?php if ( $stripe_subscription_id ) : ?>
                    <p>
                        <strong><?php _e( 'Stripe Abo-ID:', 'spezialist-directory' ); ?></strong><br>
                        <code><?php echo esc_html( $stripe_subscription_id ); ?></code>
                    </p>
                <?php endif; ?>
            <?php endif; ?>
            <p class="description">
                <?php _e( 'Premium Listings werden hervorgehoben und oben in der Liste angezeigt.', 'spezialist-directory' ); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Render Verification Status Meta Box (Admin only)
     */
    public function render_verification_status_meta_box( $post ) {
        wp_nonce_field( 'sd_verification_status_nonce', 'sd_verification_status_nonce' );

        $is_verified = get_post_meta( $post->ID, '_sd_verified_listing', true );
        ?>
        <div class="sd-meta-box-fields">
            <p>
                <label>
                    <input type="checkbox" name="sd_verified_listing" value="1" <?php checked( $is_verified, '1' ); ?>>
                    <strong><?php _e( 'Verifizierter Eintrag', 'spezialist-directory' ); ?></strong>
                </label>
            </p>
            <p class="description">
                <?php _e( 'Der Inhaber dieses Eintrags wurde verifiziert.', 'spezialist-directory' ); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Render Claim Information Meta Box
     */
    public function render_claim_info_meta_box( $post ) {
        $is_claimed = get_post_meta( $post->ID, '_sd_is_claimed', true );
        $claimed_by = get_post_meta( $post->ID, '_sd_claimed_by', true );
        $claimed_date = get_post_meta( $post->ID, '_sd_claimed_date', true );
        ?>
        <div class="sd-meta-box-fields">
            <?php if ( $is_claimed ) : ?>
                <p>
                    <strong><?php _e( 'Status:', 'spezialist-directory' ); ?></strong><br>
                    <span style="color: #059669;">✓ <?php _e( 'Beansprucht', 'spezialist-directory' ); ?></span>
                </p>
                <?php if ( $claimed_by ) :
                    $user = get_user_by( 'id', $claimed_by );
                    if ( $user ) :
                    ?>
                        <p>
                            <strong><?php _e( 'Beansprucht von:', 'spezialist-directory' ); ?></strong><br>
                            <a href="<?php echo esc_url( admin_url( 'user-edit.php?user_id=' . $claimed_by ) ); ?>">
                                <?php echo esc_html( $user->display_name ); ?>
                            </a>
                        </p>
                    <?php endif; ?>
                <?php endif; ?>
                <?php if ( $claimed_date ) : ?>
                    <p>
                        <strong><?php _e( 'Beansprucht am:', 'spezialist-directory' ); ?></strong><br>
                        <?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $claimed_date ) ) ); ?>
                    </p>
                <?php endif; ?>
            <?php else : ?>
                <p>
                    <strong><?php _e( 'Status:', 'spezialist-directory' ); ?></strong><br>
                    <?php _e( 'Nicht beansprucht', 'spezialist-directory' ); ?>
                </p>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Save meta boxes
     */
    public function save_meta_boxes( $post_id, $post ) {
        // Check if this is an autosave
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Check post type
        if ( 'spezialist' !== $post->post_type ) {
            return;
        }

        // Check user permissions
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Save Contact Information
        if ( isset( $_POST['sd_contact_info_nonce'] ) && wp_verify_nonce( $_POST['sd_contact_info_nonce'], 'sd_contact_info_nonce' ) ) {
            $this->save_contact_info( $post_id );
        }

        // Synchronize neighborhood from Location-Term
        $this->sync_neighborhood_from_location( $post_id );

        // Save Business Hours
        if ( isset( $_POST['sd_business_hours_nonce'] ) && wp_verify_nonce( $_POST['sd_business_hours_nonce'], 'sd_business_hours_nonce' ) ) {
            $this->save_business_hours( $post_id );
        }

        // Save Services
        if ( isset( $_POST['sd_services_nonce'] ) && wp_verify_nonce( $_POST['sd_services_nonce'], 'sd_services_nonce' ) ) {
            $this->save_services( $post_id );
        }

        // Save Social Media
        if ( isset( $_POST['sd_social_media_nonce'] ) && wp_verify_nonce( $_POST['sd_social_media_nonce'], 'sd_social_media_nonce' ) ) {
            $this->save_social_media( $post_id );
        }

        // Save Premium Status (only admins)
        if ( current_user_can( 'manage_options' ) && isset( $_POST['sd_premium_status_nonce'] ) && wp_verify_nonce( $_POST['sd_premium_status_nonce'], 'sd_premium_status_nonce' ) ) {
            $this->save_premium_status( $post_id );
        }

        // Save Verification Status (only admins)
        if ( current_user_can( 'manage_options' ) && isset( $_POST['sd_verification_status_nonce'] ) && wp_verify_nonce( $_POST['sd_verification_status_nonce'], 'sd_verification_status_nonce' ) ) {
            $this->save_verification_status( $post_id );
        }
    }

    /**
     * Save contact information
     */
    private function save_contact_info( $post_id ) {
        $fields = array( 'phone', 'email', 'website', 'address', 'zip', 'city', 'latitude', 'longitude', 'place_id' );

        foreach ( $fields as $field ) {
            $value = isset( $_POST[ 'sd_' . $field ] ) ? sanitize_text_field( $_POST[ 'sd_' . $field ] ) : '';

            // Special sanitization for email and URL
            if ( 'email' === $field ) {
                $value = sanitize_email( $value );
            } elseif ( 'website' === $field ) {
                $value = esc_url_raw( $value );
            }

            update_post_meta( $post_id, '_sd_' . $field, $value );
        }
    }

    /**
     * Save business hours
     */
    private function save_business_hours( $post_id ) {
        $business_hours = array();
        $days = array( 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' );

        if ( isset( $_POST['sd_business_hours'] ) && is_array( $_POST['sd_business_hours'] ) ) {
            foreach ( $days as $day ) {
                if ( isset( $_POST['sd_business_hours'][ $day ] ) ) {
                    $day_data = $_POST['sd_business_hours'][ $day ];
                    $business_hours[ $day ] = array(
                        'open'       => isset( $day_data['open'] ) ? true : false,
                        'from'       => isset( $day_data['from'] ) ? sanitize_text_field( $day_data['from'] ) : '',
                        'to'         => isset( $day_data['to'] ) ? sanitize_text_field( $day_data['to'] ) : '',
                        'break_from' => isset( $day_data['break_from'] ) ? sanitize_text_field( $day_data['break_from'] ) : '',
                        'break_to'   => isset( $day_data['break_to'] ) ? sanitize_text_field( $day_data['break_to'] ) : '',
                    );
                }
            }
        }

        update_post_meta( $post_id, '_sd_business_hours', $business_hours );
    }

    /**
     * Save services
     */
    private function save_services( $post_id ) {
        $services = array();

        if ( isset( $_POST['sd_services'] ) && is_array( $_POST['sd_services'] ) ) {
            $services = array_filter( array_map( 'sanitize_text_field', $_POST['sd_services'] ) );
            $services = array_values( $services ); // Re-index array
        }

        update_post_meta( $post_id, '_sd_services', $services );
    }

    /**
     * Save social media
     */
    private function save_social_media( $post_id ) {
        $fields = array( 'facebook', 'twitter', 'instagram', 'linkedin', 'youtube', 'xing' );

        foreach ( $fields as $field ) {
            $value = isset( $_POST[ 'sd_' . $field ] ) ? esc_url_raw( $_POST[ 'sd_' . $field ] ) : '';
            update_post_meta( $post_id, '_sd_' . $field, $value );
        }
    }

    /**
     * Save premium status
     */
    private function save_premium_status( $post_id ) {
        $is_premium = isset( $_POST['sd_is_premium'] ) ? '1' : '0';
        update_post_meta( $post_id, '_sd_is_premium', $is_premium );
    }

    /**
     * Save verification status (admin only)
     */
    private function save_verification_status( $post_id ) {
        $is_verified = isset( $_POST['sd_verified_listing'] ) ? '1' : '0';
        update_post_meta( $post_id, '_sd_verified_listing', $is_verified );
    }

    /**
     * Synchronize _sd_neighborhood from spezialist_location taxonomy
     *
     * @param int $post_id
     */
    private function sync_neighborhood_from_location( $post_id ) {
        $locations = wp_get_object_terms( $post_id, 'spezialist_location', array( 'fields' => 'all' ) );

        if ( ! empty( $locations ) && ! is_wp_error( $locations ) ) {
            $first_location = $locations[0];
            update_post_meta( $post_id, '_sd_neighborhood', $first_location->name );
        }
    }
}
