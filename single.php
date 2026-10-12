<?php
get_header();
while ( have_posts() ) :
    the_post();
    eg_page_hero( get_the_title() );
    echo '<article class="eg-article">';
    the_content();
    echo '</article>';
endwhile;
get_footer();
