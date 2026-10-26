---
sessionId: session-261026-115608-1lda
---

# Requirements

### Overview & Goals

Bring `wp-content/plugins/racketmanager/docs/ddd-architecture-audit-2026-10.md` up to date with the position after commit `794f564` (2026-10-26), which restored the 9.7.0 competition scheduler, removed the pre-9.7.0 `League_Schedule_Service` flow and its tracked debt, and added the schedule-page report / `MT`/`WT`/`XT`/`OT` labels.

The previous step already annotated L7, P2 item 6, §9.1 and two A.1 rows, but the rest of the document still reports the audit-time (2026-10-25) figures and §9.1 / P2-6 refer to "working tree on top of `7bbf7fe`" rather than the now-landed commit. The document must read consistently: headline figures, the layer matrix, the L5/L6 inventories, the backlog and the appendix must all agree with `tests/Architecture/baseline.json` at `794f564`.

### Scope

**In scope** — documentation only, one file: `docs/ddd-architecture-audit-2026-10.md`.
- Header / status block: date, tree audited, test count.
- §1 executive summary debt table.
- §2.2 size table note (new `Domain/Competition/Scheduling/` files, `Services/Competition/Competition_Schedule_Service.php`).
- §3 layer matrix Services row (`$wpdb`, `global`, legacy helper calls).
- L5, L6 (helper table rows and totals), L7 wording.
- §8 reconciliation row for this update.
- §9: P2 item 6 and 7 counts, §9.1 wording (commit hash instead of "working tree"), P2 status note.
- Appendix A.1 rows (`all.no_legacy_helper_calls`, timing), A.3 unchanged.

**Out of scope**
- Any source, test or baseline change (the baseline is already canonical at `794f564`).
- Re-measuring regex **[M]** figures that did not move (Admin superglobals, templates, JS).
- Rewriting the historical audit-time figures — they stay, with "since 2026-10-26" annotations, matching the convention already used for P1.

### Functional Requirements
1. Every figure that changed between `7bbf7fe` and `794f564` is updated with the measured value and tagged **[A]** (baseline) or **[M]** (re-measured), keeping the audit-time figure visible as "was …".
2. All references to "working tree on top of `7bbf7fe`" are replaced with commit `794f564` (2026-10-26).
3. P2 backlog item 6 shows which services remain (6 files, 58 refs) and item 7 shows the new helper total (243 / 64).
4. The document stays internally consistent with `tests/Architecture/baseline.json` (`services.no_wpdb` 58 / 6, `services.no_global_racketmanager` 34 / 17, `all.no_legacy_helper_calls` 243 / 64; all other rules unchanged).

# Technical Design

### Current Implementation

`docs/ddd-architecture-audit-2026-10.md` (439 lines) — relevant stale spots found by reading the file against the measured values:

| Location | Currently says | Measured at `794f564` |
|---|---|---|
| Header lines 3–7 | Date 2026-10-25; tree `b4713b1`; `src/php/` 849 files | last update 2026-10-26, commit `794f564`; `src/php/` **863** files (`Domain/` 136, `Services/` 138, `Infrastructure/` 85, others unchanged) |
| §2.1 line 79 | 4,068 tests | **4,438** tests (`--list-tests`); full suite ≈ 6–7 s, Architecture ≈ 3.2 s (185 tests, 710 assertions) |
| §1 table line 42 | helper calls 257 / 65 | **243 / 64 [A]** |
| §1 table line 43 | `$wpdb` outside `Repositories/` 147 / 11 (7 in `Services/`) | regex **103 / 11 [M]** (one is a docblock mention in `Domain/Shared/Typed_Property_Hydrator.php`; AST Domain is 0); `Services/` **58 / 6 [A]** |
| §2.2 line 103 | `Domain/` 130, `Infrastructure/` 85 | `Domain/` **136** (+6 `Competition/Scheduling/`), `Services/` **138** (+`Competition/Competition_Schedule_Service.php`), `Infrastructure/` 85 |
| §3 Services row line 117 | `$wpdb` 103 / 7; `global` 23 / 11; helpers 69 / 23 (`League_Schedule_Service` 14) | `$wpdb` **58 / 6**; `global` **25 hits / 11 files [A]** inside `Services/` (`Login` 6, `Options_Action_Dispatcher` 4 `$GLOBALS`, `Competition_Notification_Service` 4, `Standings_Service` 3, …); helpers **55 / 22** (`Validator` 8 now the top caller) |
| L5 line 165 | 33 hits / 18 files, 23 in 11 `Services/` files | rule baseline **34 / 17 [A]** (was 36 / 18); `Services/` share 25 / 11 |
| L6 lines 170–186 | 257 / 65; `get_league` 48 (`League_Schedule_Service` 5), `get_event` 20 (`LSS` 3), `get_team` 15 (`LSS` 4), `get_league_query_service` 26, `get_event_query_service` 7 (`LSS` ×2) | **243 / 64**; `get_league` **43**, `get_event` **17**, `get_team` **11**, `get_league_query_service` **25**, `get_event_query_service` **6**; `League_Schedule_Service` removed from "Top callers"; accessor subtotal 33 → **31**, other helpers 224 → **212** |
| L7 line 197 | "Update 2026-10-26 … restored" | add commit `794f564`; regex now 103 / 11 (Services 58 / 6) |
| §8 table | no row for this update | add a row: "P2-6 `League_Schedule_Service` figures (45 `$wpdb`, 14 helpers, 2 globals)" → removed at `794f564` |
| P2 item 6 line 322 | "working tree on top of `7bbf7fe`" | commit `794f564` |
| P2 item 7 line 323 | 257 calls (33 accessors + 224 others) | **243** (31 + 212) |
| §9.1 line 329–336 | fine, but add commit `794f564`, scheduling file list, test additions (`Competition_Scheduler_Test`, `Competition_Schedule_Service_Test`, presenter/template/delivery tests) and the `deliver_league_action_result()` `flash_data` fix |
| A.1 line 374 | `all.no_legacy_helper_calls` 257 / 65 | add "**243 / 64 since 2026-10-26** (`League_Schedule_Service` −14)" |
| A.1 intro line 353 | ≈2.7 s | ≈3.2 s, 185 tests |

### Key Decisions
- **Annotate, don't rewrite history.** Keep the audit-time numbers and append "since 2026-10-26 (`794f564`)" deltas, exactly as P1 did — the document's legend (§Legend) relies on it.
- **Cite the commit, not the working tree.** `794f564` is the landed commit; every "working tree on top of `7bbf7fe`" is replaced.
- **Prefer **[A]** baseline figures** for the matrix/L5/L6/A.1 rows; use **[M]** regex only for the §1/L7 "outside `Repositories/`" headline where the doc has always used the regex (noting the one docblock hit in `Typed_Property_Hydrator`).
- **Add a P2 status note** mirroring the P1 blockquote: "Status 2026-10-26: P2 item 6 — `League_Schedule_Service` part done (`794f564`); remaining `services.no_wpdb` 58 / 6."

### Proposed Changes
Single file edit of `docs/ddd-architecture-audit-2026-10.md` per the table above; no code, tests or baseline change. Verification is a re-run of the counting one-liners (Appendix B + a `php -r` sum over `baseline.json`) and `vendor/bin/phpunit --testsuite Architecture` to confirm the baseline is still canonical (nothing should change).

# Delivery Steps

### Step 1: Refresh header, executive summary, size table, layer matrix and L5–L7 inventories
Sections 1–4 of the audit report state the `794f564` figures with the audit-time values kept as "was …".

- Header: add `**Last updated:** 2026-10-26 (commit `794f564`)`, bump the tree line, `src/php/` 863 files; §2.1 test count 4,438 and suite timings.
- §1 debt table: helper calls 243 / 64 **[A]**; `$wpdb` outside `Repositories/` 103 / 11 **[M]** (58 / 6 in `Services/` **[A]**, docblock hit noted).
- §2.2: `Domain/` 136 (`Competition/Scheduling/` ×6), `Services/` 138.
- §3 Services row: `$wpdb` 58 / 6, `global` 25 / 11, helpers 55 / 22 (`Validator` 8 top).
- L5: 34 / 17 **[A]** since 2026-10-26; L6: totals 243 / 64, accessor 31 / other 212, per-helper rows (`get_league` 43, `get_event` 17, `get_team` 11, `get_league_query_service` 25, `get_event_query_service` 6) and drop `League_Schedule_Service` from "Top callers"; L7: cite `794f564`, regex 103 / 11.
- Re-run the Appendix B one-liners and a `baseline.json` sum to confirm every number written.

### Step 2: Refresh backlog, §9.1, reconciliation and Appendix A for the landed commit
Sections 8, 9 and Appendix A reference commit `794f564` and the updated rule baselines.

- P2: add a status blockquote (item 6 `League_Schedule_Service` part done at `794f564`; remaining 58 / 6 in `Player_Query_Service` 34, `Team_Service` 11, `Competition_Entry_Service` 4, `Tournament_Service` 4, `Player_Service_Provider` 3, `Upgrade` 2); replace "working tree on top of `7bbf7fe`" in item 6; item 7 → 243 (31 + 212).
- §9.1: cite `794f564`, list the new files (`Domain/Competition/Scheduling/{Competition_Scheduler,Schedule_Request_DTO,Schedule_Division_DTO,Schedule_Team_DTO,Scheduled_Match_DTO,Schedule_Result_DTO}.php`, `Services/Competition/Competition_Schedule_Service.php`, `League_Team_Repository::find_teams_missing_match_day()`), the `Admin_Display::deliver_league_action_result()` `flash_data` forwarding fix, and the new tests.
- §8: add a reconciliation row for the `League_Schedule_Service` figures removed at `794f564`.
- A.1: `all.no_legacy_helper_calls` row gains "243 / 64 since 2026-10-26"; intro timing ≈3.2 s; `services.*` rows cite the commit.
- Run `vendor/bin/phpunit --testsuite Architecture` (≈3 s) to confirm the baseline is untouched and canonical; proof-read the doc for any remaining `7bbf7fe` / "working tree" wording.
