<?php
/**
 * Template Name: Notitle
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

get_header( 'basic' );
?>
<div id="primary" class="content-area">
    <main id="main" class="site-main" role="main">

        <?php
        while ( have_posts() ) {
            the_post();
            ?>

            <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                <header class="entry-header">
                </header><!-- .entry-header -->

                <div class="entry-content">
                    <?php the_content(); ?>
                    <?php
                    wp_link_pages(
                        array(
                            // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch -- Preserve Sydney theme translations.
                            'before' => '<div class="page-links">' . __( 'Pages:', 'sydney' ),
                            'after'  => '</div>',
                        )
                    );
                    ?>
                </div><!-- .entry-content -->

                <footer class="entry-footer">
                    <?php
                    // phpcs:ignore WordPress.WP.I18n.TextDomainMismatch -- Preserve Sydney theme translations.
                    edit_post_link( __( 'Edit', 'sydney' ), '<span class="edit-link">', '</span>' );
                    ?>
                </footer><!-- .entry-footer -->
            </article><!-- #post-## -->

            <?php

            if ( comments_open() || 0 < (int) get_comments_number() ) {

                comments_template();

            }
            ?>

        <?php } // end of the loop. ?>


    </main><!-- #main -->
</div><!-- #primary -->

<?php get_footer( 'basic' ); ?>
