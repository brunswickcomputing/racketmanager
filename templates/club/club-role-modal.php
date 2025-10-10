<?php
/**
 * Template for club role modal
 *
 * @package Racketmanager/Templates/Club
 */

namespace Racketmanager;

/** @var object $club_role */
/** @var object $club_roles */
/** @var string $modal */
?>
<div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
        <form id="club-role-update" class="" action="#" method="post">
            <?php wp_nonce_field( 'club-role-update', 'racketmanager_nonce' ); ?>
            <input type="hidden" name="clubRoleId" value="<?php echo esc_attr( $club_role->id ); ?>" />
            <input type="hidden" name="clubId" id="clubId" value="<?php echo esc_attr( $club_role->club_id ); ?>" />
            <input type="hidden" name="modal" value="<?php echo esc_attr( $modal ); ?>" />
            <div class="modal-header modal__header">
                <h4 class="modal-title"><?php esc_html_e( 'Edit club role', 'racketmanager' ) ; ?></h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body ui-front">
                <div class="container">
                    <fieldset>
                        <legend><?php echo esc_html( $club_role->role->desc ); ?></legend>
                        <div class="row mb-3">
                            <div class="form-floating">
                                <input type="text" class="form-control userName" id="userName" name="userName" value="<?php echo esc_html( $club_role->user->display_name ); ?>" />
                                <label for="userName" ><?php esc_html_e( 'User', 'racketmanager' ); ?></label>
                                <input type="hidden" id="userId" name="userId" value="<?php echo esc_html( $club_role->user->id ); ?>" />
                                <div class="invalid-feedback" id="userFeedback"></div>
                                <div id="user-feedback"></div>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-sm-6 mb-3">
                                <div class="form-floating">
                                    <input type="tel" class="form-control" id="contactno" name="contactno" value="<?php echo esc_html( $club_role->user->contactno ); ?>" />
                                    <label for="contactno"><?php esc_html_e( 'Contact Number', 'racketmanager' ); ?></label>
                                    <div id="contactnoFeedback" class="invalid-feedback"></div>
                                </div>
                            </div>
                            <div class="col-sm-6 mb-3">
                                <div class="form-floating">
                                    <input type="email" class="form-control" id="contactemail" name="contactemail" value="<?php echo esc_html( $club_role->user->email ); ?>" />
                                    <label for="contactemail"><?php esc_html_e( 'Contact Email', 'racketmanager' ); ?></label>
                                    <div id="contactemailFeedback" class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>
                    </fieldset>
                    <div id="clubRoleResponse" class="alert_rm alert--danger" style="display: none;">
                        <div class="alert__body">
                            <div class="alert__body-inner">
                                <span id="clubRoleResponseText"></span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-plain" data-bs-dismiss="modal"><?php esc_html_e( 'Cancel', 'racketmanager' ); ?></button>
                    <button class="btn btn-primary" type="button" id="clubRoleUpdateSubmit" name="clubRoleUpdateSubmit">
                        <?php esc_html_e( 'Update', 'racketmanager' ); ?>
                    </button>
                </div>
        </form>
    </div>
</div>
<script type="text/javascript">
    document.getElementById('clubRoleUpdateSubmit').addEventListener('click', function (e) {
        Racketmanager.setClubRole(e, this);
    });
</script>
