### CI/CD for RacketManager (2025-10-23)

This document describes the CI and CD pipelines configured via GitHub Actions.

---

### Continuous Integration (CI)

Workflow: .github/workflows/ci.yml

Triggers:
- push to: main, master, develop, feature/**
- pull_request to: main, master, develop

Jobs:
- Qodana PHP (Static Analysis)
  - Linter: jetbrains/qodana-php:2024.3
  - Config: qodana.yaml
  - Artifacts: full HTML report and SARIF uploaded to GitHub Security tab
- Qodana JS (ESLint-backed)
  - Linter: jetbrains/qodana-js:2024.3
  - Config: qodana-js.yaml
  - Artifacts: full HTML report

Notes:
- SARIF output is uploaded for code scanning alerts where supported.
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
- Open a PR – both Qodana jobs run and attach reports as artifacts. Review any issues in the PR checks or the Security tab.

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
