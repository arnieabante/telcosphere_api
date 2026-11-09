# MikroTik Integration: Test Run Guide

This guide shows how to test the MikroTik integration from start to finish, using your hAP and RouterBOARD as a safe lab. It covers what to do, what you should see, and what to check when you don't.

For the background and design decisions, see [mikrotik_integration.md](mikrotik_integration.md).

> **Test status (2026-11-09)**
> - All **68 automated tests pass**. They use a simulated router.
> - This code has **not yet been run against a real MikroTik**. The "you should see" results below come from reading the code, and this lab run is the first real proof. If something differs, trust the router and note it.

> **Safety:** only use a lab router for this guide. If a router has real subscribers, start with [Test 8 (import)](#test-8-import-existing-subscribers), and never use `--force` on it.

---

## 1. How it works

```
 Staff save a client or payment          Hourly sweep (mikrotik:sweep)
              │                                     │
              └───────────► Queue jobs ◄────────────┘
                                │
                    One job per router: compare
                    the database with the router,
                    apply only the difference
                                │
                                ▼
              MikroTik: add / disable / enable PPPoE
              secret, and drop the live session
                                │
                                ▼
              Status saved on the client + audit log
```

A subscriber is disconnected when **all** of these are true:

- The client is active, has a PPPoE username, and is linked to a router.
- At least one active bill is `Pending` or `Partial`, with a balance above zero.
- That bill's disconnection date is today or earlier, in the company's timezone.
- The client has no staff exemption (`suspension_exempt_until`) still in force.

A partial payment does **not** reconnect. The overdue bill has to be fully paid.

### What each company setting does

Find these in **Site Settings → MikroTik Settings** tab. New companies start with the integration off and Dry Run on.

| Setting | Default | Effect |
|---|---|---|
| Enable MikroTik Integration | Off | When off, the app does nothing on that company's routers |
| Dry Run | On | Logs who would be cut off, without cutting anyone |
| Disconnect From / Until | 08:00 / 17:00 | Sweeps only cut off during these hours. Reconnects happen at any time |
| Safety Limit (%) | 30 | Stops a run that would cut off too many subscribers (only above 5 cuts) |
| Timezone | Asia/Manila | Decides what "today" and the hours above mean |

> **Dry Run only blocks disconnections.** It still adds missing subscribers, applies plan and password changes, and reconnects people. Link only the clients you mean to manage.

---

## 2. Run the automated tests first

These need no router. Your PHP has the SQLite driver turned off, so load it for this one command:

```powershell
php -d extension=pdo_sqlite -d extension=sqlite3 vendor/bin/pest tests/Feature/MikrotikIntegrationTest.php tests/Unit/RouterOsClientTest.php
```

**You should see:** `Tests: 68 passed`.

To avoid the extra flags, uncomment `extension=pdo_sqlite` (line 955) and `extension=sqlite3` (line 966) in `D:\xampp\php\php.ini`. After that, `php artisan test` works too.

---

## 3. Set up the lab

### Example used in this guide

| Item | Example value |
|---|---|
| Company | Your local site (site 1), acting as "Exisi Inc. Internet Services" |
| Area | Samboan Server |
| Router name | EX-Samboan-1 |
| Router role | **hAP** is the company's PPPoE server |
| Subscriber role | **RouterBOARD** acts as the customer's device |
| PPPoE user | `lab-user-01` |
| Plans | PLAN_10M and PLAN_20M |

### Wiring

```
 Your PC ──── hAP (LAN port) ──── RouterBOARD (ether1)
              192.168.88.1
              PPPoE server         PPPoE client
```

### On the hAP

Adjust `bridge` if your LAN interface has another name. Replace the `<...>` values.

```routeros
/ip pool add name=pppoe-pool ranges=10.10.10.2-10.10.10.254
/ppp profile add name=PLAN_10M local-address=10.10.10.1 remote-address=pppoe-pool rate-limit=10M/10M
/ppp profile add name=PLAN_20M local-address=10.10.10.1 remote-address=pppoe-pool rate-limit=20M/20M
/interface pppoe-server server add service-name=lab interface=bridge default-profile=PLAN_10M disabled=no

# A dedicated login for the app, allowed only from your PC
/user group add name=telcosphere policy=read,write,api
/user add name=telcosphere group=telcosphere password=<strong-password>
/ip service set api disabled=no address=<your-pc-ip>/32
```

Do **not** create the PPPoE secret yourself. Creating it is the app's job in Test 1.

### On the RouterBOARD

```routeros
/interface pppoe-client add name=pppoe-lab interface=ether1 user=lab-user-01 password=LabPass123 disabled=no
```

It won't connect yet, because the account doesn't exist. That's expected.

### Start the app

Run each of these in its own terminal:

```powershell
# telcosphere_api
php artisan serve

# telcosphere_api: this applies the router changes, so keep it running
php artisan queue:work

# telcosphere_ui
npm run dev
```

If `queue:work` isn't running, saves still succeed but nothing reaches the router.

### Configure the app

**a) Add the router:** go to `/mikrotiksettings` and click create.

| Field | Value |
|---|---|
| Router Name | EX-Samboan-1 |
| IP Address | 192.168.88.1 |
| Server / Area | Samboan Server |
| Port | 8728 |
| Username | telcosphere |
| Password | the one you set on the hAP |

Click **Test Connection**.
**You should see:** `Connected to <router name> (RouterOS <version>).` Then click **Save**.

**b) Turn on the integration:** in the **MikroTik Settings** tab, enable the integration and **leave Dry Run on**.

**c) Set up plans:** in Internet Plans, make two plans and fill in **MikroTik PPP Profile** exactly as on the router (`PLAN_10M`, `PLAN_20M`). Spelling and case must match.

**d) Add a billing category for the lab:** create "Lab Category" with **cycle day 30**. Use it only for the test client, because a monthly bill goes to everyone in its category.

**e) Find the router's ID** for the commands below, using phpMyAdmin:

```sql
SELECT id, name, last_seen_at, last_error FROM mikrotiks;
```

The commands below use `--router=1`. Use your own ID if it's different.

---

## 4. Test run

### Handy checks

**On the hAP:**

```routeros
/ppp secret print detail where name=lab-user-01
/ppp active print
```

An `X` flag on the secret means it's disabled.

**In phpMyAdmin:**

```sql
SELECT pppoe_username, network_status, network_synced_at, network_error
FROM clients WHERE pppoe_username = 'lab-user-01';

SELECT created_at, action, result, `trigger`, message
FROM mikrotik_logs ORDER BY id DESC LIMIT 10;
```

**In the app:** the MikroTik Settings tab shows the activity log, and the client edit page shows Network Status.

---

### Test 1: A new subscriber is added to the router

Create a client with these values:

| Field | Value |
|---|---|
| Server | Samboan Server |
| MikroTik Router | EX-Samboan-1 |
| Internet Plan | the PLAN_10M plan |
| Billing Category | Lab Category |
| Installation Date | any date **last month** (so the bill is the full monthly price) |
| PPPoE Username | lab-user-01 |
| PPPoE Password | LabPass123 |

**You should see:**

- A secret on the hAP with `profile=PLAN_10M` and your account number as the comment
- The RouterBOARD connected, and shown in `/ppp active print`
- `network_status` = `active`
- Log entry: **Added to router**, success

---

### Test 2: Dry run reports an overdue subscriber

1. Go to `/billings/create`. Choose Monthly Subscription and Lab Category, then set **Disconnection Date to today**.
2. Confirm the bill is overdue. Replace `<id>` with the client's ID:

   ```sql
   SELECT billing_status, billing_balance, disconnection_date FROM billings WHERE client_id = <id>;
   ```

   You want `Pending`, a balance above 0, and today's date.
3. Run the sweep. `--now` runs it in this terminal and prints the result. `--force` ignores the disconnect hours and Safety Limit:

   ```powershell
   php artisan mikrotik:sweep --router=1 --now --force
   ```

**You should see:**

```
EX-Samboan-1: {"skipped":false,"checked":1,"suspended":0,"reconnected":0,"provisioned":0,"failed":0,"dry_run":1,"breaker":false}
```

- The RouterBOARD **stays connected**
- Log entry: action `suspend`, result `dry_run`, "Would be disconnected: overdue bill."

---

### Test 3: Live disconnect

1. In MikroTik Settings, **turn Dry Run off** and set Disconnect From/Until to a period that doesn't include the current time, such as `01:00` to `02:00`.
2. Run the sweep **without** `--force`, to prove the disconnect hours are respected:

   ```powershell
   php artisan mikrotik:sweep --router=1 --now
   ```

   **You should see:** `"suspended":0`, and the subscriber stays online.
3. Now run it with `--force`:

   ```powershell
   php artisan mikrotik:sweep --router=1 --now --force
   ```

4. Set Disconnect From/Until back to `08:00` and `17:00`.

**After step 3 you should see:**

- `"suspended":1` in the output
- The secret flagged `X` on the hAP
- `/ppp active print` no longer lists the subscriber
- The RouterBOARD offline, and unable to reconnect
- `network_status` = `suspended`, and log entry **Disconnected**

---

### Test 4: Partial payment keeps them disconnected

Record a collection for **part** of the bill.

**You should see:** the bill is `Partial`, the secret stays disabled, and the status stays `suspended`.

---

### Test 5: Full payment reconnects automatically

Record a collection for the **remaining balance**. Keep `queue:work` running.

**You should see, within a few seconds:**

- The `X` flag gone from the secret
- The RouterBOARD connected again after it redials
- `network_status` = `active`
- Log entry: **Reconnected**, trigger `payment`

---

### Test 6: Plan change

Change the client's plan to the PLAN_20M plan.

**You should see:** the secret now has `profile=PLAN_20M`, the session drops briefly, the RouterBOARD redials, and the new `rate-limit` applies. The log shows **Updated**.

---

### Test 7: Router offline

1. Unplug the hAP from your PC, or disable the API service.
2. Edit the client and save a small change, such as the notes.
3. Run:

   ```powershell
   php artisan mikrotik:sweep --router=1 --now
   ```

**You should see:**

- The client **still saves** without an error
- `Cannot reach 192.168.88.1:8728 (...)` from the sweep
- The reason in `network_error`, and on the client page
- After you reconnect the hAP, the queued retry clears the error. Retries back off up to 10 minutes apart, so it may take a while. **Sync** on the client page queues one immediately.

---

### Test 8: Import existing subscribers

This is how you bring in a router that already has customers. It only reads from the router.

On the hAP, add an account the app doesn't know about:

```routeros
/ppp secret add name=orphan-user password=Temp12345 service=pppoe
```

Then preview the import:

```powershell
php artisan mikrotik:import 1
```

**You should see:** `1 matched (report only, add --apply to save).` and a table listing `orphan-user` as "On router, no client".

Adding `--apply` saves the matches. It links clients to the router, and it never changes the router.

---

### Test 9: Remove the subscriber (also your cleanup)

Delete the test client.

**You should see:** the secret removed from the hAP, the session dropped, and log entry **Removed from router**.

Then finish cleaning up:

```routeros
/ppp secret remove [find name=orphan-user]
```

---

## 5. Hardware questions to answer during this run

The simulated tests can't answer these, so please note what you find.

| # | Check | Why it matters |
|---|---|---|
| 1 | With a subscriber online, disable the secret by hand. Do they stay online? | Confirms why the app also drops the session |
| 2 | Is `read,write,api` enough for every action in Tests 1-9? | Keeps the router login at least privilege |
| 3 | How long does the RouterBOARD take to come back after reconnect? | Sets customer expectations |
| 4 | Does API-SSL work on port 8729 with **Use SSL** on? | Needed for routers reached without a tunnel |
| 5 | Which RouterOS version are your devices on? (`/system resource print`) | v6 or v7 affects the tunnel choice |

---

## 6. Trying the scheduler

So far you've run the sweep by hand. To let it run on its own:

```powershell
php artisan schedule:list   # shows what's scheduled
php artisan schedule:work   # runs the schedule until you stop it
```

The sweep runs **hourly** and writes to `storage/logs/mikrotik-sweep.log`. Each run queues one job per router.

| `.env` setting | Default | Meaning |
|---|---|---|
| `AUTOMATED_BILLING_ENABLED` | `false` | When `true`, the scheduler issues **real invoices**. Keep it off while staff bill by hand |
| `MIKROTIK_SCHEDULER_RUNS_QUEUE` | `true` | The scheduler also processes the queue. Set `false` if you run a supervised `queue:work` |

---

## 7. Command reference

| Command | What it does |
|---|---|
| `php artisan mikrotik:sweep` | Queues a job for each enabled router |
| `... --router=1` | Only that router |
| `... --now` | Runs here and prints the result, without using the queue |
| `... --dry-run` | Reports cuts without making them, even if Dry Run is off |
| `... --force` | Ignores disconnect hours and the Safety Limit. **Lab only** |
| `php artisan mikrotik:import 1` | Compares a router with your clients (report only) |
| `... --apply` | Saves the matches |
| `php artisan queue:failed` | Lists jobs that gave up |
| `php artisan queue:retry all` | Tries those jobs again |

### Client network statuses

| Status | Meaning |
|---|---|
| `unmanaged` | Not synced with a router yet |
| `active` | On the router and enabled |
| `suspended` | Disabled on the router by the app for an overdue bill |
| `inactive` | Client deactivated, and removed or disabled on the router |
| `disabled_externally` | Someone disabled it directly on the router. Routine sweeps leave it alone, but a payment will enable it |

### API endpoints

All of these need `Authorization: Bearer <token>` from a staff account. Get the token from `POST /api/login` with `username`, `password` and `siteId`.

| Method and URL | Purpose |
|---|---|
| `GET` `POST /api/mikrotiks` | List or add routers |
| `GET` `PATCH` `PUT` `DELETE /api/mikrotiks/{uuid}` | View, edit or delete a router |
| `POST /api/mikrotiks/test-connection` | Test a login without saving |
| `GET` `PUT /api/mikrotik-settings` | Read or save company settings |
| `GET /api/mikrotik-logs` | Activity log. Optional `?result=failed` |
| `POST /api/clients/{uuid}/network-sync` | Re-check one client now |

Example body for `POST /api/mikrotiks`:

```json
{
  "name": "EX-Samboan-1",
  "serverId": 2,
  "ipAddress": "192.168.88.1",
  "port": 8728,
  "useSsl": false,
  "username": "telcosphere",
  "password": "your-router-password",
  "timeout": 5
}
```

---

## 8. Troubleshooting

| What you see | Likely cause | Fix |
|---|---|---|
| Nothing reaches the router after a save | `queue:work` isn't running, the integration is off, or the client has no router or username | Check all three. `SELECT COUNT(*) FROM jobs;` above 0 means jobs are waiting |
| `No enabled routers to sweep.` | Integration off, or the router is inactive | Enable it in MikroTik Settings |
| `"skipped":true` | Same as above | Same as above |
| `"suspended":0` for an overdue client | Outside disconnect hours, still in Dry Run, or an exemption applies | Check the settings and bill. In the lab, add `--force` |
| Bill not treated as overdue | Status isn't `Pending`/`Partial`, balance is 0, or date is in the future | Run the query in Test 2 |
| `Cannot reach <ip>:8728` | API off, wrong IP, or `/ip service` address list doesn't include your PC | `/ip service print`, then compare your PC's IP |
| `The router rejected the username or password.` | Wrong login | Re-enter it in the router settings |
| `That address is not allowed for a router.` | The IP is loopback or link-local (`127.x`, `169.254.x`) | Use the router's real LAN or tunnel IP |
| `RouterOS older than 6.43 is not supported.` | Very old firmware | Upgrade RouterOS |
| A router error about the profile | MikroTik PPP Profile doesn't match the router | Fix the spelling on the plan |
| `Cannot create the subscriber on the router: no PPPoE password is saved.` | Client has a username but no password | Set a PPPoE password on the client |
| A permissions error from the router | Group policy is too narrow | Check the group with `/user group print`, then note it for section 5 |
| `"breaker":true` or `Stopped: X of Y subscribers would be cut` | Run exceeded the Safety Limit | **Check the latest billing run** before changing the limit |
| `could not find driver` in tests | SQLite driver off | See section 2 |
| `MissingAppKeyException` or `No application encryption key` | `APP_KEY` is empty | `php artisan key:generate`, once only. Regenerating later makes saved passwords unreadable |

---

## 9. Before going live

Server installation steps are in [deploy/README.md](deploy/README.md).

**Emergency stop:** switch off **Enable MikroTik Integration** for the company. The app then makes no more router calls for that company, including jobs already queued.

- [ ] Every test above passes on real hardware, and section 5 is answered
- [ ] Production has its own `APP_KEY`, kept in a secure backup
- [ ] Migrations have run in production
- [ ] A cron entry runs `php artisan schedule:run` every minute
- [ ] Queue processing is confirmed (scheduler or supervised `queue:work`)
- [ ] Each router is reachable through a VPN tunnel, or by API-SSL with a firewall allowlist. **Plain port 8728 must never be exposed to the internet**, because it sends the password in plain text
- [ ] Each router uses a dedicated app login, not `admin`
- [ ] For each company: PPP profiles match on every router, and `mikrotik:import` has been reviewed and applied
- [ ] Each company runs in **Dry Run for at least one billing cycle**, and staff review the log
- [ ] Start live disconnection with one pilot company
- [ ] Decide whether to turn on `AUTOMATED_BILLING_ENABLED`
