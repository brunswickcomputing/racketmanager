<?php
/**
 * Crosstable template
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var object $league */
/** @var array  $teams */
?>
<div class="module module--card">
	<div class="module__banner">
		<h3 class="module__title"><?php esc_html_e( 'Crosstable', 'racketmanager' ); ?></h3>
	</div>
	<div class="module__content">
		<div class="module-container">
			<?php
            if ( empty( $teams ) ) {
	            esc_html_e( 'No teams found', 'racketmanager' );
            } else {
                ?>
                <div class="table-responsive">
                    <table class='table table-striped table-borderless align-middle' aria-describedby='<?php esc_html_e( 'Crosstable', 'racketmanager' ); ?> <?php echo esc_html( $league->title ); ?>'>
                        <thead class="">
                            <tr>
                                <th colspan='2' class="team" scope="col"><?php esc_html_e( 'Team', 'racketmanager' ); ?></th>
                                <?php
                                $num_teams = count( $teams );
                                for ( $i = 1; $i <= $num_teams; $i++ ) {
                                    ?>
                                    <th class="fixture" scope="col"><?php echo esc_html( $i ); ?></th>
                                    <?php
                                }
                                ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            foreach ( $teams as $team ) {
                                ?>
                                <tr>
                                    <th scope="row" class="rank"><?php echo esc_html( $team->rank ); ?></th>
                                    <td><?php echo esc_html( $team->title ); ?></td>
		                            <?php
		                            for ( $i = 1; $i <= $num_teams; $i++ ) {
			                            ?>
			                            <td><?php echo $league->get_crosstable_field( $team->id, $league->teams[ $i - 1 ]->id ); ?></td>
			                            <?php
		                            }
		                            ?>
                                </tr>
                                <?php
                            }
                            ?>
                        </tbody>
                </table>
                <?php
            }
            ?>
		</div>
	</div>
</div>
