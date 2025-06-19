<?php
/**
 * Crosstable administration viewing panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

/** @var array  $teams */
/** @var object $league */
?>
<div class="container">
    <div class="table-responsive">
        <table class="table table-striped table-borderless" aria-describedby="<?php esc_html_e( 'Crosstable', 'racketmanager' ); ?> <?php echo esc_html( $league->title ); ?>">
            <thead class="">
                <tr>
                    <th colspan="2" scope="col"><?php esc_html_e( 'Team', 'racketmanager' ); ?></th>
                    <?php
                    for ( $i = 1; $i <= $league->num_teams; $i++ ) {
                        ?>
                        <th class="fixture" scope="col"><?php echo esc_html( $i ); ?></th>
                        <?php
                    }
                    ?>
                <tr>
            </thead>
            <tbody>
                <?php
                foreach ( $teams as $rank => $team ) {
                    $team = get_league_team( $team );
                    ?>
                    <tr>
                        <th scope="row"><?php echo esc_html( $rank + 1 ); ?></th>
                        <td><?php echo esc_html( $team->title ); ?></td>
                        <?php
                        for ( $i = 1; $i <= $league->num_teams; $i++ ) {
                            ?>
                            <td class="fixture"><?php echo $league->get_crosstable_field( $team->id, $teams[ $i - 1 ]->id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
                            <?php
                        }
                        ?>
                    </tr>
                    <?php
                }
                ?>
            </tbody>
        </table>
    </div>
</div>
