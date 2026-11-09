<?php
declare( strict_types=1 );

namespace Racketmanager\Infrastructure\Wordpress\Shortcodes;

use Racketmanager\Application\Fixture\Queries\Get_Fixture_Details_Handler;
use Racketmanager\Application\Fixture\Queries\Get_Fixture_Details_Query;
use Racketmanager\Application\Ports\Query_Vars_Interface;
use Racketmanager\Domain\Shared\Seo_Slug;
use Racketmanager\Presentation\Presenters\Fixture_Presenter;
use function shortcode_atts;

/**
 * Adapter for the [fixture] and [match] shortcodes.
 */
readonly class Fixture_Shortcode_Adapter implements Shortcode_Adapter_Interface {
    public function __construct(
        private Get_Fixture_Details_Handler $handler,
        private Fixture_Presenter $presenter,
        private ?Query_Vars_Interface $query_vars = null
    ) {}

    /**
     * Handle the shortcode.
     *
     * @param array|string $atts Shortcode attributes.
     *
     * @return string HTML output.
     */
    public function handle( array|string $atts ): string {
        $params     = $this->get_params( $atts );
        $fixture_id = $params['fixture_id'];
        $action     = $params['action'];

        // If no ID is provided, try to resolve via slug criteria from URL
        $slug_criteria = [];
        if ( ! $fixture_id ) {
            $slug_criteria = $this->get_slug_criteria();
        }

        $query = new Get_Fixture_Details_Query(
            fixture_id: $fixture_id ?: null,
            slug_criteria: $slug_criteria
        );

        $dto = $this->handler->handle( $query );

        if ( ! $dto ) {
            return 'not found';
        }

        return $this->render_response( $dto, $action );
    }

    /**
     * Get parameters from shortcode attributes and query variables.
     *
     * @param array|string $atts Shortcode attributes.
     *
     * @return array
     */
    private function get_params( array|string $atts ): array {
        $args = shortcode_atts(
            [
                'match_id' => 0,
                'player'   => '',
                'template' => '',
            ],
            is_array( $atts ) ? $atts : []
        );

        return [
            'fixture_id' => empty( $args['match_id'] ) ? (int) ( $this->query_vars?->get( 'match_id' ) ?? 0 ) : (int) $args['match_id'],
            'action'     => (string) ( $this->query_vars?->get( 'action' ) ?? '' ),
        ];
    }

    /**
     * Get slug criteria from query variables.
     *
     * @return array
     */
    private function get_slug_criteria(): array {
        return [
            'league_slug'    => Seo_Slug::decode( (string) $this->query_vars?->get( 'league_name' ) ) ?: null,
            'home_team_slug' => Seo_Slug::decode( (string) $this->query_vars?->get( 'teamHome' ) ) ?: null,
            'away_team_slug' => Seo_Slug::decode( (string) $this->query_vars?->get( 'teamAway' ) ) ?: null,
            'match_day'      => $this->query_vars?->get( 'match_day' ) ? (int) $this->query_vars->get( 'match_day' ) : null,
            'season'         => $this->query_vars?->get( 'season' ) ?: null,
            'round'          => $this->query_vars?->get( 'round' ) ?: null,
            'leg'            => $this->query_vars?->get( 'leg' ) ? (int) $this->query_vars->get( 'leg' ) : null,
        ];
    }

    /**
     * Render the fixture response.
     *
     * @param mixed $dto Fixture details DTO.
     * @param string $action Current action.
     *
     * @return string
     */
    private function render_response( mixed $dto, string $action ): string {
        $header_model = $this->presenter->map_to_header_read_model( $dto, 'result' === $action, true );
        $content      = $this->presenter->render_header( $header_model );

        $detail_model = $this->presenter->map_to_detail_read_model( $dto, true, 'result' === $action );
        $content     .= $this->presenter->render_detail( $detail_model );

        return $content;
    }
}
