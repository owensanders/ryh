# Pre-go-live checklist

Everything that has to be true before RYH takes real households and real
payments. Most of it is setup outside the code. Tick items off in the PR that
does them, or note where they were done.

RYH runs on **Laravel Cloud**, region **EU West (London)**, Starter plan.
Cloud provides TLS, DDoS protection and the Cloudflare edge, deploys, the
scheduler, queue workers, Reverb and database backups, so there are no
servers, Supervisor, cron or Redis tuning to look after. Expected cost is
about $32 (£25) a month at most. Upgrade to Growth ($20/month plus usage) for
7-day logs, Pro compute and autoscaling once revenue covers it.

**Status (October 2026):** the code is launch-ready. Launch is on hold until
RYH Ltd exists, so Stripe, the ICO registration and the legal pages are in the
company's name from day one rather than moved over later.

## Order of work

Some steps take days because they depend on someone else's verification.
Start those first and in parallel, and do the rest while they complete.

1. **Start the slow items:** the business, the domain, Stripe, the mail
   provider and the ICO registration (next section).
2. **Set up Laravel Cloud** on its free `*.laravel.cloud` address with Stripe
   *test* keys, and run the client-IP check under Final checks early: it's
   the only check that could need a code change. Hold off until the company
   is close, because Cloud bills from the first deploy.
3. **Go live:** the custom domain, live Stripe and mail keys, the `LEGAL_*`
   details, then every item under Final checks.

## Business and accounts

These don't need Laravel Cloud and can be done first.

- [ ] **Register the company** (RYH Ltd) at Companies House, about £50 online
      and usually done within a day or two. Use a registered office service
      if your home address shouldn't be public. This gives
      `LEGAL_ENTITY_NAME`, `LEGAL_ENTITY_TYPE=limited_company`,
      `LEGAL_COMPANY_NUMBER` and `LEGAL_ADDRESS`.
- [ ] **Business bank account** in the company's name, for Stripe payouts.
- [ ] **Accountant** (optional but sensible) for the yearly accounts,
      confirmation statement and Corporation Tax. VAT isn't needed until
      turnover passes the registration threshold (£90,000 a year in 2026).
- [ ] **ICO data protection fee**, registered in the company's name once it
      exists. The self-assessment (October 2026) puts RYH in **Tier 1, £52 a
      year** (£5 less by direct debit): a controller, not exempt, ten or
      fewer staff, and none of the special sectors. Not-for-profit and
      "household affairs" don't apply, because the service is commercial.
      The registration number goes in `LEGAL_ICO_REGISTRATION`, which shows
      it on `/privacy`. Renew it every year.
- [ ] **Domain** bought. `APP_URL`, mail, Stripe and the custom domain on
      Cloud all use it.
- [ ] **Stripe account** in the company's name, with live mode activated
      (business verification can take a few days). Create the live prices
      (£3.99 monthly, £39.99 annual) for `STRIPE_PRICE_HOME_MONTHLY` and
      `STRIPE_PRICE_HOME_ANNUAL`.
- [ ] **Mail provider** (Postmark or Resend) account, with the domain's
      SPF/DKIM records added. DNS changes take time to verify, and Postmark
      reviews new accounts before they can send to anyone.
- [ ] **Inboxes** for the support and privacy addresses on the domain (e.g.
      `support@` and `privacy@`, which can be the same inbox), since
      customers and data requests reply to them. The mail provider only
      sends. These become `SUPPORT_EMAIL` and `PRIVACY_EMAIL`.
- [ ] **Production VAPID keys** generated once (`php artisan webpush:vapid`)
      and stored in a password manager. They go in Cloud later, and replacing
      them afterwards stops every existing push subscription working.
- [ ] **Error tracker** account chosen (Nightwatch, Sentry or Flare).
- [ ] **Anthropic API key** for production, with credit added.

## Laravel Cloud environment

Create an application from the GitHub repo, with one `production` environment
in London. On its canvas:

- [ ] **PHP 8.4** in the environment settings, matching CI (typed class
      constants need 8.3 or newer).
- [ ] **App cluster:** Flex 1 GiB, **Scheduler** toggle on. It runs the 03:00
      prunes: notifications over 90 days old, chores completed over 30 days
      ago, and shopping items ticked off over a week ago. Decide on
      scale-to-zero: it saves money while nobody's using the app, but the
      first request after an idle spell waits about half a second. Cloud
      wakes the app for scheduled tasks either way.
- [ ] **Managed queue:** standard (not FIFO), Flex 256 MiB, 1–3 workers.
      Cloud sets `QUEUE_CONNECTION=cloud`. Every email, live notification and
      push goes through it. Flex jobs may run for at most 90s and wait at most
      15 minutes between retries (`RetriesEmailTrait` stays within that).
      Failed jobs are retried from the **Queues** dashboard: `queue:retry` and
      `queue:failed` don't work with managed queues.
- [ ] **MySQL:** flex 512 MB. Daily backups are included. Set
      `LEGAL_BACKUP_RETENTION_DAYS` to Cloud's retention (the Privacy Policy
      quotes it), and do a test restore once.
- [ ] **Cache:** Laravel Valkey, flex 250 MB, eviction `allkeys-lru`. Set
      `CACHE_STORE=redis` and `SESSION_DRIVER=redis`. A Flex cache that
      sleeps snapshots to disk, so sessions survive it.
- [ ] **WebSockets:** a Reverb cluster with 100 concurrent connections,
      attached to the environment. Cloud injects `REVERB_*` and
      `VITE_REVERB_*` (the build needs these) and allows the app's origins.
- [ ] **Bucket:** Laravel Object Storage, **private**, disk name
      `documents`, not the default disk. Set `DOCUMENTS_DISK=documents`.
      Downloads use short-lived `temporaryUrl` links.
- [ ] **Deploy command:** `php artisan migrate --force`. The build uses
      Cloud's defaults: `composer install --no-dev` (this also leaves
      Telescope out) and `npm ci && npm run build`.
- [ ] **Custom domain** added and verified. Turn **HSTS** on under Edge
      network once HTTPS works on the real domain. Push notifications and
      installing the app both need HTTPS.
- [ ] **Spending:** set alerts only (50/80/100% of about $40). Don't set a
      spending *limit*: reaching it pauses compute, which takes a paid
      product offline.

## Configuration

Environment variables, set under the environment's settings.

- [ ] `APP_ENV=production`, `APP_DEBUG=false`, a fresh `APP_KEY`, and `APP_URL`
      set to the real `https://` domain.
- [ ] `SESSION_SECURE_COOKIE=true`.
- [ ] `LOG_LEVEL` at `info` or above. The Starter plan keeps logs for **one
      day**, so an error tracker (Nightwatch, Sentry or Flare) is required,
      not optional.
- [ ] Stripe live keys: `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`,
      `STRIPE_PRICE_HOME_MONTHLY`, `STRIPE_PRICE_HOME_ANNUAL`. The webhook
      endpoint (`/stripe/webhook`) registered in the Stripe dashboard.
- [ ] `ANTHROPIC_API_KEY` with credit, `AI_MEAL_PLAN_DAILY_CAP` chosen, and
      `AI_ALERT_EMAIL` set so you hear when the AI runs out of credit.
- [ ] `VAPID_PUBLIC_KEY` / `VAPID_PRIVATE_KEY` copied from the password
      manager (generated under Business and accounts; never regenerate them).
      `VAPID_SUBJECT` set.
- [ ] Mail through an API provider (Postmark or Resend), with `MAIL_MAILER`
      and its key, and a `MAIL_FROM_ADDRESS` on a domain with SPF/DKIM set up.
- [ ] Every `LEGAL_*`, `SUPPORT_EMAIL` and `PRIVACY_EMAIL` filled in, so
      `/terms`, `/privacy` and `/support` show no "[… to be confirmed]".
      That includes `LEGAL_MAIL_PROVIDER` (e.g. "Postmark (ActiveCampaign
      LLC)") and `LEGAL_ERROR_TRACKER` (e.g. "Sentry (Functional Software
      Inc.)"), the processors named in the Privacy Policy.
      `LEGAL_HOSTING_PROVIDER` already defaults to Laravel Cloud.
- [ ] Leave `TRUSTED_PROXIES` unset to begin with, and run the client-IP
      check under Final checks. The app is only reachable through Cloud's
      edge, but whether `request()->ip()` sees the visitor or a Cloudflare
      address has to be checked live.

## Monitoring

- [ ] An uptime check on `/up`.
- [ ] After each deploy, glance at `/status`. It checks the database, cache,
      queue (a heartbeat job through the managed queue), Reverb, AI and the
      documents bucket.

## Final checks

- [ ] **Rate limits see the real visitor.** Sign-up and password reset count
      per IP. If the app sees a Cloudflare address instead, one spammer locks
      everyone out.
      1. From one machine, POST `/forgot-password` 6 times within a minute
         (the 6th gets a 429).
      2. From a different network (a phone on 4G), the form still works.
      3. From the first machine, once the minute is up, POST 6 more times,
         each with a different forged `X-Forwarded-For: 1.2.3.<n>` header.
         The 6th must still get a 429.

      If step 2 or 3 fails, trust only Cloudflare's published IP ranges
      (`trustProxies(at: [...])` in `bootstrap/app.php`) and run the check
      again.
- [ ] Sign up a brand-new account on the live site: the verification email
      arrives (it shows in the Queues dashboard), the trial starts, a real
      card subscribes, and the Stripe webhook lands (subscription shows as
      active).
- [ ] The bell updates live when another account assigns you a chore. That
      confirms Reverb, and that `VITE_REVERB_*` were set at build time.
- [ ] Install the app on an Android and an iPhone, turn push on, and get a
      push from another account.
- [ ] Upload and download a document. The download should be a short-lived
      link to the R2 bucket.
- [ ] **Cookies match the Cookie Policy.** In the browser's dev tools, look
      at the cookies on the live domain after signing in. Expect the session
      cookie, `XSRF-TOKEN`, `remember_web_…` (if you ticked Remember me) and
      possibly Cloudflare's `__cf_bm`/`cf_clearance`. If anything else
      appears, add it to `resources/js/pages/legal/Cookies.vue` and bump
      `cookies_version` in `config/legal.php`.
- [ ] The day after launch, check the 03:00 prunes ran (the scheduler is
      on).
