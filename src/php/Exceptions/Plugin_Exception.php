<?php

namespace Racketmanager\Exceptions;

use RuntimeException;

/**
 * Base exception for the RacketManager plugin.
 *
 * Create domain- or service-specific exceptions by extending this class under
 * the Racketmanager\Exceptions namespace (e.g., Exceptions\Domain\InvalidClubData).
 */
class Plugin_Exception extends RuntimeException {}
