<?php

namespace Racketmanager\Presenters;

use Racketmanager\Application\Fixture\DTOs\Fixture_Date_Update_Read_Model;
use Racketmanager\Application\Fixture\DTOs\Fixture_Detail_Read_Model;
use Racketmanager\Application\Fixture\DTOs\Fixture_Details_DTO as Application_Fixture_Details_DTO;
use Racketmanager\Application\Fixture\DTOs\Fixture_Header_Read_Model;
use Racketmanager\Application\Fixture\DTOs\Fixture_Result_Read_Model;
use Racketmanager\Application\Fixture\DTOs\Fixture_Status_Read_Model;
use Racketmanager\Application\Fixture\DTOs\Fixture_Switch_Teams_Read_Model;
use Racketmanager\Domain\DTO\Fixture\Fixture_Date_Update_Request;
use Racketmanager\Domain\DTO\Fixture\Fixture_Details_DTO;
use Racketmanager\Domain\DTO\Fixture\Fixture_Result_Update_Request;
use Racketmanager\Domain\DTO\Fixture\Fixture_Status_Update_Request;
use Racketmanager\Domain\DTO\Fixture\Fixture_Switch_Teams_Request;
use Racketmanager\Domain\DTO\Fixture\Fixture_Update_Response;
use Racketmanager\Domain\DTO\Fixture\Fixture_Option_Request;
use Racketmanager\Domain\Enums\Fixture\Fixture_Update_Status;
use Racketmanager\Domain\Enums\Fixture_Reset_Status;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Scoring\Set_Score;
use Racketmanager\Services\Fixture\Fixture_Link_Service;
use Racketmanager\Util\Util;
use Racketmanager\Util\Util_Lookup;
use stdClass;

use function Racketmanager\seo_url;

readonly class Fixture_Presenter {

    public function __construct(
        private Fixture_Link_Service $link_service
    ) {
    }

    public function map_to_status_read_model(
        Fixture $fixture, Fixture_Status_Update_Request $request, int $num_rubbers = 0
    ): Fixture_Status_Read_Model {
        $status_dtls = $this->get_status_details( (string) $request->match_status, (int) $fixture->get_home_team(), (int) $fixture->get_away_team() );

        return new Fixture_Status_Read_Model( (int) $fixture->get_id(), $status_dtls->status, $status_dtls->message, $status_dtls->class, $request->modal, $num_rubbers, $request->rubber_number );
    }

    /**
     * Function to set match or rubber status details
     *
     * @param string $status status value.
     * @param int $home_team home team id.
     * @param int $away_team away_team id.
     */
    public function get_status_details( string $status, int $home_team, int $away_team ): object {
        $status_message = array();
        $status_class   = array();
        $status_values  = explode( '_', $status );
        $status_value   = $status_values[0];
        $player_ref     = $status_values[1] ?? null;
        $winner         = null;
        $loser          = null;
        $score_message  = null;
        switch ( $status_value ) {
            case 'walkover':
                $score_message = __( 'Walkover', 'racketmanager' );
                if ( 'player2' === $player_ref ) {
                    $winner = $away_team;
                    $loser  = $home_team;
                } elseif ( 'player1' === $player_ref ) {
                    $winner = $home_team;
                    $loser  = $away_team;
                }
                break;
            case 'retired':
                $score_message = __( 'Retired', 'racketmanager' );
                if ( 'player1' === $player_ref ) {
                    $winner = $away_team;
                    $loser  = $home_team;
                } elseif ( 'player2' === $player_ref ) {
                    $winner = $home_team;
                    $loser  = $away_team;
                }
                break;
            case 'invalid':
                $score_message = __( 'Invalid player', 'racketmanager' );
                if ( 'player1' === $player_ref ) {
                    $winner = $away_team;
                    $loser  = $home_team;
                } elseif ( 'player2' === $player_ref ) {
                    $winner = $home_team;
                    $loser  = $away_team;
                }
                break;
            case 'share':
                $score_message = __( 'Not played', 'racketmanager' );
                break;
            case 'abandoned':
                $score_message = __( 'Abandoned', 'racketmanager' );
                break;
            case 'cancelled':
                $score_message = __( 'Cancelled', 'racketmanager' );
                break;
            case 'none':
                $status = '';
                break;
            default:
                break;
        }
        if ( $winner ) {
            $status_message[ $winner ] = '';
            $status_message[ $loser ]  = $score_message;
            $status_class[ $winner ]   = 'winner';
            $status_class[ $loser ]    = 'loser';
        } elseif ( 'share' === $status_value || 'cancelled' === $status_value || 'invalid' === $status_value ) {
            $status_message[ $home_team ] = $score_message;
            $status_message[ $away_team ] = $score_message;
            $status_class[ $home_team ]   = 'tie';
            $status_class[ $away_team ]   = 'tie';
        } elseif ( 'abandoned' === $status_value ) {
            $status_message[ $home_team ] = $score_message;
            $status_message[ $away_team ] = $score_message;
            $status_class[ $home_team ]   = '';
            $status_class[ $away_team ]   = '';
        } else {
            $status_message[ $home_team ] = '';
            $status_message[ $away_team ] = '';
            $status_class[ $home_team ]   = '';
            $status_class[ $away_team ]   = '';
        }
        $status_dtls          = new stdClass();
        $status_dtls->message = $status_message;
        $status_dtls->class   = $status_class;
        $status_dtls->status  = $status;

        return $status_dtls;
    }

    public function map_to_status_options( Fixture_Details_DTO $dto, ?string $status, ?string $modal ): array {
        $match = $dto->fixture;
        if ( empty( $status ) ) {
            $status = $this->resolve_match_status( $match );
        }

        $select = $this->build_status_options( $dto );

        return [
            'dto'    => $dto,
            'match'  => $match,
            'status' => $status,
            'modal'  => $modal,
            'select' => $select,
        ];
    }

    private function resolve_match_status( Fixture $match ): ?string {
        return match ( true ) {
            $match->is_walkover() => 'home' === $match->get_walkover() ? 'walkover_player1' : 'walkover_player2',
            $match->is_retired() => 'home' === $match->get_retired() ? 'retired_player1' : 'retired_player2',
            $match->is_shared() => 'share',
            default => null,
        };
    }

    private function build_status_options( Fixture_Details_DTO $dto ): array {
        $select = [];
        [ $home_name, $away_name ] = $this->get_team_names( $dto );

        $this->add_walkover_options( $select, $home_name, $away_name );

        // Retired options
        if ( $dto->competition->is_player_entry ) {
            $this->add_retired_options( $select, $home_name, $away_name );
        }

        // Standard options
        $select[] = $this->create_option( 'cancelled', __( 'Cancelled', 'racketmanager' ) );
        $select[] = $this->create_option( 'share', __( 'Not played', 'racketmanager' ) );

        if ( $dto->competition->is_team_entry ) {
            $select[] = $this->create_option( 'abandoned', __( 'Abandoned', 'racketmanager' ) );
        }

        // Reset option
        $select[] = $this->create_option( 'none', __( 'Reset', 'racketmanager' ) );

        return $select;
    }

    private function get_team_names( Fixture_Details_DTO $dto ): array {
        $home_name = $dto->home_team ? $dto->home_team->team->get_name() : ( $dto->prev_home_fixture_title ?? '' );
        $away_name = $dto->away_team ? $dto->away_team->team->get_name() : ( $dto->prev_away_fixture_title ?? '' );

        return [ $home_name, $away_name ];
    }

    private function add_walkover_options( array &$select, string $home_name, string $away_name ): void {
        $select[] = $this->create_option( 'walkover_player2', sprintf( __( 'Match not played - %s did not show', 'racketmanager' ), $home_name ) );
        $select[] = $this->create_option( 'walkover_player1', sprintf( __( 'Match not played - %s did not show', 'racketmanager' ), $away_name ) );
    }

    private function create_option( string $value, string $desc ): stdClass {
        $option         = new stdClass();
        $option->value  = $value;
        $option->select = $value;
        $option->desc   = $desc;

        return $option;
    }

    private function add_retired_options( array &$select, string $home_name, string $away_name ): void {
        $select[] = $this->create_option( 'retired_player1', sprintf( __( 'Retired - %s', 'racketmanager' ), $home_name ) );
        $select[] = $this->create_option( 'retired_player2', sprintf( __( 'Retired - %s', 'racketmanager' ), $away_name ) );
    }

    public function map_to_rubber_status_options(
        Fixture_Details_DTO $dto, object $rubber, ?string $status, ?string $modal
    ): array {
        $select = [];
        [ $home_name, $away_name ] = $this->get_team_names( $dto );

        $this->add_walkover_options( $select, $home_name, $away_name );
        $this->add_retired_options( $select, $home_name, $away_name );

        // Standard options
        $select[] = $this->create_option( 'abandoned', __( 'Abandoned', 'racketmanager' ) );
        $select[] = $this->create_option( 'share', __( 'Not played', 'racketmanager' ) );

        // Reset option
        $select[] = $this->create_option( 'none', __( 'Reset', 'racketmanager' ) );

        // Invalid player options
        $select[] = $this->create_option( 'invalid_player1', sprintf( __( 'Invalid player - %s', 'racketmanager' ), $home_name ) );
        $select[] = $this->create_option( 'invalid_player2', sprintf( __( 'Invalid player - %s', 'racketmanager' ), $away_name ) );
        $select[] = $this->create_option( 'invalid_players', __( 'Invalid player on both teams', 'racketmanager' ) );

        return [
            'dto'        => $dto,
            'match'      => $dto->fixture,
            'status'     => $status,
            'modal'      => $modal,
            'select'     => $select,
            'rubber'     => $rubber,
            'not_played' => __( 'Not played', 'racketmanager' ),
        ];
    }

    public function map_to_alert( string $message, string $type ): array {
        return [
            'msg'   => $message,
            'class' => $type,
        ];
    }

    public function map_to_fixture_option_vars(
        Fixture_Details_DTO $dto, Fixture_Option_Request $request, string $title, string $button, string $action
    ): array {
        return array(
            'dto'    => $dto,
            'match'  => $dto->fixture,
            'title'  => $title,
            'modal'  => $request->modal,
            'option' => $request->option,
            'action' => $action,
            'button' => $button,
        );
    }

    public function map_to_result_read_model(
        Fixture $fixture, Fixture_Update_Response $response, Fixture_Result_Update_Request $request
    ): Fixture_Result_Read_Model {
        return new Fixture_Result_Read_Model( $this->get_update_message( $response ), $fixture->get_home_points(), $fixture->get_away_points(), $fixture->get_winner_id(), $request->sets );
    }

    public function get_update_message( Fixture_Update_Response $response ): string {
        if ( $response->has_outcome( Fixture_Update_Status::PROGRESSED ) ) {
            return __( 'Result saved and draw updated', 'racketmanager' );
        }

        if ( $response->has_outcome( Fixture_Update_Status::TABLE_UPDATED ) ) {
            return __( 'Result saved and league table updated', 'racketmanager' );
        }

        return __( 'Result saved', 'racketmanager' );
    }

    public function get_reset_message( Fixture_Reset_Status $status ): string {
        return match ( $status ) {
            Fixture_Reset_Status::SUCCESS_DIVISION_RESET => __( 'Fixture result reset', 'racketmanager' ),
            Fixture_Reset_Status::SUCCESS_KNOCKOUT_RESET => __( 'Knockout result and progression reset', 'racketmanager' ),
            Fixture_Reset_Status::ERROR_NOT_FOUND => __( 'Fixture not found', 'racketmanager' ),
        };
    }

    public function map_to_date_update_read_model(
        Fixture_Date_Update_Request $request, string $formatted_date
    ): Fixture_Date_Update_Read_Model {
        return new Fixture_Date_Update_Read_Model( msg: __( 'Match schedule updated', 'racketmanager' ), match_id: $request->match_id, schedule_date: (string) $request->schedule_date, schedule_date_formated: $formatted_date, modal: $request->modal );
    }

    /**
     * Map to the switch teams read model.
     */
    public function map_to_switch_teams_read_model( Fixture $fixture, Fixture_Switch_Teams_Request $request, Fixture_Details_DTO $details ): Fixture_Switch_Teams_Read_Model {
        return new Fixture_Switch_Teams_Read_Model( msg: __( 'Home and away teams switched', 'racketmanager' ), match_id: $fixture->get_id(), link: $this->link_service->get_fixture_link( $fixture, $details->league, $details->home_team, $details->away_team ), modal: $request->modal );
    }

    public function render_header( Fixture_Header_Read_Model $model ): string {
        return $this->render( 'match/fragments/header', [ 'model' => $model ] );
    }

    public function render_detail( Fixture_Detail_Read_Model $model ): string {
        $template = $model->is_editing ? 'match/fragments/result-form' : 'match/fragments/detail';
        return $this->render( $template, [ 'model' => $model ] );
    }

    private function render( string $template, array $args ): string {
        extract( $args );
        ob_start();
        $template_path = RACKETMANAGER_PATH . 'src/php/Infrastructure/Wordpress/Views/' . $template . '.php';
        if ( ! file_exists( $template_path ) ) {
            // Fallback to legacy templates if new ones don't exist yet
            $template_path = RACKETMANAGER_PATH . 'templates/' . $template . '.php';
        }
        if ( file_exists( $template_path ) ) {
            include $template_path;
        }
        return ob_get_clean();
    }

    public function map_to_header_read_model( Application_Fixture_Details_DTO $dto, bool $edit_mode = false, bool $is_shortcode = false ): Fixture_Header_Read_Model {
        $fixture = $dto->fixture;

        $is_update_allowed = $dto->is_update_allowed ?? (object) [ 'user_can_update' => false, 'match_approval_mode' => false, 'user_type' => '' ];
        $event = $dto->event ?? (object) [ 'name' => '' ];
        $competition = $dto->competition ?? (object) [ 'type' => 'league' ];
        $league = $dto->league ?? (object) [ 'title' => '' ];
        $home_team_dto = $dto->home_team ?? null;
        $away_team_dto = $dto->away_team ?? null;
        $link = $dto->link ?? '';

        $allow_schedule_match     = false;
        $allow_switch_match       = false;
        $allow_amend_score        = false;
        $allow_reset_match_result = false;
        $show_menu                = false;

        if ( $fixture->is_pending() ) {
            if ( ! $edit_mode && $is_update_allowed->user_can_update ) {
                $allow_amend_score = true;
                $show_menu         = true;
                if ( ( 'admin' === $is_update_allowed->user_type || 'matchsecretary' === $is_update_allowed->user_type || 'captain' === $is_update_allowed->user_type ) && ( 'admin' === $is_update_allowed->user_type || 'both' === $is_update_allowed->user_team || 'home' === $is_update_allowed->user_team ) ) {
                    $allow_schedule_match = true;
                }
                if ( ( 'admin' === $is_update_allowed->user_type || ( 'matchsecretary' === $is_update_allowed->user_type && ( 'both' === $is_update_allowed->user_team || 'home' === $is_update_allowed->user_team ) ) ) && ( ! empty( $event->get_season_by_name( $fixture->get_season() )['home_away'] ) ) ) {
                    $allow_switch_match = true;
                }
            }
        } elseif ( ! $edit_mode ) {
            if ( 'admin' === $is_update_allowed->user_type ) {
                $allow_amend_score = true;
                $show_menu         = true;
            } elseif ( 'P' === $fixture->get_confirmed() ) {
                if ( $is_update_allowed->user_can_update && ! $is_update_allowed->match_approval_mode ) {
                    $allow_amend_score = true;
                    $show_menu         = true;
                }
            }
        } elseif ( 'admin' === $is_update_allowed->user_type ) {
            $allow_reset_match_result = true;
            $show_menu                = true;
        }

        $status_message     = null;
        $status_description = null;
        if ( ! empty( $fixture->get_status() ) ) {
            $status_message = Util_Lookup::get_match_status( (int) $fixture->get_status() );
            if ( 1 === (int) $fixture->get_status() ) {
                $team_ref_alt = $fixture->get_walkover();
                if ( $team_ref_alt ) {
                    $team_ref = 'home' === $team_ref_alt ? 'away' : 'home';
                    $team     = 'home' === $team_ref ? $home_team_dto : $away_team_dto;
                    if ( $team ) {
                        $status_description = $status_message . ' - ' . $team->team->get_name() . ' ' . __( 'did not show', 'racketmanager' );
                    }
                }
            } elseif ( 5 === (int) $fixture->get_status() && ! empty( $fixture->get_date_original() ) ) {
                $status_description = __( 'Match rescheduled from', 'racketmanager' ) . ' ' . mysql2date( 'j F Y H:i', $fixture->get_date_original() );
            }
        }

        $home_name = $home_team_dto ? $home_team_dto->team->get_name() : ( $dto->prev_home_fixture_title ?? $dto->enriched_data['prev_home_fixture_title'] ?? '' );
        $away_name = $away_team_dto ? $away_team_dto->team->get_name() : ( $dto->prev_away_fixture_title ?? $dto->enriched_data['prev_away_fixture_title'] ?? '' );

        $comp_type = 'league';
        if ( $competition instanceof Competition ) {
            $comp_type = $competition->get_type();
        } elseif ( is_object( $competition ) && method_exists( $competition, 'get_type' ) ) {
            $comp_type = $competition->get_type();
        } elseif ( is_object( $competition ) && isset( $competition->type ) ) {
            $comp_type = is_string( $competition->type ) ? $competition->type : ( method_exists( $competition->type, 'value' ) ? $competition->type->value : (string) $competition->type );
        }

        return new Fixture_Header_Read_Model(
            id: (int) $fixture->get_id(),
            event_name: $event->name,
            event_url: '/' . $comp_type . 's/' . seo_url( $event->name ) . '/' . $fixture->get_season() . '/',
            league_title: $league->title,
            league_url: '/' . $comp_type . '/' . seo_url( $league->title ) . '/' . $fixture->get_season() . '/',
            season: $fixture->get_season(),
            round_name: $fixture->get_final() ? Util::get_final_name( $fixture->get_final() ) : null,
            match_day: $fixture->get_match_day() ? (int) $fixture->get_match_day() : null,
            leg: $fixture->get_leg() ? (int) $fixture->get_leg() : null,
            fixture_date: $fixture->get_date(),
            formatted_date: (string) mysql2date( get_option( 'date_format' ), $fixture->get_date() ),
            original_date_formatted: $fixture->get_date_original() ? (string) mysql2date( 'j F Y H:i', $fixture->get_date_original() ) : null,
            home_team_name: $home_name,
            home_team_url: '/' . $comp_type . '/' . seo_url( $league->title ) . '/' . $fixture->get_season() . '/team/' . seo_url( $home_name ) . '/',
            home_team_withdrawn: $home_team_dto->is_withdrawn ?? false,
            away_team_name: $away_name,
            away_team_url: '/' . $comp_type . '/' . seo_url( $league->title ) . '/' . $fixture->get_season() . '/team/' . seo_url( $away_name ) . '/',
            away_team_withdrawn: $away_team_dto->is_withdrawn ?? false,
            is_pending: $fixture->is_pending(),
            score_class: $fixture->is_pending() ? 'is-not-played' : '',
            home_points: sprintf( '%g', $fixture->get_home_points() ),
            away_points: sprintf( '%g', $fixture->get_away_points() ),
            match_time: (string) mysql2date( get_option( 'time_format' ), $fixture->get_date() ),
            status_message: $status_message,
            status_description: $status_description,
            show_menu: $show_menu,
            allow_amend_score: $allow_amend_score,
            allow_schedule_match: $allow_schedule_match,
            allow_switch_match: $allow_switch_match,
            allow_reset_match_result: $allow_reset_match_result,
            amend_score_label: $fixture->is_pending() ? __( 'Enter result', 'racketmanager' ) : __( 'Adjust team score', 'racketmanager' ),
            match_link: $link,
            rubbers_score: isset( $fixture->get_custom()['stats']['rubbers'] ) ? $fixture->get_custom()['stats']['rubbers']['home'] . ' - ' . $fixture->get_custom()['stats']['rubbers']['away'] : '0 - 0',
            sets_score: isset( $fixture->get_custom()['stats']['sets'] ) ? $fixture->get_custom()['stats']['sets']['home'] . ' - ' . $fixture->get_custom()['stats']['sets']['away'] : '0 - 0',
            games_score: isset( $fixture->get_custom()['stats']['games'] ) ? $fixture->get_custom()['stats']['games']['home'] . ' - ' . $fixture->get_custom()['stats']['games']['away'] : '0 - 0',
            edit_mode: $edit_mode,
            user_can_update: (bool) ( $is_update_allowed->user_can_update ?? false ),
            match_approval_mode: (bool) ( $is_update_allowed->match_approval_mode ?? false ),
            is_shortcode: $is_shortcode
        );
    }

    public function map_to_detail_read_model( Application_Fixture_Details_DTO $dto, bool $is_standalone = false, bool $is_editing = false ): Fixture_Detail_Read_Model {
        $fixture       = $dto->fixture;
        $home_team_dto = $dto->home_team ?? null;
        $away_team_dto = $dto->away_team ?? null;
        
        $rubbers = [];

        foreach ( $dto->rubbers as $rubber ) {
            $rubbers[] = $this->map_rubber( $rubber, $dto );
        }

        $comments = $fixture->get_comments();

        $approvals = [];
        $opponents = [ 'home', 'away' ];
        foreach ( $opponents as $opponent ) {
            $team = 'home' === $opponent ? $home_team_dto : $away_team_dto;
            $approver_name = 'home' === $opponent ? $dto->home_approver_name : $dto->away_approver_name;
            $approvals[ $opponent ] = [
                'team_name' => $team ? $team->team->get_name() : '',
                'approver_name' => $approver_name,
                'comment' => $comments[ $opponent ] ?? null,
            ];
        }

        $location = null;
        $host_key = empty( $fixture->get_host() ) ? 'home' : $fixture->get_host();
        $host_team_dto = 'home' === $host_key ? $home_team_dto : $away_team_dto;

        if ( $host_team_dto && $host_team_dto->club ) {
            $location = [
                'club_name' => $host_team_dto->club->get_name(),
                'address' => $host_team_dto->club->get_address(),
                'match_secretary_name' => $host_team_dto->match_secretary ? $host_team_dto->match_secretary->get_fullname() : '',
                'match_secretary_contactno' => $host_team_dto->match_secretary ? $host_team_dto->match_secretary->get_contactno() : '',
                'match_secretary_email' => $host_team_dto->match_secretary ? $host_team_dto->match_secretary->get_email() : '',
                'website' => $host_team_dto->club->get_website(),
            ];
        }

        $is_update_allowed = $dto->is_update_allowed ?? (object) [ 'user_can_update' => false, 'match_approval_mode' => false, 'user_type' => '' ];

        $match_status = null;
        if ( ! empty( $fixture->get_walkover() ) ) {
            $match_status = 'home' === $fixture->get_walkover() ? 'walkover_player1' : 'walkover_player2';
        } elseif ( ! empty( $fixture->get_shared() ) ) {
            $match_status = 'share';
        } elseif ( ! empty( $fixture->get_retired() ) ) {
            $match_status = 'home' === $fixture->get_retired() ? 'retired_player1' : 'retired_player2';
        } elseif ( method_exists( $fixture, 'is_abandoned' ) && $fixture->is_abandoned() ) {
            $match_status = 'abandoned';
        }

        $teams = [];
        foreach ( $opponents as $opponent ) {
            $team_dto = 'home' === $opponent ? $home_team_dto : $away_team_dto;
            if ( $team_dto ) {
                $captain_name = 'home' === $opponent ? $dto->home_captain_name : $dto->away_captain_name;
                $contactno = 'home' === $opponent ? $dto->home_captain_contactno : $dto->away_captain_contactno;
                $contactemail = 'home' === $opponent ? $dto->home_captain_email : $dto->away_captain_email;

                $teams[ $opponent ] = [
                    'captain_name' => $captain_name,
                    'team_name' => $team_dto->team->get_name(),
                    'contactno' => $contactno,
                    'contactemail' => $contactemail,
                ];
            }
        }

        return new Fixture_Detail_Read_Model(
            rubbers: $rubbers,
            approvals: $approvals,
            general_comments: $comments['result'] ?? null,
            is_standalone: $is_standalone,
            location: $location,
            teams: $teams,
            show_print_button: ! $is_editing && ! $fixture->get_winner_id(),
            show_edit_button: ! $is_editing && $is_update_allowed->user_can_update,
            match_id: (int) $fixture->get_id(),
            edit_url: $dto->link ? $dto->link . 'result/' : "",
            is_editing: $is_editing,
            club_players: $dto->club_players,
            match_status: $match_status,
            permissions: [
                'user_can_update' => (bool) $is_update_allowed->user_can_update,
                'match_approval_mode' => (bool) $is_update_allowed->match_approval_mode,
                'user_type' => $is_update_allowed->user_type ?? '',
                'user_team' => $is_update_allowed->user_team ?? '',
                'match_update' => $is_update_allowed->match_update ?? false,
            ],
            num_sets: $dto->event ? $dto->event->get_num_sets() : 3,
            scoring_info: $this->get_match_scoring_info( $dto )
        );
    }

    private function get_match_scoring_info( Application_Fixture_Details_DTO $dto ): array {
        $scoring_info = [];
        $num_sets = $dto->event ? $dto->event->get_num_sets() : 3;
        $scoring = $dto->event->scoring ?? '';
        foreach ( $dto->rubbers as $rubber ) {
            $rubber_scoring = [];
            for ( $i = 1; $i <= $num_sets; $i++ ) {
                $set_type = Util::get_set_type( $scoring, $dto->fixture->final, $num_sets, $i, $rubber->rubber_number, $dto->event->num_rubbers, $dto->fixture->leg );
                $set_info = Util::get_set_info( $set_type );
                $rubber_scoring[ $i ] = [
                    'set_type' => $set_type,
                    'max_win' => $set_info->max_win,
                    'max_loss' => $set_info->max_loss,
                    'min_win' => $set_info->min_win,
                    'min_loss' => $set_info->min_loss,
                    'tiebreak_set' => $set_info->tiebreak_set,
                ];
            }
            $scoring_info[ $rubber->rubber_number ] = $rubber_scoring;
        }
        return $scoring_info;
    }

    private function map_rubber( object $rubber, Application_Fixture_Details_DTO $dto ): array {
        $home_team_dto = $dto->home_team ?? null;
        $away_team_dto = $dto->away_team ?? null;
        $event = $dto->event ?? (object) [ 'name' => '' ];
        $competition = $dto->competition ?? (object) [ 'type' => 'league' ];

        $winner = null;
        if ( ! empty( $rubber->winner_id ) ) {
            if ( $rubber->winner_id === $dto->fixture->get_home_team() ) {
                $winner = 'home';
            } elseif ( $rubber->winner_id === $dto->fixture->get_away_team() ) {
                $winner = 'away';
            }
        }

        $opponents = [ 'home', 'away' ];
        $mapped_opponents = [];
        foreach ( $opponents as $opponent ) {
            $team = 'home' === $opponent ? $home_team_dto : $away_team_dto;
            $players = [];
            $opponent_players = $rubber->players[ $opponent ] ?? [];
            foreach ( $opponent_players as $player_detail ) {
                $p_id = $player_detail->id ?? 0;
                // Use registration_id for dropdown selection consistency
                if ( ! empty( $player_detail->registration_id ) ) {
                    $p_id = (int) $player_detail->registration_id;
                } elseif ( ! empty( $player_detail->id ) ) {
                    $p_id = (int) $player_detail->id;
                }
                
                $players[] = [
                    'id' => $p_id,
                    'gender' => $player_detail->gender ?? 'm',
                    'name' => $player_detail->display_name ?? '',
                    'url' => empty( $player_detail->system_record ) ? '/' . $competition->get_type() . 's/' . seo_url( $event->name ) . '/' . $dto->fixture->get_season() . '/player/' . seo_url( $player_detail->display_name ?? '' ) . '/' : null,
                    'class' => $player_detail->class ?? '',
                    'description' => $player_detail->description ?? '',
                    'is_system_record' => ! empty( $player_detail->system_record ),
                ];
            }

            $gender_short = 'm';
            if ( ! empty( $players ) ) {
                $gender_short = $players[0]['gender'];
            } else {
                // Determine gender from rubber type if no players present
                $type_char = substr( $rubber->type, 0, 1 );
                if ( in_array( $type_char, [ 'W', 'G' ] ) ) {
                    $gender_short = 'f';
                }
            }

            $message = null;
            $status_text = null;
            $status_class = null;

            if ( $winner === $opponent ) {
                $status_text = 'W';
                $status_class = 'winner';
                if ( ! empty( $rubber->is_abandoned ) ) {
                    $message = __( 'Abandoned', 'racketmanager' );
                }
            } elseif ( $winner ) {
                $status_text = 'L';
                $status_class = 'loser';
                if ( ! empty( $rubber->is_walkover ) ) {
                    $message = __( 'Walkover', 'racketmanager' );
                } elseif ( ! empty( $rubber->is_invalid ) ) {
                    $message = __( 'Invalid player', 'racketmanager' );
                } elseif ( ! empty( $rubber->is_retired ) ) {
                    $message = __( 'Retired', 'racketmanager' );
                } elseif ( ! empty( $rubber->is_abandoned ) ) {
                    $message = __( 'Abandoned', 'racketmanager' );
                }
            } elseif ( ! empty( $rubber->winner_id ) && '-1' == $rubber->winner_id ) {
                $status_text = 'T';
                $status_class = 'tie';
                if ( ! empty( $rubber->is_walkover ) ) {
                    $message = __( 'Walkover', 'racketmanager' );
                } elseif ( ! empty( $rubber->is_invalid ) ) {
                    $message = __( 'Invalid player', 'racketmanager' );
                } elseif ( ! empty( $rubber->is_shared ) ) {
                    $message = __( 'Not played', 'racketmanager' );
                } elseif ( ! empty( $rubber->is_abandoned ) ) {
                    $message = __( 'Abandoned', 'racketmanager' );
                }
            }

            if ( $team ) {
                $team_name = $team->team->get_name();
            } else {
                if ( 'home' === $opponent ) {
                    $team_name = $dto->prev_home_fixture_title ?? $dto->enriched_data['prev_home_fixture_title'] ?? '';
                } else {
                    $team_name = $dto->prev_away_fixture_title ?? $dto->enriched_data['prev_away_fixture_title'] ?? '';
                }
            }

            $mapped_opponents[ $opponent ] = [
                'team_id' => 'home' === $opponent ? $dto->fixture->get_home_team() : $dto->fixture->get_away_team(),
                'team_name' => $team_name,
                'players' => $players,
                'message' => $message,
                'status_text' => $status_text,
                'status_class' => $status_class,
                'is_winner' => $winner === $opponent,
                'gender' => $gender_short,
            ];
        }

        $sets = [];
        if ( isset( $rubber->sets ) && ! empty( $rubber->sets ) ) {
            $s_count = 1;
            foreach ( $rubber->sets as $set_score ) {
                $p1 = null;
                $p2 = null;
                $tb = null;
                if ( $set_score instanceof Set_Score ) {
                    $p1 = $set_score->get_home_games();
                    $p2 = $set_score->get_away_games();
                    $tb = $set_score->get_home_tiebreak() ?? $set_score->get_away_tiebreak() ?? '';
                } elseif ( is_array( $set_score ) ) {
                    $p1 = $set_score['player1'] ?? '';
                    $p2 = $set_score['player2'] ?? '';
                    $tb = $set_score['tiebreak'] ?? '';
                }

                if ( ! empty( $p1 ) || ! empty( $p2 ) ) {
                    $sets[ $s_count ] = [
                        'home' => $p1,
                        'away' => $p2,
                        'tiebreak' => $tb,
                        'home_won' => (int) $p1 > (int) $p2,
                        'away_won' => (int) $p2 > (int) $p1,
                    ];
                    $s_count++;
                }
            }
        }

        $num_sets = 3;
        if ( $dto->event ) {
            if ( method_exists( $dto->event, 'get_num_sets' ) ) {
                $num_sets = $dto->event->get_num_sets();
            } elseif ( isset( $dto->event->num_sets ) ) {
                $num_sets = $dto->event->num_sets;
            }
        }
        if ( empty( $sets ) ) {
            for ( $i = 1; $i <= $num_sets; $i++ ) {
                $sets[ $i ] = [
                    'home' => '',
                    'away' => '',
                    'tiebreak' => '',
                    'home_won' => false,
                    'away_won' => false,
                ];
            }
        }

        return [
            'id' => $rubber->id,
            'number' => $rubber->rubber_number,
            'type' => $rubber->type,
            'status_key' => $rubber->status ?? null,
            'title' => $rubber->type . $rubber->rubber_number,
            'opponents' => $mapped_opponents,
            'sets' => $sets,
        ];
    }

}
