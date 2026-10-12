<?php
/** Theme-owned layout parts generated from the archived entertainmentguarantors.net site (tools/build_eg_theme.py). */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Resolve our controlled /assets/ tokens and root-relative links, on any (sub)directory install. */
function eg_theme_fragment( $html ) {
    $html = eg_media_swap( $html );
    $html = preg_replace_callback( '~(src|href|poster|data-hd)="(/assets/[^"<>]+)"~', function ( $m ) {
        return $m[1] . '="' . esc_url( get_theme_file_uri( ltrim( $m[2], '/' ) ) ) . '"';
    }, $html );
    $html = preg_replace_callback( '~url\((/assets/[^)"\'<>]+)\)~', function ( $m ) {
        return 'url(' . esc_url( get_theme_file_uri( ltrim( $m[1], '/' ) ) ) . ')';
    }, $html );
    return preg_replace_callback( '~href="(/(?!/)[^"<>]*)"~', function ( $m ) {
        return 'href="' . esc_url( home_url( $m[1] ) ) . '"';
    }, $html );
}

/** Path of a generated part. Only fixed names are accepted, never user-controlled paths. */
function eg_part_file( $name ) {
    if ( ! preg_match( '~^(?:header|footer|cta|home|about|solutions|contact)$~D', $name ) ) { return ''; }
    $file = get_theme_file_path( 'parts/' . $name . '.html' );
    return is_readable( $file ) ? $file : '';
}

function eg_theme_part( $name, $return = false ) {
    $file = eg_part_file( $name );
    if ( ! $file ) { return ''; }
    $html = eg_theme_fragment( (string) file_get_contents( $file ) );
    // Copyright year is always the current year (never a stale year copied from the old site).
    $html = str_replace( '<!--eg:year-->', esc_html( wp_date( 'Y' ) ), $html );
    if ( $return ) { return $html; }
    echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Generated theme HTML, not user input.
    return '';
}

/** Contact page: generated layout with the contact form placed at its marker. */
function eg_contact_page() {
    $html = eg_theme_part( 'contact', true );
    ob_start();
    eg_contact_form();
    return str_replace( '<!--eg:form-->', (string) ob_get_clean(), $html );
}

/** Simple title band for pages that have no designed layout (404, search, other pages). */
function eg_page_hero( $title ) {
    echo '<section class="inner-hero plain"><div class="wrap"><h1 class="reveal-title">' . esc_html( $title ) . '</h1></div></section>';
}

/** Request path relative to the site root, e.g. "contact" or "about". */
function eg_request_path() {
    $path = (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH ); // phpcs:ignore WordPress.Security.ValidationSanitization.InputNotSanitized -- Only compared with fixed strings.
    $base = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
    if ( $base && str_starts_with( $path, $base ) ) { $path = substr( $path, strlen( $base ) ); }
    return trim( rawurldecode( $path ), '/' );
}

/**
 * The original URLs /about, /solutions and /contact always show their designed layouts, whether or not a
 * WordPress page with that slug exists (the layouts are theme files, so they are never lost).
 */
function eg_virtual_route() {
    $path = eg_request_path();
    if ( ! in_array( $path, array( 'about', 'solutions', 'contact' ), true ) ) { return ''; }
    return ( is_404() || is_page( $path ) ) ? $path : '';
}
add_action( 'template_redirect', function () {
    $route = eg_virtual_route();
    if ( ! $route ) { return; }
    $GLOBALS['eg_route'] = $route;
    global $wp_query;
    if ( $wp_query->is_404 ) {
        $wp_query->is_404 = false;
        status_header( 200 );
    }
}, 0 );
add_filter( 'template_include', function ( $template ) {
    return empty( $GLOBALS['eg_route'] ) ? $template : get_theme_file_path( 'page-design.php' );
} );

function eg_current_route() {
    if ( is_front_page() ) { return 'home'; }
    return $GLOBALS['eg_route'] ?? '';
}
