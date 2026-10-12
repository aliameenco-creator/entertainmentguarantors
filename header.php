<!doctype html>
<html <?php language_attributes(); ?>>
<head><meta charset="<?php bloginfo( 'charset' ); ?>"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="theme-color" content="#0d0d0f"><?php wp_head(); ?></head>
<body <?php body_class(); ?>><?php wp_body_open(); ?>
<a class="skip" href="#main">Skip to main content</a>
<header class="site-header" data-header>
    <div class="header-inner">
        <?php eg_logo( 'brand' ); ?>
        <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="navigation"><span class="bars" aria-hidden="true"></span><span class="sr">Menu</span></button>
        <nav class="nav" id="navigation" aria-label="Main navigation">
            <?php eg_menu( 'menu' ); ?>
            <a class="nav-contact" href="<?php echo esc_url( home_url( '/contact' ) ); ?>">Contact</a>
        </nav>
    </div>
</header>
<main id="main">
