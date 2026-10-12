<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Templates;

use PHPUnit\Framework\TestCase;
use Racketmanager\Domain\Competition\Competition_Type;
use Racketmanager\Domain\DTO\Fixture\Fixture_Hydration_DTO;
use Racketmanager\Domain\Fixture\Fixture;

class Matches_Team_List_Template_Test extends TestCase {
    private string $template_path;

    protected function setUp(): void {
        parent::setUp();
        $this->template_path = dirname( __DIR__, 3 ) . '/templates/includes/matches-team-list.php';
        if ( ! defined( 'RACKETMANAGER_PATH' ) ) {
            define( 'RACKETMANAGER_PATH', dirname( __DIR__, 3 ) . '/' );
        }
        if ( ! defined( 'RACKETMANAGER_URL' ) ) {
            define( 'RACKETMANAGER_URL', 'https://example.test/wp-content/plugins/racketmanager/' );
        }
    }

    public function test_renders_with_fixture_domain_objects() {
        global $racketmanager;
        $racketmanager = new class {
            public string $date_format = 'Y-m-d';
            public function is_player_in_club( array $args ): bool {
                return false;
            }
        };

        $dto = new Fixture_Hydration_DTO(
            id: 101,
            fixture_title: 'Team A vs Team B',
            link: 'https://example.test/match/101/',
            group: 'Group A',
            date: '2026-10-12',
            date_original: '2026-10-12',
            home_team: '1',
            away_team: '2',
            match_day: 1,
            location: 'Court 1',
            host: null,
            league_id: 5,
            season: '2026',
            status: null,
            start_time: '14:00',
            winner_id: null,
            loser_id: null,
            home_points: null,
            away_points: null,
            custom: []
        );

        $fixture = new Fixture( $dto );
        $fixture->teams = [
            'home' => (object) [ 'id' => 1, 'title' => 'Team A', 'club' => (object) [ 'id' => 10 ] ],
            'away' => (object) [ 'id' => 2, 'title' => 'Team B', 'club' => (object) [ 'id' => 20 ] ],
        ];
        $fixture->league = (object) [
            'id'    => 5,
            'title' => 'Division 1',
            'event' => (object) [
                'id'          => 3,
                'competition' => (object) [
                    'type' => Competition_Type::LEAGUE,
                ],
            ],
        ];

        // Verify Fixture::is_update_allowed() returns object
        $perm = $fixture->is_update_allowed();
        $this->assertIsObject( $perm );
        $this->assertFalse( $perm->user_can_update );

        $matches = [ $fixture ];

        ob_start();
        include $this->template_path;
        $output = ob_get_clean();

        $this->assertStringContainsString( 'match--team-match', $output );
        $this->assertStringContainsString( 'Team A', $output );
        $this->assertStringContainsString( 'Team B', $output );
        $this->assertStringContainsString( 'is-not-played', $output );
    }
}
