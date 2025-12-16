<?php
/**
 * Admin Class for Spezialist Screenshots
 * Uses ScreenshotAPI.net for capturing website screenshots
 */

if (!defined('ABSPATH')) {
    exit;
}

class SS_Admin {

    /**
     * Screenshot API URL
     */
    const API_URL = 'https://shot.screenshotapi.net/screenshot';

    /**
     * Single instance
     */
    protected static $_instance = null;

    /**
     * Get instance
     */
    public static function instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));

        // AJAX handlers
        add_action('wp_ajax_ss_get_stats', array($this, 'ajax_get_stats'));
        add_action('wp_ajax_ss_get_eligible_posts', array($this, 'ajax_get_eligible_posts'));
        add_action('wp_ajax_ss_process_single', array($this, 'ajax_process_single'));
        add_action('wp_ajax_ss_save_api_key', array($this, 'ajax_save_api_key'));
        add_action('wp_ajax_ss_get_repair_posts', array($this, 'ajax_get_repair_posts'));
        add_action('wp_ajax_ss_regenerate_sizes', array($this, 'ajax_regenerate_sizes'));
        add_action('wp_ajax_ss_pause_post', array($this, 'ajax_pause_post'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_management_page(
            'Screenshot Generator',
            'Screenshot Generator',
            'manage_options',
            'spezialist-screenshots',
            array($this, 'render_admin_page')
        );
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_assets($hook) {
        if ($hook !== 'tools_page_spezialist-screenshots') {
            return;
        }

        wp_enqueue_style(
            'ss-admin',
            SS_PLUGIN_URL . 'assets/css/ss-admin.css',
            array(),
            SS_VERSION
        );

        wp_enqueue_script(
            'ss-admin',
            SS_PLUGIN_URL . 'assets/js/ss-admin.js',
            array('jquery'),
            SS_VERSION,
            true
        );

        wp_localize_script('ss-admin', 'ssAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ss_admin_nonce'),
        ));
    }

    /**
     * Render admin page
     */
    public function render_admin_page() {
        $api_key = Spezialist_Screenshots::get_api_key();
        $api_configured = Spezialist_Screenshots::is_api_configured();
        $gd_available = extension_loaded('gd');
        ?>
        <div class="wrap ss-admin-page">
            <h1>Screenshot Generator</h1>
            <p class="description">Erstellt automatisch Website-Screenshots für Spezialist-Einträge ohne Featured Image.</p>

            <!-- API Settings -->
            <div class="ss-settings-box">
                <h3>API Einstellungen</h3>
                <p>
                    <label for="ss-api-key">Screenshot API Key:</label><br>
                    <input type="text" id="ss-api-key" value="<?php echo esc_attr($api_key); ?>" class="regular-text" placeholder="Ihr API Key von screenshotapi.net">
                    <button type="button" class="button" id="ss-save-api-key">Speichern</button>
                    <span id="ss-api-save-status"></span>
                </p>
                <p class="description">
                    Holen Sie sich einen API Key von <a href="https://screenshotapi.net" target="_blank">screenshotapi.net</a>
                </p>
            </div>

            <!-- Status -->
            <div class="ss-status-box">
                <h3>System-Status</h3>
                <p>
                    <span class="ss-status-indicator <?php echo $api_configured ? 'ss-status-ok' : 'ss-status-error'; ?>"></span>
                    Screenshot API: <?php echo $api_configured ? 'Konfiguriert' : '<strong>Nicht konfiguriert</strong>'; ?>
                </p>
                <p>
                    <span class="ss-status-indicator <?php echo $gd_available ? 'ss-status-ok' : 'ss-status-error'; ?>"></span>
                    GD Library: <?php echo $gd_available ? 'Verfügbar' : '<strong>Nicht verfügbar</strong>'; ?>
                </p>
            </div>

            <!-- Statistics -->
            <div class="ss-stats-grid" id="ss-stats">
                <div class="ss-stat-card">
                    <span class="ss-stat-number" id="ss-total-posts">-</span>
                    <span class="ss-stat-label">Gesamt Einträge</span>
                </div>
                <div class="ss-stat-card">
                    <span class="ss-stat-number" id="ss-without-image">-</span>
                    <span class="ss-stat-label">Ohne Featured Image</span>
                </div>
                <div class="ss-stat-card ss-stat-highlight">
                    <span class="ss-stat-number" id="ss-eligible">-</span>
                    <span class="ss-stat-label">Zu verarbeiten</span>
                </div>
                <div class="ss-stat-card ss-stat-warning">
                    <span class="ss-stat-number" id="ss-missing-base">-</span>
                    <span class="ss-stat-label">Fehlende Basisbilder</span>
                </div>
                <div class="ss-stat-card">
                    <span class="ss-stat-number" id="ss-missing-sizes">-</span>
                    <span class="ss-stat-label">Fehlende Größen</span>
                </div>
            </div>

            <!-- Controls -->
            <div class="ss-controls">
                <button type="button" class="button button-primary button-hero" id="ss-start" <?php echo (!$api_configured || !$gd_available) ? 'disabled' : ''; ?>>
                    Screenshots erstellen starten
                </button>
                <button type="button" class="button button-secondary" id="ss-stop" disabled>
                    Stoppen
                </button>
            </div>

            <!-- Repair Controls -->
            <div class="ss-controls ss-repair-controls" style="margin-top: 15px;">
                <button type="button" class="button" id="ss-repair" <?php echo (!$api_configured || !$gd_available) ? 'disabled' : ''; ?>>
                    Fehlende Basisbilder reparieren
                </button>
                <button type="button" class="button" id="ss-regenerate-sizes" <?php echo !$gd_available ? 'disabled' : ''; ?>>
                    Größen regenerieren
                </button>
            </div>

            <!-- Progress -->
            <div class="ss-progress-container" id="ss-progress-container" style="display: none;">
                <div class="ss-progress-bar-wrapper">
                    <div class="ss-progress-bar" id="ss-progress-bar"></div>
                </div>
                <p class="ss-progress-text" id="ss-progress-text">0 von 0 (0%)</p>
                <p class="ss-current-item" id="ss-current-item">Warte auf Start...</p>
            </div>

            <!-- Log -->
            <div class="ss-log-container">
                <h3>Verarbeitungsprotokoll</h3>
                <div class="ss-log" id="ss-log"></div>
            </div>

            <!-- Summary -->
            <div class="ss-summary" id="ss-summary" style="display: none;">
                <h3>Zusammenfassung</h3>
                <div class="ss-summary-grid">
                    <div class="ss-summary-item ss-summary-success">
                        <span class="ss-summary-number" id="ss-success-count">0</span>
                        <span class="ss-summary-label">Erfolgreich</span>
                    </div>
                    <div class="ss-summary-item ss-summary-error">
                        <span class="ss-summary-number" id="ss-error-count">0</span>
                        <span class="ss-summary-label">Fehler</span>
                    </div>
                    <div class="ss-summary-item ss-summary-skipped">
                        <span class="ss-summary-number" id="ss-skipped-count">0</span>
                        <span class="ss-summary-label">Übersprungen</span>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX: Save API key
     */
    public function ajax_save_api_key() {
        check_ajax_referer('ss_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Keine Berechtigung');
        }

        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        update_option('ss_api_key', $api_key);

        wp_send_json_success(array(
            'message' => 'API Key gespeichert',
        ));
    }

    /**
     * AJAX: Get statistics
     */
    public function ajax_get_stats() {
        check_ajax_referer('ss_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Keine Berechtigung');
        }

        global $wpdb;

        // Total spezialist posts
        $total = $wpdb->get_var("
            SELECT COUNT(*)
            FROM {$wpdb->posts}
            WHERE post_type = 'spezialist'
            AND post_status = 'publish'
        ");

        // Posts without featured image
        $without_image = $wpdb->get_var("
            SELECT COUNT(*)
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} tm ON (p.ID = tm.post_id AND tm.meta_key = '_thumbnail_id')
            WHERE p.post_type = 'spezialist'
            AND p.post_status = 'publish'
            AND tm.meta_id IS NULL
        ");

        // Eligible posts (without image but with website)
        $eligible = $wpdb->get_var("
            SELECT COUNT(*)
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON (p.ID = pm.post_id AND pm.meta_key = '_sd_website')
            LEFT JOIN {$wpdb->postmeta} tm ON (p.ID = tm.post_id AND tm.meta_key = '_thumbnail_id')
            WHERE p.post_type = 'spezialist'
            AND p.post_status = 'publish'
            AND tm.meta_id IS NULL
            AND pm.meta_value IS NOT NULL
            AND pm.meta_value <> ''
            AND pm.meta_value NOT LIKE '%facebook.com%'
            AND pm.meta_value NOT LIKE '%instagram.com%'
            AND pm.meta_value NOT LIKE '%linkedin.com%'
            AND pm.meta_value NOT LIKE '%twitter.com%'
            AND pm.meta_value NOT LIKE '%xing.com%'
        ");

        // Count missing base images - nur gültige Posts zählen
        $missing_base_ids = $this->find_missing_base_images();
        $missing_base = 0;
        foreach ($missing_base_ids as $post_id) {
            $post = get_post($post_id);
            if ($post && $post->post_status === 'publish') {
                $website = get_post_meta($post_id, '_sd_website', true);
                if (!empty($website)) {
                    $missing_base++;
                }
            }
        }

        // Count missing sizes - nur gültige Posts zählen
        $missing_size_ids = $this->find_missing_sizes();
        $missing_sizes = 0;
        foreach ($missing_size_ids as $post_id) {
            $post = get_post($post_id);
            if ($post) {
                $thumb_id = get_post_thumbnail_id($post_id);
                if ($thumb_id && $thumb_id > 0) {
                    $file = get_attached_file($thumb_id);
                    if ($file && file_exists($file)) {
                        $missing_sizes++;
                    }
                }
            }
        }

        wp_send_json_success(array(
            'total' => intval($total),
            'without_image' => intval($without_image),
            'eligible' => intval($eligible),
            'missing_base' => $missing_base,
            'missing_sizes' => $missing_sizes,
        ));
    }

    /**
     * AJAX: Get eligible posts
     */
    public function ajax_get_eligible_posts() {
        check_ajax_referer('ss_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Keine Berechtigung');
        }

        global $wpdb;

        $posts = $wpdb->get_results("
            SELECT p.ID, p.post_title, pm.meta_value as website
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON (p.ID = pm.post_id AND pm.meta_key = '_sd_website')
            LEFT JOIN {$wpdb->postmeta} tm ON (p.ID = tm.post_id AND tm.meta_key = '_thumbnail_id')
            WHERE p.post_type = 'spezialist'
            AND p.post_status = 'publish'
            AND tm.meta_id IS NULL
            AND pm.meta_value IS NOT NULL
            AND pm.meta_value <> ''
            AND pm.meta_value NOT LIKE '%facebook.com%'
            AND pm.meta_value NOT LIKE '%instagram.com%'
            AND pm.meta_value NOT LIKE '%linkedin.com%'
            AND pm.meta_value NOT LIKE '%twitter.com%'
            AND pm.meta_value NOT LIKE '%xing.com%'
            ORDER BY p.ID ASC
            LIMIT 5000
        ");

        $result = array();
        foreach ($posts as $post) {
            $result[] = array(
                'id' => intval($post->ID),
                'title' => $post->post_title,
                'website' => $post->website,
            );
        }

        wp_send_json_success($result);
    }

    /**
     * AJAX: Process single post
     */
    public function ajax_process_single() {
        check_ajax_referer('ss_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Keine Berechtigung');
        }

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $force_repair = isset($_POST['force_repair']) && $_POST['force_repair'] === 'true';

        if (!$post_id) {
            wp_send_json_error('Keine Post-ID angegeben');
        }

        // Handle force repair mode - delete attachment if base file is missing
        if ($force_repair && has_post_thumbnail($post_id)) {
            $thumb_id = get_post_thumbnail_id($post_id);
            $file = get_attached_file($thumb_id);
            if (!file_exists($file)) {
                // Base file is missing - delete the attachment
                wp_delete_attachment($thumb_id, true);
                // Clear the thumbnail meta
                delete_post_meta($post_id, '_thumbnail_id');
            }
        }

        // Check if post already has featured image (re-check after potential deletion)
        if (has_post_thumbnail($post_id)) {
            wp_send_json_success(array(
                'status' => 'skipped',
                'message' => 'Hat bereits ein Featured Image',
            ));
        }

        // Get website URL
        $website = get_post_meta($post_id, '_sd_website', true);

        if (empty($website)) {
            wp_send_json_success(array(
                'status' => 'skipped',
                'message' => 'Keine Website hinterlegt',
            ));
        }

        // Ensure URL has protocol
        if (!preg_match('/^https?:\/\//', $website)) {
            $website = 'https://' . $website;
        }

        // Validate URL
        if (!filter_var($website, FILTER_VALIDATE_URL)) {
            // Ungültige URL - Eintrag pausieren
            wp_update_post([
                'ID' => $post_id,
                'post_status' => 'draft'
            ]);

            wp_send_json_success(array(
                'status' => 'error',
                'message' => 'Ungültige URL - Eintrag pausiert: ' . $website,
                'paused' => true,
            ));
        }

        // Check API key
        $api_key = Spezialist_Screenshots::get_api_key();
        if (empty($api_key)) {
            wp_send_json_error('API Key nicht konfiguriert');
        }

        // Take screenshot via API
        $result = $this->take_screenshot_via_api($post_id, $website, $api_key);

        // Bei HTTP-Fehler (4xx oder 5xx): Eintrag pausieren
        if ($result['status'] === 'error' && isset($result['http_code']) && $result['http_code'] >= 400) {
            wp_update_post([
                'ID' => $post_id,
                'post_status' => 'draft'
            ]);

            // Erweiterte Fehlermeldung
            $result['message'] = 'HTTP ' . $result['http_code'] . ' - Eintrag pausiert: ' . $result['message'];
            $result['paused'] = true;
        }

        wp_send_json_success($result);
    }

    /**
     * Take screenshot via ScreenshotAPI.net and set as featured image
     */
    private function take_screenshot_via_api($post_id, $website, $api_key) {
        // Build API URL
        $api_params = array(
            'token' => $api_key,
            'url' => $website,
            'width' => 1200,
            'height' => 900,
            'output' => 'image',
            'file_type' => 'png',
            'wait_for_event' => 'load',
            'delay' => 2000,
            'no_cookie_banners' => 'true',
            'block_ads' => 'true',
        );

        $api_url = self::API_URL . '?' . http_build_query($api_params);

        // Fetch screenshot from API
        $response = wp_remote_get($api_url, array(
            'timeout' => 60,
            'sslverify' => true,
        ));

        if (is_wp_error($response)) {
            $error_message = $response->get_error_message();

            // Timeout oder Netzwerkfehler - Eintrag pausieren
            if (strpos($error_message, 'cURL error 28') !== false ||
                strpos(strtolower($error_message), 'timed out') !== false ||
                strpos(strtolower($error_message), 'timeout') !== false) {

                wp_update_post([
                    'ID' => $post_id,
                    'post_status' => 'draft'
                ]);

                return array(
                    'status' => 'error',
                    'message' => 'Zeitüberschreitung - Eintrag pausiert: ' . $error_message,
                    'paused' => true,
                );
            }

            return array(
                'status' => 'error',
                'message' => 'API Fehler: ' . $error_message,
            );
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $content_type = wp_remote_retrieve_header($response, 'content-type');

        if ($response_code !== 200) {
            $body = wp_remote_retrieve_body($response);
            return array(
                'status' => 'error',
                'message' => 'API Error ' . $response_code . ': ' . substr($body, 0, 100),
                'http_code' => $response_code,
            );
        }

        // Check if we got an image
        if (strpos($content_type, 'image/') === false) {
            return array(
                'status' => 'error',
                'message' => 'API hat kein Bild zurückgegeben',
            );
        }

        $image_data = wp_remote_retrieve_body($response);

        if (empty($image_data)) {
            return array(
                'status' => 'error',
                'message' => 'Leere Antwort von API',
            );
        }

        // Save to temp file
        $temp_file = sys_get_temp_dir() . '/ss_' . $post_id . '_' . time() . '.png';
        file_put_contents($temp_file, $image_data);

        // Convert to WebP and set as featured image
        $result = $this->convert_and_attach($post_id, $temp_file);

        // Cleanup temp file
        @unlink($temp_file);

        return $result;
    }

    /**
     * Convert PNG to WebP and attach as featured image
     */
    private function convert_and_attach($post_id, $temp_png) {
        // Load image with GD
        $source_image = @imagecreatefrompng($temp_png);

        if (!$source_image) {
            return array(
                'status' => 'error',
                'message' => 'PNG konnte nicht geladen werden',
            );
        }

        // Get original dimensions
        $orig_width = imagesx($source_image);
        $orig_height = imagesy($source_image);

        // Target dimensions
        $target_width = 800;
        $target_height = 600;

        // Create new image
        $new_image = imagecreatetruecolor($target_width, $target_height);

        // Fill with white background
        $white = imagecolorallocate($new_image, 255, 255, 255);
        imagefill($new_image, 0, 0, $white);

        // Calculate crop dimensions (center crop)
        $src_ratio = $orig_width / $orig_height;
        $dst_ratio = $target_width / $target_height;

        if ($src_ratio > $dst_ratio) {
            // Source is wider - crop sides
            $new_height = $orig_height;
            $new_width = $orig_height * $dst_ratio;
            $src_x = ($orig_width - $new_width) / 2;
            $src_y = 0;
        } else {
            // Source is taller - crop from top (shows header/hero)
            $new_width = $orig_width;
            $new_height = $orig_width / $dst_ratio;
            $src_x = 0;
            $src_y = 0;
        }

        // Resize and crop
        imagecopyresampled(
            $new_image, $source_image,
            0, 0, (int)$src_x, (int)$src_y,
            $target_width, $target_height,
            (int)$new_width, (int)$new_height
        );

        imagedestroy($source_image);

        // Get upload directory
        $upload_dir = wp_upload_dir();
        $filename = 'spezialist-screenshot-' . $post_id . '.webp';
        $filepath = $upload_dir['path'] . '/' . $filename;

        // Save as WebP
        $saved = imagewebp($new_image, $filepath, 85);
        imagedestroy($new_image);

        if (!$saved || !file_exists($filepath)) {
            return array(
                'status' => 'error',
                'message' => 'WebP konnte nicht gespeichert werden',
            );
        }

        // Create WordPress attachment
        $filetype = wp_check_filetype($filename);

        $attachment = array(
            'post_mime_type' => $filetype['type'],
            'post_title'     => sanitize_file_name($filename),
            'post_content'   => '',
            'post_status'    => 'inherit',
        );

        $attach_id = wp_insert_attachment($attachment, $filepath, $post_id);

        if (is_wp_error($attach_id)) {
            return array(
                'status' => 'error',
                'message' => 'Attachment konnte nicht erstellt werden',
            );
        }

        // Generate attachment metadata
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $filepath);
        wp_update_attachment_metadata($attach_id, $attach_data);

        // Set as featured image
        set_post_thumbnail($post_id, $attach_id);

        return array(
            'status' => 'success',
            'message' => 'Featured Image gesetzt',
            'attachment_id' => $attach_id,
        );
    }

    /**
     * Find posts with missing base images (size variants exist but base is missing)
     */
    private function find_missing_base_images() {
        $upload_dir = wp_upload_dir();
        $basedir = $upload_dir['basedir'];

        $missing = [];

        // Search in all year/month directories
        $year_dirs = glob($basedir . '/20[0-9][0-9]', GLOB_ONLYDIR);
        foreach ($year_dirs as $year_dir) {
            $month_dirs = glob($year_dir . '/[0-9][0-9]', GLOB_ONLYDIR);
            foreach ($month_dirs as $path) {
                $path .= '/';
                $patterns = [
                    $path . 'spezialist-screenshot-*-150x150.webp',
                    $path . 'spezialist-screenshot-*-300x225.webp',
                    $path . 'spezialist-screenshot-*-768x576.webp'
                ];

                foreach ($patterns as $pattern) {
                    foreach (glob($pattern) as $file) {
                        $base = preg_replace('/-\d+x\d+\.webp$/', '.webp', $file);
                        if (!file_exists($base)) {
                            if (preg_match('/spezialist-screenshot-(\d+)/', basename($file), $m)) {
                                $missing[$m[1]] = true;
                            }
                        }
                    }
                }
            }
        }
        return array_keys($missing);
    }

    /**
     * Find posts with missing size variants (base exists but some sizes are missing)
     */
    private function find_missing_sizes() {
        $upload_dir = wp_upload_dir();
        $basedir = $upload_dir['basedir'];

        $missing = [];

        // Search in all year/month directories
        $year_dirs = glob($basedir . '/20[0-9][0-9]', GLOB_ONLYDIR);
        foreach ($year_dirs as $year_dir) {
            $month_dirs = glob($year_dir . '/[0-9][0-9]', GLOB_ONLYDIR);
            foreach ($month_dirs as $path) {
                $path .= '/';
                // Find all base images
                foreach (glob($path . 'spezialist-screenshot-[0-9]*.webp') as $file) {
                    $basename = basename($file);
                    // Skip size variants
                    if (preg_match('/-\d+x\d+\.webp$/', $basename)) continue;

                    if (preg_match('/spezialist-screenshot-(\d+)\.webp$/', $basename, $m)) {
                        $id = $m[1];
                        $s150 = $path . "spezialist-screenshot-{$id}-150x150.webp";
                        $s300 = $path . "spezialist-screenshot-{$id}-300x225.webp";
                        $s768 = $path . "spezialist-screenshot-{$id}-768x576.webp";

                        if (!file_exists($s150) || !file_exists($s300) || !file_exists($s768)) {
                            $missing[] = $id;
                        }
                    }
                }
            }
        }
        return $missing;
    }

    /**
     * AJAX: Get posts with missing base images for repair
     */
    public function ajax_get_repair_posts() {
        check_ajax_referer('ss_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Keine Berechtigung');
        }

        $missing_ids = $this->find_missing_base_images();
        $result = [];

        foreach ($missing_ids as $post_id) {
            $post = get_post($post_id);
            if ($post && $post->post_status === 'publish') {
                $website = get_post_meta($post_id, '_sd_website', true);
                if (!empty($website)) {
                    $result[] = [
                        'id' => intval($post_id),
                        'title' => $post->post_title,
                        'website' => $website,
                    ];
                }
            }
        }

        wp_send_json_success($result);
    }

    /**
     * AJAX: Regenerate missing image sizes (batch process, no API calls)
     */
    public function ajax_regenerate_sizes() {
        check_ajax_referer('ss_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Keine Berechtigung');
        }

        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $missing_ids = $this->find_missing_sizes();
        $processed = 0;
        $errors = 0;
        $skipped = 0;

        // Limit to avoid timeout (process max 50 at a time)
        $batch_limit = 50;
        $batch = array_slice($missing_ids, 0, $batch_limit);

        foreach ($batch as $post_id) {
            // Validate post exists
            $post = get_post($post_id);
            if (!$post) {
                $skipped++;
                continue;
            }

            $thumb_id = get_post_thumbnail_id($post_id);
            if (!$thumb_id || $thumb_id <= 0) {
                $skipped++;
                continue;
            }

            $file = get_attached_file($thumb_id);
            if (!$file || !file_exists($file)) {
                $errors++;
                continue;
            }

            // Regenerate metadata (creates missing sizes)
            try {
                $metadata = wp_generate_attachment_metadata($thumb_id, $file);
                if (!is_wp_error($metadata) && !empty($metadata)) {
                    wp_update_attachment_metadata($thumb_id, $metadata);
                    $processed++;
                } else {
                    $errors++;
                }
            } catch (Exception $e) {
                $errors++;
            }
        }

        $remaining = count($missing_ids) - $batch_limit;

        wp_send_json_success([
            'processed' => $processed,
            'errors' => $errors,
            'skipped' => $skipped,
            'total' => count($missing_ids),
            'remaining' => max(0, $remaining)
        ]);
    }

    /**
     * AJAX handler to pause a post (set to draft) - used for timeout errors
     */
    public function ajax_pause_post() {
        check_ajax_referer('ss_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Keine Berechtigung');
        }

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $reason = isset($_POST['reason']) ? sanitize_text_field($_POST['reason']) : 'Unbekannt';

        if (!$post_id) {
            wp_send_json_error('Keine Post-ID angegeben');
        }

        wp_update_post([
            'ID' => $post_id,
            'post_status' => 'draft'
        ]);

        wp_send_json_success([
            'message' => 'Eintrag pausiert: ' . $reason,
            'paused' => true
        ]);
    }
}
