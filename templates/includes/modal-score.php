<?php
/**
 * Template for match for teams
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

?>
<div class="modal" id="scoreStatusModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header modal__header">
                <h4 class="modal-title"><?php esc_html_e( 'Score status', 'racketmanager' ); ?></h4>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="<?php esc_html_e( 'Close', 'racketmanager' ); ?>"></button>
            </div>
            <div class="modal-body"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php esc_html_e( 'Close', 'racketmanager' ); ?></button>
                <button type="button" class="btn btn-primary"><?php esc_html_e( 'Save changes', 'racketmanager' ); ?></button>
            </div>
        </div>
    </div>
</div>
