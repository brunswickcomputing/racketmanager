# PHPUnit 13 Migration & Deprecation Baseline Audit

## 1. Executive Summary & Baseline Comparison

This audit establishes the baseline testing metrics, deprecation inventory, and compatibility blockers for migrating the `racketmanager` WordPress plugin test suite from PHPUnit 12.5.29 to PHPUnit 13.4.2 under PHP 8.4.25.

| Metric | PHPUnit 12.5.29 Baseline | PHPUnit 13.4.2 Trial | Final Post-Step 4/5 |
| :--- | :--- | :--- | :--- |
| **Total Tests** | 1,626 (1,565 Unit, 61 Integration) | 1,626 (1,565 Unit, 61 Integration) | 1,626 (1,565 Unit, 61 Integration) |
| **Total Assertions** | 7,667 | 7,667 | 7,769 |
| **Execution Time** | ~0.80s – 0.90s | ~0.69s – 0.85s | ~0.68s – 0.81s (avg ~0.72s) |
| **Peak Memory** | ~85 MB | ~85 MB | ~84.50 MB |
| **Pass Status** | `OK (1626 tests, 7667 assertions)` | `OK, but there were issues!` | `OK (1626 tests, 7769 assertions)` |
| **PHPUnit Deprecations** | 0 | 100 occurrences (72 tests) | 0 |
| **PHP Deprecations / Errors**| 0 | 0 | 0 |

---

## 2. Primary Blocker: `with*() without expects()` Deprecations

### Issue Description
Under PHPUnit 13.4.2, 72 tests trigger **100 deprecation notices**:
`Using with*() without expects() is deprecated and will no longer be possible in PHPUnit 14.`

### Root Cause
Test doubles (primarily `createStub()` or `createMock()` objects marked with `#[AllowMockObjectsWithoutExpectations]`) configure method arguments via `->with(...)` without chaining an expectation constraint (e.g. `->expects($this->once())`). In PHPUnit 13+, stubs should configure return values without parameter constraints, whereas mocks requiring parameter verification must define explicit invocation counts.

### Affected Files Breakdown (33 Files, 100 Occurrences)

| Test File | Deprecation Occurrences | Affected Tests | Recommended Step 4 Fix |
| :--- | :---: | :---: | :--- |
| `tests/Unit/Services/Notification/Notification_Service_Test.php` | 12 | 5 | Add `expects($this->once())` or convert to pure `createStub()` without `with()` |
| `tests/Unit/Controllers/Admin/Admin_Cup_Delivery_Test.php` | 10 | 6 | Remove `with()` on stub return setups or add explicit expectations |
| `tests/Unit/Infrastructure/Wordpress/Ajax/Fixture_Ajax_Adapter_Test.php` | 10 | 5 | Add explicit `expects()` for command/query dispatch verifications |
| `tests/Unit/Services/Fixture/Fixture_Permission_Service_Test.php` | 9 | 8 | Convert stub `method()->with()->willReturn()` to `method()->willReturn()` or add `expects()` |
| `tests/Integration/Services/Fixture/Fixture_Result_Manager_Integration_Test.php` | 7 | 5 | Add explicit expectations or clean parameter filters on stubs |
| `tests/Unit/Domain/Competition/League_Mutation_Test.php` | 5 | 5 | Clean stub method definitions |
| `tests/Unit/Domain/Competition/Event_Settings_Test.php` | 3 | 1 | Fix mock expectation chain |
| `tests/Unit/Presenters/Fixture_Presentation_Adapter_Test.php` | 3 | 1 | Add explicit `expects()` or remove `with()` on stub |
| `tests/Unit/Services/Validator/Validator_Fixture_Can_Player_Enter_Result_Test.php` | 3 | 3 | Add explicit `expects($this->once())` |
| `tests/Integration/Services/Result/Result_Reporting_Service_Integration_Test.php` | 3 | 1 | Add explicit expectations for match reporting |
| `tests/Unit/Controllers/Admin/Tournament_Competition_Config_Admin_Controller_Test.php` | 2 | 2 | Fix stub/mock configuration |
| `tests/Unit/Controllers/Admin/Tournament_Information_Admin_Controller_Test.php` | 2 | 2 | Fix stub/mock configuration |
| `tests/Unit/Controllers/Admin/Tournament_Team_Admin_Controller_Test.php` | 2 | 1 | Fix stub/mock configuration |
| `tests/Unit/Controllers/Admin/Tournament_Teams_Admin_Controller_Test.php` | 2 | 2 | Fix stub/mock configuration |
| `tests/Unit/Infrastructure/Wordpress/Ajax/Frontend_Ajax_Adapter_Test.php` | 2 | 2 | Fix mock expectation chain |
| `tests/Unit/Infrastructure/Wordpress/Shortcodes/Fixture_Shortcode_Controller_Test.php` | 2 | 1 | Fix stub/mock configuration |
| `tests/Unit/Services/Championship_Manager_Test.php` | 2 | 2 | Add explicit `expects()` |
| `tests/Unit/Services/Competition_Service_Test.php` | 2 | 2 | Add explicit `expects()` |
| `tests/Unit/Services/Fixture/Fixture_Result_Manager_Test.php` | 2 | 2 | Add explicit `expects()` |
| `tests/Unit/Services/Result/Rubber_Result_Manager_Test.php` | 2 | 1 | Fix stub/mock configuration |
| `tests/Integration/Services/Admin/Championship_Admin_Integration_Test.php` | 2 | 2 | Add explicit `expects()` |
| `tests/Integration/Services/Fixture/Fixture_Maintenance_Service_Integration_Test.php` | 2 | 2 | Add explicit `expects()` |
| `tests/Unit/Controllers/Admin/Admin_Tournament_Delivery_Test.php` | 1 | 1 | Fix stub/mock configuration |
| `tests/Unit/Controllers/Admin/Tournament_Draw_Admin_Controller_Test.php` | 1 | 1 | Fix stub/mock configuration |
| `tests/Unit/Controllers/Admin/Tournament_Event_Config_Admin_Controller_Test.php` | 1 | 1 | Fix stub/mock configuration |
| `tests/Unit/Controllers/Admin/Tournament_Fixtures_Admin_Controller_Test.php` | 1 | 1 | Fix stub/mock configuration |
| `tests/Unit/Controllers/Admin/Tournament_Plan_Admin_Controller_Test.php` | 1 | 1 | Fix stub/mock configuration |
| `tests/Unit/Presenters/Notification_Presenter_Test.php` | 1 | 1 | Fix stub/mock configuration |
| `tests/Unit/Services/Admin/Competition/Competition_Action_Dispatcher_Test.php` | 1 | 1 | Fix stub/mock configuration |
| `tests/Unit/Services/Admin/League/League_Schedule_Service_Test.php` | 1 | 1 | Fix stub/mock configuration |
| `tests/Unit/Services/Export/Export_Service_Test.php` | 1 | 1 | Fix stub/mock configuration |
| `tests/Unit/Services/Player_Service_Validation_Test.php` | 1 | 1 | Fix stub/mock configuration |
| `tests/Unit/Services/Validator/Player_Validation_Service_Test.php` | 1 | 1 | Fix stub/mock configuration |

---

## 3. Mock Hygiene & Special APIs Inventory

### 3.1 `#[AllowMockObjectsWithoutExpectations]`
- **Occurrences:** 139 attribute declarations across 96 test files.
- **Diagnosis:** Added historically to suppress PHPUnit notices when `$this->createMock()` was used without configuring expectations.
- **Decision:** **Keep for initial Step 2/3 upgrade** (PHPUnit 13 retains attribute compatibility). During Step 4, incrementally convert pure value providers to `$this->createStub()` to resolve root mock hygiene debt.

### 3.2 Process Isolation (`#[RunInSeparateProcess]`)
- **Occurrences:** 1 test (`tests/Unit/Repositories/Fixture_Repository_Slug_Criteria_Test.php:54-55`) with `#[PreserveGlobalState(false)]`.
- **Diagnosis:** Isolates custom global constants/functions for repository slug criteria testing.
- **Decision:** **Keep**. Fully supported in PHPUnit 13 and completes in < 20ms without side effects.

### 3.3 Exception Assertions (`expectExceptionMessage()`)
- **Occurrences:** 11 invocations across 8 files:
  - `tests/Unit/Util/Php_View_Renderer_Test.php:60`
  - `tests/Unit/Infrastructure/Admin/Wp_Action_Guard_Test.php:51`
  - `tests/Unit/Infrastructure/Gateways/Payment/Stripe_Payment_Gateway_Test.php:27, 141, 162`
  - `tests/Unit/Controllers/Admin/Tournament_Draw_Admin_Controller_Test.php:343`
  - `tests/Unit/Controllers/Admin/Tournament_Teams_Admin_Controller_Test.php:113`
  - `tests/Unit/Domain/DTO/Fixture/Fixture_Hydration_DTO_Test.php:124`
  - `tests/Unit/Services/Player_Query_Service_Test.php:277, 292`
  - `tests/Unit/Services/Finance_Service_Test.php:257`
- **Decision:** **Keep**. `expectExceptionMessage()` behaves identically in PHPUnit 13 with zero warnings.

### 3.4 Output Buffer Expectations (`expectOutputString()` / `expectOutputRegex()`)
- **Occurrences:** 15 `expectOutputString()` across 5 files + 1 `expectOutputRegex()` in `Admin_Finances_Delivery_Test.php:148`.
- **Decision:** **Keep**. Standard, valid assertions for AJAX endpoints and controller delivery methods echoing responses.

### 3.5 Legacy Docblock Annotations
- **Occurrences:** 1 legacy `@dataProvider` docblock tag in `tests/Unit/Infrastructure/Wordpress/Shortcodes/Event_Shortcode_Handlers_Test.php:494`.
- **Diagnosis:** The method already declares the native attribute `#[DataProvider('age_limit_provider')]`.
- **Decision:** Remove the redundant docblock line during Step 4.

### 3.6 Uncaptured Stdout Output
- **Diagnosis:** During test execution, certain AJAX adapters and webhook listeners write directly to stdout (e.g. `{"msg":"saved"}`, `<select></select>`, `customer.created`).
- **Decision:** Suppress/buffer these uncaptured emissions during Step 4 to ensure clean test runner reports.

---

## 4. Execution Performance & Parallelization Analysis

- **Sequential Execution:** Total suite (1,626 tests / 7,769 assertions) runs in **~0.68s – 0.81s** sequential time.
- **Parallelization (ParaTest):** Orchestrating separate worker processes would introduce process startup overhead exceeding current execution time. Sequential execution is optimal and satisfies performance requirements (< 1.0s).

---

## 5. Migration Roadmap Status & Quality Gates Validation

1. **Step 1 (Audit & Deprecation Baseline):** Completed. Baseline established (1,626 tests, 7,667 assertions).
2. **Step 2 (Configuration):** Completed. `phpunit.xml.dist` migrated to schema `https://schema.phpunit.de/13.4/phpunit.xsd`.
3. **Step 3 (Composer Update):** Completed. `"phpunit/phpunit": "^13.4"` installed (PHPUnit 13.4.2 resolved with all dependencies).
4. **Step 4 (Test Refactoring & Cleanup):** Completed. All 100 `with*() without expects()` deprecations resolved across 33 files; docblock cleaned; output buffer noise silenced. Suite passes with 0 issues: `OK (1626 tests, 7769 assertions)`.
5. **Step 5 (Static Analysis & Quality Gates Validation):**
   - **Performance Benchmark:** Sequential execution consistently runs in **0.68s – 0.81s** (average ~0.72s) with **~84.50 MB** peak memory usage on PHP 8.4.25.
   - **Quality Gate Assertions:** PHPUnit 13 runs with `--display-all-issues --fail-on-all-issues` and passes with 0 deprecations, 0 notices, 0 warnings, and 0 errors.
   - **JavaScript Quality Gate:** Jest test suite (`npm test`) passes 10/10 tests across 4 suites in ~0.68s.
   - **What was actually verified locally:**
     - JUnit XML only: `--log-junit` produces a valid report for 1,626 tests / 7,769 assertions in ~0.8s.
     - YAML syntax of `ci.yml`, `sonarcloud.yml`, and `qodana.yaml` via Ruby's YAML loader.
     - **Not verified, locally or in GitHub Actions:** SonarCloud analysis, Clover coverage generation/ingestion, and Qodana. This environment has no PCOV/Xdebug, no `sonar-scanner`, no Qodana CLI, and no Docker; GitHub Actions was not exercised in this migration.
   - **CI/CD Static Analysis & Coverage Wiring (configuration only, not executed):**
     - `sonar-project.properties`: `sonar.organization=brunswickcomputing`, `sonar.projectKey=brunswickcomputing_racketmanager`, `sonar.sources=src/php`, `sonar.tests=tests`, `sonar.php.tests.reportPath=build/logs/junit.xml`, `sonar.php.coverage.reportPaths=build/logs/clover.xml`.
     - `.github/workflows/sonarcloud.yml`: Sole Sonar workflow. Same job: checkout (`fetch-depth: 0`), PHP 8.4 with `coverage: pcov`, Composer install, PHPUnit JUnit+Clover, then `SonarSource/sonarqube-scan-action@v8.3.0`.
     - Scan gating: `SONAR_TOKEN` is set at **job-level** `env` from `secrets.SONAR_TOKEN`, and the scan step uses `if: ${{ env.SONAR_TOKEN != '' }}` (a step's own `env` is not visible to its `if`). Fork PRs get empty secrets, so the scan is skipped without failing the job.
     - Prerequisites (must exist in GitHub/SonarCloud; not created here): repository secret `SONAR_TOKEN`; SonarCloud organization `brunswickcomputing` and project `brunswickcomputing_racketmanager`; SonarCloud Automatic Analysis disabled (Administration > Analysis Method) so CI analysis is used.
     - Removed redundant `.github/workflows/sonarqube.yml`.
     - `.github/workflows/ci.yml`: `phpunit` job runs the suite once with `--display-all-issues --fail-on-all-issues` and `coverage: none`. Qodana PHP/JS jobs remain commented out.
     - `qodana.yaml`: `profile: qodana.starter`, `php: version: 8.4`, linter `jetbrains/qodana-php:2025.3`. No Qodana run has been executed.
