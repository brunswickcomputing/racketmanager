<?php
/**
 *
 * Template page for event header
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var object $club */
/** @var object $event */
/** @var string $curr_season */
global $wp_query, $racketmanager;
$post_id = $wp_query->post->ID ?? ''; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
if ( isset( $wp_query->query['pagename'] ) && 'club/event' === $wp_query->query['pagename'] ) {
    $pagename = '/clubs/' . seo_url( $club->shortcode ) . '/event/' . seo_url( $event->name ) . '/';
} elseif ( 'tournament' === $event->competition->type ) {
    $pagename = $wp_query->query['pagename'] ?? '';
} else {
    $pagename = '/' . $event->competition->type . 's/' . seo_url( $event->name ) . '/';
}
if ( $event->is_box ) {
    $season_label = __( 'Round', 'racketmanager' );
} else {
    $season_label = __( 'Season', 'racketmanager' );
}
$image = match ($event->competition->type) {
    'league' => 'images/bootstrap-icons.svg#table',
    'cup' => 'images/bootstrap-icons.svg#trophy-fill',
    'tournament' => 'images/lta-icons.svg#icon-bracket',
    default => null,
};
$seasons     = $event->seasons;
$curr_season = $event->current_season['name'];
if ( empty( $header_level ) ) {
    $header_level = 1;
}
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
                <h<?php echo esc_attr( $header_level ); ?> class="media__title"><?php echo esc_html( $event->name ); ?></h<?php echo esc_attr( $header_level ); ?>>
                <div class="media__content-subinfo">
                    <?php
                    if ( ! empty( $event->competition->name ) ) {
                        ?>
                        <small class="media__subheading">
                            <span class="nav--link">
                                <a href="/<?php echo esc_html( seo_url( $event->competition->name ) ); ?>/<?php echo esc_html( $curr_season ); ?>/">
                                    <span class="nav-link__value">
                                        <?php echo esc_html( $event->competition->name ); ?>
                                    </span>
                                </a>
                            </span>
                        </small>
                        <?php
                    }
                    ?>
                    <?php
                    if ( ! empty( $event->competition->current_season['date_start'] ) && ! empty( $event->competition->current_season['date_end'] ) ) {
                        ?>
                        <small class="media__subheading">
                            <span class="nav--link">
                                <span class="nav-link__value">
                                    <svg width="32" height="32" class="icon ">
                                        <use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'images/bootstrap-icons.svg#calendar-range-fill' ); ?>"></use>
                                    </svg>
                                    <?php echo esc_html( mysql2date( $racketmanager->date_format, $event->competition->current_season['date_start'] ) ); ?> <?php esc_html_e( 'to', 'racketmanager' ); ?> <?php echo esc_html( mysql2date( $racketmanager->date_format, $event->competition->current_season['date_end'] ) ); ?>
                                </span>
                            </span>
                        </small>
                        <?php
                    }
                    ?>
                </div>
            </div>
            <?php
            if ( empty( $standings_template ) || 'constitution' !== $standings_template ) {
                ?>
                <div class="media__aside">
                    <form method="get" action="<?php echo esc_html( get_permalink( $post_id ) ); ?>" id="racketmanager_competition_archive" class="season-select">
                        <input type="hidden" name="page_id" value="<?php echo esc_html( $post_id ); ?>" />
                        <input type="hidden" name="pagename" id="pagename" value="<?php echo esc_html( $pagename ); ?>" />
                        <div class="row g-1 align-items-center">
                            <div class="col-md">
                                <div class="form-floating">
                                    <select class="form-select" size="1" name="season" id="season">
                                        <?php
                                        foreach ( array_reverse( $seasons ) as $season ) {
                                            $option_name = $season['name'];
                                            ?>
                                            <option value="<?php echo esc_html( $season['name'] ); ?>" <?php selected( $season['name'], $curr_season ); ?>>
                                                <?php echo esc_html( $option_name ); ?>
                                            </option>
                                            <?php
                                        }
                                        ?>
                                    </select>
                                    <label for="season"><?php echo esc_html( $season_label ); ?></label>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <?php
            }
            ?>
        </div>
    </div>
</div>
