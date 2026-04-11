<?php
declare( strict_types=1 );

namespace Racketmanager\Presentation\Presenters;

use Racketmanager\Domain\Enums\Results_Checker_Status;
use Racketmanager\Domain\Fixture\Fixture;
use Racketmanager\Domain\Results_Checker;
use Racketmanager\Presentation\View_Models\Results_Checker_View_Model;
use Racketmanager\Repositories\Interfaces\Fixture_Repository_Interface;
use Racketmanager\Repositories\Interfaces\League_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Player_Repository_Interface;
use Racketmanager\Repositories\Interfaces\Team_Repository_Interface;
use Racketmanager\Services\Fixture\Fixture_Detail_Service;
use function Racketmanager\seo_url;

/**
 * Presenter for Results Checker
 */
readonly class Results_Checker_Presenter {
    private const string RESULT_SUFFIX = 'result/';

    /**
     * @param Fixture_Repository_Interface $fixture_repository Fixture repository.
     * @param Player_Repository_Interface $player_repository Player repository.
     * @param Team_Repository_Interface $team_repository Team repository.
     * @param League_Repository_Interface $league_repository League repository.
     * @param Fixture_Detail_Service $fixture_detail_service Fixture detail service.
     */
    public function __construct(
        private Fixture_Repository_Interface $fixture_repository, private Player_Repository_Interface $player_repository, private Team_Repository_Interface $team_repository, private League_Repository_Interface $league_repository, private Fixture_Detail_Service $fixture_detail_service
    ) {
    }

    /**
     * Map a collection of entities
     *
     * @param Results_Checker[] $checkers Entities.
     * @param string $current_filter Current filter.
     *
     * @return Results_Checker_View_Model[]
     */
    public function present_collection( array $checkers, string $current_filter ): array {
        return array_map( fn( Results_Checker $checker ) => $this->present( $checker, $current_filter ), $checkers );
    }

    /**
     * Map Results_Checker entity to View Model
     *
     * @param Results_Checker $checker Entity.
     * @param string $current_filter Current filter.
     *
     * @return Results_Checker_View_Model
     */
    public function present( Results_Checker $checker, string $current_filter ): Results_Checker_View_Model {
        // Hydrate only when needed for presentation
        $match  = $this->fixture_repository->find_by_id( $checker->match_id );
        $player = $checker->player_id ? $this->player_repository->find( $checker->player_id ) : null;
        $team   = $this->team_repository->find_by_id( $checker->team_id );

        return new Results_Checker_View_Model( id: $checker->id, formatted_date: $this->get_formatted_date( $match->date ?? '' ), match_link: $this->get_match_link( $match ), match_title: $this->get_match_title( $match ), team_title: $team->title ?? '', player_link: $this->get_player_link( $player->display_name ?? '', $team->club->shortcode ?? '' ), player_name: $player->display_name ?? '', description: $checker->description ?? '', status_desc: $this->get_status_description( $checker->status ), tooltip: $this->get_tooltip( $checker->updated_user, $checker->updated_date ), show_status: 'outstanding' !== $current_filter );
    }

    /**
     * Format match date
     *
     * @param string|null $raw_date Raw date.
     *
     * @return string
     */
    private function get_formatted_date( ?string $raw_date ): string {
        if ( empty( $raw_date ) ) {
            return '';
        }

        return ( function_exists( 'mysql2date' ) ) ? mysql2date( 'Y-m-d', $raw_date ) : $raw_date;
    }

    /**
     * Get the match link
     *
     * @param Fixture|null $match Fixture.
     *
     * @return string
     */
    private function get_match_link( ?Fixture $match ): string {
        if ( ! $match ) {
            return '';
        }

        $details = $this->fixture_detail_service->get_fixture_with_details( $match );
        if ( ! $details ) {
            return ( $match->link ?? '' ) . self::RESULT_SUFFIX;
        }

        return $details->link . self::RESULT_SUFFIX;
    }

    /**
     * Get match title
     *
     * @param Fixture|null $match Fixture.
     *
     * @return string
     */
    private function get_match_title( ?Fixture $match ): string {
        if ( ! $match instanceof Fixture ) {
            return '';
        }

        if ( ! empty( $match->match_title ) ) {
            return $match->match_title;
        }

        return $this->generate_match_title( $match );
    }

    /**
     * Generate match title from teams
     *
     * @param Fixture $match Match.
     *
     * @return string
     */
    private function generate_match_title( Fixture $match ): string {
        $league = ! empty( $match->league_id ) ? $this->league_repository->find_by_id( $match->league_id ) : null;
        if ( ! $league ) {
            return '';
        }

        $home_team_name = $this->fixture_detail_service->get_team_name_or_placeholder( $match->home_team, $match->season ?? '', $league, $match->final );

        $away_team_name = $this->fixture_detail_service->get_team_name_or_placeholder( $match->away_team, $match->season ?? '', $league, $match->final );

        return ( $home_team_name && $away_team_name ) ? sprintf( '%s vs %s', $home_team_name, $away_team_name ) : '';
    }

    /**
     * Get player link
     *
     * @param string $player_name Player name.
     * @param string $shortcode Club shortcode.
     *
     * @return string
     */
    private function get_player_link( string $player_name, string $shortcode ): string {
        if ( empty( $player_name ) || empty( $shortcode ) ) {
            return '';
        }

        return $this->format_player_link( $player_name, $shortcode );
    }

    /**
     * Format player link
     *
     * @param string $player_name Player name.
     * @param string $shortcode Club shortcode.
     *
     * @return string
     */
    private function format_player_link( string $player_name, string $shortcode ): string {
        if ( function_exists( 'Racketmanager\Util\seo_url' ) ) {
            return '/clubs/' . seo_url( $shortcode ) . '/players/' . seo_url( $player_name ) . '/';
        }

        $formatted_shortcode = str_replace( ' ', '-', strtolower( $shortcode ) );
        $formatted_player    = str_replace( ' ', '-', strtolower( $player_name ) );

        return "/clubs/$formatted_shortcode/players/$formatted_player/";
    }

    /**
     * Get status description
     *
     * @param int|null $status Status code.
     *
     * @return string
     */
    private function get_status_description( ?int $status ): string {
        $description = match ( $status ) {
            Results_Checker_Status::APPROVED->value => 'Approved',
            Results_Checker_Status::HANDLED->value => 'Handled',
            default => '',
        };

        return ( '' !== $description && function_exists( '__' ) ) ? __( $description, 'racketmanager' ) : $description;
    }

    /**
     * Get tooltip
     *
     * @param int|null $updated_user User ID.
     * @param string|null $updated_date Updated date.
     *
     * @return string
     */
    private function get_tooltip( ?int $updated_user, ?string $updated_date ): string {
        $updated_user_name = '';
        if ( $updated_user ) {
            $user              = get_userdata( $updated_user );
            $updated_user_name = $user->display_name ?? '';
        }

        $on_text = function_exists( '__' ) ? __( 'on', 'racketmanager' ) : 'on';

        return sprintf( '%s %s %s', $updated_user_name, $on_text, $updated_date ?? '' );
    }
}
