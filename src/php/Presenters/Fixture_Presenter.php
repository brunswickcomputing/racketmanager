<?php

namespace Racketmanager\Presenters;

use Racketmanager\Application\Fixture\DTOs\Fixture_Date_Update_Read_Model;
use Racketmanager\Application\Fixture\DTOs\Fixture_Result_Read_Model;
use Racketmanager\Application\Fixture\DTOs\Fixture_Status_Read_Model;
use Racketmanager\Application\Fixture\DTOs\Fixture_Switch_Teams_Read_Model;
use Racketmanager\Domain\DTO\Fixture\Fixture_Date_Update_Request;
use Racketmanager\Domain\DTO\Fixture\Fixture_Details_DTO;
use Racketmanager\Domain\DTO\Fixture\Fixture_Result_Update_Request;
use Racketmanager\Domain\DTO\Fixture\Fixture_Status_Update_Request;
use Racketmanager\Domain\DTO\Fixture\Fixture_Switch_Teams_Request;
use Racketmanager\Domain\DTO\Fixture\Fixture_Update_Response;
use Racketmanager\Domain\DTO\Fixture\Match_Option_Request;
use Racketmanager\Domain\Enums\Fixture\Fixture_Update_Status;
use Racketmanager\Domain\Enums\Fixture_Reset_Status;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Services\Fixture\Fixture_Link_Service;
use stdClass;

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

    public function map_to_match_option_vars(
        Fixture_Details_DTO $dto, Match_Option_Request $request, string $title, string $button, string $action
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
            Fixture_Reset_Status::SUCCESS_DIVISION_RESET => __( 'Division match result reset', 'racketmanager' ),
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

}
