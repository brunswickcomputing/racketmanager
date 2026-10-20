<?php
/**
 * Template for round draw
 *
 * Rows are presenter-supplied Draw_Fixture_View_Model instances (see Draw_Presenter::present_finals(),
 * wired through Tournament_Presenter::present_tournament_draw() and Competition_Shortcode_Presenter::present_event_draw()).
 * Compatibility note: un-presented legacy rows (raw fixtures / DTOs) passed by external theme callers are skipped;
 * this template performs no presentation, URL generation or score parsing.
 *
 * @package Racketmanager/Templates
 */

namespace Racketmanager;

use Racketmanager\Presentation\View_Models\Draw_Fixture_View_Model;

/** @var object $final */
/** @var int    $f */
/** @var string $player_class */

foreach ( ( $final->fixtures ?? [] ) as $fixture_row ) {
    if ( ! $fixture_row instanceof Draw_Fixture_View_Model || $fixture_row->skip_render ) {
        continue;
    }
    ?>
    <div class="score-row draws-score-row round-<?php echo esc_attr( (string) $f ); ?> carousel-index-<?php echo esc_attr( (string) $f ); ?> <?php echo empty( $last_round ) ? '' : 'last-round'; ?> ">
        <div class="score-row__wrapper" aria-label="<?php esc_html_e( 'Match Link', 'racketmanager' ); ?>">
            <?php if ( '' !== $fixture_row->match_link ) : ?>
                <a href="<?php echo esc_url( $fixture_row->match_link ); ?>" class="score-row__anchor" aria-label="<?php esc_html_e( 'Match Link', 'racketmanager' ); ?>">
                </a>
            <?php endif; ?>
            <div class="score-row__players-wrapper">
                <?php foreach ( [ $fixture_row->home, $fixture_row->away ] as $opp ) : ?>
                    <?php
                    if ( ! $opp ) {
                        continue;
                    }
                    ?>
                    <div class="player-row">
                        <div class="player-row__team-wrapper <?php echo esc_attr( (string) $opp->winner_class ); ?>">
                            <?php if ( empty( $opp->players ) ) : ?>
                                <div class="player-row__team">
                                    <?php if ( $opp->has_team ) : ?>
                                        <?php if ( ! empty( $opp->team_url ) ) : ?>
                                            <a class="" href="<?php echo esc_url( $opp->team_url ); ?>">
                                        <?php endif; ?>
                                            <p>
                                                <?php if ( $opp->is_withdrawn ) : ?>
                                                    <s aria-label="<?php echo esc_attr( $opp->withdrawn_text ); ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="<?php echo esc_attr( $opp->withdrawn_text ); ?>">
                                                <?php endif; ?>
                                                <?php echo esc_html( $opp->team_name ); ?>
                                                <?php if ( $opp->is_withdrawn ) : ?>
                                                    </s>
                                                <?php endif; ?>
                                            </p>
                                        <?php if ( ! empty( $opp->team_url ) ) : ?>
                                            </a>
                                        <?php endif; ?>
                                    <?php else : ?>
                                        <p>&nbsp;</p>
                                    <?php endif; ?>
                                </div>
                            <?php else : ?>
                                <?php foreach ( $opp->players as $player ) : ?>
                                    <div class="player-row__team <?php echo esc_attr( $player_class ?? '' ); ?>">
                                        <?php if ( ! empty( $player->url ) ) : ?>
                                            <a href="<?php echo esc_url( $player->url ); ?>">
                                        <?php endif; ?>
                                            <p>
                                                <?php if ( $opp->is_withdrawn ) : ?>
                                                    <s aria-label="<?php echo esc_attr( $opp->withdrawn_text ); ?>" data-bs-toggle="tooltip" data-bs-placement="right" title="<?php echo esc_attr( $opp->withdrawn_text ); ?>">
                                                <?php endif; ?>
                                                <?php echo esc_html( $player->name ); ?>
                                                <?php if ( $opp->is_withdrawn ) : ?>
                                                    </s>
                                                <?php endif; ?>
                                            </p>
                                        <?php if ( ! empty( $player->url ) ) : ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div class="player-row__score-wrapper">
                            <div class="player-row__score-badge">
                                <span class="match__message <?php echo esc_attr( (string) $opp->match_message_class ); ?>" id="match-message-<?php echo esc_attr( (string) $opp->team_id ); ?>"><?php echo esc_html( (string) $opp->match_message_text ); ?></span>
                                <span class="match__status <?php echo esc_attr( (string) $opp->match_status_class ); ?>" id="match-status-<?php echo esc_attr( (string) $opp->team_id ); ?>"><?php echo esc_html( (string) $opp->match_status_text ); ?></span>
                            </div>
                            <div class="d-none d-lg-flex player-row__score-game-wrapper">
                                <?php foreach ( $opp->scores as $score ) : ?>
                                    <div class="player-row__score-game <?php echo esc_attr( (string) $score->winner_class ); ?>">
                                        <?php echo esc_html( $score->games ); ?>
                                        <?php if ( $score->has_tiebreak ) : ?>
                                            <span class="player-row__tie-break"><?php echo esc_html( $score->tiebreak ); ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php
}
