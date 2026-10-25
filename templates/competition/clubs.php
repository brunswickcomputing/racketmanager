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
                if ( ! empty( $clubs ) ) {
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
                        foreach ( $clubs as $club ) {
                            $club_id  = is_object( $club ) ? $club->id ?? 0 : (int) ( $club['id'] ?? 0 );
                            $url_link = $club_links[ $club_id ] ?? '';
                            ?>
                            <div class="row mb-2 row-list">
                                <div class="col-6" name="<?php esc_html_e( 'Club', 'racketmanager' ); ?>">
                                    <?php if ( '' !== $url_link ) : ?>
                                        <a href="<?php echo esc_attr( $url_link ); ?>" class="tabDataLink" data-type="competition" data-type-id="<?php echo esc_attr( (string) ( $competition->id ?? '' ) ); ?>" data-season="<?php echo esc_attr( (string) ( $competition->current_season['name'] ?? '' ) ); ?>" data-link="<?php echo esc_attr( $url_link ); ?>" data-link-id="<?php echo esc_attr( (string) $club_id ); ?>" data-link-type="clubs">
                                            <?php echo esc_html( $club->shortcode ); ?>
                                        </a>
                                    <?php else : ?>
                                        <?php echo esc_html( $club->shortcode ); ?>
                                    <?php endif; ?>
                                </div>
                                <div class="col-3 text-end">
                                    <?php echo esc_html( $club->num_teams ); ?>
                                </div>
                                <div class="col-3 text-end">
                                    <?php echo isset( $club->num_players ) ? esc_html( $club->num_players ) : null; ?>
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
                            $words    = array_filter( explode( ' ', $competition_club->shortcode ?? '' ) );
                            $initials = '';
                            foreach ( $words as $w ) {
                                if ( '' !== $w ) {
                                    $initials .= $w[0];
                                }
                            }
                            echo esc_html( $initials );
                            ?>
                        </span>
                    </span>
                </div>
                <div class="media__content">
                    <h3 class="media__title">
                        <?php if ( ! empty( $club_url ) ) : ?>
                            <a href="<?php echo esc_attr( $club_url ); ?>">
                                <?php echo esc_html( $competition_club->name ); ?>
                            </a>
                        <?php else : ?>
                            <?php echo esc_html( $competition_club->name ); ?>
                        <?php endif; ?>
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
                                if ( 'league' === $competition->get_type() ) {
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
                                $team_id              = is_object( $team ) ? $team->id ?? 0 : (int) ( $team['id'] ?? 0 );
                                $team_link            = $team_links[ $team_id ]['team_link'] ?? '';
                                $league_or_event_link = $team_links[ $team_id ]['league_link'] ?? '';
                                ?>
                                <div class="row mb-2 row-list">
                                    <div class="col-6">
                                        <?php if ( '' !== $team_link ) : ?>
                                            <a href="<?php echo esc_attr( $team_link ); ?>">
                                                <?php echo esc_html( $team->name ); ?>
                                            </a>
                                        <?php else : ?>
                                            <?php echo esc_html( $team->name ); ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-5">
                                        <?php if ( '' !== $league_or_event_link ) : ?>
                                            <a href="<?php echo esc_attr( $league_or_event_link ); ?>">
                                                <?php echo esc_html( ! empty( $competition->is_championship ) ? $team->event_name : $team->league_title ); ?>
                                            </a>
                                        <?php else : ?>
                                            <?php echo esc_html( ! empty( $competition->is_championship ) ? $team->event_name : $team->league_title ); ?>
                                        <?php endif; ?>
                                    </div>
                                    <?php
                                    if ( 'league' === $competition->get_type() ) {
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
                                $player_id       = is_object( $player ) ? $player->id ?? ( $player->ID ?? 0 ) : (int) ( $player['id'] ?? 0 );
                                if ( $player_id === get_current_user_id() ) {
                                    $selected_player = true;
                                }
                                $player_link = $player_links[ $player_id ] ?? '';
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
                                                            <?php if ( '' !== $player_link ) : ?>
                                                                <a href="<?php echo esc_attr( $player_link ); ?>" class="nav--link tabDataLink" data-type="competition" data-type-id="<?php echo esc_attr( (string) ( $competition->id ?? '' ) ); ?>" data-season="<?php echo esc_attr( (string) ( $competition->current_season['name'] ?? '' ) ); ?>" data-link="<?php echo esc_attr( $player_link ); ?>" data-link-id="<?php echo esc_attr( (string) $player_id ); ?>" data-link-type="players">
                                                                    <span class="nav-link__value">
                                                                        <?php echo esc_html( $player->get_fullname() ); ?>
                                                                    </span>
                                                                </a>
                                                            <?php else : ?>
                                                                <span class="nav-link__value">
                                                                    <?php echo esc_html( $player->get_fullname() ); ?>
                                                                </span>
                                                            <?php endif; ?>
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
