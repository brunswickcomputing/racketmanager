<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Templates;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Competition_Type;

class Event_Template_Test extends TestCase {
    private string $template_path;

    protected function setUp(): void {
        parent::setUp();
        $this->template_path = dirname( __DIR__, 3 ) . '/templates/event.php';
    }

    public function test_renders_with_enum_competition_type() {
        global $wp, $wp_query, $racketmanager;
        $wp = new \stdClass();
        $wp->query_vars = [];
        $wp_query = new \stdClass();
        $wp_query->query = [];
        $racketmanager = (object) [
            'date_format' => 'Y-m-d',
        ];

        $event = (object) [
            'id'             => 10,
            'name'           => 'Summer Tournament Event',
            'is_box'         => false,
            'current_season' => [
                'name'       => '2026',
            ],
            'competition'    => (object) [
                'id'              => 5,
                'name'            => 'Summer Tournament',
                'type'            => Competition_Type::TOURNAMENT,
                'is_championship' => false,
                'current_season'  => [
                    'name'        => '2026',
                    'date_start'  => '2026-06-01',
                    'date_end'    => '2026-06-15',
                ],
            ],
        ];

        $curr_season = '2026';
        $seasons = [
            ['name' => '2026'],
        ];

        ob_start();
        include $this->template_path;
        $output = ob_get_clean();

        $this->assertStringContainsString( 'data-competition-type="tournament"', $output );
        $this->assertStringContainsString( 'Summer Tournament Event', $output );
    }

    public function test_renders_with_league_enum_competition_type() {
        global $wp, $wp_query, $racketmanager;
        $wp = new \stdClass();
        $wp->query_vars = [];
        $wp_query = new \stdClass();
        $wp_query->query = [];
        $racketmanager = (object) [
            'date_format' => 'Y-m-d',
        ];

        $event = (object) [
            'id'             => 12,
            'name'           => 'Division 1',
            'is_box'         => false,
            'current_season' => [
                'name'       => '2026',
            ],
            'competition'    => (object) [
                'id'              => 6,
                'name'            => 'Summer League',
                'type'            => Competition_Type::LEAGUE,
                'is_championship' => false,
                'current_season'  => [
                    'name'        => '2026',
                    'date_start'  => '2026-05-01',
                    'date_end'    => '2026-08-31',
                ],
            ],
        ];
        $curr_season = '2026';
        $seasons = [
            ['name' => '2026'],
        ];

        ob_start();
        include $this->template_path;
        $output = ob_get_clean();

        $this->assertStringContainsString( 'data-competition-type="league"', $output );
        $this->assertStringContainsString( 'Division 1', $output );
    }
}
