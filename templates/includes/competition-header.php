<?php
/**
 *
 * Template page to display competition header
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

global $racketmanager;
/** @var object $competition */
/** @var string $post_id */
/** @var string $pagename */
/** @var array $competition_season */
$image = match ($competition->type) {
    'league' => 'images/bootstrap-icons.svg#table',
    'cup' => 'images/bootstrap-icons.svg#trophy-fill',
    'tournament' => 'images/lta-icons.svg#icon-bracket',
    default => null,
};
?>
<div class="page-subhead competition">
    <div class="media competition-head">
        <div class="media__wrapper">
            <div class="media__img">
                <svg width="16" height="16" class="media__img-element--icon">
                    <use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . $image ); ?>"></use>
                </svg>
            </div>
            <div class="media__content">
                <h1 class="media__title"><?php echo esc_html( $competition->name ); ?><?php echo empty( $season ) ? null : ' - ' . esc_html( $season ); ?></h1>
                <div class="media__content-subinfo">
                    <?php
                    if ( ! empty( $competition_season['venue_name'] ) ) {
                        ?>
                        <small class="media__subheading">
                            <span class="nav--link">
                                <span class="nav-link__value">
                                    <?php echo esc_html( $competition_season['venue_name'] ); ?>
                                </span>
                            </span>
                        </small>
                        <?php
                    }
                    ?>
                    <?php
                    if ( ! empty( $competition_season['date_start'] ) && ! empty( $competition_season['date_end'] ) ) {
                        ?>
                        <small class="media__subheading">
                            <span class="nav--link">
                                <span class="nav-link__value">
                                    <svg width="32" height="32" class="icon ">
                                        <use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'images/bootstrap-icons.svg#calendar-range-fill' ); ?>"></use>
                                    </svg>
                                    <?php echo esc_html( mysql2date( $racketmanager->date_format, $competition_season['date_start'] ) ); ?> <?php esc_html_e( 'to', 'racketmanager' ); ?> <?php echo esc_html( mysql2date( $racketmanager->date_format, $competition_season['date_end'] ) ); ?>
                                </span>
                            </span>
                        </small>
                        <?php
                    }
                    ?>
                </div>
            </div>
            <div class="media__aside">
                <?php
                if ( ! empty( $competition->entry_link ) ) {
                    ?>
                    <a href="<?php echo esc_url( $competition->entry_link ); ?>" class="btn btn-primary reverse">
                        <i class="racketmanager-svg-icon">
                            <?php racketmanager_the_svg( 'icon-pencil' ); ?>
                        </i>
                        <span><?php esc_html_e( 'Enter', 'racketmanager' ); ?></span>
                    </a>
                    <?php
                }
                if ( empty( $season ) ) {
                    ?>
                    <form method="get" action="<?php echo esc_html( get_permalink( $post_id ) ); ?>" id="racketmanager_competition_archive" class="season-select">
                        <input type="hidden" name="page_id" value="<?php echo esc_html( $post_id ); ?>" />
                        <input type="hidden" name="pagename" id="pagename" value="<?php echo esc_html( $pagename ); ?>" />
                        <div class="row g-1 align-items-center">
                            <div class="col-md">
                                <div class="form-floating">
                                    <select class="form-select" size="1" name="season" id="season">
                                        <?php
                                        foreach ( array_reverse( $competition->seasons ) as $season ) {
                                            $option_name = $season['name'];
                                            ?>
                                            <option value="<?php echo esc_html( $season['name'] ); ?>" <?php selected( $season['name'], $competition_season['name'] ); ?>>
                                                <?php echo esc_html( $option_name ); ?>
                                            </option>
                                            <?php
                                        }
                                        ?>
                                    </select>
                                    <label for="season"><?php esc_html_e( 'Seasons', 'racketmanager' ); ?></label>
                                </div>
                            </div>
                        </div>
                    </form>
                    <?php
                }
                ?>
            </div>
        </div>
    </div>
</div>
