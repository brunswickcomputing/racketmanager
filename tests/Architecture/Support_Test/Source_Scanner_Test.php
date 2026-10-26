<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Architecture\Support_Test;

use PHPUnit\Framework\TestCase;
use Racketmanager\Tests\Architecture\Support\Source_File;
use Racketmanager\Tests\Architecture\Support\Source_Scanner;

final class Source_Scanner_Test extends TestCase {
    private const string FIXTURES = 'tests/Fixtures/architecture';

    /** @var array<string, Source_File> */
    private static array $files = [];

    public static function setUpBeforeClass(): void {
        self::$files = Source_Scanner::scan( Source_Scanner::plugin_root() . '/' . self::FIXTURES );
    }

    private function file( string $name ): Source_File {
        $path = self::FIXTURES . '/' . $name;
        self::assertArrayHasKey( $path, self::$files, 'Fixture not scanned: ' . $path );

        return self::$files[ $path ];
    }

    public function test_scan_returns_plugin_relative_paths_sorted_without_parse_errors(): void {
        self::assertSame(
            [
                self::FIXTURES . '/clean_service.php',
                self::FIXTURES . '/globals_access.php',
                self::FIXTURES . '/procedural_template.php',
                self::FIXTURES . '/untyped_entity.php',
            ],
            array_keys( self::$files )
        );
        foreach ( self::$files as $file ) {
            self::assertFalse( $file->has_parse_error(), $file->path . ': ' . (string) $file->parse_error );
        }
    }

    public function test_scan_is_memoised_per_root(): void {
        $again = Source_Scanner::scan( Source_Scanner::plugin_root() . '/' . self::FIXTURES );

        self::assertSame( self::$files, $again );
    }

    public function test_wpdb_in_comments_and_strings_is_not_a_variable(): void {
        $entity = $this->file( 'untyped_entity.php' );

        self::assertNotContains( 'wpdb', $entity->variable_names() );
        self::assertNotContains( 'racketmanager', array_column( $entity->globals, 'name' ) );
        self::assertSame( [], $entity->globals );
    }

    public function test_use_function_import_resolves_helper_call(): void {
        $entity = $this->file( 'untyped_entity.php' );

        $calls = $this->calls_named( $entity, 'Racketmanager\get_league' );
        self::assertCount( 1, $calls );
        self::assertSame( 29, $calls[0]['line'] );
        self::assertNull( $calls[0]['fallback'], 'an imported function is fully resolved and has no global fallback' );

        self::assertContains(
            [ 'name' => 'Racketmanager\get_league', 'kind' => 'function', 'line' => 13 ],
            $entity->imports
        );
    }

    public function test_unqualified_wordpress_call_inside_namespace_keeps_global_fallback(): void {
        $entity = $this->file( 'untyped_entity.php' );

        $sanitize = array_values( array_filter( $entity->function_calls, static fn( array $call ): bool => 'sanitize_text_field' === $call['fallback'] ) );
        self::assertCount( 1, $sanitize );
        self::assertSame( 'Racketmanager\Tests\Fixtures\Architecture\sanitize_text_field', $sanitize[0]['name'] );
    }

    public function test_unqualified_helper_call_in_root_namespace_resolves_via_namespaced_name(): void {
        $template = $this->file( 'procedural_template.php' );

        $seo = $this->calls_named( $template, 'Racketmanager\seo_url' );
        self::assertCount( 1, $seo, 'only the unqualified seo_url() call resolves to the Racketmanager helper' );
        self::assertSame( 25, $seo[0]['line'] );
        self::assertSame( 'seo_url', $seo[0]['fallback'] );

        $root = $this->calls_named( $template, 'seo_url' );
        self::assertCount( 1, $root, 'the fully qualified \seo_url() call targets the global function' );
        self::assertSame( 26, $root[0]['line'] );
        self::assertNull( $root[0]['fallback'] );
    }

    public function test_group_use_imports_are_expanded_for_classes_and_functions(): void {
        $template = $this->file( 'procedural_template.php' );

        self::assertContains( 'Racketmanager\Repositories\Club_Repository', $template->import_names() );
        self::assertContains( 'Racketmanager\Repositories\Interfaces\Club_Repository_Interface', $template->import_names() );
        self::assertContains( 'Racketmanager\get_event', $template->import_names() );
        self::assertContains( 'Racketmanager\un_seo_url', $template->import_names() );

        self::assertCount( 1, $this->calls_named( $template, 'Racketmanager\get_event' ) );
        self::assertCount( 1, $this->calls_named( $template, 'Racketmanager\un_seo_url' ) );
    }

    public function test_method_calls_are_not_reported_as_function_calls(): void {
        $service = $this->file( 'clean_service.php' );

        self::assertNotContains( 'extract', array_column( $service->function_calls, 'fallback' ) );
        self::assertNotContains( 'extract', array_map( static fn( string $name ): string => strtolower( substr( strrchr( '\\' . $name, '\\' ), 1 ) ), $service->function_call_names() ) );
    }

    public function test_globals_superglobals_and_globals_array_are_recorded(): void {
        $template = $this->file( 'procedural_template.php' );

        self::assertContains( [ 'name' => 'racketmanager', 'via' => 'global', 'line' => 16 ], $template->globals );
        self::assertContains( [ 'name' => 'racketmanager', 'via' => 'GLOBALS', 'line' => 23 ], $template->globals );
        self::assertContains( '_GET', $template->variable_names() );
        self::assertContains( '_POST', $template->variable_names() );
        self::assertNotContains( 'this', $template->variable_names() );
    }

    public function test_new_static_and_instanceof_targets_are_resolved_class_references(): void {
        $template = $this->file( 'procedural_template.php' );

        self::assertContains( [ 'class' => 'Racketmanager\Repositories\Club_Repository', 'line' => 28 ], $template->new_targets );
        self::assertSame( [], $template->static_calls, 'a static call on a variable ($event::get_instance()) has no static class name' );

        $contexts = [];
        foreach ( $template->class_references as $reference ) {
            $contexts[ $reference['context'] ][] = $reference['class'];
        }
        self::assertContains( 'Racketmanager\Repositories\Club_Repository', $contexts['import'] );
        self::assertContains( 'Racketmanager\Repositories\Club_Repository', $contexts['new'] );
        self::assertContains( 'Racketmanager\Repositories\Interfaces\Club_Repository_Interface', $contexts['instanceof'] );
    }

    public function test_static_call_records_class_and_method(): void {
        $entity = $this->file( 'untyped_entity.php' );

        self::assertSame(
            [ [ 'class' => 'Racketmanager\Util\Util', 'method' => 'seo_url', 'line' => 30 ] ],
            $entity->static_calls
        );
    }

    public function test_type_declarations_catch_and_extends_are_class_references(): void {
        $service = $this->file( 'clean_service.php' );

        $by_context = [];
        foreach ( $service->class_references as $reference ) {
            $by_context[ $reference['context'] ][] = $reference['class'];
        }
        self::assertContains( 'Racketmanager\Repositories\Interfaces\Club_Repository_Interface', $by_context['type'] );
        self::assertContains( 'Racketmanager\Tests\Fixtures\Architecture\Typed\Club_Hydration_Data', $by_context['type'] );
        self::assertContains( 'RuntimeException', $by_context['catch'] );
        self::assertContains( 'InvalidArgumentException', $by_context['catch'] );
    }

    public function test_nullable_object_constructor_param_is_detected_with_leaf_types(): void {
        $entity = $this->file( 'untyped_entity.php' );

        self::assertSame(
            [
                [
                    'class' => 'Racketmanager\Tests\Fixtures\Architecture\Untyped_Entity',
                    'param' => 'data',
                    'type'  => '?object',
                    'types' => [ 'object' ],
                    'line'  => 18,
                ],
                [
                    'class' => 'Racketmanager\Tests\Fixtures\Architecture\Untyped_Entity',
                    'param' => 'flag',
                    'type'  => 'int|string|null',
                    'types' => [ 'int', 'string', 'null' ],
                    'line'  => 18,
                ],
            ],
            $entity->constructor_params
        );
    }

    public function test_typed_constructor_params_resolve_class_names(): void {
        $service = $this->file( 'clean_service.php' );

        self::assertSame( 'Racketmanager\Repositories\Interfaces\Club_Repository_Interface', $service->constructor_params[0]['type'] );
        self::assertSame( [ 'Racketmanager\Tests\Fixtures\Architecture\Typed\Club_Hydration_Data', 'int' ], $service->constructor_params[1]['types'] );
    }

    public function test_strict_types_declaration_is_detected(): void {
        self::assertTrue( $this->file( 'clean_service.php' )->strict_types );
        self::assertFalse( $this->file( 'untyped_entity.php' )->strict_types );
        self::assertFalse( $this->file( 'procedural_template.php' )->strict_types );
    }

    public function test_bom_and_leading_whitespace_do_not_hide_strict_types(): void {
        $code = "\xEF\xBB\xBF\n<?php\n\ndeclare( strict_types=1 );\n\nnamespace Foo;\n\nfinal class Bar { public function __construct( ?object \$raw ) {} }\n";

        $file = Source_Scanner::scan_source( $code, 'in-memory/bom.php' );

        self::assertFalse( $file->has_parse_error(), (string) $file->parse_error );
        self::assertTrue( $file->strict_types );
        self::assertSame( 'Foo', $file->namespace );
        self::assertSame( [ 'Foo\Bar' ], $file->declared_classes );
        self::assertSame( 8, $file->constructor_params[0]['line'], 'line numbers are preserved when leading whitespace is normalised' );
    }

    public function test_strict_types_zero_does_not_count(): void {
        $file = Source_Scanner::scan_source( "<?php\ndeclare(strict_types=0);\n", 'in-memory/zero.php' );

        self::assertFalse( $file->strict_types );
    }

    public function test_includes_record_kind_and_literal_path_fragments(): void {
        $template = $this->file( 'procedural_template.php' );

        self::assertSame(
            [
                [ 'kind' => 'require_once', 'target' => 'templates/admin/includes/.php', 'line' => 31 ],
                [ 'kind' => 'include_once', 'target' => 'templates/includes/.php', 'line' => 32 ],
            ],
            $template->includes
        );
    }

    public function test_namespace_and_declarations_are_recorded(): void {
        $template = $this->file( 'procedural_template.php' );
        $entity   = $this->file( 'untyped_entity.php' );

        self::assertSame( 'Racketmanager', $template->namespace );
        self::assertSame( [], $template->declared_classes );
        self::assertSame( 'Racketmanager\Tests\Fixtures\Architecture', $entity->namespace );
        self::assertSame( [ 'Racketmanager\Tests\Fixtures\Architecture\Untyped_Entity' ], $entity->declared_classes );
    }

    public function test_declared_functions_are_namespaced(): void {
        $file = Source_Scanner::scan_source( "<?php\nnamespace Racketmanager;\nfunction get_league( int \$id ) { return get_league_query_service()->find( \$id ); }\n", 'in-memory/functions.php' );

        self::assertSame( [ 'Racketmanager\get_league' ], $file->declared_functions );
        self::assertCount( 1, $this->calls_named( $file, 'Racketmanager\get_league_query_service' ) );
    }

    public function test_html_only_template_and_early_return_parse_cleanly(): void {
        $html = Source_Scanner::scan_source( "<div>no php here</div>\n", 'in-memory/html.php' );
        $early = Source_Scanner::scan_source( "<?php\nif ( empty( \$vm ) ) {\n    return;\n}\n?>\n<p><?php echo \$vm->title; ?></p>\n", 'in-memory/early.php' );

        self::assertFalse( $html->has_parse_error() );
        self::assertFalse( $early->has_parse_error() );
        self::assertSame( [ 'vm' ], $early->variable_names() );
    }

    public function test_parse_error_is_recorded_instead_of_thrown(): void {
        $file = Source_Scanner::scan_source( "<?php\nclass {\n", 'in-memory/broken.php' );

        self::assertTrue( $file->has_parse_error() );
        self::assertStringContainsString( 'Syntax error', (string) $file->parse_error );
        self::assertSame( [], $file->variables );
    }

    public function test_plugin_scan_covers_src_templates_and_root_helper_files(): void {
        $files = Source_Scanner::scan_plugin();

        self::assertArrayHasKey( 'functions.php', $files );
        self::assertArrayHasKey( 'template-tags.php', $files );
        self::assertArrayHasKey( 'src/php/RacketManager.php', $files );
        self::assertNotEmpty( array_filter( array_keys( $files ), static fn( string $path ): bool => str_starts_with( $path, 'templates/' ) ) );
        self::assertNotEmpty( array_filter( array_keys( $files ), static fn( string $path ): bool => str_starts_with( $path, 'src/php/Domain/' ) ) );
        self::assertSame( [], array_filter( array_keys( $files ), static fn( string $path ): bool => str_starts_with( $path, 'tests/' ) ) );
        self::assertSame( [], array_filter( $files, static fn( Source_File $file ): bool => $file->has_parse_error() ), 'every plugin PHP file must parse' );
    }

    /**
     * @return array<int, array{name: string, fallback: ?string, line: int}>
     */
    private function calls_named( Source_File $file, string $name ): array {
        return array_values( array_filter( $file->function_calls, static fn( array $call ): bool => $call['name'] === $name ) );
    }
}
