<?php
/**
 * Crosstable administration viewing panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

/** @var array  $teams */
/** @var object $league */
/** @var array  $crosstable_teams Hydrated League_Team rows in display order (pre-computed by Admin_League). */
/** @var array  $crosstable_cells Score matrix keyed by [ row team id ][ column team id ] (pre-computed by Admin_League). */
$crosstable_teams = $crosstable_teams ?? $teams;
$crosstable_cells = $crosstable_cells ?? array();
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
                foreach ( $crosstable_teams as $rank => $team ) {
                    ?>
                    <tr>
                        <th scope="row"><?php echo esc_html( $rank + 1 ); ?></th>
                        <td><?php echo esc_html( $team->title ); ?></td>
                        <?php
                        for ( $i = 1; $i <= $league->num_teams; $i++ ) {
                            $column_team_id = isset( $crosstable_teams[ $i - 1 ] ) ? (int) $crosstable_teams[ $i - 1 ]->id : null;
                            ?>
                            <td class="fixture"><?php echo $crosstable_cells[ (int) $team->id ][ $column_team_id ] ?? '&nbsp;'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
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
