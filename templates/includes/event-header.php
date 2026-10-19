<?php
/**
 * Template page for event header
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

use Racketmanager\Presentation\View_Models\Event_Header_View_Model;

/** @var object|null $club */
/** @var object|null $event */
/** @var Event_Header_View_Model|null $event_header */
/** @var string|null $curr_season */
/** @var array|null $seasons */
/** @var int|null $header_level */
/** @var string|null $standings_template */

$header_vm = $event_header ?? null;
if ( ! $header_vm instanceof Event_Header_View_Model ) {
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
                <h<?php echo esc_attr( (string) $header_vm->header_level ); ?> class="media__title"><?php echo esc_html( $header_vm->name ); ?></h<?php echo esc_attr( (string) $header_vm->header_level ); ?>>
                <div class="media__content-subinfo">
                    <?php if ( ! empty( $header_vm->competition_name ) && ! empty( $header_vm->competition_link ) ) : ?>
                        <small class="media__subheading">
                            <span class="nav--link">
                                <a href="<?php echo esc_url( $header_vm->competition_link ); ?>">
                                    <span class="nav-link__value">
                                        <?php echo esc_html( $header_vm->competition_name ); ?>
                                    </span>
                                </a>
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
            <?php if ( $header_vm->show_season_selector ) : ?>
                <div class="media__aside">
                    <form method="get" action="<?php echo esc_url( $header_vm->form_action ); ?>" id="racketmanager_competition_archive" class="season-select">
                        <input type="hidden" name="page_id" value="<?php echo esc_attr( (string) $header_vm->post_id ); ?>" />
                        <input type="hidden" name="pagename" id="pagename" value="<?php echo esc_attr( $header_vm->pagename ); ?>" />
                        <div class="row g-1 align-items-center">
                            <div class="col-md">
                                <div class="form-floating">
                                    <select class="form-select" size="1" name="season" id="season">
                                        <?php
                                        // Only iterate arrays and ensure each season item is an array with a name
                                        foreach ( array_reverse( $header_vm->seasons ) as $season ) {
                                            if ( ! is_array( $season ) ) {
                                                continue;
                                            }
                                            $option_name = $season['name'] ?? '';
                                            if ( '' === $option_name ) {
                                                continue;
                                            }
                                            ?>
                                            <option value="<?php echo esc_html( $option_name ); ?>" <?php selected( $option_name, $header_vm->curr_season ); ?>>
                                                <?php echo esc_html( $option_name ); ?>
                                            </option>
                                            <?php
                                        }
                                        ?>
                                    </select>
                                    <label for="season"><?php echo esc_html( $header_vm->season_label ); ?></label>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
