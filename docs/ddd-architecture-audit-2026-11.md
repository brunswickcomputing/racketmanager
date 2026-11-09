# DDD / Hexagonal Architecture Audit — RacketManager (November 2026)

**Date:** 2026-11-03
**Plugin version:** 10.0.0 (`racketmanager.php`, `Requires PHP: 8.4`)
**Toolchain:** PHP 8.4.27 · PHPUnit 13.4.2 · nikic/php-parser 5.9.0 · Jest 30.4 · esbuild
**Tree audited:** commit `9075ff8` (2026-11-03, clean working tree) — the commit that closed the last October backlog item (P3-17, `Public_Api` + alias/helper removal).
**Scope:** `src/php/` (1,020 files: `Application/` 312, `Domain/` 164, `Services/` 140, `Admin/` 137, `Infrastructure/` 83, `Presentation/` 78, `Repositories/` 49, `Exceptions/` 43, `Util/` 4, `Rest/` 3, `Public/` 1, `Cli/` 1, `Sports/` 1, 4 root files), `functions.php` / `template-tags.php`, `templates/` (302 files), `src/js/` (102 files).
**Test count:** 5,524 (`vendor/bin/phpunit --list-tests`; 5,454 at `9075ff8` + 70 Architecture tests added by this audit). Full suite green.

This report **supersedes `docs/ddd-architecture-audit-2026-10.md` for the purpose of tracking architectural debt**. The October report remains the historical record of the 17-item backlog (P1-1 … P3-17) and of how each rule was flipped to strict; it is not re-told here. §1.1 verifies its completion claims, §4–§7 inventory the *second tier* of debt that the October rule set could not see, and §9 publishes the next backlog. Debt is numbered **N1–N12** (new legacy patterns) and **D1–D4** (documentation drift) so that it cannot be confused with the October **L1–L12** / **S1–S6** items.

---

## Legend: measured vs. estimated

Every number in this report is tagged:

- **[M]** — *measured* on 2026-11-03 by regex over raw file contents (`preg_match_all`, see §2.1 and Appendix B). Token-level, so comments and docblocks can be included; treat as an **upper bound** unless stated otherwise.
- **[A]** — *AST-measured* by the PHP guardrail suite (`tests/Architecture/`, php-parser 5.9 + `NameResolver`; comments, strings and method calls excluded). The figure is the sum of the rule's entries in `tests/Architecture/baseline.json` (occurrences / distinct files). Where **[A]** and **[M]** disagree, **[A]** is authoritative.
- **[J]** — *Jest-measured* by the JS/template ratchet (`tests/js/architecture/`, `@babel/parser` tokens + AST); the figure is the sum of the rule's entries in `tests/js/architecture/baseline.json`.
- **[V]** — *verified by inspection* (a fact, not a count).

> **Status of the guardrail suite after this audit.** PHP: **37 rules** in `tests/Architecture/Rules/Layer_Rules.php` — **22 strict / 15 ratchet** (27 → 37; the 10 new rules all start as ratchet because none of them measures 0 today). `tests/Architecture/baseline.json` grew from 36 to 2,208 lines (15 rule keys); the Architecture suite has **273 tests** (was 203) and runs in ≈3.5 s. Jest: still **3 rules**, `js.jquery_reference` widened to count `window['jQuery']` / `` globalThis[`jQuery`] `` bracket access (41 tests, was 39). The scanner gained three facts (`fallback_news`, `public_properties`, `literal_key_method_calls`), four rule kinds (`FORBIDDEN_FALLBACK_NEW`, `FORBIDDEN_PUBLIC_MUTABLE_PROPERTY`, `FORBIDDEN_LITERAL_CONTAINER_KEY`, `REQUIRE_VARIABLE`) and a `contexts` filter on `FORBIDDEN_CLASS_REFERENCE` (Appendix A).

---

## 1. Executive summary

The October programme delivered what it promised: the core of the plugin is layered, the Domain is pure, the composition root exists, and every claim in the October report's "done" notes is reproducible at `9075ff8` (§1.1). **No deprecated WordPress core function, no PHP 8.4-removed function or syntax, and no `Racketmanager_Match` / alias / legacy-helper residue was found** **[V]** (§6).

What the October rules could not see is a **second tier of debt that sits in the seams between the layers** rather than inside them. It is large in count but shallow in kind, and it is now AST-measured and ratcheted:

| # | Pattern | Size **[A]** unless noted | Tracked by (new rule) |
|---|---|---|---|
| **N1** | Application handlers depend on concrete `Services\*` classes instead of ports | **189 distinct dependencies / 86 of 312 `Application/` files** | `application.no_concrete_services` |
| **N2** | Optional-dependency / hidden-default anti-pattern: `?X_Service $x = null` + `?? new X()` | **131 `?? new` fallbacks / 60 files**; 449 nullable collaborator ctor params **[M]** | `all.no_fallback_construction` |
| **N3** | Service-locator residue: string container keys and `->get('key')` consumers | **1,989 literal keys / 45 files** (1,619 in the 8 providers; 370 in 37 consumers); 357 `set('string')` vs 25 `set(X::class)`, 187 `alias()` **[M]** | `all.no_literal_container_keys` |
| **N4** | Anemic / Active-Record aggregates (public mutable state, no invariants) | **581 public mutable properties / 21 entities**; 3 domain events, 3 value objects, 12 enums **[M]** | `domain.no_public_mutable_properties` |
| **N5** | WordPress boundary code inside `Services/` (hook registration, superglobal parsing) | **176 hook registrations / 2 files** (`Rewrites` 152, `Login` 24); **61 superglobal reads / 7 files** (`Login` 44) | `services.no_wordpress_hooks`, `services.no_superglobals` |
| **N6** | `global $wp` query-var reads outside Infrastructure | **5 / 4** in the ratcheted layers (incl. `Application/Tournament/Queries/Get_Tournament_Handler`); 24 / 15 plugin-wide **[M]** | `application.no_global_wp` |
| **N7** | Remaining `Admin_Display` subclasses with inline request processing | **8 / 8** (60 superglobal reads between them **[M]**); `Admin/` 334 superglobal refs / 53 files **[M]** | `admin.no_admin_display_inheritance` |
| **N8** | Root-file residue: `Admin.php` raw `$wpdb`, `global $racketmanager`, `get_instance()`, `#[AllowDynamicProperties]` | `Admin.php` 13 `$wpdb` refs, 3 `global $racketmanager` **[M]**; `Public_Api` 1; `Export_Command` 1 `get_instance()` **[A]**; 1 dynamic-property class **[V]** | `all.no_get_instance_outside_bootstrap` (existing); rest §9 P1 |
| **N9** | Interfaces declared outside the port locations | **15 in `Services/`, 5 in `Infrastructure/`, 1 in `Admin/`** **[V]** | `Ports_Location_Test` (to extend, §9 item 13) |
| **N10** | Templates not reading `$vm` | **39 / 302** (34 never mention `$vm`; 5 declare it in a docblock but never read it) | `templates.require_vm` |
| **N11** | Presentation calls `get_option()` directly although `Option_Gateway_Interface` exists | 7 calls / 4 files vs 22 port consumers **[M]** | — (§9 item 12) |
| **N12** | jQuery runtime dependency, previously under-counted | **755 / 77** **[J]** (738 / 71 before the rule saw bracket access; +17 sites in 7 files) | `js.jquery_reference` (widened) |

`Util`-statics coupling, previously tracked only for Domain (`domain.no_util_static` 14 / 8), is now also tracked for Application: **34 calls / 19 files** **[A]** (`application.no_util_static`).

The headline for planning: **N1 + N2 + N3 are one problem seen from three sides** — the container autowires (P3-15) but nothing was migrated onto it, so handlers still name concrete services, services still construct their own collaborators, and bridges still pull string keys. Retiring them together (§9 P2 items 7–9) is the single highest-leverage move left.

### 1.1 Verification of the October backlog **[V]/[A]**

| October claim (report §1.1 / §9) | Evidence at `9075ff8` |
|---|---|
| 27 PHP rules, 22 strict / 5 ratchet; baseline holds only `admin.no_template_require` 4 / 3, `all.no_get_instance_outside_bootstrap` 1 / 1, `application.no_superglobals` 1 / 1, `domain.no_util_static` 14 / 8, `repositories.no_service_imports` 5 / 5 | `baseline.json` at `9075ff8` has exactly those 5 keys with those sums **[A]**; `Layer_Rules_Test::test_catalogue_modes_match_the_audit` lists 22 strict ids |
| P1-4 / P1-5: 0 WordPress functions in `Domain/`, 0 files without `strict_types` | `domain.no_wordpress_functions`, `all.strict_types` strict, 0 **[A]** |
| P2-6 / P2-7: 0 `$wpdb` in `Services/`, 0 legacy helper calls | `services.no_wpdb`, `all.no_legacy_helper_calls` strict, 0 **[A]**; `$wpdb` outside `Repositories/` is `Activator` 24, `Admin.php` 13, `Util` 4, `Plugin_Bootstrap` 4, `Upgrade` 1 (property), 3 docblock mentions **[M]** |
| P2-8 / P2-8a: entities hydrate from typed DTOs through factories | `domain.no_untyped_constructor`, `domain.no_lenient_from_object` strict, 0 **[A]**; 21 `DOMAIN_ENTITIES` / 21 `DOMAIN_ENTITY_FILES` |
| P2-9: six target screens + `Admin_Tournament` are `final readonly` bridges; 38 `*_Admin_Controller`s | all 7 bridges `final readonly class … { use Completes_Admin_Requests; }`; `Admin/Controllers/` holds 38 controllers; `Admin_Display` 403 lines **[V]** |
| P2-10: ports consolidated into `Domain/Ports/` (2) and `Application/Ports/` (2) + `Application/<Context>/Ports/`; `Gateways/`, `Services/Contracts/` deleted | directories absent; `Domain/Ports/{Clock,Rating_Gateway}_Interface`, `Application/Ports/{Option_Gateway,Template_Renderer}_Interface` present; `Ports_Location_Test` green **[V]** |
| P2-11: 0 Application/Services → Presentation dependencies | both rules strict, 0 **[A]** |
| P3-12: `RacketManager.php` 659 lines / 39 public methods; `services.no_global_racketmanager` strict | 659 lines **[M]**; rule strict, 0 **[A]** |
| P3-13: `League.php` 1,083 lines, UI/import state extracted | `League_View_State`, `League_Import_Mapper` present; `League_Purity_Test` green **[V]** |
| P3-14: `extract()` gone; templates free of `$wpdb` / superglobals / `global $racketmanager` | three rules strict, 0 **[A]**; `Template_Scope` binds `$vm` **[V]** |
| P3-15: `Simple_Container` autowires, `::class` keys resolvable, `Admin_Display` has 0 fallback branches | `Simple_Container::get()` resolves aliases → definitions → reflection autowiring **[V]**; `Admin_Display` 0 `has()`-guarded fallbacks **[V]** — but see N3: autowiring is *available*, not *adopted* |
| P3-16: 0 inline handlers, `js.window_global_assignment` 2 / 1, `js.jquery_reference` 738 / 71 | `templates.inline_event_handler` empty, 2 / 1, 738 / 71 at `9075ff8` **[J]** (755 / 77 after the measurement correction in §7) |
| P3-17: `aliases.php`, 29 helpers, `Legacy_User` removed; `Public_Api` + `docs/public-api.md` shipped | `functions.php` defines only `debug_to_console`, `log_trace`, `create_new_url_querystring`, `wp_get_current_url`; `src/php/Public_Api.php` and `docs/public-api.md` exist; `all.no_alias_class_references` strict at 0 against the 65-name `Alias_Map` **[V]/[A]** |
| No deprecated WordPress core / PHP-removed functions | 0 `each()`, `create_function()`, `utf8_encode()`/`utf8_decode()`, `FILTER_SANITIZE_STRING`, `${var}` string interpolation, `get_page_by_title()`, `wp_make_content_images_responsive()`, `get_currentuserinfo()`, `wp_get_sites()`; `Requires PHP: 8.4` **[V]** |

All 17 items are confirmed complete. Nothing in this report re-opens them.

---

## 2. Method & metrics

### 2.1 How the figures were produced

**[A]** figures are the guardrail suite's own output: `RM_ARCH_UPDATE_BASELINE=1 vendor/bin/phpunit --testsuite Architecture` regenerated `tests/Architecture/baseline.json` with the 10 new rules in place, and the per-rule sums were read back with the one-liner in Appendix B. The new rules are evaluated by the same `Source_Scanner` / `Fact_Collector_Visitor` (php-parser 5.9, `NameResolver`) as the October rules; three new facts were added to the scanner for this audit:

| Fact | What is collected | Used by |
|---|---|---|
| `fallback_news` | the right-hand `new X()` of a `??` or `??=` whose class name resolves statically; in a `$a ?? $b ?? new X()` chain the `new` counts once | `FORBIDDEN_FALLBACK_NEW` |
| `public_properties` | every `public` property declaration or promoted constructor parameter, with `readonly`, `static` and `private(set)`/`protected(set)` flags | `FORBIDDEN_PUBLIC_MUTABLE_PROPERTY` (exempts readonly / static / asymmetric-visibility; constants are not properties) |
| `literal_key_method_calls` | every method call whose first argument is a string literal, with the receiver rendered source-like (`$c`, `$container`, `$this->container`, `$this->racketmanager->container`, `self::$container`, `{expr}`) | `FORBIDDEN_LITERAL_CONTAINER_KEY` (receiver- and method-filtered) |

Two existing facts were reused with new filters: `class_references` now accepts a `contexts` argument (`admin.no_admin_display_inheritance` looks only at `extends`), and `variables` gained a presence check (`REQUIRE_VARIABLE` for `templates.require_vm`; a template that never reads `$vm` is reported once as `missing $vm`).

**[M]** figures come from the regex one-liners in Appendix B. Where **[M]** and **[A]** differ the difference is explained inline — the two largest are `services.no_wordpress_hooks` (planning estimate ≈27, AST 176: `Rewrites.php` registers 152 rewrite rules one call each) and `all.no_literal_container_keys` (planning estimate ≈550 string *definitions*, AST 1,989 because every `$c->get('…')` inside a provider closure is a literal key too — the figure is correct and is the real size of the migration).

**[J]** figures are the Jest baseline sums; the jQuery rule's bracket-access pass was cross-checked against a regex (`/\b(window|globalThis)\s*\[\s*['"`]jQuery['"`]\s*\]/`) — both report 17 sites in 7 files.

### 2.2 Size of the codebase **[M]**

| Directory (`src/php/…`) | Files | October (2026-10-25) |
|---|---|---|
| `Application/` | 312 | 239 |
| `Domain/` | 164 | 148 |
| `Services/` | 140 | 137 |
| `Admin/` | 137 (16 `Admin_*.php` screens — 8 bridges, 8 `Admin_Display` subclasses — 38 `Controllers/`, view models, presenters) | 81 |
| `Infrastructure/` | 83 | 61 |
| `Presentation/` | 78 | 76 |
| `Repositories/` | 49 (21 in `Interfaces/`) | 46 |
| `Exceptions/` | 43 | 42 |
| `Util/`, `Rest/`, `Public/`, `Cli/`, `Sports/` | 4, 3, 1, 1, 1 | 4, 3, 1, 1, 1 |
| root (`RacketManager.php` 659 lines, `Admin.php` 672, `Public_Api.php`, `Activator.php`) | 4 | 5 (`aliases.php` deleted) |
| **Total** | **1,020** | 849 |
| `templates/` | 302 | 304 |
| `src/js/` | 102 | 90 |

The 20 % growth is almost entirely the P2-9 / P2-11 decomposition (per-view admin controllers, view models, Application result DTOs). Lines, not files, are where the remaining god objects show: `Rewrites.php` 1,009, `Util.php` 830 + `Util_Lookup.php` 538 (32 + 33 static methods, 105 consumer files), `Shortcodes.php` 792, `Admin.php` 672, `RacketManager.php` 659, `Login.php` 605.

---

## 3. Layer compliance matrix

✅ = 0 **[A]** (strict rule) · 🔒 = ratcheted debt (count / files **[A]**) · ⚠️ = measured, not yet ratcheted · — = not applicable / not in rule scope.

| Layer | No `$wpdb` | No `global $racketmanager` | No superglobals | No outer-layer / Presentation deps | No concrete `Services\*` | No fallback `?? new` | No `global $wp` | No WP hook registration | No `Util` statics | Ports in place |
|---|---|---|---|---|---|---|---|---|---|---|
| `Domain/` | ✅ | ✅ | ✅ | ✅ (`domain.no_outer_layer_imports`) | ✅ (covered by outer-layer rule) | 🔒 0 (in scope of `all.no_fallback_construction`) | — | ✅ (`domain.no_wordpress_functions`) | 🔒 14 / 8 | ✅ `Domain/Ports/` (2) |
| `Application/` | ✅ | ✅ | 🔒 1 / 1 | ✅ (`application.no_presentation_imports`, `no_concrete_repositories`) | 🔒 **189 / 86** | 🔒 1 / 1 | 🔒 **1 / 1** (`Get_Tournament_Handler`) | 🔒 0 (in scope) | 🔒 **34 / 19** | ⚠️ `Application/Ports/` (2) + 4 `Application/<Context>/Ports/` directories; **most "ports" are still concrete services** (N1) |
| `Services/` | ✅ | ✅ | 🔒 **57 / 6** | ✅ (`services.no_presentation_imports`) | — | 🔒 **52 / 35** | 🔒 2 / 2 | 🔒 **176 / 2** | ⚠️ not ratcheted | ⚠️ 15 interfaces declared here (N9) |
| `Repositories/` | — (owner) | ✅ (`services.no_global_racketmanager` scope) | 🔒 0 (in scope) | 🔒 5 / 5 (`repositories.no_service_imports`) | — | 🔒 4 / 4 | 🔒 0 | 🔒 0 | ⚠️ | ✅ `Repositories/Interfaces/` (21) |
| `Presentation/` | — | ✅ | 🔒 0 (in scope) | — | — | 🔒 **12 / 5** | 🔒 0 | 🔒 0 | ⚠️ | ⚠️ `get_option()` 7 / 4 bypasses `Option_Gateway_Interface` (N11) |
| `Infrastructure/` | ⚠️ `Plugin_Bootstrap` 4 (install SQL) | ✅ (excl. `Plugin_Bootstrap`) | — (driving adapters may parse HTTP) | — | — | 🔒 **56 / 12** | — (allowed) | — (allowed) | ⚠️ | ⚠️ 5 interfaces declared here (N9) |
| `Admin/` | — | ⚠️ `Admin_Date_Presenter` 1 **[M]** | ⚠️ 334 / 53 **[M]** | — | — | 🔒 2 / 1 | — | — | ⚠️ | 🔒 `Admin_Display` subclasses **8 / 8**; `admin.no_template_require` 4 / 3 |
| `Util/` | ⚠️ `Util.php` 4 **[M]** | ✅ | 🔒 4 / 1 (`Util_Lookup`) | — | — | 🔒 3 / 1 | 🔒 **2 / 1** (`Util_Lookup`) | — | (is `Util`) | — |
| `Rest/` | — | ✅ | — | — | — | 🔒 1 / 1 | — | — | ⚠️ | — |
| `templates/` | ✅ | ✅ | ✅ | ✅ (`templates.no_legacy_helper_calls`) | — | — | — | — | — | 🔒 `templates.require_vm` **39 / 39** |
| Container / providers | — | — | — | — | — | excluded by design (`Services/Container/`, `Plugin_Bootstrap`) | — | — | — | 🔒 literal keys **1,989 / 45** plugin-wide (`all.no_literal_container_keys`, providers included) |

Per-layer splits of the cross-cutting `all.*` rules above were read from the baseline file prefixes (Appendix B). `all.no_fallback_construction` 131 / 60 = Infrastructure 56 / 12 + Services 52 / 35 + Presentation 12 / 5 + Repositories 4 / 4 + Util 3 / 1 + Admin 2 / 1 + Application 1 / 1 + Rest 1 / 1.

---

## 4. Legacy pattern inventory (N1–N12)

Each item: where / size · why it matters for DDD · tracked by.

### N1 — Application depends on concrete `Services\*` classes

- **Size:** 189 distinct concrete-service dependencies in 86 of 312 `Application/` files **[A]** (`application.no_concrete_services`; one violation per distinct class per file, however spelled). Regex: 190 `use Racketmanager\Services\…` lines in 87 files **[M]**, of which exactly one is an interface (`Flash_Message_Store_Interface`). Most-depended-on targets **[A]** (files): `Competition_Service` 27, `Player_Service` 17, `Club_Service` 16, `Tournament_Service` 15, `League_Service` 14, `Event_Query_Service` 14, `League_Query_Service` 12, `Finance_Service` 10, `Competition_Season_Service` 8, `Player_Stats_Service` 8, `Competition_Entry_Service` 7, `Registration_Service` 6.
- **Why it matters:** `docs/architecture.md` ("Services vs Application Layering Rules") says use-case handlers talk to the outside world through ports and that `Services/` is the legacy orchestration layer to be dissolved. With handlers hard-wired to concrete orchestrators the dependency arrow points the wrong way — `Services/` cannot be retired or replaced piecemeal, and a handler cannot be unit-tested without the service's full object graph (which, per N2, builds repositories on demand).
- **Tracked by:** `application.no_concrete_services` (ratchet 189 / 86). Interfaces under `Services\` are deliberately allowed by the pattern so that N9 can be fixed by *moving* them without first rewriting every handler.

### N2 — Optional-dependency / hidden-default anti-pattern

- **Size:** 131 `?? new X()` / `??= new X()` fallbacks constructing a plugin collaborator, in 60 files **[A]** (`all.no_fallback_construction`): Infrastructure 56 / 12, Services 52 / 35, Presentation 12 / 5, Repositories 4 / 4, Util 3 / 1, Admin 2 / 1, Application 1 / 1, Rest 1 / 1. The raw count is 139 / 65; the rule's collaborator pattern (`_Service|_Repository|_Presenter|_Handler|_Adapter|_Factory|_Gateway|_Renderer|_Validator|_Dispatcher|_Mailer|_Clock|_Loader|_Manager` under `Racketmanager\`) intentionally skips `DateTimeImmutable` ×2, `stdClass`, `WP_REST_Request` and the domain strategy `Schedule_Round_Robin`, and the rule excludes `Services/Container/` and `Infrastructure/Wordpress/Bootstrap/`. Files with the most fallbacks: `Competition_Shortcode_Adapter` 12, `Event_Shortcode_Adapter` 11, `Club_Shortcode_Adapter` 7, `League_Shortcode_Adapter` 6, `Validator` 6, `Tournament_Ajax_Adapter` 4, `General_Shortcode_Adapter` 4, `League_Standings_Presenter` 4. Classes most often constructed this way: `Competition_Shortcode_Presenter` 14, `Player_Presenter` 14, `Club_Presenter` 10, `Option_Service` 9, `Wp_Mailer` 9, `Tournament_Presenter` 8, `Fixture_Repository` 5. 449 constructor parameters of the shape `?X_Service $x = null` **[M]** feed these fallbacks.
- **Why it matters:** the fallback *is* a service locator with the container removed. It bypasses the composition root (`.junie/guidelines.md` §3), hides the real dependency graph from the container and from readers, silently instantiates concrete repositories inside services (so `Validator::team_repository()` reaches `$wpdb` without any port), and makes every "optional" collaborator effectively required. It is also why `Simple_Container` autowiring (P3-15) has had no practical effect: a constructor whose parameters all default to `null` autowires to nothing.
- **Tracked by:** `all.no_fallback_construction` (ratchet 131 / 60). `$x ?: new X()` and plain `new X()` outside a coalesce are not counted (documented non-goal; the former does not occur in `src/php`, the latter is the subject of D2).

### N3 — Service-locator residue in the container

- **Size:** 1,989 string-keyed container calls in 45 files **[A]** (`all.no_literal_container_keys`; receivers `$c`, `$container`, `*->container`, `*::$container`; methods `get|set|has|alias|has_definition|is_registered|remove`). Providers: `Competition_Service_Provider` 428, `Admin_Screens_Service_Provider` 343, `Admin_Service_Provider` 275, `Core_Service_Provider` 197, `Fixture_Service_Provider` 148, `Presentation_Service_Provider` 93, `Club_Service_Provider` 84, `Player_Service_Provider` 51 (1,619). Consumers that pull from the container at runtime: `Public/Shortcodes` 48, `Frontend_Ajax_Controller` 39, `Rest_Resources` 24, `Admin_Ajax_Controller` 19, `Component_Coordinator` 19, `Fixture_Ajax_Adapter` 18, `Admin_League` 17, `Finance_Ajax_Controller` 16, `Admin_Cup` 15, `Admin_Display` 14, `Admin_Tournament` 14, `Admin.php` 13, `Account_Ajax_Controller` 12, `Club_Ajax_Controller` 12, `Fixture_Ajax_Controller` 10, `Export_Controller` 9, `RacketManager.php` 8, `Tournament_Ajax_Controller` 8, `Admin_Club` 7, `Public_Api` 6, … (370 in 37 files). Definitions: 357 `set('string', …)` vs 25 `set(X::class, …)`; 187 `alias()` bridges **[M]**.
- **Why it matters:** string keys are untyped, un-navigable and un-refactorable — the IDE cannot find the consumers of `'league_service'`, a typo is a runtime error, and the 187 aliases exist only to let two spellings coexist. Runtime `->get()` in adapters, bridges and `Shortcodes` means those classes are service locators: their dependencies are invisible in the constructor and they cannot be constructed in a test without a populated container. P3-15 made `::class` keys and autowiring *possible*; this is the measurement of how much has not yet moved.
- **Tracked by:** `all.no_literal_container_keys` (ratchet 1,989 / 45). Providers are intentionally *in* scope — they are where the migration happens. `$request->get('id')`, `$settings->get('x')` and `$c->get(X::class)` are not counted.

### N4 — Anemic / Active-Record aggregates

- **Size:** 581 public mutable properties across the 21 Domain entities **[A]** (`domain.no_public_mutable_properties`; `readonly`, `static`, `private(set)`/`protected(set)` and constants exempt): `Competition` 74, `League_Team` 64, `Event` 62, `Player` 51, `Fixture` 49, `User` 44, `League` 42, `Rubber` 39, `Tournament` 37, `Team` 22, `Club` 21, `Invoice` 14, `Club_Player` 11, `Results_Checker` 10, `Message` 9, `Charge` 9, `Club_Role` 7, `Tournament_Entry` 7, `Results_Report` 4, `Player_Error` 4, `Season` 1. `Fixture` additionally exposes 43 `set_*` mutators **[M]**. The domain vocabulary is thin: 3 domain events (`Abstract_Domain_Event` + dispatcher/listener plumbing), 3 value objects (`Money`, `Date_Range`, `Json_Encoder`), 12 enums **[M]**; 43 `$a->b->c->d` property chains in `Presentation/` + `Application/` **[M]** read straight through aggregates.
- **Why it matters:** with every field writable from anywhere, invariants (a fixture cannot have a result before both teams are set; an entry cannot be accepted after the deadline; standings must be recomputed when a result changes) live in whichever service remembers to enforce them, and repositories hydrate by assigning public properties instead of going through the P2-8 factories. This is the textbook anemic-domain smell and the main reason `Services/` is still 140 files.
- **Tracked by:** `domain.no_public_mutable_properties` (ratchet 581 / 21; scope `DOMAIN_ENTITY_FILES`, so DTOs, value objects and the scheduling engine are untouched).

### N5 — WordPress boundary code inside `Services/`

- **Size:** hook / shortcode / rewrite / REST registration: 176 calls in 2 files **[A]** (`services.no_wordpress_hooks`: `Rewrites.php` 152 `add_rewrite_rule`, `Login.php` 24 `add_action`/`add_filter`). Superglobal reads: 61 in 7 files **[A]** (`services.no_superglobals`: `Login` 44, `League_Query_Service` 5, `Competition_Season_Service` 4, `Util_Lookup` 4, `Championship_Factory` 2, `Player_Details_Validator` 1, `Validator` 1). `Services/Widget.php` (222 lines) is a `WP_Widget` subclass **[V]**; `Services/Container/` is the DI container itself **[V]**.
- **Why it matters:** registering hooks and parsing `$_POST` are *driving-adapter* concerns; `docs/architecture.md` places them in `Infrastructure/Wordpress/`. While they sit in `Services/`, "Services" has no single meaning, the hook-registration rule for Application/Presentation cannot be made strict, and `Login` (605 lines) cannot be tested without WordPress.
- **Tracked by:** `services.no_wordpress_hooks` (ratchet 176 / 2; scope Services, Application, Presentation, Repositories), `services.no_superglobals` (ratchet 61 / 7; scope Services, Repositories, Presentation, Util). `Widget` registers nothing itself, so it is tracked by §9 item 1 rather than by a rule.

### N6 — `global $wp` query-var reads

- **Size:** 5 / 4 in the ratcheted layers **[A]** (`application.no_global_wp`: `Application/Tournament/Queries/Get_Tournament_Handler` 1, `Services/Competition/Competition_Season_Service` 1, `Services/Seo/Page_Title_Service` 1, `Util/Util_Lookup` 2). Plugin-wide 24 sites / 15 files **[M]**: the other 19 are in `Infrastructure/Wordpress/Shortcodes/*_Shortcode_Adapter` (11 adapters), `Infrastructure/Wordpress/Request/Competition_Request` and `Public/Shortcodes` ×2 — driving adapters, where reading `$wp` is permitted but should still go through one place.
- **Why it matters:** the Application hit is a direct layer violation (a query handler that reads the WordPress router). The adapter hits are 11 copies of the same 6 lines.
- **Tracked by:** `application.no_global_wp` (ratchet 5 / 4; globals `wp`, `wp_query`, `post`, `current_user`). Fix shape in §9 item 2.

### N7 — Remaining `Admin_Display` subclasses

- **Size:** 8 / 8 **[A]** (`admin.no_admin_display_inheritance`): `Admin_Upgrade`, `Admin_Options`, `Admin_Season`, `Admin_Championship`, `Admin_Import`, `Admin_Event`, `Admin_Index`, `Admin_Result` — 60 superglobal reads between them (`Admin_Event` 13, `Admin_Result` 13, `Admin_Index` 9, `Admin_Championship` 8, `Admin_Options` 6, `Admin_Season` 5, `Admin_Import` 5, `Admin_Upgrade` 1) **[M]**; `admin.no_template_require` 4 / 3 is entirely inside this set (`Admin_Display` documentation 1, `Admin_Result` 1, `Admin_Upgrade` 2). `Admin/` as a whole still has 334 superglobal references in 53 files **[M]** (bridges forward `$_POST`/`$_GET` arrays to dispatchers, which is the P2-9 design; the per-view controllers parse them).
- **Why it matters:** these screens still process requests inline (no PRG, no typed command, no view model), so they are the only admin code that cannot be covered by the `Completes_Admin_Requests` / controller tests.
- **Tracked by:** `admin.no_admin_display_inheritance` (ratchet 8 / 8), `admin.no_template_require` (4 / 3).

### N8 — Root-file residue

- `Admin.php` (672 lines) still runs raw SQL: `add_meta_box()` reads leagues and the match row with `$wpdb->get_results()` / `get_row()` (13 `$wpdb` refs — the last query code outside `Repositories/` and the installer) and uses `global $racketmanager` 3× **[M]**.
- `Public_Api::container()` reaches the container through `global $racketmanager` **[V]** (the façade shipped in P3-17 inherits the locator it was meant to replace); `Admin/Presenters/Admin_Date_Presenter` has one more **[M]**.
- `Cli/Export_Command` calls `RacketManager::get_instance()` — the single remaining `all.no_get_instance_outside_bootstrap` entry **[A]**.
- `Presentation/Presenters/Fixture_Presentation_Adapter` carried `#[AllowDynamicProperties]` (removed 2026-11-09) — no dynamic-property classes remain in `src/php` **[V]**.
- **Tracked by:** `all.no_get_instance_outside_bootstrap` (1 / 1); the rest are §9 P1 items 3–5 (small, mechanical).

### N9 — Interfaces declared outside the port locations

- **Size [V]:** 15 in `Services/` — `Fixture/Pending_Result_Orchestrator_Interface`, `Notification/Notification_Presenter_Interface`, `Mail/Mail_Filter_Service_Interface`, `Admin/Flash_Message_Store_Interface`, `Admin/Security/Action_Guard_Interface`, `Admin/Options/Options_Action_Dispatcher_Interface`, `Admin/Index/Index_Action_Dispatcher_Interface`, `Admin/Import/Import_Action_Dispatcher_Interface`, `Admin/Result/Results_Checker_Action_Dispatcher_Interface`, `Admin/Championship/Draw_Action_Handler_Interface`, `Admin/Overview/Tournament_Overview_Action_Handler_Interface`, `View/View_Renderer_Interface`, `Seo/Page_Title_Service_Interface`, `Export/Formatters/Export_Formatter_Interface`, `Container/Providers/Service_Provider_Interface`; 5 in `Infrastructure/` — `Security/Security_Service_Interface`, `Wordpress/Admin/Admin_Redirector_Interface`, `Wordpress/Response/Json_Response_Factory_Interface`, `Wordpress/Component/Component_Coordinator_Interface`, `Wordpress/Shortcodes/Shortcode_Adapter_Interface`; 1 in `Admin/` — `Presenters/Championship_Finals_Presenter_Interface`.
- **Why it matters:** P2-10 consolidated the *gateway* ports but `Ports_Location_Test` only guards `Domain/Ports/`, `Application/Ports/` and `Application/<Context>/Ports/`; interfaces that are genuinely ports of the Application layer (`Flash_Message_Store_Interface`, `Notification_Presenter_Interface`, `View_Renderer_Interface`, `Security_Service_Interface`, `Admin_Redirector_Interface`, `Json_Response_Factory_Interface`) are declared next to one implementation, which is why handlers import them from `Services\` (N1). `Service_Provider_Interface`, `Export_Formatter_Interface` and the `Shortcode_Adapter_Interface` / `Component_Coordinator_Interface` pair are *infrastructure contracts* and may legitimately stay where they are — the backlog item asks for a decision per interface, not a blanket move.
- **Tracked by:** `Ports_Location_Test` after §9 item 13 extends it.

### N10 — Templates not reading `$vm`

- **Size:** 39 / 302 **[A]** (`templates.require_vm`). 34 never mention `$vm` **[M]**; 5 (`admin/finances/show-charges.php`, `show-invoices.php`, `admin/players/show-errors.php`, `show-players.php`, `show-requests.php`) declare `/** @var …_Page_View_Model $vm */` but never read it — they are the thin wrappers the P2-9 controllers render *around* partials. The 34: 10 `admin/documentation/*`, 3 `admin/event/include/settings/*`, `admin/includes/player.php`, 5 `email/*` (components `hr`, `salutation`, `contact-urgent`, `div-top`, `div-bottom`), 9 `includes/*` (`loading`, `modal-loading`, `modal-score`, `match-modal`, `nav-pills`, `round-draw`, `championship-draw`, `matches-team-list`, `matches-team-list-group`), `event/teams-list.php`, `tournament/match-unavailable.php`, 2 `page_template/*`, 2 `pages/*`. 51 templates call entity methods and 30 have 3-deep property chains **[M]** (not ratcheted; a presentation-quality metric rather than a layering one).
- **Why it matters:** `Template_Scope` guarantees `$vm` exists (P3-14), but the partials still receive their data as loose variables bound by the caller, so the contract between controller and template is implicit and `templates.no_globals_or_db` can only stay strict because nobody has added a superglobal read yet.
- **Tracked by:** `templates.require_vm` (ratchet 39 / 39).

### N11 — Presentation calls `get_option()` directly

- **Size:** 7 calls in 4 `Presentation/` files **[M]** (earlier planning notes said 24 / 10; that count included `->get_option()` method calls on the service). 22 files already consume `Application/Ports/Option_Gateway_Interface` **[V]**.
- **Why it matters:** a port that is bypassed in 4 places is not a port; presenters that read options cannot be rendered in isolation.
- **Tracked by:** — (§9 item 12; small enough not to need a rule — if the fix stalls, add `get_option` to a `presentation.no_wordpress_options` rule).

### N12 — jQuery runtime dependency

See §7. 755 token/bracket references in 77 files **[J]**; the admin UI widgets (tooltips, colour picker, autocomplete, drag-and-drop) still need the jQuery runtime.

---

## 5. Structural & naming inconsistencies

| # | Finding **[V]/[M]** | Effect |
|---|---|---|
| S1 | **Two dependency-injection styles coexist**: 25 `::class` bindings and autowiring (P3-15) next to 357 string keys, 187 aliases and 449 nullable-with-fallback constructor parameters (N2/N3). | New code has no single pattern to copy; `Admin_Screens_Service_Provider` (603 lines, 343 literal keys) is the largest file in `Admin/`. |
| S2 | **"Services" means four things**: domain-ish orchestration (`Competition_Entry_Service`), WordPress adapters (`Login`, `Rewrites`, `Widget`), the DI container (`Container/`), and ports (15 interfaces). | The layer cannot be given a rule stricter than "no `$wpdb`, no Presentation" without first splitting it (N5, N9). |
| S3 | **`Util` / `Util_Lookup` grab-bag** — 65 static methods, 1,368 lines, 105 consumer files, 4 `$wpdb` refs, 4 superglobal reads, 2 `global $wp` **[M]**; only Domain (14 / 8) and Application (34 / 19) are ratcheted. | Every layer reaches into it, so it is the one place where a change can break all layers at once (D4). |
| S4 | **Three admin screen generations**: 7 `final readonly` bridges (P2-9), 8 `Admin_Display` subclasses (N7), and the root `Admin.php` god object with its own `add_meta_box` SQL (N8). | Onboarding cost; two of the three cannot be unit-tested. |
| S5 | **`Public/Shortcodes.php` (792 lines)** is the last pre-adapter shortcode host: 48 literal container keys, 2 `global $wp`, 2 `@deprecated` methods (`show_daily_matches()`, `show_latest_results()`) whose replacements (`Matches_Shortcode_Adapter`) already exist. | The 11 `*_Shortcode_Adapter`s are the pattern; this file is the exception that keeps the container-as-locator alive on the front end. |
| S6 | **Domain vocabulary is thin**: 3 events, 3 value objects, 12 enums against 21 entities with 581 public fields (N4). `Season` is an entity with 1 property; `Money` exists but prices are still `float`/`string` fields on `Charge`, `Invoice`, `Competition`. | Business rules are expressed as service code rather than as types. |

---

## 6. Deprecation surface & external consumers

### 6.1 Deprecated WordPress / PHP usage **[V]**

Searched `src/php`, `functions.php`, `template-tags.php`, `templates/`: **none** of `each()`, `create_function()`, `utf8_encode()` / `utf8_decode()`, `FILTER_SANITIZE_STRING`, `${var}` string interpolation, `mysql_*`, `get_page_by_title()`, `wp_make_content_images_responsive()`, `get_currentuserinfo()`, `wp_get_sites()`, `wp_blacklist_check()`, `screen_icon()`, `add_contextual_help()`. The plugin header declares `Requires PHP: 8.4` and the code uses 8.4 features (`private(set)`, `readonly` classes, `new X()->method()` without parentheses).

### 6.2 `@deprecated` members still present **[V]**

| Member | Since | Replacement | Internal callers |
|---|---|---|---|
| `Public\Shortcodes::show_daily_matches()` (`[dailymatches]`) | 10.0 | `Matches_Shortcode_Adapter` (`Get_Daily_Matches_Handler` + `Matches_List_Presenter`) | 0 — shortcode registration only |
| `Public\Shortcodes::show_latest_results()` (`[latest_results]`) | 10.0 | same | 0 |
| `RacketManager::read_directory()` | 10.0.0 | native filesystem functions / `Plugin_Bootstrap` | 0 |
| `Infrastructure\Wordpress\Request\Player\Search_Players_Command` | "2.0.0" (sic) | `Application\Player\Queries\Search_Players_Query` | 0 |

All four can be deleted in the next minor; none has a caller. Deprecated container keys: `application_fixture_detail_service` and `domain_fixture_detail_service` in `Fixture_Service_Provider` ("Deprecated 10.1.0 … removed in v11.0", both forward to `fixture_details_assembler`) — removable with §9 item 9.

### 6.3 External consumer **[V]**

`themes/sydney-child/functions.php` uses `Racketmanager\Public_Api` only (patched in P3-17); `docs/public-api.md` documents the surface. No other theme or plugin in `wp-content/` references `Racketmanager\` symbols.

---

## 7. JavaScript (`src/js/`) **[J]**

| Rule | At `9075ff8` | After this audit | Change |
|---|---|---|---|
| `js.jquery_reference` | 738 / 71 | **755 / 77** | **measurement correction, not new debt**: the rule now counts `window['jQuery']` / `` globalThis[`jQuery`] `` bracket access (17 sites in 7 files — `admin/features/ajax/admin-ajax.js` 5, `admin/features/dropdowns/dropdown-cascade.js` 4, `features/autocomplete/autocomplete-helpers.js` 4 (`globalThis`), `admin/functions.js` +1, `admin/features/forms/team-autocomplete.js` 1, `admin/features/forms/team-validation.js` 1, `admin/features/ui/admin-ui.js` 1). Six of those files were previously invisible to the rule. The October report estimated "13 + autocomplete helpers"; AST and regex agree on 17 (double-quoted and template-literal keys were missed by the estimate). |
| `js.window_global_assignment` | 2 / 1 | 2 / 1 | unchanged (`config/constants.js`) |
| `templates.inline_event_handler` | 0 / 0 | 0 / 0 | unchanged (baseline empty = strict) |

Residual picture: 25 of 102 JS files are jQuery-free; the remaining dependency is concentrated in the admin UI (`admin/features/*` — AJAX, dropdown cascades, team autocomplete/validation, draggable, tooltips, colour picker) and in `features/autocomplete/`. `racketmanager-admin-ajax` declares the `jquery` script dependency explicitly, so removal is a feature-by-feature port, not a build change (§9 item 17).

---

## 8. Reconciliation with the October report and documentation drift

### 8.1 Measurement corrections

| Figure | October / planning | Now | Why |
|---|---|---|---|
| `js.jquery_reference` | 738 / 71 | 755 / 77 | bracket access counted (§7) |
| `services.no_wordpress_hooks` estimate | ≈27 / 3 | 176 / 2 | `Rewrites.php` registers 152 rules individually; `Widget.php` registers nothing itself |
| `services.no_superglobals` estimate | ≈35 / 8 | 61 / 7 | `Login.php` alone has 44 reads |
| `all.no_literal_container_keys` estimate | ≈550 | 1,989 / 45 | every `$c->get('…')` inside provider closures is a literal key |
| `domain.no_public_mutable_properties` estimate | ≈450 | 581 / 21 | `League_Team` (64), `User` (44), `Rubber` (39) were not in the regex sample |
| Templates without `$vm` | 34 | 39 | 5 templates declare `$vm` in a docblock only |
| Presentation `get_option()` | 24 / 10 | 7 / 4 | regex had matched `->get_option()` method calls |
| `global $wp` sites | 24 / 16 | 24 / 15 | file recount |
| Test count | 5,454 | 5,524 | +70 Architecture tests |

### 8.2 Documentation drift (D1–D4)

| # | Finding | Fix |
|---|---|---|
| **D1** | `docs/architecture.md` §PHP Architecture still said "In wp-admin: instantiate `Admin` (extends `RacketManager`)" and listed `Ajax\*` controllers; `Admin` has no parent and is wired by `Admin_Service_Provider`, AJAX lives in `Infrastructure/Wordpress/Ajax/*_Ajax_Controller` + `Ajax_Registry`. | corrected 2026-11-03 together with this report |
| **D2** | `.junie/guidelines.md` §3 "Never instantiate service classes directly using `new`" was contradicted by 164 `new *_Service/_Repository/_Presenter/_Handler(` sites outside providers **[M]**, 131 of them the `?? new` fallbacks of N2. | guideline reworded to "collaborators are constructor-injected; `?? new` fallbacks and string container keys are ratcheted"; the 10 new rule ids listed |
| **D3** | `docs/` still carries ~15 superseded plans (`ddd-audit-report.md`, `ddd-architecture-report.md`, `ddd-analysis-2026.md`, `match-migration-*.md`, `move-away-from-match*.md`, `migration-plan.md`, …) that describe `Racketmanager_Match`, `aliases.php` and `Ajax/` as live. | §9 item 18 (archive under `docs/archive/`) |
| **D4** | `Util/Util.php` + `Util_Lookup.php` are undocumented in `docs/architecture.md` although 105 files depend on them. | §9 item 16; until then the two new `*.no_util_static` rules make the coupling visible |

The October report carries a supersession banner pointing here; its L/S/P numbering is frozen.

---

## 9. Prioritised backlog

Effort: S < ½ day · M 1–3 days · L > 3 days. "Tracked by" names the rule whose baseline must shrink (and, at 0, flip to strict per Appendix A.3).

### P1 — Stop the bleeding (S–M, low risk)

| # | Item | Effort | Risk | Tracked by |
|---|---|---|---|---|
| 1 | Relocate `Services/Login.php`, `Services/Rewrites.php`, `Services/Widget.php` to `Infrastructure/Wordpress/{Auth,Rewrites,Widgets}/`; move their `$_POST`/`$_GET` parsing into `Infrastructure/Wordpress/Request/*` command objects; leave only behaviour (login rules, URL building) in a `Services/` or `Application/` class. Then flip `services.no_wordpress_hooks` to strict (N5). | M | low | `services.no_wordpress_hooks` (176 / 2 → 0), `services.no_superglobals` (61 / 7 → ≤ 17 / 6) |
| 2 | **(Done 2026-11-09)** Introduce `Application/Ports/Query_Vars_Interface` with an `Infrastructure/Wordpress/Request/Wp_Query_Vars` adapter; inject it into `Get_Tournament_Handler`, `Competition_Season_Service`, `Page_Title_Service`, `Util_Lookup` and the 11 shortcode adapters (N6). | S | low | `application.no_global_wp` (5 / 4 → 0, flipped to strict) |
| 3 | **(Done 2026-11-09)** `Public_Api::container()` and `Admin_Date_Presenter` resolve the container via `Plugin_Bootstrap` instead of `global $racketmanager`; add `Public_Api.php` and `Admin/` to the `services.no_global_racketmanager` scope (N8). | S | low | `services.no_global_racketmanager` scope widened, stays strict |
| 4 | **(Done 2026-11-09)** Remove `#[AllowDynamicProperties]` from `Fixture_Presentation_Adapter` by declaring the properties it sets (N8). | S | low | — (inspection) |
| 5 | Move the `Admin::add_meta_box()` SQL into `League_Repository::find_titles()` / `Fixture_Repository::find_by_post()`; delete the 3 `global $racketmanager` in `Admin.php` (N8). | S | low | regex `$wpdb` outside `Repositories/` → `Activator`, `Plugin_Bootstrap`, `Util` only |
| 6 | `Cli/Export_Command` receives the container from the WP-CLI bootstrap (`Plugin_Bootstrap::container()`), closing the last `get_instance()` call; flip the rule to strict. | S | low | `all.no_get_instance_outside_bootstrap` (1 / 1 → 0 → strict) |

### P2 — Finish the layering (M–L)

| # | Item | Effort | Risk | Tracked by |
|---|---|---|---|---|
| 7 | **Application → ports.** For the ~20 most-depended-on services (`Competition_Service` 27 files, `Player_Service` 17, `Club_Service` 16, `Tournament_Service` 15, `League_Service` 14, `Event_Query_Service` 14, `League_Query_Service` 12, `Finance_Service` 10, `Competition_Season_Service` 8, `Player_Stats_Service` 8, …) extract `Application/<Context>/Ports/*_Interface`, bind `Interface::class → Service::class` in the provider, retarget the handlers. Do it context by context; the baseline file shows the per-context counts (N1). | L | medium | `application.no_concrete_services` (189 / 86 ↓) |
| 8 | **Retire `?? new` fallbacks.** Make the collaborator parameters required (drop `?` and `= null`), let `Simple_Container` autowire them, delete the fallback. Order: Infrastructure shortcode/AJAX adapters (56 in 12 files — `Competition_`, `Event_`, `Club_`, `League_Shortcode_Adapter` alone are 36) → Services (52 in 35 files, `Validator` first) → Presentation (12) → the long tail. Every removed fallback also removes one of the 449 nullable parameters (N2). | M | medium (test doubles that relied on the defaults must now pass stubs) | `all.no_fallback_construction` (131 / 60 ↓) |
| 9 | **`::class` keys provider-by-provider.** Register by `X::class` (or the port from item 7), replace `$c->get('key')` closures with autowiring where the constructor allows, keep `alias('old_key', X::class)` until the last consumer in `Shortcodes` / AJAX controllers / admin bridges has moved, then delete the alias. Start with `Core_Service_Provider` (197) because every other provider's closures read from it (N3). | L | medium | `all.no_literal_container_keys` (1,989 / 45 ↓) |
| 10 | **Migrate the 8 `Admin_Display` subclasses** to the P2-9 bridge pattern (`final readonly` + `Completes_Admin_Requests` + per-view `*_Admin_Controller` + PRG + `*_Page_View_Model`), smallest first: `Admin_Upgrade`, `Admin_Season`, `Admin_Options`, `Admin_Import`, `Admin_Championship`, `Admin_Index`, `Admin_Event`, `Admin_Result`. Delete `Admin_Display` when the count reaches 0 (N7). | L | high (UI) | `admin.no_admin_display_inheritance` (8 / 8 → 0), `admin.no_template_require` (4 / 3 → 0) |
| 11 | **Finish `$vm` adoption** in the 39 templates: give each partial a small view-model class (or a shared `Partial_View_Model`) and pass it through `Template_Scope`; delete the 5 unused `@var $vm` docblocks or make the wrappers read `$vm` (N10). | M | low | `templates.require_vm` (39 / 39 → 0 → strict) |
| 12 | Route the 7 Presentation `get_option()` calls through `Option_Gateway_Interface` (N11). | S | low | regex (`Presentation/` `get_option(` → 0); add a rule only if it regresses |
| 13 | **Decide and move the 21 misplaced interfaces** (N9): Application ports (`Flash_Message_Store_Interface`, `Notification_Presenter_Interface`, `View_Renderer_Interface`, `Page_Title_Service_Interface`, `Security_Service_Interface`, `Admin_Redirector_Interface`, `Json_Response_Factory_Interface`, the 6 `*_Action_Dispatcher/Handler_Interface`s, `Pending_Result_Orchestrator_Interface`, `Mail_Filter_Service_Interface`, `Championship_Finals_Presenter_Interface`) → `Application/<Context>/Ports/`; infrastructure contracts (`Service_Provider_Interface`, `Export_Formatter_Interface`, `Shortcode_Adapter_Interface`, `Component_Coordinator_Interface`) stay and are allow-listed. Extend `Ports_Location_Test` to fail on any new `interface` under `Services/`, `Infrastructure/`, `Presentation/`, `Admin/` outside the allow-list. | M | low | `Ports_Location_Test` |

### P3 — Aggregate hardening / consolidation (L)

| # | Item | Effort | Risk | Tracked by |
|---|---|---|---|---|
| 14 | **Aggregate hardening**, one aggregate per iteration starting with `Fixture` (49 props + 43 setters, richest invariants) then `Competition`, `Event`, `Player`: properties become `private(set)` / `readonly`, mutation goes through intention-revealing methods (`Fixture::record_result()`, `Entry::accept()`, `Competition::open_season()`), repositories hydrate via the P2-8 factories only. Each step shrinks `domain.no_public_mutable_properties` (N4). | L | medium | `domain.no_public_mutable_properties` (581 / 21 ↓) |
| 15 | **Value objects and events**: `Season`, `Match_Day`, `Score`/`Set_Score`, `Team_Id`/`Player_Id`, `Entry_Fee` (reuse `Money`); events `Fixture_Scheduled`, `Result_Recorded`, `Entry_Accepted`, `Invoice_Issued` dispatched from the aggregates of item 14 so notifications/standings listen instead of being called (N4, S6). | L | medium | count of `Domain/Value_Objects/*`, `Domain/**/Events/*` (manual) |
| 16 | **Decompose `Util` / `Util_Lookup`** into context services behind ports (`Date_Formatter`, `Lookup_Tables`, `Schedule_Clock`, …); 105 consumers, so do it method-family by method-family and keep a thin `Util` façade until the Domain/Application baselines reach 0 (S3, D4). | L | medium | `domain.no_util_static` (14 / 8 → 0), `application.no_util_static` (34 / 19 → 0) |
| 17 | **jQuery runtime retirement** for the admin UI: replace the jQuery UI widgets (autocomplete, draggable, tooltip, colour picker) with native/`@wordpress/components` equivalents feature by feature; drop the `jquery` script dependency when `js.jquery_reference` reaches 0 (N12). | L | medium | `js.jquery_reference` (755 / 77 ↓) |
| 18 | **Archive superseded plans** (`docs/ddd-audit-report.md`, `ddd-architecture-report.md`, `ddd-analysis-2026.md`, `match-migration-*.md`, `move-away-from-match*.md`, `migration-plan.md`, …) under `docs/archive/` with a one-line index; keep `architecture.md`, `public-api.md`, the two dated audits and the living how-tos at the top level (D3). | S | none | — |

---

## Appendix A — Guardrail rule catalogue

### A.1 PHP rules (`tests/Architecture/`) — 37 rules, 23 strict / 14 ratchet

Rules unchanged since the October report are listed by id only; see `docs/ddd-architecture-audit-2026-10.md` Appendix A.1 for their history.

**Strict (23, baseline 0):** `domain.no_wpdb`, `domain.no_outer_layer_imports`, `domain.no_superglobals`, `domain.no_wordpress_functions`, `domain.no_untyped_constructor`, `domain.no_lenient_from_object`, `application.no_wpdb`, `application.no_global_racketmanager`, `application.no_concrete_repositories`, `application.no_presentation_imports`, `application.no_global_wp`, `services.no_wpdb`, `services.no_global_racketmanager`, `services.no_presentation_imports`, `all.no_legacy_helper_calls`, `all.no_deprecated_helper_calls`, `all.no_alias_class_references`, `all.strict_types`, `all.no_extract`, `templates.no_globals_or_db`, `templates.no_global_racketmanager`, `templates.no_legacy_helper_calls`, `scanner.parse_error`.

**Ratchet carried over (5):** `domain.no_util_static` 14 / 8 · `application.no_superglobals` 1 / 1 (`Login/Queries/Get_Login_Form_Query`, `$_SERVER`) · `repositories.no_service_imports` 5 / 5 · `all.no_get_instance_outside_bootstrap` 1 / 1 (`Cli/Export_Command`) · `admin.no_template_require` 4 / 3.

**Ratchet added 2026-11-03 (10):**

| Rule id | Scope | Detects | Kind | Baseline **[A]** | Item |
|---|---|---|---|---|---|
| `domain.no_public_mutable_properties` | the 21 `DOMAIN_ENTITY_FILES` | `public` property or promoted parameter that is not `readonly`, `static`, `private(set)` / `protected(set)`; detail `Class::$prop` | `FORBIDDEN_PUBLIC_MUTABLE_PROPERTY` | **581 / 21** | N4 |
| `application.no_concrete_services` | `Application/` | dependency (import, `new`, static, type, `instanceof`, extends/implements, catch) on `Racketmanager\Services\…` whose name does not end in `_Interface`; one violation per distinct class per file | `FORBIDDEN_DEPENDENCY` | **189 / 86** | N1 |
| `application.no_util_static` | `Application/` | static call on `Util` / `Util_Lookup` | `FORBIDDEN_STATIC_CALL` | **34 / 19** | D4 |
| `application.no_global_wp` | `Application/`, `Services/`, `Presentation/`, `Repositories/`, `Util/` | `global $wp` / `$wp_query` / `$post` / `$current_user` or the `$GLOBALS[...]` spelling | `FORBIDDEN_GLOBAL` | **0 (strict, was 5 / 4)** | N6 |
| `services.no_superglobals` | `Services/`, `Repositories/`, `Presentation/`, `Util/` | `$_POST/$_GET/$_REQUEST/$_SERVER/$_COOKIE/$_FILES` | `FORBIDDEN_VARIABLE` | **61 / 7** | N5 |
| `services.no_wordpress_hooks` | `Services/`, `Application/`, `Presentation/`, `Repositories/` | calls to `add_action`, `add_filter`, `add_shortcode`, `register_widget`, `add_rewrite_rule`, `add_rewrite_tag`, `register_rest_route` (function calls only; `do_action`/`apply_filters` are not registration) | `FORBIDDEN_FUNCTION_CALL` | **176 / 2** | N5 |
| `all.no_fallback_construction` | `src/php/**` excl. `Services/Container/`, `Infrastructure/Wordpress/Bootstrap/` | `?? new X()` / `??= new X()` where `X` is `Racketmanager\…(_Service\|_Repository\|_Presenter\|_Handler\|_Adapter\|_Factory\|_Gateway\|_Renderer\|_Validator\|_Dispatcher\|_Mailer\|_Clock\|_Loader\|_Manager)`; detail `?? new FQCN` | `FORBIDDEN_FALLBACK_NEW` | **131 / 60** | N2 |
| `all.no_literal_container_keys` | `src/php/**` (providers included) | `get\|set\|has\|alias\|has_definition\|is_registered\|remove` called with a string-literal first argument on `$c`, `$container`, `*->container`, `*::$container`; detail `$c->set('key')` | `FORBIDDEN_LITERAL_CONTAINER_KEY` | **1,989 / 45** | N3 |
| `templates.require_vm` | `templates/**` | file never reads the `$vm` variable (docblock mentions do not count); detail `missing $vm` | `REQUIRE_VARIABLE` | **39 / 39** | N10 |
| `admin.no_admin_display_inheritance` | `Admin/` | `extends Racketmanager\Admin\Admin_Display` (`contexts: ['extends']`; imports, type hints and `new` are not counted) | `FORBIDDEN_CLASS_REFERENCE` | **8 / 8** | N7 |

Every new rule has at least one positive and one negative case in `Support_Test/Layer_Rules_Test.php` (e.g. `?? new DateTimeImmutable()` and `?? new Money(0)` do not match `all.no_fallback_construction`; `public readonly`, `public private(set)`, `public static` and promoted `public readonly` do not match the property rule; `$request->get('id')` and `$c->get(X::class)` do not match the container-key rule; `use Admin_Display` in a provider does not match the inheritance rule), plus `applies_to` tests for the exclusions. `test_catalogue_modes_match_the_audit` still lists exactly the 22 strict ids.

### A.2 JS / template rules (`tests/js/architecture/`) — 3 rules, all ratchet

| Rule id | Scope | Detects | Baseline **[J]** |
|---|---|---|---|
| `js.jquery_reference` | `src/js/**/*.js` | Babel identifier token `jQuery` (any position), identifier `$` immediately followed by `(`, **and — since 2026-11-03 — a computed `MemberExpression` on `window`/`globalThis` whose key is the string literal or expression-free template literal `jQuery`** (`jqueryBracketAccess()`, detail `jQuery`, same baseline key as the dotted spelling). `window['jQueryUI']`, `window[name]`, `plugins['jQuery']` and `'jQuery'` in any other string do not count. | **755 / 77** (738 / 71 before the widening) |
| `js.window_global_assignment` | `src/js/**/*.js` | assignment to a direct member of `window`/`globalThis` (any operator), exempting `window.location` and the `X.N = X.N \|\| {}` idiom | 2 / 1 |
| `templates.inline_event_handler` | `templates/**/*.php` | `\son<event>\s*=` for DOM event names | 0 / 0 (empty = strict) |

### A.3 Baseline workflow

Unchanged from October:

```bash
vendor/bin/phpunit --testsuite Architecture              # ≈3.5 s
npm test -- tests/js/architecture                        # <1 s
RM_ARCH_UPDATE_BASELINE=1 vendor/bin/phpunit --testsuite Architecture   # only after *removing* debt
RM_ARCH_UPDATE_BASELINE=1 npm test -- tests/js/architecture
```

1. A failing guardrail means new debt; fix the code, never add to the baseline.
2. Keys are `file|detail` with a tolerated count — line-independent, so `all.no_literal_container_keys` (1,989 occurrences, 1,187 keys) does not churn when code moves inside a file.
3. When a ratchet reaches 0, flip it to `strict` in `Layer_Rules.php`, add it to `test_catalogue_modes_match_the_audit`, regenerate. Expected first candidates: `application.no_global_wp` (P1-2), `all.no_get_instance_outside_bootstrap` (P1-6), `services.no_wordpress_hooks` (P1-1), `templates.require_vm` (P2-11), `admin.no_admin_display_inheritance` (P2-10).
4. `Baseline_Integrity_Test` rejects entries for strict rules, unknown rule ids and unsorted keys.

---

## Appendix B — Reproducing the measurements

```bash
cd wp-content/plugins/racketmanager

# [A] per-rule occurrences / files straight from the PHP baseline
python3 -c 'import json;b=json.load(open("tests/Architecture/baseline.json"));[print(r.ljust(45),sum(e.values()),len({k.split("|")[0] for k in e})) for r,e in b.items()]'

# [A] per-layer split of a cross-cutting rule (here all.no_fallback_construction)
python3 -c 'import json,collections;b=json.load(open("tests/Architecture/baseline.json"))["all.no_fallback_construction"];c=collections.Counter();f=collections.defaultdict(set)
for k,v in b.items():
  l=k.split("/")[2];c[l]+=v;f[l].add(k.split("|")[0])
[print(l,c[l],len(f[l])) for l in sorted(c)]'

# [A] most-depended-on concrete services in Application/ (files per class)
python3 -c 'import json,collections;b=json.load(open("tests/Architecture/baseline.json"))["application.no_concrete_services"];print(collections.Counter(k.split("depends on ")[1].rsplit(chr(92),1)[-1] for k in b).most_common(12))'

# [J] per-rule occurrences / files from the Jest baseline
node -e 'const b=require("./tests/js/architecture/baseline.json");for(const r in b){const k=Object.keys(b[r]);console.log(r,k.reduce((s,x)=>s+b[r][x],0),new Set(k.map(x=>x.split("|")[0])).size)}'

# [M] jQuery bracket access (cross-check for the widened rule) — expect 17
php -r '$n=0;foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator("src/js")) as $p){if($p->getExtension()!=="js")continue;$n+=preg_match_all("/\b(window|globalThis)\s*\[\s*[\x27\"`]jQuery[\x27\"`]\s*\]/",file_get_contents($p));}echo $n,PHP_EOL;'

# [M] Application files importing concrete Services (lines / files)
php -r '$n=0;$f=0;foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator("src/php/Application")) as $p){if($p->getExtension()!=="php")continue;$c=preg_match_all("/^use Racketmanager\\\\Services\\\\/m",file_get_contents($p));if($c){$n+=$c;$f++;}}echo "$n / $f",PHP_EOL;'

# [M] string vs ::class container keys and aliases
php -r '$s=0;$c=0;$a=0;foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator("src/php")) as $p){if($p->getExtension()!=="php")continue;$t=file_get_contents($p);$s+=preg_match_all("/->set\(\s*[\x27\"]/",$t);$c+=preg_match_all("/->set\(\s*\\\\?[A-Z]\w*(\\\\\w+)*::class/",$t);$a+=preg_match_all("/->alias\(/",$t);}echo "set(string) $s set(::class) $c alias() $a",PHP_EOL;'

# [M] nullable collaborator constructor parameters (?X_Service $x = null, …)
php -r '$n=0;foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator("src/php")) as $p){if($p->getExtension()!=="php")continue;$n+=preg_match_all("/\?[A-Z]\w*(?:_Service|_Repository|_Presenter|_Handler|_Adapter|_Factory|_Gateway|_Renderer|_Validator|_Dispatcher|_Interface|_Mailer|_Clock|_Loader|_Manager)\s+\\\$\w+\s*=\s*null/",file_get_contents($p));}echo $n,PHP_EOL;'

# [M] global $wp sites plugin-wide
php -r '$n=0;$f=0;foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator("src/php")) as $p){if($p->getExtension()!=="php")continue;$c=preg_match_all("/global\s+\\\$wp\b/",file_get_contents($p));if($c){$n+=$c;$f++;}}echo "$n / $f",PHP_EOL;'

# [M] templates that never mention $vm (the AST rule additionally catches docblock-only mentions)
php -r '$n=0;$t=0;foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator("templates")) as $p){if($p->getExtension()!=="php")continue;$t++;if(!str_contains(file_get_contents($p),"\$vm"))$n++;}echo "$n / $t",PHP_EOL;'

# [M] interfaces declared outside Domain/Ports, Application/Ports, Repositories/Interfaces
php -r 'foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator("src/php")) as $p){if($p->getExtension()!=="php")continue;$r=str_replace(getcwd()."/","",$p);if(preg_match("#^src/php/(Services|Infrastructure|Presentation|Admin|Util|Rest)/#",$r)&&preg_match("/^interface\s/m",file_get_contents($p)))echo $r,PHP_EOL;}'

# [M] $wpdb outside Repositories/
php -r '$it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator("src/php",FilesystemIterator::SKIP_DOTS));foreach($it as $f){$p=$f->getPathname();if(str_contains($p,"/Repositories/"))continue;$n=preg_match_all("/\\\$wpdb\b/",file_get_contents($p));if($n)echo "$n\t$p",PHP_EOL;}'

# test count without running the suite
vendor/bin/phpunit --list-tests | grep -c '^ - '
```
