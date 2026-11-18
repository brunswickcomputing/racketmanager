<?php
/**
 * Portal Layout Shell Template
 *
 * @package RacketManager
 * @var Portal_Layout_View_Model $vm
 * @var View_Renderer_Interface $renderer
 */

use Racketmanager\Application\Ports\View_Renderer_Interface;
use Racketmanager\Infrastructure\Wordpress\Portal\Portal_Layout_View_Model;

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
    <head>
        <meta charset="<?php bloginfo( 'charset' ); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo esc_html( $vm->title ); ?> &mdash; <?php echo esc_html( get_bloginfo( 'name' ) ); ?></title>
        <?php wp_head(); ?>
    </head>
    <body <?php body_class( 'racketmanager-portal-shell' ); ?>>
    <?php wp_body_open(); ?>

    <div class="rm-portal-wrapper">
        <header class="rm-portal-header">
            <div class="rm-portal-header__brand">
                <a href="<?php echo esc_url( home_url( '/manage/competitions' ) ); ?>" class="rm-portal-header__logo">
                    <span class="rm-portal-header__title"><?php echo esc_html__( 'RacketManager Portal', 'racketmanager' ); ?></span>
                </a>
            </div>
            <div class="rm-portal-header__user">
                <?php
                if ( ! empty( $vm->user_name ) ) {
                    ?>
                    <span class="rm-portal-header__username"><?php echo esc_html( $vm->user_name ); ?></span>
                    <?php
                }
                if ( ! empty( $vm->logout_url ) ) {
                    ?>
                    <a href="<?php echo esc_url( $vm->logout_url ); ?>" class="rm-portal-header__logout"><?php echo esc_html__( 'Log Out', 'racketmanager' ); ?></a>
                    <?php
                }
                ?>
            </div>
        </header>

        <div class="rm-portal-navigation-bar">
            <?php $renderer->render( 'portal/navigation', $vm->navigation ); ?>
        </div>

        <main class="rm-portal-main" id="rm-portal-content">
            <?php
            if ( ! empty( $vm->flash['message'] ) ) {
                $notice_type = ! empty( $vm->flash['message_type'] ) && 'error' === $vm->flash['message_type']
                    ? 'rm-alert--error'
                    : 'rm-alert--success';
                ?>
                <div class="rm-alert <?php echo esc_attr( $notice_type ); ?>" role="alert">
                    <p><?php echo esc_html( strval( $vm->flash['message'] ) ); ?></p>
                </div>
                <?php
            }
            ?>

            <div class="rm-portal-content-body">
                <?php echo $vm->content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pre-rendered by presenter ?>
            </div>
        </main>

        <footer class="rm-portal-footer">
            <p>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( get_bloginfo( 'name' ) ); ?>. <?php echo esc_html__( 'All rights reserved.', 'racketmanager' ); ?></p>
        </footer>
    </div>

    <?php wp_footer(); ?>
    </body>
</html>
