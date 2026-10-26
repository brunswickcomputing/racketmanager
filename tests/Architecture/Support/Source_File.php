<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Architecture\Support;

/**
 * Facts extracted from one PHP file by Source_Scanner. All names are fully qualified without a
 * leading backslash. Every fact carries the 1-based line where it occurs.
 *
 * @phpstan-type Import           array{name: string, kind: string, line: int}
 * @phpstan-type Function_Call    array{name: string, fallback: ?string, line: int}
 * @phpstan-type Global_Access    array{name: string, via: string, line: int}
 * @phpstan-type Variable_Use     array{name: string, line: int}
 * @phpstan-type New_Target       array{class: string, line: int}
 * @phpstan-type Static_Call      array{class: string, method: ?string, line: int}
 * @phpstan-type Class_Reference  array{class: string, context: string, line: int}
 * @phpstan-type Constructor_Param array{class: ?string, param: string, type: ?string, types: array<int,string>, line: int}
 * @phpstan-type Include_Target   array{kind: string, target: string, line: int}
 */
final readonly class Source_File {
    /**
     * @param string                        $path               Plugin-root-relative path with forward slashes.
     * @param ?string                       $namespace          First namespace declared in the file, if any.
     * @param bool                          $strict_types       Whether `declare(strict_types=1)` is present.
     * @param ?string                       $parse_error        php-parser error message, if the file failed to parse.
     * @param array<int,Import>             $imports            `use` imports (kind: class|function|const).
     * @param array<int,Function_Call>      $function_calls     Function calls by name (not method calls). `name` is the
     *                                                          namespace-resolved name; `fallback` is the global name an
     *                                                          unqualified call inside a namespace would fall back to.
     * @param array<int,Global_Access>      $globals            `global $x;` statements (via=global) and `$GLOBALS['x']` (via=GLOBALS).
     * @param array<int,Variable_Use>       $variables          Every named variable read or written (excluding `$this`).
     * @param array<int,New_Target>         $new_targets        `new Class` targets with a static class name.
     * @param array<int,Static_Call>        $static_calls       `Class::method()` calls with a static class name.
     * @param array<int,Class_Reference>    $class_references   Every static class reference (import, new, static, instanceof, type …).
     * @param array<int,Constructor_Param>  $constructor_params Parameters of every `__construct`, with resolved leaf type names.
     * @param array<int,Include_Target>     $includes           include/require expressions with their literal path fragments.
     * @param array<int,string>             $declared_functions Fully qualified names of functions declared in the file.
     * @param array<int,string>             $declared_classes   Fully qualified names of classes/interfaces/traits/enums declared.
     */
    public function __construct(
        public string $path,
        public ?string $namespace = null,
        public bool $strict_types = false,
        public ?string $parse_error = null,
        public array $imports = [],
        public array $function_calls = [],
        public array $globals = [],
        public array $variables = [],
        public array $new_targets = [],
        public array $static_calls = [],
        public array $class_references = [],
        public array $constructor_params = [],
        public array $includes = [],
        public array $declared_functions = [],
        public array $declared_classes = [],
    ) {
    }

    public function has_parse_error(): bool {
        return null !== $this->parse_error;
    }

    /**
     * @return array<int,string>
     */
    public function function_call_names(): array {
        return array_values( array_unique( array_column( $this->function_calls, 'name' ) ) );
    }

    /**
     * @return array<int,string>
     */
    public function import_names(): array {
        return array_values( array_unique( array_column( $this->imports, 'name' ) ) );
    }

    /**
     * @return array<int,string>
     */
    public function variable_names(): array {
        return array_values( array_unique( array_column( $this->variables, 'name' ) ) );
    }
}
