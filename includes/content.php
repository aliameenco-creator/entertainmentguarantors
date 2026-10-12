<?php
/**
 * Editable pages. parts/pages.json holds the original Home, Services, Our Team and Partners and Contact pages
 * as block-editor content. Setup creates them as normal WordPress pages (edit them under Pages); until they
 * exist the same content is shown at the original URLs, so the site is never empty.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function eg_page_records() {
    static $pages = null;
    if ( null === $pages ) {
        $file = get_theme_file_path( 'parts/pages.json' );
        $pages = is_readable( $file ) ? (array) json_decode( (string) file_get_contents( $file ), true ) : array();
    }
    return $pages;
}

function eg_page_record( $key ) {
    foreach ( eg_page_records() as $record ) {
        if ( $record['key'] === $key ) { return $record; }
    }
    return null;
}

/** Theme-media key ('logo', 'hero') => manifest key. */
function eg_theme_media( $name ) {
    static $map = null;
    if ( null === $map ) {
        $file = get_theme_file_path( 'parts/theme-media.json' );
        $map = is_readable( $file ) ? (array) json_decode( (string) file_get_contents( $file ), true ) : array();
    }
    return $map[ $name ] ?? '';
}

/** URL of a manifest image: the Media Library copy once imported, otherwise the bundled file. */
function eg_media_url( $key ) {
    $id = eg_media_ids()[ $key ] ?? 0;
    $url = $id ? wp_get_attachment_image_url( $id, 'full' ) : '';
    if ( $url ) { return $url; }
    foreach ( eg_media_manifest() as $m ) {
        if ( $m['key'] === $key ) { return get_theme_file_uri( $m['file'] ); }
    }
    return '';
}

/** A core/image block for one manifest image (attachment ID when it is in the Media Library). */
function eg_image_block( $key, $alt, $class ) {
    $id = (int) ( eg_media_ids()[ $key ] ?? 0 );
    $data = $id ? array( 'id' => $id, 'sizeSlug' => 'full', 'linkDestination' => 'none' ) : array( 'sizeSlug' => 'full', 'linkDestination' => 'none' );
    if ( $class ) { $data['className'] = $class; }
    $img = '<img src="' . esc_url( eg_media_url( $key ) ) . '" alt="' . esc_attr( $alt ) . '"' . ( $id ? ' class="wp-image-' . $id . '"' : '' ) . '/>';
    return '<!-- wp:image ' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . " -->\n" . '<figure class="wp-block-image size-full' . ( $class ? ' ' . esc_attr( $class ) : '' ) . '">' . $img . "</figure>\n<!-- /wp:image -->";
}

function eg_resolve_content( $content ) {
    return preg_replace_callback( '~<!--eg:image (\{.*?\})-->~', function ( $m ) {
        $data = json_decode( $m[1], true );
        return is_array( $data ) ? eg_image_block( (string) $data['key'], (string) $data['alt'], (string) $data['class'] ) : '';
    }, $content );
}

/** The original page content, rendered (used before setup has created the WordPress pages). */
function eg_page_fallback( $key ) {
    $record = eg_page_record( $key );
    if ( ! $record ) { return ''; }
    return '<div class="eg-canvas">' . apply_filters( 'the_content', eg_resolve_content( $record['content'] ) ) . '</div>'; // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core filter.
}

/** Create the four original pages (never overwrites: existing pages or slugs are skipped), menu and homepage. */
function eg_create_pages() {
    $report = array( 'created' => array(), 'skipped' => array(), 'failed' => array() );
    $seo = (array) json_decode( (string) file_get_contents( get_theme_file_path( 'parts/seo.json' ) ), true );
    $ids = array();
    foreach ( eg_page_records() as $record ) {
        $existing = get_posts( array( 'post_type' => 'page', 'post_status' => 'any', 'meta_key' => '_eg_source_key', 'meta_value' => $record['key'], 'numberposts' => 1, 'fields' => 'ids' ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
        $collision = get_page_by_path( $record['slug'], OBJECT, 'page' );
        if ( $existing || $collision ) {
            $ids[ $record['key'] ] = $existing ? (int) $existing[0] : (int) $collision->ID;
            $report['skipped'][] = $record['title'];
            continue;
        }
        $id = wp_insert_post( wp_slash( array(
            'post_type'     => 'page',
            'post_status'   => 'publish',
            'post_title'    => $record['title'],
            'post_name'     => $record['slug'],
            'post_content'  => eg_resolve_content( $record['content'] ),
            'page_template' => 'template-canvas.php',
            'meta_input'    => array(
                '_eg_source_key'     => $record['key'],
                // Original page titles for Yoast SEO (used only if Yoast is installed).
                '_yoast_wpseo_title' => $seo[ $record['key'] ]['title'] ?? '',
            ),
        ) ), true );
        if ( is_wp_error( $id ) ) { $report['failed'][ $record['title'] ] = $id->get_error_message(); continue; }
        $ids[ $record['key'] ] = (int) $id;
        $report['created'][] = $record['title'];
    }
    if ( ! empty( $ids['home'] ) && ( 'page' !== get_option( 'show_on_front' ) || ! get_option( 'page_on_front' ) ) ) {
        update_option( 'show_on_front', 'page' );
        update_option( 'page_on_front', $ids['home'] );
    }
    if ( ! has_nav_menu( 'primary' ) ) {
        $menu = wp_get_nav_menu_object( 'Main menu' );
        $menu_id = $menu ? $menu->term_id : wp_create_nav_menu( 'Main menu' );
        if ( ! is_wp_error( $menu_id ) ) {
            if ( ! wp_get_nav_menu_items( $menu_id ) ) {
                foreach ( array( 'home', 'solutions', 'about', 'contact' ) as $key ) {
                    $record = eg_page_record( $key );
                    $item = ! empty( $ids[ $key ] )
                        ? array( 'menu-item-title' => $record['title'], 'menu-item-object' => 'page', 'menu-item-object-id' => $ids[ $key ], 'menu-item-type' => 'post_type', 'menu-item-status' => 'publish' )
                        : array( 'menu-item-title' => $record['title'], 'menu-item-url' => home_url( 'home' === $key ? '/' : '/' . $record['slug'] ), 'menu-item-type' => 'custom', 'menu-item-status' => 'publish' );
                    wp_update_nav_menu_item( $menu_id, 0, $item );
                }
            }
            $locations = get_theme_mod( 'nav_menu_locations', array() );
            $locations['primary'] = (int) $menu_id;
            set_theme_mod( 'nav_menu_locations', $locations );
        }
    }
    update_option( 'eg_pages_report', $report, false );
    return $report;
}

function eg_pages_missing() {
    foreach ( eg_page_records() as $record ) {
        $found = get_posts( array( 'post_type' => 'page', 'post_status' => 'any', 'meta_key' => '_eg_source_key', 'meta_value' => $record['key'], 'numberposts' => 1, 'fields' => 'ids' ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
        if ( ! $found && ! get_page_by_path( $record['slug'], OBJECT, 'page' ) ) { return true; }
    }
    return false;
}

/** [eg_hero_media]: the homepage hero still image and looping video (lighter file on phones). */
add_shortcode( 'eg_hero_media', function () {
    $still = esc_url( eg_media_url( eg_theme_media( 'hero' ) ) );
    $hd = esc_url( get_theme_file_uri( 'assets/video/hero-1080.mp4' ) );
    $sd = esc_url( get_theme_file_uri( 'assets/video/hero-720.mp4' ) );
    return '<div class="eg-hero-media" aria-hidden="true"><img class="eg-hero-still" src="' . $still . '" alt="" width="1920" height="1080" fetchpriority="high" decoding="async">'
        . '<video class="eg-hero-video" muted loop playsinline preload="none" poster="' . $still . '" data-hd="' . $hd . '"><source src="' . $sd . '" type="video/mp4"></video></div>';
} );

/** [eg_contact_form]: the contact form (fields in parts/contact-form.json). */
add_shortcode( 'eg_contact_form', function () {
    ob_start();
    eg_contact_form();
    return (string) ob_get_clean();
} );
