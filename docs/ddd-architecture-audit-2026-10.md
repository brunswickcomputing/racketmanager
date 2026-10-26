# DDD / Hexagonal Architecture Audit — RacketManager (October 2026)

**Date:** 2026-10-25
**Plugin version:** 10.0.0 (`racketmanager.php`)
**Toolchain:** PHP 8.4.27 · PHPUnit 13.4.2 · nikic/php-parser 5.9.0 (transitive) · Jest ^30.3 · esbuild
**Scope:** `src/php/` (849 files), `functions.php` / `template-tags.php`, `templates/` (304 files), `src/js/` (90 files), theme consumer `themes/sydney-child`.
**Tree audited:** working tree at commit `8e5f3da` (2026-10-21) plus the then-uncommitted changes (mostly `Admin/`, `Application/`, `Admin/Controllers/`), since committed as `b4713b1` (2026-10-25). The **[A]** figures and `tests/Architecture/baseline.json` were generated against `b4713b1` with a clean working tree.

This report supersedes the undated `docs/ddd-audit-report.md`, `docs/ddd-architecture-report.md` and `docs/ddd-analysis-2026.md` for the purpose of tracking architectural debt. Section 8 reconciles statements in older docs with the current source.

---

## Legend: measured vs. estimated

Every number in this report is tagged:

- **[M]** — *measured* on 2026-10-25. Most source metrics use the regex-based measurement script described in §2. Counts are token-level (they can include matches inside comments and docblocks) and are therefore **upper bounds**; where a figure was manually filtered or recounted with the AST this is stated. Legacy-helper call counts use php-parser 5.9 with `NameResolver` and the resolution rule given in §2.1.
- **[E]** — *estimate* carried over from the planning notes or from manual reading; not reproduced by the script. Treat as indicative.
- **[V]** — *verified by inspection* (a fact, not a count — e.g. "file X exists", "theme calls Y").

> **Status of the guardrail suite.** The PHP architecture guardrail suite (`tests/Architecture/`, PHPUnit + php-parser ratchet, `Architecture` suite in `phpunit.xml.dist`) is **implemented**: 26 rules in `tests/Architecture/Rules/Layer_Rules.php`, 12 strict / 14 ratchet (`templates.no_legacy_helper_calls`, `domain.no_wordpress_functions` and `all.strict_types` flipped to strict on 2026-10-26), with the current debt committed in `tests/Architecture/baseline.json` (see Appendix A.1 for the AST-measured baseline of every rule, tagged **[A]**). The Jest ratchet (Appendix A.2, `tests/js/architecture/`) is **implemented** too: 3 rules in `support/rules.js`, all ratchet, JS detection via `@babel/parser` tokens/AST (already present through the `@babel/core` devDependency — no new package), debt committed in `tests/js/architecture/baseline.json` (121 keys), picked up by the existing `npm test` run. Where the AST/Jest scanners and the original regex measurement disagree, the scanner figure is authoritative and is noted inline.
>
> - **[A]** — *AST-measured* by the guardrail suite on 2026-10-25 (`Source_Scanner`, php-parser 5.9 + `NameResolver`; comments, strings and method calls excluded).
> - **[J]** — *Jest-measured* by the JS/template ratchet on 2026-10-25 (`tests/js/architecture/support/source-scanner.js`; JS is tokenised/parsed with `@babel/parser`, so comments, string/template-literal text and regex literals are never counted — docblock mentions of jQuery do not count — and `globalThis.*` assignments are seen alongside `window.*`).

---

## 1. Executive summary

The October 2026 refactors landed the structural core of a layered DDD / hexagonal design:

- `Domain/` is **free of persistence and WordPress-global coupling**: 0 `$wpdb`, 0 `global`, 0 `get_option`/`wp_cache_*`/`apply_filters`/`do_action`, 0 imports from outer layers, 0 superglobals **[M]**.
- `Application/` has 0 `$wpdb`, 0 `global $racketmanager`, 0 imports of concrete repositories **[M]**; it depends on `Repositories\Interfaces\*`.
- Bounded contexts, 9 factories, 8 `*_Hydration_DTO`s, domain events (3 concrete events + dispatcher + 1 listener), a composition root (`Plugin_Bootstrap` → `Container_Bootstrap` → `Simple_Container` + 6 providers), container-managed AJAX (7 controller/adapter pairs + `Ajax_Registry`) and 11 shortcode adapters are in place **[V]**.
- `Racketmanager_Match` is gone from `src/php` **[V]**; no deprecated WordPress core functions and no PHP 8.x-removed syntax were found **[V]**.

What remains is a measurable, well-bounded debt that mostly sits in the *edges* and *legacy helpers*:

| Debt area | Measured size **[M]** |
|---|---|
| Files missing `declare(strict_types=1)` | 292 / 849 at audit time (0 since 2026-10-26, P1-5) |
| Legacy global helper call sites (`get_league()`, `seo_url()`, query-service accessors, …) | 257 AST-resolved calls in 65 `src/php` files; 7 calls in 4 templates |
| Raw `$wpdb` outside `Repositories/` | 147 references in 11 files (7 of them in `Services/`) |
| `Application/` → `Presentation\|Admin` dependencies | 123 distinct classes in 65 files **[A]** |
| Domain entities with `object`/`?object`/`stdClass` constructor hydration | 18 constructors **[A]** (17 `?object` entities + `Results_Checker`'s `…\|stdClass\|null` union; +5 DTOs excluded by scope) |
| WordPress function calls inside `Domain/` | **173** call sites in 27 files **[A]** (157 in 22 `Domain/DTO/**` request/command DTOs; 16 in 5 entities). The regex audit measured 141 / 24 against a narrower list; the guardrail's family patterns (`wp_*`, `sanitize_*`, `esc_*`, `absint`, `maybe_unserialize`, `stripslashes_deep`, …) add 32 calls. **0 since 2026-10-26** (P1-4a/4b; rule strict). |
| `Admin_*` superglobal references / template `require`s | 608 references in 30 files / 33 `require`s in 7 files |
| Templates not using a `$vm` view model | 238 / 304 |
| Templates with inline `on*=` handlers | 35 (29 admin, 6 frontend) **[J]** — 52 attributes; the regex audit's 33 covered `on(click\|change\|submit\|load)=` only, the guardrail also counts `onblur=` (5, in `match-teams-result.php` and `match-tournament.php`) |
| JS files referencing jQuery | 74 / 90 **[J]** (1,009 code references; the regex audit's 75 / 1,080 included 71 docblock/comment/string mentions and one file — `features/autocomplete/index.js` — that only mentions jQuery in comments) |

The debt is tractable because none of it is load-bearing for the domain model; it can be retired incrementally behind a ratchet. The prioritised backlog is in §9.

---

## 2. Method & metrics

### 2.1 How the figures were produced

Most **[M]** figures come from a single PHP script run from the plugin root that walks `src/php`, `templates` and `src/js` and applies PCRE patterns per file (`preg_match_all`). Legacy helper calls were separately recounted with the already-installed php-parser 5.9 `NameResolver`. A call counts when its resolved name is `Racketmanager\<helper>`: fully qualified, imported via `use function`, or unqualified inside `namespace Racketmanager` (resolved through the `namespacedName` attribute). Method calls, same-named method definitions, comments and strings are excluded. Root-namespace `\seo_url()` / `\un_seo_url()` calls (3 in total: `Club_Presenter` 2, `Get_Player_Query` 1) are excluded because they target a different function. The guardrail suite (§9, Appendix A) uses an AST scanner for all PHP rules; its counts (**[A]**) are equal to or lower than the regex measurements except where the AST sees through a construct the regex could not (`stdClass` unions in `domain.no_untyped_constructor`, `$GLOBALS['racketmanager']` in `templates.no_global_racketmanager`) or where the rule's list is deliberately wider than the audit regex (`domain.no_wordpress_functions`: 173 vs 141; `services.no_presentation_imports`: 59 vs 58 because the top-level `Racketmanager\Admin` class counts as well). Where the two disagree, the **[A]** figure is authoritative and is the one in `tests/Architecture/baseline.json`.

Key patterns (all case-sensitive, applied to raw file contents):

| Metric | Pattern | Notes |
|---|---|---|
| `$wpdb` | `/\$wpdb\b/` | token references, not queries |
| `global $racketmanager` | `/global\s+\$racketmanager/` | |
| Outer-layer imports | `/^use\s+Racketmanager\\(Repositories\|Services\|Infrastructure\|Admin\|Presentation\|Application\|Rest\|Public\|Gateways)\\/m` | |
| Legacy helper call | php-parser `FuncCall` with `NameResolver`; resolved name must be `Racketmanager\<helper>` (fully qualified, `use function`, or `namespacedName` for unqualified calls in `namespace Racketmanager`) | Excludes method calls, same-named method definitions and root-namespace `\seo_url()` / `\un_seo_url()` |
| WP functions in Domain | `/\b(sanitize_text_field\|mysql2date\|current_time\|wp_unslash\|esc_html\|esc_attr\|esc_url\|…)\s*\(\|(?<![\w\\])(__\|_e\|_n)\s*\(/` | regex count 141; the guardrail uses the wider family list in `Layer_Rules::WORDPRESS_FUNCTIONS` (173 **[A]**), see §4 L3 |
| Untyped constructor | `/function\s+__construct\s*\([^)]*\??\bobject\b\s+\$/s` | |
| Dynamic property loop | `/\$this->\$\w+\s*=/` | |
| Superglobals | `/\$_(POST\|GET\|REQUEST\|SERVER)\b/` | |
| `strict_types` | absence of `/declare\s*\(\s*strict_types\s*=\s*1\s*\)/` | |
| Inline handlers | `/\son(click\|change\|submit\|load)=/i` | templates only; the Jest rule uses a full DOM event-name list (`onblur`, `onkeyup`, `onmouseover`, …) **[J]** |
| jQuery | `/\bjQuery\b\|(?<![\w$])\$\(/` | JS only; the Jest rule counts the equivalent `@babel/parser` identifier tokens (`jQuery`, or `$` followed by `(`), so comments, strings, template text and regex literals are excluded by construction **[J]** |
| `window.*` assignment | `/\bwindow\.[A-Za-z_$][\w$]*\s*=(?!=)/` | JS only; the Jest rule walks the Babel AST for `AssignmentExpression`s (any operator, incl. `\|\|=`/`??=`) whose target is a direct member of `window` **or `globalThis`** (dotted or bracket-literal key), exempting only `window.location` and the `window.X = window.X \|\| {}` idiom **[J]** |

Test count: `vendor/bin/phpunit --list-tests` → **4,068** tests **[M]**. The full suite was not executed for this report (documentation-only change).

Existing enforcement **[V]**: `tests/Unit/Domain/Domain_Architecture_Purity_Test.php` performs token-based checks for Domain globals, superglobals, `$GLOBALS`, `echo`/`print`, `wp_cache_*` and outer-layer imports; its `KNOWN_EXCEPTIONS` list is empty. `tests/Unit/Template_Tags_Decoupling_Test.php` renders the orphan templates through `template-tags.php` and checks for deprecation notices — it does **not** guard helper calls (that is `all.no_legacy_helper_calls`, whose scope includes `template-tags.php`). These are already active tests, distinct from the ratchet suite; Appendix A.4 records the overlap to avoid duplicating or weakening current coverage.

### 2.2 Size of the codebase **[M]**

| Directory (`src/php/…`) | Files |
|---|---|
| `Application/` | 239 |
| `Domain/` | 148 |
| `Services/` | 137 |
| `Admin/` | 81 (16 `Admin_*.php` screens, 16 `Controllers/`, view models, …) |
| `Presentation/` | 76 |
| `Infrastructure/` | 61 |
| `Repositories/` | 46 (20 in `Interfaces/`) |
| `Exceptions/` | 42 |
| `Gateways/` | 5 |
| `Util/` | 4 |
| `Rest/` | 3 |
| `Cli/`, `Public/` | 1 each |
| `Sports/` | 1 |
| Root (`Activator.php`, `Admin.php`, `RacketManager.php`, `aliases.php`) | 4 |
| **Total** | **849** |

These figures (and the per-layer counts in the §3 matrix) are the audit-time measurements. After the P1 work of 2026-10-26 the current counts are `Domain/` **130** (22 request DTOs relocated, `DTOs/` merged into `DTO/`, `View_Models/` removed, `Shared/` and `Ports/` added) and `Infrastructure/` **85** (`Wordpress/Request/**`, `Time/Wordpress_Clock.php`); the other directories are unchanged.

`templates/`: 304 PHP files. `src/js/`: 90 JS files (4 of them separate admin bundles).

---

## 3. Layer compliance matrix

Rows are layers; columns are the dependency rules a hexagonal layout implies. ✅ = 0 violations **[M]**; ⚠️ = violations measured (count); ➖ = not applicable.

| Layer | No `$wpdb` | No `global $racketmanager` | No superglobals | No outer-layer imports | No WP functions | No legacy helper calls | `strict_types` complete |
|---|---|---|---|---|---|---|---|
| **Domain** (148 at audit time; 130 since 2026-10-26, §2.2) | ✅ 0 | ✅ 0 | ✅ 0 | ✅ 0 | ✅ 0 since 2026-10-26 (was ⚠️ 173 calls / 27 files **[A]**, L3) | ✅ 0 global-helper calls (`Player_Error::get_message()` is a method) | ✅ 0 since 2026-10-26 (was ⚠️ 72 missing, L12) |
| **Application** (239) | ✅ 0 | ✅ 0 | ⚠️ 1 (`Login/Queries/Get_Login_Form_Query.php`) | ⚠️ 123 `Presentation\|Admin` classes depended on / 65 files (inversion); ✅ 0 concrete repositories | ➖ (allowed at this layer) | ⚠️ 27 calls in 16 files (26 helper calls + 1 query-service accessor at `League_Match_Handler.php:91`) | ✅ 0 since 2026-10-26 (was ⚠️ 10 missing, L12) |
| **Services** (137) | ⚠️ 103 refs / 7 files **[A]** (L7; regex said 102 — the AST also sees `$GLOBALS['wpdb']`) | ⚠️ 23 hits / 11 files (L5) | ➖ | ⚠️ 59 `Presentation\|Admin` classes depended on / 12 files **[A]** (58 `use` lines + `Container/Providers/Admin_Service_Provider.php` → top-level `Racketmanager\Admin`) | ➖ | ⚠️ 69 calls in 23 files (`League_Schedule_Service` 14, `Validator` 8, …) | ✅ 0 since 2026-10-26 (was ⚠️ 62 missing, L12) |
| **Repositories** (46) | ➖ (home of `$wpdb`) | ✅ 0 | ➖ | ⚠️ 7 `Services\` classes depended on / 6 files (inversion) | ➖ | ⚠️ 8 calls in 2 files (`User_Repository` 5, `Competition_Repository` 3) | ✅ 0 since 2026-10-26 (was ⚠️ 35 missing, L12) |
| **Infrastructure** (61) | ⚠️ `Bootstrap/Plugin_Bootstrap.php` (4) | ⚠️ part of the 33 hits (L5) | ➖ (HTTP boundary) | ➖ | ➖ | ⚠️ 31 calls in 9 files (`Club_Shortcode_Adapter` 11, `Admin_Ajax_Adapter` 5, `Email_Shortcode_Adapter` 5, `Fixture_Shortcode_Adapter` 3, `Matches_Shortcode_Adapter` 3) | ✅ 0 since 2026-10-26 (was ⚠️ 21 missing, L12) |
| **Presentation** (76) | ✅ 0 | ✅ 0 | ➖ | ➖ | ➖ | ⚠️ 59 calls in 7 files (58 `seo_url()` + 1 query-service accessor) | ✅ 0 since 2026-10-26 (was ⚠️ 2 missing, L12) |
| **Admin** (81) | ⚠️ `Admin.php` (13) | ⚠️ part of the 33 hits | ⚠️ 608 refs / 30 files (L9) | ➖ | ➖ | ⚠️ 22 calls in 4 files (`Admin_Cup` 10, `Admin_Display` 10, two `Admin/Controllers/*` 1 each) | ✅ 0 since 2026-10-26 (was ⚠️ 38 missing, L12) |
| **Rest / Util / Public / Cli** | ⚠️ `Util.php` (4) | ⚠️ `Util.php`, `Shortcodes.php`, `Cli/Export_Command.php` (L5) | — | — | — | ⚠️ `Rest_Resources` 27; `Public/Shortcodes` 11; `Util_Lookup` 2; `Export_Command` → `RacketManager::get_instance()` | ✅ 0 since 2026-10-26 (was Rest 2/3, Util 3/4, Public 1/1, Cli 1/1 missing, L12) |

Other top-level files/layers: `Exceptions/` has 42 files (42 missing `strict_types` at audit time, 0 since 2026-10-26), `Gateways/` 5 (0 missing), `Sports/` 1 (1 missing at audit time), and the four root PHP files had 2 missing declarations. Summed with the rows above, the measured total was 849 files and 292 missing declarations at audit time; **since 2026-10-26 every file under `src/php/` declares `strict_types=1`** (`all.strict_types` strict at 0, L12). The existing Domain purity test already enforces part of the Domain row; see Appendix A.4.

**Reading the matrix:** the *inner* layers (Domain, Application) are clean on the rules that matter most for a hexagonal core (no DB, no globals, no concrete repositories). The violations cluster in (a) request-parsing DTOs misplaced in `Domain/DTO`, (b) the Application→Presentation inversion created by query handlers returning view models, and (c) legacy helper and `$wpdb` use in `Services/`, `Admin/` and adapters.

---

## 4. Legacy pattern inventory (L1–L12)

Each item lists: where, measured size, why it matters, and the guardrail rule id (Appendix A) that ratchets it.

### L1 — Untyped `object`/`?object` hydration and dynamic `$this->$key = $value` loops
- **Where [M]:** 17 Domain entities accept an `object`/`?object` constructor argument:
  `Club/Club.php`, `Club/Club_Player.php`, `Club/Club_Role.php`, `Communication/Message.php`, `Communication/Results_Report.php`, `Competition/Event.php`, `Competition/League.php`, `Competition/League_Team.php`, `Finance/Charge.php`, `Finance/Invoice.php`, `Fixture/Rubber.php`, `Player/Player_Error.php`, `Player/User.php`, `Season/Season.php`, `Team/Team.php`, `Tournament/Tournament.php`, `Tournament/Tournament_Entry.php`.
  A further 5 DTOs also take `object` (`Club_Hydration_DTO`, `Player_Hydration_DTO`, `Team_Hydration_DTO`, `Invoice_Details_DTO`, `Fixture_Details_DTO`) — acceptable at a DTO boundary.
  Dynamic property assignment `$this->$key =`: 20 occurrences in 15 files (`League` 3, `Invoice` 3, `Event` 2, …).
- **Note:** `Event`, `League`, `League_Team`, `Club`, `Team`, `Tournament` already have a factory and a hydration DTO **[V]** but still *also* accept `?object`, so the typed path is optional rather than enforced. Only `Fixture`, `Player`, `Competition` are typed-only **[E]**.
- **Why it matters:** Active-Record residue; entities can be hydrated with arbitrary row shapes, defeating type safety and making the factories bypassable.
- **Guardrail:** `domain.no_untyped_constructor` (ratchet, **18** entries **[A]** after excluding `Domain/DTO/`: the 17 entities above plus `Result/Results_Checker.php`, whose `Results_Checker_Data|stdClass|null` union the AST scanner also flags — the regex only matched `object`).

### L2 — `League` aggregate owns UI / pagination / import concerns
- **Where [V]:** `Domain/Competition/League.php` — 1,449 lines **[M]**; methods `set_tab`, `set_template`, `set_num_pages`, `get_current_page`, `import_matches`, `import_teams`, `set_match_query_args`. `League`/`Event`/`Tournament` reach into `event->competition->settings[...]`.
- **Why it matters:** The aggregate carries presentation state and CSV import logic; it is the largest single file in `Domain/`.
- **Guardrail:** none directly; tracked by `domain.no_untyped_constructor` (League still accepts `?object`) and by file-size review in backlog item 13.

### L3 — WordPress functions inside `Domain/`
- **Where [A]:** **173** call sites in 27 files (every non-PHP-internal function called from `Domain/` is a WordPress function; the guardrail list covers all of them). Breakdown: `sanitize_text_field()` 61 / 21 files; `wp_unslash()` 57 / 18; `__()` 18 / 7; `absint()` 14 / 4; `maybe_unserialize()` 5 / 3; `sanitize_textarea_field()` 5 / 3; `stripslashes_deep()` 3 / 2; `sanitize_email()` 3 / 3; `current_time()` 2, `mysql2date()` 2, `sanitize_title()` 2, `wp_json_encode()` 1.
  The regex audit **[M]** counted 141 / 24 because its list lacked `absint`, `maybe_unserialize`, `sanitize_textarea_field`, `stripslashes_deep`, `sanitize_email` and `sanitize_title` (32 calls).
  Hot spots: `Domain/DTO/Tournament/Tournament_Request_DTO.php` (23), `Domain/DTO/Competition/Cup_Entry_Request_DTO.php` (14), `Tournament_Information_Request_DTO.php` (12), `League_Entry_Request_DTO.php` (11), `Domain/DTO/Fixture/Fixture_Status_Update_Request.php` (10), `Domain/DTO/Player/Search_Players_Command.php` (10), `Tournament_Entry_Request_DTO.php` (10).
  Non-DTO Domain files affected — 16 call sites in 5 entities: `Fixture/Rubber.php` (6: `maybe_unserialize` ×2, `mysql2date` ×2, `stripslashes_deep` ×2), `Club/Club_Player.php` (4: `__` ×2, `current_time` ×2), `Competition/League_Team.php` (3: `maybe_unserialize` ×2, `stripslashes_deep`), `Competition/Competition.php` (2: `sanitize_title`), `Communication/Results_Report.php` (1: `wp_json_encode`). The other 157 are in 22 `Domain/DTO/**` request/command DTOs.
- **Why it matters:** The DTOs are *request* DTOs — they parse and sanitise HTTP input — and belong at the WordPress boundary (`Infrastructure/Wordpress/Request/` already exists **[V]**). Moving them removes ~90 % of the WP-function surface from Domain in one step; the 16 entity calls are hydration (`maybe_unserialize`/`stripslashes_deep` on serialised columns), time (`current_time`/`mysql2date`), slugs (`sanitize_title`) and i18n, and need a repository-side hydration step, a `Clock` port, a slug formatter and a translator port respectively.
- **Status 2026-10-26 (P1-4a done):** the request/command DTOs (23 classes, not 22 — the Fixture context has ten) were relocated to `Infrastructure/Wordpress/Request/{Fixture,Tournament,Competition,Player}/` and `Event_Details_DTO` now decodes its `settings` column through the pure `Domain\Shared\Serialized_Column::decode_array()`; the baseline is **16 / 5** — exactly the entity calls listed above (P1-4b). `Tournament::update_from_request()` takes a pure `Tournament_Update_DTO` so Domain does not import the relocated parser (`domain.no_outer_layer_imports` stays strict at 0).
- **Status 2026-10-26 (P1-4b done):** the 16 entity calls are gone and `Domain/` calls no WordPress function. `Rubber` and `League_Team` decode their serialised `custom`/`roster` columns through `Domain\Shared\Serialized_Column` (`decode()` / `decode_array()` with `allowed_classes => [stdClass]`, because legacy rows really hold plain `stdClass`, plus `unslash()` — a recursive `stripslashes` equal to `stripslashes_deep()` — applied only where the entity previously unslashed); `League::decode_serialized_array()` delegates to the same helper. `Rubber` slices `Y-m-d` / `H:i` with `substr()` (equal to `mysql2date()` for the well-formed MySQL datetimes the column holds). `Results_Report` uses the existing `Domain\Value_Objects\Json_Encoder` (same UTF-8 recovery as `wp_json_encode()`, default flags, byte-identical output). `Competition::resolve_event()` compares both sides through the new `Domain\Shared\Slug::normalise()`, a port of `sanitize_title()` verified against WordPress core on accented/entity/`×` samples (`Slug_Test`). `Club_Player::approve()` / `remove()` take an explicit `DateTimeImmutable $now` and throw untranslated messages; time comes from the new `Domain\Ports\Clock_Interface` port implemented by `Infrastructure\Time\Wordpress_Clock` (`current_time('mysql')` in `wp_timezone()`, so stored timestamps are unchanged), registered as container key `clock` (and `Clock_Interface::class`) in `Core_Service_Provider` and injected into `Registration_Service` as an optional constructor parameter defaulting to `Wordpress_Clock` (the existing `mailer`/`Wp_Mailer` pattern). `Player_Request_Action_Dispatcher` needed no change — it only calls `Registration_Service`. No user-visible behaviour changed (the pre-existing `remove_registration()` → `approve()` routing is preserved as-is). Tests: `Serialized_Column_Test`, `Slug_Test`, `Club_Player_Test`, `Wordpress_Clock_Test`, parity cases in `Rubber_Test`/`League_Team_Test`/`Results_Report_Test`, `Registration_Service_Test` with `tests/Support/Fixed_Clock`.
- **Guardrail:** `domain.no_wordpress_functions` (**strict, 0** since 2026-10-26; 173 **[A]** at audit time, 16 after P1-4a). The rule matches families (`wp_*`, `sanitize_*`, `esc_*`, `get|add|update|delete_*_meta`) plus an explicit list of i18n, option/transient, hook, user, time, URL and data-helper functions (`Layer_Rules::WORDPRESS_FUNCTIONS`); `Layer_Rules_Test` asserts that no pattern matches a PHP built-in, so plain PHP (`get_class`, `current`, `date`, …) is never penalised.

### L4 — Domain → `Util` static coupling
- **Where [M]:** `Util::`/`Util_Lookup::` static calls: 14 in 8 Domain files (`Tournament.php` 4, `Club_Role.php` 2, `Competition_Policy.php` 2, `League_Team.php` 2, `Competition.php`, `Event.php`, `Team.php`, `DTO/Team/Team_Fixture_Settings_DTO.php` 1 each). `use Racketmanager\Util\…` imports: 12 in 10 files (adds `Player_Factory.php`, `Players_List_DTO.php`).
  `Util/Util.php` itself uses `$wpdb` (4) and `global $racketmanager` **[M]**, so these calls are a transitive leak of infrastructure into Domain.
- **Why it matters:** `Util` is a grab-bag (SEO slugs, date arithmetic, cron clearing); Domain should depend on narrow ports (e.g. `Slug_Formatter_Interface`, `Clock_Interface` — the latter exists in `Domain/Ports/` since 2026-10-26, P1-4b) or pure helpers (`Domain\Shared\Slug`, `Serialized_Column`).
- **Guardrail:** `domain.no_util_static` (ratchet, 14).

### L5 — Service-locator via global / singleton
- **Where [M]:** The text scan finds 33 `global $racketmanager` hits in 18 `src/php` files, including one docblock at `RacketManager.php:412`; 32 are code statements. Of the hits, 23 are in 11 `Services/` files. There are a further 23 in root `functions.php`. `->container->get(` reads: 93 in 25 files (the planning estimate of 188 **[E]** included `$c->get()` inside providers, which is legitimate composition-root code and is excluded here). `RacketManager::get_instance()` outside bootstrap: 1 (`Cli/Export_Command.php`); `Infrastructure/Wordpress/Bootstrap/Plugin_Bootstrap.php` (2) is the composition root and is allowed. `Rest_Resources` receives the plugin instance and does not declare the global.
- **Why it matters:** Hidden dependencies, untestable without booting the plugin.
- **Guardrail:** `application.no_global_racketmanager` (strict), `services.no_global_racketmanager` (ratchet), `all.no_get_instance_outside_bootstrap` (ratchet, 1).

### L6 — Legacy global helper functions called from layered code
- **Where [M]:** AST-resolved count: **257 calls in 65 `src/php` files** (definitions and method calls excluded; resolution rule in §2.1). Query-service accessors account for 33 calls in 19 files (`get_league_query_service()` 26 / 18, `get_event_query_service()` 7 / 6). One repository accessor (`get_event_repository_for_helpers()`, `Standings_Service`) is counted with the non-query-service calls, which total **224**. Namespace-qualified calls include `\Racketmanager\get_league_query_service()` at `Application/Admin/Commands/League_Match_Handler.php:91` and `\Racketmanager\un_seo_url()` at `Application/Event/Queries/Get_Event_Players_Query.php:45`.

  | Helper | Calls | Files | Top callers |
  |---|---|---|---|
  | `seo_url()` | 75 | 10 | `Email_Shortcode_Presenter` 13, `Notification_Presenter` 13, `Tournament_Presenter` 12, `Rest_Resources` 9 |
  | `get_league()` | 48 | 24 | `Admin_Display` 6, `Public/Shortcodes` 5, `League_Schedule_Service` 5, `Admin_Cup` 4 |
  | `un_seo_url()` | 44 | 20 | `Rest_Resources` 8, `Club_Shortcode_Adapter` 6, `Get_Competition_Entry_Query` 4, `Fixture_Shortcode_Adapter` 3 |
  | `get_event()` | 20 | 11 | `Club_Shortcode_Adapter` 3, `Rest_Resources` 3, `League_Schedule_Service` 3 |
  | `get_team()` | 15 | 10 | `League_Schedule_Service` 4, `Admin_Cup` 2, `Club_Shortcode_Adapter` 2 |
  | `get_club()` | 7 | 7 | `Admin_Cup`, `Admin_Display`, `Shortcodes`, `User_Repository`, … |
  | `get_player()` | 7 | 5 | `Validator` 3, `User_Repository`, `Player_Service`, `Validator_Club` |
  | `get_league_team()` | 5 | 4 | `Standings_Service` 2, `League_Constitution_Handler`, `Admin_Ajax_Adapter`, `Competition_Service` |
  | `get_message()` | 1 | 1 | `Account_Ajax_Adapter` |
  | `get_user()` | 1 | 1 | `Validator_Club` |
  | `get_*_repository_for_helpers()` | 1 | 1 | `Standings_Service` |
  | `get_league_query_service()` | 26 | 18 | `Championship_Admin_Service` 4, `Admin_Cup` 3, `Championship_Manager` 3, `Rest_Resources` 2 |
  | `get_event_query_service()` | 7 | 6 | `Rest_Resources` 2, `Admin_Display`, `Email_Shortcode_Adapter`, `Public/Shortcodes`, `League_Schedule_Service` ×2 |

  The query-service accessors account for 33 calls across 19 source files (the 18 files calling `get_league_query_service()` plus `Competition/League_Schedule_Service.php`, which calls only `get_event_query_service()`). Three same-named **method definitions** are excluded: `Admin/Admin_Display.php:1223` (`get_event_query_service`), `Admin/Admin_Display.php:1231` (`get_league_query_service`) and `Services/Player_Query_Service.php:52` (`get_event_query_service`).

  **Templates [M]:** 7 call sites in 4 templates — `templates/admin/league/crosstable.php:31` (`get_league_team()`) and `:39` (`get_league_query_service()`), `templates/league/crosstable.php:49` (`get_league_query_service()`), `templates/match-teams-result.php:151,411` (`seo_url()` ×3; line 411 holds two calls), `templates/match-list.php:44` (`seo_url()`).
- **Why it matters:** Helpers resolve repositories through `global $racketmanager`, re-introducing the service locator everywhere they are called; `seo_url()`/`un_seo_url()` are pure formatting and should be an injectable formatter.
- **Guardrail:** `all.no_legacy_helper_calls` (ratchet, 257), `templates.no_legacy_helper_calls` (**strict, 0** since 2026-10-26 — the seven template sites listed above were fixed by P1-5a; the line references describe the pre-fix state).

### L7 — Raw `$wpdb` outside `Repositories/`
- **Where [M]:** 147 references in 11 files: `Services/Admin/League/League_Schedule_Service.php` (45), `Services/Player_Query_Service.php` (32 by regex, **34 [A]** — the AST also counts the two `$GLOBALS['wpdb']` reads on line 67, invisible to `\$wpdb\b`), `Activator.php` (24), `Admin.php` (13), `Services/Team_Service.php` (11), `Infrastructure/Wordpress/Bootstrap/Plugin_Bootstrap.php` (4), `Services/Competition_Entry_Service.php` (4), `Services/Tournament_Service.php` (4), `Util/Util.php` (4), `Services/Container/Providers/Player_Service_Provider.php` (3), `Services/Upgrade.php` (3).
  (`Activator`, `Upgrade`, `Plugin_Bootstrap`, `Admin.php` are install/bootstrap code — tolerable; the five `Services/*` files are the real targets.)
- **Guardrail:** `services.no_wpdb` (ratchet, 7 files), `application.no_wpdb` / `domain.no_wpdb` (strict, 0).

### L8 — `RacketManager` god-facade
- **Where [M]:** `src/php/RacketManager.php` — 744 lines, 46 public methods. Callers of facade-style query methods (method-name match on any receiver, so upper bounds): `->get_matches(` 67 / 31 files, `->get_leagues(` 25 / 15, `->get_clubs(` 22 / 19, `->get_confirmation_email(` 22 / 6, `->get_team_id(` 12 / 6, `->get_events(` 2 / 1, `->get_winners(` 0. Public `ajax_*`/`shortcodes_*` properties still exposed **[V]**.
- **Guardrail:** none yet; tracked via `services.no_global_racketmanager` (callers reach the facade through the global) and backlog item 12.

### L9 — Admin screens as `Admin_Display` subclasses with inline request processing
- **Where [M]:** 13 classes `extends Admin_Display`; `Admin_Display.php` is 1,234 lines and pulls dependencies via `$c->get()`. Its five `$c->has(...)` branches have distinct fallbacks: one constructs `Event_Repository`, two call `get_event_query_service()` / `get_league_query_service()`, one constructs `Admin_Championship_Finals_Presenter`, and one optional schedule presenter is only assigned when registered. Superglobal references: 608 in 30 `Admin/` files — `Admin_League` 122, `Admin_Display` 88, `Admin_Cup` 79, `Admin_Club` 49, `Admin_Finances` 38, `Admin_Player` 32, `Tournament_Event_Config_Admin_Controller` 25, `Admin_Tournament` 22, `Admin_Redirect_Url_Builder` 22, `Admin_Competition` 21. (The planning figure of ~300 **[E]** counted statements; this count is token references.) Template `require`/`include` from classes: 33 in 7 files (`Admin_League` 10, `Admin_Cup` 8, `Admin_Club` 6, `Admin_Display` 5, `Admin_Upgrade` 2, `Admin_Import` 1, `Admin_Result` 1).
- **Modern path already present [V]:** `Admin/Controllers/` (15 `Tournament_*_Admin_Controller` + `Export_Admin_Controller` + `Admin_Redirect_Url_Builder`) with Action Dispatchers (`Services/Admin/**/*_Action_Dispatcher`), `Action_Guard_Interface` and PRG redirects.
- **Guardrail:** `admin.no_template_require` (ratchet, 33). Superglobal reads in `Admin/` are *not* ratcheted (controllers legitimately snapshot `$_POST` at the boundary); tracked by backlog item 9.

### L10 — Templates not on `$vm`
- **Where [M]:** 66 / 304 templates reference `$vm`; 71 templates call methods on non-`$vm` variables (e.g. `$match->league->event->competition->get_type()`); 6 read superglobals (`admin/tournament-edit.php` 14, `championship.php` 4, `finals.php` 4, `competitions.php` 4, `setup-event.php` 2, `setup.php` 2); 2 use `global $racketmanager` (`match-list.php`, `match.php`); 0 use `$wpdb`. Inline `on*=` handlers: 47 `click|change|submit|load` attributes in 33 templates **[M]**; **52 attributes in 35 templates [J]** with the full event list (29 under `templates/admin/`, 6 frontend: `competition/dropdown.php`, `event/dropdown.php`, `event/team-matches.php`, `league/season-dropdown.php`, plus `onblur=` in `match-teams-result.php` and `match-tournament.php`).
  The earlier figure "194 templates call entity methods directly" **[E]** could not be reproduced; 71 is the measured count for *method calls*. Property access on entities (`$league->title`) is far more common and was not counted.
- **Guardrail:** `templates.no_globals_or_db` (ratchet, 30 superglobal reads in 6 files **[A]**: `admin/tournament-edit.php` 14, `admin/championship.php` 4, `admin/championship/finals.php` 4, `admin/includes/competitions.php` 4, `admin/tournament/setup-event.php` 2, `admin/tournament/setup.php` 2) and `templates.no_global_racketmanager` (ratchet, 8 service-locator accesses in 4 files **[A]**: the 2 `global $racketmanager` statements above plus 6 `$GLOBALS['racketmanager']` reads in `forms/match-input.php` (3) and `match-teams-result.php` (3) that the `global` regex did not see) — split into two rules because the scanner records `global`/`$GLOBALS` access and plain variable reads as separate facts; JS/Jest rule `templates.inline_event_handler` (ratchet, 52 attributes in 35 templates **[J]**: 25 `onclick=`, 21 `onchange=`, 5 `onblur=`, 1 `onload=`; the 33 above is the narrower `click|change|submit|load` regex count).

### L11 — `extract()` in renderers
- **Where [A]:** 4 real `extract()` *function calls*: `Services/View/Php_View_Renderer.php`, `Services/View/Shortcode_Template_Renderer.php`, `Public/Shortcodes.php`, `Presentation/Presenters/Fixture_Presenter.php`. The regex count of 5 **[M]** also matched `Presentation/Presenters/Team_Match_Row_Presenter.php`, but there `extract` is a *private method* (`$this->extract(...)`), not the PHP builtin — the AST scanner correctly excludes it.
- **Guardrail:** `all.no_extract` (ratchet, 4).

### L12 — Missing `declare(strict_types=1)`
- **Where [M]:** 292 / 849 files. Per top-level directory: `Domain/` 72, `Services/` 62, `Admin/` 38, `Repositories/` 35, `Infrastructure/` 21, `Application/` 10, `Presentation/` 2, `Rest/` 2, `Util/` 3, `Public/` 1, `Cli/` 1, `Sports/` 1, `Exceptions/` 42, plus 2 of 4 root files. `Gateways/` has 0 missing declarations. Useful subdirectory hotspots include `Domain/DTO` 44, `Infrastructure/Wordpress` 19, `Admin/Controllers` 16, `Repositories/Interfaces` 16, `Services/Admin` 14, `Admin/View_Models` 13, and `Services/Validator` 11.
- **Why it matters:** `.junie/guidelines.md` §3 mandates `strict_types` in every file; 34 % of the tree does not comply.
- **Guardrail:** `all.strict_types` (**strict since 2026-10-26**; was ratchet, 292).
- **Progress (2026-10-26, P1 item 5 part 1):** `Exceptions/` (42), `Domain/` (62), `Repositories/` (35) and `Application/` (10) now declare `strict_types=1` — baseline **282 → 133** (remaining: `Services/` 62, `Admin/` 38, `Infrastructure/` 21, `Util/` 3, `Presentation/` 2, `Rest/` 2, `Cli/` 1, `Public/` 1, `Sports/` 1, `Activator.php`, `RacketManager.php`). Strict mode surfaced the row-hydration coercions the entities relied on (`$wpdb` strings into `int`/`bool` properties, `floor()`/`ceil()` floats into `int`, serialized-settings ints into `?string`); they are handled by the new `Domain\Shared\Typed_Property_Hydrator` (the reflection-based coercion previously duplicated in `Event`/`League`, now also used by `Club_Player`, `Club_Role`, `League_Team`, `Rubber`, `Message`) and by explicit casts at the call sites (`Tournament::get_season()`, `League_Team::set_season()`, `Charge_Details_DTO`, `Club_Role_Repository::search()`, `Event_Repository::find_by_competition_id()`, `Fixture_Details_Assembler`, `Delete_Messages_Command::from_post()`). No declared type was loosened.
- **Done (2026-10-26, P1 item 5 part 2):** the remaining 133 files (`Services/` 62, `Infrastructure/` 21, `Admin/` 38, `Util/` 3, `Presentation/` 2, `Rest/` 2, `Cli/` 1, `Public/` 1, `Sports/` 1, `Activator.php`, `RacketManager.php`) declare `strict_types=1`; baseline **133 → 0** and the rule is **strict**. The Unit + Integration suites surfaced ~130 `TypeError`s from a handful of root causes, all fixed at the call site with explicit casts and no declared type loosened: `season` crossing between `string`-typed request DTOs / `Tournament::$season` and `?int`/`int` repository and service parameters (`Competition_Entry_Service`, `Finance_Service`, `Tournament_Service`, `Admin_League`, `Championship_Admin_Service`), `lta_number`/`btm` strings into `?int` validators, `int` ids into `string`-typed lookups (`Util_Lookup::get_match_day()`, `Util::get_users_for_favourite()`, `Util_Lookup::get_club_role()`, `Event::get_season_by_name()`), `Result_Factory` winner/loser ids, `round()` on a numeric string in `Set_Score_Validator`, `(int)` user ids into `Fixture::set_*_approver()`, `Fixture_Presenter` points into `Fixture_Result_Read_Model`, `Util_Lookup::get_player_id()` return, and `RacketManager::read_directory()` passing `readdir()`'s terminal `false` to `is_dir()`. A static hazard scan (typed scalars flowing into incompatible PHP built-ins or project signatures, receiver-aware) found six further boundaries that no test exercised — `Club_Service::set_club_role()`, `Competition_Service::is_season_valid_for_competition()`/`set_championship_round_dates_for_competition_season()`, `Finance_Service::set_charge_used()`, `Player_Service::update_btm()`, `Admin_Club` club validation, `Validator_Fixture::add_player_error()` — fixed the same way and pinned by `tests/Unit/Services/Strict_Types_Boundary_Regression_Test.php` and three new `Competition_Service_Test` cases.

### Other verified items **[V]**
- One `#[AllowDynamicProperties]` (`Fixture_Presentation_Adapter`).
- No `${var}` string interpolation, `each()`, `create_function()`, `utf8_encode()`, `FILTER_SANITIZE_STRING`.
- `Racketmanager_Match` no longer exists in `src/php` (older docs still describe it — see §8).

---

## 5. Structural & naming inconsistencies

| # | Finding | Evidence **[V]/[M]** | Recommendation |
|---|---|---|---|
| S1 | **Ports scattered across five locations** | `Domain/Ports/` (1 at audit time: `Rating_Gateway_Interface`; 2 since 2026-10-26 with `Clock_Interface`), `Application/Matches/Ports/` (1: `Fixture_Listing_Finder_Interface`), top-level `Gateways/` (5 files in `Account`, `Mail`, `Option`, `Payment`), `Repositories/Interfaces/` (20), `Services/Contracts/` (2: `Data_Exporter_Interface`, `Wtn_Api_Client_Interface`). Adapters live in `Infrastructure/Gateways/` and `Infrastructure/Legacy/`. | Consolidate to `Domain/Ports/` (domain-facing) and `Application/Ports/` (use-case-facing); keep `Repositories/Interfaces/` as the persistence port group. Backlog 10. |
| S2 | **`Domain/DTO/` and `Domain/DTOs/` coexist** | `Domain/DTOs/Results_Checker_Data.php` is the only file in `DTOs/`. `Domain/View_Models/` is empty. `Application/Fixture/DTOs` vs `Domain/DTO/Fixture`. | Move `Results_Checker_Data` into `Domain/DTO/Result/`; delete `Domain/View_Models/`; standardise on `DTO`. Backlog 3. **Done 2026-10-26** (old FQCN aliased in `aliases.php`; `Application/Fixture/DTOs` naming is left for P3). |
| S3 | **Two unrelated classes named `Fixture_Detail_Service`** | `Services/Fixture/Fixture_Detail_Service.php` and `Application/Fixture/Services/Fixture_Detail_Service.php`; the latter is aliased in its provider to avoid the clash. Usage split ≈14 / 2 **[E]**. | Rename the Application one to `Fixture_Details_Assembler`. Backlog 3. **Done 2026-10-26** (container key `fixture_details_assembler`; old FQCN and the `application_fixture_detail_service` key kept as deprecated aliases). |
| S4 | **`Services/` vs `Application/` has no documented rule** | 137 files in `Services/` (application services, 40+ `*_Action_Dispatcher`s, query services, validators, the container) vs 239 in `Application/` (commands/queries/handlers). | Document: *Application* = use-case handlers + ports; *Services* = legacy application services pending migration; *Infrastructure* = adapters. Backlog 10. |
| S5 | **Application → Presentation inversion** | 123 distinct `Presentation\Presenters\*` / `Presentation\View_Models\*` classes depended on by 65 `Application/` files **[A]** (query handlers return view models). Also `Services/` → `Presentation\|Admin` 59 classes / 12 files **[A]** (58 `use` lines plus `Admin_Service_Provider` → top-level `Racketmanager\Admin`), and `Repositories/` → `Services\` 7 classes / 6 files (`Tournament_Repository`→`Competition_Service`, `League_Repository`→`Championship_Factory`, `Fixture_Repository`/`Competition_Repository`→`Fixture_Relation_Loader`, `Rubber_Repository`→`Registration_Service`, `Fixture_Repository(_Interface)`→`Export\DTO\Export_Criteria`). | Handlers return application DTOs; presenters are applied in shortcode/admin adapters. Move `Export_Criteria` to a neutral namespace. Backlog 11. |
| S6 | **Request DTOs in `Domain/DTO/*_Request*`** | 21 request/command DTOs parse HTTP input with `sanitize_text_field`/`__()` (L3). `Infrastructure/Wordpress/Request/` exists (currently holds `Competition_Request.php`). | Relocate. Backlog 4. **Done 2026-10-26** (23 classes moved to `Infrastructure/Wordpress/Request/<Context>/`, old FQCNs aliased). |
| S7 | **Container keys are ad-hoc strings** | `Simple_Container` exposes `set/get/has`; providers register **306 distinct string keys** and 20 `::class` keys **[M]** (the planning note's "74" **[E]** was stale). `Admin_Display` has 5 `$c->has()` branches: one falls back to `new Event_Repository()`, two to global query-service helpers, one to a presenter constructor, and one is an optional presenter with no fallback. | Introduce `::class` keys + autowiring; remove the repository/helper/presenter fallback branches. Backlog 15. |

---

## 6. Deprecation surface & external consumers

### 6.1 `src/php/aliases.php` **[V]/[M]**
- **29** `class_alias` map entries at audit time (one `foreach` performs the aliasing — a naive `class_alias(` grep returns 1). Planning notes said 33 **[E]**; 29 is the measured count. **31** since 2026-10-26: P1-3 added `Application\Fixture\Services\Fixture_Detail_Service` → `Fixture_Details_Assembler` and `Domain\DTOs\Results_Checker_Data` → `Domain\DTO\Result\Results_Checker_Data`; **54** after P1-4a added the 23 `Domain\DTO\<Context>\*_Request*` / `*_Command` → `Infrastructure\Wordpress\Request\<Context>\*` relocations (group `// Relocated request DTOs (10.1.0)`).
- Targets: 19 relocated Domain classes (e.g. `Racketmanager\Domain\Club` → `Domain\Club\Club`, `Racketmanager\Domain\User` → `Domain\Player\User`), 10 commands moved from `Domain\DTO\*` to `Application\*\Commands\*`, and the 2 naming-collision fixes above.
- Annotated `@deprecated 10.0.0`, sunset **v11.0.0**. No references to alias names inside `src/php` **[V]**; `tests/Unit/Aliases_Test.php` covers them.

### 6.2 `functions.php` helpers **[M]**
34 functions are defined. Five carry `@deprecated`: `get_competition()`, `get_match()`, `get_rubber()`, `match_add_players()`, `get_club_role()`. A regex scan for callers returned 3 hits, **all of which are method definitions** (`Tournament::get_competition()`, `Competition_Service::get_competition()`, `Util_Lookup::get_club_role()`), so the real caller count is **0** — safe to emit `_deprecated_function()` now and remove in v11.

Un-annotated but legacy at audit time (should be marked `@deprecated 10.1.0` with replacements): `get_league`, `get_event`, `get_team`, `get_club`, `get_player`, `get_league_team`, `get_user`, `to_legacy_user`, `get_message`, `get_tournament_entry`, `get_result_check`, `seo_url`, `un_seo_url`, `create_new_url_querystring`, `wp_get_current_url`, and the nine `get_*_repository_for_helpers()` / `get_*_query_service()` accessors. `debug_to_console`/`log_trace` are dev utilities.

**Status 2026-10-26 (P1-2 done):** a php-parser recount gives **33** functions (not 34); 29 of them now carry `@deprecated` + `@see`: the 24 legacy accessors listed above (all except `create_new_url_querystring`/`wp_get_current_url`) were annotated `10.1.0`, and the five pre-existing `@deprecated` helpers gained an `@see`. `@see` targets are the `Repositories\Interfaces\*_Repository_Interface` method / container key for the entity accessors, `Util::seo_url()`/`un_seo_url()`, `Legacy_User_Factory::from_user()` and the `event_query_service`/`league_query_service` container entries. **No runtime notice was added** to the newly annotated helpers (they still have ~257 internal callers, tracked by `all.no_legacy_helper_calls`); the five original ones keep `trigger_error(E_USER_DEPRECATED)` rather than switching to `_deprecated_function()` so they behave identically with or without WordPress loaded (observed by `Template_Tags_Decoupling_Test`). `create_new_url_querystring`, `wp_get_current_url`, `debug_to_console`, `log_trace` remain un-deprecated plugin plumbing. Enforced by `tests/Unit/Functions_Deprecation_Annotations_Test.php`.

Other `@deprecated` members **[V]:** `RacketManager::read_directory()`; `Public/Shortcodes::show_daily_matches()` / `show_latest_results()` (adapters exist).

### 6.3 External consumer: `themes/sydney-child/functions.php` **[V]**
`sydney_woocommerce_header_cart()` (lines 39–46):
```php
if ( class_exists( 'Racketmanager\Domain\User' ) ) {              // alias (sunset v11)
    $rm_user = Racketmanager\get_user( get_current_user_id() );   // legacy helper
    …  Racketmanager\seo_url( $rm_user->display_name ) …          // legacy helper
    $message_count = $rm_user->get_messages( … );                  // served by Infrastructure\Legacy\Legacy_User
```
This is the **only** external consumer found. Removing `aliases.php`, `get_user()`/`seo_url()` or `Legacy_User` without a replacement will break the theme header. The v11 sunset must ship a supported entry point (e.g. a small `Racketmanager\Public_Api` facade or documented container access) **and** a theme patch. Backlog 17.

---

## 7. JavaScript (`src/js/`) **[M]** / **[J]**

- 90 files; modular ESM build via esbuild (`src/js/index.js` single entry) with feature folders and delegated `data-action` handlers — aligned with `docs/architecture.md`.
- **jQuery residue:** 1,080 `jQuery`/`$(` token matches in 75 / 90 files **[M]**; **1,009 code references in 74 / 90 files [J]** once comments, strings and regex literals are excluded (`utils/jquery-helpers.js` drops from 62 to 26 — most of its mentions are docblocks; `features/autocomplete/index.js` only mentions jQuery in comments). Hot spots **[J]**: `admin/ajax.js` 153, `admin/functions.js` 91 (78 `$(` + 13 `jQuery`), `features/messages/messages.js` 46, `team-order.js` 33, `set-calculator.js` 32, `utils/jquery-helpers.js` 26.
- **`window.*` assignment-pattern matches:** 10 in 8 files **[M]** (planning note said 13 **[E]**); after excluding the four `window.Racketmanager = window.Racketmanager || {}` namespace idioms and `window.location` navigation, **5 actionable `window.*` assignments in 3 files** remain, and the AST pass adds **2 `globalThis.*` assignments** the regex could not see — **7 actionable global assignments in 4 files [J]** (the `js.window_global_assignment` baseline):
  `config/constants.js:7` (`window.Racketmanager = {…}`), `:17` (`window.RACKETMANAGER_DISABLE_LEGACY = true`); `features/tabdata/tab-data.js:359` (`window.Racketmanager = {}` — *overwrites* the namespace); `admin/functions.js:217,222` (`window.activaTab`, `window.setIframeHeight`); `features/match/set-calculator.js:169,170` (`globalThis.SetCalculator`, `globalThis.SetCalculatorTieBreak` — exposed solely so the inline `onblur=` handlers in `match-teams-result.php` / `match-tournament.php` can call them, i.e. the same debt as the template rule); namespace idiom `window.Racketmanager = window.Racketmanager || {}` in `print-scorecard.js`, `po-modal.js`, `admin/ajax.js`, `admin/utils.js` and its `globalThis` spelling in `set-match-rubber-status.js`; `match-viewer.js:78` is `window.location = …` (navigation, not a global).
- **Admin bundles:** `admin/{functions,ajax,draggable,utils}.js` are built as four separate non-modular bundles (see `package.json` `build`).
- **Templates:** 35 templates with inline `on*=` handlers **[J]** (52 attributes; L10), 29 of them admin — the regex audit's 33 did not count the 5 `onblur=` attributes in `match-teams-result.php` and `match-tournament.php`.
- **Guardrails (Jest, implemented — Appendix A.2):** `js.jquery_reference` (ratchet, 1,009 / 74 files), `js.window_global_assignment` (ratchet, 7 / 4 files; `window.location` and the `||{}` idiom exempt), `templates.inline_event_handler` (ratchet, 52 / 35 files). Run with `npm test -- tests/js/architecture` (<1 s); the rule ids are the "Tracked by" column of backlog item 16.

---

## 8. Reconciliation with earlier documentation

| Document / statement | Current source **[V]** | Action |
|---|---|---|
| `docs/architecture.md` lists `src/php/Ajax/` and "AJAX controllers under `src/php/Ajax/` (e.g. `Ajax_Tournament`) ✓" | `src/php/Ajax/` no longer exists; AJAX lives in `Infrastructure/Wordpress/Ajax/*_Ajax_Controller` + `*_Ajax_Adapter` with `Ajax_Registry`. | Status note added to `architecture.md`; full rewrite of the layout section is backlog hygiene. |
| `docs/architecture.md` says `Admin extends RacketManager` | `class Admin` has no parent; it is wired through the container (`Admin_Service_Provider`). | Status note added. |
| `docs/ddd-audit-report.md`, `ddd-architecture-report.md`, `ddd-analysis-2026.md`, `match-migration-*.md` describe `Racketmanager_Match` as a live God Object | Removed from `src/php` (0 references). | Historical; this report supersedes them for debt tracking. |
| `.junie/guidelines.md` said "PHPUnit 10" | `composer.lock`: `phpunit/phpunit` 13.4.2; `phpunit.xml.dist` schema 13.4. | Corrected in guidelines. |
| `.junie/guidelines.md` §3: "Never use WordPress globals" | 33 `global $racketmanager` in `src/php` + 23 in `functions.php`. | Guideline stands; the ratchet makes it enforceable. |
| Planning notes: 33 aliases / 20 untyped constructors / 4 `extract()` / 74 container keys / 14 shortcode adapters / 23 `onclick` templates / 0 legacy helper calls in templates | 29 / 18 **[A]** (17 `?object` + 1 `stdClass` union; +5 DTO) / 4 **[A]** (regex said 5, one is a method) / 306 string keys / 11 adapters / 35 templates **[J]** (33 with the narrower regex) / 7 AST-resolved helper calls. | Measured values used throughout this report; AST figures from the guardrail baseline where they differ. |
| First draft of this report: 141 WP-function calls in Domain / `Player_Query_Service` 32 `$wpdb` / 58 Services→Presentation imports | 173 / 34 / 59 **[A]** — the guardrail list is wider than the audit regex (`absint`, `maybe_unserialize`, `sanitize_*` family, `stripslashes_deep`), counts `$GLOBALS['wpdb']`, and counts the top-level `Racketmanager\Admin` class. | Report reconciled to the baseline (§1, §3, L3, L7, S5, backlog 4/6, A.1). |
| Regex JS/template audit: 75 jQuery files / 1,080 refs, 33 `on*=` templates, 5 `window.*` globals; §3 Services `$wpdb` 102 | 74 / 1,009 **[J]** (comments, strings, regex literals excluded — the Babel token count is identical to the earlier lexer-based count), 35 templates / 52 attributes **[J]** (`onblur=` now counted), 7 global assignments / 4 files **[J]** (the Babel AST pass adds the 2 `globalThis.*` exposures in `set-calculator.js` that no `window.` regex can see), 103 **[A]** | Report reconciled to the Jest baseline (§1, §2.1, §3, L10, §7, backlog 16, A.2). |

---

## 9. Prioritised backlog

Effort: **S** < 1 day · **M** 1–3 days · **L** > 3 days. Risk: behavioural regression risk. "Tracked by" names the proposed guardrail rule(s) whose baseline should shrink when the item is done.

### P1 — Stop the bleeding (low risk)

> **Status 2026-10-26: all P1 items (1, 2, 3, 4, 5, 5a) are done.** Three ratchet rules flipped to strict as a result: `templates.no_legacy_helper_calls` (5a), `domain.no_wordpress_functions` (4) and `all.strict_types` (5).

| # | Item | Effort | Risk | Tracked by |
|---|---|---|---|---|
| 1 | **Land the architecture guardrail suite** (`tests/Architecture/`, php-parser scanner, `baseline.json` ratchet) and the Jest ratchet (`tests/js/architecture/`). *PHP suite done* (26 rules, 185 tests, Appendix A.1); *Jest ratchet done* (3 rules, 35 Jest tests incl. the engine tests, Appendix A.2); the existing Domain-purity and template-tag tests (Appendix A.4) are preserved alongside them. | M | none | all rules |
| 2 | Emit `_deprecated_function()` from the 5 `@deprecated` helpers (0 callers); annotate the remaining `functions.php` helpers `@deprecated 10.1.0` with repository/service replacements. *Done 2026-10-26* — `@deprecated` + `@see` on all 29 legacy helpers (§6.2); the 5 keep `trigger_error(E_USER_DEPRECATED)` (WordPress-independent) instead of `_deprecated_function()`; no notice added to the other 24. Guarded by `Functions_Deprecation_Annotations_Test`. | S | low | `all.no_deprecated_helper_calls` (strict) |
| 3 | Resolve naming collisions: rename `Application\Fixture\Services\Fixture_Detail_Service` → `Fixture_Details_Assembler`; move `Domain/DTOs/Results_Checker_Data` into `Domain/DTO/`; delete empty `Domain/View_Models/`. *Done 2026-10-26* — `Domain/DTO/Result/Results_Checker_Data`, `Domain/DTOs/` and `Domain/View_Models/` gone, container key `fixture_details_assembler`; both old FQCNs aliased in `aliases.php` (§6.1) and `all.no_alias_class_references` still 0. | S | low | — |
| 4 | Move `Domain/DTO/**/*_Request*` (and `Search_Players_Command`, `Tournament_Payment_Command`, `Tournament_Withdrawal_Command`) to `Infrastructure/Wordpress/Request/`; add aliases for any public names. Removes 157 of 173 WP-function calls from Domain **[A]**; the remaining 16 sit in five entities — `Rubber` (6) and `League_Team` (3) `maybe_unserialize`/`stripslashes_deep` serialised columns (move to repository hydration), `Club_Player` (4) and `Rubber` use `current_time`/`mysql2date` (a `Clock` port), `Competition` (2) `sanitize_title` (slug formatter), `Club_Player` `__()` and `Results_Report` `wp_json_encode` (translator port / `json_encode`). *Part (a) done 2026-10-26* — the 23 request/command DTOs (10 `Fixture`, 10 `Tournament`, 2 `Competition`, 1 `Player`; the audit figure of 22 undercounted by one) now live in `Infrastructure/Wordpress/Request/<Context>/` with `strict_types`; 36 consumers/tests re-imported, the 7 DTO unit tests moved to `tests/Unit/Infrastructure/Wordpress/Request/**`, 23 old FQCNs aliased under `// Relocated request DTOs (10.1.0)` in `aliases.php` (`all.no_alias_class_references` still strict at 0). `Tournament::update_from_request()` now takes a pure `Domain\DTO\Tournament\Tournament_Update_DTO` (built by `Tournament_Request_DTO::to_update_dto()`) so the aggregate no longer imports the request parser. New `Domain\Shared\Serialized_Column` (pure `maybe_unserialize` equivalent) replaces the `maybe_unserialize` in `Event_Details_DTO`. Baseline **173 → 16** (the five entities above). *Part (b) done 2026-10-26* — `Serialized_Column` (now admitting plain `stdClass`, plus `unslash()`) in `Rubber`/`League_Team`/`League`, `substr()` date slices in `Rubber`, `Json_Encoder` in `Results_Report`, `Domain\Shared\Slug::normalise()` in `Competition`, and a `Clock_Interface` port (`Infrastructure\Time\Wordpress_Clock`, container key `clock`) feeding `Club_Player::approve()/remove()` through `Registration_Service` (details in L3). Baseline **16 → 0**, rule flipped to **strict**. | M | low | `domain.no_wordpress_functions` (strict) |
| 5 | Backfill `declare(strict_types=1)` directory by directory (start with `Exceptions` 42 and `Domain/DTO` 44 — pure data, lowest risk; run the Unit suite after each directory because strict typing can surface int/string coercions). *Part 1 done 2026-10-26* — `Exceptions/`, `Domain/`, `Repositories/`, `Application/` (149 files) declared; baseline **282 → 133**; coercion fixes via `Domain\Shared\Typed_Property_Hydrator` and call-site casts (L12). *Part 2 done 2026-10-26* — `Services/`, `Infrastructure/`, `Presentation/`, `Rest/`, `Util/`, `Cli/`, `Public/`, `Sports/`, `Admin/` and the root files (133) declared; baseline **133 → 0**, rule flipped to **strict**; call-site casts plus regression tests for the untested boundaries (L12). **P1 complete.** | M | medium | `all.strict_types` (strict) |
| 5a | Fix the 7 AST-resolved legacy helper calls in templates (`admin/league/crosstable.php`, `league/crosstable.php`, `match-teams-result.php`, `match-list.php`) so `templates.no_legacy_helper_calls` can flip to strict. *Done 2026-10-26* — `Admin_League` and `Get_League_Crosstable_Handler` pre-compute the `$crosstable_cells[row][column]` matrix via `League_Query_Service::get_crosstable_field()` (the handler also passes the explicit `$crosstable_columns` list it computed cells for; admin passes hydrated `$crosstable_teams`); the two orphan templates (no production caller) derive slugs with `Util::seo_url()` (static, identical implementation). Rendered HTML verified byte-identical for all four templates; render-level tests added (`tests/Unit/Templates/League_Crosstable_Templates_Test.php`, `Admin_League_Regression_Test::test_display_league_page_renders_precomputed_crosstable_*`, `Template_Tags_Decoupling_Test`); rule flipped to **strict** (0). | S | low | `templates.no_legacy_helper_calls` (strict) |

### P2 — Finish the layering

| # | Item | Effort | Risk | Tracked by |
|---|---|---|---|---|
| 6 | Extract raw SQL from `League_Schedule_Service` (45 `$wpdb` refs), `Player_Query_Service` (34 **[A]**), `Team_Service` (11), `Competition_Entry_Service` (4), `Tournament_Service` (4), `Player_Service_Provider` (3), `Upgrade` (2) into `League_Team_Repository`, `Player_Repository`, `Season_Repository`, `Team_Repository`. | L | medium | `services.no_wpdb` |
| 7 | Replace the 257 AST-resolved helper calls (33 query-service accessor calls and 224 other helper calls, including one repository accessor) in `Services/`, `Infrastructure/`, `Repositories/`, `Application/`, `Admin/`, `Rest/`, and `Presentation/` with injected repository interfaces/services; replace `seo_url()` / `un_seo_url()` (119 calls) with an injectable `Slug_Formatter` in `Presentation/Formatters`. | L | medium | `all.no_legacy_helper_calls`, `repositories.no_service_imports` |
| 8 | Typed hydration for the 18 untyped constructors (17 `?object` entities + `Results_Checker`'s `stdClass` union) via `*_Hydration_DTO` + factory, removing the `?object` fallback where a factory already exists (`Club`, `Team`, `Tournament`, `Event`, `League`, `League_Team`) and adding DTOs for `Rubber`, `Season`, `Charge`, `Invoice`, `Message`, `Results_Report`, `User`, `Club_Player`, `Club_Role`, `Player_Error`, `Tournament_Entry`; drop `stdClass` from `Results_Checker`. | L | medium | `domain.no_untyped_constructor` |
| 9 | Migrate `Admin_League`, `Admin_Cup`, `Admin_Club`, `Admin_Finances`, `Admin_Player`, `Admin_Competition` to `Admin/Controllers/*` + Action Dispatcher + PRG + `$vm` view models (pattern: `Tournament_*_Admin_Controller`). | L | high | `admin.no_template_require` |
| 10 | Consolidate ports (`Gateways/*`, `Services/Contracts/*`, `Application/*/Ports/*`) into `Domain/Ports/` / `Application/Ports/`; document the `Services/` vs `Application/` rule in `docs/architecture.md`; migrate `Services/` incrementally. | M | low | — |
| 11 | Break Application → Presentation: handlers return application DTOs; presenters applied in shortcode/admin adapters. Move `Services\Export\DTO\Export_Criteria` out of `Services`. (123 + 59 distinct `Presentation\|Admin` classes depended on **[A]**; the rules count dependencies however they are spelled, so a fully-qualified `new \Racketmanager\Presentation\…` without a `use` line is caught too.) | L | medium | `application.no_presentation_imports`, `services.no_presentation_imports` |

### P3 — Consolidation / v11

| # | Item | Effort | Risk | Tracked by |
|---|---|---|---|---|
| 12 | Slim `RacketManager.php` (744 lines / 46 public methods) to bootstrap + thin facade; move `get_confirmation_email`, `get_matches`, `get_clubs`, `get_leagues`, `get_team_id` to services; remove public `ajax_*`/`shortcodes_*` props. | L | medium | `services.no_global_racketmanager` |
| 13 | Strip UI/import/pagination from `League` (→ `League_View_State`, `League_Import_Mapper`). | M | medium | `domain.no_wordpress_functions` (strict), `domain.no_util_static` |
| 14 | Replace `extract()` in the 4 renderers/presenters with an explicit `$vm` scope; move remaining 238 templates onto typed view models. | L | medium | `all.no_extract`, `templates.no_globals_or_db`, `templates.no_global_racketmanager` |
| 15 | `::class` container keys + autowiring in `Simple_Container`; remove the `Admin_Display` `$c->has()` fallback branches (one repository, two global query helpers, one presenter), retaining only the intentionally optional presenter check. | M | medium | — |
| 16 | JS: retire `utils/jquery-helpers.js`; modularise `admin/*.js` into `src/js/admin/features/`; remove `window.activaTab`/`window.setIframeHeight` and `globalThis.SetCalculator`/`SetCalculatorTieBreak` (the latter disappear together with the `onblur=` handlers in `match-teams-result.php` / `match-tournament.php`); fix `tab-data.js` overwriting `window.Racketmanager`; remove inline `on*=` from the 35 templates **[J]** (start with the 29 admin templates as part of item 9). Progress is visible as shrinking counts in `tests/js/architecture/baseline.json`. | L | medium | `js.jquery_reference` (1,009 / 74), `js.window_global_assignment` (7 / 4), `templates.inline_event_handler` (52 / 35) |
| 17 | **v11.0:** remove `aliases.php` (29 entries), deprecated helpers, `Legacy_User`; ship a documented public API and a `sydney-child` theme patch (§6.3). | M | high (external) | `all.no_alias_class_references` |

---

## Appendix A — Guardrail rule catalogue

> **A.1 is implemented** (`tests/Architecture/Rules/Layer_Rules.php`; run with `vendor/bin/phpunit --testsuite Architecture`, ≈2.7 s, 185 tests). Each rule is its own PHPUnit case (`Domain_Layer_Test`, `Application_Layer_Test`, `Services_And_Repositories_Layer_Test`, `Cross_Cutting_Test`, one data-set per rule id); `Baseline_Integrity_Test` checks that the baseline is canonical, names only known rules, carries no strict-rule entries and that no rule's path scope is vacuous; `Support_Test/Layer_Rules_Test` proves every rule detects a positive snippet and ignores a clean one. "Baseline" is the AST-measured size committed to `tests/Architecture/baseline.json` on 2026-10-25 **[A]** (occurrences / files; keys are `file|detail` with a tolerated count). A rule marked *strict* has 0 violations and must stay at 0 (strict rules may not have baseline entries); a rule marked *ratchet* fails only on violations **not** covered by the baseline. **A.2 (Jest) is implemented** with the same baseline semantics (`tests/js/architecture/`, see below).

### A.1 PHP rules (`tests/Architecture/`)

| Rule id | Scope | Detects | Mode | Baseline **[A]** |
|---|---|---|---|---|
| `domain.no_wpdb` | `Domain/` | `$wpdb` variable, `global $wpdb`, `$GLOBALS['wpdb']` | strict | 0 |
| `domain.no_outer_layer_imports` | `Domain/` | any *class reference* (import, `new`, static, type hint, `instanceof`, extends/implements, catch) to `Racketmanager\(Repositories\|Services\|Infrastructure\|Admin\|Presentation\|Application\|Rest\|Public\|Gateways)\…` — stronger than the import-only check first proposed | strict | 0 |
| `domain.no_superglobals` | `Domain/` | `$_POST/$_GET/$_REQUEST/$_SERVER/$_COOKIE/$_FILES` | strict | 0 |
| `domain.no_wordpress_functions` | `Domain/` | calls to WordPress runtime functions: families `wp_*`, `sanitize_*`, `esc_*`, `get_*_meta`/`add_*_meta`/`update_*_meta`/`delete_*_meta` plus explicit i18n (`__`, `_e`, `_n`, `_x`, `_ex`, `_nx`, `_n_noop`, `_nx_noop`, `translate`, `number_format_i18n`, `date_i18n`), options/transients/metadata, hooks (`apply_filters`, `do_action`, `add_action`, `add_filter`, …), users (`get_userdata`, `get_user_by`, `get_current_user_id`, `is_user_logged_in`, `current_user_can`, …), time (`mysql2date`, `current_time`, `current_datetime`, …), URLs (`home_url`, `site_url`, `admin_url`, `add_query_arg`, `get_permalink`, …) and data helpers (`absint`, `maybe_serialize`, `maybe_unserialize`, `stripslashes_deep`, `is_email`, `is_wp_error`, `checked`, `selected`, `shortcode_atts`, …). Full list: `Layer_Rules::WORDPRESS_FUNCTIONS`; no pattern matches a PHP built-in (asserted by `Layer_Rules_Test`). | strict (since 2026-10-26, backlog 4) | 0 (was 173 / 27 at audit time — 157 in 23 `Domain/DTO/**` request DTOs relocated by 4a, then the 16 entity calls — `Rubber` 6, `Club_Player` 4, `League_Team` 3, `Competition` 2, `Results_Report` 1 — removed by 4b) |
| `domain.no_util_static` | `Domain/` | static calls on `Racketmanager\Util\Util`, `Util_Lookup` | ratchet | 14 / 8 |
| `domain.no_untyped_constructor` | `Domain/` excl. `Domain/DTO/` | `__construct` param whose type contains `object`/`stdClass` (incl. unions) | ratchet | 18 / 18 |
| `application.no_wpdb` | `Application/` | `$wpdb` | strict | 0 |
| `application.no_global_racketmanager` | `Application/`, `Domain/` | `global $racketmanager` / `$GLOBALS['racketmanager']` | strict | 0 |
| `application.no_superglobals` | `Application/` | superglobals | ratchet | 1 / 1 (`Login/Queries/Get_Login_Form_Query.php`, `$_SERVER`) |
| `application.no_concrete_repositories` | `Application/` | any class reference to `Racketmanager\Repositories\<non-Interfaces>` | strict | 0 |
| `application.no_presentation_imports` | `Application/` | *dependency* on any class matching `Racketmanager\(Presentation\|Admin)\…` or the top-level `Racketmanager\Admin` — one violation per distinct class per file, whether spelled as `use`, fully-qualified `new`/static call, type hint or `instanceof` | ratchet | 123 / 65 |
| `services.no_wpdb` | `Services/` | `$wpdb` | ratchet | 103 / 7 (`League_Schedule_Service` 45, `Player_Query_Service` 34, `Team_Service` 11, `Competition_Entry_Service` 4, `Tournament_Service` 4, `Player_Service_Provider` 3, `Upgrade` 2) |
| `services.no_global_racketmanager` | `Services/`, `Infrastructure/`, `Presentation/`, `Rest/`, `Util/`, `Public/` | `global $racketmanager` / `$GLOBALS['racketmanager']` | ratchet | 36 / 18 |
| `services.no_presentation_imports` | `Services/` | dependency on `Racketmanager\(Presentation\|Admin)\…` or `Racketmanager\Admin` (same semantics as above) | ratchet | 59 / 12 (58 `use` lines + `Container/Providers/Admin_Service_Provider.php` → `Racketmanager\Admin`) |
| `repositories.no_service_imports` | `Repositories/` | dependency on `Racketmanager\Services\…` (same semantics as above) | ratchet | 7 / 6 |
| `all.no_legacy_helper_calls` | `src/php/**` (not `functions.php`) **and `template-tags.php`** | resolved calls to root `Racketmanager\` helper functions: `get_league`, `get_event`, `get_team`, `get_club`, `get_player`, `get_league_team`, `get_user`, `get_message`, `seo_url`, `un_seo_url`, `get_*_repository_for_helpers`, `get_*_query_service` | ratchet | 257 / 65 (`template-tags.php` contributes 0 today) |
| `all.no_deprecated_helper_calls` | `src/php/**`, `templates/**`, `template-tags.php` | calls to `get_match, get_rubber, get_competition, match_add_players, get_club_role, to_legacy_user` (namespaced or global; function calls only, not methods) | strict | 0 |
| `all.no_get_instance_outside_bootstrap` | `src/php/**` minus `Infrastructure/Wordpress/Bootstrap/`, `RacketManager.php`, `Admin.php` | `RacketManager::get_instance()` / `Admin::get_instance()` | ratchet | 1 / 1 (`Cli/Export_Command`) |
| `all.no_alias_class_references` | `src/php/**`, `templates/**`, `functions.php`, `template-tags.php` | any class reference to an alias name in `src/php/aliases.php` (parsed at runtime by `Support/Alias_Map`, 29 at audit time, 54 since 2026-10-26) | strict | 0 |
| `all.strict_types` | `src/php/**` | missing `declare(strict_types=1)` | strict (since 2026-10-26, backlog 5) | 0 (was 292 → 282 after the request-DTO relocation → 133 after `Exceptions/`, `Domain/`, `Repositories/`, `Application/` → 0 after `Services/`, `Infrastructure/`, `Admin/` and the rest were backfilled) |
| `all.no_extract` | `src/php/**` | call to the `extract()` function | ratchet | 4 / 4 |
| `templates.no_globals_or_db` | `templates/**` | `$wpdb`, superglobals | ratchet | 30 / 6 |
| `templates.no_global_racketmanager` | `templates/**` | `global $racketmanager` / `$GLOBALS['racketmanager']` (split from the rule above: separate scanner fact) | ratchet | 8 / 4 |
| `templates.no_legacy_helper_calls` | `templates/**` | same list as `all.no_legacy_helper_calls` | strict (since 2026-10-26, backlog 5a) | 0 |
| `admin.no_template_require` | `Admin/**` | `require`/`include` whose literal path contains `templates/admin/` | ratchet | 33 / 7 |
| `scanner.parse_error` | all scanned files (1,155) | php-parser failure | strict | 0 |

### A.2 JS / template rules (`tests/js/architecture/legacy-patterns.test.js`)

> **Implemented** (`npm test -- tests/js/architecture`, <1 s; 7 guardrail/integrity tests + 32 engine tests in `engine.test.js`). Rules are data in `tests/js/architecture/support/rules.js`; `support/source-scanner.js` walks `src/js/**/*.js` and `templates/**/*.php` with Node `fs` and parses each JS file once with **`@babel/parser`** (`sourceType: 'unambiguous'`, `tokens: true`, `errorRecovery: true`; it ships with the project's `@babel/core` devDependency, so no new package). The jQuery rule reads identifier tokens and the global-assignment rule walks the AST with a 20-line dependency-free `walkAst`, so comments, strings, template text and regex literals can never be mistaken for code and the regex-vs-division ambiguity (`i++ / 2; jQuery()`, `if (ok) /jQuery/.test(s)`, `{…} / 2`, ASI `return\n/re/`) does not arise — the hand-written lexer this replaced had exactly those blind spots. A file Babel cannot parse is reported as a `parse error: …` violation rather than skipped. `support/baseline.js` mirrors the PHP `Baseline` (same `{ rule: { "file|detail": count } }` format, 4-space JSON, sorted); `support/ratchet.js` produces the same failure message (violation list, exceeded keys, remediation, how-to-regenerate) and prints stale entries to STDERR. The baseline test suite verifies canonical sorting, known rule ids and `file|detail` key shape. Baseline keys are line-independent and occurrence-counted, so moving code never churns the baseline but one more `jQuery(` in a file already listed fails.
>
> **Deliberate exemptions / non-goals of `js.window_global_assignment`:** only `window.location` (navigation) and the namespace idiom `X.N = X.N || {}` / `X.N = X.N ?? {}` / `X.N ||= {}` / `X.N ??= {}` (same object and name on both sides, empty object literal) are exempt. `self.X =` is not matched because `self` is routinely a local alias (`const self = this`) and would need scope analysis; `Object.assign(window, …)` / `Object.defineProperty(window, …)` and destructuring targets are not matched either. Nested members (`window.Racketmanager.x =`) are extensions of an existing namespace, not new globals, and `foo.window.X =` is not the global object. Dynamic keys (`window[name] =`) are reported as `window[dynamic]`.

| Rule id | Scope | Detects | Mode | Baseline **[J]** (occurrences / files) |
|---|---|---|---|---|
| `js.jquery_reference` | `src/js/**/*.js` | Babel identifier token `jQuery` (any position: call, `window.jQuery`, `{ jQuery }`) or identifier `$` immediately followed by `(` (so `foo$(`, `$$(`, `$.ajax` are not counted; comments/strings/template text/regex are not tokens) — key detail `jQuery` or `$(` | ratchet | 1,009 / 74 (930 `jQuery`, 79 `$(`; 76 keys) — identical to the previous lexer-based baseline |
| `js.window_global_assignment` | `src/js/**/*.js` | Babel `AssignmentExpression` with **any** operator (`=`, `\|\|=`, `??=`, `&&=`, `+=` …) whose target is a direct member of `window` or `globalThis` (`window.X`, `window['X']`, `` window[`X`] ``, `window[expr]` → `window[dynamic]`); exempt: `window.location`, the namespace idiom (see note above); never matched: nested members `window.Ns.member =`, `foo.window.X =`, comparisons, `self.X =` | ratchet | 7 / 4 (`admin/functions.js` `activaTab`, `setIframeHeight`; `config/constants.js` `Racketmanager`, `RACKETMANAGER_DISABLE_LEGACY`; `features/tabdata/tab-data.js` `Racketmanager`; `features/match/set-calculator.js` `globalThis.SetCalculator`, `globalThis.SetCalculatorTieBreak` — the two `globalThis` entries were invisible to the regex/lexer version) |
| `templates.inline_event_handler` | `templates/**/*.php` | `\son<event>\s*=` for the DOM event names (`click`, `change`, `submit`, `load`, `blur`, `focus`, `key*`, `mouse*`, `input`, `select`, `drag*`, `touch*`, …), case-insensitive; key detail is the lower-cased attribute (`onclick=`) | ratchet | 52 / 35 (25 `onclick=`, 21 `onchange=`, 5 `onblur=`, 1 `onload=`; 29 admin + 6 frontend templates; 38 keys) |

### A.3 Baseline workflow

```bash
# run the guardrails
vendor/bin/phpunit --testsuite Architecture
npm test -- tests/js/architecture

# regenerate the baselines after *removing* debt (never to add debt)
RM_ARCH_UPDATE_BASELINE=1 vendor/bin/phpunit --testsuite Architecture
RM_ARCH_UPDATE_BASELINE=1 npm test -- tests/js/architecture
```

Rules of engagement:
1. A failing guardrail means **new** debt was introduced; fix the code, do not add it to the baseline.
2. Baseline keys are `file|detail` per rule with a tolerated occurrence count (line-independent), so moving code inside a file does not churn the baseline; adding one more occurrence of an existing key fails; renaming a file surfaces as one new violation plus one stale entry — regenerate after confirming the rename is intended. The PHP and Jest baselines share this format (`{ "<rule>": { "<file>|<detail>": <count> } }`, sorted, 4-space indent) and the same `RM_ARCH_UPDATE_BASELINE` switch.
3. Stale baseline entries (debt that no longer exists) are reported as warnings; prune them by regenerating.
4. When a ratchet rule reaches 0, flip its mode to `strict` in `tests/Architecture/Rules/Layer_Rules.php`. The Jest rules are all ratchet today; when one reaches 0 its rule entry simply disappears from `tests/js/architecture/baseline.json` and any new occurrence fails immediately (an empty baseline is the strict case).

### A.4 Existing test coverage and overlap

The architecture suite is not the project's first architecture guardrail. `tests/Unit/Domain/Domain_Architecture_Purity_Test.php` strictly checks tokenized Domain source for `global`, superglobals, `$GLOBALS`, `echo`/`print`, `wp_cache_*`, and imports from outer layers; `KNOWN_EXCEPTIONS` is empty. It overlaps with `domain.no_superglobals`, `domain.no_outer_layer_imports` and the Domain portion of `application.no_global_racketmanager`. **It has been deliberately kept** alongside the new suite (both are strict and both report zero, so there is no duplicate baseline); it may be retired once the Architecture suite has run in CI for a release cycle. `tests/Unit/Template_Tags_Decoupling_Test.php` also stays, but it is a *behavioural* test, not a guardrail: it renders the orphan templates through `template-tags.php` and asserts that no deprecation notice is raised. It does not inspect helper calls — `template-tags.php` is therefore in the scope of `all.no_legacy_helper_calls`, `all.no_deprecated_helper_calls` and `all.no_alias_class_references` (only `functions.php`, which *defines* the helpers, is excluded from the legacy-helper rule).

---

## Appendix B — Reproducing the measurements

Most counts in this report were produced with a throw-away PHP script (regex per file, see §2.1); legacy helper calls were separately resolved through php-parser 5.9 and `NameResolver` to exclude definitions and methods. The authoritative per-rule figures are now the counts in `tests/Architecture/baseline.json` (sum the values under a rule id for occurrences; count distinct `file|` prefixes for files). Quick regex cross-checks:

```bash
# files missing strict_types
php -r '$it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator("src/php",FilesystemIterator::SKIP_DOTS));$n=0;foreach($it as $f){if($f->getExtension()==="php"&&!preg_match("/declare\s*\(\s*strict_types\s*=\s*1\s*\)/",file_get_contents($f)))$n++;}echo $n,PHP_EOL;'

# $wpdb outside Repositories/
php -r '$it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator("src/php",FilesystemIterator::SKIP_DOTS));foreach($it as $f){$p=$f->getPathname();if(str_contains($p,"/Repositories/"))continue;$n=preg_match_all("/\\\$wpdb\b/",file_get_contents($p));if($n)echo "$n\t$p",PHP_EOL;}'

# test count without running the suite
vendor/bin/phpunit --list-tests | grep -c '^ - '

# JS / template figures [J] straight from the Jest baseline (occurrences, files per rule)
node -e 'const b=require("./tests/js/architecture/baseline.json");for(const r in b){const k=Object.keys(b[r]);console.log(r,k.reduce((s,x)=>s+b[r][x],0),new Set(k.map(x=>x.split("|")[0])).size)}'
```
