<?php
declare( strict_types=1 );
/**
 * Template scope resolver
 *
 * @package RacketManager
 * @subpackage Services/View
 */

namespace Racketmanager\Services\View;

/**
 * Class Template_Scope
 *
 * Resolves the explicit `$vm` object bound into a template's scope. Replaces the legacy
 * `extract()` behaviour: templates receive `$vm` (and, where the renderer provides it, `$vars`)
 * and never have individual variables injected into their local scope.
 *
 * Resolution rules for a flat variables array:
 *  1. `$vars['vm']` is an object -> that exact instance is returned (identity preserved, never cloned or mutated).
 *  2. `$vars['vm']` is an array -> returned as `(object)` of the `vm` array merged with the remaining keys.
 *                                    Keys of the `vm` array take precedence over sibling keys of the same name.
 *  3. Otherwise -> `(object) $vars`.
 */
final class Template_Scope {

    /**
     * Resolve the `$vm` object for a flat variables array.
     *
     * @param array<string, mixed> $vars Template variables, optionally containing a `vm` entry.
     * @return object
     */
    public static function resolve_vm( array $vars ): object {
        $vm = $vars['vm'] ?? null;

        if ( is_object( $vm ) ) {
            return $vm;
        }

        if ( is_array( $vm ) ) {
            unset( $vars['vm'] );
            return (object) ( $vm + $vars );
        }

        return (object) $vars;
    }
}
