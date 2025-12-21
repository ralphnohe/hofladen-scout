<?php
/**
 * OG Screenshots Discovery Class
 *
 * Handles discovery of all content that needs OG screenshots
 *
 * @package Spezialist_OG_Screenshots
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * OGS_Discovery Class
 */
class OGS_Discovery {

    /**
     * Single instance
     *
     * @var OGS_Discovery
     */
    protected static $_instance = null;

    /**
     * Content type constants
     */
    const TYPE_PAGE = 'page';
    const TYPE_POST = 'post';
    const TYPE_SPEZIALIST = 'hofladen';
    const TYPE_CATEGORY = 'spezialist_category';
    const TYPE_LOCATION = 'spezialist_location';
    const TYPE_TAG = 'spezialist_tag';

    /**
     * Main Instance
     *
     * @return OGS_Discovery
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
        // No hooks needed for discovery
    }

    /**
     * Get all content types
     *
     * @return array
     */
    public static function get_content_types() {
        return array(
            self::TYPE_PAGE       => 'Pages',
            self::TYPE_POST       => 'Posts',
            self::TYPE_SPEZIALIST => 'Spezialisten',
            self::TYPE_CATEGORY   => 'Kategorien',
            self::TYPE_LOCATION   => 'Standorte',
            self::TYPE_TAG        => 'Tags',
        );
    }

    /**
     * Check if type is a post type
     *
     * @param string $type Content type.
     * @return bool
     */
    public static function is_post_type( $type ) {
        return in_array( $type, array( self::TYPE_PAGE, self::TYPE_POST, self::TYPE_SPEZIALIST ), true );
    }

    /**
     * Check if type is a taxonomy
     *
     * @param string $type Content type.
     * @return bool
     */
    public static function is_taxonomy( $type ) {
        return in_array( $type, array( self::TYPE_CATEGORY, self::TYPE_LOCATION, self::TYPE_TAG ), true );
    }

    /**
     * Get all items that need screenshots
     *
     * @param string|null $type        Filter by content type.
     * @param bool        $only_missing Only return items without screenshots.
     * @param int         $limit       Limit number of results (0 = no limit).
     * @return array
     */
    public function get_items( $type = null, $only_missing = true, $limit = 0 ) {
        $items = array();

        // Post types
        $post_types = array();
        if ( ! $type || self::TYPE_PAGE === $type ) {
            $post_types[] = self::TYPE_PAGE;
        }
        if ( ! $type || self::TYPE_POST === $type ) {
            $post_types[] = self::TYPE_POST;
        }
        if ( ! $type || self::TYPE_SPEZIALIST === $type ) {
            $post_types[] = self::TYPE_SPEZIALIST;
        }

        if ( ! empty( $post_types ) ) {
            $items = array_merge( $items, $this->get_post_items( $post_types, $only_missing ) );
        }

        // Taxonomies
        $taxonomies = array();
        if ( ! $type || self::TYPE_CATEGORY === $type ) {
            $taxonomies[] = self::TYPE_CATEGORY;
        }
        if ( ! $type || self::TYPE_LOCATION === $type ) {
            $taxonomies[] = self::TYPE_LOCATION;
        }
        if ( ! $type || self::TYPE_TAG === $type ) {
            $taxonomies[] = self::TYPE_TAG;
        }

        if ( ! empty( $taxonomies ) ) {
            $items = array_merge( $items, $this->get_term_items( $taxonomies, $only_missing ) );
        }

        // Apply limit if set
        if ( $limit > 0 && count( $items ) > $limit ) {
            $items = array_slice( $items, 0, $limit );
        }

        return $items;
    }

    /**
     * Get post-based items (pages, posts, spezialist CPT)
     *
     * @param array $post_types   Post types to include.
     * @param bool  $only_missing Only return items without screenshots.
     * @return array
     */
    private function get_post_items( $post_types, $only_missing ) {
        $args = array(
            'post_type'      => $post_types,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'orderby'        => 'ID',
            'order'          => 'ASC',
        );

        if ( $only_missing ) {
            $args['meta_query'] = array(
                array(
                    'key'     => '_og_screenshot_id',
                    'compare' => 'NOT EXISTS',
                ),
            );
        }

        $post_ids = get_posts( $args );
        $items    = array();

        foreach ( $post_ids as $post_id ) {
            $post_type = get_post_type( $post_id );
            $permalink = get_permalink( $post_id );

            // Skip if no valid permalink
            if ( ! $permalink || is_wp_error( $permalink ) ) {
                continue;
            }

            $items[] = array(
                'id'             => $post_id,
                'type'           => $post_type,
                'entity_type'    => 'post',
                'title'          => get_the_title( $post_id ),
                'url'            => $permalink,
                'has_screenshot' => (bool) get_post_meta( $post_id, '_og_screenshot_id', true ),
            );
        }

        return $items;
    }

    /**
     * Get term-based items (categories, locations, tags)
     *
     * @param array $taxonomies   Taxonomies to include.
     * @param bool  $only_missing Only return items without screenshots.
     * @return array
     */
    private function get_term_items( $taxonomies, $only_missing ) {
        $items = array();

        foreach ( $taxonomies as $taxonomy ) {
            $args = array(
                'taxonomy'   => $taxonomy,
                'hide_empty' => false,
                'orderby'    => 'term_id',
                'order'      => 'ASC',
            );

            if ( $only_missing ) {
                $args['meta_query'] = array(
                    array(
                        'key'     => '_og_screenshot_id',
                        'compare' => 'NOT EXISTS',
                    ),
                );
            }

            $terms = get_terms( $args );

            if ( is_wp_error( $terms ) ) {
                continue;
            }

            foreach ( $terms as $term ) {
                $term_link = get_term_link( $term );

                // Skip if no valid term link
                if ( ! $term_link || is_wp_error( $term_link ) ) {
                    continue;
                }

                $items[] = array(
                    'id'             => $term->term_id,
                    'type'           => $taxonomy,
                    'entity_type'    => 'term',
                    'title'          => $term->name,
                    'url'            => $term_link,
                    'has_screenshot' => (bool) get_term_meta( $term->term_id, '_og_screenshot_id', true ),
                );
            }
        }

        return $items;
    }

    /**
     * Get statistics for all content types
     *
     * @return array
     */
    public function get_stats() {
        $stats = array();

        foreach ( self::get_content_types() as $type => $label ) {
            $stats[ $type ] = $this->get_type_stats( $type );
        }

        return $stats;
    }

    /**
     * Get statistics for a single content type
     *
     * @param string $type Content type.
     * @return array
     */
    private function get_type_stats( $type ) {
        $total   = count( $this->get_items( $type, false ) );
        $missing = count( $this->get_items( $type, true ) );

        return array(
            'total'           => $total,
            'with_screenshot' => $total - $missing,
            'missing'         => $missing,
            'label'           => self::get_content_types()[ $type ] ?? $type,
        );
    }

    /**
     * Get total statistics across all types
     *
     * @return array
     */
    public function get_total_stats() {
        $stats = $this->get_stats();

        $total           = 0;
        $with_screenshot = 0;
        $missing         = 0;

        foreach ( $stats as $type_stats ) {
            $total           += $type_stats['total'];
            $with_screenshot += $type_stats['with_screenshot'];
            $missing         += $type_stats['missing'];
        }

        return array(
            'total'           => $total,
            'with_screenshot' => $with_screenshot,
            'missing'         => $missing,
        );
    }

    /**
     * Get a single item by ID and entity type
     *
     * @param int    $id          Item ID.
     * @param string $entity_type 'post' or 'term'.
     * @return array|null
     */
    public function get_item( $id, $entity_type ) {
        if ( 'post' === $entity_type ) {
            $post = get_post( $id );
            if ( ! $post || 'publish' !== $post->post_status ) {
                return null;
            }

            return array(
                'id'             => $id,
                'type'           => $post->post_type,
                'entity_type'    => 'post',
                'title'          => get_the_title( $id ),
                'url'            => get_permalink( $id ),
                'has_screenshot' => (bool) get_post_meta( $id, '_og_screenshot_id', true ),
            );
        }

        if ( 'term' === $entity_type ) {
            $term = get_term( $id );
            if ( ! $term || is_wp_error( $term ) ) {
                return null;
            }

            return array(
                'id'             => $id,
                'type'           => $term->taxonomy,
                'entity_type'    => 'term',
                'title'          => $term->name,
                'url'            => get_term_link( $term ),
                'has_screenshot' => (bool) get_term_meta( $id, '_og_screenshot_id', true ),
            );
        }

        return null;
    }
}
