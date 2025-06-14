<?php
/**
 * Template for alert modal
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var string $msg */
/** @var string $class */
?>
<div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
        <div class="modal-header modal__header">
            <h4 class="modal-title"><?php esc_html_e( 'Error', 'racketmanager' ); ?></h4>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <div class="container-fluid">
                <div class="alert_rm alert--<?php echo esc_attr( $class ); ?>">
                    <div class="alert__body">
                        <div class="alert__body-inner">
                            <span><?php echo esc_html( $msg ); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-plain" data-bs-dismiss="modal"><?php esc_html_e( 'Cancel', 'racketmanager' ); ?></button>
        </div>
    </div>
</div>
