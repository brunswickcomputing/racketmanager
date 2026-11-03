<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Architecture\Support_Test;

use PHPUnit\Framework\TestCase;
use Racketmanager\Tests\Architecture\Support\Layer_Rule;
use Racketmanager\Tests\Architecture\Support\Rule_Evaluator;
use Racketmanager\Tests\Architecture\Support\Rule_Kind;
use Racketmanager\Tests\Architecture\Support\Rule_Mode;
use Racketmanager\Tests\Architecture\Support\Source_File;
use Racketmanager\Tests\Architecture\Support\Source_Scanner;
use Racketmanager\Tests\Architecture\Support\Violation;

final class Rule_Evaluator_Test extends TestCase {
    private const string FIXTURES = 'tests/Fixtures/architecture/';

    /** @var array<string, Source_File> */
    private static array $files = [];

    public static function setUpBeforeClass(): void {
        Source_Scanner::reset_cache();
        self::$files = Source_Scanner::scan( Source_Scanner::plugin_root() . '/' . rtrim( self::FIXTURES, '/' ) );
    }

    /**
     * @param array<mixed>      $arguments
     * @param array<int,string> $prefixes
     * @param array<int,string> $excludes
     */
    private function rule( Rule_Kind $kind, array $arguments, array $prefixes = [ self::FIXTURES ], array $excludes = [] ): Layer_Rule {
        return new Layer_Rule( 'test.' . $kind->value, 'test rule', 'fix it', $prefixes, $kind, $arguments, Rule_Mode::RATCHET, $excludes );
    }

    /**
     * @return array<int,string>
     */
    private function describe( array $violations ): array {
        return array_map( static fn( Violation $violation ): string => $violation->describe(), $violations );
    }

    public function test_forbidden_variable_ignores_comments_and_strings(): void {
        $rule = $this->rule( Rule_Kind::FORBIDDEN_VARIABLE, [ 'wpdb' ], [ self::FIXTURES . 'untyped_entity.php' ] );
        $violations = ( new Rule_Evaluator() )->evaluate( $rule, self::$files );

        self::assertSame( [], $violations );
    }

    public function test_forbidden_variable_reports_each_superglobal_read(): void {
        $violations = ( new Rule_Evaluator() )->evaluate( $this->rule( Rule_Kind::FORBIDDEN_VARIABLE, [ '$_GET', '_POST', '_REQUEST' ] ), self::$files );

        self::assertSame(
            [
                self::FIXTURES . 'globals_access.php:4 — $_POST',
                self::FIXTURES . 'procedural_template.php:22 — $_GET',
                self::FIXTURES . 'procedural_template.php:24 — $_POST',
            ],
            $this->describe( $violations )
        );
    }

    public function test_forbidden_variable_detects_globals_wpdb(): void {
        $violations = ( new Rule_Evaluator() )->evaluate( $this->rule( Rule_Kind::FORBIDDEN_VARIABLE, [ 'wpdb', '_POST' ] ), self::$files );

        self::assertSame(
            [
                self::FIXTURES . 'globals_access.php:3 — $wpdb',
                self::FIXTURES . 'globals_access.php:4 — $_POST',
                self::FIXTURES . 'procedural_template.php:24 — $_POST',
            ],
            $this->describe( $violations )
        );
    }

    public function test_forbidden_global_covers_global_statement_and_globals_array(): void {
        $violations = ( new Rule_Evaluator() )->evaluate( $this->rule( Rule_Kind::FORBIDDEN_GLOBAL, [ 'racketmanager' ] ), self::$files );

        self::assertSame(
            [
                self::FIXTURES . 'procedural_template.php:16 — global $racketmanager',
                self::FIXTURES . "procedural_template.php:23 — \$GLOBALS['racketmanager']",
            ],
            $this->describe( $violations )
        );
    }

    public function test_forbidden_function_call_resolves_use_function_and_root_namespace_calls(): void {
        $rule = $this->rule( Rule_Kind::FORBIDDEN_FUNCTION_CALL, [ 'Racketmanager\get_league', 'Racketmanager\get_event', 'Racketmanager\seo_url', 'Racketmanager\un_seo_url' ] );

        $violations = ( new Rule_Evaluator() )->evaluate( $rule, self::$files );

        self::assertSame(
            [
                self::FIXTURES . 'procedural_template.php:24 — Racketmanager\get_event()',
                self::FIXTURES . 'procedural_template.php:25 — Racketmanager\seo_url()',
                self::FIXTURES . 'procedural_template.php:27 — Racketmanager\un_seo_url()',
                self::FIXTURES . 'untyped_entity.php:29 — Racketmanager\get_league()',
            ],
            $this->describe( $violations ),
            'the root-namespace \seo_url() call on line 26 must not match the Racketmanager helper'
        );
    }

    public function test_forbidden_function_call_matches_wordpress_functions_via_global_fallback(): void {
        $rule = $this->rule( Rule_Kind::FORBIDDEN_FUNCTION_CALL, [ 'sanitize_text_field', 'esc_*' ] );

        $violations = ( new Rule_Evaluator() )->evaluate( $rule, self::$files );

        self::assertSame(
            [
                self::FIXTURES . 'procedural_template.php:34 — esc_attr()',
                self::FIXTURES . 'procedural_template.php:35 — esc_html()',
                self::FIXTURES . 'untyped_entity.php:30 — sanitize_text_field()',
            ],
            $this->describe( $violations )
        );
    }

    public function test_forbidden_function_call_does_not_match_same_named_methods(): void {
        $violations = ( new Rule_Evaluator() )->evaluate( $this->rule( Rule_Kind::FORBIDDEN_FUNCTION_CALL, [ 'extract' ] ), self::$files );

        self::assertSame( [], $violations, '$this->extract() in clean_service.php is a method call' );
    }

    public function test_forbidden_function_call_supports_glob_and_regex_patterns(): void {
        $glob  = $this->rule( Rule_Kind::FORBIDDEN_FUNCTION_CALL, [ 'Racketmanager\get_*' ] );
        $regex = $this->rule( Rule_Kind::FORBIDDEN_FUNCTION_CALL, [ '/^Racketmanager\\\\(un_)?seo_url$/' ] );

        self::assertCount( 2, ( new Rule_Evaluator() )->evaluate( $glob, self::$files ) );
        self::assertCount( 2, ( new Rule_Evaluator() )->evaluate( $regex, self::$files ) );
    }

    public function test_forbidden_namespace_import_matches_resolved_imports_including_group_use(): void {
        $rule = $this->rule( Rule_Kind::FORBIDDEN_NAMESPACE_IMPORT, [ 'Racketmanager\Repositories\*' ] );

        $violations = ( new Rule_Evaluator() )->evaluate( $rule, self::$files );

        self::assertSame(
            [
                self::FIXTURES . 'clean_service.php:11 — use Racketmanager\Repositories\Interfaces\Club_Repository_Interface',
                self::FIXTURES . 'procedural_template.php:13 — use Racketmanager\Repositories\Club_Repository',
                self::FIXTURES . 'procedural_template.php:13 — use Racketmanager\Repositories\Interfaces\Club_Repository_Interface',
                self::FIXTURES . 'untyped_entity.php:12 — use Racketmanager\Repositories\Club_Repository',
            ],
            $this->describe( $violations )
        );
    }

    public function test_regex_pattern_can_exclude_interfaces_from_a_namespace(): void {
        $rule = $this->rule( Rule_Kind::FORBIDDEN_NAMESPACE_IMPORT, [ '/^Racketmanager\\\\Repositories\\\\(?!Interfaces\\\\)/' ] );

        $violations = ( new Rule_Evaluator() )->evaluate( $rule, self::$files );

        self::assertSame(
            [
                self::FIXTURES . 'procedural_template.php:13 — use Racketmanager\Repositories\Club_Repository',
                self::FIXTURES . 'untyped_entity.php:12 — use Racketmanager\Repositories\Club_Repository',
            ],
            $this->describe( $violations )
        );
    }

    public function test_forbidden_static_call_filters_by_class_and_optionally_method(): void {
        $any_method = $this->rule( Rule_Kind::FORBIDDEN_STATIC_CALL, [ 'Racketmanager\Util\Util', 'Racketmanager\Util\Util_Lookup' ] );
        $one_method = $this->rule( Rule_Kind::FORBIDDEN_STATIC_CALL, [ 'classes' => [ 'Racketmanager\Util\*' ], 'methods' => [ 'get_instance' ] ] );

        self::assertSame(
            [ self::FIXTURES . 'untyped_entity.php:30 — Racketmanager\Util\Util::seo_url()' ],
            $this->describe( ( new Rule_Evaluator() )->evaluate( $any_method, self::$files ) )
        );
        self::assertSame( [], ( new Rule_Evaluator() )->evaluate( $one_method, self::$files ) );
    }

    public function test_forbidden_new_and_class_reference(): void {
        $new       = $this->rule( Rule_Kind::FORBIDDEN_NEW, [ 'Racketmanager\Repositories\*' ] );
        $reference = $this->rule( Rule_Kind::FORBIDDEN_CLASS_REFERENCE, [ 'Racketmanager\Repositories\Club_Repository' ] );

        self::assertSame(
            [
                self::FIXTURES . 'procedural_template.php:28 — new Racketmanager\Repositories\Club_Repository',
                self::FIXTURES . 'untyped_entity.php:36 — new Racketmanager\Repositories\Club_Repository',
            ],
            $this->describe( ( new Rule_Evaluator() )->evaluate( $new, self::$files ) )
        );
        self::assertSame(
            [
                self::FIXTURES . 'procedural_template.php:13 — import Racketmanager\Repositories\Club_Repository',
                self::FIXTURES . 'procedural_template.php:28 — new Racketmanager\Repositories\Club_Repository',
                self::FIXTURES . 'untyped_entity.php:12 — import Racketmanager\Repositories\Club_Repository',
                self::FIXTURES . 'untyped_entity.php:35 — type Racketmanager\Repositories\Club_Repository',
                self::FIXTURES . 'untyped_entity.php:36 — new Racketmanager\Repositories\Club_Repository',
            ],
            $this->describe( ( new Rule_Evaluator() )->evaluate( $reference, self::$files ) )
        );
    }

    public function test_forbidden_dependency_reports_each_class_once_per_file_at_first_occurrence(): void {
        $rule = $this->rule( Rule_Kind::FORBIDDEN_DEPENDENCY, [ '/^Racketmanager\\\\Repositories\\\\(?!Interfaces\\\\)/' ] );

        self::assertSame(
            [
                self::FIXTURES . 'procedural_template.php:13 — depends on Racketmanager\Repositories\Club_Repository',
                self::FIXTURES . 'untyped_entity.php:12 — depends on Racketmanager\Repositories\Club_Repository',
            ],
            $this->describe( ( new Rule_Evaluator() )->evaluate( $rule, self::$files ) ),
            'import + type hint + new of the same class collapse into one dependency violation'
        );

        $no_import = Source_Scanner::scan_source(
            "<?php\nnamespace Racketmanager\\Application\\X;\nclass H { function f( \\Racketmanager\\Presentation\\Presenters\\P \$p ) { return new \\Racketmanager\\Presentation\\Presenters\\P(); } }",
            'src/php/Application/X/H.php'
        );
        $fq_rule   = $this->rule( Rule_Kind::FORBIDDEN_DEPENDENCY, [ 'Racketmanager\Presentation\*' ], [ 'src/php/Application/' ] );

        self::assertSame(
            [ 'src/php/Application/X/H.php:3 — depends on Racketmanager\Presentation\Presenters\P' ],
            $this->describe( ( new Rule_Evaluator() )->evaluate( $fq_rule, [ $no_import->path => $no_import ] ) ),
            'a fully-qualified reference without a use statement is still a dependency'
        );
    }

    public function test_require_strict_types_reports_files_without_declaration(): void {
        $violations = ( new Rule_Evaluator() )->evaluate( $this->rule( Rule_Kind::REQUIRE_STRICT_TYPES, [] ), self::$files );

        self::assertSame(
            [
                self::FIXTURES . 'procedural_template.php:1 — missing declare(strict_types=1)',
                self::FIXTURES . 'untyped_entity.php:1 — missing declare(strict_types=1)',
            ],
            $this->describe( $violations )
        );
    }

    public function test_forbidden_constructor_param_type_detects_nullable_object(): void {
        $violations = ( new Rule_Evaluator() )->evaluate( $this->rule( Rule_Kind::FORBIDDEN_CONSTRUCTOR_PARAM_TYPE, [ 'object', 'stdClass' ] ), self::$files );

        self::assertSame(
            [ self::FIXTURES . 'untyped_entity.php:18 — Racketmanager\Tests\Fixtures\Architecture\Untyped_Entity::__construct($data: ?object)' ],
            $this->describe( $violations ),
            'typed constructor params in clean_service.php must not be reported'
        );
    }

    public function test_forbidden_include_matches_literal_path_fragments(): void {
        $violations = ( new Rule_Evaluator() )->evaluate( $this->rule( Rule_Kind::FORBIDDEN_INCLUDE, [ '*templates/admin/*' ] ), self::$files );

        self::assertSame(
            [ self::FIXTURES . 'procedural_template.php:31 — require_once templates/admin/includes/.php' ],
            $this->describe( $violations )
        );
    }

    public function test_forbidden_class_reference_contexts_filter_restricts_to_listed_contexts(): void {
        $code = "<?php\nnamespace Racketmanager\\Admin;\nuse Racketmanager\\Admin\\Admin_Display;\nfinal class Admin_Result extends Admin_Display {\n    public function f( Admin_Display \$d ): bool { return \$d instanceof Admin_Display; }\n}\n";
        $file = Source_Scanner::scan_source( $code, 'src/php/Admin/Admin_Result.php' );
        $files = [ $file->path => $file ];

        $extends_only = $this->rule( Rule_Kind::FORBIDDEN_CLASS_REFERENCE, [ 'classes' => [ 'Racketmanager\Admin\Admin_Display' ], 'contexts' => [ 'extends' ] ], [ 'src/php/Admin/' ] );
        $keyed_any    = $this->rule( Rule_Kind::FORBIDDEN_CLASS_REFERENCE, [ 'classes' => [ 'Racketmanager\Admin\Admin_Display' ] ], [ 'src/php/Admin/' ] );
        $plain_list   = $this->rule( Rule_Kind::FORBIDDEN_CLASS_REFERENCE, [ 'Racketmanager\Admin\Admin_Display' ], [ 'src/php/Admin/' ] );

        self::assertSame(
            [ 'src/php/Admin/Admin_Result.php:4 — extends Racketmanager\Admin\Admin_Display' ],
            $this->describe( ( new Rule_Evaluator() )->evaluate( $extends_only, $files ) ),
            'import, type and instanceof references are filtered out'
        );
        self::assertCount( 4, ( new Rule_Evaluator() )->evaluate( $keyed_any, $files ), 'keyed form without contexts matches every context' );
        self::assertCount( 4, ( new Rule_Evaluator() )->evaluate( $plain_list, $files ), 'the plain-list form is unchanged' );
    }

    public function test_forbidden_fallback_new_matches_collaborator_patterns_only(): void {
        $pattern = [ '/(_Service|_Repository|_Presenter|_Handler)$/' ];
        $rule    = $this->rule( Rule_Kind::FORBIDDEN_FALLBACK_NEW, $pattern );

        self::assertSame(
            [
                self::FIXTURES . 'container_consumer.php:34 — ?? new Racketmanager\Services\Team_Service',
                self::FIXTURES . 'container_consumer.php:39 — ?? new Racketmanager\Services\Team_Service',
                self::FIXTURES . 'container_consumer.php:40 — ?? new Racketmanager\Services\Team_Service',
            ],
            $this->describe( ( new Rule_Evaluator() )->evaluate( $rule, self::$files ) ),
            '`??=` is reported as `?? new`; `?? new DateTimeImmutable()`, `?? new Money()` and `?: new` are not violations'
        );
    }

    public function test_forbidden_fallback_new_is_not_triggered_by_plain_new(): void {
        $rule = $this->rule( Rule_Kind::FORBIDDEN_FALLBACK_NEW, [ 'Racketmanager\Repositories\*' ] );

        self::assertSame( [], ( new Rule_Evaluator() )->evaluate( $rule, self::$files ), 'the plain `new Club_Repository()` sites in the fixtures are not fallbacks' );
    }

    public function test_forbidden_public_mutable_property_exempts_readonly_static_and_restricted_set(): void {
        $rule = $this->rule( Rule_Kind::FORBIDDEN_PUBLIC_MUTABLE_PROPERTY, [] );

        self::assertSame(
            [
                self::FIXTURES . 'container_consumer.php:17 — Container_Consumer::$count',
                self::FIXTURES . 'container_consumer.php:20 — Container_Consumer::$legacy',
                self::FIXTURES . 'container_consumer.php:20 — Container_Consumer::$other',
                self::FIXTURES . 'container_consumer.php:25 — Container_Consumer::$injected',
                self::FIXTURES . 'untyped_entity.php:16 — Untyped_Entity::$id',
            ],
            $this->describe( ( new Rule_Evaluator() )->evaluate( $rule, self::$files ) ),
            'public readonly, public static and promoted public readonly are exempt; private promoted params are not public'
        );

        $code = "<?php\nnamespace Racketmanager\\Domain\\Sample;\nfinal readonly class Frozen { public int \$year; }\nfinal class Guarded { public private(set) int \$guarded = 0; public function __construct( public protected(set) int \$p = 0, public int \$q = 0 ) {} }\n\$anon = new class { public int \$z = 1; };\n";
        $file = Source_Scanner::scan_source( $code, 'src/php/Domain/Sample/Frozen.php' );

        self::assertSame(
            [
                'src/php/Domain/Sample/Frozen.php:4 — Guarded::$q',
                'src/php/Domain/Sample/Frozen.php:5 — {anonymous}::$z',
            ],
            $this->describe( ( new Rule_Evaluator() )->evaluate( $this->rule( Rule_Kind::FORBIDDEN_PUBLIC_MUTABLE_PROPERTY, [], [ 'src/php/Domain/' ] ), [ $file->path => $file ] ) ),
            'readonly classes and private(set)/protected(set) properties are exempt'
        );
    }

    public function test_forbidden_literal_container_key_is_receiver_and_method_scoped(): void {
        $scoped = $this->rule(
            Rule_Kind::FORBIDDEN_LITERAL_CONTAINER_KEY,
            [
                'receivers' => [ '$c', '$container', '$this->container', '*->container' ],
                'methods'   => [ 'get', 'set', 'has', 'alias', 'bind' ],
            ]
        );

        self::assertSame(
            [
                self::FIXTURES . "container_consumer.php:48 — \$c->set('league_service')",
                self::FIXTURES . "container_consumer.php:50 — \$this->container->get('team_service')",
                self::FIXTURES . "container_consumer.php:51 — \$this->plugin->container->get('club_service')",
                self::FIXTURES . "container_consumer.php:53 — \$c->has('nullsafe_key')",
            ],
            $this->describe( ( new Rule_Evaluator() )->evaluate( $scoped, self::$files ) ),
            '$request->get(\'id\') and $c->get( Team_Service::class ) are not container-key lookups'
        );

        $methods_only = $this->rule( Rule_Kind::FORBIDDEN_LITERAL_CONTAINER_KEY, [ 'methods' => [ 'set' ] ] );
        self::assertSame(
            [ self::FIXTURES . "container_consumer.php:48 — \$c->set('league_service')" ],
            $this->describe( ( new Rule_Evaluator() )->evaluate( $methods_only, self::$files ) ),
            'an omitted receivers list matches any receiver'
        );

        $receiver_only = $this->rule( Rule_Kind::FORBIDDEN_LITERAL_CONTAINER_KEY, [ 'receivers' => [ '$request' ] ] );
        self::assertSame(
            [ self::FIXTURES . "container_consumer.php:52 — \$request->get('id')" ],
            $this->describe( ( new Rule_Evaluator() )->evaluate( $receiver_only, self::$files ) ),
            'an omitted methods list matches any method'
        );
    }

    public function test_require_variable_reports_each_missing_variable_once_at_line_one(): void {
        $with_vm    = Source_Scanner::scan_source( "<?php\nif ( empty( \$vm ) ) {\n    return;\n}\n?>\n<p><?php echo \$vm->title; ?></p>\n", 'templates/with_vm.php' );
        $without_vm = Source_Scanner::scan_source( "<?php\n\$league = \$GLOBALS['league'];\n?>\n<p><?php echo esc_html( \$league->title ); ?></p>\n", 'templates/without_vm.php' );
        $html_only  = Source_Scanner::scan_source( "<div class=\"vm\">\$vm in text only</div>\n", 'templates/html_only.php' );
        $files      = [ $with_vm->path => $with_vm, $without_vm->path => $without_vm, $html_only->path => $html_only ];

        $rule = $this->rule( Rule_Kind::REQUIRE_VARIABLE, [ '$vm' ], [ 'templates/' ] );

        self::assertSame(
            [
                'templates/html_only.php:1 — missing $vm',
                'templates/without_vm.php:1 — missing $vm',
            ],
            $this->describe( ( new Rule_Evaluator() )->evaluate( $rule, $files ) ),
            'a `$vm` mention in inline HTML is not a variable use'
        );

        $two = $this->rule( Rule_Kind::REQUIRE_VARIABLE, [ 'vm', 'league' ], [ 'templates/' ] );
        self::assertSame(
            [
                'templates/html_only.php:1 — missing $league',
                'templates/html_only.php:1 — missing $vm',
                'templates/with_vm.php:1 — missing $league',
                'templates/without_vm.php:1 — missing $vm',
            ],
            $this->describe( ( new Rule_Evaluator() )->evaluate( $two, $files ) ),
            'one violation per missing variable, sorted by file then detail'
        );
    }

    public function test_path_prefixes_and_excludes_scope_the_rule(): void {
        $evaluator = new Rule_Evaluator();
        $scoped    = $this->rule( Rule_Kind::REQUIRE_STRICT_TYPES, [], [ self::FIXTURES . 'untyped_' ] );
        $excluded  = $this->rule( Rule_Kind::REQUIRE_STRICT_TYPES, [], [ self::FIXTURES ], [ self::FIXTURES . 'untyped_entity.php' ] );
        $elsewhere = $this->rule( Rule_Kind::REQUIRE_STRICT_TYPES, [], [ 'src/php/Domain/' ] );

        self::assertSame( [ self::FIXTURES . 'untyped_entity.php:1 — missing declare(strict_types=1)' ], $this->describe( $evaluator->evaluate( $scoped, self::$files ) ) );
        self::assertSame( [ self::FIXTURES . 'procedural_template.php:1 — missing declare(strict_types=1)' ], $this->describe( $evaluator->evaluate( $excluded, self::$files ) ) );
        self::assertSame( [], $evaluator->evaluate( $elsewhere, self::$files ) );
    }

    public function test_parse_errors_are_reported_only_by_the_parse_error_rule(): void {
        $broken = Source_Scanner::scan_source( "<?php\nnamespace Racketmanager;\nglobal \$wpdb\nclass {\n", 'src/php/Broken.php' );
        $files  = [ $broken->path => $broken ];

        $parse_rule = $this->rule( Rule_Kind::NO_PARSE_ERRORS, [], [ 'src/php/' ] );
        $wpdb_rule  = $this->rule( Rule_Kind::FORBIDDEN_VARIABLE, [ 'wpdb' ], [ 'src/php/' ] );

        $parse_violations = ( new Rule_Evaluator() )->evaluate( $parse_rule, $files );
        self::assertCount( 1, $parse_violations );
        self::assertStringStartsWith( 'src/php/Broken.php:1 — parse error: Syntax error', $parse_violations[0]->describe() );
        self::assertSame( [], ( new Rule_Evaluator() )->evaluate( $wpdb_rule, $files ) );
    }

    public function test_violations_carry_rule_id_and_line_independent_keys(): void {
        $rule       = $this->rule( Rule_Kind::FORBIDDEN_FUNCTION_CALL, [ 'Racketmanager\seo_url' ] );
        $violations = ( new Rule_Evaluator() )->evaluate( $rule, self::$files );

        self::assertCount( 1, $violations );
        self::assertSame( $rule->id, $violations[0]->rule_id );
        self::assertSame( self::FIXTURES . 'procedural_template.php|Racketmanager\seo_url()', $violations[0]->key() );
        self::assertSame( $rule->id . '|' . self::FIXTURES . 'procedural_template.php|Racketmanager\seo_url()', $violations[0]->full_key() );
    }
}
