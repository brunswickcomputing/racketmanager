<?php
/**
 *
 * Template page for Tournament header
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

global $racketmanager;
/** @var object $tournament */
?>
    <div class="page-subhead competition">
        <div class="media tournament-head">
            <div class="media__wrapper">
                <div class="media__img">
                    <svg width="16" height="16" class="media__img-element--icon">
                        <use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'images/lta-icons.svg#icon-bracket' ); ?>"></use>
                    </svg>
                </div>
                <div class="media__content">
                    <h1 class="media__title"><?php echo esc_html( $tournament->name ) . ' - ' . esc_html__( 'Tournament', 'racketmanager' ); ?></h1>
                    <div class="media__content-subinfo">
                        <small class="media__subheading">
                            <span class="nav--link">
                                <span class="nav-link__value">
                                    <?php echo esc_html( $tournament->venue_name ); ?>
                                </span>
                            </span>
                        </small>
                        <?php
                        if ( ! empty( $tournament->date_start ) && ! empty( $tournament->date ) ) {
                            ?>
                            <small class="media__subheading">
                                <span class="nav--link">
                                    <span class="nav-link__value">
                                        <?php racketmanager_the_svg( 'icon-calendar' ); ?>
                                        <?php echo esc_html( mysql2date( $racketmanager->date_format, $tournament->date_start ) ); ?> <?php esc_html_e( 'to', 'racketmanager' ); ?> <?php echo esc_html( mysql2date( $racketmanager->date_format, $tournament->date ) ); ?>
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
                    if ( $tournament->is_open && ! empty( $entry_option ) ) {
                        ?>
                        <a href="/tournament/entry-form/<?php echo esc_attr( seo_url( $tournament->name ) ); ?>/" class="btn btn-primary reverse">
                            <i class="racketmanager-svg-icon">
                                <?php racketmanager_the_svg( 'icon-pencil' ); ?>
                            </i>
                            <span><?php esc_html_e( 'Enter', 'racketmanager' ); ?></span>
                        </a>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
