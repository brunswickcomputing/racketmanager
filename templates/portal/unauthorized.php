<?php
/**
 * Portal Unauthorized Template
 *
 * @package RacketManager
 * @var Portal_Unauthorized_View_Model $vm
 */

use Racketmanager\Infrastructure\Wordpress\Portal\Portal_Unauthorized_View_Model;

?>
<div class="rm-portal-unauthorized">
    <div class="rm-portal-unauthorized__card">
        <h1 class="rm-portal-unauthorized__title"><?php echo esc_html( $vm->title ); ?></h1>
        <p class="rm-portal-unauthorized__message"><?php echo esc_html( $vm->message ); ?></p>
        <div class="rm-portal-unauthorized__actions">
            <?php if ( ! empty( $vm->dashboard_url ) ) : ?>
                <a href="<?php echo esc_url( $vm->dashboard_url ); ?>" class="button button-primary">
                    <?php echo esc_html__( 'Go to Dashboard', 'racketmanager' ); ?>
                </a>
            <?php endif; ?>
            <?php if ( ! empty( $vm->login_url ) ) : ?>
                <a href="<?php echo esc_url( $vm->login_url ); ?>" class="button">
                    <?php echo esc_html__( 'Switch User', 'racketmanager' ); ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>
