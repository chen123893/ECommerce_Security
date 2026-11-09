# Member 5: security logging, monitoring, privacy and user guidance

This document covers report sections 3.11 and 3.12. Test results are in the section "Verified results".

## Existing controls found before the change

| Area | Found in the code | Gap |
|---|---|---|
| Login records | `login_attempts`, written by `record_login_attempt()` in `includes/security.php` (Member 1) | Built for rate limiting. A successful login deletes the failed rows, and rows are pruned after 48 hours. It holds no event type, user ID or context. |
| Debug logging | `error_log()` calls in product and checkout files (Member 3) | Records server faults, not security events. |
| Authorization | `require_admin()` and `require_admin_role()` in `admin/includes/auth.php` | Refused requests leave no record. |
| Administrator visibility | `admin/pages/security/mfa.php` only | No screen shows security events. |
| Privacy and guidance | None | No privacy notice or account security page. |

`login_attempts` is unchanged. The audit log is a new table with a different purpose.

## 3.11.1 Security audit log

**Weakness.** Failed logins disappeared after a successful login, and denied access, password resets and administrative changes were not recorded.

**Change.** `includes/security_audit.php` adds `security_log_event()` and the `security_audit_log` table. Calls were added to the existing authentication and authorization paths. No existing check was replaced.

| Design decision | Reason |
|---|---|
| Separate database connection | `auth.php` runs before a page connects, `config/database.php` cannot be included twice, and an event must survive a page's transaction rollback. |
| Fixed event catalogue | Unknown event types are refused, so dashboard filters match stored values. |
| `REMOTE_ADDR` only | `X-Forwarded-For` is set by the client and could forge the record. |
| Path without query string | Reset links carry their token in the query string. |
| Masked email and keyed hash | Detection groups by account without storing the full address. |
| Failure is caught | A logging fault does not stop the user's request. |

**Files.** New: `includes/security_audit.php`, `includes/security_audit_config.php`, `database/migration_security_audit.sql`.
Hooks: `actions/auth/{login,logout,forgot-password,reset-password,signup}.php`, `actions/profile/change-password.php`, `actions/orders/cancel.php`, `actions/checkout/place-order.php`, `admin/login.php`, `admin/logout.php`, `admin/verify-2fa.php`, `admin/includes/auth.php`, `admin/pages/security/mfa.php`, `admin/pages/products/{create,edit,delete}.php`, `admin/pages/users/{create,edit,delete}.php`, `admin/pages/orders/{edit,delete}.php`, `admin/pages/clients/delete.php`.

**For Member 2.** Call `security_log_access_denied('reason')` at any new refusal, such as an order that belongs to another customer. `admin/includes/auth.php` already loads the function. When merging that file, keep the `require_once` line and the three `security_log_access_denied()` calls.

## 3.11.2 Administrator security dashboard

**Weakness.** Administrators had no view of security activity.

**Change.** `admin/pages/security/dashboard.php`, linked as **Security Monitor** in the sidebar, shows open alerts, 24-hour counts, recent failed logins, recent denied requests and a paged event table with filters for event type, outcome and date. Access uses `require_admin_role()`. Filters are checked against allow-lists and queries are prepared. Output uses `htmlspecialchars()`. Email and network addresses are masked.

## 3.11.3 Suspicious activity detection

**Weakness.** Member 1's limiter blocks requests but tells nobody.

**Change.** Rules are evaluated when a matching event is written. Settings are in `includes/security_audit_config.php`.

| Rule | Events counted | Grouped by | Threshold | Possible false positive |
|---|---|---|---|---|
| Repeated failed logins on one account | Failed or blocked login and MFA | Account hash | 5 in 15 minutes | A customer who has forgotten their password |
| Repeated failed logins from one address | Failed or blocked login and MFA | Network address | 10 in 15 minutes | A shared campus network |
| Repeated denied requests | Access denied, CSRF rejected | User, or address for guests | 5 in 10 minutes | Old bookmarks or an expired session with several tabs |
| Unusual administrative deletions | Product, user, order, client deletion | User | 10 in 5 minutes | A planned catalogue clean-up |

The account threshold matches Member 1's lockout of 5 failures in 15 minutes. A unique `open_key` allows one open alert per rule and subject. Later events update its count. Events from before an acknowledgement do not count again.

## 3.12.1 Privacy and sensitive data handling

**Weakness.** The application had no privacy information.

**Change.** `pages/privacy/index.php` explains what is collected, why, who can see it and for how long. `security_sanitize_context()` drops sensitive keys and redacts values that look like hashes, tokens or card numbers. A value typed into the email box that is not an email address is stored only as a placeholder and a keyed hash.

**Retention recommendation.** Keep events for 90 days, long enough to investigate an incident reported a few weeks later. `security_audit_purge()` deletes older events and acknowledged alerts. It runs on about 1% of logged events. A production system should run it on a schedule. The period is a project decision and has not been checked against any legal requirement.

## 3.12.2 User security guidance

**Weakness.** Users received no account security advice.

**Change.** `pages/security/index.php` covers passwords, MFA, phishing and suspicious activity. Both new pages are linked from the site footer and use the storefront header, footer and colours.

## Testing on XAMPP or MAMP

1. Check out `member5-security-logging` into `htdocs/E-Commerce-Management-System`.
2. Import `database/migration_security_audit.sql` in phpMyAdmin. The tables are also created on first use.
3. Optional: add `AUDIT_HASH_KEY=<long random value>` to `.env`. Without it a built-in development key is used.
4. Sign in to the admin portal as an Admin and open **Security Monitor**.

| Test | Steps | Expected result |
|---|---|---|
| M5-01 | Sign in at `/pages/auth/login.php` | Login succeeded event |
| M5-02 | Sign in with a wrong password | Login failed event |
| M5-03 | Signed in as a customer, open `/admin/pages/users/index.php` | Redirect, Access denied event |
| M5-04 | As Admin, open Security Monitor | Events displayed |
| M5-05 | As Staff, open `/admin/pages/security/dashboard.php` | 403 page |
| M5-06 | Enter a wrong password 5 times for one account | Warning under Suspicious activity |
| M5-07 | In phpMyAdmin, search `security_audit_log` for a test password | No match |
| M5-08 | Filter by Login failed, then by outcome and date | Only matching rows |

To repeat M5-06 for the same account, run `DELETE FROM login_attempts;` to clear Member 1's lockout.

## Verified results

Tests ran on 9 November 2026 in Docker with PHP 8.2 on Apache over HTTPS and MariaDB 10.11, using a disposable database. They have not been run on XAMPP or MAMP. Raw output is in `docs/member5-evidence/`.

Before the change, on commit `d5e7e7a` (`baseline-results.txt`): no audit or alert table existed, 3 failed login rows fell to 0 after a successful login, a customer opening an admin page left no record, and the dashboard, privacy and guidance URLs returned 404.

After the change (`e2e-results.txt`): 52 of 52 scripted checks passed. Chrome was used to confirm the dashboard, filters, Staff 403 page, privacy notice and account security page.

| Test | Observed result |
|---|---|
| M5-01 | Customer and administrator logins recorded with user ID, role and masked email |
| M5-02 | `login_failure` recorded with no user ID and a masked email |
| M5-03 | Customer and guest redirected, Staff received 403, and each request was recorded |
| M5-04 | Dashboard returned 200 and displayed events |
| M5-05 | Staff received 403; customer and guest were redirected to the admin login |
| M5-06 | No alert after 4 failures, one alert on the 5th, and still one alert after 3 more attempts |
| M5-07 | 18 test values, including passwords, a real reset token and a session ID, were not found in either audit table |
| M5-08 | Event type, outcome and date filters returned only matching rows. Invalid values were ignored without an SQL error |

Other checks: the other three rules raised alerts at their thresholds, a forged `X-Forwarded-For` was not stored, stored markup was escaped, a 91-day-old event was purged while an 89-day-old event was kept, and login still worked when the audit table could not be written.

## Screenshots for the report

| Chapter | Screenshot |
|---|---|
| 2.1.5 | Table list with no audit table; `login_attempts` before and after a successful login |
| 3.11.1 | `security_audit_log` structure and stored rows |
| 3.11.2 | Security Monitor with filters applied |
| 3.11.3 | Suspicious activity warning |
| 3.12.1 | Privacy notice |
| 3.12.2 | Account security page |
| 4.2.5 | M5-05 403 page and the M5-06 warning |

## Remaining limitations

- The application connects as one database account, so SQL injection elsewhere could alter the log. Setting `AUDIT_DB_USER` to a restricted account narrows this. It has not been tested with one.
- Alerts appear only on the dashboard. No email or message is sent.
- `AUDIT_HASH_KEY` falls back to a development key when unset.
- If the database is down, events are lost and one line goes to the PHP error log.
- Masked addresses limit what an administrator can see. The full address is in the database for investigation.
