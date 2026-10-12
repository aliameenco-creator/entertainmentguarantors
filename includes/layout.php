<?php
/** Header, footer, menus, routes and Customizer settings. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Original menu (labels, order, addresses), used until a menu is assigned under Appearance > Menus. */
function eg_default_menu_items() {
    return array( array( 'Home', '/' ), array( 'Services', '/solutions' ), array( 'Our Team and Partners', '/about' ), array( 'Contact', '/contact' ) );
}

function eg_menu( $class = 'menu' ) {
    if ( has_nav_menu( 'primary' ) ) {
        wp_nav_menu( array( 'theme_location' => 'primary', 'container' => false, 'menu_class' => $class, 'depth' => 2, 'fallback_cb' => false ) );
        return;
    }
    echo '<ul class="' . esc_attr( $class ) . '">';
    foreach ( eg_default_menu_items() as $item ) {
        echo '<li class="menu-item"><a href="' . esc_url( home_url( $item[1] ) ) . '">' . esc_html( $item[0] ) . '</a></li>';
    }
    echo '</ul>';
}

function eg_logo( $class = 'brand' ) {
    $custom = get_theme_mod( 'custom_logo' );
    $src = $custom ? wp_get_attachment_image_url( $custom, 'full' ) : eg_media_url( eg_theme_media( 'logo' ) );
    echo '<a class="' . esc_attr( $class ) . '" href="' . esc_url( home_url( '/' ) ) . '" aria-label="' . esc_attr__( 'Entertainment Guarantors home', 'entertainment-guarantors' ) . '"><img src="' . esc_url( $src ) . '" alt="Entertainment Guarantors - Supporting the Film Industry" width="433" height="116"></a>';
}

/** Footer contacts, editable under Appearance > Customize > Footer contacts (defaults are the original site's). */
function eg_people_defaults() {
    return array(
        1 => array( 'Barry Filby', '07704 263577', 'https://www.entertainmentguarantors.london/', 'barry@entertainmentguarantors.london' ),
        2 => array( 'Stephen Naulls', '07941 334879', 'https://www.entertainmentguarantors.london/', 'stephen@entertainmentguarantors.london' ),
    );
}

function eg_people() {
    $people = array();
    foreach ( eg_people_defaults() as $n => $d ) {
        $people[] = array(
            'name'  => get_theme_mod( "eg_person{$n}_name", $d[0] ),
            'phone' => get_theme_mod( "eg_person{$n}_phone", $d[1] ),
            'link'  => get_theme_mod( "eg_person{$n}_phone_link", $d[2] ),
            'email' => get_theme_mod( "eg_person{$n}_email", $d[3] ),
        );
    }
    return array_filter( $people, function ( $p ) { return '' !== trim( $p['name'] . $p['phone'] . $p['email'] ); } );
}

add_action( 'customize_register', function ( $wp_customize ) {
    $wp_customize->add_section( 'eg_footer', array( 'title' => 'Footer contacts', 'priority' => 120, 'description' => 'The two contacts shown in the footer of every page. A phone link may be a website address (as on the original site) or tel:+44...' ) );
    $fields = array( 'name' => 'Name', 'phone' => 'Phone (as shown)', 'phone_link' => 'Phone link', 'email' => 'Email' );
    foreach ( array( 1, 2 ) as $n ) {
        foreach ( $fields as $field => $label ) {
            $id = "eg_person{$n}_{$field}";
            $sanitize = 'email' === $field ? 'sanitize_email' : ( 'phone_link' === $field ? 'esc_url_raw' : 'sanitize_text_field' );
            $wp_customize->add_setting( $id, array( 'default' => eg_people_defaults()[ $n ][ array_search( $field, array_keys( $fields ), true ) ], 'sanitize_callback' => $sanitize ) );
            $wp_customize->add_control( $id, array( 'label' => "Contact {$n}: {$label}", 'section' => 'eg_footer', 'type' => 'email' === $field ? 'email' : ( 'phone_link' === $field ? 'url' : 'text' ) ) );
        }
    }
} );

/** Request path relative to the site root, e.g. "contact" or "about". */
function eg_request_path() {
    $path = (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH ); // phpcs:ignore WordPress.Security.ValidationSanitization.InputNotSanitized -- Only compared with fixed strings.
    $base = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
    if ( $base && str_starts_with( $path, $base ) ) { $path = substr( $path, strlen( $base ) ); }
    return trim( rawurldecode( $path ), '/' );
}

/** Until setup has created the pages, the original URLs show the original content instead of "not found". */
function eg_virtual_route() {
    if ( ! is_404() ) { return ''; }
    $path = eg_request_path();
    return in_array( $path, array( 'about', 'solutions', 'contact' ), true ) ? $path : '';
}
add_action( 'template_redirect', function () {
    $route = eg_virtual_route();
    if ( ! $route ) { return; }
    $GLOBALS['eg_route'] = $route;
    global $wp_query;
    $wp_query->is_404 = false;
    status_header( 200 );
}, 0 );
add_filter( 'template_include', function ( $template ) {
    return empty( $GLOBALS['eg_route'] ) ? $template : get_theme_file_path( 'template-canvas.php' );
} );

/** Route key for SEO data: home, solutions, about, contact (pages created by setup, or the fallback routes). */
function eg_current_route() {
    if ( is_front_page() ) { return 'home'; }
    if ( ! empty( $GLOBALS['eg_route'] ) ) { return $GLOBALS['eg_route']; }
    if ( is_page() ) {
        $key = (string) get_post_meta( get_queried_object_id(), '_eg_source_key', true );
        if ( in_array( $key, array( 'solutions', 'about', 'contact' ), true ) ) { return $key; }
    }
    return '';
}

/** Header sits over a photo/video at the top of the page (transparent) or on a plain page (solid). */
function eg_has_media_top() {
    if ( ! empty( $GLOBALS['eg_route'] ) ) { return 'contact' !== $GLOBALS['eg_route']; }
    if ( is_front_page() && ! is_page() ) { return true; }
    if ( ! is_singular() ) { return false; }
    $start = substr( (string) get_post_field( 'post_content', get_queried_object_id() ), 0, 600 );
    return str_contains( $start, 'eg-hero' ) || str_contains( $start, 'eg-banner' );
}

function eg_page_hero( $title ) {
    echo '<section class="eg-plain-hero"><div class="eg-wrap"><h1>' . esc_html( $title ) . '</h1></div></section>';
}
