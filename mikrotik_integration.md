# MikroTik Integration: Analysis Report

**Status:** analysis only. No application code has been changed.
**Reviewed:** `telcosphere_api` (`development` @ `96a5825`), `telcosphere_ui` (@ `53fb6e5`), the local database (aggregate counts only), and the MikroTik documentation.
**Date:** 2026-11-09

**How to read this report**

- **Verified** means I confirmed it in your code, your local database, or MikroTik's documentation.
- **Proposed** means a design recommendation. None of it is built.
- **Needs lab test** means it could not be proven without a real router. No router was reachable during this review.

---

## 1. Summary

**Yes, this can be fully integrated, and your data model is a good starting point.** You already have per-tenant `site_id`, PPPoE credentials on the client, billing categories with disconnection-day settings, and per-bill `disconnection_date` values.

**But none of it talks to a router yet.** There is no RouterOS client library and no code that opens a connection. The MikroTik module is a settings screen whose save does not actually save.

| Your requirement | State today | What stops it |
|---|---|---|
| Find unpaid clients whose disconnection date has passed | Data exists for manually created bills | Automated bills have no disconnection date (B6). The client-level date is unreliable (B7). |
| Automatically disconnect them | Not built | No router client (B3), no client-to-router link (B4), no sweep job, scheduler not confirmed running (B9) |
| New subscriber is added to MikroTik | Not built | Same, plus router settings cannot be saved (B1, B2) |
| Reconnect after payment (**not in your list, but required**) | Not built | Without it, a subscriber who pays stays offline until staff fix it by hand |

**The hardest problem is network reachability, not code.** Your SaaS has to reach each tenant's router, and many of those will be behind NAT or CGNAT (section 4).

---

## 2. How MikroTik integration works

A web app controls a MikroTik router by logging in to one of its management interfaces and issuing the same commands an admin would type.

| Interface | Port | Works on | Notes |
|---|---|---|---|
| **API** | TCP 8728 | v6 and v7 | Since v6.43 the password is sent **in plain text** at login, so never expose this on a public network |
| **API-SSL** | TCP 8729 | v6 and v7 | Encrypted version of the above |
| **REST** | HTTPS 443 at `/rest` | **v7.1 and later only** | Requires `www-ssl`. Commands have a 60-second timeout |

Your `mikrotiks` table already assumes the API (`port` default 8728, `use_ssl` flag), which is the right choice for a multi-tenant product because tenants may be on v6 or v7.

The three operations you need, for PPPoE subscribers:

| Action | RouterOS operation |
|---|---|
| **Add subscriber** | `/ppp/secret/add` with `name`, `password`, `service=pppoe`, `profile=<plan>`, `comment=<account no>` |
| **Disconnect** | `/ppp/secret/set` with `disabled=yes`, **then** find the session in `/ppp/active/print ?name=<user>` and `/ppp/active/remove` it |
| **Reconnect** | `/ppp/secret/set` with `disabled=no`. The subscriber's device redials on its own |

**Why the session must be removed as well.** From field experience, disabling a secret only blocks the *next* login, and a subscriber already online stays online. MikroTik's PPPoE documentation does not state this either way, so it is on the lab checklist in section 9. The design removes the session regardless, so it is correct whichever way the test goes.

Speed plans map to **PPP profiles** (`/ppp/profile`, using `rate-limit`). A plan change means setting a new profile on the secret and dropping the session so the new limit takes effect.

---

## 3. What your code does today

### Tenant and network data

```
sites (tenant)
 ├─ clients ── server_id ──────► servers (only: name, site_id)   ✗ no link to a router
 │    │  pppoe_username / pppoe_password
 │    └─ internet_plan_id ─────► internetplans (name, price)      ✗ no PPP profile name
 └─ mikrotiks (ip, port, ssl, username, password)                 ✗ nothing references this table
```

### Billing flow

1. Staff create a monthly bill in `createbilling.jsx`. The form calculates:
   `disconnection date = today + days_to_due_date + days_to_disconnection_date` (lines 167-173).
2. `BillingService::generateBilling` stores it in `billings.disconnection_date` and copies it to `clients.latest_disconnection_date` (`BillingService.php:65`, `:127`).
3. `PaymentController::store` updates bill and item balances, sets the status to `Partial` or `Paid`, and reduces the client balance.

Local data for reference: 1 site, 3 clients, 3 servers, **0 mikrotiks**, 1 of 3 clients with a PPPoE username, bill statuses `Paid` (1) and `Pending` (2).

The branch `features/mikrotik_integration` has **no commits beyond `development`**, so there is no hidden work there.

---

## 4. The key decision: how the SaaS reaches each router

Your local setup hides this problem, because XAMPP and your hAP are on the same LAN. In production each router is in a different building, often behind CGNAT.

| Option | How it works | Fits when | Drawback |
|---|---|---|---|
| **A. Public IP + API-SSL + firewall allowlist** | SaaS connects to the router's public IP on 8729, firewall allows only the SaaS IP | Tenant has a static public IP | Fails behind CGNAT. Exposes a management port |
| **B. WireGuard hub (recommended)** | Each router dials *out* to a hub, and the SaaS reaches it on a private tunnel IP | RouterOS v7 | Needs a VPS you control. Needs per-tenant isolation on the hub |
| **C. L2TP/IPsec or SSTP hub** | Same as B, for older routers | Tenants stuck on v6 | Slower and more setup |
| **D. RADIUS (User Manager or FreeRADIUS)** | Routers ask a central server to authenticate subscribers | Large ISPs, greenfield | Requires migrating every tenant's subscribers and still needs a path for disconnect commands |

**Recommendation (proposed): B, with A as a fallback and C for v6 tenants.** Outbound tunnels work through CGNAT, and the plain API inside the tunnel is already encrypted. `ip_address` then simply holds the tunnel IP, with no schema change.

- Give every router a unique tunnel IP, so tenants with identical LAN ranges cannot collide.
- Block router-to-router traffic on the hub, or one tenant could reach another's router.
- Check your production hosting: **shared hosting usually cannot run WireGuard**, so the hub may need a separate small VPS with routing to your app.

---

## 5. Blockers found in your code

All items here are **verified**.

| # | Problem | Evidence | Effect |
|---|---|---|---|
| **B1** | Save never saves. A leftover `return $data;` exits before `Mikrotik::create` | `MikrotikController.php:71` | Returns 200, so the UI reports success while nothing is stored. Explains 0 rows locally |
| **B2** | UI and API disagree | `MikrotikSettingsForm.jsx`: sends `host`, API expects `ipAddress`. Sends `connectionTimeout`, API expects `timeout`. `isEnabled` and `retryAttempts` are ignored. Loads a single object, but `index` returns a list. Save is always POST | Removing B1 alone gives a DB error (`ip_address` is required). A second save fails the unique-name rule |
| **B2a** | "Test connection" button calls a route that does not exist | `MikrotikSettingsForm.jsx:271`, no such route in `routes/api.php` | Button always fails |
| **B3** | No RouterOS client, and the PHP extension is off | `composer.json`. `ext-sockets` is commented out at `D:\xampp\php\php.ini:964`, and the usual library (`evilfreelancer/routeros-api-php`) requires it | Must be enabled locally and in production |
| **B4** | Nothing links a client to a router or a plan to a profile | `servers` has only `name`. `internetplans` has no profile column | System cannot know which router to call |
| **B5** | Automated billing cannot run from the scheduler | `BillingService.php:278` and `MonthlySubscription.php:35,111,120,129` use `auth()->user()->site_id`. Reproduced under a console bootstrap: `ErrorException`, caught at `RunAutomatedBilling.php:33` and logged as "No billing generated" | **Silently produces no bills.** Unpaid clients never appear |
| **B6** | Even if B5 is fixed, automated bills have no disconnection date | `BillingService.php:286-296` sends no `disconnectionDate` or `billingCutoff`. `days_to_disconnection_date` is only used in the UI | Auto-billed clients would **never** be disconnected. A null cutoff also defeats the duplicate-bill guard (`MonthlySubscription.php:41`) |
| **B7** | `clients.latest_disconnection_date` is not a safe trigger | `BillingService.php:127` overwrites it on every bill type. Only monthly bills require a date (`StoreBillingRequest.php:27`), and payments never clear it | A repair bill sets it to NULL and **saves a delinquent client**. A client who has paid still shows a past date and could be **wrongly cut off** |
| **B8** | No record of router state | No column or table for it | Cannot retry, audit, or show "suspended" |
| **B9** | No evidence the scheduler runs | `storage/logs/billing.log` does not exist locally. A production cron and a queue worker are both required | Please confirm for production |

### Related billing issues that affect correctness

| Issue | Evidence | Why it matters here |
|---|---|---|
| Timezone is UTC | `config('app.timezone')` returns `UTC` | `->daily()` fires at 8:00 AM Philippine time, and "today" is wrong for 8 hours each day |
| Cycle day 30 or 31 has no run in shorter months | `BillingService.php:277` compares to `date('d')`, and local data has cycle 30 | No February bills for those categories |
| Dashboard "for disconnection" widget is always empty | `DashboardService.php:232` filters statuses `overdue` and `due`, which are never written | Staff have no working view of this today |
| Boundary inconsistency | Dashboard uses "today **after** date" | Must pick one rule for the sweep (Q3) |
| `current_balance` is calculated two different ways | Re-summed in `BillingService`, decremented in `PaymentController.php:184` | Can drift, so do not base disconnection on it |
| Deleting a normal payment does not reverse the bills | `PaymentController.php:530` | Bill stays `Paid`, so the client stays connected |

### Security issues to fix before storing real credentials

| # | Problem | Evidence |
|---|---|---|
| **S1** | Router passwords are stored in plain text, despite the `// encrypted` comment. These are admin credentials to a tenant's network | `Mikrotik.php` has no cast, `create_mikrotik_table.php:23` |
| **S2** | Router password is returned in API responses. No role check, policy is commented out, so any staff account can read it | `MikrotikResource.php:27`, `MikrotikController.php:105` |
| **S3** | PPPoE passwords are in plain text and exposed in responses. They must be *reversible*, because the router needs the original, so use Laravel's `encrypted` cast and not a hash. Widen the column to `text`, since encrypted values can exceed 255 characters | `create_clients_table.php:45`, `ClientResource.php:54`, `ClientController.php:320` |
| **S4** | **Cross-tenant write.** The `site_id` header takes priority over the logged-in user, and the UI sends it from `localStorage`. A staff user can register a router under another tenant | `Mikrotik.php:44`, `MikrotikSettingsForm.jsx:196` |
| **S5** | Tenant filter switches off in console. Verified: the query becomes `select * from clients`. Useful for a sweep, but every job must filter by site and router explicitly | `SiteScope.php:14-18` |
| **S6** | PPPoE fields are not validated. Rules use `pppoe_username`, the request sends `PPPOEUsername`, and there is no per-router uniqueness | `StoreClientRequest.php:52-53`, `UpdateClientRequest.php:52-53` |

### Minor cleanup

- `MikrotikController.php:38-39,50` filter on `next_harvest_date` and `harvest_day`, which are not columns on `mikrotiks`, so searching will throw a SQL error.
- Error messages in that controller still say "Peso Wifi Client".
- The `mikrotik_se` table is empty and unused.
- `MikrotikResource` outputs `iPAddress` but the request reads `ipAddress`.

---

## 6. Proposed design

### Core idea: one reconcile function, many triggers

Do not write separate "disconnect" and "reconnect" code paths scattered through controllers. Instead:

1. Work out what the router **should** look like for a client, from the database.
2. Compare it with what the router **actually** has.
3. Apply only the difference.

Every event simply asks for a reconcile. Because it is safe to repeat, a failed call is fixed by the next sweep.

```
 Client saved ─┐
 Payment ──────┤                                  ┌──────────────┐  tunnel  ┌─────────┐
 Bill changed ─┼─► queue job (after DB commit) ─► │ Reconcile    │ ───────► │ Tenant  │
 Scheduled     │     one at a time per router     │ desired vs   │  API     │ router  │
 sweep ────────┘                                  │ actual       │ ◄─────── │         │
                                                  └──────┬───────┘          └─────────┘
                                                         ▼
                                              status + audit log in DB
```

### Who should be suspended

Use **`billings`** as the source of truth, not `clients.latest_disconnection_date` (see B7). A client is suspended when all of these are true:

- `clients.is_active = 1`, has a `pppoe_username`, and is mapped to an active router
- has at least one bill with `is_active = 1`, status `Pending` or `Partial`, `billing_balance > 0`, and `disconnection_date` on or before today in the tenant's timezone
- no staff exemption is in force, such as a promise-to-pay date

Bills with no disconnection date (installation, repair) never trigger a cut. An unpaid *older* bill still does, even if the newest one was paid.

### Requirement 1 and 2: detect and disconnect

1. A scheduled command loops through active routers, per site, and queues one job per router.
2. The job loads eligible clients for that router, and fetches `/ppp/secret` and `/ppp/active` **once** to compare in memory. This avoids hundreds of round trips.
3. For each client that should be suspended but is not: disable the secret, remove the active session, record the result.

### Requirement 3: new subscriber

1. `ClientController::store` commits as it does now.
2. A job is dispatched **after commit**.
3. The job creates the secret or adopts an existing one with the same name, and sets the status.

**Never call the router inside the DB transaction.** A router timeout would otherwise fail customer creation. The client should be saved, marked "provisioning pending", and retried.

### Reconnection and other events

| Event | Where in code | Router effect |
|---|---|---|
| Client created | `ClientController@store` | Add secret |
| PPPoE or plan changed | `ClientController@update`, `@replace` | Update secret, drop session |
| Client deactivated | `ClientController@update` | Disable secret, drop session |
| Client moved to another router | `ClientController@update` | Add on the new router, then remove from the old one |
| Client deleted | `ClientController@destroy` (hard delete at `:204`) | Capture router and username **before** delete, then disable or remove |
| Payment recorded | `PaymentController@store` | Reconcile, enable if nothing is overdue |
| Payment reversed or deleted | `PaymentController@update`, `@destroy` | Reconcile, may suspend again |
| Bill voided or edited | `BillingService@deactivateBilling`, `@updateBilling` | Reconcile |
| Sweep | Scheduler | Catches anything missed |

### Proposed data changes

| Table | Addition | Purpose |
|---|---|---|
| `mikrotiks` | `server_id` | One area (Server) has many routers |
| `clients` | `mikrotik_id` | A subscriber's PPPoE secret lives on exactly one router |
| `internetplans` | `mikrotik_profile` | Plan to PPP profile name |
| `clients` | network status, last sync time, last error, exemption date | State, retry, UI |
| `mikrotiks` | status and last error, per-site enable flag | Health and kill switch |
| **new** `mikrotik_logs` | client, router, action, result, reason, actor | Audit trail for billing disputes |

Do **not** store RouterOS `.id` values long term. Look secrets up by name, which is unique per router.

### Safety controls (treat as required)

An automated job that can cut off paying customers needs guard rails.

- **Dry-run mode** per site, which logs who *would* be cut and touches nothing. Run it first for every new tenant.
- **Kill switch** per site and per router.
- **Circuit breaker**: abort the run if it would suspend an abnormal share of a router's subscribers (for example, more than 30%), since that usually signals a bad billing run.
- **Disconnect window**: cut only during hours when staff can respond. Reconnect at any time.
- **Router offline**: never fail billing or client saves. Queue, retry with backoff, alert.
- **Least privilege**: a dedicated RouterOS user, not `admin`, with access limited to the tunnel or SaaS IP.
- **Own only what you manage**: never delete secrets the app did not create or adopt.

### Onboarding existing tenants

Tenants already have subscribers on their routers. The first sync must **import and match** by PPPoE username, then report mismatches for staff to resolve (on the router but not in the app, or the reverse). A blind "add all" would create conflicts or overwrite passwords. Locally only 1 of 3 clients has a PPPoE username, so check how complete this is in production before enabling automation.

The same import can fill in `clients.mikrotik_id`: search the routers in the client's area for that username. A username found on two routers, or on none, goes to staff to resolve.

### Two companies, one app

Each company is one row in `sites`, and everything below it belongs to that company. The company names, areas and routers here are examples.

```
App (one codebase, one database)
│
├─ Site: Red Jacob Internet Services
│    ├─ Area "Samboan Server"
│    │    ├─ Router RJ-Samboan-1 ── subscribers
│    │    └─ Router RJ-Samboan-2 ── subscribers
│    └─ Area "Oslob Server"
│         └─ Router RJ-Oslob-1 ──── subscribers
│
└─ Site: Exisi Inc. Internet Services
     └─ Area "Samboan Server"          (same name, separate record)
          ├─ Router EX-Samboan-1 ── subscribers
          └─ Router EX-Samboan-2 ── subscribers
```

**Assumption to confirm:** your local `servers` rows are named after places (Cantinlo, Samboan, Oslob), so this report treats a `servers` record as an **area**. The table stays as it is, and the UI label could become "Area / Server".

**What already works for two companies (verified):**

- `servers` and `billing_categories` are unique per site, so both companies can use the same names.
- `internetplans` is per site, and `mikrotiks` names are validated per site.
- `users` are unique per site for username and email.

**What must be added or fixed:**

| # | Item | Detail |
|---|---|---|
| **T1** | Subscriber email is unique across **all** companies (verified) | `StoreClientRequest.php:29` uses `unique:clients`. A person subscribed to both companies is rejected, and the error reveals that the email exists elsewhere. Scope it by site |
| **T2** | One company needs **many** routers (verified) | `MikrotikSettingsForm.jsx` handles a single router. It needs a list with add and edit |
| **T3** | Each client needs a router, and it must be valid | On save, the router must belong to the same site and to the client's area. Reject anything else |
| **T4** | Platform assigns the router address | With a tunnel, the tenant should not type `ip_address`. Otherwise a tenant could point "test connection" at another company's router or at internal hosts on your network |
| **T5** | Jobs are built around one router | Each job loads that router's own credentials and handles only clients with the same `site_id` and `mikrotik_id`. Credentials are never shared or chosen by company name |
| **T6** | One company's outage must not affect the other | One job per router, with short timeouts and retries, so a dead Exisi router cannot delay Red Jacob |
| **T7** | Settings are per company | Dry-run, kill switch, disconnect window, circuit breaker, and timezone if needed |
| **T8** | Each router needs the plan's PPP profile | `mikrotik_profile` is per company, but the profile must exist on **every** router that serves that plan. Report a missing profile rather than guessing |

**Identical values do not collide.** Both companies can have a `juan01` PPPoE user, because a secret is unique only per router. Both can also use MikroTik's default LAN range, because the app reaches each router by its unique tunnel IP. Inside one company, a username needs to be unique only per router, but per site is simpler for staff and makes moving a client easier.

**One sweep, both companies:**

1. The scheduler lists active routers across all sites. Each router with its site enabled gets its own job.
2. The `EX-Samboan-1` job connects with that router's credentials and loads only Exisi clients assigned to it.
3. It disables and disconnects those who are overdue, and writes the audit log with that site and router.
4. A Red Jacob payment triggers a reconcile only on that subscriber's router.

Exisi staff see only Exisi routers and logs, through the existing site filter. Fix S4 first, or that boundary can be crossed when a router is created.

---

## 7. Files affected

### Backend, existing files

| File | Change | Reason |
|---|---|---|
| `app/Http/Controllers/Api/MikrotikController.php` | Modify | B1, test connection, remove harvest filters, authorization |
| `app/Http/Requests/Api/MikrotikRequest/*` | Modify | Field contract, validation |
| `app/Http/Resources/Api/MikrotikResource.php` | Modify | Remove password (S2) |
| `app/Models/Mikrotik.php` | Modify | Encrypted cast, fix `site_id` (S1, S4), relations |
| `app/Models/Server.php` | Modify | Router relation |
| `app/Models/Internetplan.php` | Modify | Profile field |
| `app/Models/Client.php` | Modify | Encrypted PPPoE password, status fields, relations |
| `app/Http/Resources/Api/ClientResource.php` | Modify | Stop exposing password, add status |
| `app/Http/Requests/Api/ClientRequest/*` | Modify | Fix PPPoE validation (S6), router validation (T3), per-site email (T1) |
| `app/Http/Controllers/Api/ClientController.php` | Modify | Dispatch reconcile, line 320 leak |
| `app/Http/Controllers/Api/PaymentController.php` | Modify | Dispatch reconcile |
| `app/Http/Controllers/Api/ServerController.php`, `InternetplanController.php` and their Requests/Resources | Modify | New fields |
| `app/Services/BillingService.php` | Modify | B5, B6, B7, reconcile trigger |
| `app/Libraries/Billing/MonthlySubscription.php` | Modify | Remove `auth()` dependency (B5) |
| `app/Console/Commands/RunAutomatedBilling.php` | Modify | Run per site, stop hiding failures |
| `app/Services/DashboardService.php` | Modify | Real overdue statuses |
| `routes/api.php` | Modify | Test connection, sync, manual suspend/reconnect |
| `routes/console.php` | Modify | Schedule the sweep |
| `config/app.php` | Modify | Timezone, depends on Q6 |
| `composer.json`, `composer.lock` | Modify | RouterOS client |

### Backend, new files (proposed)

| File | Purpose |
|---|---|
| `app/Services/Mikrotik/RouterClient` (interface and API implementation) | Single wrapper for RouterOS, mockable in tests |
| `app/Services/Mikrotik/SubscriberSyncService` | The reconcile logic |
| `app/Services/SuspensionPolicy` | The one definition of "should be suspended" |
| `app/Jobs/ReconcileClient`, `ReconcileRouter` | Queued work, one at a time per router |
| `app/Console/Commands/RunNetworkSweep`, `ImportRouterSubscribers` | Scheduled sweep, tenant onboarding |
| `app/Models/MikrotikLog` | Audit |
| Migrations for section 6, plus re-encrypting existing passwords | Schema |
| `tests/Feature/Mikrotik*`, `tests/Unit/SuspensionPolicyTest` | Using a fake router |

### Frontend (`telcosphere_ui`)

| File | Change |
|---|---|
| `src/commoncomponents/MikrotikSettingsForm.jsx` | Align fields, list/edit many routers per company, choose the area, working test button |
| `src/components/pages/clients/createclient.jsx`, `editclient.jsx` | Router picker filtered by the chosen area, network status, suspend/reconnect actions, stop displaying password in clear |
| Internet plan forms | PPP profile field |
| Dashboard or new page | Suspension list, sync errors, dry-run preview |

### Infrastructure

- `extension=sockets` enabled in PHP, locally and in production
- Production cron for `schedule:run`, and a supervised `queue:work`. On Windows/XAMPP, use Task Scheduler for local tests
- WireGuard hub, if option B is chosen

---

## 8. Recommended order of work

| Phase | Outcome | Risk to live subscribers |
|---|---|---|
| **0. Fix foundations** | B1, B2, S1-S6, B5-B7, timezone decision | None |
| **1. Connect** | Router client, "test connection", router health | None, read only |
| **2. Map and import** | Router-to-area, client-to-router, plan-to-profile, import existing secrets | None, read only |
| **3. Provision** | New subscribers appear on the router | Low |
| **4. Enforce in dry-run** | Sweep logs who would be cut, staff review for at least one billing cycle | None |
| **5. Enforce live** | Disconnect and auto-reconnect, one pilot tenant first | Controlled |

Reconnect must ship **in the same release as** disconnect, never after it.

---

## 9. Testing on your hAP and RouterBOARD

You have the ideal lab: **one router as the PPPoE server (the tenant's router), the other as the subscriber.** The commands below are a starting point for RouterOS v7. Adjust interface names to match your device.

**On the PPPoE server router:**

```routeros
/ip pool add name=pppoe-pool ranges=10.10.10.2-10.10.10.254
/ppp profile add name=PLAN_10M local-address=10.10.10.1 remote-address=pppoe-pool rate-limit=10M/10M
/interface pppoe-server server add service-name=lab interface=<lan-port-or-bridge> default-profile=PLAN_10M disabled=no
/ppp secret add name=lab-user-01 password=<test-password> service=pppoe profile=PLAN_10M

# App login, restricted to your PC
/user group add name=telcosphere policy=read,write,api
/user add name=telcosphere group=telcosphere password=<strong-password>
/ip service set api disabled=no address=<your-pc-ip>/32
```

**On the subscriber router:**

```routeros
/interface pppoe-client add name=pppoe-lab interface=<wan-port> user=lab-user-01 password=<test-password> disabled=no
```

**Questions only the hardware can answer (Needs lab test):**

| # | Test | Why it matters |
|---|---|---|
| 1 | Disable a secret while the subscriber is online. Does the session stay up? | Confirms section 2 |
| 2 | Remove the active session on a disabled secret. Does redial fail? | Core disconnect |
| 3 | Enable the secret. How long until the subscriber is back? | Sets reconnect expectations |
| 4 | Change the profile on a live session. Does the speed change before a re-dial? | Plan changes |
| 5 | Is the `read,write,api` policy enough for every operation? | Least privilege |
| 6 | Time 300 lookups in one connection vs one connection each | Validates batching and the default 3-second timeout |
| 7 | Unplug the router mid-run | Retry and no partial damage |
| 8 | Check your RouterOS version on both devices (`/system resource print`) | v6 vs v7 decides the tunnel and REST options |

---

## 10. Questions for you

These change the design, so they need your decision before building.

| # | Question | My recommendation |
|---|---|---|
| **Q1** | ~~Is a "Server" exactly one physical router?~~ **Answered:** an area may have several | Add `mikrotiks.server_id` and `clients.mikrotik_id`. See "Two companies, one app" |
| **Q2** | Are all subscribers PPPoE, or also Hotspot, static IP, or DHCP? | Your schema assumes PPPoE. Build that first and keep the client class extensible |
| **Q3** | Cut **on** the disconnection date or the day **after**? | On the date, in the morning window |
| **Q4** | Does a **partial** payment keep the connection? | No, keep it suspended until the overdue bills are cleared, but allow staff exemptions |
| **Q5** | Full disconnect, or a "walled garden" showing a pay-your-bill page? | Start with full disconnect, as it is simpler. A suspended profile with redirect is a good later upgrade and reduces support calls |
| **Q6** | Are all tenants in the Philippines? | If yes, set the app timezone to `Asia/Manila`. If not, use a per-site timezone. Existing timestamps need checking either way |
| **Q7** | Where is production hosted, and can it run WireGuard, cron, and a queue worker? | Decides option B and B9 |

---

## 11. Sources

- RouterOS API: <https://help.mikrotik.com/docs/spaces/ROS/pages/47579160/API>
- RouterOS REST API: <https://help.mikrotik.com/docs/spaces/ROS/pages/47579162/REST+API>
- PPPoE: <https://help.mikrotik.com/docs/spaces/ROS/pages/2031625/PPPoE>
- WireGuard: <https://help.mikrotik.com/docs/spaces/ROS/pages/69664792/WireGuard>
- PHP client requirements: <https://github.com/EvilFreelancer/routeros-api-php/blob/master/composer.json>

MikroTik marks that documentation site as frozen and points to <https://manual.mikrotik.com/docs/introduction/> for current docs. Re-check version-specific details there during implementation.
