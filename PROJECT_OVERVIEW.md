# Daily Closing Web System — Project Overview

## 1. Application summary
- The application is a PHP/MySQL intranet used to capture and verify daily closing reports across several business units, branded in configuration as the “Daily Closing Web System.”【F:config/.env.php†L12-L33】
- Users authenticate through a shared login screen and are routed to dashboards tailored for managers, accounting staff, executives (CEO), and administrators, reflecting the multi-role workflow baked into the system.【F:auth/login.php†L7-L44】【F:views/manager/dashboard.php†L6-L180】【F:views/account/dashboard.php†L6-L188】【F:views/ceo/dashboard.php†L6-L180】【F:views/admin/dashboard.php†L6-L180】

## 2. Configuration and bootstrap
- All requests include `includes/init.php`, which defines the boot sequence: load environment constants, configure PHP error handling based on the `APP_ENV` flag, harden session cookies, and load shared helpers for database access, CSRF protection, and authentication.【F:includes/init.php†L1-L54】
- Environment variables (database host/name, application metadata, session lifetime, timezone, and CSRF token policy) live in `config/.env.php`; the file is protected by an `APP_INIT` guard to discourage direct access and should be kept outside source control in production deployments.【F:config/.env.php†L1-L33】

## 3. Core infrastructure services
- `includes/db.php` centralizes PDO access, exposing helpers to open a connection, run prepared statements, and fetch single or multiple rows with consistent error logging and environment-aware diagnostics.【F:includes/db.php†L1-L103】
- `includes/csrf.php` issues expiring tokens, validates submissions, and provides a helper for embedding hidden CSRF fields in HTML forms; tokens are one-time use and stored in the session.【F:includes/csrf.php†L1-L80】
- Authentication helpers in `includes/auth.php` provide login, logout, session checks, role-based redirects, and current-user retrieval. Passwords are verified with Argon2id and transparently rehashed when algorithms change.【F:includes/auth.php†L1-L192】

## 4. Entry points and routing
- `index.php` is the default entry point, redirecting authenticated users to the dashboard for their role or sending unauthenticated visitors to the login page.【F:index.php†L1-L18】
- The login page (`auth/login.php`) performs CSRF validation, submits credentials to `authLogin`, and shows development test accounts for each role (all with the shared password `Password!234`).【F:auth/login.php†L19-L267】
- Logout simply boots users back to the login screen via `auth/logout.php` after destroying the session (handled in `authLogout`).【F:auth/logout.php†L1-L15】【F:includes/auth.php†L92-L115】

## 5. Role-focused dashboards
- Manager dashboards highlight quick links for submitting daily expenses, forwarding batches to HQ, and reviewing history, while also surfacing user metadata from the session.【F:views/manager/dashboard.php†L6-L180】
- Accounting dashboards emphasize verification tasks and provide navigation to submission review tooling.【F:views/account/dashboard.php†L6-L188】
- CEO and admin dashboards provide executive/reporting and governance links respectively, using the same session-driven layout pattern.【F:views/ceo/dashboard.php†L6-L180】【F:views/admin/dashboard.php†L6-L180】

## 6. Manager submission pipeline
- `includes/submission_handler.php` encapsulates the manager workflow for creating daily submissions: it validates outlet ownership, prevents duplicates, computes total income, stores an initial “draft,” and records summary expenses. Receipts can be uploaded (JPG/PNG/PDF up to 5 MB) and are stored under `uploads/receipts/` with unique filenames.【F:includes/submission_handler.php†L12-L323】
- The handler enforces at least one receipt when expenses are provided, tracks totals, persists files with permission checks, and updates net income after expense deductions.【F:includes/submission_handler.php†L95-L194】

## 7. Accountant verification and external data imports
- `views/account/verify_submission.php` aggregates pending submissions by manager/outlet, summing incomes per stream, hydrating related expenses, and preparing data structures for downstream verification flows.【F:views/account/verify_submission.php†L1-L132】
- Batch processors in `includes/account/save_berhad_external_sales_batch.php` allow accountants to import third-party sales data, reconcile it against manager-submitted numbers, and store per-row comparisons within a transaction scope.【F:includes/account/save_berhad_external_sales_batch.php†L1-L200】
- Complementary endpoints let accountants approve or reject uncategorized expenses via JSON APIs; both enforce CSRF, verify submission status, and update audit metadata (though they rely on an undefined `dbExecute` helper, noted below).【F:includes/account/approve_expenses.php†L1-L70】【F:includes/account/reject_expenses.php†L1-L98】

## 8. Data model overview
- Core tables include `users` (role-based accounts), `outlets` tied to managers, `daily_submissions` capturing income/expense totals with workflow status, and `expenses` storing line items linked to categories.【F:database/001_create_users.sql†L5-L25】【F:database/002_create_manager_tables.sql†L8-L87】
- The schema enforces foreign keys and indexes for common access patterns (by outlet, manager, submission date, and status).【F:database/002_create_manager_tables.sql†L32-L88】
- Seed data (`database/010_seed_initial_users.sql`) populates sample users for each role; hashes are intentionally basic because they are upgraded to Argon2id upon first login.【F:database/010_seed_initial_users.sql†L1-L51】

## 9. Security and compliance considerations
- Sessions adopt HTTP-only, same-site cookies, and rotate identifiers every 30 minutes, while production mode further enforces secure cookies and routes PHP errors to log files.【F:includes/init.php†L16-L48】
- File uploads are validated for MIME type, size, and extension before being persisted, and errors during storage roll back the surrounding database transaction to maintain consistency.【F:includes/submission_handler.php†L111-L194】【F:includes/submission_handler.php†L221-L323】

## 10. Known gaps and development notes
- Several accountant actions call an undefined `dbExecute` helper; only `dbQuery`, `dbFetchOne`, and `dbFetchAll` exist in `includes/db.php`, so inserts/updates should either extend that module or replace `dbExecute` with `dbQuery`/`dbFetchOne` equivalents.【F:includes/account/approve_expenses.php†L43-L60】【F:includes/account/reject_expenses.php†L50-L78】【F:includes/db.php†L52-L103】
- Placeholder files such as `includes/config.php`, `includes/db_connect.php`, and `login.php` at the project root suggest legacy scaffolding or planned refactors; ensure only one configuration path is maintained to avoid divergence.【F:includes/config.php†L1-L1】【F:includes/db_connect.php†L1-L1】【F:login.php†L1-L1】
- Development artifacts (debug/test scripts and diary notes) live alongside production code (e.g., `PROJECT_DIARY.md`, `debug_submission.php`), so review deployment pipelines to prevent exposing internal diagnostics in production.

