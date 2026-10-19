<?php
/**
 * Template page to display competition
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var object $competition */
/** @var array $menu_options */
/** @var bool $is_singular */
/** @var string $competition_seo_name */
/** @var string $pagename */

$competition_seo_name = $competition_seo_name ?? '';
$is_singular          = $is_singular ?? false;
$pagename             = $pagename ?? $competition_seo_name;
?>
<div class="container">
    <?php require 'includes/competition-header.php'; ?>
    <div id="pageContentTab">
        <nav class="navbar navbar-expand-lg">
            <div class="">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse mt-3" id="navbarSupportedContent">
                    <!-- Nav tabs -->
                    <ul class="nav nav-pills frontend" id="myTab" role="tablist">
                        <?php
                        foreach ( $menu_options as $option ) {
                            if ( $option['available'] ) {
                                $singular_class = $option['selected'] && ! empty( $is_singular ) ? 'is-singular' : null;
                                ?>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link tabData <?php echo $option['selected'] ? 'active' : null; ?> <?php echo esc_attr( $singular_class ); ?>" id="<?php echo esc_attr( $option['name'] ); ?>-tab" data-bs-toggle="pill" data-bs-target="#<?php echo esc_attr( $option['name'] ); ?>" type="button" role="tab" aria-controls="<?php echo esc_attr( $option['name'] ); ?>" aria-selected="<?php echo esc_attr( $option['selected'] ); ?>" data-type="competition" data-type-id="<?php echo esc_attr( $competition->id ); ?>" data-season="<?php echo esc_attr( $competition->current_season['name'] ); ?>" data-name="<?php echo esc_attr( $competition_seo_name ?? ( $pagename ?? '' ) ); ?>" data-competition-type=""><?php echo esc_attr( $option['description'] ); ?></button>
                                </li>
                                <?php
                            }
                        }
                        ?>
                    </ul>
                </div>
            </div>
        </nav>
        <div class="tab-content" id="competitionTabContent">
            <?php require RACKETMANAGER_PATH . 'templates/includes/loading.php'; ?>
            <?php
            foreach ( $menu_options as $option ) {
                if ( $option['available'] ) {
                    ?>
                    <div class="tab-pane <?php echo $option['selected'] ? 'active' : 'fade'; ?>" id="<?php echo esc_attr( $option['name'] ); ?>" role="tabpanel" aria-labelledby="<?php echo esc_attr( $option['name'] ); ?>-tab">
                        <?php
                        if ( $option['selected'] ) {
                            $function_name = 'Racketmanager\competition_' . $option['name'];
                            if ( function_exists( $function_name ) ) {
                                $function_name( $competition->id, array( 'season' => $competition->current_season['name'] ) );
                            } else {
                                /* translators: %s: function name */
                                printf( esc_html__( 'function %s does not exist', 'racketmanager' ), esc_attr( $function_name ) );
                            }
                        }
                        ?>
                    </div>
                    <?php
                }
            }
            ?>
        </div>
    </div>
</div>
