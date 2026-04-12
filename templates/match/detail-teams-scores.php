<?php
/**
 * Form to allow input of match scores for rubbers
 *
 * @package Racketmanager/Templates;
 */

namespace Racketmanager;

use Racketmanager\Domain\DTO\Fixture\Fixture_Details_DTO;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Util\Util;

/** @var Fixture_Details_DTO $dto */
/** @var Fixture $match */
/** @var string $match_type */
global $racketmanager;
$opponents        = array( 'home', 'away' );
$opponents_points = array( 'player1', 'player2' );
if ( ! empty( $match_player ) ) {
    $match->player = $match_player;
}
$team        = null;
$team_status = null;
$winner      = null;
if ( ! empty( $competition_team ) ) {
    if ( $competition_team === $match->get_home_team() ) {
        $team = 'home';
    } elseif ( $competition_team === $match->get_away_team() ) {
        $team = 'away';
    }
}
if ( ! empty( $match->get_winner_id() ) ) {
    if ( (string) $match->get_winner_id() === $match->get_home_team() ) {
        $winner = 'home';
    } elseif ( (string) $match->get_winner_id() === $match->get_away_team() ) {
        $winner = 'away';
    }
    if ( isset( $team_statistics ) ) {
        ++ $team_statistics['played'][ $team_statistics ][ $match_type ];
        ++ $team_statistics['played'][ $team_statistics ]['t'];
    }
}
?>
<div id="matchRubbers">
    <ul class="match-group">
        <?php
        if ( ! empty( $match->player ) ) {
            $match_link = $dto->link;
            ?>
            <div class="match match--team-match">
                <a class="team-match__wrapper" href="<?php echo esc_attr( $match_link ); ?>">
                    <div class="match__header">
                        <span class="match__header-title">
                            <?php
                            if ( ! empty( $match->get_final() ) ) {
                                ?>
                                <span><?php echo esc_html( Util::get_final_name( $match->get_final() ) ); ?>&nbsp;&#8226;&nbsp;</span>
                                <?php
                            } elseif ( ! empty( $match->get_match_day() ) ) {
                                ?>
                                <span><?php echo esc_html__( 'Match Day', 'racketmanager' ) . ' ' . esc_html( $match->get_match_day() ); ?>&nbsp;&#8226;&nbsp;</span>
                                <?php
                            }
                            ?>
                            <span>
                                <time datetime="<?php echo esc_attr( $match->get_date() ); ?>"><?php echo esc_html( mysql2date( $racketmanager->date_format, $match->get_date() ) ); ?></time>
                            </span>
                        </span>
                    </div>
                    <div class="match__body">
                        <div class="team-match">
                            <div class="team-match__name <?php echo esc_attr( 'home' === $winner ? 'winner' : '' ); ?> is-team-1">
                                <span class="nav--link">
                                    <span class="nav-link__value">
                                        <?php echo esc_html( $dto->home_team ? $dto->home_team->team->get_name() : ( $dto->prev_home_match_title ?? '' ) ); ?>
                                    </span>
                                </span>
                            </div>
                            <div class="score">
                                <span class="is-team-1"><?php echo esc_html( sprintf( '%g', $match->get_home_points() ) ); ?></span>
                                <span class="score-separator">-</span>
                                <span class="is-team-2"><?php echo esc_html( sprintf( '%g', $match->get_away_points() ) ); ?></span>
                            </div>
                            <div class="team-match__name <?php echo esc_attr( 'away' === $winner ? 'winner' : '' ); ?> is-team-2">
                                <span class="nav--link">
                                    <span class="nav-link__value">
                                        <?php echo esc_html( $dto->away_team ? $dto->away_team->team->get_name() : ( $dto->prev_away_match_title ?? '' ) ); ?>
                                    </span>
                                </span>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <?php
        }
        ?>
        <?php
        foreach ( $match->rubbers as $rubber ) {
            $player_team        = null;
            $player_team_status = null;
            $winner             = null;
            $loser              = null;
            $is_tie             = false;
            if ( ! empty( $rubber->winner_id ) ) {
                if ( $rubber->winner_id === $match->get_home_team() ) {
                    $winner = 'home';
                    $loser  = 'away';
                } elseif ( $rubber->winner_id === $match->get_away_team() ) {
                    $winner = 'away';
                    $loser  = 'home';
                } elseif ( '-1' == $rubber->winner_id ) {
                    $is_tie = true;
                }
                if ( $winner === $team ) {
                    $team_status = 'winner';
                } elseif ( $loser === $team ) {
                    $team_status = 'loser';
                } else {
                    $team_status = null;
                }
            }
            $rubber_title = $rubber->type . $rubber->rubber_number;
            if ( 'D' === substr( $rubber->type, 1, 1 ) ) {
                $rubber_players = array(
                    '1' => array(),
                    '2' => array(),
                );
            } else {
                $rubber_players = array( '1' => array() );
            }
            if ( str_starts_with( $rubber->type, 'M' ) || str_starts_with( $rubber->type, 'B' ) ) {
                foreach ( $rubber_players as $p => $rubber_player ) {
                    $rubber_players[ $p ]['gender'] = 'm';
                }
            } elseif ( str_starts_with( $rubber->type, 'W' ) || str_starts_with( $rubber->type, 'G' ) ) {
                foreach ( $rubber_players as $p => $rubber_player ) {
                    $rubber_players[ $p ]['gender'] = 'f';
                }
            } elseif ( str_starts_with( $rubber->type, 'X' ) ) {
                $rubber_players['1']['gender'] = 'm';
                $rubber_players['2']['gender'] = 'f';
            }
            if ( ! empty( $match->player ) ) {
                foreach ( $opponents as $opponent ) {
                    foreach ( $rubber_players as $p => $rubber_player ) {
                        if ( $rubber->players[ $opponent ][ $p ]->display_name === $match->player->display_name ) {
                            $player_team = $opponent;
                            break 2;
                        }
                    }
                }
                if ( $winner === $player_team ) {
                    $player_team_status = 'winner';
                } elseif ( $loser === $player_team ) {
                    $player_team_status = 'loser';
                }
            }
            ?>
            <li class="match-group__item">
                <div class="match"
                     id="rubber-<?php echo esc_attr( $rubber->id ); ?>">
                    <div class="match__header">
                        <ul class="match__header-title">
                            <li class="match__header-title-item">
                                <span title="<?php echo esc_attr( $rubber_title ); ?>" class="nav--link">
                                    <span class="nav-link__value"><?php echo esc_html( $rubber_title ); ?></span>
                                </span>
                            </li>
                        </ul>
                    </div>
                    <div class="match__body">
                        <div class="match__row-wrapper">
                            <?php
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
                                $team = 'home' === $opponent ? $dto->home_team : $dto->away_team;
                                ?>
                                <div class="match__row">
                                    <div class="match__row-title">
                                        <div class="match__row-title-header">
                                            <?php
                                            if ( $team && $team->team->get_instance( $team->team->get_id() )->is_withdrawn ) {
                                                // Skipping complex withdrawn logic for now as noted before.
                                            }
                                            ?>
                                            <?php echo esc_html( $team ? $team->team->get_name() : ( 'home' === $opponent ? $dto->prev_home_match_title : $dto->prev_away_match_title ) ); ?>
                                        </div>
                                        <?php
                                        foreach ( $rubber_players as $player_number => $rubber_player ) {
                                            ?>
                                            <div class="match__row-title-value">
                                                <span class="match__row-title-value-content">
                                                    <span class="nav-link__value <?php echo esc_html( $winner_class ); ?>">
                                                        <?php
                                                        if ( ! empty( $rubber->players[ $opponent ][ $player_number ] ) ) {
                                                            $player_detail = $rubber->players[ $opponent ][ $player_number ];
                                                            if ( empty( $player_detail->system_record ) ) {
                                                                ?>
                                                                <a href="/<?php echo esc_attr( $dto->competition->type ); ?>s/<?php echo esc_attr( seo_url( $dto->event->name ) ); ?>/<?php echo esc_attr( $match->get_season() ); ?>/player/<?php echo esc_attr( seo_url( $player_detail->display_name ) ); ?>/">
                                                                <?php
                                                            }
                                                            ?>
                                                            <span class="<?php echo esc_attr( $player_detail->class ); ?>"
                                                            <?php
                                                            if ( ! empty( $player_detail->class ) ) {
                                                                ?>
                                                                data-bs-toggle="tooltip" data-bs-placement="top" data-bs-title="<?php echo esc_attr( $player_detail->description ); ?>"
                                                                <?php
                                                            }
                                                            ?>
                                                            ><?php echo esc_html( $player_detail->display_name ); ?></span>
                                                            <?php
                                                            if ( empty( $player_detail->system_record ) ) {
                                                                ?>
                                                                </a>
                                                                <?php
                                                            }
                                                            ?>
                                                            <?php
                                                        }
                                                        ?>
                                                    </span>
                                                </span>
                                            </div>
                                            <?php
                                        }
                                        ?>
                                    </div>
                                    <?php
                                    $match_message_class = null;
                                    $match_message_text  = null;
                                    $match_status_class  = null;
                                    $match_status_text   = null;
                                    if ( $is_winner ) {
                                        if ( ! empty( $match->player ) ) {
                                            if ( empty( $player_team_status ) || 'winner' === $player_team_status ) {
                                                $match_status_class = 'winner';
                                                $match_status_text  = 'W';
                                            }
                                        } elseif ( empty( $team_status ) || 'winner' === $team_status ) {
                                            $match_status_class = 'winner';
                                            $match_status_text  = 'W';
                                        }
                                        if ( $rubber->is_abandoned ) {
                                            $match_message_class = 'match-warning';
                                            $match_message_text  = __( 'Abandoned', 'racketmanager' );
                                        }
                                    } elseif ( $is_loser ) {
                                        if ( ! empty( $match->player ) ) {
                                            if ( 'loser' === $player_team_status ) {
                                                $match_status_class = 'loser';
                                                $match_status_text  = 'L';
                                            }
                                        } elseif ( 'loser' === $team_status ) {
                                            $match_status_class = 'loser';
                                            $match_status_text  = 'L';
                                        } else {
                                            $match_status_class = 'd-none';
                                            $match_status_text  = '';
                                        }
                                        if ( $rubber->is_walkover ) {
                                            $match_message_class = 'match-warning';
                                            $match_message_text  = __( 'Walkover', 'racketmanager' );
                                            if ( empty( $match_status_class ) ) {
                                                $match_status_class = 'd-none';
                                            }
                                            if ( isset( $team_statistics ) && 'winner' === $team_status ) {
                                                ++ $team_statistics['walkover'][ $match_type ];
                                                ++ $team_statistics['walkover']['t'];
                                            }
                                        } elseif ( $rubber->is_invalid ) {
                                            $match_message_class = 'match-warning';
                                            $match_message_text  = __( 'Invalid player', 'racketmanager' );
                                            if ( empty( $match_status_class ) ) {
                                                $match_status_class = 'd-none';
                                            }
                                            if ( isset( $team_statistics ) && 'winner' === $team_status ) {
                                                ++ $team_statistics['walkover'][ $match_type ];
                                                ++ $team_statistics['walkover']['t'];
                                            }
                                        } elseif ( $rubber->is_retired ) {
                                            $match_message_class = 'match-warning';
                                            $match_message_text  = __( 'Retired', 'racketmanager' );
                                        } elseif ( $rubber->is_abandoned ) {
                                            $match_message_class = 'match-warning';
                                            $match_message_text  = __( 'Abandoned', 'racketmanager' );
                                            if ( empty( $match_status_text ) ) {
                                                $match_status_class = 'loser';
                                                $match_status_text  = 'L';
                                            }
                                        }
                                    } elseif ( $is_tie ) {
                                        if ( $rubber->is_walkover ) {
                                            $match_message_class = 'match-warning';
                                            $match_message_text  = __( 'Walkover', 'racketmanager' );
                                            $match_status_class  = 'd-none';
                                            $match_status_text   = '';
                                        } elseif ( $rubber->is_invalid ) {
                                            $match_message_class = 'match-warning';
                                            $match_message_text  = __( 'Invalid player', 'racketmanager' );
                                            $match_status_class  = 'tie';
                                            $match_status_text   = 'T';
                                        } elseif ( $rubber->is_shared ) {
                                            $match_status_class  = 'tie';
                                            $match_message_class = 'match-warning';
                                            $match_status_text   = 'T';
                                            $match_message_text  = __( 'Not played', 'racketmanager' );
                                        } elseif ( $rubber->is_abandoned ) {
                                            $match_status_class  = 'tie';
                                            $match_message_class = 'match-warning';
                                            $match_status_text   = 'T';
                                            $match_message_text  = __( 'Abandoned', 'racketmanager' );
                                        }
                                    }
                                    ?>
                                    <span class="match__message <?php echo esc_attr( $match_message_class ); ?>"
                                          id="match-message-<?php echo esc_attr( $rubber->rubber_number ); ?>-<?php echo esc_attr( $team ? $team->team->get_id() : '' ); ?>">
                                    <?php echo esc_html( $match_message_text ); ?>
                                </span>
                                    <span class="match__status <?php echo esc_attr( $match_status_class ); ?>"
                                          id="match-status-<?php echo esc_attr( $rubber->rubber_number ); ?>-<?php echo esc_attr( $team ? $team->team->get_id() : '' ); ?>">
                                    <?php echo esc_html( $match_status_text ); ?>
                                </span>
                                </div>
                                <?php
                            }
                            ?>
                        </div>
                        <div class="match__result">
                            <?php
                            $sets = $rubber->sets ?? array();
                            for ( $i = 1; $i <= $dto->league->num_sets; $i ++ ) {
                                $set = $sets[ $i ] ?? array();
                                $p1 = '';
                                $p2 = '';
                                $tb = '';
                                if ( $set instanceof \Racketmanager\Domain\Scoring\Set_Score ) {
                                    $p1 = $set->get_home_games();
                                    $p2 = $set->get_away_games();
                                    $tb = $set->get_home_tiebreak() ?? $set->get_away_tiebreak() ?? '';
                                } elseif ( is_array( $set ) ) {
                                    $p1 = $set['player1'] ?? '';
                                    $p2 = $set['player2'] ?? '';
                                    $tb = $set['tiebreak'] ?? '';
                                }

                                if ( ! empty( $p1 ) || ! empty( $p2 ) ) {
                                    if ( $p1 > $p2 ) {
                                        $winner_set = 'player1';
                                    } elseif ( $p1 < $p2 ) {
                                        $winner_set = 'player2';
                                    } else {
                                        $winner_set = null;
                                    }
                                    ?>
                                    <ul class="match-points">
                                        <?php
                                        foreach ( $opponents_points as $opponent ) {
                                            if ( $winner_set === $opponent ) {
                                                $winner_class = ' winner';
                                            } else {
                                                $winner_class = '';
                                            }
                                            $val = 'player1' === $opponent ? $p1 : $p2;
                                            ?>
                                            <li class="match-points__cell <?php echo esc_html( $winner_class ); ?>">
                                                <?php echo esc_html( $val ); ?>
                                                <?php
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
                                ?>
                                <?php
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </li>
            <?php
        }
        ?>
    </ul>
    <?php
    if ( empty( $match_player ) && ( ! empty( $match->get_home_captain() ) || ! empty( $match->get_away_captain() ) ) ) {
        ?>
        <div class="mt-3" id="approvals">
            <div class="match">
                <div class="match__header">
                    <ul class="match__header-title">
                        <li class="match__header-title-item">
                            <span class="nav-link__value"><?php esc_html_e( 'Approvals', 'racketmanager' ); ?></span>
                        </li>
                    </ul>
                </div>
                <div class="match__body">
                    <div class="match__row-wrapper">
                        <?php
                        foreach ( $opponents as $opponent ) {
                            $team = 'home' === $opponent ? $dto->home_team : $dto->away_team;
                            ?>
                            <div class="match__row">
                                <div class="match__row-title">
                                    <div class="match__row-title-header">
                                        <?php echo esc_html( $team ? $team->team->get_name() : ( 'home' === $opponent ? $dto->prev_home_match_title : $dto->prev_away_match_title ) ); ?>
                                    </div>
                                    <div class="match__row-title-value">
                                        <?php
                                        $approval_captain_method = 'get_' . $opponent . '_captain';
                                        if ( method_exists( $match, $approval_captain_method ) && ! empty( $match->$approval_captain_method() ) ) {
                                            ?>
                                            <span class="match__row-title-value-content">
                                                <span class="nav-link__value"><?php echo esc_html( $racketmanager->get_player_name( $match->$approval_captain_method() ) ); ?></span>
                                            </span>
                                            <?php
                                        }
                                        ?>
                                    </div>
                                    <?php
                                    if ( ! empty( $match->get_custom()['comments'][ $opponent ] ) ) {
                                        ?>
                                        <div class="match__row-title-value">
                                            <span class="match__row-title-value-content">
                                                <span class="nav-link__value match-comments" title="<?php esc_attr_e( 'Match comments', 'racketmanager' ); ?>"><?php echo esc_html( $match->get_custom()['comments'][ $opponent ] ); ?></span>
                                            </span>
                                        </div>
                                        <?php
                                    }
                                    ?>
                                </div>
                            </div>
                            <?php
                        }
                        ?>
                        <?php
                        if ( ! empty( $match->get_custom()['comments']['result'] ) ) {
                            ?>
                            <div class="match__row match__row-comments">
                                <div class="match__row-title">
                                    <div class="match__row-title-header">
                                        <?php esc_html_e( 'Comments', 'racketmanager' ); ?>
                                    </div>
                                    <div class="match__row-title-value">
                                        <span class="match__row-title-value-content">
                                            <span class="nav-link__value match-comments" title="<?php esc_attr_e( 'Match comments', 'racketmanager' ); ?>"><?php echo esc_html( $match->get_custom()['comments']['result'] ); ?></span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <?php
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    ?>
</div>
