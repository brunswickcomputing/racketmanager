<?php
/**
 * Season view administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

/** @var string $tab */
/** @var object $competition */
/** @var string $season */
?>
<script type='text/javascript'>
jQuery(document).ready(function(){
    activaTab('<?php echo esc_html( $tab ); ?>');
});
</script>
<div class='container'>
    <div class="row justify-content-end">
        <div class="col-auto racketmanager_breadcrumb">
            <a href="/wp-admin/admin.php?page=racketmanager-<?php echo esc_attr( $competition->type ); ?>s"><?php echo esc_html( ucfirst( $competition->type ) ); ?>s</a> &raquo; <a href="/wp-admin/admin.php?page=racketmanager-<?php echo esc_html( $competition->type ); ?>s&amp;view=seasons&amp;competition_id=<?php echo esc_attr( $competition->id ); ?>"><?php echo esc_html( $competition->name ); ?></a> &raquo; <?php echo esc_html( $season ); ?>
        </div>
    </div>
    <h1><?php echo esc_html( $competition->name ); ?> - <?php echo esc_html( $season ); ?></h1>
    <div class="row mb-3">
        <nav class="navbar navbar-expand-lg bg-body-tertiary">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar<?php echo esc_attr( $competition->type ); ?>" aria-controls="navbar<?php echo esc_attr( $competition->type ); ?>" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbar<?php echo esc_attr( $competition->type ); ?>">
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
                    <li class="nav-item">
                        <a class="nav-link" id="plan-tab" href="/wp-admin/admin.php?page=racketmanager-<?php echo esc_attr( $competition->type ); ?>s&view=plan&competition_id=<?php echo esc_attr( $competition->id ); ?>&season=<?php echo esc_attr( $season ); ?>" type="button" role="tab"><?php esc_html_e( 'Schedule', 'racketmanager' ); ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/wp-admin/admin.php?page=racketmanager-<?php echo esc_html( $competition->type ); ?>s&view=setup&competition_id=<?php echo esc_attr( $competition->id ); ?>&season=<?php echo esc_attr( $season ); ?>" type="button" role="tab"><?php esc_html_e( 'Setup', 'racketmanager' ); ?></a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/wp-admin/admin.php?page=racketmanager-<?php echo esc_html( $competition->type ); ?>s&view=contact&competition_id=<?php echo esc_attr( $competition->id ); ?>&season=<?php echo esc_attr( $season ); ?>" type="button" role="tab"><?php esc_html_e( 'Contact', 'racketmanager' ); ?></a>
                    </li>
                </ul>
            </div>
        </nav>
    </div>
    <div class="tab-content">
        <div class="tab-pane" id="overview" role="tabpanel" aria-labelledby="overview-tab">
            <h2><?php esc_html_e( 'Overview', 'racketmanager' ); ?></h2>
            <?php require_once RACKETMANAGER_PATH . 'templates/admin/includes/overview.php'; ?>
        </div>
        <div class="tab-pane" id="events" role="tabpanel" aria-labelledby="events-tab">
            <h2><?php esc_html_e( 'Events', 'racketmanager' ); ?></h2>
            <?php require_once 'events.php'; ?>
        </div>
        <div class="tab-pane" id="entries" role="tabpanel" aria-labelledby="entries-tab">
            <h2><?php esc_html_e( 'Entries', 'racketmanager' ); ?></h2>
            <?php require_once RACKETMANAGER_PATH . 'templates/admin/includes/entries.php'; ?>
        </div>
    </div>
