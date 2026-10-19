<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Templates;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Presentation\Presenters\Competition_Shortcode_Presenter;
use Racketmanager\Services\View\Php_View_Renderer;

class Event_Header_Template_Test extends TestCase {
    private string $template_path;
    private Competition_Shortcode_Presenter $presenter;

    protected function setUp(): void {
        parent::setUp();
        $this->template_path = dirname( __DIR__, 3 ) . '/templates/includes/event-header.php';
        $this->presenter     = new Competition_Shortcode_Presenter( new Php_View_Renderer( dirname( __DIR__, 3 ) . '/templates/' ) );
    }

    public function test_renders_with_enum_competition_type() {
        global $wp_query, $racketmanager;
        $wp_query = new \stdClass();
        $wp_query->query = [];
        $racketmanager = (object) [
            'date_format' => 'Y-m-d',
        ];

        $event = (object) [
            'name'        => 'Men Singles',
            'is_box'      => false,
            'competition' => (object) [
                'name'           => 'Summer Tournament',
                'type'           => Competition_Type::TOURNAMENT,
                'current_season' => [
                    'name'       => '2026',
                    'date_start' => '2026-06-01',
                    'date_end'   => '2026-06-15',
                ],
            ],
        ];
        $curr_season = '2026';
        $seasons     = [
            ['name' => '2026'],
        ];

        $event_header = $this->presenter->present_event_header(
            event: $event,
            curr_season: $curr_season,
            seasons: $seasons,
            header_level: 1,
            post_id: 10,
            pagename_query: 'tournament/summer-tournament/event/men-singles'
        );

        ob_start();
        include $this->template_path;
        $output = ob_get_clean();

        $this->assertStringContainsString( 'Men Singles', $output );
        $this->assertStringContainsString( 'Summer Tournament', $output );
        $this->assertStringContainsString( 'lta-icons.svg#icon-bracket', $output );
        $this->assertStringContainsString( 'name="page_id" value="10"', $output );
        $this->assertStringContainsString( 'id="pagename" value="tournament/summer-tournament/event/men-singles"', $output );
    }

    public function test_renders_with_league_enum_competition_type() {
        global $wp_query, $racketmanager;
        $wp_query = new \stdClass();
        $wp_query->query = [];
        $racketmanager = (object) [
            'date_format' => 'Y-m-d',
        ];

        $event = (object) [
            'name'        => 'Premier Division',
            'is_box'      => false,
            'competition' => (object) [
                'name'           => 'Summer League',
                'type'           => Competition_Type::LEAGUE,
                'current_season' => [
                    'name'       => '2026',
                    'date_start' => '2026-05-01',
                    'date_end'   => '2026-08-31',
                ],
            ],
        ];
        $curr_season = '2026';
        $seasons     = [
            ['name' => '2026'],
        ];

        $event_header = $this->presenter->present_event_header(
            event: $event,
            curr_season: $curr_season,
            seasons: $seasons,
            header_level: 1,
            post_id: 20
        );

        ob_start();
        include $this->template_path;
        $output = ob_get_clean();

        $this->assertStringContainsString( 'Premier Division', $output );
        $this->assertStringContainsString( 'bootstrap-icons.svg#table', $output );
        $this->assertStringContainsString( 'value="/leagues/premier-division/"', $output );
        $this->assertStringContainsString( 'name="page_id" value="20"', $output );
    }
}
