<?php
    /**
     * Rules category archives template
     *
     * @package Sydney
     */
    
    get_header(); ?>
    <div id="primary" class="content-area">
        <main id="main" class="post-wrap" role="main">
            <?php
            if ( have_posts() ) {
                ?>
                <?php
                while ( have_posts() ) {
                    the_post();
                    //Get the custom field values
                    ?>
                    <div>
                        <div class="name"><h2><?php the_title(); ?></h2></div>
                        <div class="content"><?php the_content(); ?></div>
                    </div>
                    <?php
                }
                ?>
                <?php the_posts_navigation(); ?>
                <?php
            } else {
                ?>
                <?php get_template_part( 'content', 'none' ); ?>
                <?php
            }
            ?>

        </main><!-- #main -->
    </div><!-- #primary -->
    <?php get_footer(); ?>
