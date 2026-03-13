<?php
/**
 * Championship admin page
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

use Racketmanager\Util\Util;
use Racketmanager\Admin\View_Models\Tournament_Draw_Page_View_Model;

// Preferred input (when included from draw template).
$vm = isset( $vm ) && ( $vm instanceof Tournament_Draw_Page_View_Model ) ? $vm : null;

// BC fallback.
if ( $vm ) {
    $league     = $vm->league;
    $season     = $vm->season;
    $tournament = $vm->tournament;
}

/** @var object $league */
/** @var string $season */
/** @var object|null $tournament */
?>
<!-- Nav tabs -->
    <ul class="nav nav-pills" id="myTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="draw-tab" data-bs-toggle="pill" data-bs-target="#draw" type="button" role="tab" aria-controls="draw" aria-selected="true"><?php esc_html_e( 'Draw', 'racketmanager' ); ?></button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="fixtures-tab" data-bs-toggle="pill" data-bs-target="#fixtures" type="button" role="tab" aria-controls="fixtures" aria-selected="false"><?php esc_html_e( 'Fixtures', 'racketmanager' ); ?></button>
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
        <div class="tab-pane fade" id="draw" role="tabpanel" aria-labelledby="draw-tab">
            <h2><?php esc_html_e( 'Final Results', 'racketmanager' ); ?></h2>
            <?php require_once RACKETMANAGER_PATH . 'templates/admin/championship/final-results.php'; ?>
        </div>
        <div class="tab-pane fade" id="fixtures" role="tabpanel" aria-labelledby="fixtures-tab">
            <h2><?php echo esc_html( Util::get_final_name() ); ?></h2>
            <?php require_once RACKETMANAGER_PATH . 'templates/admin/championship/finals.php'; ?>
        </div>
        <div class="tab-pane fade" id="preliminary" role="tabpanel" aria-labelledby="preliminary-tab">
            <h2><?php esc_html_e( 'Teams', 'racketmanager' ); ?></h2>
            <?php require_once RACKETMANAGER_PATH . 'templates/admin/championship/preliminary.php'; ?>
        </div>
    </div>
