### Dependency Injection (DI) in RacketManager

This document describes the lightweight dependency injection (DI) container introduced on 2025-12-05 to simplify service creation and wiring across the plugin.

---

#### Overview

- A tiny service container is now available to centralize the creation of repositories, external API clients, and services.
- Classes can resolve shared services from the container instead of constructing dependencies manually.
- Existing code paths are preserved; classes still work without the container (backward compatible).

---

#### Key Classes

- `Racketmanager\Services\Container\Simple_Container`
  - Minimal container with:
    - `set(id, factory|object)`
    - `get(id)` (shared instances)
    - `has(id)`
  - Factories are lazy (only created when first requested).

- `Racketmanager\Services\Container\Container_Bootstrap`
  - Central registration point. Wires repositories, external clients, and services.
  - Booted from `Racketmanager\RacketManager` during construction.

---

#### Integration Points (as of 2025‑12‑05)

- `Racketmanager\RacketManager`
  - New property: `public Simple_Container $container`.
  - Bootstraps the container via `Container_Bootstrap::boot($this)` after libraries load.
  - Resolves `player_service` from the container.

- `Racketmanager\Admin\Admin_Display`
  - Prefers resolving `Club_Service`, `Player_Service`, and `Registration_Service` from the container.
  - Falls back to legacy direct instantiation when a container is not present (e.g., during gradual migration).

---

#### Registered Service IDs

Repositories
- `club_repository`
- `registration_repository`
- `club_role_repository`
- `player_repository`
- `player_error_repository`
- `team_repository`

External clients
- `wtn_api_client`

Services
- `player_service`
- `club_service`
- `registration_service`

---

#### Usage Examples

From within `Racketmanager\RacketManager` or any class that receives a reference to it:

```php
// Prefer resolving shared services from the container
$player_service = $this->container->get('player_service');

// Optional: check existence before using
if ($this->container->has('club_service')) {
    $club_service = $this->container->get('club_service');
}
```

Registering a new service in `Container_Bootstrap`:

```php
$c->set('my_service', function (Simple_Container $c) use ($app) {
    return new My_Service(
        $app,
        $c->get('player_repository'),
        $c->get('wtn_api_client')
    );
});
```

---

#### Backward Compatibility

- Classes updated to use the container maintain a complete fallback path to the previous `new`-based construction.
- This ensures no breakage for code that doesn’t (yet) rely on the container.

---

#### Migration Guidance

When refactoring additional classes (Ajax handlers, Shortcodes, Services):
1. Add or reuse IDs in `Container_Bootstrap` for any shared dependencies.
2. In the target class’s constructor, resolve services via `$racketmanager->container->get('…')` if available; otherwise keep the old `new` calls as a fallback.
3. Avoid creating multiple instances of the same repository/service in multiple places — prefer container usage.

Minimal example pattern for constructors:

```php
if (isset($this->racketmanager->container)) {
    $c = $this->racketmanager->container;
    $this->player_service = $c->get('player_service');
} else {
    // Legacy fallback
    $player_repository       = new Player_Repository();
    $player_error_repository = new Player_Error_Repository();
    $wtn_api_client          = new Wtn_Api_Client();
    $this->player_service    = new Player_Service($this->racketmanager, $player_repository, $player_error_repository, $wtn_api_client);
}
```

---

#### Extending the Container

- Keep `Container_Bootstrap` as the single place where registrations happen to avoid duplication and surprises.
- Prefer registering interfaces → implementations if/when interfaces are introduced (e.g., `Wtn_Api_Client_Interface`).
- Keep factories small and side‑effect free; only resolve dependencies via `$c->get()`.

---

#### FAQ

- Why not use a full‑featured DI framework?
  - The plugin targets WordPress environments where simplicity and minimal dependencies are preferred. The tiny container is sufficient and easy to maintain.

- Is everything forced to use the container?
  - No. Migration is incremental. Fallback paths keep legacy code working.

- Will this impact performance?
  - Services are created lazily and shared by default; this typically reduces duplicate instantiations.

---

#### Changelog

- 2025‑12‑05: Introduced `Simple_Container` and `Container_Bootstrap`. Migrated `RacketManager` and `Admin_Display` to prefer container‑based resolution.
