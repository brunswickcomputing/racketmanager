<?php
/**
 * Global settings administration panel
 *
 * @package Racketmanager_admin
 */

namespace Racketmanager;

/** @var string $tab */
$menu_page_url = admin_url( 'options-general.php?page=racketmanager-settings' );
?>
<script type='text/javascript'>
jQuery(document).ready(function(){
    activaTab('<?php echo esc_html( $tab ); ?>');
});
</script>
<div class='container'>
    <h1><?php esc_html_e( 'Racketmanager Global Settings', 'racketmanager' ); ?></h1>

    <form action='' method='post' name='settings'>
        <?php wp_nonce_field( 'racketmanager_manage-global-league-options', 'racketmanager_nonce' ); ?>

        <input type="hidden" class="active-tab" name="active-tab" value="<?php echo esc_html( $tab ); ?>" />

        <div class="row mb-3">
            <div class=col-12">
                <!-- Nav tabs -->
                <ul class="nav nav-tabs" id="myTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="club-players-tab" data-bs-toggle="tab" data-bs-target="#club-players" type="button" role="tab" aria-controls="club-players" aria-selected="true"><?php esc_html_e( 'Club Players', 'racketmanager' ); ?></button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="display-tab" data-bs-toggle="tab" data-bs-target="#display" type="button" role="tab" aria-controls="display" aria-selected="false"><?php esc_html_e( 'Display', 'racketmanager' ); ?></button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="players-tab" data-bs-toggle="tab" data-bs-target="#players" type="button" role="tab" aria-controls="players" aria-selected="false"><?php esc_html_e( 'Player Checks', 'racketmanager' ); ?></button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="match-results-tab" data-bs-toggle="tab" data-bs-target="#match-results" type="button" role="tab" aria-controls="match-results" aria-selected="false"><?php esc_html_e( 'Match Results', 'racketmanager' ); ?></button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="entries-tab" data-bs-toggle="tab" data-bs-target="#entries" type="button" role="tab" aria-controls="entries" aria-selected="false"><?php esc_html_e( 'Entries', 'racketmanager' ); ?></button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="championship-tab" data-bs-toggle="tab" data-bs-target="#championship" type="button" role="tab" aria-controls="championship" aria-selected="false"><?php esc_html_e( 'Championship', 'racketmanager' ); ?></button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="billing-tab" data-bs-toggle="tab" data-bs-target="#billing" type="button" role="tab" aria-controls="billing" aria-selected="false"><?php esc_html_e( 'Billing', 'racketmanager' ); ?></button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="keys-tab" data-bs-toggle="tab" data-bs-target="#keys" type="button" role="tab" aria-controls="keys" aria-selected="false"><?php esc_html_e( 'Keys', 'racketmanager' ); ?></button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="walkover-tab" data-bs-toggle="tab" data-bs-target="#walkover" type="button" role="tab" aria-controls="walkover" aria-selected="false"><?php esc_html_e( 'Walkovers', 'racketmanager' ); ?></button>
                    </li>
                </ul>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-12">
                <button type='submit' class='btn btn-primary' name='updateRacketManager'><?php esc_html_e( 'Save Preferences', 'racketmanager' ); ?></button>
            </div>
        </div>
        <div class="row mb-3">
            <!-- Tab panes -->
            <div class="tab-content">
                <div class="tab-pane active show fade" id="club-players" role="tabpanel" aria-labelledby="club-players-tab">
                    <?php require_once RACKETMANAGER_PATH . 'templates/admin/includes/settings/rosters.php'; ?>
                </div>
                <div class="tab-pane fade" id="display" role="tabpanel" aria-labelledby="display-tab">
                    <?php require_once RACKETMANAGER_PATH . 'templates/admin/includes/settings/display.php'; ?>
                </div>
                <div class="tab-pane fade" id="players" role="tabpanel" aria-labelledby="players-tab">
                    <?php require_once RACKETMANAGER_PATH . 'templates/admin/includes/settings/players.php'; ?>
                </div>
                <div class="tab-pane fade" id="match-results" role="tabpanel" aria-labelledby="match-results-tab">
                    <?php require_once RACKETMANAGER_PATH . 'templates/admin/includes/settings/results.php'; ?>
                </div>
                <div class="tab-pane fade" id="entries" role="tabpanel" aria-labelledby="entries-tab">
                    <?php require_once RACKETMANAGER_PATH . 'templates/admin/includes/settings/entries.php'; ?>
                </div>
                <div class="tab-pane fade" id="championship" role="tabpanel" aria-labelledby="championship-tab">
                    <?php require_once RACKETMANAGER_PATH . 'templates/admin/includes/settings/championship.php'; ?>
                </div>
                <div class="tab-pane fade" id="billing" role="tabpanel" aria-labelledby="billing-tab">
                    <?php require_once RACKETMANAGER_PATH . 'templates/admin/includes/settings/billing.php'; ?>
                </div>
                <div class="tab-pane fade" id="keys" role="tabpanel" aria-labelledby="keys-tab">
                    <?php require_once RACKETMANAGER_PATH . 'templates/admin/includes/settings/keys.php'; ?>
                </div>
                <div class="tab-pane fade" id="walkover" role="tabpanel" aria-labelledby="walkover-tab">
                    <?php require_once RACKETMANAGER_PATH . 'templates/admin/includes/settings/walkover.php'; ?>
                </div>
            </div>
        </div>
    </form>
</div>
