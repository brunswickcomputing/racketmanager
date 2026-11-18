<?php
/**
 * Player main page administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

/** @var \Racketmanager\Admin\View_Models\Player_Page_View_Model $vm */
$player         = $vm->player;
$player_id      = $vm->player_id;
$club           = $vm->club;
$club_id        = $vm->club_id;
$page_referrer  = $vm->page_referrer;
$errors         = $vm->errors;
$form_valid     = $vm->form_valid;
$error_fields   = array();
$error_messages = array();
foreach ( $errors->all() as $err_fld => $err_msg ) {
    $error_fields[]   = (string) $err_fld;
    $error_messages[] = (string) $err_msg;
}
?>
<div class="container">
    <div class="row justify-content-end">
        <div class="col-auto racketmanager_breadcrumb">
            <?php
            if ( isset( $club_id ) && ! empty( $club ) ) {
                ?>
                <a href="<?php echo esc_url( manage_url( 'clubs' ) ); ?>"><?php esc_html_e( 'Clubs', 'racketmanager' ); ?></a> &raquo; <a href="<?php echo esc_url( manage_url( 'clubs', 'players', array( 'club_id' => (string) $club_id ) ) ); ?>"><?php echo esc_html( $club->shortcode ?? '' ); ?></a> &raquo; <?php esc_html_e( 'Players', 'racketmanager' ); ?>
                <?php
            } else {
                ?>
                <a href="<?php echo esc_url( manage_url( 'players' ) ); ?>"><?php esc_html_e( 'Players', 'racketmanager' ); ?></a>
                <?php
            }
            ?>
        </div>
    </div>
    <h1><?php esc_html_e( 'Edit Player', 'racketmanager' ); ?> - <?php echo esc_html( $player->display_name ?? '' ); ?></h1>

    <!-- Edit Player -->
    <div class="mb-3">
        <?php require RACKETMANAGER_PATH . 'templates/admin/includes/player.php'; ?>
    </div>
    <?php
    if ( isset( $player_id ) ) {
        ?>
        <div class="">
            <a href="<?php echo esc_url( $page_referrer ?? manage_url( 'players', 'players' ) ); ?>" class="button button-secondary"><?php esc_html_e( 'Back', 'racketmanager' ); ?></a>
        </div>
        <?php
    }
    ?>
</div>
