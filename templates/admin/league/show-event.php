<?php
/**
 * League Event administration panel
 *
 * @package Racketmanager_admin
 */

namespace Racketmanager;

/** @var string $tab */
/** @var object $event */
/** @var string $season */
?>
<script type='text/javascript'>
jQuery(document).ready(function(){
    activaTab('<?php echo esc_html( $tab ); ?>');
});
</script>
<div class="container">
    <div class="row justify-content-end">
        <div class="col-auto racketmanager_breadcrumb">
            <a href="/admin.php?page=racketmanager-<?php echo esc_html( $event->competition->type ); ?>s"><?php echo esc_html( ucfirst( $event->competition->type ) ); ?>s</a> &raquo; <a href="/admin.php?page=racketmanager-<?php echo esc_html( $event->competition->type ); ?>s&amp;view=seasons&amp;competition_id=<?php echo esc_attr( $event->competition->id ); ?>"><?php echo esc_html( $event->competition->name ); ?></a> &raquo; <a href="/admin.php?page=racketmanager-<?php echo esc_html( $event->competition->type ); ?>s&amp;view=overview&amp;competition_id=<?php echo esc_attr( $event->competition->id ); ?>&amp;season=<?php echo esc_attr( $season ); ?>"><?php echo esc_html( $season ); ?></a> &raquo; <?php echo esc_html( $event->name ); ?>
        </div>
    </div>
    <div class="row justify-content-between">
        <div class="col-auto">
            <h1><?php echo esc_html( $event->name ); ?></h1>
        </div>
    </div>

    <?php $this->show_message(); ?>
    <div>
        <nav class="navbar navbar-expand-lg bg-body-tertiary">
            <div class="">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse mt-3" id="navbarSupportedContent">
                    <!-- Nav tabs -->
                    <ul class="nav nav-pills" id="myTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="leagues-tab" data-bs-toggle="tab" data-bs-target="#leagues" type="button" role="tab" aria-controls="leagues" aria-selected="true"><?php esc_html_e( 'Leagues', 'racketmanager' ); ?></button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="setup-tab" href="/admin.php?page=racketmanager-<?php echo esc_html( $event->competition->type ); ?>s&view=setup-event&competition_id=<?php echo esc_attr( $event->competition->id ); ?>&season=<?php echo esc_attr( $season ); ?>&event_id=<?php echo esc_attr( $event->id ); ?>" type="button" role="tab"><?php esc_html_e( 'Setup', 'racketmanager' ); ?></a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="constitution-tab" href="/admin.php?page=racketmanager-<?php echo esc_html( $event->competition->type ); ?>s&view=constitution&competition_id=<?php echo esc_attr( $event->competition->id ); ?>&season=<?php echo esc_attr( $season ); ?>&event_id=<?php echo esc_attr( $event->id ); ?>" type="button" role="tab"><?php esc_html_e( 'Constitution', 'racketmanager' ); ?></a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="matches-tab" href="/admin.php?page=racketmanager-<?php echo esc_html( $event->competition->type ); ?>s&view=matches&competition_id=<?php echo esc_attr( $event->competition->id ); ?>&season=<?php echo esc_attr( $season ); ?>&event_id=<?php echo esc_attr( $event->id ); ?>" type="button" role="tab"><?php esc_html_e( 'Matches', 'racketmanager' ); ?></a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="config-tab" href="/admin.php?page=racketmanager-<?php echo esc_html( $event->competition->type ); ?>s&view=event-config&competition_id=<?php echo esc_attr( $event->competition->id ); ?>&season=<?php echo esc_attr( $season ); ?>&event_id=<?php echo esc_attr( $event->id ); ?>" type="button" role="tab"><?php esc_html_e( 'Configuration', 'racketmanager' ); ?></a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
        <!-- Tab panes -->
        <div class="tab-content">
            <div class="tab-pane" id="leagues" role="tabpanel" aria-labelledby="leagues-tab">
                <h2><?php esc_html_e( 'Leagues', 'racketmanager' ); ?></h2>
                <?php require_once RACKETMANAGER_PATH . 'admin/event/leagues.php'; ?>
            </div>
        </div>
    </div>
