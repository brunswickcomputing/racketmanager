<?php
declare( strict_types=1 );

namespace Racketmanager\Tests\Architecture\Support;

/**
 * A declarative architecture rule: which files it applies to, what it forbids/requires and how
 * violations are judged (strict or ratchet).
 */
final readonly class Layer_Rule {
    /**
     * @param string           $id               Unique id, e.g. `domain.no_wpdb`. Used as the baseline key.
     * @param string           $description      One-line human description shown on failure.
     * @param string           $remediation      How to fix a violation (backlog reference), shown on failure.
     * @param array<int,string> $path_prefixes    Plugin-root-relative path prefixes the rule applies to,
     *                                            e.g. `['src/php/Domain/']`. An empty list means every scanned file.
     * @param Rule_Kind        $kind             What fact the rule evaluates.
     * @param array<mixed>     $arguments        Kind-specific arguments (names or patterns).
     * @param Rule_Mode        $mode             Strict or ratchet.
     * @param array<int,string> $exclude_prefixes Path prefixes excluded from the rule, e.g. `['src/php/Domain/DTO/']`.
     */
    public function __construct(
        public string $id,
        public string $description,
        public string $remediation,
        public array $path_prefixes,
        public Rule_Kind $kind,
        public array $arguments,
        public Rule_Mode $mode,
        public array $exclude_prefixes = [],
    ) {
        if ( '' === trim( $id ) ) {
            throw new \InvalidArgumentException( 'Layer_Rule id must not be empty.' );
        }
    }

    public function is_strict(): bool {
        return Rule_Mode::STRICT === $this->mode;
    }

    /**
     * Whether a plugin-root-relative path falls inside the rule scope.
     */
    public function applies_to( string $path ): bool {
        foreach ( $this->exclude_prefixes as $prefix ) {
            if ( self::has_prefix( $path, $prefix ) ) {
                return false;
            }
        }
        if ( [] === $this->path_prefixes ) {
            return true;
        }
        foreach ( $this->path_prefixes as $prefix ) {
            if ( self::has_prefix( $path, $prefix ) ) {
                return true;
            }
        }
        return false;
    }

    private static function has_prefix( string $path, string $prefix ): bool {
        return '' !== $prefix && str_starts_with( $path, $prefix );
    }
}
