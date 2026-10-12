<?php
/**
 * Original SEO data (title, canonical, Open Graph, Twitter card) for the theme-served routes, from
 * parts/seo.json. The original pages had no meta descriptions, so none is printed (nothing is invented).
 * When an SEO plugin (Yoast, Rank Math, AIOSEO, SEOPress) is active it owns the tags of real pages; the
 * theme still titles the routes it serves itself.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function eg_seo_data( $route ) {
    static $data = null;
    if ( null === $data ) {
        $file = get_theme_file_path( 'parts/seo.json' );
        $data = is_readable( $file ) ? (array) json_decode( (string) file_get_contents( $file ), true ) : array();
    }
    return $data[ $route ] ?? array();
}

function eg_seo_plugin_active() {
    return defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'AIOSEO_VERSION' ) || defined( 'SEOPRESS_VERSION' );
}

function eg_route_url( $route ) {
    // Match the address WordPress actually serves (trailing slash per the permalink setting), so canonical never points at a redirect.
    return 'home' === $route ? home_url( '/' ) : home_url( user_trailingslashit( '/' . $route ) );
}

add_filter( 'pre_get_document_title', function ( $title ) {
    $route = eg_current_route();
    $virtual = ! empty( $GLOBALS['eg_route'] );
    if ( ! $route || ( eg_seo_plugin_active() && ! $virtual ) ) { return $title; }
    $seo = eg_seo_data( $route );
    return ! empty( $seo['title'] ) ? $seo['title'] : $title;
}, 99 );

add_action( 'wp_head', function () {
    $route = eg_current_route();
    if ( ! $route || eg_seo_plugin_active() ) { return; }
    $seo = eg_seo_data( $route );
    if ( ! $seo ) { return; }
    $url = eg_route_url( $route );
    $title = $seo['title'] ?? get_bloginfo( 'name' );
    $image = get_theme_file_uri( 'assets/eg-share.jpg' );
    if ( ! empty( $seo['description'] ) ) { echo '<meta name="description" content="' . esc_attr( $seo['description'] ) . '">' . "\n"; }
    if ( ! is_singular() || ! empty( $GLOBALS['eg_route'] ) ) { echo '<link rel="canonical" href="' . esc_url( $url ) . '">' . "\n"; }
    echo '<meta property="og:type" content="website"><meta property="og:site_name" content="Entertainment Guarantors Malta"><meta property="og:title" content="' . esc_attr( $title ) . '"><meta property="og:url" content="' . esc_url( $url ) . '"><meta property="og:image" content="' . esc_url( $image ) . '">' . "\n";
    echo '<meta name="twitter:card" content="summary_large_image"><meta name="twitter:title" content="' . esc_attr( $title ) . '"><meta name="twitter:image" content="' . esc_url( $image ) . '">' . "\n";
    if ( 'home' === $route ) {
        echo '<script type="application/ld+json">' . wp_json_encode( array( '@context' => 'https://schema.org/', '@type' => 'WebSite', 'name' => 'Entertainment Guarantors Malta', 'url' => home_url( '/' ) ), JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
    }
}, 3 );

/** Add the theme-served original URLs to WordPress's XML sitemap (real pages with the same slug are already listed by WordPress). */
add_action( 'init', function () {
    if ( ! function_exists( 'wp_register_sitemap_provider' ) || ! class_exists( 'WP_Sitemaps_Provider' ) ) { return; }
    wp_register_sitemap_provider( 'eg', new class() extends WP_Sitemaps_Provider {
        public function __construct() { $this->name = 'eg'; $this->object_type = 'eg'; }
        public function get_url_list( $page_num, $object_subtype = '' ) {
            $urls = array( array( 'loc' => home_url( '/' ) ) );
            foreach ( array( 'solutions', 'about', 'contact' ) as $route ) {
                if ( ! get_page_by_path( $route ) ) { $urls[] = array( 'loc' => eg_route_url( $route ) ); }
            }
            return $urls;
        }
        public function get_max_num_pages( $object_subtype = '' ) { return 1; }
    } );
} );
