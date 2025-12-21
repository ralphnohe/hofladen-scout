<?php
/**
 * GeneratePress Child Theme Functions
 *
 * @package GeneratePress Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

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

// Remove entry-header (page title) on front page / Spezialisten finden page
add_action( 'wp', 'sd_remove_entry_header_on_front_page' );
function sd_remove_entry_header_on_front_page() {
	if ( is_front_page() || is_page( 'spezialisten-finden' ) ) {
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

