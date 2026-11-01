<?php
declare( strict_types=1 );
/**
 * Event matches administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

/** @var array $matches */
/** @var \Racketmanager\Admin\View_Models\League_Matches_Page_View_Model|\stdClass $vm */
$matches = $matches ?? $vars['matches'] ?? $vm->matches;
?>
<div class="container">
    <table class="table table-striped">
        <thead class="table-dark">
        <tr>
            <th><?php esc_html_e( 'Round', 'racketmanager' ); ?></th>
            <th><?php esc_html_e( 'Date', 'racketmanager' ); ?></th>
            <th><?php esc_html_e( 'Match', 'racketmanager' ); ?></th>
            <th><?php esc_html_e( 'League', 'racketmanager' ); ?></th>
        </tr>
    <?php
    if ( $matches ) {
        ?>
        <tbody class="table table-striped">
        <?php
        $match_day = '';
        foreach ( $matches as $match ) {
            if ( $match->match_day !== $match_day ) {
                $match_day = $match->match_day;
                ?>
                <tr>
                    <td colspan="4"><?php echo __( 'Match Day', 'racketmanager' ) . ' matches.php' . esc_html( $match_day ); ?></td>
                </tr>
                <?php
            }
            ?>
            <tr>
                <td></td>
                <td><?php echo esc_html( $match->date ); ?></td>
                <td><?php echo esc_html( $match->match_title ); ?></td>
                <td><?php echo esc_html( $match->league->title ?? '' ); ?></td>
            </tr>
            <?php
        }
        ?>
        </tbody>
        <?php
    }
    ?>
    </table>
</div>
