<?php
/**
 * Template for league selections
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

?>
<div id="racketmanager_archive_selections" class="">
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
			<div class="form-floating col-auto">
				<select class="form-select" size="1" name="season" id="season">
					<?php
					foreach ( array_reverse( $seasons ) as $key => $season ) {
						?>
						<option value="<?php echo esc_attr( $season['name'] ); ?>"
							<?php
							if ( $season['name'] === $league->current_season['name'] ) {
								echo ' selected="selected"';
							}
							?>
						><?php echo esc_html( $season['name'] ); ?></option>
						<?php
					}
					?>
				</select>
				<label for="season"><?php esc_html_e( 'Season', 'racketmanager' ); ?></label>
			</div>
			<div class="col-auto">
				<input type="submit" class="submit" value="<?php esc_html_e( 'Show', 'racketmanager' ); ?>" />
			</div>
		</div>
	</form>
</div>
