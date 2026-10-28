<?php
declare( strict_types=1 );
/**
 * Cup Setup Event Admin Controller
 *
 * Handles display and actions for cup event round setup view.
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Racketmanager\Admin\Presenters\Admin_Message_Mapper;
use Racketmanager\Admin\View_Models\Setup_Page_View_Model;
use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\League_Not_Found_Exception;
use Racketmanager\Exceptions\Season_Not_Found_Exception;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Services\Admin\Cup\Cup_Setup_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Competition\League_Query_Service;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Util\Util;

final readonly class Cup_Setup_Event_Admin_Controller {

    public function __construct(
        private Competition_Service $competition_service,
        private League_Repository_Interface $league_repository,
        private League_Query_Service $league_query_service,
        private Action_Guard_Interface $action_guard,
        private Cup_Setup_Action_Dispatcher $cup_setup_dispatcher,
    ) {
    }

    /**
     * @param array<string, mixed> $query Typically $_GET
     * @param array<string, mixed> $post  Typically $_POST
     *
     * @return array{view_model?: Setup_Page_View_Model, redirect?: string, message?: string, message_type?: bool|string, flash_data?: array<string, mixed>}
     *
     * @throws Competition_Not_Found_Exception
     * @throws League_Not_Found_Exception
     * @throws Season_Not_Found_Exception
     */
    public function setup_event_page( array $query, array $post ): array {
        $this->action_guard->assert_capability( 'edit_matches' );

        if ( isset( $post['action'] ) || isset( $post['rank'] ) ) {
            try {
                $result = $this->cup_setup_dispatcher->handle( $query, $post );
            } catch ( Invalid_Status_Exception $e ) {
                $result = new Action_Result_DTO( $e->getMessage(), Admin_Message_Type::ERROR );
            }

            $extra_args = array();
            if ( null !== $result->tab_override && '' !== $result->tab_override ) {
                $extra_args['tab'] = $result->tab_override;
            }
            $extra_args = array_merge( $extra_args, $result->query_params );
            $redirect   = Admin_Redirect_Url_Builder::cup_screen( $query, $extra_args );

            return array(
                'redirect'     => $redirect,
                'message'      => $result->message,
                'message_type' => Admin_Message_Mapper::to_legacy( $result->message_type ),
                'flash_data'   => $result->flash_data,
            );
        }

        $league_id = isset( $query['league'] ) ? intval( $query['league'] ) : ( isset( $query['league_id'] ) ? intval( $query['league_id'] ) : ( isset( $post['league_id'] ) ? intval( $post['league_id'] ) : null ) );
        if ( ! $league_id ) {
            throw new League_Not_Found_Exception( __( 'League not found', 'racketmanager' ) );
        }

        $league = $this->league_repository->find_by_id( (int) $league_id );
        if ( ! $league ) {
            throw new League_Not_Found_Exception( __( 'League not found', 'racketmanager' ) );
        }

        $competition_id = isset( $query['competition_id'] ) ? intval( $query['competition_id'] ) : ( isset( $league->event?->competition?->id ) ? (int) $league->event->competition->id : null );
        if ( ! $competition_id ) {
            throw new Competition_Not_Found_Exception( __( 'Competition not found', 'racketmanager' ) );
        }

        $competition = $this->competition_service->get_by_id( $competition_id );
        if ( ! $competition ) {
            throw new Competition_Not_Found_Exception( __( 'Competition not found', 'racketmanager' ) );
        }

        $season = isset( $query['season'] ) ? intval( $query['season'] ) : null;
        if ( ! $season ) {
            throw new Season_Not_Found_Exception( __( 'Season is required', 'racketmanager' ) );
        }

        $competition_season = $competition->get_season_by_name( (string) $season );
        if ( ! $competition_season ) {
            throw new Season_Not_Found_Exception( __( 'Season not found', 'racketmanager' ) );
        }

        $match_count = (int) $this->league_query_service->get_matches(
            $league,
            array(
                'count' => true,
                'final' => 'all',
            )
        );

        $league_season = $league->seasons[ $season ] ?? array();
        $event_season  = isset( $league->event ) && method_exists( $league->event, 'get_season_by_name' )
            ? $league->event->get_season_by_name( (string) $season )
            : null;
        $match_dates   = array();
        if ( empty( $league_season['rounds'] ) ) {
            if ( empty( $event_season['match_dates'] ) ) {
                if ( empty( $league->event->offset ) ) {
                    $match_dates = $competition_season['match_dates'] ?? array();
                } elseif ( isset( $competition_season['match_dates'] ) && is_array( $competition_season['match_dates'] ) ) {
                    $i               = 0;
                    $num_match_dates = count( $competition_season['match_dates'] );
                    foreach ( $competition_season['match_dates'] as $match_date ) {
                        if ( $i === $num_match_dates - 1 ) {
                            $match_dates[ $i ] = $match_date;
                        } else {
                            $match_dates[ $i ] = Util::amend_date( (string) $match_date, (int) $league->event->offset, '+', 'week' );
                        }
                        ++$i;
                    }
                }
            } else {
                $match_dates = $event_season['match_dates'];
            }
        } else {
            foreach ( array_reverse( $league_season['rounds'] ) as $round ) {
                $match_dates[] = $round->date;
            }
        }

        $vm = new Setup_Page_View_Model(
            competition: $competition,
            season: (string) $season,
            current_season: $league_season,
            event: $league->event ?? null,
            league: $league,
            match_count: $match_count,
            match_dates: $match_dates,
        );

        return array(
            'view_model' => $vm,
        );
    }
}
