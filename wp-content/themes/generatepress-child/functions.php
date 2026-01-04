<?php
/**
 * GeneratePress Child Theme Functions
 *
 * @package GeneratePress Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

add_filter( 'litespeed_crawler_disable_blocklist', '__return_true' );

/**
 * Enqueue parent and child theme styles
 */
function generatepress_child_enqueue_styles() {
	// Enqueue Google Fonts - DM Sans for bold industrial design
	wp_enqueue_style(
		'google-fonts-dm-sans',
		'https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;0,9..40,800;1,9..40,400;1,9..40,500&display=swap',
		array(),
		null
	);

	// Enqueue Font Awesome 6
	wp_enqueue_style(
		'font-awesome-6',
		'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css',
		array(),
		'6.5.1'
	);

	// Enqueue parent theme stylesheet
	wp_enqueue_style( 'generatepress-parent', get_template_directory_uri() . '/style.css' );

	// Enqueue child theme stylesheet
	wp_enqueue_style(
		'generatepress-child',
		get_stylesheet_directory_uri() . '/style.css',
		array( 'generatepress-parent', 'google-fonts-dm-sans' ),
		wp_get_theme()->get( 'Version' )
	);
}
add_action( 'wp_enqueue_scripts', 'generatepress_child_enqueue_styles' );

/**
 * Add your custom functions below this line
 */

/**
 * Spezialist Directory Theme Support
 *
 * Customizations for the Spezialist Directory plugin
 */

// Enable user registration for the directory
add_action( 'after_setup_theme', 'sd_enable_user_registration' );
function sd_enable_user_registration() {
	// Enable user registration programmatically if needed
	// Users will need to register to claim listings
}

// Add custom body classes for directory pages
add_filter( 'body_class', 'sd_custom_body_classes' );
function sd_custom_body_classes( $classes ) {
	// Add class when on any directory page
	if ( is_singular( 'hofladen' ) || is_post_type_archive( 'hofladen' ) ) {
		$classes[] = 'spezialist-directory-page';
	}

	return $classes;
}

// Customize excerpt length for specialist listings
add_filter( 'excerpt_length', 'sd_custom_excerpt_length', 999 );
function sd_custom_excerpt_length( $length ) {
	if ( is_post_type_archive( 'hofladen' ) || ( is_search() && get_post_type() === 'hofladen' ) ) {
		return 20;
	}
	return $length;
}

// Remove "..." from excerpt
add_filter( 'excerpt_more', 'sd_excerpt_more' );
function sd_excerpt_more( $more ) {
	if ( get_post_type() === 'hofladen' ) {
		return '...';
	}
	return $more;
}

// Remove entry-header (page title) on front page / Hofläden finden page
add_action( 'wp', 'sd_remove_entry_header_on_front_page' );
function sd_remove_entry_header_on_front_page() {
	if ( is_front_page() || is_page( 'hoflaeden-finden' ) ) {
		// Remove the entry header from GeneratePress
		remove_action( 'generate_after_entry_header', 'generate_post_image' );
		add_filter( 'generate_show_title', '__return_false' );
	}
}


/************************/
/* DNS Prefetch         */
/************************/
function dns_prefetch()
{
    // List of domains to set prefetching for
    $prefetchDomains = [
        '//www.google-analytics.com',
        '//www.googletagmanager.com',
        '//fonts.googleapis.com',       // Google Fonts (DM Sans)
        '//fonts.gstatic.com',          // Google Fonts asset delivery
        '//nominatim.openstreetmap.org', // Geocoding API
        '//tile.openstreetmap.org',     // Map tiles
    ];

    $prefetchDomains = array_unique($prefetchDomains);
    $result = '';

    foreach ($prefetchDomains as $domain) {
        $domain = esc_url($domain);
        $result .= '<link rel="dns-prefetch" href="' . $domain . '" crossorigin />';
        $result .= '<link rel="preconnect" href="' . $domain . '" crossorigin />';
    }

    echo $result;
}
add_action('wp_head', 'dns_prefetch', 0);

/***********************************/
/* OpenLiteSpeed                   */
/***********************************/

add_filter("litespeed_media_ignore_remote_missing_sizes", "__return_true");

/**
 * Conditional Menu Items based on Login Status
 *
 * - Show "Mein Dashboard" only to logged-in users
 * - Show "Anmelden" only to logged-out users
 */
add_filter( 'wp_nav_menu_objects', 'sd_conditional_menu_items', 10, 2 );
function sd_conditional_menu_items( $items, $args ) {
	foreach ( $items as $key => $item ) {
		// Hide "Mein Dashboard" for logged-out users
		if ( strpos( $item->url, 'mein-dashboard' ) !== false && ! is_user_logged_in() ) {
			unset( $items[ $key ] );
		}

		// Hide "Anmelden" for logged-in users
		if ( strpos( $item->url, '/anmelden' ) !== false && is_user_logged_in() ) {
			unset( $items[ $key ] );
		}
	}

	return $items;
}

/**
 * TinyMCE Editor Yellow Background
 * Sets the background color for the rich text editor iframe content
 */
add_filter( 'tiny_mce_before_init', 'sd_tinymce_yellow_background' );
function sd_tinymce_yellow_background( $mce_init ) {
	$styles = 'body.mce-content-body { background-color: #fffae2 !important; }';
	if ( isset( $mce_init['content_style'] ) ) {
		$mce_init['content_style'] .= ' ' . $styles;
	} else {
		$mce_init['content_style'] = $styles;
	}
	return $mce_init;
}

/**
 * Style Site Title with colored pipe and Nürnberg
 * Makes "|" yellow (#f1c232) and "Nürnberg" gray (#aaaaaa)
 * Uses GeneratePress filter to modify the site title output
 */
add_filter( 'generate_site_title_output', 'sd_style_site_title_output' );
function sd_style_site_title_output( $output ) {
	$site_name = get_bloginfo( 'name' );
	if ( strpos( $site_name, '|' ) !== false ) {
		$parts = explode( '|', $site_name );
		if ( count( $parts ) === 2 ) {
			$styled_name = esc_html( trim( $parts[0] ) ) . ' <span class="site-title-pipe">|</span> <span class="site-title-city">' . esc_html( trim( $parts[1] ) ) . '</span>';
			$output = str_replace( '>' . esc_html( $site_name ) . '<', '>' . $styled_name . '<', $output );
		}
	}
	return $output;
}

/**
 * Dequeue Elementor scripts on /merkliste page
 * Fixes "elementorFrontendConfig is not defined" error
 */
add_action( 'wp_enqueue_scripts', 'sd_dequeue_elementor_on_merkliste', 999 );
function sd_dequeue_elementor_on_merkliste() {
	global $post;
	$post_content = isset( $post->post_content ) ? $post->post_content : '';

	if ( is_page( 'merkliste' ) || has_shortcode( $post_content, 'spezialist_favorites' ) ) {
		wp_dequeue_script( 'elementor-frontend' );
		wp_dequeue_style( 'elementor-frontend' );
	}
}

/**
 * Remove page title on Dashboard page (has hero header with H1)
 */
add_filter( 'the_title', 'sd_remove_dashboard_page_title', 10, 2 );
function sd_remove_dashboard_page_title( $title, $id = null ) {
	if ( is_page( 'mein-dashboard' ) && in_the_loop() && is_main_query() && $id == get_queried_object_id() ) {
		return '';
	}
	return $title;
}

/**
 * hCaptcha Integration for Contact Form 7
 * Keys defined in wp-config.php: SD_HCAPTCHA_SITE_KEY, SD_HCAPTCHA_SECRET_KEY
 */

/**
 * 1. Enqueue hCaptcha script on contact page
 */
add_action( 'wp_enqueue_scripts', 'sd_enqueue_hcaptcha_script' );
function sd_enqueue_hcaptcha_script() {
	if ( is_page( 'kontakt' ) ) {
		wp_enqueue_script(
			'hcaptcha',
			'https://js.hcaptcha.com/1/api.js',
			array(),
			null,
			true
		);
	}
}

/**
 * 2. Add hCaptcha widget to CF7 form before submit button
 */
add_filter( 'wpcf7_form_elements', 'sd_add_hcaptcha_to_cf7' );
function sd_add_hcaptcha_to_cf7( $content ) {
	if ( ! is_page( 'kontakt' ) ) {
		return $content;
	}

	$hcaptcha_html = '
	<div class="sd-hcaptcha-wrapper">
		<div class="h-captcha" data-sitekey="' . SD_HCAPTCHA_SITE_KEY . '" data-callback="sdHcaptchaCallback"></div>
		<div class="sd-hcaptcha-error" style="display:none;">Bitte bestätige, dass Du kein Roboter bist.</div>
	</div>';

	// Insert before submit button
	$content = preg_replace(
		'/(<input[^>]*type=["\']submit["\'][^>]*>)/i',
		$hcaptcha_html . '$1',
		$content
	);

	return $content;
}

/**
 * 3. Server-side hCaptcha validation
 * Uses wp_get_referer() instead of is_page() - works during AJAX requests!
 */
add_filter( 'wpcf7_validate', 'sd_validate_hcaptcha', 10, 2 );
function sd_validate_hcaptcha( $result, $tags ) {
	// Check referer instead of is_page() - works during AJAX!
	$referer = wp_get_referer();
	if ( strpos( $referer, '/kontakt' ) === false ) {
		return $result;
	}

	$hcaptcha_response = isset( $_POST['h-captcha-response'] ) ? sanitize_text_field( $_POST['h-captcha-response'] ) : '';

	if ( empty( $hcaptcha_response ) ) {
		$result->invalidate( '', 'Bitte bestätige, dass Du kein Roboter bist.' );
		return $result;
	}

	// Verify with hCaptcha API
	$verify_url = 'https://api.hcaptcha.com/siteverify';
	$response = wp_remote_post( $verify_url, [
		'body' => [
			'secret'   => SD_HCAPTCHA_SECRET_KEY,
			'response' => $hcaptcha_response,
			'remoteip' => $_SERVER['REMOTE_ADDR'] ?? ''
		],
		'timeout' => 10
	] );

	if ( is_wp_error( $response ) ) {
		// Network error: allow form submission as fallback
		return $result;
	}

	$body = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( empty( $body['success'] ) || $body['success'] !== true ) {
		$result->invalidate( '', 'Die Captcha-Überprüfung ist fehlgeschlagen. Bitte versuche es erneut.' );
	}

	return $result;
}

/**
 * 4. Client-side validation script
 */
add_action( 'wp_footer', 'sd_hcaptcha_validation_script' );
function sd_hcaptcha_validation_script() {
	if ( ! is_page( 'kontakt' ) ) {
		return;
	}
	?>
	<script>
	// hCaptcha callback when solved
	function sdHcaptchaCallback() {
		var errorDiv = document.querySelector('.sd-hcaptcha-error');
		if (errorDiv) {
			errorDiv.style.display = 'none';
		}
	}

	document.addEventListener('DOMContentLoaded', function() {
		var form = document.querySelector('.sd-cf7-form .wpcf7-form');
		if (!form) return;

		form.addEventListener('submit', function(e) {
			var hcaptchaResponse = form.querySelector('[name="h-captcha-response"]');
			var errorDiv = form.querySelector('.sd-hcaptcha-error');

			if (!hcaptchaResponse || !hcaptchaResponse.value) {
				e.preventDefault();
				e.stopPropagation();
				if (errorDiv) {
					errorDiv.style.display = 'block';
				}
				// Scroll to captcha
				var captchaWrapper = form.querySelector('.sd-hcaptcha-wrapper');
				if (captchaWrapper) {
					captchaWrapper.scrollIntoView({ behavior: 'smooth', block: 'center' });
				}
				return false;
			}
			if (errorDiv) {
				errorDiv.style.display = 'none';
			}
		});
	});
	</script>
	<?php
}

/**
 * Custom OG Image for Homepage (Kartensuche)
 * Priority 99 to override OG Screenshots plugin
 */
add_filter( 'spezialist_seo_og_image', function( $image, $post ) {
    if ( is_front_page() || is_page_template( 'page-kartensuche.php' ) ) {
        return home_url( '/wp-content/uploads/og-image-v1.png' );
    }
    return $image;
}, 99, 2 );

add_filter( 'spezialist_seo_twitter_image', function( $image, $post ) {
    if ( is_front_page() || is_page_template( 'page-kartensuche.php' ) ) {
        return home_url( '/wp-content/uploads/og-image-v1.png' );
    }
    return $image;
}, 99, 2 );

/**
 * Disclaimer above Footer Widget Area
 * "Alle Angaben ohne Gewähr." centered above footer
 */
add_action( 'generate_before_footer_content', 'sd_footer_disclaimer' );
function sd_footer_disclaimer() { ?>
    <div class="sd-footer-disclaimer" style="text-align: center; padding: 15px 0; font-size: 14px; color: #666;">
        Alle Angaben ohne Gewähr.
    </div>
<?php }
