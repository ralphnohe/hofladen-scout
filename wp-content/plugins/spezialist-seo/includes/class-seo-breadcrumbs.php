<?php
/**
 * SEO Breadcrumbs Visual Component
 *
 * Renders visual breadcrumb navigation for frontend
 *
 * @package Spezialist_SEO
 * @since 1.1.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * SDSEO_Breadcrumbs Class
 */
class SDSEO_Breadcrumbs {

    /**
     * Render breadcrumb navigation HTML
     *
     * @param array $args Optional arguments for customization
     * @return void
     */
    public static function render( $args = array() ) {
        $defaults = array(
            'home_text'   => __( 'Start', 'spezialist-seo' ),
            'separator'   => '›',
            'show_home'   => true,
            'before'      => '',
            'after'       => '',
        );

        $args = wp_parse_args( $args, $defaults );
        $items = self::get_breadcrumb_items( $args );

        if ( count( $items ) < 2 ) {
            return; // Don't show breadcrumbs if only home
        }

        $output = $args['before'];
        $output .= '<nav class="sd-breadcrumbs" aria-label="' . esc_attr__( 'Breadcrumb', 'spezialist-seo' ) . '">';
        $output .= '<ol class="sd-breadcrumb-list">';

        $count = count( $items );
        $i = 0;

        foreach ( $items as $item ) {
            $i++;
            $is_last = ( $i === $count );

            $output .= '<li class="sd-breadcrumb-item' . ( $is_last ? ' sd-breadcrumb-current' : '' ) . '">';

            if ( ! $is_last && ! empty( $item['url'] ) ) {
                $output .= '<a href="' . esc_url( $item['url'] ) . '">' . esc_html( $item['name'] ) . '</a>';
            } else {
                $output .= '<span aria-current="page">' . esc_html( $item['name'] ) . '</span>';
            }

            $output .= '</li>';

            // Add separator (except after last item)
            if ( ! $is_last ) {
                $output .= '<li class="sd-breadcrumb-separator" aria-hidden="true">' . esc_html( $args['separator'] ) . '</li>';
            }
        }

        $output .= '</ol>';
        $output .= '</nav>';
        $output .= $args['after'];

        echo $output;
    }

    /**
     * Get breadcrumb items based on current page
     *
     * @param array $args
     * @return array
     */
    private static function get_breadcrumb_items( $args ) {
        $items = array();

        // Home
        if ( $args['show_home'] ) {
            $items[] = array(
                'name' => $args['home_text'],
                'url'  => home_url( '/' ),
            );
        }

        // Single specialist page
        if ( is_singular( 'hofladen' ) ) {
            $post_id = get_the_ID();

            // Add category
            if ( class_exists( 'Spezialist_SEO' ) ) {
                $categories = Spezialist_SEO::get_specialist_categories( $post_id );
                if ( ! empty( $categories ) ) {
                    $term = get_term_by( 'name', $categories[0], 'spezialist_category' );
                    if ( $term && ! is_wp_error( $term ) ) {
                        $items[] = array(
                            'name' => $term->name,
                            'url'  => get_term_link( $term ),
                        );
                    }
                }
            }

            // Current page (no URL)
            $items[] = array(
                'name' => get_the_title(),
                'url'  => '',
            );
        }

        // Category archive
        elseif ( is_tax( 'spezialist_category' ) ) {
            $term = get_queried_object();
            if ( $term && ! is_wp_error( $term ) ) {
                // Check for parent category
                if ( $term->parent ) {
                    $parent = get_term( $term->parent, 'spezialist_category' );
                    if ( $parent && ! is_wp_error( $parent ) ) {
                        $items[] = array(
                            'name' => $parent->name,
                            'url'  => get_term_link( $parent ),
                        );
                    }
                }

                $items[] = array(
                    'name' => $term->name,
                    'url'  => '',
                );
            }
        }

        // Location archive
        elseif ( is_tax( 'spezialist_location' ) ) {
            $term = get_queried_object();
            if ( $term && ! is_wp_error( $term ) ) {
                $items[] = array(
                    'name' => sprintf( __( 'Standort: %s', 'spezialist-seo' ), $term->name ),
                    'url'  => '',
                );
            }
        }

        // Tag archive
        elseif ( is_tax( 'spezialist_tag' ) ) {
            $term = get_queried_object();
            if ( $term && ! is_wp_error( $term ) ) {
                $items[] = array(
                    'name' => $term->name,
                    'url'  => '',
                );
            }
        }

        // Directory page (not front page)
        elseif ( class_exists( 'Spezialist_SEO' ) && Spezialist_SEO::is_directory_page() && ! is_front_page() ) {
            $items[] = array(
                'name' => __( 'Spezialisten', 'spezialist-seo' ),
                'url'  => '',
            );
        }

        return $items;
    }

    /**
     * Get breadcrumbs as HTML string (without echo)
     *
     * @param array $args Optional arguments
     * @return string
     */
    public static function get( $args = array() ) {
        ob_start();
        self::render( $args );
        return ob_get_clean();
    }
}
