<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Architecture\Support;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Identifier;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Param;
use PhpParser\Node\Scalar;
use PhpParser\Node\Stmt;
use PhpParser\Node\UnionType;
use PhpParser\Node\UseItem;
use PhpParser\NodeVisitorAbstract;

/**
 * Collects the facts recorded in Source_File from an AST that has already been passed through
 * PhpParser\NodeVisitor\NameResolver (so class names are FullyQualified and unqualified function
 * calls inside a namespace carry a `namespacedName` attribute).
 *
 * @internal Used by Source_Scanner only.
 */
final class Fact_Collector_Visitor extends NodeVisitorAbstract {
    public ?string $namespace = null;
    public bool $strict_types = false;
    /** @var array<int,array{name: string, kind: string, line: int}> */
    public array $imports = [];
    /** @var array<int,array{name: string, fallback: ?string, line: int}> */
    public array $function_calls = [];
    /** @var array<int,array{name: string, via: string, line: int}> */
    public array $globals = [];
    /** @var array<int,array{name: string, line: int}> */
    public array $variables = [];
    /** @var array<int,array{class: string, line: int}> */
    public array $new_targets = [];
    /** @var array<int,array{class: string, method: ?string, line: int}> */
    public array $static_calls = [];
    /** @var array<int,array{class: string, context: string, line: int}> */
    public array $class_references = [];
    /** @var array<int,array{class: ?string, param: string, type: ?string, types: array<int,string>, line: int}> */
    public array $constructor_params = [];
    /** @var array<int,array{kind: string, target: string, line: int}> */
    public array $includes = [];
    /** @var array<int,string> */
    public array $declared_functions = [];
    /** @var array<int,string> */
    public array $declared_classes = [];

    /** @var array<int,?string> Stack of enclosing class-like names (null for anonymous classes). */
    private array $class_stack = [];

    public function enterNode( Node $node ): null {
        match ( true ) {
            $node instanceof Stmt\Namespace_    => $this->enter_namespace( $node ),
            $node instanceof Stmt\Declare_      => $this->enter_declare( $node ),
            $node instanceof Stmt\Use_          => $this->enter_use( $node->uses, $node->type, null ),
            $node instanceof Stmt\GroupUse      => $this->enter_use( $node->uses, $node->type, $node->prefix ),
            $node instanceof Stmt\Global_       => $this->enter_global( $node ),
            $node instanceof Stmt\Function_     => $this->enter_function( $node ),
            $node instanceof Stmt\ClassLike     => $this->enter_class_like( $node ),
            $node instanceof Stmt\TraitUse      => $this->add_class_references( $node->traits, 'trait_use' ),
            $node instanceof Stmt\Catch_        => $this->add_class_references( $node->types, 'catch' ),
            $node instanceof Stmt\ClassMethod   => $this->enter_class_method( $node ),
            $node instanceof Expr\FuncCall      => $this->enter_func_call( $node ),
            $node instanceof Expr\Variable      => $this->enter_variable( $node ),
            $node instanceof Expr\ArrayDimFetch => $this->enter_array_dim_fetch( $node ),
            $node instanceof Expr\New_          => $this->enter_new( $node ),
            $node instanceof Expr\StaticCall    => $this->enter_static_call( $node ),
            $node instanceof Expr\ClassConstFetch,
            $node instanceof Expr\StaticPropertyFetch => $this->add_class_reference_if_name( $node->class, 'static' ),
            $node instanceof Expr\Instanceof_   => $this->add_class_reference_if_name( $node->class, 'instanceof' ),
            $node instanceof Expr\Include_      => $this->enter_include( $node ),
            $node instanceof Param              => $this->add_type_references( $node->type ),
            $node instanceof Stmt\Property      => $this->add_type_references( $node->type ),
            $node instanceof Expr\Closure,
            $node instanceof Expr\ArrowFunction => $this->add_type_references( $node->returnType ),
            default                             => null,
        };

        return null;
    }

    public function leaveNode( Node $node ): null {
        if ( $node instanceof Stmt\ClassLike ) {
            array_pop( $this->class_stack );
        }

        return null;
    }

    private function enter_namespace( Stmt\Namespace_ $node ): void {
        if ( null === $this->namespace && null !== $node->name ) {
            $this->namespace = $node->name->toString();
        }
    }

    private function enter_declare( Stmt\Declare_ $node ): void {
        foreach ( $node->declares as $declare ) {
            if ( 'strict_types' !== $declare->key->toString() ) {
                continue;
            }
            if ( $declare->value instanceof Scalar\Int_ && 1 === $declare->value->value ) {
                $this->strict_types = true;
            }
        }
    }

    /**
     * @param array<int,UseItem> $uses
     */
    private function enter_use( array $uses, int $type, ?Name $prefix ): void {
        foreach ( $uses as $use ) {
            $item_type = Stmt\Use_::TYPE_UNKNOWN !== $use->type ? $use->type : $type;
            $name      = null === $prefix ? $use->name->toString() : $prefix->toString() . '\\' . $use->name->toString();
            $kind      = match ( $item_type ) {
                Stmt\Use_::TYPE_FUNCTION => 'function',
                Stmt\Use_::TYPE_CONSTANT => 'const',
                default                  => 'class',
            };

            $this->imports[] = [ 'name' => $name, 'kind' => $kind, 'line' => $use->getStartLine() ];
            if ( 'class' === $kind ) {
                $this->class_references[] = [ 'class' => $name, 'context' => 'import', 'line' => $use->getStartLine() ];
            }
        }
    }

    private function enter_global( Stmt\Global_ $node ): void {
        foreach ( $node->vars as $var ) {
            if ( $var instanceof Expr\Variable && is_string( $var->name ) ) {
                $this->globals[] = [ 'name' => $var->name, 'via' => 'global', 'line' => $var->getStartLine() ];
            }
        }
    }

    private function enter_function( Stmt\Function_ $node ): void {
        $resolved                   = $node->namespacedName ?? $node->name;
        $this->declared_functions[] = $resolved->toString();
        $this->add_type_references( $node->returnType );
    }

    private function enter_class_like( Stmt\ClassLike $node ): void {
        $name = null;
        if ( null !== $node->name ) {
            $resolved = $node->namespacedName ?? $node->name;
            $name     = $resolved->toString();

            $this->declared_classes[] = $name;
        }
        $this->class_stack[] = $name;

        if ( $node instanceof Stmt\Class_ ) {
            if ( null !== $node->extends ) {
                $this->add_class_references( [ $node->extends ], 'extends' );
            }
            $this->add_class_references( $node->implements, 'implements' );
        } elseif ( $node instanceof Stmt\Interface_ ) {
            $this->add_class_references( $node->extends, 'extends' );
        } elseif ( $node instanceof Stmt\Enum_ ) {
            $this->add_class_references( $node->implements, 'implements' );
        }
    }

    private function enter_class_method( Stmt\ClassMethod $node ): void {
        $this->add_type_references( $node->returnType );

        if ( '__construct' !== $node->name->toLowerString() ) {
            return;
        }

        $class = [] === $this->class_stack ? null : end( $this->class_stack );
        foreach ( $node->params as $param ) {
            $param_name = $param->var instanceof Expr\Variable && is_string( $param->var->name ) ? $param->var->name : '?';

            $this->constructor_params[] = [
                'class' => $class,
                'param' => $param_name,
                'type'  => null === $param->type ? null : self::type_to_string( $param->type ),
                'types' => self::leaf_type_names( $param->type ),
                'line'  => $param->getStartLine(),
            ];
        }
    }

    private function enter_func_call( Expr\FuncCall $node ): void {
        $name = $node->name;
        if ( ! $name instanceof Name ) {
            return;
        }

        if ( $name instanceof Name\FullyQualified ) {
            $this->function_calls[] = [ 'name' => $name->toString(), 'fallback' => null, 'line' => $node->getStartLine() ];
            return;
        }

        $namespaced = $name->getAttribute( 'namespacedName' );
        if ( $namespaced instanceof Name ) {
            $this->function_calls[] = [
                'name'     => $namespaced->toString(),
                'fallback' => $name->isUnqualified() ? $name->toString() : null,
                'line'     => $node->getStartLine(),
            ];
            return;
        }

        $this->function_calls[] = [ 'name' => $name->toString(), 'fallback' => null, 'line' => $node->getStartLine() ];
    }

    private function enter_variable( Expr\Variable $node ): void {
        if ( is_string( $node->name ) && 'this' !== $node->name ) {
            $this->variables[] = [ 'name' => $node->name, 'line' => $node->getStartLine() ];
        }
    }

    private function enter_array_dim_fetch( Expr\ArrayDimFetch $node ): void {
        if ( $node->var instanceof Expr\Variable && 'GLOBALS' === $node->var->name && $node->dim instanceof Scalar\String_ ) {
            $this->globals[] = [ 'name' => $node->dim->value, 'via' => 'GLOBALS', 'line' => $node->getStartLine() ];
            $this->variables[] = [ 'name' => $node->dim->value, 'line' => $node->getStartLine() ];
        }
    }

    private function enter_new( Expr\New_ $node ): void {
        if ( $node->class instanceof Name ) {
            $class               = $node->class->toString();
            $this->new_targets[] = [ 'class' => $class, 'line' => $node->getStartLine() ];
            $this->class_references[] = [ 'class' => $class, 'context' => 'new', 'line' => $node->getStartLine() ];
        }
    }

    private function enter_static_call( Expr\StaticCall $node ): void {
        if ( ! $node->class instanceof Name ) {
            return;
        }
        $class                = $node->class->toString();
        $this->static_calls[] = [
            'class'  => $class,
            'method' => $node->name instanceof Identifier ? $node->name->toString() : null,
            'line'   => $node->getStartLine(),
        ];
        $this->class_references[] = [ 'class' => $class, 'context' => 'static', 'line' => $node->getStartLine() ];
    }

    private function enter_include( Expr\Include_ $node ): void {
        $kind = match ( $node->type ) {
            Expr\Include_::TYPE_INCLUDE_ONCE => 'include_once',
            Expr\Include_::TYPE_REQUIRE      => 'require',
            Expr\Include_::TYPE_REQUIRE_ONCE => 'require_once',
            default                          => 'include',
        };

        $this->includes[] = [
            'kind'   => $kind,
            'target' => implode( '', self::string_literals( $node->expr ) ),
            'line'   => $node->getStartLine(),
        ];
    }

    /**
     * Collect string literal fragments of an expression in source order (e.g. the pieces of a
     * concatenated path), ignoring non-literal parts such as constants and variables.
     *
     * @return array<int,string>
     */
    private static function string_literals( Expr $expr ): array {
        if ( $expr instanceof Scalar\String_ ) {
            return [ $expr->value ];
        }
        if ( $expr instanceof Scalar\InterpolatedString ) {
            $parts = [];
            foreach ( $expr->parts as $part ) {
                if ( $part instanceof Node\InterpolatedStringPart ) {
                    $parts[] = $part->value;
                }
            }
            return $parts;
        }
        if ( $expr instanceof Expr\BinaryOp\Concat ) {
            return array_merge( self::string_literals( $expr->left ), self::string_literals( $expr->right ) );
        }
        if ( $expr instanceof Expr\FuncCall ) {
            $parts = [];
            foreach ( $expr->args as $arg ) {
                if ( $arg instanceof Node\Arg ) {
                    $parts = array_merge( $parts, self::string_literals( $arg->value ) );
                }
            }
            return $parts;
        }

        return [];
    }

    /**
     * @param array<int,Name> $names
     */
    private function add_class_references( array $names, string $context ): void {
        foreach ( $names as $name ) {
            $this->add_class_reference_if_name( $name, $context );
        }
    }

    private function add_class_reference_if_name( mixed $name, string $context ): void {
        if ( $name instanceof Name && ! $name->isSpecialClassName() ) {
            $this->class_references[] = [ 'class' => $name->toString(), 'context' => $context, 'line' => $name->getStartLine() ];
        }
    }

    /**
     * Record class names appearing in a type declaration (param, property, return).
     */
    private function add_type_references( mixed $type ): void {
        if ( null === $type ) {
            return;
        }
        if ( $type instanceof Name ) {
            $this->add_class_reference_if_name( $type, 'type' );
            return;
        }
        if ( $type instanceof NullableType ) {
            $this->add_type_references( $type->type );
            return;
        }
        if ( $type instanceof UnionType || $type instanceof IntersectionType ) {
            foreach ( $type->types as $inner ) {
                $this->add_type_references( $inner );
            }
        }
    }

    /**
     * Leaf type names of a (possibly nullable/union/intersection) type, e.g. `?object` → ['object'].
     *
     * @return array<int,string>
     */
    public static function leaf_type_names( mixed $type ): array {
        if ( null === $type ) {
            return [];
        }
        if ( $type instanceof Identifier ) {
            return [ $type->toLowerString() ];
        }
        if ( $type instanceof Name ) {
            return [ $type->toString() ];
        }
        if ( $type instanceof NullableType ) {
            return self::leaf_type_names( $type->type );
        }
        if ( $type instanceof UnionType || $type instanceof IntersectionType ) {
            $names = [];
            foreach ( $type->types as $inner ) {
                $names = array_merge( $names, self::leaf_type_names( $inner ) );
            }
            return $names;
        }

        return [];
    }

    /**
     * Human-readable form of a type declaration, e.g. `?object`, `int|string`, `A&B`.
     */
    public static function type_to_string( mixed $type ): string {
        if ( $type instanceof Identifier ) {
            return $type->toString();
        }
        if ( $type instanceof Name ) {
            return $type->toString();
        }
        if ( $type instanceof NullableType ) {
            return '?' . self::type_to_string( $type->type );
        }
        if ( $type instanceof UnionType ) {
            return implode( '|', array_map( self::type_to_string( ... ), $type->types ) );
        }
        if ( $type instanceof IntersectionType ) {
            return implode( '&', array_map( self::type_to_string( ... ), $type->types ) );
        }

        return 'mixed';
    }
}
