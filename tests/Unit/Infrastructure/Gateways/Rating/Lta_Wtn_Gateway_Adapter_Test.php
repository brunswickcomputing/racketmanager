<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit\Infrastructure\Gateways\Rating;

use Closure;
use PHPUnit\Framework\TestCase;
use Racketmanager\Exceptions\LTA_System_Not_Available_Exception;
use Racketmanager\Infrastructure\Gateways\Rating\Lta_Wtn_Gateway_Adapter;
use Racketmanager\Tests\Helpers\Player_Test_Factory;
use stdClass;
use WP_Error;

require_once __DIR__ . '/../../../../wp-stubs.php';

final class Lta_Wtn_Gateway_Adapter_Test extends TestCase {

    private Lta_Wtn_Gateway_Adapter $adapter;

    protected function setUp(): void {
        parent::setUp();
        $this->adapter = new Lta_Wtn_Gateway_Adapter();
        unset(
            $GLOBALS['wp_stubs_remote_get_return'],
            $GLOBALS['wp_stubs_remote_get_default'],
            $GLOBALS['wp_stubs_remote_get_callback']
        );
    }

    protected function tearDown(): void {
        unset(
            $GLOBALS['wp_stubs_remote_get_return'],
            $GLOBALS['wp_stubs_remote_get_default'],
            $GLOBALS['wp_stubs_remote_get_callback']
        );
        parent::tearDown();
    }

    private function create_cookie( string $name = 'st', string $value = 'tok' ): stdClass {
        $cookie        = new stdClass();
        $cookie->name  = $name;
        $cookie->value = $value;
        return $cookie;
    }

    private function normalize_response( mixed $response, string $url = '', array $args = [], mixed $default = null ): mixed {
        if ( $response instanceof Closure ) {
            return ( $response )( $url, $args );
        }
        if ( $response instanceof WP_Error ) {
            return $response;
        }
        if ( is_int( $response ) ) {
            return [
                'response' => [ 'code' => $response ],
                'body'     => '',
            ];
        }
        if ( is_string( $response ) ) {
            return [
                'response' => [ 'code' => 200 ],
                'body'     => $response,
            ];
        }
        if ( is_array( $response ) ) {
            return $response;
        }
        return $default;
    }

    private function mock_lta_http(
        mixed $search_response = null,
        mixed $profile_response = null,
        ?stdClass $cookiewall_cookie = null,
        string $player_profile_path = 'player/'
    ): void {
        $cookie = $cookiewall_cookie ?? $this->create_cookie();

        $GLOBALS['wp_stubs_remote_get_callback'] = function ( string $url, array $args = [] ) use ( $cookie, $search_response, $profile_response, $player_profile_path ) {
            if ( str_contains( $url, 'cookiewall' ) ) {
                return [
                    'response' => [ 'code' => 200 ],
                    'cookies'  => [ $cookie ],
                    'body'     => '',
                ];
            }

            if ( str_contains( $url, 'DoSearch' ) ) {
                return $this->normalize_response(
                    $search_response,
                    $url,
                    $args,
                    [
                        'response' => [ 'code' => 200 ],
                        'body'     => '<h5 class="media__title"><a href="/player/1">Player</a></h5>',
                    ]
                );
            }

            if ( str_contains( $url, $player_profile_path ) ) {
                $response = $this->normalize_response( $profile_response, $url, $args );
                if ( null !== $response ) {
                    return $response;
                }
            }

            return [ 'response' => [ 'code' => 404 ], 'body' => '' ];
        };
    }

    private function fetch_player_wtn( string $btm = '12345678' ): array {
        $this->adapter->prepare_env();
        $player = Player_Test_Factory::create_sparse( [ 'btm' => $btm ] );
        return $this->adapter->fetch_player_wtn( $player );
    }

    public function test_prepare_env_success_sets_cookies_and_args(): void {
        $cookie = $this->create_cookie( 'st', 'session_token' );

        $GLOBALS['wp_stubs_remote_get_return'] = [
            'https://competitions.lta.org.uk/cookiewall/' => [
                'response' => [ 'code' => 200 ],
                'cookies'  => [ $cookie ],
                'body'     => '<html>Cookiewall</html>',
            ],
        ];

        $this->adapter->prepare_env();
        $this->assertSame( 'session_token&c=1&cp=31', $cookie->value );
    }

    public function test_prepare_env_throws_exception_on_non_200_response(): void {
        $GLOBALS['wp_stubs_remote_get_return'] = [
            'https://competitions.lta.org.uk/cookiewall/' => [
                'response' => [ 'code' => 503 ],
                'cookies'  => [],
                'body'     => 'Service Unavailable',
            ],
        ];

        $this->expectException( LTA_System_Not_Available_Exception::class );
        $this->adapter->prepare_env();
    }

    public function test_fetch_player_wtn_success_parses_html_response(): void {
        $search_body = '
            <div class="results">
                <h5 class="media__title">
                    <a href="/player/profile/12345">John Doe</a>
                </h5>
            </div>
        ';

        $profile_body = '
            <div class="player-profile">
                World Tennis Number
                <ul class="list--inline list">
                    <li class="list__item">
                        <span class="tag-duo__title">Singles</span>
                        <svg></svg> 22.4 </span>
                    </li>
                    <li class="list__item">
                        <span class="tag-duo__title">Doubles</span>
                        <svg></svg> 19.8 </span>
                    </li>
                </ul>
            </div>
        ';

        $this->mock_lta_http( $search_body, $profile_body );
        $result = $this->fetch_player_wtn( '10203040' );

        $this->assertTrue( $result['status'] );
        $this->assertIsArray( $result['value'] );
        $this->assertSame( '22.4', $result['value']['S'] ?? null );
        $this->assertSame( '19.8', $result['value']['D'] ?? null );
    }

    public function test_fetch_player_wtn_returns_error_status_when_player_search_fails(): void {
        $this->mock_lta_http( new WP_Error( 'http_request_failed', 'Connection timeout' ) );
        $result = $this->fetch_player_wtn( '99999999' );

        $this->assertFalse( $result['status'] );
        $this->assertStringContainsString( 'search error', $result['message'] );
    }

    public function test_fetch_player_wtn_returns_error_status_when_player_not_found(): void {
        $this->mock_lta_http( '<div class="no-results">No players found</div>' );
        $result = $this->fetch_player_wtn( '99999999' );

        $this->assertFalse( $result['status'] );
        $this->assertStringContainsString( 'Player not found', $result['message'] );
    }

    public function test_fetch_player_wtn_encodes_search_query(): void {
        $requested_url = '';
        $this->mock_lta_http(
            function ( string $url ) use ( &$requested_url ) {
                $requested_url = $url;
                return [
                    'response' => [ 'code' => 200 ],
                    'body'     => '<div class="no-results">No players found</div>',
                ];
            }
        );

        $this->fetch_player_wtn( '10 20&30' );

        $this->assertStringContainsString( 'Query=10%2020%2630', $requested_url );
    }

    public function test_fetch_player_wtn_returns_error_when_search_status_not_200(): void {
        $this->mock_lta_http( 500 );
        $result = $this->fetch_player_wtn( '12345678' );

        $this->assertFalse( $result['status'] );
        $this->assertStringContainsString( 'LTA number not found', $result['message'] );
    }

    public function test_fetch_player_wtn_returns_error_when_player_link_malformed(): void {
        $this->mock_lta_http( '<h5 class="media__title">No link here</h5>' );
        $result = $this->fetch_player_wtn( '12345678' );

        $this->assertFalse( $result['status'] );
        $this->assertStringContainsString( 'Player link not found', $result['message'] );
    }

    public function test_fetch_player_wtn_returns_error_when_profile_request_fails_wp_error(): void {
        $this->mock_lta_http( null, new WP_Error( 'http_error', 'Profile connection timeout' ) );
        $result = $this->fetch_player_wtn( '12345678' );

        $this->assertFalse( $result['status'] );
        $this->assertStringContainsString( 'View error', $result['message'] );
    }

    public function test_fetch_player_wtn_returns_error_when_profile_response_not_200(): void {
        $this->mock_lta_http( null, 500 );
        $result = $this->fetch_player_wtn( '12345678' );

        $this->assertFalse( $result['status'] );
        $this->assertStringContainsString( 'Unable to retrieve player page', $result['message'] );
    }

    public function test_fetch_player_wtn_returns_error_when_world_tennis_number_not_found(): void {
        $this->mock_lta_http( null, '<div>Player without tennis ratings</div>' );
        $result = $this->fetch_player_wtn( '12345678' );

        $this->assertFalse( $result['status'] );
        $this->assertStringContainsString( 'WTN not found', $result['message'] );
    }

    public function test_fetch_player_wtn_returns_error_when_no_wtn_list_found(): void {
        $this->mock_lta_http( null, '<div>World Tennis Number without list block</div>' );
        $result = $this->fetch_player_wtn( '12345678' );

        $this->assertFalse( $result['status'] );
        $this->assertStringContainsString( 'No WTN list found', $result['message'] );
    }

    public function test_fetch_player_wtn_returns_error_when_no_wtn_entries_found(): void {
        $this->mock_lta_http( null, 'World Tennis Number <ul class="list--inline list"></ul>' );
        $result = $this->fetch_player_wtn( '12345678' );

        $this->assertFalse( $result['status'] );
        $this->assertStringContainsString( 'No WTN entries found', $result['message'] );
    }

    public function test_fetch_player_wtn_handles_malformed_list_item_html_without_type_error(): void {
        $malformed_profile = 'World Tennis Number <ul class="list--inline list"><li class="list__item">Malformed Content Without Tags</li></ul>';
        $this->mock_lta_http( null, $malformed_profile );
        $result = $this->fetch_player_wtn( '12345678' );

        $this->assertFalse( $result['status'] );
        $this->assertStringContainsString( 'WTN values not set', $result['message'] );
    }
}
