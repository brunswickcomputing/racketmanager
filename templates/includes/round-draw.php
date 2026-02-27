<?php
/**
 * Template for round draw
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

/** @var object $final */
/** @var int    $f */
/** @var string $player_class */

foreach ( $final->matches as $fixture_details ) {
    $fixture = $fixture_details->fixture;
    $league  = $fixture_details->league;
    $event   = $fixture_details->event;
    $competition = $fixture_details->competition;
    $home_team = $fixture_details->home_team->team ?? null;
    $away_team = $fixture_details->away_team->team ?? null;
    if ( empty( $fixture->leg ) || 2 === $fixture->leg ) {
        $winner = null;
        $loser  = null;
        $is_tie = false;
        if ( empty( $fixture->leg ) ) {
            if ( ! empty( $fixture->winner_id ) ) {
                if ( $fixture->winner_id === $home_team->id ) {
                    $winner = 'home';
                    $loser  = 'away';
                } elseif ( $fixture->winner_id === $away_team->id ) {
                    $winner = 'away';
                    $loser  = 'home';
                } elseif ( '-1' === $fixture->winner_id ) {
                    $is_tie = true;
                }
            }
        } elseif ( ! empty( $fixture->winner_id_tie ) ) {
            if ( $fixture->winner_id_tie === $home_team->id ) {
                $winner = 'home';
                $loser  = 'away';
            } elseif ( $fixture->winner_id_tie === $away_team->id ) {
                $winner = 'away';
                $loser  = 'home';
            }
        }
        ?>
        <div class="score-row draws-score-row round-<?php echo esc_attr( $f ); ?> carousel-index-<?php echo esc_attr( $f ); ?> <?php echo empty( $last_round ) ? '' : 'last-round'; ?> ">
            <div class="score-row__wrapper" aria-label="<?php esc_html_e( 'Match Link', 'racketmanager' ); ?>">
                <?php
                if ( is_numeric( $fixture->home_team ) && $fixture->home_team >= 1 && is_numeric( $fixture->away_team ) && $fixture->away_team >= 1 ) {
                    if ( empty( $tournament ) ) {
                        $match_link = $fixture->link_tie;
                    } else {
                        $match_link = '/tournament/' . seo_url( $tournament->name ) . '/match/' . seo_url( $league->title ) . '/' . seo_url( $home_team->title ) . '-vs-' . seo_url( $away_team->title ) . '/' . $fixture->id . '/';
                    }
                    ?>
                    <a href="<?php echo esc_url( ( $match_link ) ); ?>" class="score-row__anchor" aria-label="<?php esc_html_e( 'Match Link', 'racketmanager' ); ?>">
                    </a>
                    <?php
                }
                ?>
                <div class="score-row__players-wrapper">
                    <?php
                    $opponents = array( 'home' => $home_team, 'away' => $away_team );
                    foreach ( $opponents as $team_ref => $team ) {
                        $is_winner    = false;
                        $is_loser     = false;
                        $winner_class = null;
                        if ( $winner === $team_ref ) {
                            $is_winner    = true;
                            $winner_class = 'winner';
                        } elseif ( $loser === $team_ref) {
                            $is_loser = true;
                        }
                        if ( $team ) {
                            $team_id = $team->get_id();
                            $team_name = match (substr($event->type, 0, 1)) {
                                'M' => str_replace('Mens ', '', $team->title),
                                'W' => str_replace('Ladies ', '', $team->title),
                                'X' => str_replace('Mixed ', '', $team->title),
                                default => $team->title,
                            };
                        } else {
                            $team_id   = null;
                            $team_name = null;
                        }
                        ?>
                        <div class="player-row">
                            <div class="player-row__team-wrapper <?php echo esc_html( $winner_class ); ?>">
                                <?php
                                if ( empty( $team->players ) ) {
                                    ?>
                                    <div class="player-row__team">
                                        <?php
                                        if ( is_numeric( $team_id ) ) {
                                            if ( -1 !== $team_id && ! $competition->is_tournament ) {
                                                ?>
                                                <a class="" href="/<?php echo esc_attr( seo_url( $competition->type ) ); ?>s/<?php echo esc_attr( seo_url( $competition->name ) ); ?>/<?php echo esc_attr( seo_url( $league->title ) ); ?>/<?php echo esc_attr( seo_url( $team->title ) ); ?>">
                                                <?php
                                            }
                                            ?>
                                                <p>
                                                    <?php
                                                    if ( ! empty( $team->is_withdrawn ) ) {
                                                        $title_text = $team->title . ' ' . __( 'has withdrawn', 'racketmanager' );
                                                        ?>
                                                        <s aria-label="<?php echo esc_attr( $title_text ); ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="<?php echo esc_attr( $title_text ); ?>">
                                                        <?php
                                                    }
                                                    ?>
                                                    <?php echo esc_html( $team_name ); ?>
                                                    <?php
                                                    if ( ! empty( $team->is_withdrawn ) ) {
                                                        ?>
                                                        </s>
                                                        <?php
                                                    }
                                                    ?>
                                                    <?php
                                                    if ( ! $league->championship->is_consolation && isset( $team->rank ) && intval( $team->rank ) <= intval( $league->championship->num_seeds ) ) {
                                                        ?>
                                                        <span class="seeding"><?php echo esc_html( $team->rank ); ?></span>
                                                        <?php
                                                    }
                                                    ?>
                                                </p>
                                            <?php
                                            if ( -1 !== $team_id ) {
                                                ?>
                                                </a>
                                                <?php
                                            }
                                        } else {
                                            ?>
                                            <p>&nbsp;</p>
                                            <?php
                                        }
                                        ?>
                                    </div>
                                    <?php
                                } else {
                                    foreach ( $team->players as $player ) {
                                        ?>
                                        <div class="player-row__team <?php echo esc_attr( $player_class ); ?>">
                                            <?php
                                            if ( ! empty( $tournament ) ) {
                                                ?>
                                                <a href="/tournament/<?php echo esc_html( seo_url( $tournament->name ) ); ?>/player/<?php echo esc_html( seo_url( trim( $player->get_fullname() ) ) ); ?>">
                                                <?php
                                            }
                                            ?>
                                            <p>
                                                <?php
                                                if ( ! empty( $team->is_withdrawn ) ) {
                                                    $title_text = $team->title . ' ' . __( 'has withdrawn', 'racketmanager' );
                                                    ?>
                                                    <s aria-label="<?php echo esc_attr( $title_text ); ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="<?php echo esc_attr( $title_text ); ?>">
                                                    <?php
                                                }
                                                ?>
                                                <?php echo esc_html( trim( $player->get_fullname() ) ); ?>
                                                <?php
                                                if ( ! empty( $team->is_withdrawn ) ) {
                                                    ?>
                                                    </s>
                                                    <?php
                                                }
                                                ?>
                                                <?php
                                                if ( ! $league->championship->is_consolation && isset( $team->rank ) && intval( $team->rank ) <= intval( $league->championship->num_seeds ) ) {
                                                    ?>
                                                    <span class="seeding"><?php echo esc_html( $team->rank ); ?></span>
                                                    <?php
                                                }
                                                ?>
                                            </p>
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
                                    ?>
                                    <?php
                                }
                                ?>
                            </div>
                            <div class="player-row__score-wrapper">
                                <div class="player-row__score-badge">
                                    <?php
/**                                    if ( $is_winner ) {
                                        ?>
                                        <span class="match__status winner">W</span>
                                        <?php
                                    } elseif ( $is_tie ) {
                                        ?>
                                        <span class="match__status tie">T</span>
                                        <?php
                                    } elseif ( empty( $fixture->leg ) && ! empty( $fixture->host ) && $team_ref === $fixture->host ) {
                                        ?>
                                        <span><?php esc_html_e( 'H', 'racketmanager' ); ?></span>
                                        <?php
                                    } */
                                    ?>
                                    <?php
                                    $match_message_class = null;
                                    $match_message_text  = null;
                                    $match_status_class  = null;
                                    $match_status_text   = null;
                                    if ( $is_winner ) {
                                        $match_status_class = 'winner';
                                        $match_status_text  = 'W';
                                    } elseif ( $is_loser ) {
                                        $match_status_class = 'blank';
                                        if ( $fixture->is_walkover ) {
                                            $match_message_class = 'match-warning';
                                            $match_message_text  = __( 'Walkover', 'racketmanager' );
                                        } elseif ( $fixture->is_retired ) {
                                            $match_message_class = 'match-warning';
                                            $match_message_text  = __( 'Retired', 'racketmanager' );
                                        }
                                    } elseif ( $is_tie ) {
                                        $match_status_class  = 'tie';
                                        $match_message_class = 'match-warning';
                                        $match_status_text   = 'T';
                                        $match_message_text  = __( 'Not played', 'racketmanager' );
                                    } elseif ( empty( $fixture->leg ) && ! empty( $fixture->host ) && $team_ref === $fixture->host ) {
                                        $match_status_class  = 'blank';
                                        $match_message_class = 'match-info';
                                        $match_message_text  = __( 'H', 'racketmanager' );
                                    } else {
                                        $match_status_class  = 'blank';
                                    }
                                    ?>
                                    <span class="match__message <?php echo esc_attr( $match_message_class ); ?>" id="match-message-<?php echo esc_attr( $team_id ); ?>"><?php echo esc_html( $match_message_text ); ?></span>
                                    <span class="match__status <?php echo esc_attr( $match_status_class ); ?>" id="match-status-<?php echo esc_attr( $team_id ); ?>"><?php echo esc_html( $match_status_text ); ?></span>
                                </div>
                                <div class="d-none d-lg-flex player-row__score-game-wrapper">
                                    <?php
                                    if ( empty( $fixture->leg ) && $competition->is_team_entry ) {
                                        if ( 'home' === $team_ref ) {
                                            $points = $fixture->home_points;
                                        } else {
                                            $points = $fixture->away_points;
                                        }
                                        if ( ! empty( $winner ) ) {
                                            ?>
                                            <div class="player-row__score-game <?php echo esc_html( $winner_class ); ?>">
                                                <?php echo esc_html( sprintf( '%g', $points ) ); ?>
                                            </div>
                                            <?php
                                        }
                                        ?>
                                        <?php
                                    } elseif ( ! empty( $fixture->leg ) ) {
                                        if ( 'home' === $team_ref ) {
                                            $points = $fixture->home_points_tie;
                                        } else {
                                            $points = $fixture->away_points_tie;
                                        }
                                        if ( ! empty( $winner ) && '-1' !== $fixture->home_team && '-1' !== $fixture->away_team ) {
                                            ?>
                                            <div class="player-row__score-game <?php echo esc_html( $winner_class ); ?>">
                                                <?php echo esc_html( sprintf( '%g', $points ) ); ?>
                                            </div>
                                            <?php
                                        }
                                        ?>
                                        <?php
                                    } elseif ( ! empty( $fixture->rubbers ) ) {
                                        foreach ( $fixture->rubbers as $rubber ) {
                                            if ( 'home' === $team_ref ) {
                                                $set_ref     = 'player1';
                                                $set_ref_alt = 'player2';
                                            } else {
                                                $set_ref     = 'player2';
                                                $set_ref_alt = 'player1';
                                            }
                                            $sets = $rubber->custom['sets'] ?? array();
                                            foreach ( $sets as $set ) {
                                                if ( isset( $set[ $set_ref ] ) && '' !== $set[ $set_ref ] ) {
                                                    if ( $set[ $set_ref ] > $set [ $set_ref_alt ] ) {
                                                        $winner_class_set = 'winner';
                                                    } else {
                                                        $winner_class_set = null;
                                                    }
                                                    ?>
                                                    <div class="player-row__score-game  <?php echo esc_html( $winner_class_set ); ?>">
                                                        <?php echo esc_html( $set[ $set_ref ] ); ?>
                                                        <?php
                                                        if ( isset( $set['tiebreak'] ) ) {
                                                            ?>
                                                            <span class="player-row__tie-break"></span>
                                                            <?php
                                                        }
                                                        ?>
                                                    </div>
                                                    <?php
                                                }
                                            }
                                        }
                                    } elseif ( ! empty( $fixture->custom['sets'] ) ) {
                                        if ( 'home' === $team_ref ) {
                                            $set_ref     = 'player1';
                                            $set_ref_alt = 'player2';
                                        } else {
                                            $set_ref     = 'player2';
                                            $set_ref_alt = 'player1';
                                        }
                                        $sets = $fixture->custom['sets'];
                                        foreach ( $sets as $set ) {
                                            if ( isset( $set[ $set_ref ] ) && '' !== $set[ $set_ref ] ) {
                                                if ( $set[ $set_ref ] > $set [ $set_ref_alt ] ) {
                                                    $winner_class_set = 'winner';
                                                } else {
                                                    $winner_class_set = null;
                                                }
                                                ?>
                                                <div class="player-row__score-game  <?php echo esc_html( $winner_class_set ); ?>">
                                                    <?php echo esc_html( $set[ $set_ref ] ); ?>
                                                    <?php
                                                    if ( isset( $set['tiebreak'] ) && ! empty( $winner_class_set ) ) {
                                                        ?>
                                                        <span class="player-row__tie-break"><?php echo esc_html( $set['tiebreak'] ); ?></span>
                                                        <?php
                                                    }
                                                    ?>
                                                </div>
                                                <?php
                                            }
                                        }
                                    }
                                    ?>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>
        <?php
    }
}
