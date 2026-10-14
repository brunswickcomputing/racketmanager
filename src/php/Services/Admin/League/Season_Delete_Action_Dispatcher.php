<?php
/**
 * Season delete action dispatcher
 *
 * @package RacketManager
 * @subpackage Services/Admin/League
 */

declare( strict_types=1 );

namespace Racketmanager\Services\Admin\League;

use Racketmanager\Domain\DTO\Admin\Action_Result_DTO;
use Racketmanager\Domain\DTO\Admin\Admin_Message_Type;
use Racketmanager\Exceptions\Competition_Not_Found_Exception;
use Racketmanager\Exceptions\Competition_Not_Updated_Exception;
use Racketmanager\Exceptions\Invalid_Status_Exception;
use Racketmanager\Services\Admin\Security\Action_Guard_Interface;
use Racketmanager\Services\Competition_Service;

/**
 * Handles bulk season deletion from the competition seasons admin screen.
 */
readonly final class Season_Delete_Action_Dispatcher {

    public function __construct(
        private Competition_Service $competition_service,
        private Action_Guard_Interface $action_guard,
    ) {
    }

    /**
     * @param array<string, mixed> $query Typically $_GET
     * @param array<string, mixed> $post  Typically $_POST
     */
    public function handle( array $query, array $post ): Action_Result_DTO {
        if ( ! isset( $post['doActionSeason'] ) ) {
            return new Action_Result_DTO();
        }

        $competition_id_query = isset( $query['competition_id'] ) ? intval( $query['competition_id'] ) : null;
        $competition_id_post  = isset( $post['competition_id'] ) ? intval( $post['competition_id'] ) : null;
        $compare_error        = $this->compare_competition_ids( $competition_id_post, $competition_id_query );
        if ( null !== $compare_error ) {
            return new Action_Result_DTO(
                message: $compare_error,
                message_type: Admin_Message_Type::ERROR,
            );
        }

        try {
            $this->action_guard->assert_allowed( 'racketmanager_nonce', 'seasons-bulk', 'del_matches' );
        } catch ( Invalid_Status_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }

        if ( ! isset( $post['action'] ) || 'delete' !== $post['action'] ) {
            return new Action_Result_DTO();
        }

        $seasons = isset( $post['del_season'] ) ? array_map( 'intval', (array) $post['del_season'] ) : array();

        try {
            $this->competition_service->delete_seasons( $competition_id_query, $seasons );

            return new Action_Result_DTO(
                message: __( 'Season(s) deleted', 'racketmanager' ),
                message_type: Admin_Message_Type::SUCCESS,
            );
        } catch ( Competition_Not_Updated_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::WARNING,
            );
        } catch ( Competition_Not_Found_Exception $e ) {
            return new Action_Result_DTO(
                message: $e->getMessage(),
                message_type: Admin_Message_Type::ERROR,
            );
        }
    }

    private function compare_competition_ids( ?int $passed, ?int $original ): ?string {
        if ( empty( $passed ) ) {
            return __( 'New value not found', 'racketmanager' );
        }
        if ( empty( $original ) ) {
            return __( 'Original value not found', 'racketmanager' );
        }
        if ( $passed !== $original ) {
            return __( 'Passed values do not match', 'racketmanager' );
        }

        return null;
    }
}
