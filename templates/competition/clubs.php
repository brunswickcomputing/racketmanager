<?php
/**
 * Template for competition clubs
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var object $competition */
if ( empty( $competition_club ) ) {
    ?>
    <div class="module module--card">
        <div class="module__banner">
            <h3 class="module__title"><?php esc_html_e( 'Clubs', 'racketmanager' ); ?></h3>
        </div>
        <div class="module__content">
            <div class="module-container">
                <?php
                if ( ! empty( $competition->clubs ) ) {
                    ?>
                    <div class="col-12 col-md-12 col-lg-6">
                        <div class="row mb-2 row-header">
                            <div class="col-6">
                                <?php esc_html_e( 'Club', 'racketmanager' ); ?>
                            </div>
                            <div class="col-3 text-end">
                                <?php esc_html_e( 'Teams', 'racketmanager' ); ?>
                            </div>
                            <div class="col-3 text-end">
                                <?php esc_html_e( 'Players', 'racketmanager' ); ?>
                            </div>
                        </div>
                        <?php
                        foreach ( $competition->clubs as $club ) {
                            $url_link = '/' . seo_url( $competition->name ) . '/' . $competition->current_season['name'] . '/club/' . seo_url( $club->shortcode ) . '/';
                            ?>
                            <div class="row mb-2 row-list">
                                <div class="col-6" name="<?php esc_html_e( 'Club', 'racketmanager' ); ?>">
                                    <a href="<?php echo esc_attr( $url_link ); ?>" class="tabDataLink" data-type="competition" data-type-id="<?php echo esc_attr( $competition->id ); ?>" data-season="<?php echo esc_attr( $competition->current_season['name'] ); ?>" data-link="<?php echo esc_attr( $url_link ); ?>" data-link-id="<?php echo esc_attr( $club->id ); ?>" data-link-type="clubs">
                                        <?php echo esc_html( $club->name ); ?>
                                    </a>
                                </div>
                                <div class="col-3 text-end">
                                    <?php echo esc_html( $club->team_count ); ?>
                                </div>
                                <div class="col-3 text-end">
                                    <?php echo esc_html( $club->player_count ); ?>
                                </div>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                    <?php
                } else {
                    esc_html_e( 'No clubs found', 'racketmanager' );
                }
                ?>
            </div>
        </div>
    </div>
    <?php
} else {
    ?>
    <div class="page-subhead">
        <div class="media">
            <div class="media__wrapper">
                <div class="media__img">
                    <span class="profile-icon">
                        <span class="profile-icon__abbr">
                            <?php
                            $words    = explode( ' ', $competition_club->shortcode );
                            $initials = null;
                            foreach ( $words as $w ) {
                                $initials .= $w[0];
                            }
                            echo esc_html( $initials );
                            ?>
                        </span>
                    </span>
                </div>
                <div class="media__content">
                    <h3 class="media__title">
                        <a href="/clubs/<?php echo esc_attr( seo_url( $competition_club->shortcode ) ); ?>/">
                            <?php echo esc_html( $competition_club->name ); ?>
                        </a>
                    </h3>
                    <?php
                    if ( ! empty( $competition_club->address ) ) {
                        ?>
                        <span class="media__subheading">
                            <span><?php echo esc_html( $competition_club->address ); ?></span>
                        </span>
                        <?php
                    }
                    ?>
                </div>
                <div class="media__aside">
                </div>
            </div>
        </div>
    </div>
    <div class="page_content row">
        <div class="page-content__main col-12 col-lg-6">
            <?php
            if ( $competition_club->matches ) {
                ?>
                <div class="module module--card">
                    <div class="module__banner">
                        <h3 class="module__title"><?php esc_html_e( 'Upcoming matches', 'racketmanager' ); ?></h3>
                    </div>
                    <div class="module__content">
                        <div class="module-container">
                            <div class="module">
                                <?php
                                $current_club = $competition_club->id;
                                $matches_list = $competition_club->matches;
                                require RACKETMANAGER_PATH . 'templates/includes/matches-team-list-group.php';
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
            }
            ?>
            <div class="module module--card">
                <div class="module__banner">
                    <h3 class="module__title"><?php esc_html_e( 'Results', 'racketmanager' ); ?></h3>
                </div>
                <div class="module__content">
                    <div class="module-container">
                        <div class="module">
                            <?php
                            $current_club = $competition_club->id;
                            $matches_list = $competition_club->results;
                            require RACKETMANAGER_PATH . 'templates/includes/matches-team-list-group.php';
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="page-content__sidebar col-12 col-lg-6">
            <div class="module module--card">
                <div class="module__banner">
                    <h3 class="module__title"><?php esc_html_e( 'Teams', 'racketmanager' ); ?></h3>
                </div>
                <div class="module__content">
                    <div class="module-container">
                        <div class="col-12">
                            <div class="row mb-2 row-header">
                                <div class="col-6">
                                    <?php esc_html_e( 'Team', 'racketmanager' ); ?>
                                </div>
                                <?php
                                if ( $competition->is_championship ) {
                                    ?>
                                    <div class="col-3">
                                        <?php esc_html_e( 'Draw', 'racketmanager' ); ?>
                                    </div>
                                    <?php
                                } else {
                                    ?>
                                    <div class="col-3">
                                        <?php esc_html_e( 'League', 'racketmanager' ); ?>
                                    </div>
                                    <?php
                                }
                                ?>
                                <?php
                                if ( 'league' === $competition->type ) {
                                    ?>
                                    <div class="col-3 text-end">
                                        <?php esc_html_e( 'Standing', 'racketmanager' ); ?>
                                    </div>
                                    <?php
                                }
                                ?>
                            </div>
                            <?php
                            foreach ( $competition_club->teams as $team ) {
                                ?>
                                <div class="row mb-2 row-list">
                                    <div class="col-6">
                                        <?php
                                        if ( $competition->is_championship ) {
                                            ?>
                                            <a href="/<?php echo esc_attr( $competition->type ); ?>/<?php echo esc_html( seo_url( $team->event_name ) ); ?>/<?php echo esc_attr( $competition->current_season['name'] ); ?>/team/<?php echo esc_attr( seo_url( $team->name ) ); ?>/">
                                                <?php echo esc_html( $team->name ); ?>
                                            </a>
                                            <?php
                                        } else {
                                            ?>
                                            <a href="/<?php echo esc_attr( $competition->type ); ?>/<?php echo esc_html( seo_url( $team->league_title ) ); ?>/<?php echo esc_attr( $competition->current_season['name'] ); ?>/team/<?php echo esc_attr( seo_url( $team->name ) ); ?>/">
                                                <?php echo esc_html( $team->name ); ?>
                                            </a>
                                            <?php
                                        }
                                        ?>
                                    </div>
                                    <div class="col-5">
                                        <?php
                                        if ( $competition->is_championship ) {
                                            ?>
                                            <a href="/<?php echo esc_attr( $competition->type ); ?>s/<?php echo esc_html( seo_url( $team->event_name ) ); ?>/<?php echo esc_attr( $competition->current_season['name'] ); ?>/">
                                                <?php echo esc_html( $team->event_name ); ?>
                                            </a>
                                            <?php
                                        } else {
                                            ?>
                                            <a href="/<?php echo esc_attr( $competition->type ); ?>/<?php echo esc_html( seo_url( $team->league_title ) ); ?>/<?php echo esc_attr( $competition->current_season['name'] ); ?>/">
                                                <?php echo esc_html( $team->league_title ); ?>
                                            </a>
                                            <?php
                                        }
                                        ?>
                                    </div>
                                    <?php
                                    if ( 'league' === $competition->type ) {
                                        ?>
                                        <div class="col-1 text-end">
                                            <?php echo esc_html( $team->rank ); ?>
                                        </div>
                                        <?php
                                    }
                                    ?>
                                </div>
                                <?php
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="module module--card">
                <div class="module__banner">
                    <h3 class="module__title"><?php esc_html_e( 'Players', 'racketmanager' ); ?></h3>
                </div>
                <div class="module__content">
                    <div class="module-container">
                        <ol class="list list--bordered list--count">
                            <?php
                            foreach ( $competition_club->players as $player ) {
                                $selected_player = false;
                                if ( intval( $player->id ) === get_current_user_id() ) {
                                    $selected_player = true;
                                }
                                $player_link = '/' . seo_url( $competition->name ) . '/' . $competition->current_season['name'] . '/player/' . seo_url( $player->fullname ) . '/';
                                ?>
                                <li class="list__item <?php echo empty( $selected_player ) ? null : 'is-selected'; ?>">
                                    <div class="media">
                                        <div class="media__wrapper">
                                            <div class="media__img">
                                                <div class="profile-icon">
                                                    <span class="profile-icon__abbr">
                                                        <?php
                                                        $player_initials = substr( $player->firstname, 0, 1 ) . substr( $player->surname, 0, 1 );
                                                        echo esc_html( $player_initials );
                                                        ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="media__content">
                                                <div class="flex-container">
                                                    <div class="flex-item flex-item--grow">
                                                        <p class="media__title">
                                                            <a href="<?php echo esc_attr( $player_link ); ?>" class="nav--link tabDataLink" data-type="competition" data-type-id="<?php echo esc_attr( $competition->id ); ?>" data-season="<?php echo esc_attr( $competition->current_season['name'] ); ?>" data-link="<?php echo esc_attr( $player_link ); ?>" data-link-id="<?php echo esc_attr( $player->id ); ?>" data-link-type="players">
                                                                <span class="nav-link__value">
                                                                    <?php echo esc_html( $player->fullname ); ?>
                                                                </span>
                                                            </a>
                                                        </p>
                                                    </div>
                                                    <div class="progress-bar-container">
                                                        <?php
                                                        if ( $player->played ) {
                                                            ?>
                                                            <div class="clearfix">
                                                                <span class="pull-left"><?php esc_html_e( 'Win-Loss', 'racketmanager' ); ?></span>
                                                                <span class="pull-right"><?php echo esc_html( $player->matches_won ) . '-' . esc_html( $player->matches_lost ) . ' (' . esc_html( $player->played ) . ')'; ?></span>
                                                            </div>
                                                            <div class="progress">
                                                                <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo esc_html( $player->win_pct ); ?>%" aria-valuenow="<?php echo esc_html( $player->win_pct ); ?>" aria-valuemin="0" aria-valuemax="100" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo esc_html( $player->win_pct ) . ' ' . esc_html__( 'won', 'racketmanager' ); ?>%"></div>
                                                            </div>
                                                            <?php
                                                        }
                                                        ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                                <?php
                            }
                            ?>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}
?>
