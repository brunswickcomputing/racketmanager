<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Architecture\Support_Test;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Racketmanager\Tests\Architecture\Rules\Layer_Rules;
use Racketmanager\Tests\Architecture\Support\Name_Pattern;
use Racketmanager\Tests\Architecture\Support\Rule_Evaluator;
use Racketmanager\Tests\Architecture\Support\Rule_Mode;
use Racketmanager\Tests\Architecture\Support\Source_Scanner;
use Racketmanager\Tests\Architecture\Support\Violation;

/**
 * Proves every catalogue rule can detect a positive case from an in-memory snippet placed inside its
 * scope, and ignores an equivalent clean snippet. A strict rule that reports zero violations on the
 * real tree is only meaningful if it is not vacuous.
 */
final class Layer_Rules_Test extends TestCase {
    /**
     * @return iterable<string, array{0: string, 1: string, 2: string, 3: string}> rule id, path, offending code, expected detail fragment
     */
    public static function positive_cases(): iterable {
        $ns = "<?php\nnamespace Racketmanager\\Domain\\Club;\n";

        yield 'domain.no_wpdb' => [ 'domain.no_wpdb', 'src/php/Domain/Club/Club.php', $ns . 'class Club { function f() { global $wpdb; return $wpdb->prefix; } }', '$wpdb' ];
        yield 'domain.no_wpdb via GLOBALS' => [ 'domain.no_wpdb', 'src/php/Domain/Club/Club.php', $ns . 'class Club { function f() { return $GLOBALS[\'wpdb\']->prefix; } }', '$wpdb' ];
        yield 'domain.no_outer_layer_imports (use)' => [ 'domain.no_outer_layer_imports', 'src/php/Domain/Club/Club.php', $ns . 'use Racketmanager\Repositories\Club_Repository;' . "\n" . 'class Club {}', 'import Racketmanager\Repositories\Club_Repository' ];
        yield 'domain.no_outer_layer_imports (fq new)' => [ 'domain.no_outer_layer_imports', 'src/php/Domain/Club/Club.php', $ns . 'class Club { function f() { return new \Racketmanager\Services\Club_Service(); } }', 'new Racketmanager\Services\Club_Service' ];
        yield 'domain.no_outer_layer_imports (type)' => [ 'domain.no_outer_layer_imports', 'src/php/Domain/Club/Club.php', $ns . 'class Club { function f( \Racketmanager\Presentation\Presenters\Club_Presenter $p ) {} }', 'Racketmanager\Presentation\Presenters\Club_Presenter' ];
        yield 'domain.no_superglobals' => [ 'domain.no_superglobals', 'src/php/Domain/Club/Club.php', $ns . 'class Club { function f() { return $_POST[\'x\']; } }', '$_POST' ];
        yield 'domain.no_wordpress_functions' => [ 'domain.no_wordpress_functions', 'src/php/Domain/Club/Club.php', $ns . 'class Club { function f() { return sanitize_text_field( __( "x", "racketmanager" ) ); } }', 'sanitize_text_field()' ];
        yield 'domain.no_wordpress_functions (glob)' => [ 'domain.no_wordpress_functions', 'src/php/Domain/Club/Club.php', $ns . 'class Club { function f() { return wp_cache_get( "k" ); } }', 'wp_cache_get()' ];
        yield 'domain.no_wordpress_functions (wp_* family)' => [ 'domain.no_wordpress_functions', 'src/php/Domain/Club/Club.php', $ns . 'class Club { function f() { return wp_get_current_user(); } }', 'wp_get_current_user()' ];
        yield 'domain.no_wordpress_functions (sanitize_* family)' => [ 'domain.no_wordpress_functions', 'src/php/Domain/Club/Club.php', $ns . 'class Club { function f( $e ) { return sanitize_email( $e ); } }', 'sanitize_email()' ];
        yield 'domain.no_wordpress_functions (esc_* family)' => [ 'domain.no_wordpress_functions', 'src/php/Domain/Club/Club.php', $ns . 'class Club { function f() { return esc_html__( "x", "racketmanager" ); } }', 'esc_html__()' ];
        yield 'domain.no_wordpress_functions (meta family)' => [ 'domain.no_wordpress_functions', 'src/php/Domain/Club/Club.php', $ns . 'class Club { function f() { return get_user_meta( 1, "k", true ); } }', 'get_user_meta()' ];
        yield 'domain.no_wordpress_functions (_x)' => [ 'domain.no_wordpress_functions', 'src/php/Domain/Club/Club.php', $ns . 'class Club { function f() { return _x( "x", "ctx", "racketmanager" ); } }', '_x()' ];
        yield 'domain.no_wordpress_functions (absint)' => [ 'domain.no_wordpress_functions', 'src/php/Domain/Club/Club.php', $ns . 'class Club { function f( $v ) { return absint( $v ); } }', 'absint()' ];
        yield 'domain.no_wordpress_functions (maybe_unserialize)' => [ 'domain.no_wordpress_functions', 'src/php/Domain/Club/Club.php', $ns . 'class Club { function f( $v ) { return maybe_unserialize( $v ); } }', 'maybe_unserialize()' ];
        yield 'domain.no_wordpress_functions (stripslashes_deep)' => [ 'domain.no_wordpress_functions', 'src/php/Domain/Club/Club.php', $ns . 'class Club { function f( $v ) { return stripslashes_deep( $v ); } }', 'stripslashes_deep()' ];
        yield 'domain.no_wordpress_functions (fully qualified)' => [ 'domain.no_wordpress_functions', 'src/php/Domain/Club/Club.php', $ns . 'class Club { function f() { return \current_time( "mysql" ); } }', 'current_time()' ];
        yield 'domain.no_util_static' => [ 'domain.no_util_static', 'src/php/Domain/Club/Club.php', $ns . 'use Racketmanager\Util\Util;' . "\n" . 'class Club { function f() { return Util::seo_url( "x" ); } }', 'Racketmanager\Util\Util::seo_url()' ];
        yield 'domain.no_untyped_constructor' => [ 'domain.no_untyped_constructor', 'src/php/Domain/Club/Club.php', $ns . 'class Club { public function __construct( ?object $club = null ) {} }', '__construct($club: ?object)' ];
        yield 'domain.no_untyped_constructor (union)' => [ 'domain.no_untyped_constructor', 'src/php/Domain/Club/Club.php', $ns . 'class Club { public function __construct( Club_DTO|\stdClass|null $club = null ) {} }', 'stdClass' ];

        $app = "<?php\nnamespace Racketmanager\\Application\\Club\\Handlers;\n";
        yield 'application.no_wpdb' => [ 'application.no_wpdb', 'src/php/Application/Club/Handlers/H.php', $app . 'class H { function f() { global $wpdb; } }', '$wpdb' ];
        yield 'application.no_global_racketmanager' => [ 'application.no_global_racketmanager', 'src/php/Application/Club/Handlers/H.php', $app . 'class H { function f() { global $racketmanager; } }', 'global $racketmanager' ];
        yield 'application.no_global_racketmanager (domain, GLOBALS)' => [ 'application.no_global_racketmanager', 'src/php/Domain/Club/Club.php', $ns . 'class Club { function f() { return $GLOBALS[\'racketmanager\']; } }', "\$GLOBALS['racketmanager']" ];
        yield 'application.no_superglobals' => [ 'application.no_superglobals', 'src/php/Application/Club/Handlers/H.php', $app . 'class H { function f() { return $_SERVER[\'REQUEST_URI\']; } }', '$_SERVER' ];
        yield 'application.no_concrete_repositories (use)' => [ 'application.no_concrete_repositories', 'src/php/Application/Club/Handlers/H.php', $app . 'use Racketmanager\Repositories\Club_Repository;' . "\n" . 'class H {}', 'Racketmanager\Repositories\Club_Repository' ];
        yield 'application.no_concrete_repositories (new)' => [ 'application.no_concrete_repositories', 'src/php/Application/Club/Handlers/H.php', $app . 'class H { function f() { return new \Racketmanager\Repositories\Club_Repository(); } }', 'new Racketmanager\Repositories\Club_Repository' ];
        yield 'application.no_presentation_imports' => [ 'application.no_presentation_imports', 'src/php/Application/Club/Handlers/H.php', $app . 'use Racketmanager\Presentation\Presenters\Club_Presenter;' . "\n" . 'class H {}', 'depends on Racketmanager\Presentation\Presenters\Club_Presenter' ];
        yield 'application.no_presentation_imports (fq new, no use)' => [ 'application.no_presentation_imports', 'src/php/Application/Club/Handlers/H.php', $app . 'class H { function f() { return new \Racketmanager\Presentation\Presenters\Club_Presenter(); } }', 'depends on Racketmanager\Presentation\Presenters\Club_Presenter' ];
        yield 'application.no_presentation_imports (top-level Admin class)' => [ 'application.no_presentation_imports', 'src/php/Application/Club/Handlers/H.php', $app . 'class H { function f( \Racketmanager\Admin $a ) {} }', 'depends on Racketmanager\Admin' ];

        $svc = "<?php\nnamespace Racketmanager\\Services;\n";
        yield 'services.no_wpdb' => [ 'services.no_wpdb', 'src/php/Services/Team_Service.php', $svc . 'class Team_Service { function f() { global $wpdb; $wpdb->query( "x" ); } }', '$wpdb' ];
        yield 'services.no_global_racketmanager' => [ 'services.no_global_racketmanager', 'src/php/Rest/Rest_Resources.php', "<?php\nnamespace Racketmanager\\Rest;\n" . 'class R { function f() { global $racketmanager; } }', 'global $racketmanager' ];
        yield 'services.no_presentation_imports' => [ 'services.no_presentation_imports', 'src/php/Services/Team_Service.php', $svc . 'use Racketmanager\Admin\Admin_Display;' . "\n" . 'class Team_Service {}', 'depends on Racketmanager\Admin\Admin_Display' ];
        yield 'services.no_presentation_imports (fq static, no use)' => [ 'services.no_presentation_imports', 'src/php/Services/Team_Service.php', $svc . 'class Team_Service { function f() { return \Racketmanager\Presentation\Formatters\Slug_Formatter::format( "x" ); } }', 'depends on Racketmanager\Presentation\Formatters\Slug_Formatter' ];
        yield 'repositories.no_service_imports' => [ 'repositories.no_service_imports', 'src/php/Repositories/User_Repository.php', "<?php\nnamespace Racketmanager\\Repositories;\n" . 'use Racketmanager\Services\Club_Service;' . "\n" . 'class User_Repository {}', 'depends on Racketmanager\Services\Club_Service' ];
        yield 'repositories.no_service_imports (fq type, no use)' => [ 'repositories.no_service_imports', 'src/php/Repositories/User_Repository.php', "<?php\nnamespace Racketmanager\\Repositories;\n" . 'class User_Repository { public function __construct( private \Racketmanager\Services\Club_Service $s ) {} }', 'depends on Racketmanager\Services\Club_Service' ];

        yield 'all.no_legacy_helper_calls (unqualified in root namespace)' => [ 'all.no_legacy_helper_calls', 'src/php/Admin.php', "<?php\nnamespace Racketmanager;\n" . 'class Admin { function f() { return get_league( 1 ); } }', 'Racketmanager\get_league()' ];
        yield 'all.no_legacy_helper_calls (use function)' => [ 'all.no_legacy_helper_calls', 'src/php/Services/Team_Service.php', $svc . 'use function Racketmanager\seo_url;' . "\n" . 'class Team_Service { function f() { return seo_url( "x" ); } }', 'Racketmanager\seo_url()' ];
        yield 'all.no_legacy_helper_calls (query service glob)' => [ 'all.no_legacy_helper_calls', 'src/php/Admin/Admin_Display.php', "<?php\nnamespace Racketmanager\\Admin;\n" . 'class A { function f() { return \Racketmanager\get_league_query_service(); } }', 'Racketmanager\get_league_query_service()' ];
        yield 'all.no_legacy_helper_calls (template-tags.php)' => [ 'all.no_legacy_helper_calls', 'template-tags.php', "<?php\nnamespace Racketmanager;\nfunction show_league( \$id ) { return get_league( \$id ); }", 'Racketmanager\get_league()' ];
        yield 'all.no_deprecated_helper_calls (src)' => [ 'all.no_deprecated_helper_calls', 'src/php/Services/Team_Service.php', $svc . 'class Team_Service { function f() { return get_match( 1 ); } }', 'get_match()' ];
        yield 'all.no_deprecated_helper_calls (template)' => [ 'all.no_deprecated_helper_calls', 'templates/match.php', "<?php\nnamespace Racketmanager;\n\$r = get_rubber( 1 );", 'get_rubber()' ];
        yield 'all.no_deprecated_helper_calls (template-tags)' => [ 'all.no_deprecated_helper_calls', 'template-tags.php', "<?php\nnamespace Racketmanager;\nfunction x() { return to_legacy_user( \$u ); }", 'to_legacy_user()' ];
        yield 'all.no_get_instance_outside_bootstrap' => [ 'all.no_get_instance_outside_bootstrap', 'src/php/Cli/Export_Command.php', "<?php\nnamespace Racketmanager\\Cli;\nuse Racketmanager\\RacketManager;\n" . 'class E { function f() { return RacketManager::get_instance(); } }', 'Racketmanager\RacketManager::get_instance()' ];
        yield 'all.no_get_instance_outside_bootstrap (admin)' => [ 'all.no_get_instance_outside_bootstrap', 'src/php/Services/X.php', $svc . 'class X { function f() { return \Racketmanager\Admin::get_instance(); } }', 'Racketmanager\Admin::get_instance()' ];
        yield 'all.no_alias_class_references (use)' => [ 'all.no_alias_class_references', 'src/php/Services/Team_Service.php', $svc . 'use Racketmanager\Domain\User;' . "\n" . 'class Team_Service {}', 'import Racketmanager\Domain\User' ];
        yield 'all.no_alias_class_references (new)' => [ 'all.no_alias_class_references', 'templates/account.php', "<?php\nnamespace Racketmanager;\n\$u = new Domain\\Club( 1 );", 'new Racketmanager\Domain\Club' ];
        yield 'all.no_alias_class_references (instanceof)' => [ 'all.no_alias_class_references', 'functions.php', "<?php\nnamespace Racketmanager;\nfunction f( \$x ) { return \$x instanceof \\Racketmanager\\Domain\\Team; }", 'Racketmanager\Domain\Team' ];
        yield 'all.strict_types' => [ 'all.strict_types', 'src/php/Services/Team_Service.php', "<?php\nnamespace Racketmanager\\Services;\nclass Team_Service {}", 'missing declare(strict_types=1)' ];
        yield 'all.no_extract' => [ 'all.no_extract', 'src/php/Services/View/Php_View_Renderer.php', "<?php\nnamespace Racketmanager\\Services\\View;\n" . 'class R { function f( array $a ) { extract( $a ); } }', 'extract()' ];
        yield 'templates.no_globals_or_db ($wpdb)' => [ 'templates.no_globals_or_db', 'templates/x.php', "<?php\nnamespace Racketmanager;\nglobal \$wpdb;", '$wpdb' ];
        yield 'templates.no_globals_or_db ($_GET)' => [ 'templates.no_globals_or_db', 'templates/x.php', "<?php\nnamespace Racketmanager;\necho \$_GET['tab'];", '$_GET' ];
        yield 'templates.no_global_racketmanager' => [ 'templates.no_global_racketmanager', 'templates/x.php', "<?php\nnamespace Racketmanager;\nglobal \$racketmanager;", 'global $racketmanager' ];
        yield 'templates.no_legacy_helper_calls' => [ 'templates.no_legacy_helper_calls', 'templates/x.php', "<?php\nnamespace Racketmanager;\necho seo_url( 'x' );", 'Racketmanager\seo_url()' ];
        yield 'admin.no_template_require' => [ 'admin.no_template_require', 'src/php/Admin/Admin_League.php', "<?php\nnamespace Racketmanager\\Admin;\n" . 'class Admin_League { function f() { require_once RACKETMANAGER_PATH . "templates/admin/league/show.php"; } }', 'templates/admin/league/show.php' ];
        yield 'scanner.parse_error' => [ 'scanner.parse_error', 'src/php/Services/Broken.php', "<?php\nnamespace Racketmanager\\Services;\nclass Broken { function f( { }", 'parse error' ];
    }

    #[DataProvider( 'positive_cases' )]
    public function test_rule_detects_positive_case( string $rule_id, string $path, string $code, string $expected_detail ): void {
        $rule       = Layer_Rules::get( $rule_id );
        $file       = Source_Scanner::scan_source( $code, $path );
        $violations = new Rule_Evaluator()->evaluate( $rule, [ $path => $file ] );

        self::assertTrue( $rule->applies_to( $path ), "$rule_id should apply to $path" );
        self::assertNotEmpty( $violations, sprintf( 'Rule `%s` did not detect the offending snippet.', $rule_id ) );
        $details = array_map( static fn( Violation $v ): string => $v->detail, $violations );
        self::assertTrue(
            [] !== array_filter( $details, static fn( string $d ): bool => str_contains( $d, $expected_detail ) ),
            sprintf( 'Rule `%s` reported %s, expected a detail containing `%s`.', $rule_id, json_encode( $details ), $expected_detail )
        );
    }

    /**
     * @return iterable<string, array{0: string, 1: string, 2: string}>
     */
    public static function negative_cases(): iterable {
        $ns = "<?php\ndeclare( strict_types=1 );\nnamespace Racketmanager\\Domain\\Club;\n";
        $clean_entity = $ns . 'use Racketmanager\Domain\Club\Club_Hydration_DTO;' . "\n"
            . '/** $wpdb in a comment */ class Club { public function __construct( Club_Hydration_DTO $dto ) { $s = \'$_POST $wpdb\'; $this->extract(); } private function extract(): void {} }';

        foreach ( [ 'domain.no_wpdb', 'domain.no_outer_layer_imports', 'domain.no_superglobals', 'domain.no_wordpress_functions', 'domain.no_util_static', 'domain.no_untyped_constructor', 'application.no_global_racketmanager', 'all.no_alias_class_references', 'all.no_extract', 'all.strict_types', 'all.no_legacy_helper_calls', 'all.no_deprecated_helper_calls' ] as $id ) {
            yield "$id ignores clean entity" => [ $id, 'src/php/Domain/Club/Club.php', $clean_entity ];
        }

        yield 'domain.no_wordpress_functions ignores PHP built-ins and domain methods' => [ 'domain.no_wordpress_functions', 'src/php/Domain/Club/Club.php', $ns . 'class Club { function f( array $a ) { return [ get_class( $this ), array_map( "trim", $a ), is_array( $a ), current( $a ), date( "Y" ), $this->translate(), self::sanitize_name( "x" ) ]; } function translate() {} static function sanitize_name( $n ) {} }' ];
        yield 'application.no_presentation_imports allows Application and Domain' => [ 'application.no_presentation_imports', 'src/php/Application/Club/Handlers/H.php', "<?php\nnamespace Racketmanager\\Application\\Club\\Handlers;\nuse Racketmanager\\Application\\Club\\Queries\\Get_Club_Query;\nuse Racketmanager\\Domain\\Club\\Club;\nclass H { function f( Get_Club_Query \$q ): Club { return new Club(); } }" ];
        yield 'domain.no_outer_layer_imports allows Domain and Exceptions' => [ 'domain.no_outer_layer_imports', 'src/php/Domain/Club/Club.php', $ns . 'use Racketmanager\Domain\Team\Team; use Racketmanager\Exceptions\Not_Found_Exception; use Racketmanager\Domain\Ports\Clock_Interface;' . "\n" . 'class Club {}' ];
        yield 'application.no_concrete_repositories allows interfaces' => [ 'application.no_concrete_repositories', 'src/php/Application/Club/Handlers/H.php', "<?php\nnamespace Racketmanager\\Application\\Club\\Handlers;\nuse Racketmanager\\Repositories\\Interfaces\\Club_Repository_Interface;\nclass H { public function __construct( private Club_Repository_Interface \$r ) {} }" ];
        yield 'all.no_legacy_helper_calls ignores root \\seo_url and methods' => [ 'all.no_legacy_helper_calls', 'src/php/Services/X.php', "<?php\nnamespace Racketmanager\\Services;\nclass X { function f( \$o ) { return [ \\seo_url( 'x' ), \$o->get_league(), self::get_team() ]; } static function get_team() {} }" ];
        yield 'all.no_legacy_helper_calls does not apply to functions.php' => [ 'all.no_legacy_helper_calls', 'functions.php', "<?php\nnamespace Racketmanager;\nfunction get_league_team( \$id ) { return get_league( \$id ); }" ];
        yield 'all.no_get_instance_outside_bootstrap excludes the bootstrap' => [ 'all.no_get_instance_outside_bootstrap', 'src/php/Infrastructure/Wordpress/Bootstrap/Plugin_Bootstrap.php', "<?php\nnamespace Racketmanager\\Infrastructure\\Wordpress\\Bootstrap;\nuse Racketmanager\\RacketManager;\nclass Plugin_Bootstrap { static function launch() { return RacketManager::get_instance(); } }" ];
        yield 'all.no_alias_class_references allows the PSR-4 targets' => [ 'all.no_alias_class_references', 'src/php/Services/X.php', "<?php\nnamespace Racketmanager\\Services;\nuse Racketmanager\\Domain\\Club\\Club;\nuse Racketmanager\\Domain\\Player\\User;\nclass X { function f() { return [ new Club(), User::class ]; } }" ];
        yield 'domain.no_untyped_constructor excludes Domain/DTO' => [ 'domain.no_untyped_constructor', 'src/php/Domain/DTO/Club/Club_Hydration_DTO.php', "<?php\nnamespace Racketmanager\\Domain\\DTO\\Club;\nclass Club_Hydration_DTO { public function __construct( ?object \$raw = null ) {} }" ];
        yield 'admin.no_template_require ignores non-template includes' => [ 'admin.no_template_require', 'src/php/Admin/Admin_League.php', "<?php\nnamespace Racketmanager\\Admin;\nclass Admin_League { function f() { require_once RACKETMANAGER_PATH . 'functions.php'; } }" ];
        yield 'templates.no_globals_or_db ignores local variables' => [ 'templates.no_globals_or_db', 'templates/x.php', "<?php\nnamespace Racketmanager;\necho \$vm->title, \$post->ID;" ];
    }

    #[DataProvider( 'negative_cases' )]
    public function test_rule_ignores_clean_code( string $rule_id, string $path, string $code ): void {
        $rule       = Layer_Rules::get( $rule_id );
        $file       = Source_Scanner::scan_source( $code, $path );
        $violations = new Rule_Evaluator()->evaluate( $rule, [ $path => $file ] );

        self::assertNull( $file->parse_error, $file->parse_error ?? '' );
        self::assertSame( [], array_map( static fn( Violation $v ): string => $v->describe(), $violations ) );
    }

    public function test_wordpress_function_patterns_do_not_match_php_built_ins(): void {
        $matches = [];
        foreach ( Layer_Rules::WORDPRESS_FUNCTIONS as $pattern ) {
            foreach ( get_defined_functions()['internal'] as $function ) {
                if ( Name_Pattern::matches( $pattern, $function ) ) {
                    $matches[] = "$pattern -> $function";
                }
            }
        }

        self::assertSame( [], $matches, 'A WordPress-function pattern must never match a PHP built-in, or Domain code would be penalised for plain PHP.' );
    }

    public function test_every_catalogue_rule_has_a_positive_case(): void {
        $covered = [];
        foreach ( self::positive_cases() as $case ) {
            $covered[ $case[0] ] = true;
        }

        self::assertSame( [], array_values( array_diff( Layer_Rules::ids(), array_keys( $covered ) ) ), 'Every rule needs a positive detection case in this test.' );
    }

    public function test_for_prefix_and_provide_partition_the_catalogue(): void {
        $all      = Layer_Rules::ids();
        $provided = [];
        foreach ( [ [ 'domain.' ], [ 'application.' ], [ 'services.', 'repositories.' ], [ 'all.', 'templates.', 'admin.', 'scanner.' ] ] as $prefixes ) {
            foreach ( Layer_Rules::provide( ...$prefixes ) as $id => $args ) {
                self::assertArrayNotHasKey( $id, $provided, "Rule `$id` is provided by more than one prefix group." );
                self::assertSame( $id, $args[0]->id );
                $provided[ $id ] = true;
            }
        }

        self::assertSame( $all, array_keys( $provided ) );
        self::assertSame( [], Layer_Rules::for_prefix( 'nope.' ) );
    }

    public function test_catalogue_modes_match_the_audit(): void {
        $strict = array_keys( array_filter( Layer_Rules::all(), static fn( $rule ): bool => Rule_Mode::STRICT === $rule->mode ) );

        self::assertSame(
            [
                'domain.no_wpdb',
                'domain.no_outer_layer_imports',
                'domain.no_superglobals',
                'domain.no_wordpress_functions',
                'domain.no_untyped_constructor',
                'application.no_wpdb',
                'application.no_global_racketmanager',
                'application.no_concrete_repositories',
                'services.no_wpdb',
                'all.no_legacy_helper_calls',
                'all.no_deprecated_helper_calls',
                'all.no_alias_class_references',
                'all.strict_types',
                'templates.no_legacy_helper_calls',
                'scanner.parse_error',
            ],
            $strict
        );
    }
}
