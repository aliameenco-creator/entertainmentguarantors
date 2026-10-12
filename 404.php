<?php get_header(); eg_page_hero( 'Page not found' ); ?>
<section class="section"><div class="wrap"><p>The page you were looking for has moved or no longer exists.</p><div class="actions"><a class="button" href="<?php echo esc_url( home_url( '/' ) ); ?>">Home<span class="arrow" aria-hidden="true"></span></a><a class="button outline" href="<?php echo esc_url( home_url( '/contact' ) ); ?>">Contact Us<span class="arrow" aria-hidden="true"></span></a></div></div></section>
<?php get_footer(); ?>
