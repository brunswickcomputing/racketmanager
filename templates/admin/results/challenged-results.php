<?php
/**
 * Challenged Results administration panel
 *
 * @package Racketmanager_admin
 */

namespace Racketmanager;

use Racketmanager\Admin\View_Models\Admin_Results_Match_View_Model;

/** @var Admin_Results_Match_View_Model[] $challenged_results_matches */
$challenged_results_matches = $challenged_results_matches ?? array();
$prev_league                = 0;
?>
<table class="table table-striped">
    <thead class="table-dark">
    <tr>
        <th class=""><?php esc_html_e( 'Date', 'racketmanager' ); ?></th>
        <th class=""><?php esc_html_e( 'Match', 'racketmanager' ); ?></th>
        <th class=""><?php esc_html_e( 'Status', 'racketmanager' ); ?></th>
        <th class=""><?php esc_html_e( 'Score', 'racketmanager' ); ?></th>
        <th></th>
    </tr>
    </thead>
    <tbody>
    <?php
    if ( $challenged_results_matches ) {
        foreach ( $challenged_results_matches as $row ) {
            if ( $prev_league !== $row->league_id ) {
                $prev_league = $row->league_id;
                ?>
                <tr><td colspan="5" class="fw-bold fst-italic"><?php echo esc_html( $row->league_title ); ?></td></tr>
                <?php
            }
            ?>
            <tr>
                <td class=""><?php echo esc_html( $row->formatted_date ); ?></td>
                <td class="match-title"><a href="<?php echo esc_url( $row->match_link ); ?>"><?php echo esc_html( $row->match_title ); ?></a></td>
                <td class=""><?php echo esc_html( $row->confirmed_display ); ?></td>
                <td class=""><?php echo esc_html( $row->score ); ?></td>
                <td class=""><a href="<?php echo esc_url( $row->result_link ); ?>" class="btn btn-secondary"><?php esc_html_e( 'View result', 'racketmanager' ); ?></a></td>
            </tr>
            <?php
        }
    } else {
        ?>
        <tr><td colspan="5"><?php esc_html_e( 'No results found for criteria', 'racketmanager' ); ?></td></tr>
        <?php
    }
    ?>
    </tbody>
</table>
