<?php
/**
 * Template: Submission Form
 *
 * Frontend form for submitting new specialist listings
 *
 * @package Spezialist_Directory
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$categories = SD_User_Submissions::get_categories();
$locations = SD_User_Submissions::get_locations();
?>

<div class="sd-submission-form-container">
    <div class="sd-submission-header">
        <h2><?php _e( 'Neuen Spezialist-Eintrag hinzufügen', 'spezialist-directory' ); ?></h2>
        <p class="sd-submission-description">
            <?php _e( 'Füll das Formular aus, um Deinen Spezialist-Eintrag einzureichen. Alle mit * markierten Felder sind Pflichtfelder.', 'spezialist-directory' ); ?>
        </p>
    </div>

    <div class="sd-notice sd-notice-info sd-submission-notice" style="display: none;"></div>

    <form id="sd-submission-form" class="sd-form" enctype="multipart/form-data">
        <?php wp_nonce_field( 'sd_submit_spezialist', 'sd_submission_nonce' ); ?>

        <!-- Basic Information -->
        <div class="sd-form-section">
            <h3><?php _e( 'Grundinformationen', 'spezialist-directory' ); ?></h3>

            <div class="sd-form-row">
                <div class="sd-form-group sd-form-group-full">
                    <label for="sd_title"><?php _e( 'Name / Firmenname', 'spezialist-directory' ); ?> <span class="sd-required">*</span></label>
                    <input
                        type="text"
                        id="sd_title"
                        name="title"
                        class="sd-input"
                        required
                        placeholder="<?php esc_attr_e( 'z.B. Max Mustermann oder Musterfirma GmbH', 'spezialist-directory' ); ?>"
                    >
                </div>
            </div>

            <div class="sd-form-row">
                <div class="sd-form-group sd-form-group-full">
                    <label for="sd_description"><?php _e( 'Beschreibung', 'spezialist-directory' ); ?> <span class="sd-required">*</span></label>
                    <?php
                    wp_editor( '', 'sd_description', array(
                        'textarea_name' => 'description',
                        'textarea_rows' => 8,
                        'media_buttons' => false,
                        'teeny'         => true,
                        'quicktags'     => false,
                    ) );
                    ?>
                    <p class="sd-field-description"><?php _e( 'Beschreibe Deine Dienstleistungen, Expertise und was Dich einzigartig macht.', 'spezialist-directory' ); ?></p>
                </div>
            </div>

            <div class="sd-form-row">
                <div class="sd-form-group sd-form-group-half">
                    <label for="sd_category"><?php _e( 'Kategorie', 'spezialist-directory' ); ?> <span class="sd-required">*</span></label>
                    <select id="sd_category" name="category[]" class="sd-select" multiple required>
                        <?php foreach ( $categories as $category ) : ?>
                            <option value="<?php echo esc_attr( $category->term_id ); ?>">
                                <?php echo esc_html( $category->name ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="sd-form-group sd-form-group-half">
                    <label for="sd_location"><?php _e( 'Stadtteil', 'spezialist-directory' ); ?> <span class="sd-required">*</span></label>
                    <select id="sd_location" name="location[]" class="sd-select" multiple required>
                        <?php foreach ( $locations as $location ) : ?>
                            <option value="<?php echo esc_attr( $location->term_id ); ?>">
                                <?php echo esc_html( $location->name ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="sd-form-row">
                <div class="sd-form-group sd-form-group-full">
                    <label for="sd_image"><?php _e( 'Profilbild / Logo', 'spezialist-directory' ); ?></label>
                    <input
                        type="file"
                        id="sd_image"
                        name="image"
                        class="sd-input"
                        accept="image/*"
                    >
                    <p class="sd-field-description"><?php _e( 'Lade ein Profilbild oder Firmenlogo hoch (max. 5 MB).', 'spezialist-directory' ); ?></p>
                </div>
            </div>

        </div>

        <!-- Contact Information -->
        <div class="sd-form-section">
            <h3><?php _e( 'Kontaktinformationen', 'spezialist-directory' ); ?></h3>
            <p class="sd-field-description"><?php _e( 'Wie können potentielle Kunden Dich erreichen?', 'spezialist-directory' ); ?></p>

            <div class="sd-form-row">
                <div class="sd-form-group sd-form-group-half">
                    <label for="sd_phone"><?php _e( 'Telefon', 'spezialist-directory' ); ?> <span class="sd-required">*</span></label>
                    <input
                        type="tel"
                        id="sd_phone"
                        name="phone"
                        class="sd-input"
                        required
                        placeholder="+49 123 456789"
                    >
                </div>

                <div class="sd-form-group sd-form-group-half">
                    <label for="sd_email"><?php _e( 'E-Mail', 'spezialist-directory' ); ?> <span class="sd-required">*</span></label>
                    <input
                        type="email"
                        id="sd_email"
                        name="email"
                        class="sd-input"
                        required
                        placeholder="mail@example.com"
                        value="<?php echo is_user_logged_in() ? esc_attr( wp_get_current_user()->user_email ) : ''; ?>"
                    >
                </div>
            </div>

            <div class="sd-form-row">
                <div class="sd-form-group sd-form-group-full">
                    <label for="sd_website"><?php _e( 'Website', 'spezialist-directory' ); ?> <span class="sd-required">*</span></label>
                    <input
                        type="url"
                        id="sd_website"
                        name="website"
                        class="sd-input"
                        required
                        placeholder="https://example.com"
                    >
                </div>
            </div>

            <div class="sd-form-row">
                <div class="sd-form-group sd-form-group-full">
                    <label for="sd_address"><?php _e( 'Straße & Hausnummer', 'spezialist-directory' ); ?> <span class="sd-required">*</span></label>
                    <input
                        type="text"
                        id="sd_address"
                        name="address"
                        class="sd-input"
                        required
                        placeholder="Musterstraße 123"
                    >
                </div>
            </div>

            <div class="sd-form-row">
                <div class="sd-form-group sd-form-group-third">
                    <label for="sd_zip"><?php _e( 'Postleitzahl', 'spezialist-directory' ); ?> <span class="sd-required">*</span></label>
                    <input
                        type="text"
                        id="sd_zip"
                        name="zip"
                        class="sd-input"
                        required
                        placeholder="12345"
                    >
                </div>

                <div class="sd-form-group sd-form-group-two-thirds">
                    <label for="sd_city"><?php _e( 'Stadt', 'spezialist-directory' ); ?> <span class="sd-required">*</span></label>
                    <input
                        type="text"
                        id="sd_city"
                        name="city"
                        class="sd-input"
                        required
                        value="Nürnberg"
                        placeholder="Nürnberg"
                    >
                </div>
            </div>
        </div>

        <!-- Gallery (Premium) -->
        <div class="sd-form-section sd-form-section-collapsible">
            <h3>
                <?php _e( 'Bildergalerie (Optional)', 'spezialist-directory' ); ?>
                <span class="sd-premium-badge-inline">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" fill="currentColor"/>
                    </svg>
                    Premium
                </span>
            </h3>
            <p class="sd-field-description"><?php _e( 'Lade bis zu 10 Bilder für Deine Galerie hoch (max. 5 MB pro Bild).', 'spezialist-directory' ); ?></p>

            <div class="sd-form-row">
                <div class="sd-form-group sd-form-group-full sd-gallery-field">
                    <input
                        type="file"
                        id="sd_gallery"
                        name="gallery[]"
                        class="sd-input"
                        accept="image/*"
                        multiple
                    >
                    <div id="sd-gallery-preview" class="sd-gallery-preview"></div>
                </div>
            </div>
            <p class="sd-premium-note"><?php _e( 'Die Bildergalerie ist nur für Premium-Einträge sichtbar. Du kannst die Bilder jetzt hochladen.', 'spezialist-directory' ); ?></p>
        </div>

        <!-- Video Upload (Premium) -->
        <div class="sd-form-section sd-form-section-collapsible">
            <h3>
                <?php _e( 'Video hochladen (Optional)', 'spezialist-directory' ); ?>
                <span class="sd-premium-badge-inline">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" fill="currentColor"/>
                    </svg>
                    Premium
                </span>
            </h3>
            <p class="sd-field-description"><?php _e( 'Lade ein Video hoch, um Dein Unternehmen vorzustellen (max. 10 MB, MP4/WebM/MOV).', 'spezialist-directory' ); ?></p>

            <div class="sd-form-row">
                <div class="sd-form-group sd-form-group-full sd-video-field">
                    <input
                        type="file"
                        id="sd_video"
                        name="video"
                        class="sd-input"
                        accept="video/mp4,video/webm,video/quicktime,.mp4,.webm,.mov"
                    >
                    <div id="sd-video-preview" class="sd-video-preview"></div>
                </div>
            </div>
            <p class="sd-premium-note"><?php _e( 'Das Video ist nur für Premium-Einträge sichtbar. Du kannst es jetzt hochladen.', 'spezialist-directory' ); ?></p>
        </div>

        <!-- Services/Offerings (Optional) -->
        <div class="sd-form-section sd-form-section-collapsible">
            <h3>
                <?php _e( 'Angebotene Leistungen (Optional)', 'spezialist-directory' ); ?>
                <span class="sd-premium-badge-inline">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" fill="currentColor"/>
                    </svg>
                    Premium
                </span>
            </h3>
            <p class="sd-field-description"><?php _e( 'Liste Deine angebotenen Dienstleistungen oder Produkte auf.', 'spezialist-directory' ); ?></p>

            <div class="sd-services-form" id="sd-services-container">
                <div class="sd-services-list">
                    <!-- Services will be added here dynamically -->
                </div>
                <div class="sd-service-add-row">
                    <input type="text" id="sd-new-service" class="sd-input" placeholder="<?php esc_attr_e( 'z.B. Beratung, Webdesign, Reparatur...', 'spezialist-directory' ); ?>">
                    <button type="button" class="sd-button sd-button-secondary sd-add-service">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M19 13H13V19H11V13H5V11H11V5H13V11H19V13Z" fill="currentColor"/>
                        </svg>
                        <?php _e( 'Hinzufügen', 'spezialist-directory' ); ?>
                    </button>
                </div>
            </div>
            <p class="sd-field-description sd-field-hint"><?php _e( 'Gib eine Leistung ein und klicke auf "Hinzufügen". Du kannst beliebig viele Leistungen hinzufügen.', 'spezialist-directory' ); ?></p>
            <p class="sd-premium-note"><?php _e( 'Angebotene Leistungen sind nur für Premium-Einträge sichtbar. Du kannst die Daten jetzt eingeben.', 'spezialist-directory' ); ?></p>
        </div>

        <!-- Business Hours (Optional) -->
        <div class="sd-form-section sd-form-section-collapsible">
            <h3>
                <?php _e( 'Öffnungszeiten (Optional)', 'spezialist-directory' ); ?>
                <span class="sd-premium-badge-inline">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" fill="currentColor"/>
                    </svg>
                    Premium
                </span>
            </h3>
            <p class="sd-field-description"><?php _e( 'Gib Deine Öffnungszeiten an, damit Kunden wissen, wann Du erreichbar bist.', 'spezialist-directory' ); ?></p>

            <div class="sd-business-hours-form">
                <?php
                $days = array(
                    'monday'    => __( 'Montag', 'spezialist-directory' ),
                    'tuesday'   => __( 'Dienstag', 'spezialist-directory' ),
                    'wednesday' => __( 'Mittwoch', 'spezialist-directory' ),
                    'thursday'  => __( 'Donnerstag', 'spezialist-directory' ),
                    'friday'    => __( 'Freitag', 'spezialist-directory' ),
                    'saturday'  => __( 'Samstag', 'spezialist-directory' ),
                    'sunday'    => __( 'Sonntag', 'spezialist-directory' ),
                );
                foreach ( $days as $day_key => $day_name ) :
                ?>
                    <div class="sd-hours-row">
                        <div class="sd-hours-day">
                            <label class="sd-hours-checkbox">
                                <input type="checkbox" name="business_hours[<?php echo esc_attr( $day_key ); ?>][open]" value="1">
                                <span class="sd-hours-day-name"><?php echo esc_html( $day_name ); ?></span>
                            </label>
                        </div>
                        <div class="sd-hours-times">
                            <div class="sd-hours-time-group">
                                <label class="sd-hours-open-label"><?php _e( 'Geöffnet:', 'spezialist-directory' ); ?></label>
                                <input type="time" name="business_hours[<?php echo esc_attr( $day_key ); ?>][from]" class="sd-input sd-input-time" placeholder="09:00">
                                <span class="sd-hours-separator">-</span>
                                <input type="time" name="business_hours[<?php echo esc_attr( $day_key ); ?>][to]" class="sd-input sd-input-time" placeholder="18:00">
                            </div>
                            <div class="sd-hours-break">
                                <label class="sd-hours-break-label"><?php _e( 'Pause:', 'spezialist-directory' ); ?></label>
                                <input type="time" name="business_hours[<?php echo esc_attr( $day_key ); ?>][break_from]" class="sd-input sd-input-time" placeholder="12:00">
                                <span class="sd-hours-separator">-</span>
                                <input type="time" name="business_hours[<?php echo esc_attr( $day_key ); ?>][break_to]" class="sd-input sd-input-time" placeholder="13:00">
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <p class="sd-field-description sd-field-hint"><?php _e( 'Aktiviere einen Tag und gib die Öffnungszeiten ein. Die Pausenzeit ist optional.', 'spezialist-directory' ); ?></p>
            <p class="sd-premium-note"><?php _e( 'Öffnungszeiten sind nur für Premium-Einträge sichtbar. Du kannst die Daten jetzt eingeben.', 'spezialist-directory' ); ?></p>
        </div>

        <!-- Social Media (Optional) -->
        <div class="sd-form-section sd-form-section-collapsible">
            <h3>
                <?php _e( 'Social Media (Optional)', 'spezialist-directory' ); ?>
                <span class="sd-premium-badge-inline">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" fill="currentColor"/>
                    </svg>
                    Premium
                </span>
            </h3>

            <div class="sd-form-row">
                <div class="sd-form-group sd-form-group-half">
                    <label for="sd_facebook"><?php _e( 'Facebook', 'spezialist-directory' ); ?></label>
                    <div class="sd-input-group">
                        <span class="sd-input-addon">facebook.com/</span>
                        <input type="text" id="sd_facebook" name="facebook" class="sd-input sd-input-with-addon sd-social-input" placeholder="deinefirma" data-url-prefix="https://facebook.com/">
                    </div>
                </div>

                <div class="sd-form-group sd-form-group-half">
                    <label for="sd_twitter"><?php _e( 'X (ehemals Twitter)', 'spezialist-directory' ); ?></label>
                    <div class="sd-input-group">
                        <span class="sd-input-addon">x.com/</span>
                        <input type="text" id="sd_twitter" name="twitter" class="sd-input sd-input-with-addon sd-social-input" placeholder="deinprofil" data-url-prefix="https://x.com/">
                    </div>
                </div>
            </div>

            <div class="sd-form-row">
                <div class="sd-form-group sd-form-group-half">
                    <label for="sd_instagram"><?php _e( 'Instagram', 'spezialist-directory' ); ?></label>
                    <div class="sd-input-group">
                        <span class="sd-input-addon">instagram.com/</span>
                        <input type="text" id="sd_instagram" name="instagram" class="sd-input sd-input-with-addon sd-social-input" placeholder="deinprofil" data-url-prefix="https://instagram.com/">
                    </div>
                </div>

                <div class="sd-form-group sd-form-group-half">
                    <label for="sd_linkedin"><?php _e( 'LinkedIn', 'spezialist-directory' ); ?></label>
                    <div class="sd-input-group">
                        <span class="sd-input-addon">linkedin.com/in/</span>
                        <input type="text" id="sd_linkedin" name="linkedin" class="sd-input sd-input-with-addon sd-social-input" placeholder="vorname-nachname" data-url-prefix="https://linkedin.com/in/">
                    </div>
                </div>
            </div>

            <div class="sd-form-row">
                <div class="sd-form-group sd-form-group-half">
                    <label for="sd_youtube"><?php _e( 'YouTube', 'spezialist-directory' ); ?></label>
                    <div class="sd-input-group">
                        <span class="sd-input-addon">youtube.com/@</span>
                        <input type="text" id="sd_youtube" name="youtube" class="sd-input sd-input-with-addon sd-social-input" placeholder="deinkanal" data-url-prefix="https://youtube.com/@">
                    </div>
                </div>

                <div class="sd-form-group sd-form-group-half">
                    <label for="sd_xing"><?php _e( 'XING', 'spezialist-directory' ); ?></label>
                    <div class="sd-input-group">
                        <span class="sd-input-addon">xing.com/profile/</span>
                        <input type="text" id="sd_xing" name="xing" class="sd-input sd-input-with-addon sd-social-input" placeholder="Vorname_Nachname" data-url-prefix="https://xing.com/profile/">
                    </div>
                </div>
            </div>
            <p class="sd-premium-note"><?php _e( 'Social Media Links sind nur für Premium-Einträge sichtbar. Du kannst die Daten jetzt eingeben.', 'spezialist-directory' ); ?></p>
        </div>

        <!-- Premium Plan Selection (shown when premium fields are filled) -->
        <div id="sd-premium-plan-selection" class="sd-form-section sd-premium-plan-selection" style="display: none;">
            <h3>
                <?php _e( 'Premium Plan auswählen', 'spezialist-directory' ); ?>
                <span class="sd-premium-badge-inline">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z" fill="currentColor"/>
                    </svg>
                    Premium
                </span>
            </h3>
            <p class="sd-field-description"><?php _e( 'Du hast Premium-Funktionen ausgewählt. Bitte wähle Deinen Plan:', 'spezialist-directory' ); ?></p>

            <div class="sd-plan-options">
                <label class="sd-plan-option">
                    <input type="radio" name="premium_plan" value="monthly" checked>
                    <span class="sd-plan-card">
                        <span class="sd-plan-name"><?php _e( 'Monatlich', 'spezialist-directory' ); ?></span>
                        <span class="sd-plan-price">9€<span class="sd-plan-period">/Monat</span></span>
                        <span class="sd-plan-desc"><?php _e( 'Flexibel, monatlich kündbar', 'spezialist-directory' ); ?></span>
                    </span>
                </label>
                <label class="sd-plan-option">
                    <input type="radio" name="premium_plan" value="yearly">
                    <span class="sd-plan-card">
                        <span class="sd-plan-badge"><?php _e( 'Spare 26%', 'spezialist-directory' ); ?></span>
                        <span class="sd-plan-name"><?php _e( 'Jährlich', 'spezialist-directory' ); ?></span>
                        <span class="sd-plan-price">80€<span class="sd-plan-period">/Jahr</span></span>
                        <span class="sd-plan-desc"><?php _e( '2 Monate gratis', 'spezialist-directory' ); ?></span>
                    </span>
                </label>
            </div>

            <p class="sd-premium-plan-note">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z" fill="currentColor"/>
                </svg>
                <?php _e( 'Nach dem Einreichen wirst Du zur Zahlung weitergeleitet. Alle Preise zzgl. MwSt.', 'spezialist-directory' ); ?>
            </p>
        </div>

        <!-- Submit Button -->
        <div class="sd-form-footer">
            <button type="submit" class="sd-button sd-button-primary sd-button-large" id="sd-submit-button">
                <span class="sd-button-text"><?php _e( 'Eintrag einreichen', 'spezialist-directory' ); ?></span>
                <span class="sd-button-text-premium" style="display: none;"><?php _e( 'Einreichen & zur Zahlung', 'spezialist-directory' ); ?></span>
                <span class="sd-button-loading" style="display: none;">
                    <span class="sd-spinner"></span>
                    <?php _e( 'Wird eingereicht...', 'spezialist-directory' ); ?>
                </span>
            </button>

            <p class="sd-form-footer-note">
                <?php
                if ( Spezialist_Directory::get_option( 'require_approval', true ) ) {
                    _e( 'Dein Eintrag wird nach der Einreichung von einem Administrator überprüft.', 'spezialist-directory' );
                } else {
                    _e( 'Dein Eintrag wird sofort nach der Einreichung veröffentlicht.', 'spezialist-directory' );
                }
                ?>
            </p>
        </div>
    </form>
</div>

<!-- Checkout Loading Overlay -->
<div id="sd-checkout-overlay" class="sd-checkout-overlay" style="display: none;">
    <div class="sd-checkout-overlay-content">
        <div class="sd-checkout-spinner"></div>
        <p><?php _e( 'Weiterleitung zur Zahlung...', 'spezialist-directory' ); ?></p>
    </div>
</div>
