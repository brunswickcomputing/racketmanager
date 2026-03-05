<?php
/**
 * Tournament Contact Admin Controller
 *
 * @package RacketManager
 * @subpackage Admin/Controllers
 */

namespace Racketmanager\Admin\Controllers;

use Racketmanager\Admin\Presenters\Admin_Message_Mapper;
use Racketmanager\Admin\View_Models\Tournament_Contact_Page_View_Model;
use Racketmanager\Domain\DTO\Admin\Tournament_Contact_Action_Result_DTO;
use Racketmanager\Exceptions\Tournament_Not_Found_Exception;
use Racketmanager\RacketManager;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Admin\Tournament\Tournament_Contact_Action_Dispatcher;
use Racketmanager\Services\Tournament_Service;

readonly final class Tournament_Contact_Admin_Controller {

    public function __construct(
        private RacketManager $racketmanager,
        private Tournament_Service $tournament_service,
        private Tournament_Contact_Action_Dispatcher $dispatcher,
        private Action_Guard_Interface $action_guard,
    ) {
    }

    /**
     * Controller for admin.php?page=racketmanager-tournaments&view=contact
     *
     * @param array $query Typically $_GET
     * @param array $post  Typically $_POST
     * @return array{view_model?:Tournament_Contact_Page_View_Model, redirect?:string, message?:string, message_type?:bool|string}
     *
     * @throws Tournament_Not_Found_Exception
     */
    public function contact_page( array $query, array $post ): array {
        $this->action_guard->assert_capability( 'edit_teams' );

        $tournament_id = isset( $query['tournament_id'] )
            ? intval( $query['tournament_id'] )
            : ( isset( $post['tournament_id'] ) ? intval( $post['tournament_id'] ) : null );

        $is_post = ( 'POST' === strtoupper( strval( $_SERVER['REQUEST_METHOD'] ?? '' ) ) );

        if ( $is_post ) {
            $action_result = $this->dispatcher->handle( $tournament_id, $post );

            if ( Tournament_Contact_Action_Result_DTO::INTENT_PREVIEW === $action_result->intent ) {
                return array(
                    'view_model' => $this->build_view_model( $tournament_id, $post, 'preview' ),
                );
            }

            if (
                Tournament_Contact_Action_Result_DTO::INTENT_SEND === $action_result->intent
                || Tournament_Contact_Action_Result_DTO::INTENT_SEND_ACTIVE === $action_result->intent
            ) {
                $result = array(
                    'redirect' => add_query_arg(
                        array(
                            'page'          => 'racketmanager-tournaments',
                            'view'          => 'contact',
                            'tournament_id' => $tournament_id,
                        ),
                        admin_url( 'admin.php' )
                    ),
                );

                if ( null !== $action_result->message ) {
                    $result['message'] = $action_result->message;
                    $result['message_type'] = null !== $action_result->message_type
                        ? Admin_Message_Mapper::to_legacy( $action_result->message_type )
                        : false;
                }

                return $result;
            }
        }

        return array(
            'view_model' => $this->build_view_model( $tournament_id, $query, 'compose' ),
        );
    }

    /**
     * @throws Tournament_Not_Found_Exception
     */
    private function build_view_model( ?int $tournament_id, array $source, string $tab ): Tournament_Contact_Page_View_Model {
        $tournament = $this->tournament_service->get_tournament( $tournament_id );

        $season = isset( $source['season'] )
            ? sanitize_text_field( wp_unslash( strval( $source['season'] ) ) )
            : strval( $tournament->get_season() );

        $email_title = isset( $source['contactTitle'] )
            ? sanitize_text_field( wp_unslash( strval( $source['contactTitle'] ) ) )
            : '';

        $email_intro = isset( $source['contactIntro'] )
            ? sanitize_textarea_field( wp_unslash( strval( $source['contactIntro'] ) ) )
            : '';

        $email_body = array();
        if ( isset( $source['contactBody'] ) && is_array( $source['contactBody'] ) ) {
            foreach ( $source['contactBody'] as $key => $paragraph ) {
                $email_body[ $key ] = is_scalar( $paragraph ) ? wp_unslash( strval( $paragraph ) ) : '';
            }
        }

        $email_close = isset( $source['contactClose'] )
            ? sanitize_textarea_field( wp_unslash( strval( $source['contactClose'] ) ) )
            : '';

        $email_message = '';
        if ( 'preview' === $tab ) {
            $email_subject = $this->racketmanager->site_name . ' - ' . $tournament->name . ' ' . $season . ' - Important Message';
            $email_message = strval(
                $this->racketmanager->shortcodes->load_template(
                    'contact-teams',
                    array(
                        'tournament'    => $tournament,
                        'organisation'  => $this->racketmanager->site_name,
                        'season'        => $season,
                        'title_text'    => $email_title,
                        'intro'         => $email_intro,
                        'body'          => $email_body,
                        'closing_text'  => $email_close,
                        'email_subject' => $email_subject,
                    ),
                    'email'
                )
            );
        }

        return new Tournament_Contact_Page_View_Model(
            tournament: $tournament,
            object_name: 'tournament_id',
            object_id: $tournament->id,
            season: $season,
            tab: $tab,
            email_title: $email_title,
            email_intro: $email_intro,
            email_body: $email_body,
            email_close: $email_close,
            email_message: $email_message,
        );
    }
}
