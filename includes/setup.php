<?php
/** One-click setup: permalinks that match the original URLs (/about, no trailing slash), site title, Media Library images. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function eg_setup_missing() {
    $missing = array();
    $structure = (string) get_option( 'permalink_structure' );
    if ( '' === $structure || '/%postname%/' === $structure ) { $missing['permalinks'] = 'use the post-name permalink structure without a trailing slash so /about, /solutions and /contact match the original addresses'; }
    if ( 'Entertainment Guarantors Malta' !== get_option( 'blogname' ) ) { $missing['title'] = 'set the site title to "Entertainment Guarantors Malta" (the original site name)'; }
    if ( eg_media_pending() ) { $missing['media'] = 'add the website images to the Media Library with descriptive file names and alt text (SEO)'; }
    return $missing;
}

add_action( 'admin_notices', function () {
    if ( ! current_user_can( 'manage_options' ) ) { return; }
    $missing = eg_setup_missing();
    if ( ! $missing ) { return; }
    $url = wp_nonce_url( admin_url( 'admin-post.php?action=eg_setup' ), 'eg_setup' );
    echo '<div class="notice notice-warning"><p><strong>Entertainment Guarantors setup:</strong> ' . esc_html( implode( '; ', $missing ) ) . '.</p><p><a class="button button-primary" href="' . esc_url( $url ) . '">Complete Entertainment Guarantors setup</a></p></div>';
} );

add_action( 'admin_post_eg_setup', function () {
    if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Not allowed.' ); }
    check_admin_referer( 'eg_setup' );
    $structure = (string) get_option( 'permalink_structure' );
    if ( '' === $structure || '/%postname%/' === $structure ) {
        global $wp_rewrite;
        $wp_rewrite->set_permalink_structure( '/%postname%' );
        flush_rewrite_rules( true );
    }
    if ( 'Entertainment Guarantors Malta' !== get_option( 'blogname' ) ) {
        update_option( 'blogname', 'Entertainment Guarantors Malta' );
        update_option( 'blogdescription', '' );
    }
    if ( eg_media_pending() ) { wp_safe_redirect( eg_media_import_url() ); exit; }
    wp_safe_redirect( home_url( '/' ) );
    exit;
} );
