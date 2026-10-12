<?php
/** Homepage: the editable Home page once setup has created it, otherwise the original homepage content. */
if ( is_page() ) {
    require get_theme_file_path( 'template-canvas.php' );
    return;
}
get_header();
echo eg_page_fallback( 'home' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Theme content rendered through the_content.
get_footer();
