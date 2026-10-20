<?php
/**
 * Template page for the match table
 *
 * @package Racketmanager/Templates
 * The following variables are usable:
 *  $league: contains data of current league
 *  $matches: contains all matches for current league
 *  $teams: contains teams of current league in an associative array
 *  $season: current season
 *
 * You can check the content of a variable when you insert the tag <?php var_dump($variable) ?>
 */

namespace Racketmanager;

/** @var object $league */
/** @var array<int|string, array{player_link: string, team_link: string}>|null $player_links */

$player_links = $player_links ?? [];

if ( ! empty( $league->player ) ) {
    $player = $league->player;
    require RACKETMANAGER_PATH . 'templates/includes/player-header.php';
    ?>
    <div class="page_content row">
        <div class="page-content__main col-12 col-lg-6">
            <div class="module module--card">
                <div class="module__banner">
                    <h3 class="module__title"><?php esc_html_e( 'Matches', 'racketmanager' ); ?></h3>
                </div>
                <div class="module__content">
                    <div class="module-container">
                        <div class="module">
                            <?php
                            foreach ( $league->player->matches as $match ) {
                                echo match_detail( $match->id, $league->player->id );
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="page-content__sidebar col-12 col-lg-6">
            <?php
            if ( ! empty( $league->player->statistics ) ) {
                $player_statistics = $league->player->statistics;
                require RACKETMANAGER_PATH . 'templates/includes/player-statistics.php';
            }
            ?>
        </div>
    </div>
    <?php
} else {
    ?>
    <div class="module module--card">
        <div class="module__banner">
            <h3 class="module__title"><?php esc_html_e( 'Players', 'racketmanager' ); ?></h3>
        </div>
        <div class="module__content">
            <div class="module-container">
                <?php
                if ( ! empty( $league->players ) ) {
                    ?>
                    <ol class="list list--bordered list--count">
                        <?php
                        foreach ( $league->players as $player ) {
                            $player_id       = (int) ( $player->id ?? ( $player->ID ?? ( method_exists( $player, 'get_id' ) ? $player->get_id() : 0 ) ) );
                            $selected_player = $player_id === (int) get_current_user_id();
                            $links           = $player_links[ $player_id ] ?? [];
                            $player_link     = $links['player_link'] ?? '';
                            $team_link       = $links['team_link'] ?? '';
                            $player_fullname = method_exists( $player, 'get_fullname' ) ? $player->get_fullname() : ( $player->display_name ?? ( trim( ( $player->firstname ?? '' ) . ' ' . ( $player->surname ?? '' ) ) ) );
                            $team_title      = $player->team->title ?? ( $player->team->name ?? '' );
                            ?>
                            <li class="list__item <?php echo empty( $selected_player ) ? null : 'is-selected'; ?>">
                                <div class="media">
                                    <div class="media__wrapper">
                                        <div class="media__img">
                                            <div class="profile-icon">
                                                <span class="profile-icon__abbr">
                                                    <?php
                                                    $player_initials = substr( (string) ( $player->firstname ?? '' ), 0, 1 ) . substr( (string) ( $player->surname ?? '' ), 0, 1 );
                                                    echo esc_html( $player_initials );
                                                    ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="media__content">
                                            <div class="flex-container">
                                                <div class="flex-item flex-item--grow">
                                                    <p class="media__title">
                                                        <?php if ( ! empty( $player_link ) ) : ?>
                                                            <a href="<?php echo esc_attr( $player_link ); ?>" class="nav--link tabDataLink" data-type="league" data-type-id="<?php echo esc_attr( (string) ( $league->id ?? '' ) ); ?>" data-season="<?php echo esc_attr( (string) ( $league->current_season['name'] ?? '' ) ); ?>" data-link="<?php echo esc_attr( $player_link ); ?>" data-link-id="<?php echo esc_attr( (string) $player_id ); ?>" data-link-type="players" >
                                                                <span class="nav-link__value">
                                                                    <?php echo esc_html( $player_fullname ); ?>
                                                                </span>
                                                            </a>
                                                        <?php else : ?>
                                                            <span class="nav-link__value">
                                                                <?php echo esc_html( $player_fullname ); ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </p>
                                                    <p class="media__subheading">
                                                        <?php if ( ! empty( $team_link ) ) : ?>
                                                            <a href="<?php echo esc_attr( $team_link ); ?>" class="nav--link tabDataLink" data-type="league" data-type-id="<?php echo esc_attr( (string) ( $league->id ?? '' ) ); ?>" data-season="<?php echo esc_attr( (string) ( $league->current_season['name'] ?? '' ) ); ?>" data-link="<?php echo esc_attr( $team_link ); ?>" data-link-id="<?php echo esc_attr( (string) ( $player->team->id ?? '' ) ); ?>" data-link-type="teams">
                                                                <span class="nav-link__value">
                                                                    <?php echo esc_html( $team_title ); ?>
                                                                </span>
                                                            </a>
                                                        <?php else : ?>
                                                            <span class="nav-link__value">
                                                                <?php echo esc_html( $team_title ); ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </p>
                                                </div>
                                                <div class="progress-bar-container">
                                                    <?php
                                                    if ( ! empty( $player->played ) ) {
                                                        ?>
                                                        <div class="clearfix">
                                                            <span class="pull-left"><?php esc_html_e( 'Win-Loss', 'racketmanager' ); ?></span>
                                                            <span class="pull-right"><?php echo esc_html( (string) ( $player->matches_won ?? 0 ) ) . '-' . esc_html( (string) ( $player->matches_lost ?? 0 ) ) . ' (' . esc_html( (string) ( $player->played ?? 0 ) ) . ')'; ?></span>
                                                        </div>
                                                        <div class="progress">
                                                            <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo esc_html( (string) ( $player->win_pct ?? 0 ) ); ?>%" aria-valuenow="<?php echo esc_html( (string) ( $player->win_pct ?? 0 ) ); ?>" aria-valuemin="0" aria-valuemax="100" data-bs-toggle="tooltip" data-bs-placement="bottom" title="<?php echo esc_html( (string) ( $player->win_pct ?? 0 ) ) . ' ' . esc_html__( 'won', 'racketmanager' ); ?>%"></div>
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
                    <?php
                } else {
                    esc_html_e( 'No players found', 'racketmanager' );
                }
                ?>
            </div>
        </div>
    </div>
    <?php
}
?>
