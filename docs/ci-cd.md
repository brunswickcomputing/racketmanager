### CI/CD for RacketManager (2025-10-23)

This document describes the CI and CD pipelines configured via GitHub Actions.

---

### Continuous Integration (CI)

Workflow: .github/workflows/ci.yml

Triggers:
- push to: main, master, develop, feature/**
- pull_request to: main, master, develop

Jobs:
- Composer Autoload Smoke Test (Plugin)
- PHPUnit (Tests)
  - Full suite with `--display-all-issues --fail-on-all-issues`
  - Coverage disabled (`coverage: none`)
- Jest (JavaScript Tests)
- Qodana PHP (Static Analysis) — commented out in `ci.yml`; does not run
  - Linter (if re-enabled): jetbrains/qodana-php:2025.3
  - Config: qodana.yaml
- Qodana JS (ESLint-backed) — commented out in `ci.yml`; does not run
  - Config: qodana-js.yaml
- PHPCS (WordPress Standards) — commented out in `ci.yml`; does not run
  - Non-blocking style/lint check with WordPressCS via phpcs.xml.dist

Notes:
- If Qodana jobs are re-enabled, SARIF output is uploaded for code scanning alerts where supported.
- Results are published even if the job fails to ease troubleshooting.

---

### Continuous Delivery (CD)

Workflow: .github/workflows/release.yml

Triggers:
- push tag matching v* (e.g., v1.2.3)
- manual run via workflow_dispatch

Steps:
- Builds a WordPress‑ready plugin zip for wp-content/plugins/racketmanager
- Excludes dev content (.github, docs, qodana configs, etc.)
- Generates a SHA256 checksum
- Creates a GitHub Release and uploads the zip + checksum assets

Notes:
- Relies on GitHub Actions' built‑in GITHUB_TOKEN (no extra secrets required)
- WordPress.org SVN deploy is not included; can be added later if needed

---

### How to use

CI:
- Open a PR – Composer smoke, PHPUnit, and Jest jobs run. Qodana and PHPCS jobs are commented out in `ci.yml` and do not run.

CD:
- Create a version tag locally, push it to GitHub:
  - git tag v1.2.3
  - git push origin v1.2.3
- A new Release will be created automatically with the built zip and checksum.

---

### Maintenance
- Periodically update Qodana action versions and linter tags.
- Adjust excludes in release packaging as the repo layout evolves.
- If desired, add a WordPress.org deploy job gated by secrets for SVN creds.
