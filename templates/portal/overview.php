<?php
declare( strict_types=1 );
/**
 * Portal Overview Screen Template
 *
 * Explains the RacketManager service, core operational pillars, and available management options.
 *
 * @package RacketManager
 * @var Overview_Page_View_Model $vm
 */

use Racketmanager\Admin\View_Models\Overview_Option_Item;
use Racketmanager\Admin\View_Models\Overview_Page_View_Model;

// Group options by functional category for structured presentation.
/** @var array<string, array<Overview_Option_Item>> $grouped_options */
$grouped_options = [];
foreach ( $vm->options as $option ) {
    $grouped_options[ $option->category ][] = $option;
}
?>
<div class="container-fluid px-0">
    <!-- Service Introduction Banner -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body p-4">
            <div class="row align-items-center">
                <div class="col-12">
                    <span class="badge bg-primary text-uppercase mb-2"><?php echo esc_html__( 'Management Portal', 'racketmanager' ); ?></span>
                    <h1 class="h2 mb-3"><?php echo esc_html( $vm->service_name ); ?> &mdash; <?php echo esc_html( $vm->title ); ?></h1>
                    <p class="lead text-secondary mb-0">
                        <?php echo esc_html( $vm->service_summary ); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Core Operational Pillars -->
    <?php if ( ! empty( $vm->service_pillars ) ) : ?>
        <div class="row g-3 mb-4">
            <?php foreach ( $vm->service_pillars as $pillar_title => $pillar_description ) : ?>
                <div class="col-12 col-md-6 col-xl-3">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body p-3">
                            <h3 class="h6 fw-bold text-primary mb-2"><?php echo esc_html( $pillar_title ); ?></h3>
                            <p class="small text-secondary mb-0"><?php echo esc_html( $pillar_description ); ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Categorized Management Options -->
    <?php foreach ( $grouped_options as $category_name => $options_list ) : ?>
        <div class="mb-4">
            <h2 class="h4 pb-2 mb-3 border-bottom"><?php echo esc_html( $category_name ); ?></h2>
            <div class="row g-3">
                <?php foreach ( $options_list as $option ) : ?>
                    <div class="col-12 col-md-6 col-lg-4">
                        <div class="card h-100 shadow-sm border-0 d-flex flex-column">
                            <div class="card-header bg-white border-bottom-0 pt-3 pb-0 d-flex justify-content-between align-items-center">
                                <h3 class="h5 mb-0 text-dark"><?php echo esc_html( $option->title ); ?></h3>
                                <?php if ( ! empty( $option->badge ) ) : ?>
                                    <span class="badge bg-secondary"><?php echo esc_html( $option->badge ); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="card-body d-flex flex-column pt-2">
                                <p class="text-secondary small mb-3 flex-grow-1">
                                    <?php echo esc_html( $option->description ); ?>
                                </p>
                                <?php if ( ! empty( $option->capabilities ) ) : ?>
                                    <ul class="list-unstyled small text-muted mb-3">
                                        <?php foreach ( $option->capabilities as $capability ) : ?>
                                            <li class="mb-1 d-flex align-items-center">
                                                <span class="me-2 text-primary">&bull;</span>
                                                <?php echo esc_html( $capability ); ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                                <div class="mt-auto pt-2">
                                    <a href="<?php echo esc_url( $option->url ); ?>" class="btn btn-primary btn-sm w-100">
                                        <?php
                                        /* translators: %s: Option title */
                                        echo esc_html( sprintf( __( 'Open %s', 'racketmanager' ), $option->title ) );
                                        ?>
                                        &rarr;
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>
