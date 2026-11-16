<?php
/**
 * Rules category archives template
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

get_header(); ?>
<div id="primary" class="content-area">
    <main id="main" class="post-wrap" role="main">
        <?php
        if ( have_posts() ) {
            while ( have_posts() ) {
                the_post();
                // Get the custom field values.
                ?>
                <div>
                    <div class="name"><h2><?php the_title(); ?></h2></div>
                    <div class="content"><?php the_content(); ?></div>
                </div>
                <?php
            }
            the_posts_navigation();
        } else {
            get_template_part( 'content', 'none' );
        }
        ?>

    </main><!-- #main -->
</div><!-- #primary -->
<?php get_footer(); ?>
