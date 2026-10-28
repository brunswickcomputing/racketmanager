<?php
declare( strict_types=1 );
/**
 * Contact Admin Controller
 *
 * Handles the contact screen (view=contact) across tournaments, leagues, and competitions.
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Racketmanager\Admin\Presenters\Admin_Message_Mapper;
use Racketmanager\Admin\View_Models\Contact_Page_View_Model;
use Racketmanager\Domain\Competition\Competition;
use Racketmanager\Domain\Competition\League;
use Racketmanager\Domain\DTO\Admin\Tournament_Contact_Action_Result_DTO;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\Infrastructure\Wordpress\Request\Tournament\Tournament_Contact_Request_DTO;
use Racketmanager\Services\Admin\League\League_Contact_Action_Dispatcher;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Admin\Tournament\Tournament_Contact_Action_Dispatcher;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\League_Service;
use Racketmanager\Services\Tournament_Service;

final readonly class Contact_Admin_Controller {

    public function __construct(
        private Tournament_Service $tournament_service,
        private League_Service $league_service,
        private Competition_Service $competition_service,
        private Tournament_Contact_Action_Dispatcher $tournament_dispatcher,
        private League_Contact_Action_Dispatcher $league_dispatcher,
        private Action_Guard_Interface $action_guard,
    ) {
    }

    /**
     * @param array<string, mixed> $query   Typically $_GET
     * @param array<string, mixed> $post    Typically $_POST
     * @param Admin_Page_Context   $context Context factory (tournament, league, cup).
     *
     * @return array{view_model?:Contact_Page_View_Model, redirect?:string, message?:string, message_type?:bool|string}
     *
     * @throws Invalid_Status_Exception
     * @throws Tournament_Not_Found_Exception
     */
    public function contact_page( array $query, array $post, Admin_Page_Context $context ): array {
        $this->action_guard->assert_capability( 'edit_teams' );

        $request_method = isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '';
        $is_post        = 'POST' === strtoupper( $request_method );

        if ( 'tournament' === $context->target_type || 'racketmanager-tournaments' === $context->page ) {
            $tournament_id = $this->extract_tournament_id( $query, $post );
            if ( ! $tournament_id ) {
                throw new Tournament_Not_Found_Exception( __( 'Tournament not found', 'racketmanager' ) );
            }

            if ( $is_post ) {
                return $this->handle_tournament_post( $tournament_id, $query, $post, $context );
            }

            return array(
                'view_model' => $this->build_tournament_view_model( $tournament_id, $query, 'compose' ),
            );
        }

        if ( isset( $query['league_id'] ) || isset( $post['league_id'] ) ) {
            $league_id = intval( $query['league_id'] ?? $post['league_id'] );
            if ( ! $league_id ) {
                throw new Invalid_Status_Exception( __( 'League not found', 'racketmanager' ) );
            }

            if ( $is_post ) {
                return $this->handle_league_post( $league_id, $query, $post, $context );
            }

            return array(
                'view_model' => $this->build_league_view_model( $league_id, $query, 'compose' ),
            );
        }

        if ( isset( $query['competition_id'] ) || isset( $post['competition_id'] ) ) {
            $competition_id = intval( $query['competition_id'] ?? $post['competition_id'] );
            if ( ! $competition_id ) {
                throw new Invalid_Status_Exception( __( 'Competition not found', 'racketmanager' ) );
            }

            if ( $is_post ) {
                return $this->handle_competition_post( $competition_id, $query, $post, $context );
            }

            return array(
                'view_model' => $this->build_competition_view_model( $competition_id, $query, 'compose' ),
            );
        }

        throw new Invalid_Status_Exception( __( 'League not found', 'racketmanager' ) );
    }

    private function extract_tournament_id( array $query, array $post ): ?int {
        if ( isset( $query['tournament'] ) ) {
            return intval( $query['tournament'] );
        }
        if ( isset( $query['tournament_id'] ) ) {
            return intval( $query['tournament_id'] );
        }
        if ( isset( $post['tournament_id'] ) ) {
            return intval( $post['tournament_id'] );
        }
        if ( isset( $post['tournament'] ) ) {
            return intval( $post['tournament'] );
        }
        return null;
    }

    private function handle_tournament_post( int $tournament_id, array $query, array $post, Admin_Page_Context $context ): array {
        $action_result = $this->tournament_dispatcher->handle( $tournament_id, $post );

        if ( Tournament_Contact_Action_Result_DTO::INTENT_PREVIEW === $action_result->intent ) {
            return array(
                'view_model' => $this->build_tournament_view_model( $tournament_id, $post, 'preview' ),
            );
        }

        if ( $this->is_send_intent( $action_result->intent ) ) {
            $season = isset( $post['season'] ) ? sanitize_text_field( wp_unslash( strval( $post['season'] ) ) ) : null;
            $redirect = Admin_Redirect_Url_Builder::contact_view( $query, $post, $context, $tournament_id, 'tournament_id', $season );

            $result = array( 'redirect' => $redirect );
            if ( null !== $action_result->message ) {
                $result['message']      = $action_result->message;
                $result['message_type'] = null !== $action_result->message_type
                    ? Admin_Message_Mapper::to_legacy( $action_result->message_type )
                    : false;
            }
            return $result;
        }

        return array(
            'view_model' => $this->build_tournament_view_model( $tournament_id, $post, 'compose' ),
        );
    }

    private function is_send_intent( string $intent ): bool {
        return Tournament_Contact_Action_Result_DTO::INTENT_SEND === $intent
            || Tournament_Contact_Action_Result_DTO::INTENT_SEND_ACTIVE === $intent;
    }

    private function handle_league_post( int $league_id, array $query, array $post, Admin_Page_Context $context ): array {
        if ( isset( $post['contactTeamPreview'] ) ) {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_contact-teams', 'edit_teams' );

            return array(
                'view_model' => $this->build_league_view_model( $league_id, $post, 'preview' ),
            );
        }

        if ( isset( $post['contactTeam'] ) ) {
            $action_result = $this->league_dispatcher->handle( $post );
            $season        = isset( $post['season'] ) ? sanitize_text_field( wp_unslash( strval( $post['season'] ) ) ) : null;
            $redirect      = Admin_Redirect_Url_Builder::contact_view( $query, $post, $context, $league_id, 'league_id', $season );

            $result = array( 'redirect' => $redirect );
            if ( null !== $action_result->message ) {
                $result['message']      = $action_result->message;
                $result['message_type'] = null !== $action_result->message_type
                    ? Admin_Message_Mapper::to_legacy( $action_result->message_type )
                    : false;
            }
            return $result;
        }

        return array(
            'view_model' => $this->build_league_view_model( $league_id, $post, 'compose' ),
        );
    }

    private function handle_competition_post( int $competition_id, array $query, array $post, Admin_Page_Context $context ): array {
        if ( isset( $post['contactTeamPreview'] ) ) {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'racketmanager_contact-teams', 'edit_teams' );

            return array(
                'view_model' => $this->build_competition_view_model( $competition_id, $post, 'preview' ),
            );
        }

        if ( isset( $post['contactTeam'] ) ) {
            $action_result = $this->league_dispatcher->handle( $post );
            $season        = isset( $post['season'] ) ? sanitize_text_field( wp_unslash( strval( $post['season'] ) ) ) : null;
            $redirect      = Admin_Redirect_Url_Builder::contact_view( $query, $post, $context, $competition_id, 'competition_id', $season );

            $result = array( 'redirect' => $redirect );
            if ( null !== $action_result->message ) {
                $result['message']      = $action_result->message;
                $result['message_type'] = null !== $action_result->message_type
                    ? Admin_Message_Mapper::to_legacy( $action_result->message_type )
                    : false;
            }
            return $result;
        }

        return array(
            'view_model' => $this->build_competition_view_model( $competition_id, $post, 'compose' ),
        );
    }

    /**
     * @throws Tournament_Not_Found_Exception
     */
    private function build_tournament_view_model( ?int $tournament_id, array $source, string $tab ): Contact_Page_View_Model {
        $tournament = $this->tournament_service->get_tournament( $tournament_id );
        if ( ! $tournament ) {
            throw new Tournament_Not_Found_Exception( __( 'Tournament not found', 'racketmanager' ) );
        }
        $request = new Tournament_Contact_Request_DTO( $source );

        $tournament_season = method_exists( $tournament, 'get_season' ) ? $tournament->get_season() : null;
        $season            = $request->season ?? ( null !== $tournament_season ? strval( $tournament_season ) : null );

        $email_message = '';
        if ( 'preview' === $tab ) {
            $email_message = $this->tournament_service->get_contact_preview(
                $tournament_id,
                strval( $season ?? '' ),
                $request->email_title,
                $request->email_intro,
                $request->email_body,
                $request->email_close
            );
        }

        return new Contact_Page_View_Model(
            tournament: $tournament,
            object_name: 'tournament_id',
            object_id: (int) $tournament->id,
            season: $season,
            tab: $tab,
            email_title: $request->email_title,
            email_intro: $request->email_intro,
            email_body: $request->email_body,
            email_close: $request->email_close,
            email_message: $email_message,
        );
    }

    /**
     * @throws Invalid_Status_Exception
     */
    private function build_league_view_model( int $league_id, array $source, string $tab ): Contact_Page_View_Model {
        $league = $this->league_service->get_league( $league_id );
        if ( ! $league instanceof League ) {
            throw new Invalid_Status_Exception( __( 'League not found', 'racketmanager' ) );
        }
        $request = new Tournament_Contact_Request_DTO( $source );

        $league_season = method_exists( $league, 'get_season' ) ? $league->get_season() : ( $league->current_season['name'] ?? null );
        $season        = $request->season ?? ( null !== $league_season ? strval( $league_season ) : null );

        $email_message = '';
        if ( 'preview' === $tab ) {
            $email_message = $this->league_service->get_contact_preview(
                $league,
                strval( $season ?? '' ),
                $request->email_title,
                $request->email_intro,
                $request->email_body,
                $request->email_close
            );
        }

        return new Contact_Page_View_Model(
            league: $league,
            object_name: 'league_id',
            object_id: (int) $league->id,
            season: $season,
            tab: $tab,
            email_title: $request->email_title,
            email_intro: $request->email_intro,
            email_body: $request->email_body,
            email_close: $request->email_close,
            email_message: $email_message,
        );
    }

    /**
     * @throws Invalid_Status_Exception
     */
    private function build_competition_view_model( int $competition_id, array $source, string $tab ): Contact_Page_View_Model {
        $competition = $this->competition_service->get_competition( $competition_id );
        if ( ! $competition instanceof Competition ) {
            throw new Invalid_Status_Exception( __( 'Competition not found', 'racketmanager' ) );
        }
        $request = new Tournament_Contact_Request_DTO( $source );

        $competition_season = method_exists( $competition, 'get_season' ) ? $competition->get_season() : ( $competition->current_season['name'] ?? null );
        $season             = $request->season ?? ( null !== $competition_season ? strval( $competition_season ) : null );

        $email_message = '';
        if ( 'preview' === $tab ) {
            $email_message = $this->competition_service->get_contact_preview(
                $competition,
                strval( $season ?? '' ),
                $request->email_title,
                $request->email_intro,
                $request->email_body,
                $request->email_close
            );
        }

        return new Contact_Page_View_Model(
            competition: $competition,
            object_name: 'competition_id',
            object_id: (int) $competition->id,
            season: $season,
            tab: $tab,
            email_title: $request->email_title,
            email_intro: $request->email_intro,
            email_body: $request->email_body,
            email_close: $request->email_close,
            email_message: $email_message,
        );
    }
}
