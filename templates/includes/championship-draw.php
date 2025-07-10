<?php
/**
 * Template for draw
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var array $finals */
/** @var object $league */
if ( ! $finals )
    return;
$num_rounds = count( $finals );
if ( ! empty( $num_rounds ) ) {
    $cols = floor( 12 / $num_rounds );
    if ( 1 === intval( $cols ) ) {
        $cols = 2;
    }
} else {
    $cols = 12;
}
$player_class = '';
if ( $league->event->competition->is_player_entry && isset( $league->event->type ) && 'D' === substr( $league->event->type, 1, 1 ) ) {
    $player_class = 'doubles';
}
?>
<div class="d-none d-md-block knockout-layout">
    <div class="knockout-carousel">
        <div class="knockout-carousel__header row">
            <?php
            foreach ( $finals as $final ) {
                ?>
                <h4 class="d-none d-md-block col-md-<?php echo esc_html( $cols ); ?>"><?php echo esc_html( $final->name ); ?></h4>
                <?php
            }
            ?>
        </div>
        <div class="x-swiper-container knockout-carousel__draw-carousel">
            <div class="knockout-tree x-swiper-wrapper row">
                <?php
                $f = 1;
                foreach ( $finals as $final ) {
                    if ( count( $finals ) === $f ) {
                        $last_round = true;
                    } else {
                        $last_round = false;
                    }
                    ?>
                    <div class="d-none d-md-flex column-<?php echo esc_attr( $f ); ?> knockout-tree__column x-swiper-slide col-12 col-md-<?php echo esc_html( $cols ); ?>">
                        <?php
                        require 'round-draw.php';
                        ?>
                    </div>
                    <?php
                    ++$f;
                }
                ?>
            </div>
        </div>
    </div>
</div>
<div class="d-block d-md-none knockout-layout">
    <div class="knockout-carousel carousel" id="draw-<?php echo esc_attr( $league->id ); ?>" data-bs-wrap="false">
        <div class="round-navigation">
            <button class="carousel-control-prev" type="button" data-bs-target="#draw-<?php echo esc_attr( $league->id ); ?>" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#draw-<?php echo esc_attr( $league->id ); ?>" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>
        </div>
        <div class="x-swiper-container knockout-carousel__draw-carousel carousel-inner">
            <div class="knockout-tree x-swiper-wrapper row">
                <?php
                $f = 1;
                foreach ( $finals as $final ) {
                    if ( count( $finals ) === $f ) {
                        $last_round = true;
                    } else {
                        $last_round = false;
                    }
                    ?>
                    <div class="carousel-item <?php echo 1 === $f ? 'active' : ''; ?>">
                        <div class="knockout-carousel__header row">
                            <h4 class="d-block d-md-none"><?php echo esc_html( $final->name ); ?></h4>
                        </div>
                        <div class="d-flex d-md-none column-<?php echo esc_attr( $f ); ?> knockout-tree__column x-swiper-slide col-12">
                            <?php
                            require 'round-draw.php';
                            ?>
                        </div>
                    </div>
                    <?php
                    ++$f;
                }
                ?>
            </div>
        </div>
    </div>
</div>

