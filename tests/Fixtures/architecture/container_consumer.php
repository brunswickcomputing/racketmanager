<?php
declare( strict_types=1 );

/**
 * Architecture scanner fixture: a service that falls back to `new` collaborators, exposes mutable
 * public state and pulls dependencies from the container by string key. Not autoloadable and never
 * executed.
 */

namespace Racketmanager\Tests\Fixtures\Architecture;

use DateTimeImmutable;
use Racketmanager\Domain\Money;
use Racketmanager\Services\Team_Service;

final class Container_Consumer {
    public int $count = 0;
    public readonly string $label;
    public static array $cache = [];
    public $legacy, $other;
    protected int $hidden = 0;
    private ?Team_Service $teams = null;

    public function __construct(
        public ?Team_Service $injected = null,
        public readonly int $id = 0,
        private int $seed = 0,
        int $plain = 0,
    ) {
        $this->label = (string) $plain;
    }

    public function teams(): Team_Service {
        return $this->teams ??= new Team_Service();
    }

    public function fallbacks( ?Team_Service $a, ?Team_Service $b, ?DateTimeImmutable $when, ?Money $fee ): array {
        return [
            $a ?? new Team_Service(),
            $a ?? $b ?? new Team_Service(),
            $a ?: new Team_Service(),
            $when ?? new DateTimeImmutable(),
            $fee ?? new Money( 0 ),
        ];
    }

    public function resolve( object $c, object $request ): void {
        $c->set( 'league_service', static fn(): int => 1 );
        $c->get( Team_Service::class );
        $this->container->get( 'team_service' );
        $this->plugin->container->get( 'club_service' );
        $request->get( 'id' );
        $c?->has( "nullsafe_key" );
    }
}
