<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Architecture\Support;

/**
 * Evaluates a Layer_Rule against scanned Source_File facts and returns every violation.
 */
final class Rule_Evaluator {
    /**
     * @param array<string, Source_File> $files Scanned files keyed by plugin-root-relative path.
     *
     * @return array<int, Violation> Sorted by file then line.
     */
    public function evaluate( Layer_Rule $rule, array $files ): array {
        $violations = [];
        foreach ( $files as $file ) {
            if ( ! $rule->applies_to( $file->path ) ) {
                continue;
            }
            foreach ( $this->evaluate_file( $rule, $file ) as $violation ) {
                $violations[] = $violation;
            }
        }

        usort(
            $violations,
            static fn( Violation $a, Violation $b ): int => [ $a->file, $a->line, $a->detail ] <=> [ $b->file, $b->line, $b->detail ]
        );

        return $violations;
    }

    /**
     * @return array<int, Violation>
     */
    public function evaluate_file( Layer_Rule $rule, Source_File $file ): array {
        if ( Rule_Kind::NO_PARSE_ERRORS === $rule->kind ) {
            return $file->has_parse_error()
                ? [ $this->violation( $rule, $file, 1, 'parse error: ' . $file->parse_error ) ]
                : [];
        }

        // A file that failed to parse has no facts; the parse-error rule reports it instead.
        if ( $file->has_parse_error() ) {
            return [];
        }

        return match ( $rule->kind ) {
            Rule_Kind::FORBIDDEN_NAMESPACE_IMPORT       => $this->forbidden_imports( $rule, $file ),
            Rule_Kind::FORBIDDEN_FUNCTION_CALL          => $this->forbidden_function_calls( $rule, $file ),
            Rule_Kind::FORBIDDEN_GLOBAL                 => $this->forbidden_globals( $rule, $file ),
            Rule_Kind::FORBIDDEN_VARIABLE               => $this->forbidden_variables( $rule, $file ),
            Rule_Kind::FORBIDDEN_STATIC_CALL            => $this->forbidden_static_calls( $rule, $file ),
            Rule_Kind::FORBIDDEN_NEW                    => $this->forbidden_new( $rule, $file ),
            Rule_Kind::FORBIDDEN_CLASS_REFERENCE        => $this->forbidden_class_references( $rule, $file ),
            Rule_Kind::FORBIDDEN_DEPENDENCY             => $this->forbidden_dependencies( $rule, $file ),
            Rule_Kind::REQUIRE_STRICT_TYPES             => $this->require_strict_types( $rule, $file ),
            Rule_Kind::FORBIDDEN_CONSTRUCTOR_PARAM_TYPE => $this->forbidden_constructor_param_types( $rule, $file ),
            Rule_Kind::FORBIDDEN_INCLUDE                => $this->forbidden_includes( $rule, $file ),
            Rule_Kind::NO_PARSE_ERRORS                  => [],
            Rule_Kind::FORBIDDEN_FALLBACK_NEW           => $this->forbidden_fallback_new( $rule, $file ),
            Rule_Kind::FORBIDDEN_PUBLIC_MUTABLE_PROPERTY => $this->forbidden_public_mutable_properties( $rule, $file ),
            Rule_Kind::FORBIDDEN_LITERAL_CONTAINER_KEY  => $this->forbidden_literal_container_keys( $rule, $file ),
            Rule_Kind::REQUIRE_VARIABLE                 => $this->require_variables( $rule, $file ),
        };
    }

    /**
     * @return array<int, Violation>
     */
    private function forbidden_imports( Layer_Rule $rule, Source_File $file ): array {
        $patterns   = $this->patterns( $rule );
        $violations = [];
        foreach ( $file->imports as $import ) {
            if ( Name_Pattern::matches_any( $patterns, $import['name'] ) ) {
                $prefix       = 'class' === $import['kind'] ? 'use ' : 'use ' . $import['kind'] . ' ';
                $violations[] = $this->violation( $rule, $file, $import['line'], $prefix . $import['name'] );
            }
        }

        return $violations;
    }

    /**
     * @return array<int, Violation>
     */
    private function forbidden_function_calls( Layer_Rule $rule, Source_File $file ): array {
        $patterns   = $this->patterns( $rule );
        $violations = [];
        foreach ( $file->function_calls as $call ) {
            if ( Name_Pattern::matches_any( $patterns, $call['name'] ) ) {
                $violations[] = $this->violation( $rule, $file, $call['line'], $call['name'] . '()' );
            } elseif ( null !== $call['fallback'] && Name_Pattern::matches_any( $patterns, $call['fallback'] ) ) {
                $violations[] = $this->violation( $rule, $file, $call['line'], $call['fallback'] . '()' );
            }
        }

        return $violations;
    }

    /**
     * @return array<int, Violation>
     */
    private function forbidden_globals( Layer_Rule $rule, Source_File $file ): array {
        $names      = $this->variable_names( $rule );
        $violations = [];
        foreach ( $file->globals as $global ) {
            if ( Name_Pattern::matches_any( $names, $global['name'] ) ) {
                $detail       = 'GLOBALS' === $global['via'] ? "\$GLOBALS['" . $global['name'] . "']" : 'global $' . $global['name'];
                $violations[] = $this->violation( $rule, $file, $global['line'], $detail );
            }
        }

        return $violations;
    }

    /**
     * @return array<int, Violation>
     */
    private function forbidden_variables( Layer_Rule $rule, Source_File $file ): array {
        $names      = $this->variable_names( $rule );
        $violations = [];
        foreach ( $file->variables as $variable ) {
            if ( Name_Pattern::matches_any( $names, $variable['name'] ) ) {
                $violations[] = $this->violation( $rule, $file, $variable['line'], '$' . $variable['name'] );
            }
        }

        return $violations;
    }

    /**
     * @return array<int, Violation>
     */
    private function forbidden_static_calls( Layer_Rule $rule, Source_File $file ): array {
        $classes = $this->keyed_patterns( $rule, 'classes' );
        $methods = $this->normalise_patterns( $rule->arguments['methods'] ?? [] );

        $violations = [];
        foreach ( $file->static_calls as $call ) {
            if ( [] !== $classes && ! Name_Pattern::matches_any( $classes, $call['class'] ) ) {
                continue;
            }
            if ( [] !== $methods && ( null === $call['method'] || ! Name_Pattern::matches_any( $methods, $call['method'] ) ) ) {
                continue;
            }
            $violations[] = $this->violation( $rule, $file, $call['line'], $call['class'] . '::' . ( $call['method'] ?? '{expr}' ) . '()' );
        }

        return $violations;
    }

    /**
     * @return array<int, Violation>
     */
    private function forbidden_new( Layer_Rule $rule, Source_File $file ): array {
        $patterns   = $this->patterns( $rule );
        $violations = [];
        foreach ( $file->new_targets as $target ) {
            if ( Name_Pattern::matches_any( $patterns, $target['class'] ) ) {
                $violations[] = $this->violation( $rule, $file, $target['line'], 'new ' . $target['class'] );
            }
        }

        return $violations;
    }

    /**
     * @return array<int, Violation>
     */
    private function forbidden_class_references( Layer_Rule $rule, Source_File $file ): array {
        $patterns   = $this->keyed_patterns( $rule, 'classes' );
        $contexts   = $this->normalise_patterns( $rule->arguments['contexts'] ?? [] );
        $violations = [];
        foreach ( $file->class_references as $reference ) {
            if ( [] !== $contexts && ! Name_Pattern::matches_any( $contexts, $reference['context'] ) ) {
                continue;
            }
            if ( Name_Pattern::matches_any( $patterns, $reference['class'] ) ) {
                $violations[] = $this->violation( $rule, $file, $reference['line'], $reference['context'] . ' ' . $reference['class'] );
            }
        }

        return $violations;
    }

    /**
     * One violation per distinct forbidden class the file references, reported at its first occurrence.
     *
     * @return array<int, Violation>
     */
    private function forbidden_dependencies( Layer_Rule $rule, Source_File $file ): array {
        $patterns   = $this->patterns( $rule );
        $violations = [];
        foreach ( $file->class_references as $reference ) {
            $class = ltrim( $reference['class'], '\\' );
            if ( isset( $violations[ $class ] ) || ! Name_Pattern::matches_any( $patterns, $class ) ) {
                continue;
            }
            $violations[ $class ] = $this->violation( $rule, $file, $reference['line'], 'depends on ' . $class );
        }

        return array_values( $violations );
    }

    /**
     * @return array<int, Violation>
     */
    private function require_strict_types( Layer_Rule $rule, Source_File $file ): array {
        return $file->strict_types ? [] : [ $this->violation( $rule, $file, 1, 'missing declare(strict_types=1)' ) ];
    }

    /**
     * @return array<int, Violation>
     */
    private function forbidden_constructor_param_types( Layer_Rule $rule, Source_File $file ): array {
        $patterns   = $this->patterns( $rule );
        $violations = [];
        foreach ( $file->constructor_params as $param ) {
            if ( Name_Pattern::any_subject_matches( $patterns, $param['types'] ) ) {
                $class        = null === $param['class'] ? '' : $param['class'];
                $detail       = sprintf( '%s::__construct($%s: %s)', $class, $param['param'], $param['type'] ?? 'mixed' );
                $violations[] = $this->violation( $rule, $file, $param['line'], $detail );
            }
        }

        return $violations;
    }

    /**
     * @return array<int, Violation>
     */
    private function forbidden_includes( Layer_Rule $rule, Source_File $file ): array {
        $patterns   = $this->patterns( $rule );
        $violations = [];
        foreach ( $file->includes as $include ) {
            if ( Name_Pattern::matches_any( $patterns, $include['target'] ) ) {
                $violations[] = $this->violation( $rule, $file, $include['line'], $include['kind'] . ' ' . $include['target'] );
            }
        }

        return $violations;
    }

    /**
     * @return array<int, Violation>
     */
    private function forbidden_fallback_new( Layer_Rule $rule, Source_File $file ): array {
        $patterns   = $this->patterns( $rule );
        $violations = [];
        foreach ( $file->fallback_news as $fallback ) {
            if ( Name_Pattern::matches_any( $patterns, $fallback['class'] ) ) {
                $violations[] = $this->violation( $rule, $file, $fallback['line'], '?? new ' . $fallback['class'] );
            }
        }

        return $violations;
    }

    /**
     * @return array<int, Violation>
     */
    private function forbidden_public_mutable_properties( Layer_Rule $rule, Source_File $file ): array {
        $violations = [];
        foreach ( $file->public_properties as $property ) {
            if ( $property['readonly'] || $property['static'] || $property['restricted_set'] ) {
                continue;
            }
            $class        = null === $property['class'] ? '{anonymous}' : self::short_name( $property['class'] );
            $violations[] = $this->violation( $rule, $file, $property['line'], $class . '::$' . $property['name'] );
        }

        return $violations;
    }

    /**
     * @return array<int, Violation>
     */
    private function forbidden_literal_container_keys( Layer_Rule $rule, Source_File $file ): array {
        $receivers = $this->normalise_patterns( $rule->arguments['receivers'] ?? [] );
        $methods   = $this->normalise_patterns( $rule->arguments['methods'] ?? [] );

        $violations = [];
        foreach ( $file->literal_key_method_calls as $call ) {
            if ( [] !== $receivers && ! Name_Pattern::matches_any( $receivers, $call['receiver'] ) ) {
                continue;
            }
            if ( [] !== $methods && ! Name_Pattern::matches_any( $methods, $call['method'] ) ) {
                continue;
            }
            $detail       = sprintf( "%s->%s('%s')", $call['receiver'], $call['method'], $call['key'] );
            $violations[] = $this->violation( $rule, $file, $call['line'], $detail );
        }

        return $violations;
    }

    /**
     * @return array<int, Violation>
     */
    private function require_variables( Layer_Rule $rule, Source_File $file ): array {
        $present    = $file->variable_names();
        $violations = [];
        foreach ( $this->variable_names( $rule ) as $name ) {
            if ( ! in_array( $name, $present, true ) ) {
                $violations[] = $this->violation( $rule, $file, 1, 'missing $' . $name );
            }
        }

        return $violations;
    }

    private static function short_name( string $class ): string {
        $position = strrpos( $class, '\\' );

        return false === $position ? $class : substr( $class, $position + 1 );
    }

    private function violation( Layer_Rule $rule, Source_File $file, int $line, string $detail ): Violation {
        return new Violation( $rule->id, $file->path, $line, $detail );
    }

    /**
     * @return array<int,string>
     */
    private function patterns( Layer_Rule $rule ): array {
        return $this->normalise_patterns( $rule->arguments );
    }

    /**
     * Patterns for kinds that accept either a plain list or a keyed map (`['classes' => [...], …]`).
     *
     * @return array<int,string>
     */
    private function keyed_patterns( Layer_Rule $rule, string $key ): array {
        return $this->normalise_patterns( $rule->arguments[ $key ] ?? ( array_is_list( $rule->arguments ) ? $rule->arguments : [] ) );
    }

    /**
     * Variable-name arguments may be written with or without the `$` sigil.
     *
     * @return array<int,string>
     */
    private function variable_names( Layer_Rule $rule ): array {
        return array_map( static fn( string $name ): string => ltrim( $name, '$' ), $this->patterns( $rule ) );
    }

    /**
     * @param array<mixed> $arguments
     *
     * @return array<int,string>
     */
    private function normalise_patterns( array $arguments ): array {
        $patterns = [];
        foreach ( $arguments as $argument ) {
            if ( is_string( $argument ) && '' !== $argument ) {
                $patterns[] = $argument;
            }
        }

        return $patterns;
    }
}
