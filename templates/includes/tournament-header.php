<?php
/**
 *
 * Template page for Tournament header
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

use Racketmanager\Domain\Tournament\Tournament;
use Racketmanager\Presentation\View_Models\Tournament_Header_View_Model;

/** @var Tournament|object|null $tournament */
/** @var Tournament_Header_View_Model|null $tournament_header */
/** @var bool|null $entry_option */

$header_vm = $tournament_header ?? null;
if ( ! $header_vm instanceof Tournament_Header_View_Model ) {
    return;
}
?>
    <div class="page-subhead competition">
        <div class="media tournament-head">
            <div class="media__wrapper">
                <div class="media__img">
                    <svg width="16" height="16" class="media__img-element--icon">
                        <use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'assets/icons/lta-icons.svg#icon-bracket' ); ?>"></use>
                    </svg>
                </div>
                <div class="media__content">
                    <h1 class="media__title"><?php echo esc_html( $header_vm->name ) . ' - ' . esc_html__( 'Tournament', 'racketmanager' ); ?></h1>
                    <div class="media__content-subinfo">
                        <?php if ( ! empty( $header_vm->venue_name ) ) : ?>
                            <small class="media__subheading">
                                <span class="nav--link">
                                    <span class="nav-link__value">
                                        <?php echo esc_html( $header_vm->venue_name ); ?>
                                    </span>
                                </span>
                            </small>
                        <?php endif; ?>
                        <?php if ( $header_vm->has_date_range ) : ?>
                            <small class="media__subheading">
                                <span class="nav--link">
                                    <span class="nav-link__value">
                                        <svg width="32" height="32" class="icon ">
                                            <use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'assets/icons/bootstrap-icons.svg#calendar-range-fill' ); ?>"></use>
                                        </svg>
                                        <?php echo esc_html( $header_vm->date_start_display ); ?> <?php esc_html_e( 'to', 'racketmanager' ); ?> <?php echo esc_html( $header_vm->date_end_display ); ?>
                                    </span>
                                </span>
                            </small>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="media__aside">
                    <?php if ( $header_vm->show_entry_button && ! empty( $header_vm->entry_url ) ) : ?>
                        <a href="<?php echo esc_url( $header_vm->entry_url ); ?>" class="btn btn-primary reverse">
                            <svg width="16" height="16" class="icon ">
                                <use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'assets/icons/bootstrap-icons.svg#pencil' ); ?>"></use>
                            </svg>
                            <span><?php esc_html_e( 'Enter', 'racketmanager' ); ?></span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
