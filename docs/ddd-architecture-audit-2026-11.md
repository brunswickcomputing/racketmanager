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
| **N4** | Anemic / Active-Record aggregates (public mutable state, no invariants) | **209 public mutable properties / 7 entities** (574 / 21 at ratchet start; Iteration 1 `Fixture` & `Rubber` hardened −88; Iteration 2 `Competition` hardened −74; Iteration 3 `Event` hardened −62; Iteration 4 `Player`, `User` & `Player_Error` hardened −99; Iteration 5 `Season` hardened −1; Iteration 6 `Results_Checker` hardened −10; Iteration 7 `Message` & `Results_Report` hardened −13; Iteration 8 `Tournament_Entry` hardened −7; Iteration 9 `Club_Player` hardened −4; Iteration 10 `Club_Role` hardened −7); 3 domain events, 3 value objects, 12 enums **[M]** | `domain.no_public_mutable_properties` |
| **N5** | WordPress boundary code inside `Services/` (hook registration, superglobal parsing) | **176 hook registrations / 2 files** (`Rewrites` 152, `Login` 24); **61 superglobal reads / 7 files** (`Login` 44) | `services.no_wordpress_hooks`, `services.no_superglobals` |
| **N6** | `global $wp` query-var reads outside Infrastructure | **5 / 4** in the ratcheted layers (incl. `Application/Tournament/Queries/Get_Tournament_Handler`); 24 / 15 plugin-wide **[M]** | `application.no_global_wp` |
| **N7** | Remaining `Admin_Display` subclasses with inline request processing | **0 / 0 (Done 2026-11-15)**; was 8 / 8. `Admin_Display` deleted; screens are `final readonly` bridges routed by `Admin_Page_Router`. `admin.no_template_require` also 0 / 0, both flipped to strict | `admin.no_admin_display_inheritance` |
| **N8** | Root-file residue: `Admin.php` raw `$wpdb`, `global $racketmanager`, `get_instance()`, `#[AllowDynamicProperties]` | `Admin.php` 0 `$wpdb` refs, 0 `global $racketmanager` **(Done 2026-11-09)**; `Public_Api` 0 **(Done 2026-11-09)**; `Export_Command` 0 `get_instance()` **(Done 2026-11-10)**; 0 dynamic-property classes **(Done 2026-11-09)** **[V]** | `all.no_get_instance_outside_bootstrap` (strict at 0); rest §9 P1 |
| **N9** | Interfaces declared outside the port locations | **0 misplaced / 9 allow-listed (Done 2026-11-16)**; was 21 (15 in `Services/`, 5 in `Infrastructure/`, 1 in `Admin/`) **[V]** | `Ports_Location_Test` (§9 item 13) |
| **N10** | Templates not reading `$vm` | **0 / 279 enforced + 24 exempt (Done 2026-11-16)**; 303 total now (302 at initial audit); was 39 / 302 (34 never mentioned `$vm`; 5 declared it in a docblock but never read it). 15 dynamic templates migrated to read `$vm`; 24 static/WP templates exempt from requirement; rule flipped to strict | `templates.require_vm` |
| **N11** | Presentation calls `get_option()` directly although `Option_Gateway_Interface` exists | **0 / 0 (Done 2026-11-16)**; was 7 calls / 4 files vs 22 port consumers **[M]**. Routed through `Option_Gateway_Interface` with safe format defaults; regression test enforced | — (§9 item 12) |
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
| `Application/` | ✅ | ✅ | 🔒 1 / 1 | ✅ (`application.no_presentation_imports`, `no_concrete_repositories`) | 🔒 **189 / 86** | 🔒 1 / 1 | 🔒 **1 / 1** (`Get_Tournament_Handler`) | 🔒 0 (in scope) | 🔒 **34 / 19** | ✅ `Application/Ports/` + `Application/<Context>/Ports/` (N1, N9 complete) |
| `Services/` | ✅ | ✅ | 🔒 **57 / 6** | ✅ (`services.no_presentation_imports`) | — | 🔒 **52 / 35** | 🔒 2 / 2 | 🔒 **176 / 2** | ⚠️ not ratcheted | ✅ 0 misplaced / 2 allow-listed (N9) |
| `Repositories/` | — (owner) | ✅ (`services.no_global_racketmanager` scope) | 🔒 0 (in scope) | 🔒 5 / 5 (`repositories.no_service_imports`) | — | 🔒 4 / 4 | 🔒 0 | 🔒 0 | ⚠️ | ✅ `Repositories/Interfaces/` (21) |
| `Presentation/` | — | ✅ | 🔒 0 (in scope) | — | — | 🔒 **12 / 5** | 🔒 0 | 🔒 0 | ⚠️ | 🔒 `get_option()` 0 / 4 (Done 2026-11-16) |
| `Infrastructure/` | ⚠️ `Plugin_Bootstrap` 4 (`show_errors()`, no query) | ✅ (excl. `Plugin_Bootstrap`) | — (driving adapters may parse HTTP) | — | — | 🔒 **56 / 12** | — (allowed) | — (allowed) | ⚠️ | ✅ 0 misplaced / 7 allow-listed (N9) |
| `Admin/` | — | ✅ (`services.no_global_racketmanager` scope; `Admin_Date_Presenter` resolved 2026-11-09) | ⚠️ 334 / 53 **[M]** | — | — | 🔒 2 / 1 | — | — | ⚠️ | 🔒 `Admin_Display` subclasses **8 / 8**; `admin.no_template_require` 4 / 3 |
| `Util/` | ⚠️ `Util.php` 4 **[M]** | ✅ | 🔒 4 / 1 (`Util_Lookup`) | — | — | 🔒 3 / 1 | 🔒 **2 / 1** (`Util_Lookup`) | — | (is `Util`) | — |
| `Rest/` | — | ✅ | — | — | — | 🔒 1 / 1 | — | — | ⚠️ | — |
| `templates/` | ✅ | ✅ | ✅ | ✅ (`templates.no_legacy_helper_calls`) | — | — | — | — | — | ✅ `templates.require_vm` |
| Container / providers | — | — | — | — | — | excluded by design (`Services/Container/`, `Plugin_Bootstrap`) | — | — | — | 🔒 literal keys **1,989 / 45** plugin-wide (`all.no_literal_container_keys`, providers included) |

Per-layer splits of the cross-cutting `all.*` rules above were read from the baseline file prefixes (Appendix B). `all.no_fallback_construction` 131 / 60 = Infrastructure 56 / 12 + Services 52 / 35 + Presentation 12 / 5 + Repositories 4 / 4 + Util 3 / 1 + Admin 2 / 1 + Application 1 / 1 + Rest 1 / 1.

---

## 4. Legacy pattern inventory (N1–N12)

Each item: where / size · why it matters for DDD · tracked by.

### N1 — Application depends on concrete `Services\*` classes

- **Size:** 189 distinct concrete-service dependencies across 86 of 312 `Application/` files spanning **30** distinct concrete target services **[A]** (`application.no_concrete_services`; one violation per distinct class per file, however spelled). Regex: 190 `use Racketmanager\Services\…` lines in 87 files **[M]**, of which exactly one is an interface (`Flash_Message_Store_Interface`). Most-depended-on targets **[A]** (files): `Competition_Service` 27, `Player_Service` 17, `Club_Service` 16, `Tournament_Service` 15, `League_Service` 14, `Event_Query_Service` 14, `League_Query_Service` 12, `Finance_Service` 10, `Competition_Season_Service` 8, `Player_Stats_Service` 8, `Competition_Entry_Service` 7, `Registration_Service` 6, `Settings_Service` 6, `Player_Query_Service` 5, `Team_Service` 4, `Fixture_Detail_Service` 4.
- **Partitioning & Migration Order (items 7a–7h):** To prevent high-risk monolithic PRs and decouple handlers safely, all 189 violations across the 30 target services are partitioned into 7 bounded-context clusters mapped to their respective container providers, executed from lowest coupling to central orchestrators, followed by a strict rule transition:
  - **7a: Fixture & Match Operations** (13 violations / 10 files; 9 targets: `Fixture_Detail_Service` 4, `Fixture_Service` 2, `Result_Reporting_Service` 1, `Fixture_Link_Service` 1, `Fixture_Permission_Service` 1, `Fixture_Maintenance_Service` 1, `Fixture_Result_Manager` 1, `Results_Checker_Manager` 1, `Match_Date_Round_Validator` 1). Ports in `Application/Fixture/Ports/` bound in `Fixture_Service_Provider`; pure static `Match_Date_Round_Validator` relocated to `Application/Admin/Validation/`. Consumer contexts: `Admin/` (3 files, 5 violations), `Event/` (1 file, 1 violation), `Fixture/` (2 files, 3 violations), `Matches/` (3 files, 3 violations), `Tournament/` (1 file, 1 violation). Ratchet delta: 189 → 176 (−13).
  - **7b: Finance & Core Settings** (19 violations / 15 files; 4 targets: `Finance_Service` 10, `Settings_Service` 6, `Stripe_Settings` 2, `Validator_Config` 1). Ports in `Application/Ports/` bound in `Core_Service_Provider`; `Stripe_Settings` data shape moved to DTOs and `update_payment` use case; `Competition_Config_Validator_Factory` adapter bound in `Core_Service_Provider`. Consumer contexts: `Admin/` (1 file, 2 violations), `Club/` (2 files, 3 violations), `Competition/` (4 files, 5 violations), `Event/` (1 file, 1 violation), `Finance/` (3 files, 3 violations), `General/` (2 files, 3 violations), `Login/` (1 file, 1 violation), `Tournament/` (1 file, 1 violation). Ratchet delta: 176 → 157 (−19).
  - **7c: Club & Team Management** (21 violations / 18 files; 3 targets: `Club_Service` 16, `Team_Service` 4, `Validator_Club` 1). Ports in `Application/Club/Ports/` bound in `Club_Service_Provider`; `Club_Validator_Factory` adapter bound in `Club_Service_Provider`. Consumer contexts: `Admin/` (3 files, 5 violations), `Club/` (6 files, 6 violations), `Competition/` (4 files, 5 violations), `Event/` (3 files, 3 violations), `General/` (1 file, 1 violation), `League/` (1 file, 1 violation). Ratchet delta: 142 → 121 (−21).
  - **7d: Tournament Operations** (15 violations / 15 files; 1 target: `Tournament_Service` 15). Port `Application/Tournament/Ports/Tournament_Service_Interface` bound in `Competition_Service_Provider`. Consumer contexts: `Competition/` (3 files, 3 violations), `General/` (1 file, 1 violation), `Tournament/` (11 files, 11 violations). Ratchet delta: 157 → 142 (−15).
  - **7e: League & Standings Services** (29 violations / 15 files; 5 targets: `League_Service` 14, `League_Query_Service` 12, `League_Schedule_Service` 1, `Promotion_Relegation_Service` 1, `Standings_Service` 1). Ports in `Application/League/Ports/` bound in `Competition_Service_Provider` (`competition_league_schedule_service` for `Services\Competition\League_Schedule_Service`). Consumer contexts: `Admin/` (3 files, 6 violations), `Event/` (2 files, 4 violations), `League/` (10 files, 19 violations). Ratchet delta: 121 → 92 (−29).
  - **7f: Player & Registration Services** (36 violations / 22 files; 4 targets: `Player_Service` 17, `Player_Stats_Service` 8, `Registration_Service` 6, `Player_Query_Service` 5). Ports in `Application/Player/Ports/` bound in `Player_Service_Provider` (player services) and `Club_Service_Provider` (`Registration_Service`). `Player_Query_Service_Interface` declares clean shapes `get_competitions(Player, array|string)` and `get_matches(Player, ...)`. Consumer contexts: `Admin/` (2 files, 3 violations), `Club/` (1 file, 2 violations), `Competition/` (7 files, 11 violations), `Event/` (5 files, 7 violations), `General/` (2 files, 6 violations), `League/` (2 files, 4 violations), `Matches/` (1 file, 1 violation), `Player/` (2 files, 2 violations). Ratchet delta: 92 → 56 (−36).
  - **7g: Competition & Event Core Orchestration** (56 violations / 33 files; 4 targets: `Competition_Service` 27, `Event_Query_Service` 14, `Competition_Season_Service` 8, `Competition_Entry_Service` 7). Ports in `Application/Competition/Ports/` bound in `Competition_Service_Provider`. Consumer contexts: `Admin/` (3 files, 4 violations), `Club/` (1 file, 2 violations), `Competition/` (13 files, 22 violations), `Event/` (12 files, 23 violations; includes `Event_Allowlist_Guard` parameter), `League/` (1 file, 2 violations), `Tournament/` (3 files, 3 violations). Ratchet delta: 56 → 0 (−56).
  - **7h: Strict Rule Transition & Baseline Purge** (0 violations / 0 files). Final transition once 7a–7g reach 0: switch rule mode from `Rule_Mode::RATCHET` to `Rule_Mode::STRICT` in `tests/Architecture/Rules/Layer_Rules.php`, align `Layer_Rules_Test.php`, and remove key from `baseline.json`.
  - *File overlap note:* Handlers frequently inject services across domains (e.g. an Admin command injecting both `Competition_Service` and `Club_Service`), resulting in 128 cluster file memberships across 86 unique files. Per-cluster file counts cannot be summed; only violations sum directly (13 + 19 + 21 + 15 + 29 + 36 + 56 = 189).
  - *Migration strategy:* Leaf contexts (7a–7d) are addressed first to validate interface extraction and container aliasing on smaller handler groups before undertaking core domain services (7e–7f) and large orchestrators (7g).
- **Why it matters:** `docs/architecture.md` ("Services vs Application Layering Rules") says use-case handlers talk to the outside world through ports and that `Services/` is the legacy orchestration layer to be dissolved. With handlers hard-wired to concrete orchestrators the dependency arrow points the wrong way — `Services/` cannot be retired or replaced piecemeal, and a handler cannot be unit-tested without the service's full object graph (which, per N2, builds repositories on demand).
- **Tracked by:** `application.no_concrete_services` (strict at 0, was 189 / 86 across items 7a–7g). The rule now operates in strict mode (0 baseline entries in `tests/Architecture/baseline.json`), enforcing that all Application code depends on ports and preventing any concrete service dependencies.
- **Reproducing the measurements [A]:**
  ```bash
  # Total violations and unique files from baseline
  python3 -c 'import json; b = json.load(open("tests/Architecture/baseline.json"))["application.no_concrete_services"]; v = sum(b.values()); f = len({k.split("|")[0] for k in b}); print(f"{v} violations across {f} files")'

  # Distinct concrete target services (30 targets)
  python3 -c 'import json, collections; b = json.load(open("tests/Architecture/baseline.json"))["application.no_concrete_services"]; print(len(collections.Counter(k.split("depends on ")[1] for k in b)), "targets")'
  ```

### N2 — Optional-dependency / hidden-default anti-pattern

- **Size:** 131 `?? new X()` / `??= new X()` fallbacks constructing a plugin collaborator, in 60 files **[A]** (`all.no_fallback_construction`): Infrastructure 56 / 12, Services 52 / 35, Presentation 12 / 5, Repositories 4 / 4, Util 3 / 1, Admin 2 / 1, Application 1 / 1, Rest 1 / 1. The raw count is 139 / 65; the rule's collaborator pattern (`_Service|_Repository|_Presenter|_Handler|_Adapter|_Factory|_Gateway|_Renderer|_Validator|_Dispatcher|_Mailer|_Clock|_Loader|_Manager` under `Racketmanager\`) intentionally skips `DateTimeImmutable` ×2, `stdClass`, `WP_REST_Request` and the domain strategy `Schedule_Round_Robin`, and the rule excludes `Services/Container/` and `Infrastructure/Wordpress/Bootstrap/`. Files with the most fallbacks: `Competition_Shortcode_Adapter` 12, `Event_Shortcode_Adapter` 11, `Club_Shortcode_Adapter` 7, `League_Shortcode_Adapter` 6, `Validator` 6, `Tournament_Ajax_Adapter` 4, `General_Shortcode_Adapter` 4, `League_Standings_Presenter` 4. Classes most often constructed this way: `Competition_Shortcode_Presenter` 14, `Player_Presenter` 14, `Club_Presenter` 10, `Option_Service` 9, `Wp_Mailer` 9, `Tournament_Presenter` 8, `Fixture_Repository` 5. 449 constructor parameters of the shape `?X_Service $x = null` **[M]** feed these fallbacks.
- **Why it matters:** the fallback *is* a service locator with the container removed. It bypasses the composition root (`.junie/guidelines.md` §3), hides the real dependency graph from the container and from readers, silently instantiates concrete repositories inside services (so `Validator::team_repository()` reaches `$wpdb` without any port), and makes every "optional" collaborator effectively required. It is also why `Simple_Container` autowiring (P3-15) has had no practical effect: a constructor whose parameters all default to `null` autowires to nothing.
- **Tracked by:** `all.no_fallback_construction` (ratchet 131 / 60). `$x ?: new X()` and plain `new X()` outside a coalesce are not counted (documented non-goal; the former does not occur in `src/php`, the latter is the subject of D2).

### N3 — Service-locator residue in the container

- **Size:** 1,989 string-keyed container calls in 45 files **[A]** (`all.no_literal_container_keys`; receivers `$c`, `$container`, `*->container`, `*::$container`; methods `get|set|has|alias|has_definition|is_registered|remove`). Providers: `Competition_Service_Provider` 428, `Admin_Screens_Service_Provider` 343, `Admin_Service_Provider` 275, `Core_Service_Provider` 197, `Fixture_Service_Provider` 148, `Presentation_Service_Provider` 93, `Club_Service_Provider` 84, `Player_Service_Provider` 51 (1,619). Consumers that pull from the container at runtime: `Public/Shortcodes` 48, `Frontend_Ajax_Controller` 39, `Rest_Resources` 24, `Admin_Ajax_Controller` 19, `Component_Coordinator` 19, `Fixture_Ajax_Adapter` 18, `Admin_League` 17, `Finance_Ajax_Controller` 16, `Admin_Cup` 15, `Admin_Display` 14, `Admin_Tournament` 14, `Admin.php` 13, `Account_Ajax_Controller` 12, `Club_Ajax_Controller` 12, `Fixture_Ajax_Controller` 10, `Export_Controller` 9, `RacketManager.php` 8, `Tournament_Ajax_Controller` 8, `Admin_Club` 7, `Public_Api` 6, … (370 in 37 files). Definitions: 357 `set('string', …)` vs 25 `set(X::class, …)`; 187 `alias()` bridges **[M]**.
- **Why it matters:** string keys are untyped, un-navigable and un-refactorable — the IDE cannot find the consumers of `'league_service'`, a typo is a runtime error, and the 187 aliases exist only to let two spellings coexist. Runtime `->get()` in adapters, bridges and `Shortcodes` means those classes are service locators: their dependencies are invisible in the constructor and they cannot be constructed in a test without a populated container. P3-15 made `::class` keys and autowiring *possible*; this is the measurement of how much has not yet moved.
- **Tracked by:** `all.no_literal_container_keys` (ratchet 1,989 / 45). Providers are intentionally *in* scope — they are where the migration happens. `$request->get('id')`, `$settings->get('x')` and `$c->get(X::class)` are not counted.

### N4 — Anemic / Active-Record aggregates

- **Size:** 209 public mutable properties across 7 Domain entities **[A]** (`domain.no_public_mutable_properties`; 574 across 21 entities at ratchet start; `readonly`, `static`, `private(set)`/`protected(set)` and constants exempt): `League_Team` 64, `League` 42, `Tournament` 37, `Team` 22, `Club` 21, `Invoice` 14, `Charge` 9. (Iteration 1 hardened `Fixture` [49 properties] and `Rubber` [39 properties]; Iteration 2 hardened `Competition` [74 properties]; Iteration 3 hardened `Event` [62 properties]; Iteration 4 hardened `Player` [51 properties], `User` [44 properties], and `Player_Error` [4 properties]; Iteration 5 hardened `Season` [1 property] to `public private(set)`; Iteration 6 hardened `Results_Checker` [10 properties] to `public private(set)`; Iteration 7 hardened `Message` [9 properties] and `Results_Report` [4 properties] to `public private(set)`; Iteration 8 hardened `Tournament_Entry` [7 properties] to `public private(set)`; Iteration 9 hardened `Club_Player` [4 properties] to `public private(set)`; Iteration 10 hardened `Club_Role` [7 properties] to `public private(set)`). The domain vocabulary is thin: 3 domain events (`Abstract_Domain_Event` + dispatcher/listener plumbing), 3 value objects (`Money`, `Date_Range`, `Json_Encoder`), 12 enums **[M]**; 43 `$a->b->c->d` property chains in `Presentation/` + `Application/` **[M]** read straight through aggregates.
- **Why it matters:** with every field writable from anywhere, invariants (a fixture cannot have a result before both teams are set; an entry cannot be accepted after the deadline; standings must be recomputed when a result changes) live in whichever service remembers to enforce them, and repositories hydrate by assigning public properties instead of going through the P2-8 factories. This is the textbook anemic-domain smell and the main reason `Services/` is still 140 files.
- **Tracked by:** `domain.no_public_mutable_properties` (ratchet 209 / 7; was 574 / 21 at ratchet start; scope `DOMAIN_ENTITY_FILES`, so DTOs, value objects and the scheduling engine are untouched).
- **Iteration 1 (Done 2026-11-17):** `Fixture` aggregate root (49 properties) and child entity `Rubber` (39 properties) hardened with PHP 8.4 asymmetric visibility (`public private(set)`), eliminating external mutations while preserving zero-cost read access across views and templates. State mutation encapsulated behind intention-revealing domain methods (`Fixture::record_result()`, `confirm_result()`, `reschedule()`, `assign_teams()` and `Rubber::record_result()`, `assign_players()`, `reschedule()`). `Fixture_Status_Policy` internalised to aggregate delegation (`$fixture->set_status_flags()`). Production writes and test suites migrated, ratcheting down `domain.no_public_mutable_properties` from 574 / 21 at baseline start to 486 / 19 (−88 entries).
- **Iteration 2 (Done 2026-11-19):** `Competition` aggregate root (74 properties) hardened with PHP 8.4 asymmetric visibility (`public private(set)`), eliminating external mutations while preserving read backwards-compatibility. State mutations encapsulated into intention-revealing lifecycle operations (`open_season()`, `close_season()`, `start_season()`, `complete_season()`) and domain setters (`set_finals()`, `set_events()`, `set_teams()`, `set_players()`, `set_player()`, `set_type()`, `set_entry_link()`, etc.). Production query handlers (`Get_Competition_Handler`, `Get_Competition_Players_Handler`, `Get_Competition_Teams_Handler`, `Get_Competitions_Handler`), services (`Player_Query_Service`), and all test factories/suites migrated. Architecture baseline ratcheted down from 486 / 19 to 412 / 18 (−74 entries).
- **Iteration 3 (Done 2026-11-19):** `Event` aggregate root (62 properties) hardened with PHP 8.4 asymmetric visibility (`public private(set)`), eliminating external property writes while preserving read compatibility. Invariants enforced via explicit domain mutations (`Event::update_configuration()`, `Event::set_matches()`, `Event::set_stages()`, `Event::set_rounds()`, `Event::set_champion()`, `Event::set_runner_up()`, `Event::record_winner()`, `Event::set_parent_league()`, `Event::set_competition()`). Hydration aligned through `Event_Hydration_DTO` and `Event_Factory`, test ergonomics unified via `Event_Test_Factory`, and full unit verification added in `Event_Test.php`. Architecture baseline ratcheted down from 412 / 18 to 350 / 17 (−62 entries).
- **Iteration 4 (Done 2026-11-21):** `Player` aggregate root (51 properties), `User` (44 properties), and `Player_Error` (4 properties) hardened with PHP 8.4 asymmetric visibility (`public private(set)`), eliminating external mutations while preserving read backwards-compatibility. Intention-revealing domain mutators added to `Player` (`set_matches()`, `set_stats()`, `set_statistics()`, `set_stats_summary()`, `set_club()`, `set_teams()`, `set_link()`, `set_index()`, `set_locked_user_name()`) and `User` (`set_firstname()`, `set_surname()`, `set_contactno()`, `set_gender()`, `set_btm()`, `set_year_of_birth()`, `set_password()`, `set_re_password()`, `set_opt_ins()`, `set_locked_status()`, `set_favourites()`, `update_profile()`). `User_Repository::hydrate_user()` realigned to pre-assemble user metadata into the hydration array prior to `User_Factory::from_array()` instead of post-hydration property mutation; `sync_opt_ins()` and `Account_Ajax_Adapter` updated to use domain mutators. `Tournament_Shortcode_Adapter` updated to type `Tournament_Players_Result::$player` as `?Player` and invoke `set_matches()`. Test factory `Player_Test_Factory` enhanced with reflection fallback for overrides, and legacy test stubs migrated. Architecture baseline ratcheted down from 350 / 17 to 251 / 14 (−99 entries).
- **Iteration 5 (Done 2026-11-21):** `Season` aggregate root (1 property: `$name`) hardened with PHP 8.4 asymmetric visibility (`public private(set)`), eliminating external property mutation while preserving public read backwards-compatibility across templates (`show-seasons.php`, `show-tournaments.php`, `results-checker.php`). `$id` standardized to `public private(set)` matching all other domain entities. Intention-revealing lifecycle mutators (`set_name()`, `set_id()`) and getters (`get_name()`, `get_id()`) preserved with complete PHPDoc annotations resolving all docblock violations. Comprehensive unit tests added in `Season_Test.php`. Architecture baseline ratcheted down from 251 / 14 to 250 / 13 (−1 entry).
- **Iteration 6 (Done 2026-11-22):** `Results_Checker` aggregate (10 properties) hardened with PHP 8.4 asymmetric visibility (`public private(set)`), eliminating external property mutation while preserving public read backwards-compatibility across presenters (`Results_Checker_Presenter`). Encapsulated state lifecycle with intention-revealing domain mutators (`approve(?int $user_id)`, `handle(?int $user_id)`, `is_approved()`, `is_handled()`, `is_outstanding()`, `is_player_error()`, `is_match_error()`), updated `Results_Checker_Repository` to persist via `set_id()` and `set_updated_date()`, and refactored `Results_Checker_Manager` to use domain methods and safe point rule lookups (`get_point_rule()`). Dedicated unit test suites added for `Results_Checker` and `Results_Checker_Manager`. Architecture baseline ratcheted down from 250 / 13 to 240 / 12 (−10 entries).
- **Iteration 7 (Done 2026-11-22):** Communication aggregate (`Message` [9 properties] and `Results_Report` [4 properties]) hardened with PHP 8.4 asymmetric visibility (`public private(set)`), eliminating external property mutation while preserving public read backwards-compatibility across templates and presenters. State mutation encapsulated via intention-revealing methods (`is_read()`, `is_unread()`, `mark_as_read()`, `mark_as_unread()`, `is_owned_by()`, `has_data()`, `has_report()`, `is_for_match()`), full getters and setters, and domain mutators. `Results_Report` mutators synchronize decoded `data` object and serialized `result_object` JSON. `Message_Repository` and `Results_Report_Repository` updated to persist generated IDs via `set_id()`. Comprehensive unit tests added in `Message_Test.php` and `Results_Report_Test.php`. Architecture baseline ratcheted down from 240 / 12 to 227 / 10 (−13 entries).
- **Iteration 8 (Done 2026-11-22):** `Tournament_Entry` aggregate (7 properties) hardened with PHP 8.4 asymmetric visibility (`public private(set)`), eliminating external property mutation while preserving zero-cost read compatibility across templates (`tournament-payment.php`, `tournament-payment-complete.php`, `tournament-entry.php`). State mutation and lifecycle encapsulated via intention-revealing domain methods (`is_pending()`, `is_confirmed()`, `is_withdrawn()`, `withdraw()`, `has_fee()`, `has_club()`, `is_for_tournament()`, `is_for_player()`) and domain getters/setters (`get_club()`, `set_club()`, etc.). Cleaned docblocks across `Tournament_Entry`, `Tournament_Entry_Factory`, and `Tournament_Entry_Hydration_DTO` to satisfy WordPress coding standards. Updated template integration tests in `Presentation_Modernization_Templates_Test` and expanded dedicated unit tests in `Tournament_Entry_Test.php`. Architecture baseline ratcheted down from 227 / 10 to 220 / 9 (−7 entries). Remaining 9 entities: `League_Team` 64, `League` 42, `Tournament` 37, `Team` 22, `Club` 21, `Invoice` 14, `Charge` 9, `Club_Role` 7, `Club_Player` 4.
- **Iteration 9 (Done 2026-11-23):** `Club_Player` aggregate (4 properties: `id`, `player_id`, `club_id`, `player`) hardened with PHP 8.4 asymmetric visibility (`public private(set)`), eliminating external property mutation while preserving public read compatibility. New state inspection queries added (`is_active()`, `is_pending()`, `is_removed()`, `is_system_record()`). New typed getters and setters added (`get_player()`, `set_player()`, `set_player_id()`, `set_club_id()`, `set_system_record()`, `set_status()`, `set_created_date()`, `set_created_user()`, `set_requested_date()`, `set_requested_user()`, plus `get_created_date()`, `get_created_user()`, `get_requested_date()`, `get_requested_user()`, `get_removed_date()`, `get_removed_user()`). Already present before this iteration and unchanged in behaviour: `approve()` and `remove()` lifecycle methods with `Invalid_Status_Exception` invariants, which are now covered by new unit tests; `set_removal_date()` and `set_removal_user()` pre-existed and only had their parameters widened to nullable. `Registration_Repository::save()` now reads through getters (`get_requested_date()`, `get_requested_user()`, `get_created_date()`, `get_created_user()`, `get_removed_date()`, `get_removed_user()`) for both insert and update payloads; insert, update and `find_by_id()` tests were added to `Registration_Repository_Test.php`, asserting strict parameter binding and payloads. Unit tests added in `Club_Player_Test.php` and `Registration_Repository_Test.php`. Architecture baseline ratcheted down from 220 / 9 to 216 / 8 (−4 entries). Remaining 8 entities: `League_Team` 64, `League` 42, `Tournament` 37, `Team` 22, `Club` 21, `Invoice` 14, `Charge` 9, `Club_Role` 7.
- **Iteration 10 (Done 2026-11-23):** `Club_Role` aggregate (7 properties: `id`, `user_id`, `club_id`, `role_id`, `role`, `club`, `user`) hardened with PHP 8.4 asymmetric visibility (`public private(set)`), eliminating external property mutation while preserving public read compatibility. Safe scalar defaults (`0`) and nullable defaults (`null`) added. Typed getters (`get_role()`, `get_club()`, `get_user()`) and domain mutators (`set_id()`, `set_user_id()`, `set_club_id()`, `set_role_id()`, `set_role()`, `set_club()`, `set_user()`, `reassign_user()`) introduced. Backed enum classification helpers added (`is_match_secretary()`, `is_coach()`, `is_treasurer()`, `get_role_enum()`). Constructor normalized unknown lookup fallback with `?: null`. External callers aligned: `Club_Role_Repository::hydrate_user()` migrated to `set_user()`, and test fixtures in `Club_Ajax_Adapter_Test` and `Validator_Club_Test` updated to use domain setters. In `Club_Service::reassign_role_user()`, role ID and user ID are cast to `int`, non-existent roles return `null` without saving, and reassignment calls `reassign_user()`, which also clears the stale loaded `user` object. Dedicated unit tests added in `Club_Role_Test.php` (defaults, hydration roundtrips, getters, mutators, role queries, and `private(set)` mutation guards) and two regression tests added in `Club_Service_Test.php`. Architecture baseline ratcheted down from 216 / 8 to 209 / 7 (−7 entries). Remaining 7 entities: `League_Team` 64, `League` 42, `Tournament` 37, `Team` 22, `Club` 21, `Invoice` 14, `Charge` 9.

### N5 — WordPress boundary code inside `Services/`

- **Size:** hook / shortcode / rewrite / REST registration: 176 calls in 2 files **[A]** (`services.no_wordpress_hooks`: `Rewrites.php` 152 `add_rewrite_rule`, `Login.php` 24 `add_action`/`add_filter`). Superglobal reads: 61 in 7 files **[A]** (`services.no_superglobals`: `Login` 44, `League_Query_Service` 5, `Competition_Season_Service` 4, `Util_Lookup` 4, `Championship_Factory` 2, `Player_Details_Validator` 1, `Validator` 1). `Services/Widget.php` (222 lines) is a `WP_Widget` subclass **[V]**; `Services/Container/` is the DI container itself **[V]**.
- **Why it matters:** registering hooks and parsing `$_POST` are *driving-adapter* concerns; `docs/architecture.md` places them in `Infrastructure/Wordpress/`. While they sit in `Services/`, "Services" has no single meaning, the hook-registration rule for Application/Presentation cannot be made strict, and `Login` (605 lines) cannot be tested without WordPress.
- **Tracked by:** `services.no_wordpress_hooks` (ratchet 176 / 2; scope Services, Application, Presentation, Repositories), `services.no_superglobals` (ratchet 61 / 7; scope Services, Repositories, Presentation, Util). `Widget` registers nothing itself, so it is tracked by §9 item 1 rather than by a rule.

### N6 — `global $wp` query-var reads

- **Size:** 5 / 4 in the ratcheted layers **[A]** (`application.no_global_wp`: `Application/Tournament/Queries/Get_Tournament_Handler` 1, `Services/Competition/Competition_Season_Service` 1, `Services/Seo/Page_Title_Service` 1, `Util/Util_Lookup` 2). Plugin-wide 24 sites / 15 files **[M]**: the other 19 are in `Infrastructure/Wordpress/Shortcodes/*_Shortcode_Adapter` (11 adapters), `Infrastructure/Wordpress/Request/Competition_Request` and `Public/Shortcodes` ×2 — driving adapters, where reading `$wp` is permitted but should still go through one place.
- **Why it matters:** the Application hit is a direct layer violation (a query handler that reads the WordPress router). The adapter hits are 11 copies of the same 6 lines.
- **Tracked by:** `application.no_global_wp` (ratchet 5 / 4; globals `wp`, `wp_query`, `post`, `current_user`). Fix shape in §9 item 2.

### N7 — Remaining `Admin_Display` subclasses

- **Size:** 0 / 0 **[A]** (`admin.no_admin_display_inheritance`, flipped to strict 2026-11-15). The 8 screens (`Admin_Upgrade`, `Admin_Options`, `Admin_Season`, `Admin_Championship`, `Admin_Import`, `Admin_Event`, `Admin_Index`, `Admin_Result`) are `final readonly` bridges using `Completes_Admin_Requests`. `Admin_Display` is deleted; top-level WordPress admin menu callbacks dispatch through `Admin_Page_Router`. `admin.no_template_require` is also 0 / 0 and strict. `Admin/` still forwards `$_POST`/`$_GET` arrays from bridges to dispatchers (the P2-9 design; the per-view controllers parse them).
- **Why it mattered:** those screens processed requests inline (no PRG, no typed command, no view model), so they could not be covered by the `Completes_Admin_Requests` / controller tests.
- **Tracked by:** `admin.no_admin_display_inheritance` (strict at 0; was ratchet 8 / 8), `admin.no_template_require` (strict at 0; was 4 / 3).

### N8 — Root-file residue

- `Admin.php` (654 lines) raw SQL and global service locator removed (2026-11-09): `add_meta_box()` and `edit_match_report()` query and update via `League_Repository` / `Fixture_Repository` (0 `$wpdb` refs) and `Admin::get_instance()` resolves via `Plugin_Bootstrap::container()` (0 `global $racketmanager` refs).
- `Public_Api::container()` and `Admin/Presenters/Admin_Date_Presenter` resolve the container via `Plugin_Bootstrap::container()` rather than `global $racketmanager` (done 2026-11-09) **[V]**; `Public_Api.php`, `Admin/` and `Admin.php` are covered by `services.no_global_racketmanager`.
- `Cli/Export_Command` resolves dependencies via container injection (done 2026-11-10) **[V]**; `all.no_get_instance_outside_bootstrap` is strict at 0.
- `Presentation/Presenters/Fixture_Presentation_Adapter` carried `#[AllowDynamicProperties]` (removed 2026-11-09) — no dynamic-property classes remain in `src/php` **[V]**.
- **Tracked by:** `all.no_get_instance_outside_bootstrap` (strict at 0; all P1 items 1–6 complete).

### N9 — Interfaces declared outside the port locations

- **Size [V]:** **0 misplaced / 9 allow-listed (Done 2026-11-16)**; was 21 interfaces outside `Domain/` and `Application/` (15 in `Services/`, 5 in `Infrastructure/`, 1 in `Admin/`). 17 application-layer ports moved to canonical `Application/Ports/` (4 cross-cutting), `Application/Admin/Ports/` (10 admin), and contextual ports under `Application/Fixture/Ports/`, `Application/Mail/Ports/`, `Application/Notification/Ports/`. The 4 infrastructure contracts (`Service_Provider_Interface`, `Export_Formatter_Interface`, `Component_Coordinator_Interface`, `Shortcode_Adapter_Interface`) and 5 shortcode presenter contracts (`Infrastructure/Wordpress/Presentation/Ports/*`) legitimately remain in place and are explicitly allow-listed.
- **Why it matters:** In hexagonal architecture, port contracts belong to the Application and Domain layers. Relocating misplaced interfaces clarifies boundaries, enforces dependency inversion, and eliminates architectural drift.
- **Tracked by:** `Ports_Location_Test` (strictly rejects unapproved interfaces declared under `Services/`, `Infrastructure/`, `Presentation/`, or `Admin/`).

### N10 — Templates not reading `$vm`

- **Size:** 0 / 279 enforced + 24 exempt **[A]** (`templates.require_vm`, flipped to strict 2026-11-16; 303 total now (302 at initial audit), was 39 / 302). 34 previously never mentioned `$vm` **[M]**; 5 (`admin/finances/show-charges.php`, `show-invoices.php`, `admin/players/show-errors.php`, `show-players.php`, `show-requests.php`) declared `/** @var …_Page_View_Model $vm */` but never read it. The 34: 10 `admin/documentation/*`, 3 `admin/event/include/settings/*`, `admin/includes/player.php`, 5 `email/*` (components `hr`, `salutation`, `contact-urgent`, `div-top`, `div-bottom`), 9 `includes/*` (`loading`, `modal-loading`, `modal-score`, `match-modal`, `nav-pills`, `round-draw`, `championship-draw`, `matches-team-list`, `matches-team-list-group`), `event/teams-list.php`, `tournament/match-unavailable.php`, 2 `page_template/*`, 2 `pages/*`. The 15 dynamic templates consume `$vm` directly (admin wrappers, settings forms, dynamic email components, dynamic draws and match lists), while the 24 static fragments, modal shells, email boilerplate, and WordPress Core page/archive templates are exempted via `exclude_prefixes` to eliminate artificial assertion ceremony while strictly enforcing `$vm` across all dynamic templates.
- **Why it matters:** `Template_Scope` guarantees `$vm` exists (P3-14), and dynamic templates unpack and bind data from `$vm` rather than relying on implicit loose caller variables.
- **Tracked by:** `templates.require_vm` (strict at 0; was ratchet 39 / 39; 279 dynamic templates strictly enforced, 24 static/WP templates excluded).

### N11 — Presentation calls `get_option()` directly

- **Size:** **0 / 0 (Done 2026-11-16)**; was 7 calls in 4 `Presentation/` files **[M]** (earlier planning notes said 24 / 10; that count included `->get_option()` method calls on the service). All 7 calls (`Email_Date_Formatter`, `Competition_Shortcode_Presenter`, `Fixture_Presentation_Adapter_Graph_Builder`, `Player_Presenter`) routed through `Option_Gateway_Interface` with safe format defaults (`d/m/Y`, `Y-m-d`, `H:i`); unused `use function get_option;` imports purged **[V]**.
- **Why it matters:** a port that is bypassed in 4 places is not a port; presenters that read options cannot be rendered in isolation.
- **Tracked by:** `tests/Unit/Presentation/Presentation_Layer_Options_Test.php` (§9 item 12; automated regression test enforces zero direct `get_option()` calls and zero `use function get_option;` imports across `src/php/Presentation/`).

### N12 — jQuery runtime dependency

See §7. 755 token/bracket references in 77 files **[J]**; the admin UI widgets (tooltips, colour picker, autocomplete, drag-and-drop) still need the jQuery runtime.

---

## 5. Structural & naming inconsistencies

| # | Finding **[V]/[M]** | Effect |
|---|---|---|
| S1 | **Two dependency-injection styles coexist**: 25 `::class` bindings and autowiring (P3-15) next to 357 string keys, 187 aliases and 449 nullable-with-fallback constructor parameters (N2/N3). | New code has no single pattern to copy; `Admin_Screens_Service_Provider` (603 lines, 343 literal keys) is the largest file in `Admin/`. |
| S2 | **"Services" means four things**: domain-ish orchestration (`Competition_Entry_Service`), WordPress adapters (`Login`, `Rewrites`, `Widget`), the DI container (`Container/`); its 15 port interfaces were relocated (N9, done 2026-11-16), leaving 2 allow-listed infrastructure contracts. | The layer cannot be given a rule stricter than "no `$wpdb`, no Presentation" without first splitting it (N5, N9). |
| S3 | **`Util` / `Util_Lookup` grab-bag** — 65 static methods, 1,368 lines, 105 consumer files, 4 `$wpdb` refs, 4 superglobal reads, 2 `global $wp` **[M]**; only Domain (14 / 8) and Application (34 / 19) are ratcheted. | Every layer reaches into it, so it is the one place where a change can break all layers at once (D4). |
| S4 | **Admin screen generations**: all 15 screens are `final readonly` bridges (P2-9 + P2-10); `Admin_Display` deleted (N7, Done 2026-11-15). The root `Admin.php` coordinator remains (menu registration; SQL moved to repositories 2026-11-09) (N8). | Menu callbacks route through `Admin_Page_Router`; remaining onboarding cost is `Admin.php` itself. |
| S5 | **`Public/Shortcodes.php` (792 lines)** is the last pre-adapter shortcode host: 48 literal container keys, 2 `global $wp`, 2 `@deprecated` methods (`show_daily_matches()`, `show_latest_results()`) whose replacements (`Matches_Shortcode_Adapter`) already exist. | The 11 `*_Shortcode_Adapter`s are the pattern; this file is the exception that keeps the container-as-locator alive on the front end. |
| S6 | **Domain vocabulary is thin**: 3 events, 3 value objects, 12 enums against 12 entities with 240 public mutable properties (N4). `Season` and `Results_Checker` have been hardened to `public private(set)`; `Money` exists but prices are still `float`/`string` fields on `Charge`, `Invoice`, `Competition`. | Business rules are expressed as service code rather than as types. |

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
| `domain.no_public_mutable_properties` estimate | ≈450 | 574 / 21 at ratchet start → 486 / 19 after Iteration 1 → 412 / 18 after Iteration 2 → 350 / 17 after Iteration 3 → 251 / 14 after Iteration 4 → 250 / 13 after Iteration 5 → 240 / 12 after Iteration 6 | `League_Team` (64), `User` (44), `Rubber` (39) were not in the regex sample; the baseline subsequently ratcheted down by 88 (Iteration 1: `Fixture` & `Rubber`), 74 (Iteration 2: `Competition`), 62 (Iteration 3: `Event`), 99 (Iteration 4: `Player` [51], `User` [44], `Player_Error` [4]), 1 (Iteration 5: `Season` [1]), and 10 (Iteration 6: `Results_Checker` [10]) |
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
| 1 | **(Done 2026-11-08)** Relocate `Services/Login.php`, `Services/Rewrites.php`, `Services/Widget.php` to `Infrastructure/Wordpress/{Auth,Rewrites,Widgets}/`; move their `$_POST`/`$_GET` parsing into `Infrastructure/Wordpress/Request/*` command objects; leave only behaviour (login rules, URL building) in a `Services/` or `Application/` class. Then flip `services.no_wordpress_hooks` to strict (N5). | M | low | `services.no_wordpress_hooks` (176 / 2 → 0, flipped to strict), `services.no_superglobals` (61 / 7 → ≤ 17 / 6) |
| 2 | **(Done 2026-11-09)** Introduce `Application/Ports/Query_Vars_Interface` with an `Infrastructure/Wordpress/Request/Wp_Query_Vars` adapter; inject it into `Get_Tournament_Handler`, `Competition_Season_Service`, `Page_Title_Service`, `Util_Lookup` and the 11 shortcode adapters (N6). | S | low | `application.no_global_wp` (5 / 4 → 0, flipped to strict) |
| 3 | **(Done 2026-11-09)** `Public_Api::container()` and `Admin_Date_Presenter` resolve the container via `Plugin_Bootstrap` instead of `global $racketmanager`; add `Public_Api.php` and `Admin/` to the `services.no_global_racketmanager` scope (N8). | S | low | `services.no_global_racketmanager` scope widened, stays strict |
| 4 | **(Done 2026-11-09)** Remove `#[AllowDynamicProperties]` from `Fixture_Presentation_Adapter` by declaring the properties it sets (N8). | S | low | — (inspection) |
| 5 | **(Done 2026-11-09)** Move the `Admin::add_meta_box()` SQL into `League_Repository::find_names()` / `Fixture_Repository::find_by_post()`; delete the 3 `global $racketmanager` in `Admin.php` (N8). | S | low | regex `$wpdb` outside `Repositories/`: 36 occurrences — `Admin.php` 0; `Activator` 24; `Util` 4 (1 UPDATE); `Plugin_Bootstrap` 4 (`show_errors()`, no query); `Upgrade` 1 typed property; 3 docblock mentions |
| 6 | **(Done 2026-11-10)** `Cli/Export_Command` receives the container from the WP-CLI bootstrap (`Plugin_Bootstrap::container()`), closing the last `get_instance()` call; flip the rule to strict. | S | low | `all.no_get_instance_outside_bootstrap` (1 / 1 → 0, flipped to strict) |

### P2 — Finish the layering (M–L)

| # | Item | Effort | Risk | Tracked by |
|---|---|---|---|---|
| 7a | **(Done 2026-11-10)** **Application → Fixture & Match ports.** Extract `Application/Fixture/Ports/{Fixture_Detail_Service,Fixture_Service,Result_Reporting_Service,Fixture_Link_Service,Fixture_Permission_Service,Fixture_Maintenance_Service,Fixture_Result_Manager,Results_Checker_Manager}_Interface` and bind in `src/php/Services/Container/Providers/Fixture_Service_Provider.php`; relocate pure static `Match_Date_Round_Validator` into `Application/Admin/Validation/` (called directly in `Set_League_Match_Dates_Command` DTO, no container binding); retarget 10 unique Application files (13 additive violations across `Admin/` [3 files, 5 violations], `Event/` [1], `Fixture/` [2 files, 3 violations], `Matches/` [3], `Tournament/` [1]). | S | low | `application.no_concrete_services` (189 → 176 / −13) |
| 7b | **(Done 2026-11-11)** **Application → Finance & Settings ports.** Extract `Application/Ports/Finance_Service_Interface` and `Application/Ports/Settings_Service_Interface`, bound in `src/php/Services/Container/Providers/Core_Service_Provider.php`. For the 2 `Stripe_Settings` violations, move only its settings data shape to `Application/Finance/DTOs/`, update `Rest/Rest_Resources.php`, and move `update_payment(Finance_Service, …)` into a Finance use case/service using the Finance port. For the `Validator_Config` violation, add `Application/Ports/Competition_Config_Validator_Factory_Interface`; implement the factory as a `Services/Validator` adapter bound in Core provider, passing its `Competition_Service` dependency into a fresh legacy validator per call and returning the legacy validator untyped as `object` (`create(): object`, as delivered; no typed `Competition_Config_Validator_Interface` was introduced, so a typed validator contract remains outstanding; do not relocate the subclass of the concrete-service-bearing `Services\Validator\Validator` or register a validator singleton). Retarget 15 unique Application files (19 additive violations across `Admin/` [1 file, 2 violations], `Club/` [2 files, 3 violations], `Competition/` [4 files, 5 violations], `Event/` [1], `Finance/` [3], `General/` [2 files, 3 violations], `Login/` [1], `Tournament/` [1]). | S | low | `application.no_concrete_services` (176 → 157 / −19) |
| 7c | **(Done 2026-11-12)** **Application → Club & Team ports.** Extract `Application/Club/Ports/{Club_Service,Team_Service}_Interface`, bound in `src/php/Services/Container/Providers/Club_Service_Provider.php`. For the `Validator_Club` violation, add `Application/Club/Ports/{Club_Validator,Club_Validator_Factory}_Interface`; implement the factory as a `Services/Validator` adapter bound in Club provider, accepting the current per-use `Player_Repository_Interface` (and any other required repository inputs), passing them into a fresh legacy validator per call, and returning the Application contract. Keep the inherited concrete-service validator in the outer layer; do not relocate it or register it as a singleton. Retarget 18 unique Application files (21 additive violations across `Admin/` [3 files, 5 violations], `Club/` [6], `Competition/` [4 files, 5 violations], `Event/` [3], `General/` [1], `League/` [1]). | M | low | `application.no_concrete_services` (142 → 121 / −21) |
| 7d | **(Done 2026-11-12)** **Application → Tournament ports.** Extract `Application/Tournament/Ports/Tournament_Service_Interface` and bind in `src/php/Services/Container/Providers/Competition_Service_Provider.php`; retarget 15 unique Application files (15 violations across `Competition/` [3], `General/` [1], `Tournament/` [11]). | S | low | `application.no_concrete_services` (157 → 142 / −15) |
| 7e | **(Done 2026-11-12)** **Application → League & Standings ports.** Extract `Application/League/Ports/{League_Service,League_Query_Service,Promotion_Relegation_Service,Standings_Service}_Interface` and `Application/League/Ports/League_Schedule_Service_Interface`, bound in `src/php/Services/Container/Providers/Competition_Service_Provider.php`. The baseline target for `League_Schedule_Service` is `Racketmanager\Services\Competition\League_Schedule_Service` (container key `competition_league_schedule_service`; its only Application consumer, `Application/Admin/Commands/League_Constitution_Handler.php`, calls only `generate_box_league_matches( object $event ): array`), so the port exposes that single method and omits the `set_fixture_service` / `get_fixture_service` accessors. Do not bind `Services\Admin\League\League_Schedule_Service` (key `league_schedule_service` in `Admin_Service_Provider`). Retarget 15 unique Application files (29 additive violations across `Admin/` [3 files, 6 violations], `Event/` [2 files, 4 violations], `League/` [10 files, 19 violations]). | M | medium | `application.no_concrete_services` (121 → 92 / −29) |
| 7f | **(Done 2026-11-12)** **Application → Player & Registration ports.** Extract `Application/Player/Ports/{Player_Service,Player_Stats_Service}_Interface` and `Application/Player/Ports/Player_Query_Service_Interface` (bound in `src/php/Services/Container/Providers/Player_Service_Provider.php`) and `Application/Player/Ports/Registration_Service_Interface` (bound in `src/php/Services/Container/Providers/Club_Service_Provider.php`). `Player_Query_Service_Interface` declares only the shapes Application uses: `get_competitions( Player $player, array\|string $args = array() ): array` (the concrete method's optional third `?Competition_Service` argument stays on the outer-layer class only; the concrete return type is plain `array`, so the signature is compatible) and `get_matches( Player $player, ?object $grouping, ?string $season, string $match_source, ?int $period = null, ?string $type = null ): array`; no concrete `Racketmanager\Services\` type appears in the port. Retarget 22 unique Application files (36 additive violations across `Admin/` [2 files, 3 violations], `Club/` [1 file, 2 violations], `Competition/` [7 files, 11 violations], `Event/` [5 files, 7 violations], `General/` [2 files, 6 violations], `League/` [2 files, 4 violations], `Matches/` [1 file, 1 violation], `Player/` [2 files, 2 violations]). | M | medium | `application.no_concrete_services` (92 → 56 / −36) |
| 7g | **(Done 2026-11-13)** **Application → Competition & Event core ports.** Extract `Application/Competition/Ports/{Competition_Service,Competition_Season_Service,Event_Query_Service,Competition_Entry_Service}_Interface`, bound in `src/php/Services/Container/Providers/Competition_Service_Provider.php`; retarget 33 unique Application files (56 additive violations across `Admin/` [3 files, 4 violations], `Club/` [1 file, 2 violations], `Competition/` [13 files, 22 violations], `Event/` [12 files, 23 violations; includes `Event_Allowlist_Guard` method parameter], `League/` [1 file, 2 violations], `Tournament/` [3 files, 3 violations]). | M | medium | `application.no_concrete_services` (56 → 0 / −56) |
| 7h | **(Done 2026-11-13)** **Flip `application.no_concrete_services` to strict.** Verify baseline reaches 0; switch rule mode from `Rule_Mode::RATCHET` to `Rule_Mode::STRICT` in `tests/Architecture/Rules/Layer_Rules.php`; add to `$strict` list in `tests/Architecture/Support_Test/Layer_Rules_Test.php`; purge from `tests/Architecture/baseline.json`. | S | low | `application.no_concrete_services` (0 / 0, flipped to strict) |
| 8 | **(Done 2026-11-10)** **Retire `?? new` fallbacks.** Collaborator parameters made required (dropped `?` and `= null`), wired explicitly or autowired via `Simple_Container`, and all fallback instantiations removed across Infrastructure shortcode/AJAX adapters, Services (validators, action dispatchers, domain services), Presentation, Repositories, Admin, Application, Rest, and Util. Pure `Team_Placeholder_Role` enum extracted to its own PSR-4 file, read-side titles enriched with cycle-safe lazy fixture collaborator; rule flipped to strict (N2). | M | medium (test doubles that relied on the defaults must now pass stubs) | `all.no_fallback_construction` (131 / 60 → 0, flipped to strict) |
| 9 | **(Done 2026-11-14)** **`::class` keys provider-by-provider.** Register by `X::class` (or the ports from items 7a–7g), replace `$c->get('key')` closures with typed tokens/autowiring, and maintain `alias('old_key', X::class)` as BC bridges until consumers migrate. Completed migration across all domain/presentation service providers (`Core_Service_Provider`, `Player_Service_Provider`, `Presentation_Service_Provider`, `Club_Service_Provider`, `Fixture_Service_Provider`, `Competition_Service_Provider`): all services, gateways, repositories, presenters, and handlers register under canonical `::class` / interface tokens; all internal lookups converted to typed tokens. To strictly respect `services.no_presentation_imports`, presenter port contracts were introduced in `Infrastructure/Wordpress/Presentation/Ports/` alongside `Template_Renderer_Interface`, with `Presentation_Service_Provider` aliasing typed interfaces to legacy keys. `Simple_Container` enhanced with safe alias unsetting on `set()` and port fallback resolution for legacy string keys in isolated unit tests (N3). | L | medium | `all.no_literal_container_keys` (1,841 → 1,127 / −714) |
| 10 | **(Done 2026-11-15)** **Migrate the 8 `Admin_Display` subclasses** to the P2-9 bridge pattern (`final readonly` + `Completes_Admin_Requests` + per-view `*_Admin_Controller` + PRG + `*_Page_View_Model`): `Admin_Upgrade`, `Admin_Season`, `Admin_Options`, `Admin_Import`, `Admin_Championship`, `Admin_Index`, `Admin_Event`, `Admin_Result`. Introduced `Admin_Page_Router` for top-level menu dispatch and deleted `Admin_Display` (N7). | L | high (UI) | `admin.no_admin_display_inheritance` (8 / 8 → 0, flipped to strict), `admin.no_template_require` (4 / 3 → 0, flipped to strict) |
| 11 | **(Done 2026-11-16)** **Finish `$vm` adoption** in templates: 15 dynamic templates read `$vm` (admin wrappers, settings forms, dynamic email components, dynamic draw/match includes), 24 static and WordPress Core templates exempted via `exclude_prefixes` without artificial assertion ceremony. Promoted `templates.require_vm` to strict across all 279 dynamic templates (N10). | M | low | `templates.require_vm` (39 / 39 → 0 / 279 enforced + 24 exempt, flipped to strict) |
| 12 | **(Done 2026-11-16)** Route the 7 Presentation `get_option()` calls through `Option_Gateway_Interface` (N11). | S | low | `Presentation_Layer_Options_Test` (Presentation/ `get_option(` → 0) |
| 13 | **(Done 2026-11-16)** **Decide and move the 21 misplaced interfaces** (N9): 17 Application ports relocated to canonical `Application/Ports/` and `Application/<Context>/Ports/`; 4 infrastructure contracts (`Service_Provider_Interface`, `Export_Formatter_Interface`, `Shortcode_Adapter_Interface`, `Component_Coordinator_Interface`) and 5 shortcode presenter contracts explicitly allow-listed. Extended `Ports_Location_Test` with AST-based `declared_interfaces` guardrail failing on any interface declared under `Services/`, `Infrastructure/`, `Presentation/`, or `Admin/` outside the allow-list. | M | low | `Ports_Location_Test` |

### P3 — Aggregate hardening / consolidation (L)

| # | Item | Effort | Risk | Tracked by |
|---|---|---|---|---|
| 14 | **(Iteration 1, 2, 3, 4, 5, 6, 7 & 8 Done 2026-11-22; Iteration 9 & 10 Done 2026-11-23)** **Aggregate hardening**, one aggregate per iteration: Iteration 1 `Fixture` (49 props) & `Rubber` (39 props) (−88 entries, 574 → 486 / 19 entities); Iteration 2 `Competition` (74 props) (−74 entries, 486 → 412 / 18 entities); Iteration 3 `Event` (62 props) (−62 entries, 412 → 350 / 17 entities); Iteration 4 `Player` (51 props), `User` (44 props) & `Player_Error` (4 props) (−99 entries, 350 → 251 / 14 entities; new domain mutators, `User_Repository` hydration realignment, and `Tournament_Shortcode_Adapter` typing); Iteration 5 `Season` (1 prop) (−1 entry, 251 → 250 / 13 entities; public private(set) visibility on `name` and `id`, docblock remediation, dedicated unit tests); Iteration 6 `Results_Checker` (10 props) (−10 entries, 250 → 240 / 12 entities; public private(set) visibility, domain mutators, repository and manager alignment, dedicated unit tests); Iteration 7 Communication `Message` (9 props) & `Results_Report` (4 props) (−13 entries, 240 → 227 / 10 entities; public private(set) visibility, domain mutators, repository alignment, dedicated unit tests); Iteration 8 `Tournament_Entry` (7 props) (−7 entries, 227 → 220 / 9 entities; public private(set) visibility, domain mutators, template tests alignment, dedicated unit tests); Iteration 9 `Club_Player` (4 props) (−4 entries, 220 → 216 / 8 entities; public private(set) visibility, new state queries (`is_active()`, `is_pending()`, `is_removed()`, `is_system_record()`) and typed getters/setters, `Registration_Repository::save()` reading through getters, insert/update/`find_by_id()` repository tests, dedicated unit tests; `approve()`/`remove()` and `set_removal_date()`/`set_removal_user()` pre-existed, were not newly implemented, and are now covered by tests (the two setters only widened to nullable)); Iteration 10 `Club_Role` (7 props) (−7 entries, 216 → 209 / 7 entities; public private(set) visibility, safe scalar/nullable defaults, typed getters/setters, `reassign_user()`, role classification queries (`is_match_secretary()`, `is_coach()`, `is_treasurer()`, `get_role_enum()`), constructor lookup normalization, caller alignment in repository and tests, dedicated unit tests). Remaining aggregate properties become `private(set)` / `readonly`, mutation goes through intention-revealing methods, and repositories hydrate via factory/DTO patterns. Proposed implementation order for all remaining 7 files (smallest and most isolated first): Finance (`Invoice` 14, `Charge` 9 = 23), Team (22), Club (21), Tournament (37), League (42), and League_Team (64). Note that `domain.no_public_mutable_properties` can only be flipped from `RATCHET` to `STRICT` once all 7 files (209 properties) are cleared, not after the initially planned aggregates alone. | L | medium | `domain.no_public_mutable_properties` (574 → 486 → 412 → 350 → 251 → 250 → 240 → 227 → 220 → 216 → 209 / −365) |
| 15 | **Value objects and events**: `Season`, `Match_Day`, `Score`/`Set_Score`, `Team_Id`/`Player_Id`, `Entry_Fee` (reuse `Money`); events `Fixture_Scheduled`, `Result_Recorded`, `Entry_Accepted`, `Invoice_Issued` dispatched from the aggregates of item 14 so notifications/standings listen instead of being called (N4, S6). | L | medium | count of `Domain/Value_Objects/*`, `Domain/**/Events/*` (manual) |
| 16 | **Decompose `Util` / `Util_Lookup`** into context services behind ports (`Date_Formatter`, `Lookup_Tables`, `Schedule_Clock`, …); 105 consumers, so do it method-family by method-family and keep a thin `Util` façade until the Domain/Application baselines reach 0 (S3, D4). | L | medium | `domain.no_util_static` (14 / 8 → 0), `application.no_util_static` (34 / 19 → 0) |
| 17 | **jQuery runtime retirement** for the admin UI: replace the jQuery UI widgets (autocomplete, draggable, tooltip, colour picker) with native/`@wordpress/components` equivalents feature by feature; drop the `jquery` script dependency when `js.jquery_reference` reaches 0 (N12). | L | medium | `js.jquery_reference` (755 / 77 ↓) |
| 18 | **Archive superseded plans** (`docs/ddd-audit-report.md`, `ddd-architecture-report.md`, `ddd-analysis-2026.md`, `match-migration-*.md`, `move-away-from-match*.md`, `migration-plan.md`, …) under `docs/archive/` with a one-line index; keep `architecture.md`, `public-api.md`, the two dated audits and the living how-tos at the top level (D3). | S | none | — |

---

## Appendix A — Guardrail rule catalogue

### A.1 PHP rules (`tests/Architecture/`) — 37 rules, 30 strict / 7 ratchet

Rules unchanged since the October report are listed by id only; see `docs/ddd-architecture-audit-2026-10.md` Appendix A.1 for their history.

**Strict (30, baseline 0):** `domain.no_wpdb`, `domain.no_outer_layer_imports`, `domain.no_superglobals`, `domain.no_wordpress_functions`, `domain.no_untyped_constructor`, `domain.no_lenient_from_object`, `application.no_wpdb`, `application.no_global_racketmanager`, `application.no_concrete_repositories`, `application.no_presentation_imports`, `application.no_concrete_services`, `application.no_global_wp`, `services.no_wpdb`, `services.no_global_racketmanager`, `services.no_presentation_imports`, `services.no_wordpress_hooks`, `all.no_legacy_helper_calls`, `all.no_deprecated_helper_calls`, `all.no_get_instance_outside_bootstrap`, `all.no_alias_class_references`, `all.strict_types`, `all.no_extract`, `all.no_fallback_construction`, `templates.no_globals_or_db`, `templates.no_global_racketmanager`, `templates.no_legacy_helper_calls`, `templates.require_vm`, `admin.no_template_require`, `admin.no_admin_display_inheritance`, `scanner.parse_error`.

**Ratchet carried over (3):** `domain.no_util_static` 14 / 8 · `application.no_superglobals` 1 / 1 (`Login/Queries/Get_Login_Form_Query`, `$_SERVER`) · `repositories.no_service_imports` 5 / 5.

**Rules added 2026-11-03 (10):**

| Rule id | Scope | Detects | Kind | Baseline **[A]** | Item |
|---|---|---|---|---|---|
| `domain.no_public_mutable_properties` | the 21 `DOMAIN_ENTITY_FILES` | `public` property or promoted parameter that is not `readonly`, `static`, `private(set)` / `protected(set)`; detail `Class::$prop` | `FORBIDDEN_PUBLIC_MUTABLE_PROPERTY` | **209 / 7** (574 / 21 at ratchet start; Iteration 1 `Fixture` & `Rubber` hardened −88; Iteration 2 `Competition` hardened −74; Iteration 3 `Event` hardened −62; Iteration 4 `Player`, `User` & `Player_Error` hardened −99; Iteration 5 `Season` hardened −1; Iteration 6 `Results_Checker` hardened −10; Iteration 7 Communication `Message` & `Results_Report` hardened −13; Iteration 8 `Tournament_Entry` hardened −7; Iteration 9 `Club_Player` hardened −4; Iteration 10 `Club_Role` hardened −7) | N4 |
| `application.no_concrete_services` | `Application/` | dependency (import, `new`, static, type, `instanceof`, extends/implements, catch) on `Racketmanager\Services\…` whose name does not end in `_Interface`; one violation per distinct class per file (30 concrete targets across 7 domain clusters 7a–7g, ratcheted to 0 then flipped to strict in 7h) | `FORBIDDEN_DEPENDENCY` | **0 (strict, was 189 / 86)** | N1 (items 7a–7h) |
| `application.no_util_static` | `Application/` | static call on `Util` / `Util_Lookup` | `FORBIDDEN_STATIC_CALL` | **34 / 19** | D4 |
| `application.no_global_wp` | `Application/`, `Services/`, `Presentation/`, `Repositories/`, `Util/` | `global $wp` / `$wp_query` / `$post` / `$current_user` or the `$GLOBALS[...]` spelling | `FORBIDDEN_GLOBAL` | **0 (strict, was 5 / 4)** | N6 |
| `services.no_superglobals` | `Services/`, `Repositories/`, `Presentation/`, `Util/` | `$_POST/$_GET/$_REQUEST/$_SERVER/$_COOKIE/$_FILES` | `FORBIDDEN_VARIABLE` | **61 / 7** | N5 |
| `services.no_wordpress_hooks` | `Services/`, `Application/`, `Presentation/`, `Repositories/` | calls to `add_action`, `add_filter`, `add_shortcode`, `register_widget`, `add_rewrite_rule`, `add_rewrite_tag`, `register_rest_route` (function calls only; `do_action`/`apply_filters` are not registration) | `FORBIDDEN_FUNCTION_CALL` | **0 (strict, was 176 / 2)** | N5 |
| `all.no_fallback_construction` | `src/php/**` excl. `Services/Container/`, `Infrastructure/Wordpress/Bootstrap/` | `?? new X()` / `??= new X()` where `X` is `Racketmanager\…(_Service\|_Repository\|_Presenter\|_Handler\|_Adapter\|_Factory\|_Gateway\|_Renderer\|_Validator\|_Dispatcher\|_Mailer\|_Clock\|_Loader\|_Manager)`; detail `?? new FQCN` | `FORBIDDEN_FALLBACK_NEW` | **0 (strict, was 131 / 60)** | N2 |
| `all.no_literal_container_keys` | `src/php/**` (providers included) | `get\|set\|has\|alias\|has_definition\|is_registered\|remove` called with a string-literal first argument on `$c`, `$container`, `*->container`, `*::$container`; detail `$c->set('key')` | `FORBIDDEN_LITERAL_CONTAINER_KEY` | **1,989 / 45** | N3 |
| `templates.require_vm` | `templates/**` (excl. 24 static and WordPress Core templates) | dynamic template never reads the `$vm` variable (docblock mentions do not count); detail `missing $vm` | `REQUIRE_VARIABLE` | **0 (strict, was 39 / 39; 279 enforced + 24 exempt)** | N10 (item 11) |
| `admin.no_admin_display_inheritance` | `Admin/` | `extends Racketmanager\Admin\Admin_Display` (`contexts: ['extends']`; imports, type hints and `new` are not counted) | `FORBIDDEN_CLASS_REFERENCE` | **0 (strict, was 8 / 8)** | N7 (item 10) |

Every new rule has at least one positive and one negative case in `Support_Test/Layer_Rules_Test.php` (e.g. `?? new DateTimeImmutable()` and `?? new Money(0)` do not match `all.no_fallback_construction`; `public readonly`, `public private(set)`, `public static` and promoted `public readonly` do not match the property rule; `$request->get('id')` and `$c->get(X::class)` do not match the container-key rule; `use Admin_Display` in a provider does not match the inheritance rule; `use Racketmanager\Services\Flash_Message_Store_Interface` does not match `application.no_concrete_services` because it ends in `_Interface`), plus `applies_to` tests for the exclusions. `test_catalogue_modes_match_the_audit` lists the 30 strict ids.

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
3. When a ratchet reaches 0, flip it to `strict` in `Layer_Rules.php`, add it to `test_catalogue_modes_match_the_audit`, regenerate. Flipped: `templates.require_vm` (P2-11), `application.no_global_wp` (P1-2), `all.no_get_instance_outside_bootstrap` (P1-6), `services.no_wordpress_hooks` (P1-1), `application.no_concrete_services` (P2-7h), `admin.no_admin_display_inheritance` / `admin.no_template_require` (P2-10).
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

# [A] distinct concrete target services in Application/ (expect 30 targets across 189 violations and 86 files)
python3 -c 'import json,collections;b=json.load(open("tests/Architecture/baseline.json"))["application.no_concrete_services"];v=sum(b.values());f=len({k.split("|")[0] for k in b});t=len(collections.Counter(k.split("depends on ")[1] for k in b));print(f"{v} violations across {f} files, {t} target services")'

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
