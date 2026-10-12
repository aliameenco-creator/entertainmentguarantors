<?php
/**
 * Original pages served with their designed layouts: /about, /solutions and /contact. Used whether or not a
 * WordPress page with that slug exists, so the original URLs always show the designed page.
 */
get_header();
$eg_route = eg_current_route();
if ( 'contact' === $eg_route ) {
    echo eg_contact_page(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Generated theme markup plus the escaped form.
} else {
    eg_theme_part( $eg_route );
}
if ( 'contact' !== $eg_route ) { eg_theme_part( 'cta' ); }
get_footer();
