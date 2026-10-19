<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\Presenters;

use Racketmanager\Application\Matches\DTOs\Match_Detail_Result;
use Racketmanager\Application\Matches\DTOs\Match_Header_Result;
use Racketmanager\Application\Matches\DTOs\Match_Score_Result;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use Racketmanager\Services\Player_Service;
use Racketmanager\Util\Util;
use function __;
use function is_array;
use function is_object;
use function method_exists;
use function mysql2date;
use function str_starts_with;

/**
 * Turns [score], [match-header] and [match-detail] query results into rendered HTML.
 */
class Match_Shortcode_Presenter {
    public function __construct(
        private readonly Shortcode_View_Presenter $view_presenter,
        private readonly Fixture_Presenter $fixture_presenter,
        private readonly Player_Service $player_service
    ) {}

    public function render_score( Match_Score_Result $result ): string {
        if ( null !== $result->error_message || null === $result->details ) {
            return $this->view_presenter->render_error( (string) $result->error_message );
        }

        $match = $result->details->fixture;
        if ( ! $result->played ) {
            if ( ! $result->home_away ) {
                return "<span class='unplayedMatch'>&nbsp;</span>";
            }
            $match_date = (string) $match->get_date();
            $date       = str_starts_with( $match_date, '0000-00-00' ) ? 'N/A' : mysql2date( 'D d/m/Y', $match_date );
            $match_day  = $match->get_match_day() ? __( 'Match Day', 'racketmanager' ) . ' ' . $match->get_match_day() : '';

            return "<span class='unplayedMatch'>" . $match_day . '<br/>' . $date . '</span><br/>';
        }

        $link_title = $result->home_away ? __( 'Match Day', 'racketmanager' ) . ' ' . $match->get_match_day() : '';
        $filename   = '' !== $result->template ? 'score-' . $result->template : 'score';

        return $this->view_presenter->render(
            new Shortcode_View_Model(
                template: $filename,
                vars: [
                    'dto'          => $result->details,
                    'match'        => $match,
                    'score_class'  => $result->score_class,
                    'link_title'   => $link_title,
                    'score_team_1' => $result->score_team_1,
                    'score_team_2' => $result->score_team_2,
                    'home_away'    => $result->home_away,
                ],
                template_type: 'match'
            )
        );
    }

    public function render_header( Match_Header_Result $result ): string {
        if ( null !== $result->error_message || null === $result->details ) {
            return $this->view_presenter->render_error( (string) $result->error_message );
        }

        $filename = '' !== $result->template ? 'match-header-' . $result->template : 'match-header';

        $model = $this->fixture_presenter->map_to_header_read_model( $result->details, (bool) $result->edit_mode, true );

        return $this->view_presenter->render(
            new Shortcode_View_Model(
                template: $filename,
                vars: [
                    'model'     => $model,
                    // Retained for existing theme overrides; the bundled header only consumes the model.
                    'dto'       => $result->details,
                    'match'     => $result->details->fixture,
                    'edit_mode' => $result->edit_mode,
                ],
                template_type: 'match'
            )
        );
    }

    public function render_detail( Match_Detail_Result $result ): string {
        if ( null !== $result->error_message || null === $result->details ) {
            return $this->view_presenter->render_error( (string) $result->error_message );
        }

        $fixture = $result->details->fixture;
        $date_format = $this->fixture_presenter->get_date_format();
        $match_date = $fixture && method_exists( $fixture, 'get_date' ) ? (string) $fixture->get_date() : ( $fixture->date ?? '' );
        $match_date_formatted = ( '' !== $match_date && ! str_starts_with( $match_date, '0000-00-00' ) )
            ? (string) mysql2date( $date_format, $match_date )
            : '';

        $approval_captain_names = [];
        if ( $fixture ) {
            foreach ( [ 'home', 'away' ] as $opponent ) {
                $captain_id = null;
                $captain_method = 'get_' . $opponent . '_captain';
                $approver_method = 'get_' . $opponent . '_approver';
                if ( method_exists( $fixture, $captain_method ) && ! empty( $fixture->$captain_method() ) ) {
                    $captain_id = (int) $fixture->$captain_method();
                } elseif ( method_exists( $fixture, $approver_method ) && ! empty( $fixture->$approver_method() ) ) {
                    $captain_id = (int) $fixture->$approver_method();
                } elseif ( ! empty( $fixture->{$opponent . '_captain'} ) ) {
                    $captain_id = (int) $fixture->{$opponent . '_captain'};
                }

                if ( $captain_id ) {
                    $captain_name = '';
                    try {
                        $captain_player = $this->player_service->get_player( $captain_id );
                        $captain_name   = $captain_player ? $captain_player->get_name() : '';
                    } catch ( \Throwable ) {
                        $captain_name = '';
                    }
                    $approval_captain_names[ $opponent ] = $captain_name;
                }
            }
        }

        $rubbers = $fixture && method_exists( $fixture, 'get_rubbers' )
            ? $fixture->get_rubbers()
            : ( $fixture->rubbers ?? ( $result->details->rubbers ?? [] ) );

        $rubber_player_urls = [];
        if ( ! empty( $rubbers ) && is_array( $rubbers ) ) {
            $competition_type = $result->details->competition?->type instanceof \BackedEnum
                ? $result->details->competition->type->value
                : (string) ( $result->details->competition?->type ?? '' );
            $event_name = $result->details->event?->name ?? '';
            $season     = $fixture ? ( method_exists( $fixture, 'get_season' ) ? (string) $fixture->get_season() : (string) ( $fixture->season ?? '' ) ) : '';

            foreach ( $rubbers as $rubber ) {
                if ( empty( $rubber->players ) || ! is_array( $rubber->players ) ) {
                    continue;
                }
                $rubber_id = method_exists( $rubber, 'get_id' ) ? $rubber->get_id() : ( $rubber->id ?? null );
                if ( null === $rubber_id || false === $rubber_id ) {
                    continue;
                }

                foreach ( [ 'home', 'away' ] as $side ) {
                    if ( empty( $rubber->players[ $side ] ) || ! is_array( $rubber->players[ $side ] ) ) {
                        continue;
                    }
                    foreach ( $rubber->players[ $side ] as $p_idx => $player_detail ) {
                        if ( ! is_object( $player_detail ) ) {
                            continue;
                        }
                        if ( ! empty( $player_detail->system_record ) ) {
                            continue;
                        }
                        $display_name = $player_detail->display_name ?? ( method_exists( $player_detail, 'get_display_name' ) ? $player_detail->get_display_name() : '' );
                        if ( '' === $event_name || empty( $display_name ) ) {
                            continue;
                        }
                        $rubber_player_urls[ $rubber_id ][ $side ][ $p_idx ] = '/' . $competition_type . 's/' . Util::seo_url( $event_name ) . '/' . $season . '/player/' . Util::seo_url( $display_name ) . '/';
                    }
                }
            }
        }

        $final_name = ( $fixture && method_exists( $fixture, 'get_final' ) && ! empty( $fixture->get_final() ) )
            ? Util::get_final_name( $fixture->get_final() )
            : ( ! empty( $fixture?->final ) ? Util::get_final_name( $fixture->final ) : '' );

        $vars = [
            'dto'                    => $result->details,
            'match'                  => $result->details->fixture,
            'is_update_allowed'      => $result->details->is_update_allowed,
            'match_round'            => $result->round,
            'match_type'             => $result->type,
            'match_date_formatted'   => $match_date_formatted,
            'approval_captain_names' => $approval_captain_names,
            'rubber_player_urls'     => $rubber_player_urls,
            'final_name'             => $final_name,
            'match_player'           => $result->match_player,
        ];
        $filename = '' !== $result->template ? 'detail-' . $result->template : 'detail';

        return $this->view_presenter->render(
            new Shortcode_View_Model( template: $filename, vars: $vars, template_type: 'match' )
        );
    }
}
