<?php
/**
 * Template for message
 *
 * @package Racketmanager/Templates/Account
 */

namespace Racketmanager;

/** @var object $message_dtl */
?>
<div class="message_header">
    <div class="message_header_wrapper">
        <dl class="list list--flex">
            <div class="list__item">
                <dt class="list__label"><?php esc_html_e( 'From', 'racketmanager' ); ?></dt>
                <dd class="list__value">
                    <?php
                    if ( $message_dtl->from_name ) {
                        echo esc_html( $message_dtl->from_name ) . ' ';
                    }
                    echo '[<a href="mailto:' . esc_attr( $message_dtl->from_email ) . '">' . esc_html( $message_dtl->from_email ) . '</a>]';
                    ?>
                </dd>
            </div>
            <div class="list__item">
                <dt class="list__label"><?php esc_html_e( 'Subject', 'racketmanager' ); ?></dt>
                <dd class="list__value">
                    <?php echo esc_html( $message_dtl->subject ); ?>
                </dd>
            </div>
        </dl>
    </div>
    <div class="suffix_wrapper">
        <div class="time"><?php echo esc_html( mysql2date( 'd-m-Y G:i:s', $message_dtl->date ) ); ?></div>
        <div class="message-button"><a id="deleteMessage" data-msg-id="<?php echo esc_attr( $message_dtl->id ); ?>" class="btn btn-primary"><?php esc_html_e( 'Delete', 'racketmanager' ); ?></a></div>
    </div>
</div>
<div class="message_body ratio" style="--bs-aspect-ratio: 100%;">
    <?php $frame_source = $message_dtl->message_object; ?>
    <iframe title="<?php esc_html_e( 'Message details', 'racketmanager' ); ?>" srcdoc='<?php echo $frame_source; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>'></iframe>
</div>

