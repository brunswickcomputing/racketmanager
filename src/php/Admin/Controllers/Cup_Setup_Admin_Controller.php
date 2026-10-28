<?php
declare( strict_types=1 );
/**
 * Cup Setup Admin Controller
 *
 * Handles display and actions for cup competition round setup view.
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
use Racketmanager\Exceptions\Season_Not_Found_Exception;
use Racketmanager\Services\Admin\Cup\Cup_Setup_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Util\Util;

final readonly class Cup_Setup_Admin_Controller {

    public function __construct(
        private Competition_Service $competition_service,
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
     * @throws Season_Not_Found_Exception
     */
    public function setup_page( array $query, array $post ): array {
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

        $competition_id = isset( $query['competition_id'] ) ? intval( $query['competition_id'] ) : null;
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

        $season_data = $competition->get_season_by_name( (string) $season );
        if ( ! $season_data ) {
            throw new Season_Not_Found_Exception( __( 'Season not found', 'racketmanager' ) );
        }

        $match_dates = $season_data['match_dates'] ?? array();
        if ( empty( $match_dates ) ) {
            $date_end     = date_create( $season_data['date_end'] ?? '' );
            $day_end      = $date_end ? date_format( $date_end, 'N' ) : 1;
            $day_adjust   = (int) $day_end - 1;
            $end_date     = Util::amend_date( (string) ( $season_data['date_end'] ?? '' ), $day_adjust, '-' );
            $round_length = $season_data['round_length'] ?? 7;
            $match_date   = null;
            $i            = 0;
            $finals       = $competition->finals ?? array();
            foreach ( $finals as $final ) {
                $r = $final['round'] - 1;
                if ( 0 === $i ) {
                    $match_date = $season_data['date_end'] ?? '';
                } elseif ( 1 === $i ) {
                    if ( ! empty( $competition->settings['fixed_match_dates'] ) ) {
                        $match_date = Util::amend_date( (string) $end_date, (int) $round_length, '-' );
                    } else {
                        $match_date = Util::amend_date( (string) ( $season_data['date_end'] ?? '' ), 7 );
                    }
                } elseif ( 0 === $r && ! empty( $competition->settings['fixed_match_dates'] ) ) {
                    $match_date = $season_data['date_start'] ?? '';
                } else {
                    $match_date = Util::amend_date( (string) $match_date, (int) $round_length, '-' );
                }
                $match_dates[ $r ] = $match_date;
                ++$i;
            }
        }

        $vm = new Setup_Page_View_Model(
            competition: $competition,
            season: (string) $season,
            current_season: $season_data,
            event: null,
            league: null,
            match_count: 0,
            match_dates: $match_dates,
        );

        return array(
            'view_model' => $vm,
        );
    }
}
