<?php
/**
 * Cup setup action dispatcher
 *
 * @package RacketManager
 * @subpackage Services/Admin/Cup
 */

declare( strict_types=1 );

namespace Racketmanager\Services\Admin\Cup;

use Racketmanager\Application\Tournament\Commands\Generate_Tournament_Bracket_Command;
use Racketmanager\Application\Tournament\Commands\Generate_Tournament_Bracket_Handler;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\Season_Not_Found_Exception;
use Racketmanager\Services\Admin\Championship\Draw_Action_Handler_Interface;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Competition_Service;

class Cup_Setup_Action_Dispatcher {

    public function __construct(
        private readonly Competition_Service $competition_service,
        private readonly Draw_Action_Handler_Interface $championship_admin_service,
        private readonly Action_Guard_Interface $action_guard,
        private readonly ?Generate_Tournament_Bracket_Handler $generate_tournament_bracket_handler = null,
    ) {
    }

    /**
     * @param array<string, mixed> $query Typically $_GET
     * @param array<string, mixed> $post  Typically $_POST
     */
    public function handle( array $query, array $post ): Action_Result_DTO {
        if ( isset( $post['rank'] ) ) {
            return $this->handle_calculate_ratings( $query, $post );
        }

        if ( isset( $post['action'] ) ) {
            $league_id = isset( $post['league_id'] ) ? intval( $post['league_id'] ) : ( isset( $query['league_id'] ) ? intval( $query['league_id'] ) : ( isset( $query['league'] ) ? intval( $query['league'] ) : null ) );

            if ( $league_id ) {
                return $this->handle_league_setup( $query, $post, $league_id );
            }

            return $this->handle_competition_setup( $query, $post );
        }

        return new Action_Result_DTO();
    }

    /**
     * Handle calculate ratings for cup
     *
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     */
    private function handle_calculate_ratings( array $query, array $post ): Action_Result_DTO {
        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_calculate_ratings', 'edit_matches' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO( $e->getMessage(), Admin_Message_Type::ERROR );
        }

        $competition_id = isset( $post['competition_id'] ) ? intval( $post['competition_id'] ) : ( isset( $query['competition_id'] ) ? intval( $query['competition_id'] ) : null );
        $season         = isset( $post['season'] ) ? intval( $post['season'] ) : ( isset( $query['season'] ) ? intval( $query['season'] ) : null );

        if ( ! $competition_id ) {
            return new Action_Result_DTO( __( 'Competition not found', 'racketmanager' ), Admin_Message_Type::ERROR );
        }

        try {
            $this->competition_service->calculate_team_ratings( $competition_id, $season );
            return new Action_Result_DTO( __( 'Cup ratings set', 'racketmanager' ), Admin_Message_Type::SUCCESS );
        } catch ( Competition_Not_Found_Exception|Season_Not_Found_Exception $e ) {
            return new Action_Result_DTO( $e->getMessage(), Admin_Message_Type::ERROR );
        }
    }

    /**
     * Handle competition-level round match dates setup
     *
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     */
    private function handle_competition_setup( array $query, array $post ): Action_Result_DTO {
        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_add_championship-matches', 'edit_matches' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO( $e->getMessage(), Admin_Message_Type::ERROR );
        }

        $competition_id = isset( $post['competition_id'] ) ? intval( $post['competition_id'] ) : ( isset( $query['competition_id'] ) ? intval( $query['competition_id'] ) : null );
        $season         = isset( $post['season'] ) ? strval( $post['season'] ) : ( isset( $query['season'] ) ? strval( $query['season'] ) : null );

        if ( ! $competition_id || null === $season ) {
            return new Action_Result_DTO( __( 'Competition not found', 'racketmanager' ), Admin_Message_Type::ERROR );
        }

        try {
            $competition = $this->competition_service->get_competition( $competition_id );
            if ( ! $competition ) {
                return new Action_Result_DTO( __( 'Competition not found', 'racketmanager' ), Admin_Message_Type::ERROR );
            }

            $cup_season   = $competition->get_season_by_name( $season );
            $input_rounds = $post['rounds'] ?? array();

            if ( empty( $input_rounds ) || ! is_array( $input_rounds ) ) {
                return new Action_Result_DTO( __( 'No rounds specified', 'racketmanager' ), Admin_Message_Type::ERROR );
            }

            $msg             = array();
            $rounds          = array();
            $valid           = true;
            $next_round_date = null;

            foreach ( $input_rounds as $round ) {
                $round_num  = $round['round'] ?? '';
                $match_date = $round['match_date'] ?? '';

                if ( empty( $match_date ) ) {
                    /* translators: %s: round number */
                    $msg[] = sprintf( __( 'Match date missing for round %s', 'racketmanager' ), $round_num );
                    $valid = false;
                } elseif ( ! empty( $next_round_date ) && $match_date >= $next_round_date ) {
                    /* translators: %s: round number */
                    $msg[] = sprintf( __( 'Match date for round %s after next round date', 'racketmanager' ), $round_num );
                    $valid = false;
                } else {
                    $rounds[]        = $match_date;
                    $next_round_date = $match_date;
                }
            }

            if ( ! $valid ) {
                return new Action_Result_DTO(
                    message: implode( '<br>', $msg ),
                    message_type: Admin_Message_Type::ERROR,
                    flash_data: array( 'form_data' => $this->sanitize_form_data( $post ) )
                );
            }

            $cup_season['match_dates']    = array_reverse( $rounds );
            $cup_seasons                  = $competition->get_seasons();
            $cup_season['num_match_days'] = count( $cup_season['match_dates'] );
            $cup_seasons[ $season ]       = $cup_season;
            $this->competition_service->update_seasons( $competition, $cup_seasons );

            return new Action_Result_DTO( __( 'Cup match dates updated', 'racketmanager' ), Admin_Message_Type::SUCCESS );

        } catch ( Competition_Not_Found_Exception|Season_Not_Found_Exception $e ) {
            return new Action_Result_DTO( $e->getMessage(), Admin_Message_Type::ERROR );
        }
    }

    /**
     * Handle league-level championship fixtures setup
     *
     * @param array<string, mixed> $query
     * @param array<string, mixed> $post
     * @param int                  $league_id
     */
    private function handle_league_setup( array $query, array $post, int $league_id ): Action_Result_DTO {
        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_add_championship-matches', 'edit_matches' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO( $e->getMessage(), Admin_Message_Type::ERROR );
        }

        $rounds = $post['rounds'] ?? null;
        if ( empty( $rounds ) || ! is_array( $rounds ) ) {
            return new Action_Result_DTO( __( 'No rounds specified', 'racketmanager' ), Admin_Message_Type::ERROR );
        }

        $season = isset( $post['season'] ) ? sanitize_text_field( wp_unslash( strval( $post['season'] ) ) ) : ( isset( $query['season'] ) ? sanitize_text_field( wp_unslash( strval( $query['season'] ) ) ) : null );

        $command = Generate_Tournament_Bracket_Command::from_post( $post, $league_id, 0, $season );
        $handler = $this->generate_tournament_bracket_handler ?? new Generate_Tournament_Bracket_Handler( $this->championship_admin_service );
        return $handler->handle( $command );
    }

    private function sanitize_form_data( array $post ): array {
        $sanitized = array();
        foreach ( $post as $key => $value ) {
            if ( is_string( $value ) ) {
                $sanitized[ $key ] = sanitize_text_field( wp_unslash( $value ) );
            } elseif ( is_array( $value ) ) {
                $sanitized[ $key ] = $value;
            }
        }
        return $sanitized;
    }
}
