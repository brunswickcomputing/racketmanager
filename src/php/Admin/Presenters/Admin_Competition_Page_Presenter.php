<?php
/**
 * Competition admin page view model preparation.
 *
 * @package RacketManager
 */

declare( strict_types=1 );

namespace Racketmanager\Admin\Presenters;

use Racketmanager\Admin\View_Models\Error_Bag;
use Racketmanager\Admin\View_Models\Season_Modify_Page_View_Model;
use Racketmanager\Admin\View_Models\Tournament_Competition_Config_Page_View_Model;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Tournament\Tournament;
use Racketmanager\Services\Club_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Finance_Service;
use Racketmanager\Services\Season_Service;

final readonly class Admin_Competition_Page_Presenter {
    public function __construct(
        private Competition_Service $competition_service,
        private Club_Service $club_service,
        private Season_Service $season_service,
        private Finance_Service $finance_service,
    ) {
    }

    public function config( Competition $competition, ?Tournament $tournament, string $tab, array $flash_data ): Tournament_Competition_Config_Page_View_Model {
        $config            = (object) $competition->get_settings();
        $config->type      = $competition->get_type();
        $config->age_group = $competition->age_group ?? ( method_exists( $competition, 'get_age_group' ) ? $competition->get_age_group() : null );
        $title             = $competition->name ?? '';

        $form_data = $flash_data['form_data'] ?? array();
        if ( is_array( $form_data ) && $form_data ) {
            if ( isset( $form_data['competition_title'] ) ) {
                $title = sanitize_text_field( wp_unslash( strval( $form_data['competition_title'] ) ) );
            }
            if ( ! isset( $config->start_time ) || ! is_array( $config->start_time ) ) {
                $config->start_time = array(
                    'weekday' => array( 'min' => null, 'max' => null ),
                    'weekend' => array( 'min' => null, 'max' => null ),
                );
            }
            foreach ( array( 'weekday', 'weekend' ) as $period ) {
                foreach ( array( 'min', 'max' ) as $bound ) {
                    $key = $bound . '_start_time_' . $period;
                    if ( isset( $form_data[ $key ] ) ) {
                        $config->start_time[ $period ][ $bound ] = $form_data[ $key ];
                    }
                }
            }
            if ( isset( $form_data['default_match_start_time'] ) ) {
                $time = explode( ':', strval( $form_data['default_match_start_time'] ) );
                $config->default_match_start_time = 2 === count( $time )
                    ? array( 'hour' => intval( $time[0] ), 'hours' => intval( $time[0] ), 'minutes' => intval( $time[1] ) )
                    : $form_data['default_match_start_time'];
            }
            $fields = array(
                'sport', 'mode', 'entry_type', 'age_group', 'competition_code', 'grade', 'max_teams', 'num_entries',
                'teams_per_club', 'teams_prom_relg', 'lowest_promotion', 'team_ranking', 'point_rule', 'scoring',
                'num_sets', 'num_rubbers', 'reverse_rubbers', 'fixed_match_dates', 'home_away', 'round_length',
                'home_away_diff', 'filler_weeks', 'match_day_restriction', 'match_day_weekends', 'match_days_allowed',
                'point_format', 'point_2_format', 'num_matches_per_page', 'rules', 'standings', 'num_courts_available',
            );
            foreach ( $fields as $key ) {
                if ( array_key_exists( $key, $form_data ) ) {
                    $config->$key = $form_data[ $key ];
                }
            }
            foreach ( array( 'reverse_rubbers', 'match_day_restriction', 'match_day_weekends' ) as $key ) {
                if ( ! array_key_exists( $key, $form_data ) ) {
                    $config->$key = false;
                }
            }
            foreach ( array( 'match_days_allowed', 'rules' ) as $key ) {
                if ( ! array_key_exists( $key, $form_data ) ) {
                    $config->$key = array();
                }
            }
        }

        $clubs = $this->club_service->get_clubs( array( 'type' => 'affiliated' ) );
        return new Tournament_Competition_Config_Page_View_Model(
            competition: $competition,
            tournament: $tournament,
            rules_options: $this->competition_service->get_rules_options( $competition->get_type() ),
            clubs: is_array( $clubs ) ? $clubs : array(),
            events: $this->competition_service->get_events_for_competition( (int) $competition->get_id() ) ?? array(),
            tab: $tab,
            errors: $this->errors( $flash_data ),
            config: $config,
            title: $title,
        );
    }

    public function season( Competition $competition, ?string $season, bool $edit_mode, array $flash_data ): Season_Modify_Page_View_Model {
        $current_season = null;
        if ( $edit_mode ) {
            $current_season = (object) $competition->get_season_by_name( $season );
            $charges = $this->finance_service->get_charges_by_criteria( array(
                'competition' => $competition->get_id(),
                'season' => $season,
            ) );
            $fee_competition = 0;
            $fee_event       = 0;
            $fee_status      = null;
            $fee_id          = null;
            switch ( count( $charges ) ) {
                case 1:
                    $fee_competition = $charges[0]->fee_competition;
                    $fee_event       = $charges[0]->fee_event;
                    $fee_status      = $charges[0]->status;
                    $fee_id          = $charges[0]->id;
                    break;
                case 0:
                    break;
                default:
                    foreach ( $charges as $charge ) {
                        $fee_competition += $charge->fee_competition;
                        $fee_event       += $charge->fee_event;
                        $fee_status      = $charge->status;
                    }
                    break;
            }
            $current_season->fee_competition = $fee_competition;
            $current_season->fee_event       = $fee_event;
            $current_season->fee_status      = $fee_status;
            $current_season->fee_id          = $fee_id;
        }

        $form = $flash_data['form_data'] ?? array();
        if ( is_array( $form ) && $form ) {
            $current_season ??= (object) array( 'name' => $season ?? ( $form['season'] ?? null ) );
            foreach ( array( 'venue', 'max_teams', 'teams_per_club', 'teams_prom_relg', 'lowest_promotion', 'num_match_days', 'round_length', 'home_away_diff', 'filler_weeks', 'feeLeadTime' ) as $key ) {
                if ( isset( $form[ $key ] ) ) {
                    $target = 'feeLeadTime' === $key ? 'fee_lead_time' : $key;
                    $current_season->$target = intval( $form[ $key ] );
                }
            }
            foreach ( array( 'dateStart' => 'date_start', 'dateEnd' => 'date_end', 'dateOpen' => 'date_open', 'dateClose' => 'date_closing', 'grade' => 'grade' ) as $key => $target ) {
                if ( isset( $form[ $key ] ) ) {
                    $current_season->$target = sanitize_text_field( wp_unslash( strval( $form[ $key ] ) ) );
                }
            }
            foreach ( array( 'homeAway' => 'home_away', 'fixedMatchDates' => 'fixed_match_dates' ) as $key => $target ) {
                if ( isset( $form[ $key ] ) ) {
                    $current_season->$target = 'true' === $form[ $key ];
                }
            }
            foreach ( array( 'feeClub' => 'fee_competition', 'feeTeam' => 'fee_event' ) as $key => $target ) {
                if ( isset( $form[ $key ] ) ) {
                    $current_season->$target = floatval( $form[ $key ] );
                }
            }
        }

        $clubs = $this->club_service->get_clubs();
        return new Season_Modify_Page_View_Model(
            competition: $competition,
            season: $season,
            current_season: $current_season,
            seasons: $this->season_service->get_all_seasons(),
            clubs: is_array( $clubs ) ? $clubs : array(),
            edit_mode: $edit_mode,
            errors: $this->errors( $flash_data ),
        );
    }

    private function errors( array $flash_data ): Error_Bag {
        return is_array( $flash_data['errors'] ?? null ) ? new Error_Bag( $flash_data['errors'] ) : new Error_Bag();
    }
}