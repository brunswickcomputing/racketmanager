<?php
declare( strict_types=1 );

namespace Racketmanager\Services\Result;

use Racketmanager\Domain\Results_Checker;
use Racketmanager\Domain\Enums\Results_Checker_Status;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Results_Checker_Repository_Interface;
use Racketmanager\Services\Fixture\Fixture_Result_Manager;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Domain\Result\Result;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Services\Notification\Notification_Service;

/**
 * Manager for Result Checker workflows
 */
class Results_Checker_Manager {
    private ?Notification_Service $notification_service = null;

    /**
     * @param Results_Checker_Repository_Interface $repository        Repository.
     * @param Fixture_Result_Manager              $result_manager    Result manager.
     * @param League_Repository_Interface         $league_repository League repository.
     * @param Fixture_Repository_Interface        $fixture_repository Fixture repository.
     */
    public function __construct(
        private readonly Results_Checker_Repository_Interface $repository,
        private readonly Fixture_Result_Manager $result_manager,
        private readonly League_Repository_Interface $league_repository,
        private readonly Fixture_Repository_Interface $fixture_repository
    ) {}

    /**
     * Set the notification service.
     *
     * @param Notification_Service $notification_service Notification service.
     */
    public function set_notification_service( Notification_Service $notification_service ): void {
        $this->notification_service = $notification_service;
    }

    /**
     * Approve a result checker entry
     *
     * @param Results_Checker $checker Entity.
     */
    public function approve( Results_Checker $checker ): void {
        $checker->status = Results_Checker_Status::APPROVED->value;
        $checker->updated_user = get_current_user_id();
        $this->repository->save( $checker );
    }

    /**
     * Handle a result checker entry (apply penalties)
     *
     * @param Results_Checker $checker Entity.
     */
    public function handle( Results_Checker $checker ): void {
        if ( empty( $checker->player_id ) ) {
            $this->handle_match_error( $checker );
        } else {
            $this->handle_player_error( $checker );
        }
    }

    /**
     * Handle match error (late results)
     *
     * @param Results_Checker $checker Entity.
     */
    private function handle_match_error( Results_Checker $checker ): void {
        $league = $this->league_repository->find_by_id( $checker->league_id );
        if ( ! $league ) {
            return;
        }

        $penalty = (float) ( $league->point_rules['result_late'] ?? $league->point_rules['late_result'] ?? 0 );
        $match   = $this->fixture_repository->find_by_id( $checker->match_id );
        if ( ! $match instanceof Fixture ) {
            return;
        }

        if ( $penalty > 0 ) {
            $this->result_manager->apply_penalty( $match, 'home', (int) $penalty );

            $result = new Result(
                home_points: (float) $match->get_home_points(),
                away_points: (float) $match->get_away_points(),
                winner_id: $match->get_winner_id(),
                loser_id: $match->get_loser_id(),
                status: $match->get_status(),
                custom: $match->get_custom()
            );
            $this->result_manager->update_result( $match, $result, $match->get_confirmed() );

            $this->notification_service?->send_result_error_notification( $checker, $match, $penalty );
        }

        $checker->status       = Results_Checker_Status::HANDLED->value;
        $checker->updated_user = get_current_user_id();
        $this->repository->save( $checker );
    }

    /**
     * Handle player error (invalid players)
     *
     * @param Results_Checker $checker Entity.
     */
    private function handle_player_error( Results_Checker $checker ): void {
        $league = $this->league_repository->find_by_id( $checker->league_id );
        if ( ! $league ) {
            return;
        }

        $penalty = (float) ( $league->point_rules['player_error'] ?? 0 );
        $match   = $this->fixture_repository->find_by_id( $checker->match_id );
        if ( ! $match instanceof Fixture ) {
            return;
        }

        if ( $penalty > 0 ) {
            $comment = sprintf(
                /* translators: %s penalty points */
                __( 'Points deducted: %s', 'racketmanager' ),
                $penalty
            );

            $this->result_manager->apply_penalty( $match, 'home', (int) $penalty );

            $comments = $match->get_comments();
            if ( ! is_array( $comments ) ) {
                $comments = maybe_unserialize( $comments );
            }
            if ( ! is_array( $comments ) ) {
                $comments = array( 'result' => $comment );
            } elseif ( empty( $comments['result'] ) ) {
                $comments['result'] = $comment;
            } else {
                $comments['result'] .= "\n" . $comment;
            }

            $match->set_comments( maybe_serialize( $comments ) );

            $result = new Result(
                home_points: (float) $match->get_home_points(),
                away_points: (float) $match->get_away_points(),
                winner_id: $match->get_winner_id(),
                loser_id: $match->get_loser_id(),
                status: $match->get_status(),
                custom: $match->get_custom()
            );
            $this->result_manager->update_result( $match, $result, $match->get_confirmed() );

            $this->notification_service?->send_result_error_notification( $checker, $match, $penalty );
        }

        $checker->status       = Results_Checker_Status::HANDLED->value;
        $checker->updated_user = get_current_user_id();
        $this->repository->save( $checker );
    }

}
