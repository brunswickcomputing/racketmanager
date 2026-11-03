<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Racketmanager\Admin\View_Models\Contact_Page_View_Model;
use Racketmanager\Admin\View_Models\Teams_List_Page_View_Model;
use Racketmanager\Application\Account\Commands\Add_Favourite_Command;
use Racketmanager\Application\Account\Commands\Login_Command;
use Racketmanager\Application\Club\Commands\Update_Club_Command;
use Racketmanager\Domain\Club\Club;
use Racketmanager\Domain\Communication\Message;
use Racketmanager\Domain\Player\Player;
use Racketmanager\Domain\Player\User;
use Racketmanager\Domain\Season\Season;
use Racketmanager\Domain\Team\Team;
use Racketmanager\Domain\Tournament\Tournament;
use Racketmanager\Tests\Architecture\Support\Alias_Map;
use ReflectionClass;

/**
 * Sunset verification test confirming that legacy class aliases have been retired
 * and no longer exist at runtime, while their canonical PSR-4 target classes exist
 * and are directly instantiable.
 */
final class Aliases_Test extends TestCase {

    public function test_aliases_runtime_file_and_legacy_view_model_aliases_file_are_deleted(): void {
        $plugin_root = dirname( __DIR__, 2 );

        $this->assertFileDoesNotExist( $plugin_root . '/src/php/aliases.php' );
        $this->assertFileDoesNotExist( $plugin_root . '/src/php/Admin/View_Models/Legacy_View_Model_Aliases.php' );
    }

    public function test_composer_autoload_does_not_register_aliases_file(): void {
        $plugin_root   = dirname( __DIR__, 2 );
        $composer_file = $plugin_root . '/composer.json';

        $this->assertFileExists( $composer_file );
        $composer_json = json_decode( (string) file_get_contents( $composer_file ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
        $this->assertIsArray( $composer_json );

        $autoload_files = $composer_json['autoload']['files'] ?? [];
        $this->assertNotContains( 'src/php/aliases.php', $autoload_files );

        $autoload_generated_file = $plugin_root . '/vendor/composer/autoload_files.php';
        if ( file_exists( $autoload_generated_file ) ) {
            $generated_content = (string) file_get_contents( $autoload_generated_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
            $this->assertStringNotContainsString( 'aliases.php', $generated_content );
        }
    }

    public function test_retired_aliases_catalogue_contains_exact_63_entries(): void {
        $this->assertCount( 63, Alias_Map::RETIRED_ALIASES );
        $this->assertCount( 63, Alias_Map::load() );
    }

    public function test_strict_rule_alias_names_include_both_view_model_aliases(): void {
        $names = Alias_Map::alias_names();

        $this->assertCount( 65, $names );
        $this->assertSame(
            [ ...array_keys( Alias_Map::RETIRED_ALIASES ), ...array_keys( Alias_Map::RETIRED_VIEW_MODEL_ALIASES ) ],
            $names
        );
        $this->assertContains( 'Racketmanager\Admin\View_Models\Tournament_Contact_Page_View_Model', $names );
        $this->assertContains( 'Racketmanager\Admin\View_Models\Tournament_Teams_List_Page_View_Model', $names );
    }

    /**
     * Data provider of retired legacy aliases and canonical targets.
     *
     * @return iterable<string, array{0: string, 1: string}>
     */
    public static function retired_aliases_provider(): iterable {
        foreach ( Alias_Map::RETIRED_ALIASES as $alias => $target ) {
            yield $alias => [ $alias, $target ];
        }
    }

    #[\PHPUnit\Framework\Attributes\DataProvider( 'retired_aliases_provider' )]
    public function test_retired_legacy_alias_does_not_exist( string $alias, string $canonical_target ): void {
        $this->assertFalse(
            class_exists( $alias, true ),
            sprintf( 'Legacy alias class %s must not exist.', $alias )
        );
        $this->assertFalse(
            interface_exists( $alias, true ),
            sprintf( 'Legacy alias interface %s must not exist.', $alias )
        );
        $this->assertFalse(
            trait_exists( $alias, true ),
            sprintf( 'Legacy alias trait %s must not exist.', $alias )
        );
        $this->assertFalse(
            enum_exists( $alias, true ),
            sprintf( 'Legacy alias enum %s must not exist.', $alias )
        );

        $this->assertTrue(
            class_exists( $canonical_target ) || interface_exists( $canonical_target ) || enum_exists( $canonical_target ),
            sprintf( 'Canonical PSR-4 target %s must exist.', $canonical_target )
        );

        $reflection = new ReflectionClass( $canonical_target );
        $this->assertSame( $canonical_target, $reflection->getName() );
    }

    public function test_legacy_view_model_aliases_do_not_exist(): void {
        $this->assertCount( 2, Alias_Map::RETIRED_VIEW_MODEL_ALIASES );

        foreach ( Alias_Map::RETIRED_VIEW_MODEL_ALIASES as $alias => $target ) {
            $this->assertFalse(
                class_exists( $alias, true ),
                sprintf( 'Legacy view model alias %s must not exist.', $alias )
            );
            $this->assertTrue(
                class_exists( $target ),
                sprintf( 'Canonical view model target %s must exist.', $target )
            );
        }

        $this->assertTrue( class_exists( Teams_List_Page_View_Model::class ) );
        $this->assertTrue( class_exists( Contact_Page_View_Model::class ) );
    }

    public function test_no_test_files_reference_retired_aliases(): void {
        $aliases = array_merge(
            array_keys( Alias_Map::RETIRED_ALIASES ),
            array_keys( Alias_Map::RETIRED_VIEW_MODEL_ALIASES )
        );

        $plugin_root = dirname( __DIR__, 2 );
        $exempt      = [
            realpath( $plugin_root . '/tests/Architecture/Support/Alias_Map.php' ),
            realpath( $plugin_root . '/tests/Unit/Aliases_Test.php' ),
            realpath( $plugin_root . '/tests/Architecture/Support_Test/Alias_Map_Test.php' ),
            realpath( $plugin_root . '/tests/Architecture/Baseline_Integrity_Test.php' ),
            realpath( $plugin_root . '/tests/Architecture/Support_Test/Layer_Rules_Test.php' ),
            realpath( $plugin_root . '/tests/Architecture/Ports_Location_Test.php' ),
            realpath( $plugin_root . '/tests/Architecture/Support_Test/Architecture_Test_Case_Test.php' ),
        ];

        $dir        = new \RecursiveDirectoryIterator( $plugin_root . '/tests' );
        $iterator   = new \RecursiveIteratorIterator( $dir );
        $violations = [];

        foreach ( $iterator as $file ) {
            if ( ! $file->isFile() || 'php' !== $file->getExtension() ) {
                continue;
            }
            $real = realpath( $file->getPathname() );
            if ( in_array( $real, $exempt, true ) ) {
                continue;
            }
            $content = (string) file_get_contents( $real ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
            foreach ( self::find_retired_alias_references( $content, $aliases ) as $alias ) {
                $violations[] = basename( $real ) . ' references retired alias ' . $alias;
            }
        }

        $this->assertSame( [], $violations, 'Test suite must not reference retired aliases.' );
    }

    /**
     * Regression test: Ensure guardrail matches doubled-backslash retired aliases,
     * but ignores doubled-backslash canonical classes.
     */
    public function test_guardrail_catches_doubled_backslashes_for_retired_aliases_but_ignores_canonical_classes(): void {
        $aliases = array_merge(
            array_keys( Alias_Map::RETIRED_ALIASES ),
            array_keys( Alias_Map::RETIRED_VIEW_MODEL_ALIASES )
        );

        $content_retired   = 'Racketmanager\\\\Domain\\\\User';
        $content_canonical = 'Racketmanager\\\\Domain\\\\Player\\\\User';

        $this->assertContains(
            'Racketmanager\\Domain\\User',
            self::find_retired_alias_references( $content_retired, $aliases )
        );
        $this->assertSame( [], self::find_retired_alias_references( $content_canonical, $aliases ) );
    }

    /**
     * Find references to retired aliases in source text, including PHP-escaped namespace strings.
     *
     * @param string        $content Source text to scan.
     * @param array<string> $aliases Retired alias names.
     * @return array<string> Matched retired alias names.
     */
    private static function find_retired_alias_references( string $content, array $aliases ): array {
        $content = str_replace( '\\\\', '\\', $content );
        $matches = [];

        foreach ( $aliases as $alias ) {
            $pattern = '/\b' . preg_quote( $alias, '/' ) . '\b(?!\\\\)/';
            if ( preg_match( $pattern, $content ) ) {
                $matches[] = $alias;
            }
        }

        return $matches;
    }

    public function test_canonical_domain_entities_are_directly_instantiable(): void {
        $user       = new User();
        $player     = new Player();
        $club       = new Club();
        $tournament = new Tournament();
        $team       = new Team();
        $season     = new Season();
        $message    = new Message();

        $this->assertInstanceOf( User::class, $user );
        $this->assertInstanceOf( Player::class, $player );
        $this->assertInstanceOf( Club::class, $club );
        $this->assertInstanceOf( Tournament::class, $tournament );
        $this->assertInstanceOf( Team::class, $team );
        $this->assertInstanceOf( Season::class, $season );
        $this->assertInstanceOf( Message::class, $message );
    }

    public function test_canonical_application_commands_are_directly_instantiable(): void {
        $login_cmd = new Login_Command( 'testuser', 'secret' );
        $this->assertInstanceOf( Login_Command::class, $login_cmd );
        $this->assertSame( 'testuser', $login_cmd->user_login );

        $fav_cmd = new Add_Favourite_Command( 'club', 42, 101 );
        $this->assertInstanceOf( Add_Favourite_Command::class, $fav_cmd );
        $this->assertSame( 'club', $fav_cmd->type );
        $this->assertSame( 42, $fav_cmd->id );
        $this->assertSame( 101, $fav_cmd->user_id );

        $update_club_cmd = new Update_Club_Command(
            club_id: 10,
            contact_no: '0123456789',
            facilities: '3 courts',
            founded: 1990,
            website: 'https://example.com',
            address: '123 High St',
            match_secretary_id: 5,
            match_secretary_contact_no: '0987654321',
            match_secretary_email: 'sec@example.com'
        );
        $this->assertInstanceOf( Update_Club_Command::class, $update_club_cmd );
        $this->assertSame( 10, $update_club_cmd->club_id );
    }

    public function test_canonical_view_models_are_directly_instantiable(): void {
        $teams_vm = new Teams_List_Page_View_Model(
            league: (object) [ 'id' => 1 ],
            league_id: 1,
            season: '2026'
        );
        $this->assertInstanceOf( Teams_List_Page_View_Model::class, $teams_vm );

        $contact_vm = new Contact_Page_View_Model(
            tournament: (object) [ 'id' => 1 ],
            email_message: 'Hello'
        );
        $this->assertInstanceOf( Contact_Page_View_Model::class, $contact_vm );
    }
}
