<?php
/**
 * Pending tournament results administration panel
 *
 * @package Racketmanager_admin
 */

namespace Racketmanager;

use Racketmanager\Admin\View_Models\Admin_Results_Match_View_Model;

/** @var Admin_Results_Match_View_Model[] $tournament_results_matches */
$tournament_results_matches = $tournament_results_matches ?? array();
$prev_league                = 0;
?>
<table class="table table-striped">
    <thead class="table-dark">
    <tr>
        <th class=""><?php esc_html_e( 'Date', 'racketmanager' ); ?></th>
        <th class=""><?php esc_html_e( 'Match', 'racketmanager' ); ?></th>
        <th></th>
    </tr>
    </thead>
    <tbody>
    <?php
    if ( $tournament_results_matches ) {
        foreach ( $tournament_results_matches as $row ) {
            if ( $prev_league !== $row->league_id ) {
                $prev_league = $row->league_id;
                ?>
                <tr><td colspan="3" class="fw-bold fst-italic"><?php echo esc_html( $row->league_title ); ?></td></tr>
                <?php
            }
            ?>
            <tr data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="<?php echo esc_attr( $row->tooltip ); ?>">
                <td class=""><?php echo esc_html( $row->formatted_date ); ?></td>
                <td class="match-title"><a href="<?php echo esc_url( $row->match_link ); ?>"><?php echo esc_html( $row->match_title ); ?></a></td>
                <td class=""><a href="<?php echo esc_url( $row->result_link ); ?>" class="btn btn-primary"><?php esc_html_e( 'Enter result', 'racketmanager' ); ?></a></td>
            </tr>
            <?php
        }
    } else {
        ?>
        <tr><td colspan="3"><?php esc_html_e( 'No fixtures found for criteria', 'racketmanager' ); ?></td></tr>
        <?php
    }
    ?>
    </tbody>
</table>
