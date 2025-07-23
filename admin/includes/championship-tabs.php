<?php
/**
 * Championship admin page
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var object $league */
/** @var string $season */
?>
<!-- Nav tabs -->
    <ul class="nav nav-pills" id="myTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="finalResults-tab" data-bs-toggle="pill" data-bs-target="#finalResults" type="button" role="tab" aria-controls="finalResults" aria-selected="true"><?php esc_html_e( 'Draw', 'racketmanager' ); ?></button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="matches-tab" data-bs-toggle="pill" data-bs-target="#matches" type="button" role="tab" aria-controls="matches" aria-selected="false"><?php esc_html_e( 'Matches', 'racketmanager' ); ?></button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="preliminary-tab" data-bs-toggle="pill" data-bs-target="#preliminary" type="button" role="tab" aria-controls="preliminary" aria-selected="false"><?php esc_html_e( 'Teams', 'racketmanager' ); ?></button>
        </li>
        <?php
        if ( $league->event->competition->is_tournament && ! empty( $tournament ) ) {
            ?>
            <li class="nav-item">
                <a class="nav-link" href="/wp-admin/admin.php?page=racketmanager-tournaments&view=setup-event&tournament=<?php echo esc_attr( $tournament->id ); ?>&league=<?php echo esc_attr( $league->id ); ?>&season=<?php echo esc_attr( $tournament->season ); ?>" type="button" role="tab"><?php esc_html_e( 'Setup', 'racketmanager' ); ?></a>
            </li>
            <?php
        } elseif ( $league->event->competition->is_cup ) {
            ?>
            <li class="nav-item">
                <a class="nav-link" href="/wp-admin/admin.php?page=racketmanager-cups&view=setup-event&competition_id=<?php echo esc_attr( $league->event->competition->id ); ?>&league=<?php echo esc_attr( $league->id ); ?>&season=<?php echo esc_attr( $season ); ?>" type="button" role="tab"><?php esc_html_e( 'Setup', 'racketmanager' ); ?></a>
            </li>
            <?php
        }
        ?>
    </ul>
    <!-- Tab panes -->
    <div class="tab-content">
        <div class="tab-pane fade" id="finalResults" role="tabpanel" aria-labelledby="finalResults-tab">
            <h2><?php esc_html_e( 'Final Results', 'racketmanager' ); ?></h2>
            <?php require_once RACKETMANAGER_PATH . 'admin/championship/final-results.php'; ?>
        </div>
        <div class="tab-pane fade" id="matches" role="tabpanel" aria-labelledby="matches-tab">
            <h2><?php echo esc_html( Util::get_final_name() ); ?></h2>
            <?php require_once RACKETMANAGER_PATH . 'admin/championship/finals.php'; ?>
        </div>
        <div class="tab-pane fade" id="preliminary" role="tabpanel" aria-labelledby="preliminary-tab">
            <h2><?php esc_html_e( 'Teams', 'racketmanager' ); ?></h2>
            <?php require_once RACKETMANAGER_PATH . 'admin/championship/preliminary.php'; ?>
        </div>
    </div>
