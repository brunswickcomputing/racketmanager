<?php
/**
 * Template page to display clubs to be chosen as entry
 *
 * @package Racketmanager/Templates
 *
 * The following variables are usable:
 *  $clubs: array of club objects
 *
 * You can check the content of a variable when you insert the tag <?php var_dump($variable) ?>
 */

namespace Racketmanager;

/** @var array $clubs */
?>
<div class="container">
    <?php
    require RACKETMANAGER_PATH . 'templates/includes/competition-header.php';
    ?>
    <div class="module module--card">
        <div class="module__banner">
            <h3 class="module__title"><?php esc_html_e( 'Select club for entry', 'racketmanager' ); ?></h3>
        </div>
        <div class="module__content">
            <div class="module-container">
                <div class="module">
                    <div class="row mb-2 row-header">
                        <div class="col-3 col-md-1"></div>
                        <div class="col-6">
                            <?php esc_html_e( 'Club', 'racketmanager' ); ?>
                        </div>
                    </div>
                    <?php
                    foreach ( $clubs as $club ) {
                        ?>
                        <div class="row mb-2 row-list">
                            <div class="col-3 col-md-1">
                                <a href="<?php echo esc_attr( seo_url( $club->shortcode ) ); ?>/" class="btn btn-primary" role="button"><?php esc_html_e( 'Enter', 'racketmanager' ); ?></a>
                            </div>
                            <div class="col-6">
                                <span class=""><?php echo esc_html( $club->shortcode ); ?></span>
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>
