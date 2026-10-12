<?php get_header(); eg_page_hero( 'Page not found' ); ?>
<section class="eg-article"><p>The page you were looking for has moved or no longer exists.</p><div class="wp-block-buttons eg-actions"><div class="wp-block-button is-style-eg-primary"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a></div><div class="wp-block-button is-style-eg-outline"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/contact' ) ); ?>">Contact Us</a></div></div></section>
<?php get_footer(); ?>
