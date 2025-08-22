<?php
/**
 * Event config administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

/** @var object $competition */
/** @var object $event */
/** @var string $season */
/** @var string $tab */
/** @var bool $new_event */
if ( $new_event ) {
    $page_title = __( 'New event config', 'racketmanager' );
} else {
    $page_title = $event->name . ' - ' . __( 'config', 'racketmanager' );
}
if ( empty( $tournament ) ) {
    if ( $new_event ) {
        $breadcrumb_link = '<a href="/wp-admin/admin.php?page=racketmanager-' . $competition->type . 's&amp;view=seasons&amp;competition_id=' . $competition->id . '">' . $competition->name . '</a> &raquo; ' . __( 'New event', 'racketmanager' );
    } else {
        $breadcrumb_link = '<a href="/wp-admin/admin.php?page=racketmanager-' . $competition->type . 's&amp;view=seasons&amp;competition_id=' . $competition->id . '">' . $competition->name . '</a> &raquo; <a href="/wp-admin/admin.php?page=racketmanager-' . $competition->type . 's&amp;view=event&amp;competition_id=' . $competition->id . '&amp;event_id=' . $event->id . '&amp;season=' . $season . '">' . $event->name . '</a>';
    }
    $add_link        = '';
} else {
    $breadcrumb_link = '<a href="/wp-admin/admin.php?page=racketmanager-' . $competition->type . 's&amp;view=config&amp;competition_id=' . $competition->id . '&amp;tab=events&amp;tournament=' . $tournament->id . '">' . $tournament->name . '</a>';
    $add_link        = '&amp;tournament=' . $tournament->id;
}
?>
<div>
    <div class="alert_rm" id="alert-season" style="display:none;">
        <div class="alert__body">
            <div class="alert__body-inner" id="alert-season-response">
            </div>
        </div>
    </div>
<div class="container">
    <div class="row justify-content-end">
        <div class="col-auto racketmanager_breadcrumb">
            <a href="/wp-admin/admin.php?page=racketmanager-<?php echo esc_attr( $competition->type ); ?>s"><?php echo esc_html( ucfirst( $competition->type ) ); ?>s</a> &raquo; <?php echo $breadcrumb_link; ?> &raquo; <?php esc_html_e( 'Configuration', 'racketmanager' ); ?>
        </div>
    </div>
    <div class="row justify-content-between">
        <div class="col-auto">
            <h1><?php echo esc_html( $page_title ); ?></h1>
        </div>
        <div class="">
            <form action="" method="post" class="">
                <?php wp_nonce_field( 'racketmanager_manage-event-config', 'racketmanager_nonce' ); ?>
                <input type="hidden" class="active-tab" name="active-tab" value="<?php echo esc_html( $tab ); ?>" />
                <div class="mb-3">
                    <nav class="navbar navbar-expand-lg bg-body-tertiary">
                        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-event-config" aria-controls="navbar-event-config" aria-expanded="false" aria-label="Toggle navigation">
                            <span class="navbar-toggler-icon"></span>
                        </button>
                        <div class="collapse navbar-collapse" id="navbar-event-config">
                            <ul class="nav nav-pills">
                                <li class="nav-item">
                                    <button class="nav-link" id="general-tab" data-bs-toggle="tab" data-bs-target="#general" type="button" role="tab" aria-controls="general" aria-selected="true"><?php esc_html_e( 'General', 'racketmanager' ); ?></button>
                                </li>
                                <?php
                                if ( ! $competition->is_tournament ) {
                                    ?>
                                    <li class="nav-item">
                                        <button class="nav-link" id="fixtures-tab" data-bs-toggle="tab" data-bs-target="#fixtures" type="button" role="tab" aria-controls="fixtures" aria-selected="true"><?php esc_html_e( 'Fixtures', 'racketmanager' ); ?></button>
                                    </li>
                                    <?php
                                }
                                ?>
                                <?php
                                if ( $competition->is_championship && ! empty( $event->name ) ) {
                                    ?>
                                    <li class="nav-item">
                                        <button class="nav-link" id="championship-tab" data-bs-toggle="tab" data-bs-target="#championship" type="button" role="tab" aria-controls="championship" aria-selected="true"><?php esc_html_e( 'Championship', 'racketmanager' ); ?></button>
                                    </li>
                                    <?php
                                }
                                ?>
                            </ul>
                        </div>
                    </nav>
                </div>
                <div class="mb-3">
                    <input type="hidden" name="event_id" value="<?php echo empty( $event->id ) ? null : esc_attr( $event->id ); ?>" />
                    <button name="<?php echo empty( $new_event ) ? 'update' : 'add'; ?>EventConfig" class="btn btn-primary"><?php esc_html_e( 'Save Settings', 'racketmanager' ); ?></button>
                </div>
                <div class="tab-content">
                    <div class="tab-pane fade" id="general" role="tabpanel" aria-labelledby="general-tab">
                        <h2><?php esc_html_e( 'General', 'racketmanager' ); ?></h2>
                        <?php require_once RACKETMANAGER_PATH . 'admin/event/include/settings/general.php'; ?>
                    </div>
                    <?php
                    if ( ! $competition->is_tournament ) {
                        ?>
                        <div class="tab-pane fade" id="fixtures" role="tabpanel" aria-labelledby="fixtures-tab">
                            <h2><?php esc_html_e( 'Fixtures', 'racketmanager' ); ?></h2>
                            <?php require_once RACKETMANAGER_PATH . 'admin/event/include/settings/fixtures.php'; ?>
                        </div>
                        <?php
                    }
                    ?>
                    <?php
                    if ( $competition->is_championship && ! empty( $event->name ) ) {
                        ?>
                        <div class="tab-pane fade" id="championship" role="tabpanel" aria-labelledby="championship-tab">
                            <h2><?php esc_html_e( 'Championship', 'racketmanager' ); ?></h2>
                            <?php require_once RACKETMANAGER_PATH . 'admin/event/include/settings/championship.php'; ?>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </form>
        </div>
        <?php
        if ( ! empty( $error_tab ) ) {
            $tab = $error_tab; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
        }
        ?>
        <script type='text/javascript'>
        jQuery(document).ready(function(){
            activaTab('<?php echo esc_html( $tab ); ?>');
        });
        </script>
    </div>
</div>
