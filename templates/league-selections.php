<?php
/**
 * Template for league selections
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

?>
<div id="racketmanager_archive_selections" class="mt-3">
	<form method="get" action="<?php echo esc_url( get_permalink( $post_id ) ); ?>" id="racketmanager_archive">
		<div class="row g-1 align-items-center">
			<input type="hidden" name="page_id" value="<?php echo esc_attr( $post_id ); ?>" />
			<div class="form-floating col-auto">
				<select class="form-select" size="1" name="league_id" id="league_id">
					<?php
					foreach ( $leagues as $l ) {
						?>
						<option value="<?php echo esc_attr( seo_url( $l->title ) ); ?>"
							<?php
							if ( $l->id === $league->id ) {
								echo ' selected="selected"';
							}
							?>
						><?php echo esc_html( $l->title ); ?></option>
						<?php
					}
					?>
				</select>
				<label for="league_id"><?php esc_html_e( 'League', 'racketmanager' ); ?></label>
			</div>
		</div>
	</form>
</div>
