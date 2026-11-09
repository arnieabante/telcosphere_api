# Deploying the MikroTik integration

For whoever installs or upgrades the server. Run the steps in order.

## 1. Before you migrate

`APP_KEY` must be set, because the migration encrypts stored router and PPPoE
passwords. It stops with a clear error if the key is empty.

```bash
grep '^APP_KEY=' .env     # must have a value
php artisan key:generate  # only if it is empty
```

Back up the database first. Never change `APP_KEY` afterwards: every stored
router and PPPoE password would become unreadable.

## 2. Release

Set `QUEUE_CONNECTION=database` in `.env`. With `sync`, router calls would run
inside staff requests and slow them down.

```bash
php artisan migrate --force
php artisan config:cache
```

## 3. Install the scheduler (one cron entry)

`crontab -e` as the web user, using the line in [crontab](crontab) with your
own paths. This one entry runs:

| Job | When | Notes |
|---|---|---|
| `mikrotik:sweep` | Hourly | Disconnects overdue clients inside each company's window. Reconnects at any hour. |
| `queue:work` | Every minute | Carries out router jobs. |
| `app:run-automated-billing` | Daily | Only when `AUTOMATED_BILLING_ENABLED=true`. |

Check that it is working: `php artisan schedule:list`, then
`storage/logs/mikrotik-sweep.log` after the next hour.

**Using Supervisor instead for the queue:** install
[telcosphere-queue.conf](telcosphere-queue.conf) and set
`MIKROTIK_SCHEDULER_RUNS_QUEUE=false`. This gives faster reconnects than the
one-minute cron cycle. Keep the cron entry for the rest.

**Windows / XAMPP (local testing):** create a Task Scheduler task that runs
`php artisan schedule:run` in the project folder every minute.

## 4. Prepare each router

Create a dedicated login. Do not use `admin`.

```routeros
/user group add name=telcosphere policy=read,write,api
/user add name=telcosphere group=telcosphere password=<strong-password>
/ip service set api disabled=no address=<app-server-ip>/32
```

Port 8728 sends the password unencrypted at login. Only use it over a private
tunnel or LAN. On the public internet use API-SSL (8729) with a certificate
installed on the router, and restrict it to the app server's address.

Create one PPP profile per internet plan (`/ppp profile`), then enter its exact
name on the plan in the app.

## 5. Turn it on for a company

1. **Add the routers** under Integrations > Mikrotik and use Test Connection.
2. **Set the PPP profile** on each internet plan.
3. **Link existing subscribers** to a router. Report first, then save:
   ```bash
   php artisan mikrotik:import <router-id>
   php artisan mikrotik:import <router-id> --apply
   ```
4. **Enable the integration** in Site Settings > MikroTik Settings. Leave Dry Run on.
5. **Review** Recent Activity for at least one billing cycle. It lists who would have been cut off.
6. **Turn Dry Run off** for live disconnections.

Useful while testing:

```bash
php artisan mikrotik:sweep --router=<id> --dry-run --now
```

## Emergency stop

Switch off **Enable MikroTik Integration** for the company. No further router
calls are made for it, including queued jobs.

## Step by Step

Part A: Server setup (once only)
Check APP_KEY. It's empty on your local machine, so run php artisan key:generate there. On production, only run it if the key is empty, and never change it afterwards.
Back up the database, then run php artisan migrate. I haven't run this on your database yet.
Set QUEUE_CONNECTION=database in .env.
Install the scheduler, which runs both the sweep and the queue:

* * * * * cd /var/www/telcosphere_api && /usr/bin/php artisan schedule:run >> /dev/null 2>&1
On local XAMPP, use Windows Task Scheduler to run php artisan schedule:run in the project folder every minute.
Part B: Set up each company
Log in as that company's staff. Routers and settings are saved to whichever company you're logged in under.

On each MikroTik router
Make sure your app server can reach the router, by LAN, tunnel or public IP. Without this, Test Connection fails.
Create a dedicated login and allow the API from your app server only:

/user group add name=telcosphere policy=read,write,api
/user add name=telcosphere group=telcosphere password=<strong-password>
/ip service set api disabled=no address=<app-server-ip>/32
Note the PPP profile names with /ppp profile print.
In the app
Check the Servers (areas) exist for this company.
Add each router: Site Settings > MikroTik Settings > Manage Routers. Enter the name, Server/Area, IP, port 8728 and the login from step 6, then click Test Connection and save.
Fill in MikroTik PPP Profile on each Internet Plan, spelled exactly as on the router. If you're unsure, leave it blank, because a wrong or different name changes subscribers' profiles and drops their sessions.
Link the subscribers
Get the router IDs:

php artisan tinker --execute="echo App\Models\Mikrotik::get(['id','site_id','name']);"
Preview the matches, which saves nothing:

php artisan mikrotik:import <router-id>
Fix what it reports, usually a PPPoE username that differs from the router or a Server that doesn't match the router's.
Save the matches:

php artisan mikrotik:import <router-id> --apply
Repeat steps 12–14 for each router. For a brand-new subscriber, pick the router and enter the PPPoE details on the client form instead.
Test in dry-run
Turn it on: in the MikroTik Settings tab, switch on Enable MikroTik Integration and leave Dry Run on. Set the disconnect hours, Safety Limit and timezone, then save.

Dry Run only blocks disconnections for non-payment. Adding, updating and removing subscribers, and reconnecting after payment, all happen for real from this point.

See who would be cut off:


php artisan mikrotik:sweep --router=<router-id> --dry-run --force --now
--force lets this report run outside the disconnect hours, and --dry-run still prevents any cuts.

Review Recent Activity in the same tab for at least one billing cycle. Check that each listed client really is overdue.

Go live
Try one test subscriber first, ideally on your hAP. Add a client, check the secret appears on the router, then test a disconnect and a payment reconnect. This has never been run on a real MikroTik, so don't skip it.
Switch off Dry Run and save. Overdue clients are then cut at the next hourly sweep inside the disconnect hours.
Good to know
Who gets disconnected: only clients linked to a router with an unpaid bill whose disconnection date has arrived. Bills with no disconnection date never trigger a cut.
Promise to pay: set Keep Connected Until on the client's edit page.
Emergency stop: switch off Enable MikroTik Integration for that company. All router activity for it stops, including jobs already queued.

