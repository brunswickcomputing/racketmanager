<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Architecture\Support;

/**
 * The kind of fact a Layer_Rule evaluates. Each kind reads one fact list from Source_File
 * and interprets the rule arguments as described below.
 */
enum Rule_Kind: string {
    /** Arguments: name patterns matched against resolved `use` imports (class, function and const). */
    case FORBIDDEN_NAMESPACE_IMPORT = 'forbidden_namespace_import';

    /**
     * Arguments: name patterns matched against resolved function call names. A call matches when the
     * pattern matches either its namespace-resolved name (e.g. `Racketmanager\get_league`) or its
     * global fallback name (e.g. `sanitize_text_field` for an unqualified call inside a namespace).
     */
    case FORBIDDEN_FUNCTION_CALL = 'forbidden_function_call';

    /** Arguments: variable names (without `$`) used in `global $x;` statements or `$GLOBALS['x']`. */
    case FORBIDDEN_GLOBAL = 'forbidden_global';

    /** Arguments: variable names (without `$`), e.g. `wpdb`, `_POST`, `_GET`. */
    case FORBIDDEN_VARIABLE = 'forbidden_variable';

    /**
     * Arguments: either a list of class patterns, or `['classes' => [...], 'methods' => [...]]`
     * where both lists are patterns; an omitted `methods` list matches any method.
     */
    case FORBIDDEN_STATIC_CALL = 'forbidden_static_call';

    /** Arguments: class patterns matched against `new` targets. */
    case FORBIDDEN_NEW = 'forbidden_new';

    /**
     * Arguments: class patterns matched against every resolved class reference (imports, `new`,
     * static calls, `::class`/constants, `instanceof`, extends/implements, type declarations, catch).
     * Alternatively `['classes' => [...], 'contexts' => [...]]` restricts the match to the listed
     * reference contexts (`import`, `new`, `static`, `instanceof`, `extends`, `implements`, `type`,
     * `catch`, `trait_use`); an omitted `contexts` list matches every context.
     */
    case FORBIDDEN_CLASS_REFERENCE = 'forbidden_class_reference';

    /**
     * Arguments: class patterns, as for FORBIDDEN_CLASS_REFERENCE, but reported once per distinct class
     * a file references (first occurrence wins). Use it for layer-inversion rules: the violation is
     * "this file depends on that class", however the dependency is spelled (`use`, fully-qualified
     * `new`, type hint, static call, `instanceof`).
     */
    case FORBIDDEN_DEPENDENCY = 'forbidden_dependency';

    /** No arguments: the file must start with `declare(strict_types=1)`. */
    case REQUIRE_STRICT_TYPES = 'require_strict_types';

    /** Arguments: type names (e.g. `object`, `stdClass`) forbidden on `__construct` parameters. */
    case FORBIDDEN_CONSTRUCTOR_PARAM_TYPE = 'forbidden_constructor_param_type';

    /** Arguments: patterns matched against the literal parts of `include`/`require` targets. */
    case FORBIDDEN_INCLUDE = 'forbidden_include';

    /** No arguments: the file must parse without errors. */
    case NO_PARSE_ERRORS = 'no_parse_errors';

    /**
     * Arguments: class patterns matched against the `new` target of `$x ?? new Class` and
     * `$x ??= new Class`. Both spellings are reported with the detail `?? new Class`.
     */
    case FORBIDDEN_FALLBACK_NEW = 'forbidden_fallback_new';

    /**
     * No arguments (the rule scope does the work): every public property that can be written from
     * outside the class — declared or constructor-promoted — is a violation. `readonly` properties,
     * properties of a `readonly class`, `private(set)` / `protected(set)` properties and `static`
     * properties are exempt. Detail: `Class::$property` (short class name).
     */
    case FORBIDDEN_PUBLIC_MUTABLE_PROPERTY = 'forbidden_public_mutable_property';

    /**
     * Arguments: `['receivers' => [...], 'methods' => [...]]`, both pattern lists matched against the
     * rendered receiver (e.g. `$c`, `$this->container`, `*->container`) and the method name of an
     * instance method call whose first argument is a string literal. An omitted or empty list matches
     * anything. Detail: `$c->set('league_service')`.
     */
    case FORBIDDEN_LITERAL_CONTAINER_KEY = 'forbidden_literal_container_key';

    /**
     * Arguments: variable names (without `$`) that every file in scope must use at least once, e.g.
     * `vm` for templates. One violation per missing variable, reported at line 1 as `missing $vm`.
     */
    case REQUIRE_VARIABLE = 'require_variable';
}
