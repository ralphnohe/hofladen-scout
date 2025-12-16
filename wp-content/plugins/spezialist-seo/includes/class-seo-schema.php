<?php
/**
 * SEO Schema.org JSON-LD
 *
 * Handles structured data output for Google Rich Results
 *
 * @package Spezialist_SEO
 * @since 1.0.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * SDSEO_Schema Class
 */
class SDSEO_Schema {

    /**
     * Single instance
     *
     * @var SDSEO_Schema
     */
    protected static $_instance = null;

    /**
     * Main Instance
     *
     * @return SDSEO_Schema
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
        // Schema output at priority 20 (after meta tags)
        add_action( 'wp_head', array( $this, 'output_schema' ), 20 );
    }

    /**
     * Output appropriate schema based on page type
     */
    public function output_schema() {
        // Single specialist page
        if ( is_singular( 'spezialist' ) ) {
            $this->output_specialist_schema();
            $this->output_breadcrumb_schema();
            return;
        }

        // Homepage / Directory page
        if ( is_front_page() ) {
            $this->output_website_schema();
            $this->output_organization_schema();
            $this->output_directory_schema();
            return;
        }

        // Directory page (not front page)
        if ( Spezialist_SEO::is_directory_page() ) {
            $this->output_directory_schema();
            $this->output_breadcrumb_schema();
            return;
        }

        // Category archive
        if ( is_tax( 'spezialist_category' ) ) {
            $this->output_category_schema();
            $this->output_breadcrumb_schema();
            return;
        }

        // Location archive
        if ( is_tax( 'spezialist_location' ) ) {
            $this->output_location_schema();
            $this->output_breadcrumb_schema();
            return;
        }
    }

    /**
     * Output schema for single specialist (dynamic LocalBusiness subtype)
     */
    private function output_specialist_schema() {
        $post_id = get_the_ID();
        $name = get_the_title();

        // Get all meta data
        $phone = get_post_meta( $post_id, '_sd_phone', true );
        $email = get_post_meta( $post_id, '_sd_email', true );
        $website = get_post_meta( $post_id, '_sd_website', true );
        $address = get_post_meta( $post_id, '_sd_address', true );
        $zip = get_post_meta( $post_id, '_sd_zip', true );
        $city = get_post_meta( $post_id, '_sd_city', true );
        $latitude = get_post_meta( $post_id, '_sd_latitude', true );
        $longitude = get_post_meta( $post_id, '_sd_longitude', true );
        $place_id = get_post_meta( $post_id, '_sd_place_id', true );

        // Social profiles
        $facebook = get_post_meta( $post_id, '_sd_facebook', true );
        $twitter = get_post_meta( $post_id, '_sd_twitter', true );
        $instagram = get_post_meta( $post_id, '_sd_instagram', true );
        $linkedin = get_post_meta( $post_id, '_sd_linkedin', true );
        $youtube = get_post_meta( $post_id, '_sd_youtube', true );
        $xing = get_post_meta( $post_id, '_sd_xing', true );

        // Categories
        $categories = Spezialist_SEO::get_specialist_categories( $post_id );

        // Build schema with dynamic type based on category
        $schema = array(
            '@context' => 'https://schema.org',
            '@type'    => $this->get_specialist_schema_type( $post_id ),
            '@id'      => get_permalink() . '#business',
            'name'     => $name,
            'url'      => get_permalink(),
        );

        // Description
        $excerpt = get_the_excerpt();
        if ( $excerpt ) {
            $schema['description'] = Spezialist_SEO::truncate_text( $excerpt, 250 );
        }

        // Image
        if ( has_post_thumbnail( $post_id ) ) {
            $schema['image'] = array(
                '@type' => 'ImageObject',
                'url'   => get_the_post_thumbnail_url( $post_id, 'large' ),
            );
        } else {
            $schema['image'] = SDSEO()->get_placeholder_image();
        }

        // Contact information
        if ( $phone ) {
            $schema['telephone'] = $phone;
        }
        if ( $email ) {
            $schema['email'] = $email;
        }

        // Address (only if we have all parts)
        if ( $address && $city && $zip ) {
            $schema['address'] = array(
                '@type'           => 'PostalAddress',
                'streetAddress'   => $address,
                'postalCode'      => $zip,
                'addressLocality' => $city,
                'addressCountry'  => 'DE',
            );
        } elseif ( $city ) {
            // At minimum, include city
            $schema['address'] = array(
                '@type'           => 'PostalAddress',
                'addressLocality' => $city,
                'addressCountry'  => 'DE',
            );
        }

        // Geo coordinates
        if ( $latitude && $longitude ) {
            $schema['geo'] = array(
                '@type'     => 'GeoCoordinates',
                'latitude'  => floatval( $latitude ),
                'longitude' => floatval( $longitude ),
            );

            // hasMap - Google Maps link
            if ( $place_id ) {
                $schema['hasMap'] = sprintf(
                    'https://www.google.com/maps/search/?api=1&query=%s,%s&query_place_id=%s',
                    $latitude,
                    $longitude,
                    rawurlencode( $place_id )
                );
            } else {
                $schema['hasMap'] = sprintf(
                    'https://www.google.com/maps/search/?api=1&query=%s,%s',
                    $latitude,
                    $longitude
                );
            }
        }

        // Area served
        if ( $city ) {
            $schema['areaServed'] = array(
                '@type' => 'City',
                'name'  => $city,
            );
        }

        // Social profiles (sameAs)
        $social_profiles = array_filter( array(
            $facebook,
            $twitter,
            $instagram,
            $linkedin,
            $youtube,
            $xing,
            $website,
        ) );
        if ( ! empty( $social_profiles ) ) {
            $schema['sameAs'] = array_values( $social_profiles );
        }

        // Service categories as keywords (serviceType is not valid for LocalBusiness types)
        if ( ! empty( $categories ) ) {
            $schema['keywords'] = implode( ', ', $categories );
        }

        // Opening hours specification
        $opening_hours = $this->build_opening_hours_specification( $post_id );
        if ( $opening_hours ) {
            $schema['openingHoursSpecification'] = $opening_hours;
        }

        // Services/Offer catalog
        $offer_catalog = $this->build_offer_catalog( $post_id );
        if ( $offer_catalog ) {
            $schema['hasOfferCatalog'] = $offer_catalog;
        }

        // Price range (generic for now)
        $schema['priceRange'] = '€€';

        // Aggregate Rating (if ratings plugin is active and ratings exist)
        if ( class_exists( 'SR_Ratings' ) ) {
            $average = SR_Ratings::get_average( $post_id );
            $count = SR_Ratings::get_count( $post_id );
            if ( $count > 0 && $average > 0 ) {
                $schema['aggregateRating'] = array(
                    '@type'       => 'AggregateRating',
                    'ratingValue' => round( floatval( $average ), 1 ),
                    'ratingCount' => intval( $count ),
                    'bestRating'  => 5,
                    'worstRating' => 1,
                );
            }
        }

        // Check if premium
        if ( class_exists( 'SD_Premium_Features' ) && SD_Premium_Features::is_premium( $post_id ) ) {
            $schema['additionalType'] = 'https://spezialist-für.de/premium';
        }

        $this->output_json_ld( $schema );
    }

    /**
     * Output schema for directory/search page (ItemList)
     */
    private function output_directory_schema() {
        global $wp_query;

        // Get current listings from the page
        $listings = array();
        $position = 1;

        // Query specialists for the ItemList
        $args = array(
            'post_type'      => 'spezialist',
            'post_status'    => 'publish',
            'posts_per_page' => 12,
            'orderby'        => 'date',
            'order'          => 'DESC',
        );

        // Apply filters if present
        if ( isset( $_GET['sd_category'] ) && ! empty( $_GET['sd_category'] ) ) {
            $args['tax_query'][] = array(
                'taxonomy' => 'spezialist_category',
                'field'    => 'slug',
                'terms'    => sanitize_text_field( $_GET['sd_category'] ),
            );
        }
        if ( isset( $_GET['sd_location'] ) && ! empty( $_GET['sd_location'] ) ) {
            $args['tax_query'][] = array(
                'taxonomy' => 'spezialist_location',
                'field'    => 'slug',
                'terms'    => sanitize_text_field( $_GET['sd_location'] ),
            );
        }

        $query = new WP_Query( $args );

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $listings[] = array(
                    '@type'    => 'ListItem',
                    'position' => $position,
                    'url'      => get_permalink(),
                    'name'     => get_the_title(),
                );
                $position++;
            }
            wp_reset_postdata();
        }

        // Build page title based on filters
        $page_name = 'Spezialisten-Verzeichnis';
        $page_description = 'Finden Sie qualifizierte Spezialisten in Deutschland';

        $category_filter = isset( $_GET['sd_category'] ) ? sanitize_text_field( $_GET['sd_category'] ) : '';
        $location_filter = isset( $_GET['sd_location'] ) ? sanitize_text_field( $_GET['sd_location'] ) : '';

        if ( $category_filter && $location_filter ) {
            $page_name = sprintf( '%s in %s', $category_filter, $location_filter );
            $page_description = sprintf( 'Alle %s in %s', $category_filter, $location_filter );
        } elseif ( $category_filter ) {
            $page_name = sprintf( '%s - Spezialisten', $category_filter );
            $page_description = sprintf( 'Alle %s in Deutschland', $category_filter );
        } elseif ( $location_filter ) {
            $page_name = sprintf( 'Spezialisten in %s', $location_filter );
            $page_description = sprintf( 'Alle Spezialisten in %s', $location_filter );
        }

        $schema = array(
            '@context'    => 'https://schema.org',
            '@type'       => 'CollectionPage',
            'name'        => $page_name,
            'description' => $page_description,
            'url'         => home_url( '/' ),
        );

        if ( ! empty( $listings ) ) {
            $schema['mainEntity'] = array(
                '@type'           => 'ItemList',
                'numberOfItems'   => count( $listings ),
                'itemListElement' => $listings,
            );
        }

        $this->output_json_ld( $schema );
    }

    /**
     * Output WebSite schema (for homepage)
     */
    private function output_website_schema() {
        $schema = array(
            '@context'        => 'https://schema.org',
            '@type'           => 'WebSite',
            '@id'             => home_url( '/#website' ),
            'name'            => get_bloginfo( 'name' ),
            'description'     => get_bloginfo( 'description' ),
            'url'             => home_url( '/' ),
            'inLanguage'      => 'de-DE',
            'potentialAction' => array(
                '@type'       => 'SearchAction',
                'target'      => array(
                    '@type'       => 'EntryPoint',
                    'urlTemplate' => home_url( '/?sd_search={search_term_string}' ),
                ),
                'query-input' => 'required name=search_term_string',
            ),
        );

        $this->output_json_ld( $schema );
    }

    /**
     * Output Organization schema (for homepage)
     */
    private function output_organization_schema() {
        $schema = array(
            '@context' => 'https://schema.org',
            '@type'    => 'Organization',
            '@id'      => home_url( '/#organization' ),
            'name'     => get_bloginfo( 'name' ),
            'url'      => home_url( '/' ),
            'logo'     => array(
                '@type'      => 'ImageObject',
                'url'        => SDSEO()->get_logo_url(),
                'contentUrl' => SDSEO()->get_logo_url(),
            ),
        );

        $this->output_json_ld( $schema );
    }

    /**
     * Output schema for category archive
     */
    private function output_category_schema() {
        $term = get_queried_object();

        // Get listings in this category
        $listings = array();
        $position = 1;

        $args = array(
            'post_type'      => 'spezialist',
            'post_status'    => 'publish',
            'posts_per_page' => 12,
            'tax_query'      => array(
                array(
                    'taxonomy' => 'spezialist_category',
                    'field'    => 'term_id',
                    'terms'    => $term->term_id,
                ),
            ),
        );

        $query = new WP_Query( $args );

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $listings[] = array(
                    '@type'    => 'ListItem',
                    'position' => $position,
                    'url'      => get_permalink(),
                    'name'     => get_the_title(),
                );
                $position++;
            }
            wp_reset_postdata();
        }

        $schema = array(
            '@context'    => 'https://schema.org',
            '@type'       => 'CollectionPage',
            'name'        => sprintf( '%s - Spezialisten', $term->name ),
            'description' => sprintf( '%d qualifizierte %s in Deutschland', $term->count, $term->name ),
            'url'         => get_term_link( $term ),
        );

        if ( ! empty( $listings ) ) {
            $schema['mainEntity'] = array(
                '@type'           => 'ItemList',
                'numberOfItems'   => $term->count,
                'itemListElement' => $listings,
            );
        }

        $this->output_json_ld( $schema );
    }

    /**
     * Output schema for location archive
     */
    private function output_location_schema() {
        $term = get_queried_object();

        // Get listings in this location
        $listings = array();
        $position = 1;

        $args = array(
            'post_type'      => 'spezialist',
            'post_status'    => 'publish',
            'posts_per_page' => 12,
            'tax_query'      => array(
                array(
                    'taxonomy' => 'spezialist_location',
                    'field'    => 'term_id',
                    'terms'    => $term->term_id,
                ),
            ),
        );

        $query = new WP_Query( $args );

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $listings[] = array(
                    '@type'    => 'ListItem',
                    'position' => $position,
                    'url'      => get_permalink(),
                    'name'     => get_the_title(),
                );
                $position++;
            }
            wp_reset_postdata();
        }

        $schema = array(
            '@context'    => 'https://schema.org',
            '@type'       => 'CollectionPage',
            'name'        => sprintf( 'Spezialisten in %s', $term->name ),
            'description' => sprintf( '%d geprüfte Spezialisten in %s', $term->count, $term->name ),
            'url'         => get_term_link( $term ),
            'about'       => array(
                '@type' => 'City',
                'name'  => $term->name,
            ),
        );

        if ( ! empty( $listings ) ) {
            $schema['mainEntity'] = array(
                '@type'           => 'ItemList',
                'numberOfItems'   => $term->count,
                'itemListElement' => $listings,
            );
        }

        $this->output_json_ld( $schema );
    }

    /**
     * Output breadcrumb schema
     */
    private function output_breadcrumb_schema() {
        $items = array();
        $position = 1;

        // Home
        $items[] = array(
            '@type'    => 'ListItem',
            'position' => $position++,
            'name'     => 'Start',
            'item'     => home_url( '/' ),
        );

        // Single specialist
        if ( is_singular( 'spezialist' ) ) {
            $post_id = get_the_ID();
            $categories = Spezialist_SEO::get_specialist_categories( $post_id );

            // Add category if available
            if ( ! empty( $categories ) ) {
                $category_term = get_term_by( 'name', $categories[0], 'spezialist_category' );
                if ( $category_term ) {
                    $items[] = array(
                        '@type'    => 'ListItem',
                        'position' => $position++,
                        'name'     => $category_term->name,
                        'item'     => get_term_link( $category_term ),
                    );
                }
            }

            // Add current page (no item property for last breadcrumb)
            $items[] = array(
                '@type'    => 'ListItem',
                'position' => $position++,
                'name'     => get_the_title(),
            );
        }

        // Category archive
        elseif ( is_tax( 'spezialist_category' ) ) {
            $term = get_queried_object();
            $items[] = array(
                '@type'    => 'ListItem',
                'position' => $position++,
                'name'     => $term->name,
            );
        }

        // Location archive
        elseif ( is_tax( 'spezialist_location' ) ) {
            $term = get_queried_object();
            $items[] = array(
                '@type'    => 'ListItem',
                'position' => $position++,
                'name'     => $term->name,
            );
        }

        // Directory page (not front page)
        elseif ( Spezialist_SEO::is_directory_page() && ! is_front_page() ) {
            $items[] = array(
                '@type'    => 'ListItem',
                'position' => $position++,
                'name'     => 'Spezialisten',
            );
        }

        // Only output if we have more than just home
        if ( count( $items ) > 1 ) {
            $schema = array(
                '@context'        => 'https://schema.org',
                '@type'           => 'BreadcrumbList',
                'itemListElement' => $items,
            );

            $this->output_json_ld( $schema );
        }
    }

    /**
     * Output JSON-LD script tag
     *
     * @param array $schema
     */
    private function output_json_ld( $schema ) {
        echo '<script type="application/ld+json">';
        echo wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
        echo '</script>' . "\n";
    }

    /**
     * Get Schema.org type mapping for categories
     *
     * Maps spezialist_category slugs to specific Schema.org LocalBusiness subtypes
     *
     * @return array
     */
    private function get_category_schema_mapping() {
        return array(
            // Handwerk / HomeAndConstructionBusiness
            'elektriker'                 => 'Electrician',
            'elektroinstallationsdienst' => 'Electrician',
            'elektrotechniker'           => 'Electrician',
            'gasinstallateur'            => 'Plumber',
            'heizungsmonteur'            => 'Plumber',
            'installateur'               => 'Plumber',
            'sanitaerinstallateur'       => 'Plumber',
            'dachdecker'                 => 'RoofingContractor',
            'maler'                      => 'HousePainter',
            'malermeister'               => 'HousePainter',
            'anbieter-von-schloessern'   => 'Locksmith',
            'schluesselnotdienst'        => 'Locksmith',
            'schluesseldienst'           => 'Locksmith',
            'klimaanlagenanbieter'       => 'HVACBusiness',
            'heizungs-und-klimatechnikbetrieb' => 'HVACBusiness',
            'heizungsanlagenanbieter'    => 'HVACBusiness',
            'bauunternehmen'             => 'GeneralContractor',
            'allround-handwerker'        => 'GeneralContractor',
            'handwerker'                 => 'GeneralContractor',
            'umzugsunternehmen'          => 'MovingCompany',

            // Medizin / MedicalBusiness
            'zahnarzt'                   => 'Dentist',
            'kieferorthopaede'           => 'Dentist',
            'arzt'                       => 'Physician',
            'allgemeinmediziner'         => 'Physician',
            'hausarzt'                   => 'Physician',
            'internist'                  => 'Physician',
            'kardiologe'                 => 'Physician',
            'orthopaedie'                => 'Physician',
            'apotheke'                   => 'Pharmacy',
            'physiotherapeut'            => 'Physiotherapy',
            'ergotherapeut'              => 'Physiotherapy',
            'krankengymnastik'           => 'Physiotherapy',
            'optiker'                    => 'Optician',

            // Beauty / HealthAndBeautyBusiness
            'friseur'                    => 'HairSalon',
            'friseursalon'               => 'HairSalon',
            'herrenfriseur'              => 'HairSalon',
            'damenfriseur'               => 'HairSalon',
            'kosmetiker'                 => 'BeautySalon',
            'kosmetikstudio'             => 'BeautySalon',
            'kosmetikservice'            => 'BeautySalon',
            'nagelstudio'                => 'NailSalon',
            'fitnessstudio'              => 'HealthClub',
            'fitness'                    => 'HealthClub',
            'sportstudio'                => 'HealthClub',
            'spa'                        => 'DaySpa',
            'wellness'                   => 'DaySpa',
            'tattoo'                     => 'TattooParlor',
            'tattoostudio'               => 'TattooParlor',

            // Automotive / AutomotiveBusiness
            'autowerkstatt'              => 'AutoRepair',
            'kfz-werkstatt'              => 'AutoRepair',
            'kfz-meisterbetrieb'         => 'AutoRepair',
            'autohaendler'               => 'AutoDealer',
            'gebrauchtwagenhaendler'     => 'AutoDealer',
            'karosseriewerkstatt'        => 'AutoBodyShop',
            'autolackiererei'            => 'AutoBodyShop',
            'kfz-ersatzteilgeschaeft'    => 'AutoPartsStore',
            'autowaesche'                => 'AutoWash',
            'waschanlage'                => 'AutoWash',
            'reifenservice'              => 'AutoRepair',
            'reifenhaendler'             => 'TireShop',

            // Gastronomie / FoodEstablishment
            'restaurant'                 => 'Restaurant',
            'deutsches-restaurant'       => 'Restaurant',
            'italienisches-restaurant'   => 'Restaurant',
            'griechisches-restaurant'    => 'Restaurant',
            'asiatisches-restaurant'     => 'Restaurant',
            'indisches-restaurant'       => 'Restaurant',
            'tuerkisches-restaurant'     => 'Restaurant',
            'doener'                     => 'FastFoodRestaurant',
            'fast-food'                  => 'FastFoodRestaurant',
            'imbiss'                     => 'FastFoodRestaurant',
            'pizzeria'                   => 'Restaurant',
            'baeckerei'                  => 'Bakery',
            'konditorei'                 => 'Bakery',
            'cafe'                       => 'CafeOrCoffeeShop',
            'coffeeshop'                 => 'CafeOrCoffeeShop',
            'bar'                        => 'BarOrPub',
            'kneipe'                     => 'BarOrPub',
            'biergarten'                 => 'BarOrPub',
            'cocktailbar'                => 'BarOrPub',
            'brauerei'                   => 'Brewery',
            'eiscafe'                    => 'IceCreamShop',

            // Recht / LegalService
            'anwalt'                     => 'LegalService',
            'rechtsanwalt'               => 'LegalService',
            'anwaltskanzlei'             => 'LegalService',
            'notar'                      => 'Notary',

            // Finanzen / FinancialService
            'finanzberater'              => 'FinancialService',
            'finanzplaner'               => 'FinancialService',
            'versicherungsmakler'        => 'InsuranceAgency',
            'versicherungsagentur'       => 'InsuranceAgency',
            'steuerberater'              => 'AccountingService',
            'buchhaltung'                => 'AccountingService',

            // Immobilien / RealEstateAgent
            'immobilienmakler'           => 'RealEstateAgent',
            'immobilienagentur'          => 'RealEstateAgent',
            'immobilienberater'          => 'RealEstateAgent',
            'hausverwaltung'             => 'RealEstateAgent',

            // Einzelhandel / Store
            'buchhandlung'               => 'BookStore',
            'antiquariat'                => 'BookStore',
            'computergeschaeft'          => 'ComputerStore',
            'elektrofachgeschaeft'       => 'ElectronicsStore',
            'elektrofachmarkt'           => 'ElectronicsStore',
            'blumengeschaeft'            => 'Florist',
            'blumenladen'                => 'Florist',
            'moebelgeschaeft'            => 'FurnitureStore',
            'gartencenter'               => 'GardenStore',
            'baumarkt'                   => 'HardwareStore',
            'juwelier'                   => 'JewelryStore',
            'schmuckgeschaeft'           => 'JewelryStore',
            'bekleidungsgeschaeft'       => 'ClothingStore',
            'modegeschaeft'              => 'ClothingStore',
            'boutique'                   => 'ClothingStore',
            'sportgeschaeft'             => 'SportingGoodsStore',
            'fahrradgeschaeft'           => 'BikeStore',
            'fahrradladen'               => 'BikeStore',
            'musikgeschaeft'             => 'MusicStore',
            'tierhandlung'               => 'PetStore',
            'zoohandlung'                => 'PetStore',
            'supermarkt'                 => 'GroceryStore',
            'lebensmittelgeschaeft'      => 'GroceryStore',
            'spielwarengeschaeft'        => 'ToyStore',
            'schuhgeschaeft'             => 'ShoeStore',
            'drogeriegeschaeft'          => 'Store',
            'drogerie'                   => 'Store',

            // Unterkunft / LodgingBusiness
            'hotel'                      => 'Hotel',
            'pension'                    => 'LodgingBusiness',
            'ferienwohnung'              => 'LodgingBusiness',
            'hostel'                     => 'Hostel',

            // Reise / TravelAgency
            'reisebuero'                 => 'TravelAgency',

            // Reinigung / DryCleaningOrLaundry
            'reinigung'                  => 'DryCleaningOrLaundry',
            'textilreinigung'            => 'DryCleaningOrLaundry',
            'waescherei'                 => 'DryCleaningOrLaundry',
            'aenderungsschneiderei'      => 'DryCleaningOrLaundry',

            // Kinderbetreuung / ChildCare
            'kindergarten'               => 'ChildCare',
            'kita'                       => 'ChildCare',
            'kinderbetreuung'            => 'ChildCare',

            // Bildung / EducationalOrganization
            'fahrschule'                 => 'DrivingSchool',
            'musikschule'                => 'MusicStore',
            'tanzschule'                 => 'DanceGroup',
            'sprachschule'               => 'EducationalOrganization',
            'nachhilfe'                  => 'EducationalOrganization',

            // Fotografie
            'fotograf'                   => 'Photographer',
            'fotostudio'                 => 'Photographer',

            // IT / Technik
            'it-service'                 => 'ProfessionalService',
            'webdesigner'                => 'ProfessionalService',
            'computerreparatur'          => 'ProfessionalService',
        );
    }

    /**
     * Determine the best Schema.org type for a specialist listing
     *
     * @param int $post_id
     * @return string
     */
    private function get_specialist_schema_type( $post_id ) {
        $categories = wp_get_post_terms( $post_id, 'spezialist_category', array( 'fields' => 'slugs' ) );

        if ( is_wp_error( $categories ) || empty( $categories ) ) {
            return 'ProfessionalService';
        }

        $mapping = $this->get_category_schema_mapping();

        foreach ( $categories as $category_slug ) {
            if ( isset( $mapping[ $category_slug ] ) ) {
                return $mapping[ $category_slug ];
            }
        }

        return 'ProfessionalService';
    }

    /**
     * Build OpeningHoursSpecification from business hours meta
     *
     * @param int $post_id
     * @return array|null
     */
    private function build_opening_hours_specification( $post_id ) {
        if ( ! class_exists( 'SD_Business_Hours' ) ) {
            return null;
        }

        $hours = SD_Business_Hours::get_hours( $post_id );
        if ( empty( $hours ) ) {
            return null;
        }

        $day_schema_map = array(
            'monday'    => 'https://schema.org/Monday',
            'tuesday'   => 'https://schema.org/Tuesday',
            'wednesday' => 'https://schema.org/Wednesday',
            'thursday'  => 'https://schema.org/Thursday',
            'friday'    => 'https://schema.org/Friday',
            'saturday'  => 'https://schema.org/Saturday',
            'sunday'    => 'https://schema.org/Sunday',
        );

        $specifications = array();

        foreach ( $hours as $day => $data ) {
            if ( empty( $data['open'] ) || empty( $data['from'] ) || empty( $data['to'] ) ) {
                continue;
            }

            $day_url = isset( $day_schema_map[ $day ] ) ? $day_schema_map[ $day ] : null;
            if ( ! $day_url ) {
                continue;
            }

            // Check for break/lunch period - split into two time periods
            if ( ! empty( $data['break_from'] ) && ! empty( $data['break_to'] ) ) {
                // Morning period (before break)
                $specifications[] = array(
                    '@type'     => 'OpeningHoursSpecification',
                    'dayOfWeek' => $day_url,
                    'opens'     => $data['from'],
                    'closes'    => $data['break_from'],
                );
                // Afternoon period (after break)
                $specifications[] = array(
                    '@type'     => 'OpeningHoursSpecification',
                    'dayOfWeek' => $day_url,
                    'opens'     => $data['break_to'],
                    'closes'    => $data['to'],
                );
            } else {
                // Single continuous period
                $specifications[] = array(
                    '@type'     => 'OpeningHoursSpecification',
                    'dayOfWeek' => $day_url,
                    'opens'     => $data['from'],
                    'closes'    => $data['to'],
                );
            }
        }

        return ! empty( $specifications ) ? $specifications : null;
    }

    /**
     * Build hasOfferCatalog from services meta
     *
     * @param int $post_id
     * @return array|null
     */
    private function build_offer_catalog( $post_id ) {
        $services = get_post_meta( $post_id, '_sd_services', true );

        if ( ! is_array( $services ) || empty( $services ) ) {
            return null;
        }

        $offers = array();
        foreach ( $services as $service_name ) {
            if ( empty( $service_name ) ) {
                continue;
            }
            $offers[] = array(
                '@type'       => 'Offer',
                'itemOffered' => array(
                    '@type' => 'Service',
                    'name'  => $service_name,
                ),
            );
        }

        if ( empty( $offers ) ) {
            return null;
        }

        return array(
            '@type'           => 'OfferCatalog',
            'name'            => __( 'Angebotene Leistungen', 'spezialist-seo' ),
            'itemListElement' => $offers,
        );
    }

    /**
     * Output FAQPage schema (prepared for future use)
     *
     * Requires _sd_faqs meta field with array of ['question' => '', 'answer' => '']
     * This method is prepared but not yet called - activate when FAQ content is available
     *
     * @param int $post_id
     * @return void
     */
    private function output_faq_schema( $post_id ) {
        $faqs = get_post_meta( $post_id, '_sd_faqs', true );

        if ( ! is_array( $faqs ) || empty( $faqs ) ) {
            return;
        }

        $main_entity = array();
        foreach ( $faqs as $faq ) {
            if ( empty( $faq['question'] ) || empty( $faq['answer'] ) ) {
                continue;
            }
            $main_entity[] = array(
                '@type'          => 'Question',
                'name'           => $faq['question'],
                'acceptedAnswer' => array(
                    '@type' => 'Answer',
                    'text'  => $faq['answer'],
                ),
            );
        }

        if ( empty( $main_entity ) ) {
            return;
        }

        $schema = array(
            '@context'   => 'https://schema.org',
            '@type'      => 'FAQPage',
            'mainEntity' => $main_entity,
        );

        $this->output_json_ld( $schema );
    }
}
