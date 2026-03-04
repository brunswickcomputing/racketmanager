<?php
/**
 * Tournament information administration panel
 *
 * @package Racketmanager/Admin/Templates
 */

namespace Racketmanager;

use Racketmanager\Admin\View_Models\Tournament_Information_Page_View_Model;

// Preferred input.
$vm = isset( $vm ) && ( $vm instanceof Tournament_Information_Page_View_Model ) ? $vm : null;

// Back-compat: allow legacy locals if $vm isn't provided.
if ( $vm ) {
    $tournament = $vm->tournament;
    $errors     = $vm->errors;
}

/** @var object $tournament */
/** @var \Racketmanager\Admin\View_Models\Error_Bag $errors */
?>
<div class='container'>
    <div class='row justify-content-end'>
        <div class='col-auto racketmanager_breadcrumb'>
            <a href="/wp-admin/admin.php?page=racketmanager-tournaments"><?php esc_html_e( 'RacketManager Tournaments', 'racketmanager' ); ?></a> &raquo; <a href="/wp-admin/admin.php?page=racketmanager-tournaments&amp;view=tournament&amp;tournament=<?php echo esc_attr( $tournament->id ); ?>&amp;season=<?php echo esc_attr( $tournament->season ); ?>"><?php echo esc_html( $tournament->name ); ?></a> &raquo <?php esc_html_e( 'Information', 'racketmanager' ); ?>
        </div>
    </div>
    <h1><?php esc_html_e( 'Information', 'racketmanager' ); ?></h1>
    <form action="" method='post' enctype='multipart/form-data' name='tournament_information'>
        <?php
        wp_nonce_field( 'racketmanager_tournament-information', 'racketmanager_nonce' );
        ?>
        <div class="row">
            <div class="mb-3">
                <?php $is_invalid = $errors->has( 'parking' ); ?>
                <label for="parking"><?php esc_html_e( 'Parking', 'racketmanager' ); ?></label>
                <textarea class="form-control" rows="3" name="parking" id="parking" placeholder="Enter parking information"><?php echo empty( $tournament->information->parking ) ? null : esc_html( $tournament->information->parking ); ?></textarea>
                <?php
                if ( $is_invalid ) {
                    ?>
                    <div class="invalid-feedback"><?php echo esc_html( strval( $errors->message( 'parking' ) ) ); ?></div>
                    <?php
                }
                ?>
            </div>
        </div>
        <div class="row">
            <div class="mb-3">
                <?php $is_invalid = $errors->has( 'catering' ); ?>
                <label for="catering"><?php esc_html_e( 'Catering', 'racketmanager' ); ?></label>
                <textarea class="form-control" rows="3" name="catering" id="catering" placeholder="Enter catering information"><?php echo empty( $tournament->information->catering ) ? null : esc_html( $tournament->information->catering ); ?></textarea>
                <?php
                if ( $is_invalid ) {
                    ?>
                    <div class="invalid-feedback"><?php echo esc_html( strval( $errors->message( 'catering' ) ) ); ?></div>
                    <?php
                }
                ?>
            </div>
        </div>
        <div class="row">
            <div class="mb-3">
                <?php $is_invalid = $errors->has( 'photography' ); ?>
                <label for="photography"><?php esc_html_e( 'Photography', 'racketmanager' ); ?></label>
                <textarea class="form-control" rows="3" name="photography" id="photography" placeholder="Enter photography information"><?php echo empty( $tournament->information->photography ) ? null : esc_html( $tournament->information->photography ); ?></textarea>
                <?php
                if ( $is_invalid ) {
                    ?>
                    <div class="invalid-feedback"><?php echo esc_html( strval( $errors->message( 'photography' ) ) ); ?></div>
                    <?php
                }
                ?>
            </div>
        </div>
        <div class="row">
            <div class="mb-3">
                <?php $is_invalid = $errors->has( 'spectators' ); ?>
                <label for="spectators"><?php esc_html_e( 'Spectators', 'racketmanager' ); ?></label>
                <textarea class="form-control" rows="3" name="spectators" id="spectators" placeholder="Enter spectators information"><?php echo empty( $tournament->information->spectators ) ? null : esc_html( $tournament->information->spectators ); ?></textarea>
                <?php
                if ( $is_invalid ) {
                    ?>
                    <div class="invalid-feedback"><?php echo esc_html( strval( $errors->message( 'spectators' ) ) ); ?></div>
                    <?php
                }
                ?>
            </div>
        </div>
        <div class="row">
            <div class="col-md-3 mb-3">
                <?php $is_invalid = $errors->has( 'referee' ); ?>
                <div class="form-floating">
                    <input type="text" class="form-control" name="referee" id="referee" placeholder="<?php esc_html_e( 'Enter referee', 'racketmanager' ); ?>" value="<?php echo empty( $tournament->information->referee ) ? null : esc_html( $tournament->information->referee ); ?>" />
                    <label for="referee"><?php esc_html_e( 'Referee', 'racketmanager' ); ?></label>
                </div>
                <?php
                if ( $is_invalid ) {
                    ?>
                    <div class="invalid-feedback"><?php echo esc_html( strval( $errors->message( 'referee' ) ) ); ?></div>
                    <?php
                }
                ?>
            </div>
        </div>
        <div class="row">
            <div class="form-floating mb-3">
                <?php $is_invalid = $errors->has( 'matchFormat' ); ?>
                <input type="text" class="form-control" name="matchFormat" id="matchFormat" placeholder="<?php esc_html_e( 'Enter match format', 'racketmanager' ); ?>" value="<?php echo empty( $tournament->information->match_format ) ? null : esc_html( $tournament->information->match_format ); ?>" />
                <label for="matchFormat"><?php esc_html_e( 'Match format', 'racketmanager' ); ?></label>
                <?php
                if ( $is_invalid ) {
                    ?>
                    <div class="invalid-feedback"><?php echo esc_html( strval( $errors->message( 'matchFormat' ) ) ); ?></div>
                    <?php
                }
                ?>
            </div>
        </div>
        <input type="hidden" name="tournament_id" id="tournament_id" value="<?php echo empty( $tournament->id ) ? null : esc_html( $tournament->id ); ?>" />
        <button class="btn btn-primary" type="submit" name="setInformation"><?php esc_html_e( 'Update', 'racketmanager' ); ?></button>
        <button class="btn btn-secondary" type="submit" name="notifyFinalists"><?php esc_html_e( 'Finalists', 'racketmanager' ); ?></button>
    </form>

</div>
