<?php
/**
 * Member account template.
 *
 * @package Racketmnanager/Templates
 *
 * Template used for member account form.
 *
 * @since Version 3 digits
 */

namespace Racketmanager;

require_once RACKETMANAGER_PATH . 'templates/includes/member-account-update.php';

get_header();
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
							'before' => '<div class="page-links">' . __( 'Pages:', 'racketmanager' ),
							'after'  => '</div>',
						)
					);
					?>
				</div><!-- .entry-content -->

				<footer class="entry-footer">
					<?php edit_post_link( __( 'Edit', 'racketmanager' ), '<span class="edit-link">', '</span>' ); ?>
				</footer><!-- .entry-footer -->
			</article><!-- #post-## -->

			<?php

			if ( comments_open() || '0' !== get_comments_number() ) {

				comments_template();

			}
			?>

		<?php } // end of the loop. ?>


	</main><!-- #main -->
</div><!-- #primary -->

<?php get_footer(); ?>
