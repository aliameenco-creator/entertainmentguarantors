<?php
/** Entertainment Guarantors theme. Pages are normal editable WordPress pages; settings and enquiries live in WordPress. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
require_once __DIR__ . '/includes/github-updater.php';
require_once __DIR__ . '/includes/media.php';
require_once __DIR__ . '/includes/content.php';
require_once __DIR__ . '/includes/layout.php';
require_once __DIR__ . '/includes/setup.php';
require_once __DIR__ . '/includes/seo.php';
require_once __DIR__ . '/includes/contact-form.php';

add_action( 'after_setup_theme', function () {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'custom-logo', array( 'height' => 116, 'width' => 433, 'flex-height' => true, 'flex-width' => true ) );
    add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
    add_theme_support( 'editor-styles' );
    add_theme_support( 'responsive-embeds' );
    add_editor_style( array( 'assets/fonts/fonts.css', 'assets/editor.css' ) );
    register_nav_menus( array( 'primary' => 'Main menu (header and footer)' ) );
} );

/** Button styles offered in the block editor, matching the site's buttons. */
add_action( 'init', function () {
    register_block_style( 'core/button', array( 'name' => 'eg-primary', 'label' => 'Gold' ) );
    register_block_style( 'core/button', array( 'name' => 'eg-ghost', 'label' => 'White outline' ) );
    register_block_style( 'core/button', array( 'name' => 'eg-outline', 'label' => 'Dark outline' ) );
} );

add_action( 'wp_enqueue_scripts', function () {
    $version = wp_get_theme()->get( 'Version' );
    wp_enqueue_style( 'eg-fonts', get_theme_file_uri( 'assets/fonts/fonts.css' ), array(), $version );
    wp_enqueue_style( 'eg-theme', get_theme_file_uri( 'assets/eg.css' ), array( 'eg-fonts' ), $version );
    wp_enqueue_script( 'eg-theme', get_theme_file_uri( 'assets/eg.js' ), array(), $version, array( 'strategy' => 'defer', 'in_footer' => true ) );
} );

/** Mark the document before first paint so animations never hide content when JavaScript is off; preload the main fonts. */
add_action( 'wp_head', function () {
    echo "<script>document.documentElement.classList.add('js')</script>\n";
    foreach ( array( 'instrument-serif-latin', 'manrope-latin' ) as $font ) {
        echo '<link rel="preload" href="' . esc_url( get_theme_file_uri( 'assets/fonts/' . $font . '.woff2' ) ) . '" as="font" type="font/woff2" crossorigin>' . "\n";
    }
    if ( ! has_site_icon() ) {
        $icon = esc_url( get_theme_file_uri( 'assets/eg-favicon.png' ) );
        echo '<link rel="icon" type="image/png" href="' . $icon . '"><link rel="apple-touch-icon" href="' . $icon . '">' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above.
    }
}, 1 );

add_filter( 'body_class', function ( $classes ) {
    $classes[] = eg_has_media_top() ? 'eg-media-top' : 'eg-plain-top';
    $route = eg_current_route();
    if ( $route ) { $classes[] = 'eg-page-' . $route; }
    return $classes;
} );

/** Content uses root-relative links; keep them valid on subdirectory installs. */
add_filter( 'the_content', function ( $content ) {
    return preg_replace_callback( '~href="(/(?!/)[^"<>]*)"~', function ( $m ) {
        return 'href="' . esc_url( home_url( $m[1] ) ) . '"';
    }, $content );
}, 20 );

/** The original Wix site also answered at /home (its canonical was /): send it to the homepage permanently. */
add_action( 'template_redirect', function () {
    if ( 'home' === eg_request_path() ) {
        wp_safe_redirect( home_url( '/' ), 301 );
        exit;
    }
}, 1 );
