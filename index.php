<?php
/** Posts, archives and search (the original site has no blog; this keeps any future posts readable). */
get_header();
eg_page_hero( is_search() ? 'Search: ' . get_search_query() : ( is_archive() ? wp_strip_all_tags( get_the_archive_title() ) : 'News' ) );
echo '<section class="section"><div class="wrap post-list">';
if ( have_posts() ) {
    while ( have_posts() ) {
        the_post();
        echo '<article class="post-card" data-reveal><time datetime="' . esc_attr( get_the_date( DATE_W3C ) ) . '">' . esc_html( get_the_date( 'j F Y' ) ) . '</time><h2><a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a></h2><p>' . esc_html( wp_trim_words( get_the_excerpt(), 32 ) ) . '</p></article>';
    }
    the_posts_pagination( array( 'mid_size' => 1 ) );
} else {
    echo '<p>Nothing found.</p>';
}
echo '</div></section>';
get_footer();
