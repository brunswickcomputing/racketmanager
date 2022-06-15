<?php
/**
* How to category archives template
*
* @package Sydney
*/

get_header(); ?>

<div id="primary" class="content-area">
	<main id="main" class="post-wrap" role="main">

		<?php if ( have_posts() ) { ?>

			<header class="page-header">
				<h1 class="archive-title"><?php _e('How to', 'racketmanager'); ?></h1>
				<div class="taxonomy-description"><?php the_archive_description() ?></div>
			</header><!-- .page-header -->
			<div class="posts-layout">
				<div class="row" <?php sydney_masonry_data(); ?>>
					<?php while ( have_posts() ) {
						the_post();
						//Get the custom field values
						?>
						<?php get_template_part( 'content', get_post_format() ); ?>
					<?php } ?>
				</div>
			</div>
			<?php the_posts_navigation(); ?>

		<?php } else { ?>

			<?php get_template_part( 'content', 'none' ); ?>

		<?php } ?>

	</main><!-- #main -->
</div><!-- #primary -->

<?php get_footer(); ?>
