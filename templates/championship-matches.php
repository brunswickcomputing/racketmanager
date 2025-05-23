<?php
/**
 * Template for Championship matches
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var object $league */
/** @var array $finals */
?>
<!-- Nav tabs -->
<ul class="nav nav-tabs frontend" id="myTab-<?php echo esc_html( $league->id ); ?>" role="tablist">
	<?php
	$i = 0;
	foreach ( $finals as $final ) {
		?>
		<li class="nav-item" role="presentation">
			<button class="nav-link <?php echo empty( $i ) ? 'active' : ''; ?>" id="final-<?php echo esc_html( $final->key ); ?>-<?php echo esc_html( $league->id ); ?>-tab" data-bs-toggle="pill" data-bs-target="#final-<?php echo esc_html( $final->key ); ?>-<?php echo esc_html( $league->id ); ?>" type="button" role="tab" aria-controls="final-<?php echo esc_html( $final->key ); ?>-<?php echo esc_html( $league->id ); ?>" aria-selected="true"><?php echo esc_html( $final->name ); ?></button>
		</li>
		<?php
		++$i;
	}
	?>
</ul>
<!-- Tab panes -->
<div class="tab-content">
	<?php
	$i = 0;
	foreach ( $finals as $final ) {
		?>
		<div class="tab-pane fade <?php echo empty( $i ) ? 'show active' : ''; ?>" id="final-<?php echo esc_html( $final->key ); ?>-<?php echo esc_html( $league->id ); ?>" role="tabpanel" aria-labelledby="final-<?php echo esc_html( $final->key ); ?>-<?php echo esc_html( $league->id ); ?>-tab">
			<?php
			$matches     = $final->matches;
			$show_header = false;
			require RACKETMANAGER_PATH . 'templates/includes/matches-team-list.php';
			?>
		</div>
		<?php
		++$i;
	}
	?>
</div>
