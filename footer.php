</main>
<footer class="footer">
    <div class="footer-top">
        <div class="footer-brand"><?php eg_logo( 'footer-logo' ); ?></div>
        <nav class="footer-col footer-nav" aria-label="Footer navigation">
            <p class="footer-label">Menu</p>
            <?php eg_menu( 'footer-menu' ); ?>
        </nav>
        <?php foreach ( eg_people() as $eg_person ) : ?>
            <div class="footer-col footer-person">
                <p class="footer-label"><?php echo esc_html( $eg_person['name'] ); ?></p>
                <?php if ( $eg_person['phone'] ) : ?>
                    <?php if ( $eg_person['link'] ) : ?>
                        <a href="<?php echo esc_url( $eg_person['link'] ); ?>"<?php echo str_starts_with( $eg_person['link'], 'http' ) ? ' target="_blank" rel="noopener"' : ''; ?>><?php echo esc_html( $eg_person['phone'] ); ?></a>
                    <?php else : ?>
                        <span><?php echo esc_html( $eg_person['phone'] ); ?></span>
                    <?php endif; ?>
                <?php endif; ?>
                <?php if ( $eg_person['email'] ) : ?><a href="mailto:<?php echo esc_attr( $eg_person['email'] ); ?>"><?php echo esc_html( $eg_person['email'] ); ?></a><?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="footer-bottom">
        <p class="copyright">&copy; <?php echo esc_html( wp_date( 'Y' ) ); ?> Entertainment Guarantors</p>
        <a class="to-top" href="#main">Back to top <span aria-hidden="true">&uarr;</span></a>
    </div>
</footer>
<?php wp_footer(); ?>
</body></html>
