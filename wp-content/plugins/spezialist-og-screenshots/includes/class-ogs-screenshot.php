<?php
/**
 * OG Screenshots Screenshot Class
 *
 * Handles screenshot capture, processing, and storage
 *
 * @package Spezialist_OG_Screenshots
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * OGS_Screenshot Class
 */
class OGS_Screenshot {

    /**
     * Single instance
     *
     * @var OGS_Screenshot
     */
    protected static $_instance = null;

    /**
     * Screenshot API URL
     */
    const API_URL = 'https://shot.screenshotapi.net/screenshot';

    /**
     * Target image dimensions (16:9 for Twitter)
     */
    const TARGET_WIDTH = 1200;
    const TARGET_HEIGHT = 675;

    /**
     * Capture dimensions (higher for quality crop)
     */
    const CAPTURE_WIDTH = 1440;
    const CAPTURE_HEIGHT = 810;

    /**
     * Main Instance
     *
     * @return OGS_Screenshot
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
        // No hooks needed
    }

    /**
     * Take screenshot and save as OG image
     *
     * @param int    $id          Post ID or Term ID.
     * @param string $entity_type 'post' or 'term'.
     * @param string $url         The URL to screenshot.
     * @return array Result with status, message, and optional attachment_id.
     */
    public function take_screenshot( $id, $entity_type, $url ) {
        $api_key = OGS()->get_api_key();

        if ( empty( $api_key ) ) {
            return array(
                'status'  => 'error',
                'message' => 'API Key nicht konfiguriert',
            );
        }

        if ( ! OGS()->is_gd_available() ) {
            return array(
                'status'  => 'error',
                'message' => 'PHP GD Library nicht verfügbar',
            );
        }

        // Validate URL
        if ( empty( $url ) || ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
            return array(
                'status'  => 'error',
                'message' => 'Ungültige URL: ' . $url,
            );
        }

        // Build API URL with parameters
        $api_params = array(
            'token'             => $api_key,
            'url'               => $url,
            'width'             => self::CAPTURE_WIDTH,
            'height'            => self::CAPTURE_HEIGHT,
            'output'            => 'image',
            'file_type'         => 'png',
            'wait_for_event'    => 'load',
            'delay'             => 2000,
            'no_cookie_banners' => 'true',
            'block_ads'         => 'true',
        );

        $api_url = self::API_URL . '?' . http_build_query( $api_params );

        // Fetch screenshot from API
        $response = wp_remote_get(
            $api_url,
            array(
                'timeout'   => 60,
                'sslverify' => true,
            )
        );

        // Handle WP error
        if ( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();

            // Check for timeout
            if ( strpos( $error_message, 'cURL error 28' ) !== false ) {
                return array(
                    'status'  => 'error',
                    'message' => 'API Timeout - Seite zu langsam',
                );
            }

            return array(
                'status'  => 'error',
                'message' => 'API Fehler: ' . $error_message,
            );
        }

        // Check HTTP response code
        $response_code = wp_remote_retrieve_response_code( $response );
        if ( 200 !== $response_code ) {
            return array(
                'status'    => 'error',
                'message'   => 'API HTTP Fehler: ' . $response_code,
                'http_code' => $response_code,
            );
        }

        // Get image data
        $image_data = wp_remote_retrieve_body( $response );
        if ( empty( $image_data ) ) {
            return array(
                'status'  => 'error',
                'message' => 'Keine Bilddaten von API erhalten',
            );
        }

        // Check if response is actually an image (not JSON error)
        $content_type = wp_remote_retrieve_header( $response, 'content-type' );
        if ( strpos( $content_type, 'image/' ) === false ) {
            // Try to decode as JSON error
            $json_response = json_decode( $image_data, true );
            if ( $json_response && isset( $json_response['error'] ) ) {
                return array(
                    'status'  => 'error',
                    'message' => 'API Fehler: ' . $json_response['error'],
                );
            }
            return array(
                'status'  => 'error',
                'message' => 'Unerwartete API Antwort (kein Bild)',
            );
        }

        // Save to temp file
        $temp_file = sys_get_temp_dir() . '/ogs_' . $entity_type . '_' . $id . '_' . time() . '.png';
        $saved     = file_put_contents( $temp_file, $image_data );

        if ( false === $saved ) {
            return array(
                'status'  => 'error',
                'message' => 'Konnte Temp-Datei nicht speichern',
            );
        }

        // Convert and attach
        $result = $this->convert_and_attach( $id, $entity_type, $temp_file );

        // Cleanup temp file
        @unlink( $temp_file );

        return $result;
    }

    /**
     * Convert PNG to WebP, resize to target dimensions, and attach to entity
     *
     * @param int    $id          Post ID or Term ID.
     * @param string $entity_type 'post' or 'term'.
     * @param string $temp_png    Path to temporary PNG file.
     * @return array Result with status, message, and optional attachment_id.
     */
    private function convert_and_attach( $id, $entity_type, $temp_png ) {
        // Load source image
        $source = @imagecreatefrompng( $temp_png );
        if ( ! $source ) {
            return array(
                'status'  => 'error',
                'message' => 'PNG konnte nicht geladen werden',
            );
        }

        $orig_width  = imagesx( $source );
        $orig_height = imagesy( $source );

        // Create target image (16:9 for Twitter)
        $target_width  = self::TARGET_WIDTH;
        $target_height = self::TARGET_HEIGHT;

        $new_image = imagecreatetruecolor( $target_width, $target_height );

        // Fill with white background
        $white = imagecolorallocate( $new_image, 255, 255, 255 );
        imagefill( $new_image, 0, 0, $white );

        // Calculate crop dimensions (center crop, prioritize top for headers)
        $src_ratio = $orig_width / $orig_height;
        $dst_ratio = $target_width / $target_height;

        if ( $src_ratio > $dst_ratio ) {
            // Source is wider - crop sides
            $new_height = $orig_height;
            $new_width  = $orig_height * $dst_ratio;
            $src_x      = ( $orig_width - $new_width ) / 2;
            $src_y      = 0;
        } else {
            // Source is taller - crop from top to preserve hero sections
            $new_width  = $orig_width;
            $new_height = $orig_width / $dst_ratio;
            $src_x      = 0;
            $src_y      = 0; // Keep top of image
        }

        // Resize and crop
        imagecopyresampled(
            $new_image,
            $source,
            0,
            0,
            (int) $src_x,
            (int) $src_y,
            $target_width,
            $target_height,
            (int) $new_width,
            (int) $new_height
        );

        imagedestroy( $source );

        // Generate filename
        $type_prefix = 'term' === $entity_type ? 'term' : 'post';
        $filename    = "og-screenshot-{$type_prefix}-{$id}.webp";

        // Get upload directory
        $upload_dir = wp_upload_dir();
        $filepath   = $upload_dir['path'] . '/' . $filename;

        // Save as WebP (quality 85)
        $saved = imagewebp( $new_image, $filepath, 85 );
        imagedestroy( $new_image );

        if ( ! $saved || ! file_exists( $filepath ) ) {
            return array(
                'status'  => 'error',
                'message' => 'WebP konnte nicht gespeichert werden',
            );
        }

        // Delete existing attachment if exists
        $existing_id = 'post' === $entity_type
            ? get_post_meta( $id, '_og_screenshot_id', true )
            : get_term_meta( $id, '_og_screenshot_id', true );

        if ( $existing_id ) {
            wp_delete_attachment( $existing_id, true );
        }

        // Create WordPress attachment
        $filetype   = wp_check_filetype( $filename );
        $attachment = array(
            'post_mime_type' => $filetype['type'],
            'post_title'     => sanitize_file_name( $filename ),
            'post_content'   => '',
            'post_status'    => 'inherit',
        );

        // For posts, attach to the post; for terms, no parent
        $parent_id = 'post' === $entity_type ? $id : 0;
        $attach_id = wp_insert_attachment( $attachment, $filepath, $parent_id );

        if ( is_wp_error( $attach_id ) ) {
            @unlink( $filepath );
            return array(
                'status'  => 'error',
                'message' => 'Attachment konnte nicht erstellt werden',
            );
        }

        // Generate attachment metadata
        require_once ABSPATH . 'wp-admin/includes/image.php';
        $attach_data = wp_generate_attachment_metadata( $attach_id, $filepath );
        wp_update_attachment_metadata( $attach_id, $attach_data );

        // Save reference in meta
        if ( 'post' === $entity_type ) {
            update_post_meta( $id, '_og_screenshot_id', $attach_id );
        } else {
            update_term_meta( $id, '_og_screenshot_id', $attach_id );
        }

        return array(
            'status'        => 'success',
            'message'       => 'OG Screenshot erstellt',
            'attachment_id' => $attach_id,
        );
    }

    /**
     * Get screenshot URL for an entity
     *
     * @param int    $id          Post ID or Term ID.
     * @param string $entity_type 'post' or 'term'.
     * @return string|null Screenshot URL or null if not found.
     */
    public function get_screenshot_url( $id, $entity_type ) {
        $attach_id = 'post' === $entity_type
            ? get_post_meta( $id, '_og_screenshot_id', true )
            : get_term_meta( $id, '_og_screenshot_id', true );

        if ( ! $attach_id ) {
            return null;
        }

        $url = wp_get_attachment_url( $attach_id );

        // Verify attachment still exists
        if ( ! $url ) {
            // Clean up orphaned meta
            if ( 'post' === $entity_type ) {
                delete_post_meta( $id, '_og_screenshot_id' );
            } else {
                delete_term_meta( $id, '_og_screenshot_id' );
            }
            return null;
        }

        return $url;
    }

    /**
     * Delete screenshot for an entity
     *
     * @param int    $id          Post ID or Term ID.
     * @param string $entity_type 'post' or 'term'.
     * @return bool True if deleted, false otherwise.
     */
    public function delete_screenshot( $id, $entity_type ) {
        $attach_id = 'post' === $entity_type
            ? get_post_meta( $id, '_og_screenshot_id', true )
            : get_term_meta( $id, '_og_screenshot_id', true );

        if ( ! $attach_id ) {
            return false;
        }

        // Delete attachment
        wp_delete_attachment( $attach_id, true );

        // Delete meta
        if ( 'post' === $entity_type ) {
            delete_post_meta( $id, '_og_screenshot_id' );
        } else {
            delete_term_meta( $id, '_og_screenshot_id' );
        }

        return true;
    }

    /**
     * Check if an entity has a screenshot
     *
     * @param int    $id          Post ID or Term ID.
     * @param string $entity_type 'post' or 'term'.
     * @return bool
     */
    public function has_screenshot( $id, $entity_type ) {
        return null !== $this->get_screenshot_url( $id, $entity_type );
    }
}
