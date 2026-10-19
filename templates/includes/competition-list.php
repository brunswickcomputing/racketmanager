<?php
/**
 * Template for a competition list
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

use Racketmanager\Presentation\View_Models\Competition_List_Item_View_Model;

/** @var array|null $competition_list */
/** @var string|null $competition_type */
/** @var object|null $player */
/** @var bool|null $full_width */

if ( empty( $competition_list ) ) {
    return;
}

foreach ( $competition_list as $item ) {
    if ( ! $item instanceof Competition_List_Item_View_Model ) {
        continue;
    }
    ?>
    <li class="list__item col-12 <?php echo empty( $full_width ) ? 'col-sm-6' : null; ?>">
        <div class="media">
            <div class="media__wrapper">
                <?php if ( ! empty( $item->icon_path ) ) : ?>
                    <div class="media__img">
                        <svg width="16" height="16" class="media__img-element--icon">
                            <use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . $item->icon_path ); ?>"></use>
                        </svg>
                    </div>
                <?php endif; ?>
                <div class="media__content">
                    <h4 class="media__title">
                        <a class="nav--link media__link" href="<?php echo esc_url( $item->link ); ?>">
                            <span class="nav-link__value"><?php echo esc_html( $item->name ); ?></span>
                        </a>
                    </h4>
                    <div class="media__content-subinfo">
                        <?php if ( ! empty( $item->venue_name ) ) : ?>
                            <small class="media__subheading">
                                <span class="nav--link">
                                    <span class="nav-link__value">
                                        <?php echo esc_html( $item->venue_name ); ?>
                                    </span>
                                </span>
                            </small>
                        <?php endif; ?>
                        <?php if ( $item->has_date_range ) : ?>
                            <small class="media__subheading">
                                <span class="nav--link">
                                    <span class="nav-link__value">
                                        <svg width="16" height="16" class="icon ">
                                            <use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'assets/icons/bootstrap-icons.svg#calendar-range' ); ?>"></use>
                                        </svg>
                                        <?php echo esc_html( $item->date_start_display ); ?> <?php esc_html_e( 'to', 'racketmanager' ); ?> <?php echo esc_html( $item->date_end_display ); ?>
                                    </span>
                                </span>
                            </small>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="media__aside">
                    <?php if ( $item->is_open && ! empty( $item->entry_url ) ) : ?>
                        <a href="<?php echo esc_url( $item->entry_url ); ?>" class="btn btn-primary">
                            <svg width="16" height="16" class="icon ">
                                <use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'assets/icons/bootstrap-icons.svg#pencil' ); ?>"></use>
                            </svg>
                            <span class="btn__text"><?php esc_html_e( 'Enter', 'racketmanager' ); ?></span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <ul class="media__icons">
                <li class="media__icons-item">
                </li>
            </ul>
        </div>
    </li>
    <?php
}
