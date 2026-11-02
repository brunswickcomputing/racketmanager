<?php
/**
 * Autowire unresolvable exception.
 *
 * @package Racketmanager\Services\Container
 */

declare( strict_types=1 );

namespace Racketmanager\Services\Container;

use InvalidArgumentException;

/**
 * Dedicated internal exception thrown when the container cannot autowire a class or parameter.
 *
 * Extending InvalidArgumentException preserves the public API contract of Simple_Container::get(),
 * while providing a structural distinction so optional-parameter fallbacks never swallow
 * genuine runtime errors from user constructors or registered factories.
 */
class Autowire_Unresolvable_Exception extends InvalidArgumentException {
}
