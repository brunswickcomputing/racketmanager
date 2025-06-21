<?php
/**
 * Pending tournament results administration panel
 *
 * @package Racketmanager_admin
 */

namespace Racketmanager;

global $racketmanager;
$racketmanager_match_args                     = array();
$racketmanager_match_args['time']             = 'outstanding';
$racketmanager_match_args['competition_type'] = 'tournament';
$racketmanager_match_args['orderby']          = array(
    'updated' => 'ASC',
    'id'      => 'ASC',
);
$racketmanager_options                        = $racketmanager->get_options( 'tournament' );
$racketmanager_result_pending                 = '';
if ( isset( $racketmanager_options['resultPending'] ) ) {
    $racketmanager_result_pending              = $racketmanager_options['resultPending'];
    $racketmanager_match_args['resultPending'] = $racketmanager_result_pending;
}
$racketmanager_matches     = $racketmanager->get_matches( $racketmanager_match_args );
$racketmanager_prev_league = 0;
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
    if ( $racketmanager_matches ) {
        $racketmanager_class = '';
        foreach ( $racketmanager_matches as $racketmanager_match ) {
            $tooltip                     = '';
            $racketmanager_match         = get_match( $racketmanager_match );
            $racketmanager_overdue_class = '';
            $racketmanager_overdue       = false;
            if ( $racketmanager_result_pending ) {
                $racketmanager_now          = date_create();
                $racketmanager_date_overdue = date_create( $racketmanager_match->result_overdue_date );
                if ( $racketmanager_date_overdue < $racketmanager_now ) {
                    $racketmanager_overdue_class = 'bg-warning';
                    $racketmanager_overdue       = true;
                    /* translators: %d: days overdue  */
                    $tooltip = sprintf( __( 'Result overdue by %d days', 'racketmanager' ), intval( ceil( $racketmanager_match->overdue_time ) ) );
                }
            }
            if ( $racketmanager_prev_league !== $racketmanager_match->league_id ) {
                $racketmanager_prev_league = $racketmanager_match->league_id;
                ?>
                <tr><td colspan="3" class="fw-bold fst-italic"><?php echo esc_html( $racketmanager_match->league->title ); ?></td></tr>
                <?php
            }
            ?>
            <tr data-bs-toggle="tooltip" data-bs-placement="right" data-bs-title="<?php echo esc_html( $tooltip ); ?>">
                <td class=""><?php echo esc_html( mysql2date( 'Y-m-d', $racketmanager_match->date ) ); ?></td>
                <td class="match-title"><a href="<?php echo esc_html( $racketmanager_match->link ); ?>?referrer=tournament; ?>"><?php echo esc_html( $racketmanager_match->match_title ); ?></a></td>
                <td class=""><a href="<?php echo esc_html( $racketmanager_match->link ); ?>?referrer=tournament" class="btn btn-primary"><?php esc_html_e( 'Enter result', 'racketmanager' ); ?></a>                </tr>
            <?php
        }
    } else {
        ?>
        <tr><td colspan="3"><?php esc_html_e( 'No matches found for criteria', 'racketmanager' ); ?></td></tr>
        <?php
    }
    ?>
    </tbody>
</table>

