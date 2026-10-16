<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\Presenters;

use Racketmanager\Application\Matches\DTOs\Match_Detail_Result;
use Racketmanager\Application\Matches\DTOs\Match_Header_Result;
use Racketmanager\Application\Matches\DTOs\Match_Score_Result;
use Racketmanager\Presentation\View_Models\Shortcode_View_Model;
use function __;
use function mysql2date;

/**
 * Turns [score], [match-header] and [match-detail] query results into rendered HTML.
 */
class Match_Shortcode_Presenter {
    public function __construct( private readonly Shortcode_View_Presenter $view_presenter ) {}

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

        return $this->view_presenter->render(
            new Shortcode_View_Model(
                template: $filename,
                vars: [
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

        $vars = [
            'dto'               => $result->details,
            'match'             => $result->details->fixture,
            'is_update_allowed' => $result->details->is_update_allowed,
            'match_round'       => $result->round,
            'match_type'        => $result->type,
        ];
        if ( null !== $result->match_player ) {
            $vars['match_player'] = $result->match_player;
        }
        $filename = '' !== $result->template ? 'detail-' . $result->template : 'detail';

        return $this->view_presenter->render(
            new Shortcode_View_Model( template: $filename, vars: $vars, template_type: 'match' )
        );
    }
}
