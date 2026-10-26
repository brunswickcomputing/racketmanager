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
