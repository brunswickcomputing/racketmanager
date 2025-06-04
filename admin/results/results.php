<?php
/**
 * Approved Results administration panel
 *
 * @package Racketmanager_admin
 */

namespace Racketmanager;

global $racketmanager;
$racketmanager_match_args            = array();
$racketmanager_match_args['status']  = 'A';
$racketmanager_match_args['orderby'] = array(
	'updated' => 'ASC',
	'id'      => 'ASC',
);
$racketmanager_matches     = $racketmanager->get_matches( $racketmanager_match_args );
$racketmanager_prev_league = 0;
?>
<?php wp_nonce_field( 'results-update' ); ?>
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
        if ( $racketmanager_matches ) {
            foreach ( $racketmanager_matches as $racketmanager_match ) {
                $racketmanager_match = get_match( $racketmanager_match );
                if ( $racketmanager_prev_league !== $racketmanager_match->league_id ) {
                    $racketmanager_prev_league = $racketmanager_match->league_id;
                    ?>
                    <tr><td colspan="5" class="fw-bold fst-italic"><?php echo esc_html( $racketmanager_match->league->title ); ?></td></tr>
                    <?php
                }
                ?>
                <tr>
                    <td class=""><?php echo esc_html( mysql2date( 'Y-m-d', $racketmanager_match->date ) ); ?></td>
                    <td class="match-title"><a href="<?php echo esc_html( $racketmanager_match->link ); ?>?referrer=results; ?>"><?php echo esc_html( $racketmanager_match->match_title ); ?></a></td>
                    <td class=""><?php echo esc_html( $racketmanager_match->confirmed_display ); ?></td>
                    <td class=""><?php echo esc_html( $racketmanager_match->score ); ?></td>
                    <td class=""><a href="<?php echo esc_html( $racketmanager_match->link ); ?>result/?referrer=results" class="btn btn-secondary"><?php esc_html_e( 'View result', 'racketmanager' ); ?></a></td>
                </tr>
                <div class="row table-row <?php echo esc_html( $racketmanager_overdue_class ); ?> align-items-center">
                </div>
                <?php
            }
        } else {
            ?>
            <tr><td colspan="5"><?php esc_html_e( 'No matches found for criteria', 'racketmanager' ); ?></td></tr>
            <?php
        }
        ?>
    </tbody>
</table>

