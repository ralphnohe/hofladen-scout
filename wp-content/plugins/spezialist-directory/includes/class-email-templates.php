<?php
/**
 * Zentrale Email-Template-Klasse
 * Einheitliches Design für alle E-Mail-Benachrichtigungen
 *
 * @package Spezialist_Directory
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SD_Email_Templates {

    // Farbkonstanten für Header
    const COLOR_SUCCESS = 'linear-gradient(135deg, #059669 0%, #047857 100%)';  // Grün
    const COLOR_WARNING = 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)';  // Orange
    const COLOR_ERROR   = 'linear-gradient(135deg, #dc2626 0%, #b91c1c 100%)';  // Rot
    const COLOR_INFO    = 'linear-gradient(135deg, #2563EB 0%, #1d4ed8 100%)';  // Blau (default)
    const COLOR_NEUTRAL = 'linear-gradient(135deg, #6b7280 0%, #4b5563 100%)';  // Grau

    /**
     * Get base CSS styles for emails
     *
     * @return string
     */
    public static function get_base_styles() {
        return '
            body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 0; background-color: #f5f5f5; }
            .email-wrapper { max-width: 600px; margin: 0 auto; padding: 20px; }
            .email-container { background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); }
            .email-header { background: linear-gradient(135deg, #2563EB 0%, #1d4ed8 100%); color: #ffffff; padding: 32px 40px; text-align: center; }
            .email-header h1 { margin: 0; font-size: 24px; font-weight: 600; }
            .email-header .icon { font-size: 48px; margin-bottom: 16px; }
            .email-body { padding: 40px; }
            .email-body p { margin: 0 0 16px 0; color: #4b5563; }
            .email-body ul { margin: 0 0 16px 0; padding-left: 20px; color: #4b5563; }
            .email-body li { margin-bottom: 8px; }
            .email-body .greeting { font-size: 18px; color: #1f2937; margin-bottom: 24px; }
            .highlight-box { background: #f0f9ff; border-left: 4px solid #2563EB; padding: 16px 20px; margin: 24px 0; border-radius: 0 8px 8px 0; }
            .highlight-box.success { background: #ecfdf5; border-color: #059669; }
            .highlight-box.warning { background: #fffbeb; border-color: #f59e0b; }
            .highlight-box.error { background: #fef2f2; border-color: #dc2626; }
            .highlight-box strong { color: #1f2937; display: block; margin-bottom: 4px; }
            .btn { display: inline-block; padding: 14px 28px; background: linear-gradient(135deg, #2563EB 0%, #1d4ed8 100%); color: #ffffff !important; text-decoration: none; border-radius: 8px; font-weight: 600; margin: 8px 4px; }
            .btn-success { background: linear-gradient(135deg, #059669 0%, #047857 100%); }
            .btn-warning { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
            .btn-secondary { background: linear-gradient(135deg, #6b7280 0%, #4b5563 100%); }
            .email-footer { background: #f9fafb; padding: 24px 40px; text-align: center; border-top: 1px solid #e5e7eb; }
            .email-footer p { margin: 0; font-size: 13px; color: #6b7280; }
            .email-footer a { color: #2563EB; text-decoration: none; }
            .info-table { width: 100%; border-collapse: collapse; margin: 16px 0; }
            .info-table td { padding: 8px 0; border-bottom: 1px solid #e5e7eb; }
            .info-table td:first-child { font-weight: 600; color: #1f2937; width: 120px; }
        ';
    }

    /**
     * Get full email template
     *
     * @param string $title - Header title
     * @param string $body_content - Body HTML content
     * @param string $header_color - Header gradient color (use constants)
     * @param string $icon - Unicode icon for header
     * @return string
     */
    public static function get_template( $title, $body_content, $header_color = null, $icon = '&#9993;' ) {
        $site_name = get_bloginfo( 'name' );
        $site_url = home_url();
        $base_styles = self::get_base_styles();

        if ( null === $header_color ) {
            $header_color = self::COLOR_INFO;
        }

        $html = '<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . esc_html( $title ) . '</title>
    <style>' . $base_styles . '</style>
</head>
<body>
    <div class="email-wrapper">
        <div class="email-container">
            <div class="email-header" style="background: ' . $header_color . ';">
                <div class="icon">' . $icon . '</div>
                <h1>' . esc_html( $title ) . '</h1>
            </div>
            <div class="email-body">
                ' . $body_content . '
            </div>
            <div class="email-footer">
                <p>' . sprintf( __( 'Diese E-Mail wurde automatisch von %s versendet.', 'spezialist-directory' ), esc_html( $site_name ) ) . '</p>
                <p><a href="' . esc_url( $site_url ) . '">' . esc_html( $site_name ) . '</a></p>
            </div>
        </div>
    </div>
</body>
</html>';

        return $html;
    }

    /**
     * Get greeting paragraph
     *
     * @param string $name
     * @return string
     */
    public static function get_greeting( $name ) {
        return '<p class="greeting">' . sprintf( __( 'Hallo %s,', 'spezialist-directory' ), esc_html( $name ) ) . '</p>';
    }

    /**
     * Get highlight box
     *
     * @param string $label
     * @param string $content
     * @param string $type - 'default', 'success', 'warning', 'error'
     * @return string
     */
    public static function get_highlight_box( $label, $content, $type = 'default' ) {
        $class = 'highlight-box';
        if ( $type !== 'default' ) {
            $class .= ' ' . $type;
        }

        return '<div class="' . $class . '">
            <strong>' . esc_html( $label ) . '</strong>
            ' . esc_html( $content ) . '
        </div>';
    }

    /**
     * Get button
     *
     * @param string $url
     * @param string $text
     * @param string $style - 'primary', 'success', 'warning', 'secondary'
     * @return string
     */
    public static function get_button( $url, $text, $style = 'primary' ) {
        $class = 'btn';
        if ( $style !== 'primary' ) {
            $class .= ' btn-' . $style;
        }

        return '<a href="' . esc_url( $url ) . '" class="' . $class . '">' . esc_html( $text ) . '</a>';
    }

    /**
     * Get centered buttons container
     *
     * @param string $buttons_html - HTML of buttons
     * @return string
     */
    public static function get_button_container( $buttons_html ) {
        return '<p style="text-align: center; margin-top: 32px;">' . $buttons_html . '</p>';
    }

    /**
     * Get info table
     *
     * @param array $rows - Array of ['label' => 'value']
     * @return string
     */
    public static function get_info_table( $rows ) {
        $html = '<table class="info-table">';
        foreach ( $rows as $label => $value ) {
            $html .= '<tr>
                <td>' . esc_html( $label ) . '</td>
                <td>' . esc_html( $value ) . '</td>
            </tr>';
        }
        $html .= '</table>';
        return $html;
    }

    /**
     * Send HTML email
     *
     * @param string $to
     * @param string $subject
     * @param string $html_body
     * @return bool
     */
    public static function send( $to, $subject, $html_body ) {
        $headers = array( 'Content-Type: text/html; charset=UTF-8' );
        return wp_mail( $to, $subject, $html_body, $headers );
    }

    /**
     * Get admin email (with optional override)
     *
     * @return string
     */
    public static function get_admin_email() {
        $custom_email = get_option( 'sd_notification_admin_email', '' );
        if ( ! empty( $custom_email ) && is_email( $custom_email ) ) {
            return $custom_email;
        }
        return get_option( 'admin_email' );
    }

    /**
     * Check if notification is enabled
     *
     * @param string $option_key
     * @return bool
     */
    public static function is_enabled( $option_key ) {
        return (bool) get_option( $option_key, true );
    }

    // =========================================================================
    // Pre-built Email Templates
    // =========================================================================

    /**
     * Welcome Email Template
     *
     * @param string $user_name
     * @param string $dashboard_url
     * @return string
     */
    public static function template_welcome( $user_name, $dashboard_url ) {
        $body = self::get_greeting( $user_name );
        $body .= '<p>' . __( 'Herzlich willkommen bei Hofladen-Scout.de! Wir freuen uns, dass du dabei bist.', 'spezialist-directory' ) . '</p>';
        $body .= '<p>' . __( 'Mit deinem Konto kannst du:', 'spezialist-directory' ) . '</p>';
        $body .= '<ul>
            <li>' . __( 'Hofläden in deiner Nähe entdecken', 'spezialist-directory' ) . '</li>
            <li>' . __( 'Deinen eigenen Hofladen eintragen', 'spezialist-directory' ) . '</li>
            <li>' . __( 'Bewertungen schreiben und lesen', 'spezialist-directory' ) . '</li>
            <li>' . __( 'Favoriten speichern', 'spezialist-directory' ) . '</li>
        </ul>';
        $body .= self::get_button_container(
            self::get_button( $dashboard_url, __( 'Zum Dashboard', 'spezialist-directory' ) )
        );

        return self::get_template(
            __( 'Willkommen!', 'spezialist-directory' ),
            $body,
            self::COLOR_SUCCESS,
            '&#128075;' // Waving hand
        );
    }

    /**
     * Listing Submitted Confirmation Template
     *
     * @param string $user_name
     * @param string $listing_title
     * @param string $dashboard_url
     * @return string
     */
    public static function template_listing_submitted( $user_name, $listing_title, $dashboard_url ) {
        $body = self::get_greeting( $user_name );
        $body .= '<p>' . __( 'Vielen Dank für deinen Eintrag! Wir haben ihn erhalten und werden ihn schnellstmöglich prüfen.', 'spezialist-directory' ) . '</p>';
        $body .= self::get_highlight_box( __( 'Dein Eintrag:', 'spezialist-directory' ), $listing_title, 'default' );
        $body .= '<p>' . __( 'Du erhältst eine Benachrichtigung, sobald dein Eintrag freigeschaltet wurde.', 'spezialist-directory' ) . '</p>';
        $body .= self::get_button_container(
            self::get_button( $dashboard_url, __( 'Zum Dashboard', 'spezialist-directory' ) )
        );

        return self::get_template(
            __( 'Eintrag eingereicht', 'spezialist-directory' ),
            $body,
            self::COLOR_INFO,
            '&#128230;' // Package
        );
    }

    /**
     * Listing Approved Template
     *
     * @param string $user_name
     * @param string $listing_title
     * @param string $listing_url
     * @param string $dashboard_url
     * @return string
     */
    public static function template_listing_approved( $user_name, $listing_title, $listing_url, $dashboard_url ) {
        $body = self::get_greeting( $user_name );
        $body .= '<p>' . __( 'Gute Nachrichten! Dein Eintrag wurde geprüft und freigeschaltet.', 'spezialist-directory' ) . '</p>';
        $body .= self::get_highlight_box( __( 'Dein Eintrag:', 'spezialist-directory' ), $listing_title, 'success' );
        $body .= '<p>' . __( 'Dein Hofladen ist jetzt auf Hofladen-Scout.de sichtbar und kann von Besuchern gefunden werden.', 'spezialist-directory' ) . '</p>';
        $body .= self::get_button_container(
            self::get_button( $listing_url, __( 'Eintrag ansehen', 'spezialist-directory' ), 'success' ) .
            self::get_button( $dashboard_url, __( 'Zum Dashboard', 'spezialist-directory' ), 'secondary' )
        );

        return self::get_template(
            __( 'Eintrag freigeschaltet!', 'spezialist-directory' ),
            $body,
            self::COLOR_SUCCESS,
            '&#10003;' // Checkmark
        );
    }

    /**
     * Listing Rejected Template
     *
     * @param string $user_name
     * @param string $listing_title
     * @param string $reason
     * @param string $contact_url
     * @return string
     */
    public static function template_listing_rejected( $user_name, $listing_title, $reason = '', $contact_url = '' ) {
        $body = self::get_greeting( $user_name );
        $body .= '<p>' . __( 'Leider konnte dein Eintrag nicht freigeschaltet werden.', 'spezialist-directory' ) . '</p>';
        $body .= self::get_highlight_box( __( 'Betroffener Eintrag:', 'spezialist-directory' ), $listing_title, 'warning' );

        if ( ! empty( $reason ) ) {
            $body .= self::get_highlight_box( __( 'Begründung:', 'spezialist-directory' ), $reason, 'error' );
        }

        $body .= '<p>' . __( 'Wenn du Fragen hast oder den Eintrag anpassen möchtest, kontaktiere uns bitte.', 'spezialist-directory' ) . '</p>';

        if ( empty( $contact_url ) ) {
            $contact_url = home_url( '/kontakt/' );
        }
        $body .= self::get_button_container(
            self::get_button( $contact_url, __( 'Kontakt aufnehmen', 'spezialist-directory' ) )
        );

        return self::get_template(
            __( 'Eintrag nicht freigeschaltet', 'spezialist-directory' ),
            $body,
            self::COLOR_NEUTRAL,
            '&#10007;' // X mark
        );
    }

    /**
     * Rating Approved Template
     *
     * @param string $user_name
     * @param string $listing_title
     * @param int $rating
     * @param string $listing_url
     * @return string
     */
    public static function template_rating_approved( $user_name, $listing_title, $rating, $listing_url ) {
        $stars = str_repeat( '&#9733;', $rating ) . str_repeat( '&#9734;', 5 - $rating );

        $body = self::get_greeting( $user_name );
        $body .= '<p>' . __( 'Vielen Dank für deine Bewertung! Sie wurde veröffentlicht.', 'spezialist-directory' ) . '</p>';
        $body .= self::get_highlight_box( __( 'Bewerteter Hofladen:', 'spezialist-directory' ), $listing_title, 'success' );
        $body .= '<p style="font-size: 24px; text-align: center; color: #f59e0b;">' . $stars . '</p>';
        $body .= '<p>' . __( 'Deine Bewertung hilft anderen Nutzern, den richtigen Hofladen zu finden.', 'spezialist-directory' ) . '</p>';
        $body .= self::get_button_container(
            self::get_button( $listing_url, __( 'Bewertung ansehen', 'spezialist-directory' ), 'success' )
        );

        return self::get_template(
            __( 'Bewertung veröffentlicht', 'spezialist-directory' ),
            $body,
            self::COLOR_SUCCESS,
            '&#9733;' // Star
        );
    }

    /**
     * Rating Rejected Template
     *
     * @param string $user_name
     * @param string $listing_title
     * @param string $contact_url Optional contact URL for questions
     * @return string
     */
    public static function template_rating_rejected( $user_name, $listing_title, $contact_url = '' ) {
        $body = self::get_greeting( $user_name );
        $body .= '<p>' . __( 'Leider konnte deine Bewertung nicht veröffentlicht werden.', 'spezialist-directory' ) . '</p>';
        $body .= self::get_highlight_box( __( 'Betroffener Hofladen:', 'spezialist-directory' ), $listing_title, 'warning' );

        $body .= '<p>' . __( 'Mögliche Gründe:', 'spezialist-directory' ) . '</p>';
        $body .= '<ul>
            <li>' . __( 'Der Inhalt entspricht nicht unseren Richtlinien', 'spezialist-directory' ) . '</li>
            <li>' . __( 'Die Bewertung enthält unangemessene Inhalte', 'spezialist-directory' ) . '</li>
            <li>' . __( 'Die Bewertung konnte nicht verifiziert werden', 'spezialist-directory' ) . '</li>
        </ul>';

        $body .= '<p>' . __( 'Bei Fragen kannst du uns gerne kontaktieren.', 'spezialist-directory' ) . '</p>';

        if ( ! empty( $contact_url ) ) {
            $body .= self::get_button_container(
                self::get_button( $contact_url, __( 'Kontakt aufnehmen', 'spezialist-directory' ) )
            );
        }

        return self::get_template(
            __( 'Bewertung nicht veröffentlicht', 'spezialist-directory' ),
            $body,
            self::COLOR_NEUTRAL,
            '&#10007;' // X mark
        );
    }

    /**
     * Premium Reminder Template
     *
     * @param string $user_name
     * @param string $listing_title
     * @param int $days_left
     * @param string $dashboard_url
     * @return string
     */
    public static function template_premium_reminder( $user_name, $listing_title, $days_left, $dashboard_url ) {
        $body = self::get_greeting( $user_name );
        $body .= '<p>' . sprintf( __( 'Dein Premium-Listing läuft in %d Tagen ab.', 'spezialist-directory' ), $days_left ) . '</p>';
        $body .= self::get_highlight_box( __( 'Betroffener Eintrag:', 'spezialist-directory' ), $listing_title, 'warning' );
        $body .= '<p>' . __( 'Mit Premium-Status profitierst du von:', 'spezialist-directory' ) . '</p>';
        $body .= '<ul>
            <li>' . __( 'Hervorgehobene Platzierung in Suchergebnissen', 'spezialist-directory' ) . '</li>
            <li>' . __( 'Premium-Badge für mehr Vertrauen', 'spezialist-directory' ) . '</li>
            <li>' . __( 'Erweiterte Statistiken und Insights', 'spezialist-directory' ) . '</li>
        </ul>';
        $body .= '<p>' . __( 'Verlängere jetzt, um diese Vorteile nicht zu verlieren!', 'spezialist-directory' ) . '</p>';
        $body .= self::get_button_container(
            self::get_button( $dashboard_url, __( 'Premium verlängern', 'spezialist-directory' ), 'warning' )
        );

        return self::get_template(
            __( 'Premium läuft bald ab', 'spezialist-directory' ),
            $body,
            self::COLOR_WARNING,
            '&#9888;' // Warning sign
        );
    }

    /**
     * Premium Expired Template
     *
     * @param string $user_name
     * @param string $listing_title
     * @param string $dashboard_url
     * @return string
     */
    public static function template_premium_expired( $user_name, $listing_title, $dashboard_url ) {
        $body = self::get_greeting( $user_name );
        $body .= '<p>' . __( 'Dein Premium-Listing ist abgelaufen.', 'spezialist-directory' ) . '</p>';
        $body .= self::get_highlight_box( __( 'Betroffener Eintrag:', 'spezialist-directory' ), $listing_title, 'warning' );
        $body .= '<p>' . __( 'Dein Eintrag ist weiterhin auf Hofladen-Scout.de sichtbar, aber ohne die Premium-Vorteile:', 'spezialist-directory' ) . '</p>';
        $body .= '<ul>
            <li>' . __( 'Keine hervorgehobene Platzierung mehr', 'spezialist-directory' ) . '</li>
            <li>' . __( 'Kein Premium-Badge mehr', 'spezialist-directory' ) . '</li>
            <li>' . __( 'Keine erweiterten Statistiken mehr', 'spezialist-directory' ) . '</li>
        </ul>';
        $body .= '<p>' . __( 'Du kannst jederzeit ein neues Premium-Abo im Dashboard abschließen.', 'spezialist-directory' ) . '</p>';
        $body .= self::get_button_container(
            self::get_button( $dashboard_url, __( 'Zum Dashboard', 'spezialist-directory' ) )
        );

        return self::get_template(
            __( 'Premium abgelaufen', 'spezialist-directory' ),
            $body,
            self::COLOR_WARNING,
            '&#128338;' // Clock
        );
    }

    /**
     * Payment Failed Template
     *
     * @param string $user_name
     * @param string $listing_title
     * @param string $dashboard_url
     * @return string
     */
    public static function template_payment_failed( $user_name, $listing_title, $dashboard_url ) {
        $body = self::get_greeting( $user_name );
        $body .= '<p>' . __( 'Leider ist die Zahlung für dein Premium-Abo fehlgeschlagen.', 'spezialist-directory' ) . '</p>';
        $body .= self::get_highlight_box( __( 'Betroffener Eintrag:', 'spezialist-directory' ), $listing_title, 'error' );
        $body .= '<p>' . __( 'Bitte aktualisiere deine Zahlungsmethode, um dein Premium-Abo fortzuführen.', 'spezialist-directory' ) . '</p>';
        $body .= '<p>' . __( 'Mögliche Gründe für den Fehler:', 'spezialist-directory' ) . '</p>';
        $body .= '<ul>
            <li>' . __( 'Abgelaufene Kreditkarte', 'spezialist-directory' ) . '</li>
            <li>' . __( 'Unzureichendes Guthaben', 'spezialist-directory' ) . '</li>
            <li>' . __( 'Technisches Problem bei der Bank', 'spezialist-directory' ) . '</li>
        </ul>';
        $body .= self::get_button_container(
            self::get_button( $dashboard_url, __( 'Zahlungsmethode aktualisieren', 'spezialist-directory' ), 'warning' )
        );

        return self::get_template(
            __( 'Zahlung fehlgeschlagen', 'spezialist-directory' ),
            $body,
            self::COLOR_ERROR,
            '&#10060;' // Red X
        );
    }

    /**
     * Subscription Ended Template
     *
     * @param string $user_name
     * @param string $listing_title
     * @param string $dashboard_url
     * @return string
     */
    public static function template_subscription_ended( $user_name, $listing_title, $dashboard_url ) {
        $body = self::get_greeting( $user_name );
        $body .= '<p>' . __( 'Dein Premium-Abonnement wurde beendet.', 'spezialist-directory' ) . '</p>';
        $body .= self::get_highlight_box( __( 'Betroffener Eintrag:', 'spezialist-directory' ), $listing_title, 'warning' );
        $body .= '<p>' . __( 'Dein Eintrag bleibt weiterhin sichtbar, aber ohne Premium-Vorteile.', 'spezialist-directory' ) . '</p>';
        $body .= '<p>' . __( 'Du kannst jederzeit ein neues Abo abschließen, um wieder von den Premium-Funktionen zu profitieren.', 'spezialist-directory' ) . '</p>';
        $body .= self::get_button_container(
            self::get_button( $dashboard_url, __( 'Neues Abo abschließen', 'spezialist-directory' ) )
        );

        return self::get_template(
            __( 'Abonnement beendet', 'spezialist-directory' ),
            $body,
            self::COLOR_WARNING,
            '&#128276;' // Bell
        );
    }

    /**
     * Password Reset Template
     *
     * @param string $user_name
     * @param string $reset_url
     * @return string
     */
    public static function template_password_reset( $user_name, $reset_url ) {
        $body = self::get_greeting( $user_name );
        $body .= '<p>' . __( 'Du hast eine Anfrage zum Zurücksetzen deines Passworts gestellt.', 'spezialist-directory' ) . '</p>';
        $body .= '<p>' . __( 'Klicke auf den Button unten, um ein neues Passwort festzulegen:', 'spezialist-directory' ) . '</p>';
        $body .= self::get_button_container(
            self::get_button( $reset_url, __( 'Passwort zurücksetzen', 'spezialist-directory' ) )
        );
        $body .= '<p style="font-size: 13px; color: #6b7280;">' . __( 'Dieser Link ist 24 Stunden gültig. Wenn du diese Anfrage nicht gestellt hast, kannst du diese E-Mail ignorieren.', 'spezialist-directory' ) . '</p>';

        return self::get_template(
            __( 'Passwort zurücksetzen', 'spezialist-directory' ),
            $body,
            self::COLOR_INFO,
            '&#128274;' // Lock
        );
    }

    /**
     * Admin: New Listing Submitted Template
     *
     * @param string $listing_title
     * @param string $author_name
     * @param string $author_email
     * @param string $edit_url
     * @return string
     */
    public static function template_admin_new_listing( $listing_title, $author_name, $author_email, $edit_url ) {
        $body = '<p class="greeting">' . __( 'Hallo Admin,', 'spezialist-directory' ) . '</p>';
        $body .= '<p>' . __( 'Ein neuer Hofladen-Eintrag wurde eingereicht und wartet auf Freischaltung.', 'spezialist-directory' ) . '</p>';
        $body .= self::get_highlight_box( __( 'Neuer Eintrag:', 'spezialist-directory' ), $listing_title, 'default' );
        $body .= self::get_info_table( array(
            __( 'Eingereicht von', 'spezialist-directory' ) => $author_name,
            __( 'E-Mail', 'spezialist-directory' ) => $author_email,
        ) );
        $body .= self::get_button_container(
            self::get_button( $edit_url, __( 'Eintrag prüfen', 'spezialist-directory' ) )
        );

        return self::get_template(
            __( 'Neuer Hofladen-Eintrag', 'spezialist-directory' ),
            $body,
            self::COLOR_INFO,
            '&#128230;' // Package
        );
    }

    /**
     * Admin: New Rating Pending Template
     *
     * @param string $listing_title
     * @param string $author_name
     * @param int $rating
     * @param string $comment
     * @param string $moderation_url
     * @return string
     */
    public static function template_admin_new_rating( $listing_title, $author_name, $rating, $comment, $moderation_url ) {
        $stars = str_repeat( '&#9733;', $rating ) . str_repeat( '&#9734;', 5 - $rating );

        $body = '<p class="greeting">' . __( 'Hallo Admin,', 'spezialist-directory' ) . '</p>';
        $body .= '<p>' . __( 'Eine neue Bewertung wurde eingereicht und wartet auf Moderation.', 'spezialist-directory' ) . '</p>';
        $body .= self::get_highlight_box( __( 'Bewerteter Hofladen:', 'spezialist-directory' ), $listing_title, 'default' );
        $body .= '<p style="font-size: 24px; text-align: center; color: #f59e0b;">' . $stars . '</p>';
        $body .= self::get_info_table( array(
            __( 'Von', 'spezialist-directory' ) => $author_name,
        ) );
        if ( ! empty( $comment ) ) {
            $body .= '<p><strong>' . __( 'Kommentar:', 'spezialist-directory' ) . '</strong></p>';
            $body .= '<p style="background: #f5f5f5; padding: 12px; border-radius: 8px; font-style: italic;">' . esc_html( $comment ) . '</p>';
        }
        $body .= self::get_button_container(
            self::get_button( $moderation_url, __( 'Bewertung moderieren', 'spezialist-directory' ) )
        );

        return self::get_template(
            __( 'Neue Bewertung', 'spezialist-directory' ),
            $body,
            self::COLOR_INFO,
            '&#9733;' // Star
        );
    }

    /**
     * Lead Confirmation to Customer Template
     *
     * @param string $customer_name
     * @param string $listing_title
     * @return string
     */
    public static function template_lead_confirmation( $customer_name, $listing_title ) {
        $body = self::get_greeting( $customer_name );
        $body .= '<p>' . __( 'Vielen Dank für deine Anfrage!', 'spezialist-directory' ) . '</p>';
        $body .= self::get_highlight_box( __( 'Deine Anfrage an:', 'spezialist-directory' ), $listing_title, 'success' );
        $body .= '<p>' . __( 'Der Hofladen wurde über deine Anfrage informiert und wird sich in Kürze bei dir melden.', 'spezialist-directory' ) . '</p>';
        $body .= '<p>' . __( 'Wir wünschen dir viel Erfolg bei deiner Suche nach frischen, regionalen Produkten!', 'spezialist-directory' ) . '</p>';

        return self::get_template(
            __( 'Anfrage gesendet', 'spezialist-directory' ),
            $body,
            self::COLOR_SUCCESS,
            '&#10003;' // Checkmark
        );
    }
}
