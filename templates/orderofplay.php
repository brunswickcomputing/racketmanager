<?php
/**
 * Template for order of play
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

use Racketmanager\Presentation\View_Models\Order_Of_Play_View_Model;

/** @var Order_Of_Play_View_Model $order_of_play */
$col_width   = $order_of_play instanceof Order_Of_Play_View_Model ? $order_of_play->col_width : 12;
$is_expanded = $order_of_play instanceof Order_Of_Play_View_Model && $order_of_play->is_expanded;
$times       = $order_of_play instanceof Order_Of_Play_View_Model ? $order_of_play->times : array();
$courts      = $order_of_play instanceof Order_Of_Play_View_Model ? $order_of_play->courts : array();
?>
<script>
    jQuery(document).ready(function() {
    });
</script>
<div class="container">
    <div class="module module--card">
        <div class="module__banner">
            <h3 class="module__title"><?php esc_html_e( 'Order of play', 'racketmanager' ); ?></h3>
        </div>
        <div class="module__content">
            <div class="module-container">
                <?php
                if ( ! empty( $courts ) ) {
                    ?>
                    <div id="order-of-play" class="container">
                        <div class="d-none d-md-block">
                            <div class="row">
                                <div class="col-2 col-md-1">
                                    <div class="row">
                                        <div class="col-12">
                                            <h4 class="match-group__header">
                                                <span><?php esc_html_e( 'Time', 'racketmanager' ); ?></span>
                                            </h4>
                                            <?php
                                            foreach ( $times as $time ) {
                                                ?>
                                                <div class="match-group__item-wrapper time-display<?php echo empty( $is_expanded ) ? null : ' is-expanded'; ?>" id="<?php echo esc_html( $time ); ?>"><?php echo esc_html( $time ); ?>
                                                </div>
                                                <?php
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-10 col-md-11">
                                    <div class="row">
                                        <?php
                                        foreach ( $courts as $court => $court_times ) {
                                            ?>
                                            <div class="col-12 col-md-<?php echo esc_attr( $col_width ); ?>" id="<?php echo esc_html( $court ); ?>">
                                                <h4 class="match-group__header">
                                                    <span><?php echo esc_html( $court ); ?></span>
                                                </h4>
                                                <?php
                                                require 'includes/order-of-play-court.php';
                                                ?>
                                            </div>
                                            <?php
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="d-block d-md-none">
                            <div class="row">
                                <div class="col-2">
                                    <div class="match-group__header"></div>
                                    <?php
                                    foreach ( $times as $time ) {
                                        ?>
                                        <div class="match-group__item-wrapper time-display<?php echo empty( $is_expanded ) ? null : ' is-expanded'; ?>" id="<?php echo esc_html( $time ); ?>"><?php echo esc_html( $time ); ?>
                                        </div>
                                        <?php
                                    }
                                    ?>
                                </div>
                                <div class="col-10">
                                    <div class="carousel" id="order-of-play-carousel" data-bs-wrap="false">
                                        <div class="court-navigation">
                                            <button class="carousel-control-prev" type="button" data-bs-target="#order-of-play-carousel" data-bs-slide="prev">
                                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                                <span class="visually-hidden">Previous</span>
                                            </button>
                                            <button class="carousel-control-next" type="button" data-bs-target="#order-of-play-carousel" data-bs-slide="next">
                                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                                <span class="visually-hidden">Next</span>
                                            </button>
                                        </div>
                                        <div class="carousel-inner">
                                            <div class="row">
                                                <?php
                                                $c = 1;
                                                foreach ( $courts as $court => $court_times ) {
                                                    ?>
                                                    <div class="carousel-item <?php echo 1 === $c ? 'active' : ''; ?>">
                                                        <div class="row">
                                                            <h4 class="match-group__header">
                                                                <span><?php echo esc_html( $court ); ?></span>
                                                            </h4>
                                                        </div>
                                                        <?php
                                                        require 'includes/order-of-play-court.php';
                                                        ?>
                                                    </div>
                                                    <?php
                                                    ++$c;
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php
                } else {
                    esc_html_e( 'No order of play', 'racketmanager' );
                }
                ?>
            </div>
        </div>
    </div>
</div>
