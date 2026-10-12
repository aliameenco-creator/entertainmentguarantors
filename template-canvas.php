<?php
/**
 * Template Name: Designed page (full width)
 *
 * Full-width block content using the theme's section styles (hero, cards, banners...). Used by the Home,
 * Services, Our Team and Partners and Contact pages; choose it for any new page built from those blocks.
 */
get_header();
if ( ! empty( $GLOBALS['eg_route'] ) ) {
    echo eg_page_fallback( $GLOBALS['eg_route'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Theme content rendered through the_content.
} else {
    while ( have_posts() ) {
        the_post();
        echo '<div class="eg-canvas">';
        the_content();
        echo '</div>';
    }
}
get_footer();
