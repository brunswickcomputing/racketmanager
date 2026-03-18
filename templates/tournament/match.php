<?php
/**
 * Template for a tournament match
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

global $racketmanager;

use Racketmanager\Domain\DTO\Fixture\Fixture_Details_DTO;
use Racketmanager\Util\Util;

/** @var Fixture_Details_DTO $fixture_detail */

$fixture       = $fixture_detail->fixture;
$league        = $fixture_detail->league;
$event         = $fixture_detail->event;
$competition   = $fixture_detail->competition;
$home_team_dto = $fixture_detail->home_team;
$away_team_dto = $fixture_detail->away_team;

if ( ! empty( $match_display ) ) {
    $match_display = 'match--list';
} else {
    $match_display = '';
}
if ( empty( $location_in_header ) ) {
    $location_in_header = false;
}
$home_team = $home_team_dto->team ?? null;
$away_team = $away_team_dto->team ?? null;
if ( $home_team && $away_team ) {
    if ( empty( $tournament ) ) {
        if ( $league->is_championship ) {
            $match_ref = $this->final_round;
        } else {
            $match_ref = 'day' . $this->match_day;
        }
        $match_link = '/match/' . seo_url( $league->title ) . '/' . $fixture->get_season() . '/' . $match_ref . '/' . seo_url( $home_team->get_name() ) . '-vs-' . seo_url( $away_team->get_name() ) . '/';
    } else {
        $match_link = '/tournament/' . seo_url( $tournament->name ) . '/match/' . seo_url( $league->title ) . '/' . seo_url( $home_team->get_name() ) . '-vs-' . seo_url( $away_team->get_name() ) . '/' . $fixture->id . '/';
    }
    $is_update_allowed = $fixture_detail->is_update_allowed;
    $user_can_update   = $is_update_allowed?->user_can_update ?? false;
} else {
    $user_can_update = false;
    $match_link      = null;
}
$winner             = null;
$loser              = null;
$is_tie             = null;
$player_team        = null;
$player_team_status = null;
if ( ! empty( $tournament_player ) ) {
    if ( $home_team && $home_team->has_player( (int) $tournament_player->id ) ) {
        $player_team = 'home';
    } elseif ( $away_team && $away_team->has_player( (int) $tournament_player->id ) ) {
        $player_team = 'away';
    }
}
$match_selected = false;
if ( is_user_logged_in() ) {
    $current_user_id = get_current_user_id();
    if ( ( $home_team && $home_team->has_player( $current_user_id ) ) || ( $away_team && $away_team->has_player( $current_user_id ) ) ) {
        $match_selected = true;
    }
}
if ( ! empty( $fixture->winner_id ) ) {
    $match_complete = true;
    if ( $home_team && $fixture->winner_id === $home_team->get_id() ) {
        $winner = 'home';
        $loser  = 'away';
    } elseif ( $away_team && $fixture->winner_id === $away_team->get_id() ) {
        $winner = 'away';
        $loser  = 'home';
    } elseif ( - 1 === (int) $fixture->winner_id ) {
        $is_tie = true;
    }
    if ( $winner === $player_team ) {
        $player_team_status = 'winner';
    } elseif ( $loser === $player_team ) {
        $player_team_status = 'loser';
    }
}
?>
<div class="match tournament-match <?php echo esc_html( $match_display ); ?> <?php echo empty( $match_selected ) ? '' : 'is-selected'; ?>">
    <div class="match__header">
        <ul class="match__header-title">
            <li class="match__header-title-item">
                <?php echo esc_html( Util::get_final_name( $fixture->final ) ); ?>
            </li>
            <?php
            if ( ! empty( $tournament ) ) {
                ?>
                <li class="match__header-title-item">
                    <a href="<?php echo esc_html( $tournament->link ) . 'draw/' . esc_html( seo_url( $league->event->name ) ) . '/'; ?>">
                        <?php echo esc_html( $league->title ); ?>
                    </a>
                </li>
                <?php
            } elseif ( empty( $match_complete ) && ! empty( $fixture->date ) ) {
                ?>
                <li class="match__header-title-item">
                    <?php
                    if ( empty( $fixture->start_time ) ) {
                        echo esc_html_e( 'Play by', 'racketmanager' ) . ' ';
                    }
                    ?>
                    <?php echo esc_html( mysql2date( $racketmanager->date_format, $fixture->date ) ); ?>
                    <?php
                    if ( ! empty( $fixture->start_time ) ) {
                        echo ' ' . esc_html__( 'at', 'racketmanager' );
                        the_match_time( $fixture->start_time );
                    }
                    ?>
                </li>
                <?php
            }
            if ( $location_in_header && ! empty( $fixture->location ) ) {
                ?>
                <li class="match__header-title-item match__location">
                    <?php echo esc_html( $fixture->location ); ?>
                </li>
                <?php
            }
            ?>
        </ul>
    </div>
    <div class="match__body">
        <div class="match__row-wrapper">
            <?php
            $opponents = array( 'home', 'away' );
            foreach ( $opponents as $opponent ) {
                if ( $winner === $opponent ) {
                    $is_winner    = true;
                    $winner_class = ' winner';
                } else {
                    $is_winner    = false;
                    $winner_class = '';
                }
                if ( $loser === $opponent ) {
                    $is_loser = true;
                } else {
                    $is_loser = false;
                }
                ?>
                <div class="match__row <?php echo esc_html( $winner_class ); ?>">
                    <div class="match__row-title">
                        <?php
                        $team_dto = ${$opponent . '_team_dto'};
                        $team     = $team_dto?->team;
                        if ( ! $team || empty( $team->players ) ) {
                            ?>
                            <div class="match__row-title-value">
                                <?php
                                if ( 'final' === $fixture->final ) {
                                    $prev_match = 'prev_' . $opponent . '_match';
                                    if ( ! empty( $fixture->$prev_match->match_title ) ) {
                                        $match_title = $fixture->$prev_match->match_title;
                                    } else {
                                        $match_title = $team?->get_name() ?? '';
                                    }
                                } else {
                                    $match_title = $team?->get_name() ?? '';
                                }
                                echo esc_html( $match_title );
                                ?>
                            </div>
                            <?php
                        } else {
                            foreach ( $team->players as $team_player ) {
                                ?>
                                <div class="match__row-title-value">
                                    <?php
                                    if ( ! empty( $tournament ) ) {
                                    $player_link = '/tournament/' . seo_url( $tournament->name ) . '/player/' . seo_url( trim( $team_player->get_fullname() ) ) . '/';
                                    ?>
                                    <a href="<?php echo esc_attr( $player_link ); ?>" class="tabDataLink" data-type="tournament" data-type-id="<?php echo esc_attr( $tournament->id ); ?>" data-season=""
                                       data-link="<?php echo esc_attr( $player_link ); ?>" data-link-id="<?php echo esc_attr( $team_player->get_id() ); ?>" data-link-type="players">
                                        <?php
                                        }
                                        ?>
                                        <?php echo esc_html( trim( $team_player->get_fullname() ) ); ?>
                                        <?php
                                        if ( ! empty( $tournament ) ) {
                                        ?>
                                    </a>
                                <?php
                                }
                                ?>
                                </div>
                                <?php
                            }
                        }
                        ?>
                    </div>
                    <?php
                    if ( $is_winner ) {
                        if ( empty( $player_team_status ) || 'winner' === $player_team_status ) {
                            ?>
                            <span class="match__status winner">W</span>
                            <?php
                        }
                    } elseif ( $is_loser ) {
                        if ( $fixture->is_walkover ) {
                            ?>
                            <span class="match__message match-warning"><?php esc_html_e( 'Walkover', 'racketmanager' ); ?></span>
                            <?php
                        } elseif ( $fixture->is_retired ) {
                            ?>
                            <span class="match__message match-warning"><?php esc_html_e( 'Retired', 'racketmanager' ); ?></span>
                            <?php
                        }
                        if ( 'loser' === $player_team_status ) {
                            ?>
                            <span class="match__status loser">L</span>
                            <?php
                        }
                    } elseif ( $is_tie ) {
                        ?>
                        <span class="match__message match-warning"><?php esc_html_e( 'Not played', 'racketmanager' ); ?></span>
                        <?php
                    }
                    ?>
                </div>
                <?php
            }
            ?>
        </div>
        <div class="match__result">
            <?php
            $sets = ! empty( $fixture->custom['sets'] ) ? $fixture->custom['sets'] : array();
            foreach ( $sets as $set ) {
                $p1 = '';
                $p2 = '';
                $tb = '';
                $winner = null;
                if ( $set instanceof \Racketmanager\Domain\Scoring\Set_Score ) {
                    $p1 = $set->get_home_games();
                    $p2 = $set->get_away_games();
                    $tb = $set->get_home_tiebreak() ?? $set->get_away_tiebreak() ?? '';
                    $winner = $set->winner() === 'home' ? 'player1' : ($set->winner() === 'away' ? 'player2' : null);
                } elseif ( is_array( $set ) ) {
                    $p1 = $set['player1'] ?? '';
                    $p2 = $set['player2'] ?? '';
                    $tb = $set['tiebreak'] ?? '';
                    $winner = $set['winner'] ?? null;
                }

                if ( '' !== $p1 && '' !== $p2 ) {
                    ?>
                    <ul class="match-points">
                        <?php
                        $opponents = array( 'player1', 'player2' );
                        foreach ( $opponents as $opponent_ref ) {
                            if ( $winner === $opponent_ref ) {
                                $winner_class = ' winner';
                            } else {
                                $winner_class = '';
                            }
                            $val = 'player1' === $opponent_ref ? $p1 : $p2;
                            ?>
                            <li class="match-points__cell <?php echo esc_html( $winner_class ); ?>">
                                <?php
                                echo esc_html( $val );
                                if ( ! empty( $tb ) && ! empty( $winner_class ) ) {
                                    ?>
                                    <span class="player-row__tie-break"><?php echo esc_html( $tb ); ?></span>
                                    <?php
                                }
                                ?>
                            </li>
                            <?php
                        }
                        ?>
                    </ul>
                    <?php
                }
            }
            ?>
        </div>
        <?php
        if ( $user_can_update && empty( $fixture->confirmed ) ) {
            ?>
            <div class="match__button">
                <a href="<?php echo esc_url( $match_link ); ?>" class="btn match__btn">
                    <svg width="16" height="16" class="icon ">
                        <use xlink:href="<?php echo esc_url( RACKETMANAGER_URL . 'assets/icons/bootstrap-icons.svg#pencil' ); ?>"></use>
                    </svg>
                </a>
            </div>
            <?php
        }
        ?>
    </div>
    <div class="match__footer">
        <ul class="match__footer-list">
            <li class="match__footer-list-item">
                <?php
                if ( empty( $fixture->location ) ) {
                    if ( isset( $fixture->host ) ) {
                        if ( 'home' === $fixture->host ) {
                            if ( isset( $home_team_dto->club->shortcode ) ) {
                                echo esc_html( $home_team_dto->club->shortcode );
                            }
                        } elseif ( 'away' === $fixture->host ) {
                            if ( isset( $away_team_dto->club->shortcode ) ) {
                                echo esc_html( $away_team_dto->club->shortcode );
                            }
                        }
                    }
                } else {
                    echo esc_html( $fixture->location );
                }
                ?>
            </li>
            <?php
            if ( empty( $match_complete ) && ! empty( $tournament ) && empty( $match_display ) ) {
                ?>
                <li class="match__header-title-item">
                    <?php
                    if ( empty( $fixture->start_time ) ) {
                        echo esc_html_e( 'Play by', 'racketmanager' ) . ' ';
                    }
                    ?>
                    <?php echo esc_html( mysql2date( $racketmanager->date_format, $fixture->date ) ); ?>
                    <?php
                    if ( ! empty( $fixture->start_time ) ) {
                        echo ' ' . esc_html__( 'at', 'racketmanager' );
                        the_match_time( $fixture->start_time );
                    }
                    ?>
                </li>
                <?php
            }
            ?>
        </ul>
    </div>
</div>
