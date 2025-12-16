<?php
/**
 * Taxonomies
 *
 * Registers custom taxonomies for Spezialist CPT
 *
 * @package Spezialist_Directory
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * SD_Taxonomies Class
 */
class SD_Taxonomies {

    /**
     * Single instance
     *
     * @var SD_Taxonomies
     */
    protected static $_instance = null;

    /**
     * Main Instance
     *
     * @return SD_Taxonomies
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
        // Taxonomies are registered directly from main plugin init() method
        // to ensure proper timing with CPT registration

        // Term meta for FAQ-Schema Snippet
        add_action( 'spezialist_category_edit_form_fields', array( $this, 'render_faq_snippet_field' ), 10, 2 );
        add_action( 'edited_spezialist_category', array( $this, 'save_faq_snippet_field' ), 10, 2 );
    }

    /**
     * Register custom taxonomies
     */
    public function register_taxonomies() {
        $this->register_category_taxonomy();
        $this->register_location_taxonomy();
        $this->register_tag_taxonomy();
    }

    /**
     * Register Category Taxonomy
     */
    private function register_category_taxonomy() {
        $labels = array(
            'name'                       => _x( 'Kategorien', 'Taxonomy General Name', 'spezialist-directory' ),
            'singular_name'              => _x( 'Kategorie', 'Taxonomy Singular Name', 'spezialist-directory' ),
            'menu_name'                  => __( 'Kategorien', 'spezialist-directory' ),
            'all_items'                  => __( 'Alle Kategorien', 'spezialist-directory' ),
            'parent_item'                => __( 'Übergeordnete Kategorie', 'spezialist-directory' ),
            'parent_item_colon'          => __( 'Übergeordnete Kategorie:', 'spezialist-directory' ),
            'new_item_name'              => __( 'Neue Kategorie', 'spezialist-directory' ),
            'add_new_item'               => __( 'Neue Kategorie hinzufügen', 'spezialist-directory' ),
            'edit_item'                  => __( 'Kategorie bearbeiten', 'spezialist-directory' ),
            'update_item'                => __( 'Kategorie aktualisieren', 'spezialist-directory' ),
            'view_item'                  => __( 'Kategorie ansehen', 'spezialist-directory' ),
            'separate_items_with_commas' => __( 'Kategorien mit Komma trennen', 'spezialist-directory' ),
            'add_or_remove_items'        => __( 'Kategorien hinzufügen oder entfernen', 'spezialist-directory' ),
            'choose_from_most_used'      => __( 'Aus den häufigsten wählen', 'spezialist-directory' ),
            'popular_items'              => __( 'Beliebte Kategorien', 'spezialist-directory' ),
            'search_items'               => __( 'Kategorien suchen', 'spezialist-directory' ),
            'not_found'                  => __( 'Nicht gefunden', 'spezialist-directory' ),
            'no_terms'                   => __( 'Keine Kategorien', 'spezialist-directory' ),
            'items_list'                 => __( 'Kategorien Liste', 'spezialist-directory' ),
            'items_list_navigation'      => __( 'Kategorien Listen Navigation', 'spezialist-directory' ),
        );

        $args = array(
            'labels'                     => $labels,
            'hierarchical'               => true,
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_nav_menus'          => true,
            'show_tagcloud'              => false,
            'show_in_rest'               => true,
            'rewrite'                    => array(
                'slug'         => 'spezialist-kategorie',
                'with_front'   => false,
                'hierarchical' => true,
            ),
        );

        register_taxonomy( 'spezialist_category', array( 'spezialist' ), $args );
    }

    /**
     * Register Location Taxonomy
     */
    private function register_location_taxonomy() {
        $labels = array(
            'name'                       => _x( 'Standorte', 'Taxonomy General Name', 'spezialist-directory' ),
            'singular_name'              => _x( 'Standort', 'Taxonomy Singular Name', 'spezialist-directory' ),
            'menu_name'                  => __( 'Standorte', 'spezialist-directory' ),
            'all_items'                  => __( 'Alle Standorte', 'spezialist-directory' ),
            'parent_item'                => __( 'Übergeordneter Standort', 'spezialist-directory' ),
            'parent_item_colon'          => __( 'Übergeordneter Standort:', 'spezialist-directory' ),
            'new_item_name'              => __( 'Neuer Standort', 'spezialist-directory' ),
            'add_new_item'               => __( 'Neuen Standort hinzufügen', 'spezialist-directory' ),
            'edit_item'                  => __( 'Standort bearbeiten', 'spezialist-directory' ),
            'update_item'                => __( 'Standort aktualisieren', 'spezialist-directory' ),
            'view_item'                  => __( 'Standort ansehen', 'spezialist-directory' ),
            'separate_items_with_commas' => __( 'Standorte mit Komma trennen', 'spezialist-directory' ),
            'add_or_remove_items'        => __( 'Standorte hinzufügen oder entfernen', 'spezialist-directory' ),
            'choose_from_most_used'      => __( 'Aus den häufigsten wählen', 'spezialist-directory' ),
            'popular_items'              => __( 'Beliebte Standorte', 'spezialist-directory' ),
            'search_items'               => __( 'Standorte suchen', 'spezialist-directory' ),
            'not_found'                  => __( 'Nicht gefunden', 'spezialist-directory' ),
            'no_terms'                   => __( 'Keine Standorte', 'spezialist-directory' ),
            'items_list'                 => __( 'Standorte Liste', 'spezialist-directory' ),
            'items_list_navigation'      => __( 'Standorte Listen Navigation', 'spezialist-directory' ),
        );

        $args = array(
            'labels'                     => $labels,
            'hierarchical'               => true,
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_nav_menus'          => true,
            'show_tagcloud'              => false,
            'show_in_rest'               => true,
            'rewrite'                    => array(
                'slug'         => 'standort',
                'with_front'   => false,
                'hierarchical' => true,
            ),
        );

        register_taxonomy( 'spezialist_location', array( 'spezialist' ), $args );
    }

    /**
     * Register Tag Taxonomy (non-hierarchical)
     */
    private function register_tag_taxonomy() {
        $labels = array(
            'name'                       => _x( 'Schlagworte', 'Taxonomy General Name', 'spezialist-directory' ),
            'singular_name'              => _x( 'Schlagwort', 'Taxonomy Singular Name', 'spezialist-directory' ),
            'menu_name'                  => __( 'Schlagworte', 'spezialist-directory' ),
            'all_items'                  => __( 'Alle Schlagworte', 'spezialist-directory' ),
            'new_item_name'              => __( 'Neues Schlagwort', 'spezialist-directory' ),
            'add_new_item'               => __( 'Neues Schlagwort hinzufügen', 'spezialist-directory' ),
            'edit_item'                  => __( 'Schlagwort bearbeiten', 'spezialist-directory' ),
            'update_item'                => __( 'Schlagwort aktualisieren', 'spezialist-directory' ),
            'view_item'                  => __( 'Schlagwort ansehen', 'spezialist-directory' ),
            'separate_items_with_commas' => __( 'Schlagworte mit Komma trennen', 'spezialist-directory' ),
            'add_or_remove_items'        => __( 'Schlagworte hinzufügen oder entfernen', 'spezialist-directory' ),
            'choose_from_most_used'      => __( 'Aus den häufigsten wählen', 'spezialist-directory' ),
            'popular_items'              => __( 'Beliebte Schlagworte', 'spezialist-directory' ),
            'search_items'               => __( 'Schlagworte suchen', 'spezialist-directory' ),
            'not_found'                  => __( 'Nicht gefunden', 'spezialist-directory' ),
            'no_terms'                   => __( 'Keine Schlagworte', 'spezialist-directory' ),
            'items_list'                 => __( 'Schlagworte Liste', 'spezialist-directory' ),
            'items_list_navigation'      => __( 'Schlagworte Listen Navigation', 'spezialist-directory' ),
        );

        $args = array(
            'labels'                     => $labels,
            'hierarchical'               => false, // Non-hierarchical like WP tags
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_nav_menus'          => true,
            'show_tagcloud'              => true,
            'show_in_rest'               => true,
            'rewrite'                    => array(
                'slug'         => 'schlagwort',
                'with_front'   => false,
            ),
        );

        register_taxonomy( 'spezialist_tag', array( 'spezialist' ), $args );
    }

    /**
     * Render FAQ-Schema Snippet field on category edit page
     *
     * @param WP_Term $term     Current taxonomy term object
     * @param string  $taxonomy Current taxonomy slug
     */
    public function render_faq_snippet_field( $term, $taxonomy ) {
        $faq_snippet = get_term_meta( $term->term_id, '_sd_faq_snippet', true );
        ?>
        <tr class="form-field">
            <th scope="row">
                <label for="sd_faq_snippet"><?php _e( 'FAQ-Schema Snippet', 'spezialist-directory' ); ?></label>
            </th>
            <td>
                <textarea name="sd_faq_snippet" id="sd_faq_snippet" rows="12" cols="50" class="large-text code"><?php echo esc_textarea( $faq_snippet ); ?></textarea>
                <p class="description">
                    <?php _e( 'HTML mit Schema.org FAQ Markup und JSON-LD. Wird auf der Kategorie-Archivseite unter dem Titel angezeigt.', 'spezialist-directory' ); ?>
                </p>
            </td>
        </tr>
        <?php
    }

    /**
     * Save FAQ-Schema Snippet field
     *
     * @param int $term_id Term ID being saved
     * @param int $tt_id   Term taxonomy ID
     */
    public function save_faq_snippet_field( $term_id, $tt_id ) {
        if ( ! current_user_can( 'manage_categories' ) ) {
            return;
        }

        if ( isset( $_POST['sd_faq_snippet'] ) ) {
            // No sanitization - allows full HTML + JSON-LD script tags
            // Only admins can edit taxonomy terms
            $snippet = wp_unslash( $_POST['sd_faq_snippet'] );
            update_term_meta( $term_id, '_sd_faq_snippet', $snippet );
        }
    }
}
