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
        <title><?php echo esc_html( $vm->title ); ?> &mdash; <?php echo esc_html( ! empty( $vm->site_name ) ? $vm->site_name : 'RacketManager' ); ?></title>
        <?php wp_head(); ?>
    </head>
    <body <?php body_class( 'racketmanager-portal-shell' ); ?>>
    <?php wp_body_open(); ?>

    <a class="skip-link rm-portal-skip-link screen-reader-text" href="#rm-portal-content">
        <?php echo esc_html__( 'Skip to content', 'racketmanager' ); ?>
    </a>

    <div class="rm-portal-wrapper">
        <header class="rm-portal-header">
            <a href="<?php echo esc_url( ! empty( $vm->portal_home_url ) ? $vm->portal_home_url : '/manage/overview' ); ?>" class="rm-portal-header__brand">
                <?php echo esc_html__( 'Manage - ', 'racketmanager' ); ?><?php echo esc_html( ! empty( $vm->site_name ) ? $vm->site_name : 'RacketManager' ); ?>
            </a>
            <div class="rm-portal-header__actions rm-portal-header__user">
                <a href="<?php echo esc_url( ! empty( $vm->site_url ) ? $vm->site_url : '/' ); ?>" class="rm-portal-header__site-link">
                    <?php echo esc_html__( 'View Site', 'racketmanager' ); ?>
                </a>
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

        <div class="rm-portal-body">
            <aside class="rm-portal-sidebar" aria-label="<?php echo esc_attr__( 'Portal Sidebar', 'racketmanager' ); ?>">
                <?php $renderer->render( 'portal/navigation', $vm->navigation ); ?>
            </aside>

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

                <footer class="rm-portal-footer">
                    <p>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> <?php echo esc_html( ! empty( $vm->site_name ) ? $vm->site_name : 'RacketManager' ); ?>. <?php echo esc_html__( 'All rights reserved.', 'racketmanager' ); ?></p>
                </footer>
            </main>
        </div>
    </div>

    <?php wp_footer(); ?>
    </body>
</html>
