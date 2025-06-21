<?php
/**
 * Tournament view administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

/** @var string $tab */
/** @var object $tournament */
?>
<script type='text/javascript'>
jQuery(document).ready(function(){
    activaTab('<?php echo esc_html( $tab ); ?>');
});
</script>
<div class='container'>
    <div class='row justify-content-end'>
        <div class='col-auto racketmanager_breadcrumb'>
            <a href="/wp-admin/admin.php?page=racketmanager-tournaments"><?php esc_html_e( 'RacketManager Tournaments', 'racketmanager' ); ?></a> &raquo; <?php echo esc_html( $tournament->name ); ?>
        </div>
    </div>
    <h1><?php echo esc_html( $tournament->name ); ?></h1>
    <div class="row mb-3">
        <nav class="navbar navbar-expand-lg bg-body-tertiary">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarTournament" aria-controls="navbarTournament" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarTournament">
                <ul class="nav nav-pills">
                    <li class="nav-item">
                        <button class="nav-link" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab" aria-controls="overview" aria-selected="true"><?php esc_html_e( 'Overview', 'racketmanager' ); ?></button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="events-tab" data-bs-toggle="tab" data-bs-target="#events" type="button" role="tab" aria-controls="events" aria-selected="true"><?php esc_html_e( 'Events', 'racketmanager' ); ?></button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" id="entries-tab" data-bs-toggle="tab" data-bs-target="#entries" type="button" role="tab" aria-controls="entries" aria-selected="true"><?php esc_html_e( 'Entries', 'racketmanager' ); ?></button>
                    </li>
                    <?php
                    if ( $tournament->is_active ) {
                        ?>
                        <li class="nav-item">
                            <a class="nav-link" id="plan-tab" href="/wp-admin/admin.php?page=racketmanager-tournaments&view=plan&tournament=<?php echo esc_attr( $tournament->id ); ?>" type="button" role="tab"><?php esc_html_e( 'Plan', 'racketmanager' ); ?></a>
                        </li>
                        <?php
                    }
                    ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/wp-admin/admin.php?page=racketmanager-tournaments&view=setup&tournament=<?php echo esc_attr( $tournament->id ); ?>&season=<?php echo esc_attr( $tournament->season ); ?>" type="button" role="tab"><?php esc_html_e( 'Setup', 'racketmanager' ); ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/wp-admin/admin.php?page=racketmanager-tournaments&view=config&competition_id=<?php echo esc_attr( $tournament->competition_id ); ?>&tournament=<?php echo esc_attr( $tournament->id ); ?>" type="button" role="tab"><?php esc_html_e( 'Config', 'racketmanager' ); ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="contact-tab" href="/wp-admin/admin.php?page=racketmanager-tournaments&view=contact&tournament_id=<?php echo esc_attr( $tournament->id ); ?>" type="button" role="tab"><?php esc_html_e( 'Contact', 'racketmanager' ); ?></a>
                    </li>
                </ul>
            </div>
        </nav>
    </div>
    <div class="tab-content">
        <div class="tab-pane" id="overview" role="tabpanel" aria-labelledby="overview-tab">
            <h2><?php esc_html_e( 'Overview', 'racketmanager' ); ?></h2>
            <?php require 'tournament/overview.php'; ?>
        </div>
        <div class="tab-pane" id="events" role="tabpanel" aria-labelledby="events-tab">
            <h2><?php esc_html_e( 'Events', 'racketmanager' ); ?></h2>
            <?php require 'tournament/events.php'; ?>
        </div>
        <div class="tab-pane" id="entries" role="tabpanel" aria-labelledby="entries-tab">
            <h2><?php esc_html_e( 'Entries', 'racketmanager' ); ?></h2>
            <?php require 'tournament/entries.php'; ?>
        </div>
    </div>
