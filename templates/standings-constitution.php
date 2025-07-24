<?php
/**
 * Standings table by status template
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var object $league */
/** @var array  $teams */
if ( ! empty( $teams ) ) {
    ?>
    <table class="table table-striped table-borderless" aria-describedby="<?php esc_html_e( 'Standing table', 'racketmanager' ); ?>" title="<?php esc_html_e( 'Standings', 'racketmanager' ) . ' ' . $league->title; ?>">
        <thead class="">
        <tr>
            <th><?php esc_html_e( 'Team', 'racketmanager' ); ?></th>
            <th><?php esc_html_e( 'Status', 'racketmanager' ); ?></th>
        </tr>
        </thead>
        <tbody>
            <?php
            foreach ( $teams as $team ) {
                ?>
                <tr class="table-dark">
                    <td>
                        <?php
                        if ( $team->is_withdrawn ) {
                            ?>
                            <s>
                            <?php
                        }
                        ?>
                        <?php echo esc_html( $team->title ); ?>
                        <?php
                        if ( $team->is_withdrawn ) {
                            ?>
                            </s>
                            <?php
                        }
                        ?>
                    </td>
                    <td class="num">
                        <?php echo esc_html( $team->status_text ); ?>
                    </td>
                </tr>
                <?php
            }
            ?>
        </tbody>
    </table>
    <?php
}
?>
