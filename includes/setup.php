<?php
/**
 * One-click setup: permalinks that match the original URLs (/about, no trailing slash), site title,
 * Media Library images, then the editable pages, main menu and homepage. Safe to run again: existing pages
 * and edits are never overwritten.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Permalinks must end without a slash so pages live at /about, /solutions, /contact (the original addresses). */
function eg_permalinks_need_change() {
    $structure = (string) get_option( 'permalink_structure' );
    return '' === $structure || str_ends_with( $structure, '/' );
}

/** Changing the structure is safe when it is plain or post-name, or when no published posts would move. */
function eg_permalinks_safe_to_change() {
    $structure = (string) get_option( 'permalink_structure' );
    if ( in_array( $structure, array( '', '/%postname%/' ), true ) ) { return true; }
    // The original site has no blog; WordPress's sample "Hello world!" post does not count.
    $posts = get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'numberposts' => 2, 'fields' => 'ids' ) );
    return ! $posts || ( 1 === count( $posts ) && 'hello-world' === get_post_field( 'post_name', $posts[0] ) );
}

function eg_setup_missing() {
    $missing = array();
    if ( eg_permalinks_need_change() ) {
        $missing['permalinks'] = eg_permalinks_safe_to_change()
            ? 'use post-name permalinks without a trailing slash so /about, /solutions and /contact match the original addresses'
            : 'Settings > Permalinks: choose a structure without a trailing slash (e.g. /%postname%) so pages match the original addresses (not changed automatically because published posts would move)';
    }
    if ( 'Entertainment Guarantors Malta' !== get_option( 'blogname' ) ) { $missing['title'] = 'set the site title to "Entertainment Guarantors Malta"'; }
    if ( eg_media_pending() ) { $missing['media'] = 'add the website images to the Media Library'; }
    if ( eg_pages_missing() ) { $missing['pages'] = 'create the editable Home, Services, Our Team and Partners and Contact pages'; }
    return $missing;
}

function eg_setup_url() {
    return add_query_arg( array( 'action' => 'eg_setup', '_wpnonce' => wp_create_nonce( 'eg_setup' ) ), admin_url( 'admin-post.php' ) );
}

add_action( 'admin_notices', function () {
    if ( ! current_user_can( 'manage_options' ) ) { return; }
    $report = get_option( 'eg_pages_report' );
    if ( isset( $_GET['eg_setup'] ) && is_array( $report ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $parts = array();
        if ( $report['created'] ) { $parts[] = 'created ' . implode( ', ', $report['created'] ); }
        if ( $report['skipped'] ) { $parts[] = 'kept existing ' . implode( ', ', $report['skipped'] ); }
        if ( $report['failed'] ) { $parts[] = 'could not create ' . implode( ', ', array_keys( $report['failed'] ) ); }
        echo '<div class="notice notice-success is-dismissible"><p><strong>Entertainment Guarantors setup complete:</strong> ' . esc_html( implode( '; ', $parts ) ) . '. Edit the pages under Pages, the menu under Appearance &gt; Menus and the footer contacts under Appearance &gt; Customize.</p></div>';
    }
    $missing = eg_setup_missing();
    if ( ! $missing ) { return; }
    echo '<div class="notice notice-warning"><p><strong>Entertainment Guarantors setup:</strong> ' . esc_html( implode( '; ', $missing ) ) . '.</p><p><a class="button button-primary" href="' . esc_url( eg_setup_url() ) . '">Complete Entertainment Guarantors setup</a></p></div>';
} );

add_action( 'admin_post_eg_setup', function () {
    if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Not allowed.' ); }
    check_admin_referer( 'eg_setup' );
    if ( eg_permalinks_need_change() && eg_permalinks_safe_to_change() ) {
        global $wp_rewrite;
        $wp_rewrite->set_permalink_structure( '/%postname%' );
    }
    if ( 'Entertainment Guarantors Malta' !== get_option( 'blogname' ) ) {
        update_option( 'blogname', 'Entertainment Guarantors Malta' );
        update_option( 'blogdescription', '' );
    }
    // Images first, so the pages are created with Media Library images; the import returns here when done.
    if ( eg_media_pending() ) {
        update_option( 'eg_setup_continue', 1, false );
        wp_safe_redirect( eg_media_import_url() );
        exit;
    }
    delete_option( 'eg_setup_continue' );
    eg_create_pages();
    flush_rewrite_rules( true );
    wp_safe_redirect( admin_url( 'edit.php?post_type=page&eg_setup=done' ) );
    exit;
} );
