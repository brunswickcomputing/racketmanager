<?php
declare( strict_types=1 );

namespace Racketmanager\Presenters;

use Racketmanager\Application\Matches\DTOs\Match_List_Result;
use Racketmanager\Exceptions\Template_Not_Found_Exception;
use Racketmanager\Presentation\View_Models\Matches_List_View_Model;
use Racketmanager\Services\Competition_Service;
use Racketmanager\Services\Fixture\Fixture_Link_Service;
use Racketmanager\Services\Fixture\Fixture_Permission_Service;
use Racketmanager\Services\Team_Service;
use Racketmanager\Services\View\View_Renderer_Interface;
use Throwable;
use function Racketmanager\show_alert;

/**
 * Presents match listing results ([dailymatches], [latest_results]) as grouped view models
 * and renders them through the view renderer (theme overrides supported).
 */
class Matches_List_Presenter {
    public function __construct(
        private readonly Competition_Service $competition_service,
        private readonly Team_Service $team_service,
        private readonly View_Renderer_Interface $renderer,
        private readonly ?Fixture_Link_Service $link_service = null,
        private readonly ?Fixture_Permission_Service $permission_service = null,
        private readonly ?Club_Presenter $club_presenter = null,
        private readonly ?Player_Presenter $player_presenter = null
    ) {}

    public function present_daily( Match_List_Result $result, string $template ): Matches_List_View_Model {
        return new Matches_List_View_Model(
            template: '' !== $template ? 'matches-' . $template : 'matches-daily',
            matches_list: $this->group_by_league( $result->fixtures ),
            match_date: $result->match_date ?? gmdate( 'Y-m-d' )
        );
    }

    public function present_latest_results( Match_List_Result $result, string $template, int $header_level ): Matches_List_View_Model {
        return new Matches_List_View_Model(
            template: '' !== $template ? 'matches-' . $template : 'matches-results',
            matches_list: $this->group_by_league( $result->fixtures ),
            header_level: $header_level
        );
    }

    public function render( Matches_List_View_Model $vm ): string {
        $vars = array_merge(
            [
                'club_presenter'   => $this->club_presenter ?? new Club_Presenter(),
                'player_presenter' => $this->player_presenter ?? new Player_Presenter(),
            ],
            $vm->template_vars()
        );

        $level = ob_get_level();
        try {
            return $this->renderer->render_to_string( $vm->template, $vm, $vars );
        } catch ( Template_Not_Found_Exception ) {
            $this->restore_buffer_level( $level );
            /* translators: %1$s: template %2$s: directory */
            $msg = sprintf( __( 'Could not load template %1$s.php from %2$s directory', 'racketmanager' ), $vm->template, 'templates' );

            return (string) show_alert( $msg, 'danger' );
        } catch ( Throwable $e ) {
            $this->restore_buffer_level( $level );
            throw $e;
        }
    }

    /**
     * Wraps fixtures in presentation adapters (links, permissions, teams) and groups them by league name.
     *
     * @param array $fixtures Fixture entities.
     *
     * @return array<string, Fixture_Presentation_Adapter[]>
     */
    public function group_by_league( array $fixtures ): array {
        $matches_list = [];
        foreach ( $fixtures as $fixture ) {
            $match = Fixture_Presentation_Adapter::from_fixture(
                $fixture,
                $this->competition_service,
                $this->team_service,
                $this->link_service,
                $this->permission_service
            );

            $matches_list[ $this->league_key( $match ) ][] = $match;
        }

        return $matches_list;
    }

    private function league_key( Fixture_Presentation_Adapter $match ): string {
        $title = (string) ( $match->league->title ?? '' );
        if ( '' === $title && ! empty( $match->league_id ) ) {
            $league = $this->competition_service->get_league_repository()->find_by_id( (int) $match->league_id );
            if ( is_object( $league ) ) {
                $title = (string) ( method_exists( $league, 'get_name' ) ? $league->get_name() : ( $league->title ?? '' ) );
            }
        }

        return $title;
    }

    private function restore_buffer_level( int $level ): void {
        while ( ob_get_level() > $level ) {
            ob_end_clean();
        }
    }
}
