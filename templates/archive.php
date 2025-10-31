<?php
/**
 *
 * Template page for the Archive
 *
 * @package Racketmanager/Templates
 *
 * The following variables are usable:
 *  $leagues: array of all leagues
 *  $league: current league
 *  $seasons: array of all seasons
 *
 * You can check the content of a variable when you insert the tag <?php var_dump($variable) ?>
 */

namespace Racketmanager;

global $wp_query, $wp;

use function get_query_var;
use function set_query_var;

$post_id = $wp_query->post->ID; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
/** @var object $league */
$pagename  = '/' . $league->event->competition->type . '/' . seo_url( $league->title ) . '/';
$archive   = true;
$match_day = get_query_var( 'match_day' );
if ( '0' === $match_day ) {
    $match_day = '-1';
    set_query_var( 'match_day', '-1' );
}
if ( empty( $tab ) ) {
    if ( isset( $wp->query_vars['player_id'] ) ) {
        $tab = 'players'; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
    } elseif ( isset( $wp->query_vars['team'] ) ) {
        $tab = 'teams'; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
    } else {
        $tab = 'standings'; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
    }
}
if ( isset( $_GET['match_day'] ) || isset( $_GET['team_id'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $tab = 'matches'; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
}
if ( $match_day ) {
    $tab = 'matches'; //phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
}
$menu_options               = array();
$menu_options['standings']  = array(
    'name'        => 'standings',
    'selected'    => 'standings' === $tab,
    'available'   => true,
    'description' => __( 'Standings', 'racketmanager' ),
);
$menu_options['crosstable'] = array(
    'name'        => 'crosstable',
    'selected'    => 'crosstable' === $tab,
    'available'   => true,
    'description' => __( 'Crosstable', 'racketmanager' ),
);
$menu_options['matches']    = array(
    'name'        => 'matches',
    'selected'    => 'matches' === $tab,
    'available'   => true,
    'description' => __( 'Matches', 'racketmanager' ),
);
$menu_options['teams']      = array(
    'name'        => 'teams',
    'selected'    => 'teams' === $tab,
    'available'   => true,
    'description' => __( 'Teams', 'racketmanager' ),
);
if ( $league->event->competition->is_team_entry ) {
    $menu_options['players'] = array(
        'name'        => 'players',
        'selected'    => 'players' === $tab,
        'available'   => true,
        'description' => __( 'Players', 'racketmanager' ),
    );
}
if ( $league->event->is_box ) {
    $season_title     = __( 'Round', 'racketmanager' );
    $season_selection = __( 'Rounds', 'racketmanager' );
} else {
    $season_title     = __( 'Season', 'racketmanager' );
    $season_selection = __( 'Seasons', 'racketmanager' );
}
$image = match ($league->event->competition->type) {
    'league'     => 'images/bootstrap-icons.svg#table',
    'cup'        => 'images/bootstrap-icons.svg#trophy-fill',
    'tournament' => 'images/lta-icons.svg#icon-bracket',
    default      => null,
};
?>
<div id="archive-<?php echo esc_html( $league->id ); ?>" class="archive">
    <div class="page-subhead competition">
        <div class="media competition-head">
            <div class="media__wrapper">
                <div class="media__img">
                    <svg width="16" height="16" class="media__img-element--icon">
                        <use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . $image ); ?>"></use>
                    </svg>
                </div>
                <div class="media__content">
                    <h1 class="media__title"><?php echo esc_html( $league->title ); ?></h1>
                    <div class="media__content-subinfo">
                        <?php
                        if ( ! empty( $league->event->name ) ) {
                            ?>
                            <small class="media__subheading">
                                <span class="nav--link">
                                    <a href="/<?php echo esc_html( seo_url( $league->event->competition->type ) ); ?>s/<?php echo esc_html( seo_url( $league->event->name ) ); ?>/<?php echo esc_html( $league->current_season['name'] ); ?>/">
                                        <span class="nav-link__value">
                                            <?php echo esc_html( $league->event->name ); ?>
                                        </span>
                                    </a>
                                </span>
                                <span>&nbsp;&#8226&nbsp;</span>
                                <span class="nav--link">
                                    <a href="/<?php echo esc_html( seo_url( $league->event->competition->name ) ); ?>/<?php echo esc_html( $league->current_season['name'] ); ?>/">
                                        <span class="nav-link__value">
                                            <?php echo esc_html( $league->event->competition->name ); ?>
                                        </span>
                                    </a>
                                </span>
                            </small>
                            <?php
                        }
                        ?>
                        <?php
                        if ( ! empty( $league->event->competition->date_start ) && ! empty( $league->event->competition->date_end ) ) {
                            ?>
                        <small class="media__subheading">
                            <span class="nav--link">
                                <span class="nav-link__value">
                                    <svg width="32" height="32" class="icon ">
                                        <use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'images/bootstrap-icons.svg#calendar-range-fill' ); ?>"></use>
                                    </svg>
                                    <?php echo esc_html( mysql2date( 'j M Y', $league->event->competition->date_start ) ); ?> <?php esc_html_e( 'to', 'racketmanager' ); ?> <?php echo esc_html( mysql2date( 'j M Y', $league->event->competition->date_end ) ); ?>
                                </span>
                            </span>
                        </small>
                            <?php
                        }
                        ?>
                    </div>
                </div>
                <div class="media__aside">
                    <form method="get" action="<?php echo esc_html( get_permalink( $post_id ) ); ?>" id="racketmanager_competition_archive" class="season-select">
                        <input type="hidden" name="page_id" value="<?php echo esc_html( $post_id ); ?>" />
                        <input type="hidden" name="pagename" id="pagename" value="<?php echo esc_html( $pagename ); ?>" />
                        <div class="row g-1 align-items-center">
                            <div class="form-floating">
                                <select class="form-select" size="1" name="season" id="season">
                                    <?php
                                    /** @var array $seasons */
                                    foreach ( array_reverse( $seasons ) as $key => $season ) {
                                        if ( $league->event->is_box ) {
                                            $option_name = $season_title . ' - ';
                                        } else {
                                            $option_name = '';
                                        }
                                        $option_name .= $season['name'];
                                        ?>
                                        <option value="<?php echo esc_attr( $season['name'] ); ?>"
                                            <?php
                                            if ( $season['name'] === $league->current_season['name'] ) {
                                                echo ' selected="selected"';
                                            }
                                            ?>
                                        ><?php echo esc_html( $option_name ); ?></option>
                                        <?php
                                    }
                                    ?>
                                </select>
                                <label for="season"><?php echo esc_html( $season_selection ); ?></label>
                            </div>
                        </div>
                    </form>
                    <?php
                    $favourite_type = 'league';
                    $favourite_id   = $league->id;
                    require 'includes/favourite-button.php';
                    ?>
                </div>
            </div>
        </div>
    </div>
    <?php require 'league-selections.php'; ?>
    <?php
    if ( $league->event->competition->is_championship ) {
        ?>
        <?php racketmanager_championship( 0, array( 'season' => $league->season ) ); ?>
        <?php
    } else {
        ?>
        <div id="pageContentTab">
            <nav class="navbar navbar-expand-lg">
                <div class="">
                    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse mt-3" id="navbarSupportedContent">
                        <!-- Nav tabs -->
                        <ul class="nav nav-pills frontend" id="myTab" role="tablist">
                            <?php
                            foreach ( $menu_options as $option ) {
                                if ( $option['available'] ) {
                                    ?>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link tabData <?php echo $option['selected'] ? 'active' : null; ?>" id="<?php echo esc_attr( $option['name'] ); ?>-tab" data-bs-toggle="pill" data-bs-target="#<?php echo esc_attr( $option['name'] ); ?>" type="button" role="tab" aria-controls="<?php echo esc_attr( $option['name'] ); ?>" aria-selected="<?php echo esc_attr( $option['selected'] ); ?>" data-type="league" data-type-id="<?php echo esc_attr( $league->id ); ?>" data-season="<?php echo esc_attr( $league->current_season['name'] ); ?>" data-name="<?php echo esc_attr( seo_url( $league->title ) ); ?>" data-competition-type="<?php echo esc_attr( $league->event->competition->type ); ?>"><?php echo esc_attr( $option['description'] ); ?></button>
                                    </li>
                                    <?php
                                }
                            }
                            ?>
                        </ul>
                    </div>
                </div>
            </nav>
            <!-- Tab panes -->
            <div class="tab-content" id="leagueTabContent">
                <?php require RACKETMANAGER_PATH . 'templates/includes/loading.php'; ?>
                <?php
                foreach ( $menu_options as $option ) {
                    if ( $option['available'] ) {
                        ?>
                        <div class="tab-pane <?php echo $option['selected'] ? 'active' : 'fade'; ?>" id="<?php echo esc_attr( $option['name'] ); ?>" role="tabpanel" aria-labelledby="<?php echo esc_attr( $option['name'] ); ?>-tab">
                            <?php
                            if ( $option['selected'] ) {
                                $function_name = 'Racketmanager\league_' . $option['name'];
                                if ( function_exists( $function_name ) ) {
                                    $args             = array();
                                    $args['season']   = $league->current_season['name'];
                                    $args['template'] = get_league_template( $option['name'] );
                                    $function_name( $league->id, $args );
                                } else {
                                    /* translators: %s: function name */
                                    printf( esc_html__( 'function %s does not exist', 'racketmanager' ), esc_attr( $function_name ) );
                                }
                            }
                            ?>
                        </div>
                        <?php
                    }
                }
                ?>
            </div>
        </div>
        <?php
    }
    ?>
</div>
