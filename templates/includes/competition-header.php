<?php
/**
 * Template page to display competition header
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

use Racketmanager\Presentation\View_Models\Competition_Header_View_Model;

/** @var object|null $competition */
/** @var Competition_Header_View_Model|null $competition_header */
/** @var string|int|null $post_id */
/** @var string|null $pagename */
/** @var array|null $competition_season */
/** @var string|null $season */
/** @var array|null $seasons */

$header_vm = $competition_header ?? null;
if ( ! $header_vm instanceof Competition_Header_View_Model ) {
    return;
}
?>
<div class="page-subhead competition">
    <div class="media competition-head">
        <div class="media__wrapper">
            <?php if ( ! empty( $header_vm->icon_path ) ) : ?>
                <div class="media__img">
                    <svg width="16" height="16" class="media__img-element--icon">
                        <use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . $header_vm->icon_path ); ?>"></use>
                    </svg>
                </div>
            <?php endif; ?>
            <div class="media__content">
                <h1 class="media__title"><?php echo esc_html( $header_vm->name ); ?><?php echo empty( $header_vm->season_title ) ? null : ' - ' . esc_html( $header_vm->season_title ); ?></h1>
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
                <?php if ( ! empty( $header_vm->entry_link ) ) : ?>
                    <a href="<?php echo esc_url( $header_vm->entry_link ); ?>" class="btn btn-primary reverse">
                        <svg width="16" height="16" class="icon ">
                            <use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'assets/icons/bootstrap-icons.svg#pencil' ); ?>"></use>
                        </svg>
                        <span><?php esc_html_e( 'Enter', 'racketmanager' ); ?></span>
                    </a>
                <?php endif; ?>
                <?php if ( $header_vm->show_season_selector ) : ?>
                    <form method="get" action="<?php echo esc_url( $header_vm->form_action ); ?>" id="racketmanager_competition_archive" class="season-select">
                        <input type="hidden" name="page_id" value="<?php echo esc_attr( (string) $header_vm->post_id ); ?>" />
                        <input type="hidden" name="pagename" id="pagename" value="<?php echo esc_attr( $header_vm->pagename ); ?>" />
                        <div class="row g-1 align-items-center">
                            <div class="col-md">
                                <div class="form-floating">
                                    <select class="form-select" size="1" name="season" id="season">
                                        <?php
                                        foreach ( array_reverse( $header_vm->seasons ) as $season_item ) {
                                            $option_name = $season_item['name'] ?? '';
                                            if ( $option_name ) {
                                                ?>
                                                <option value="<?php echo esc_html( $option_name ); ?>" <?php selected( $option_name, $header_vm->current_season_name ); ?>>
                                                    <?php echo esc_html( $option_name ); ?>
                                                </option>
                                                <?php
                                            }
                                        }
                                        ?>
                                    </select>
                                    <label for="season"><?php esc_html_e( 'Seasons', 'racketmanager' ); ?></label>
                                </div>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
