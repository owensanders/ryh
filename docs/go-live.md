# Pre-go-live checklist

Everything that has to be true before RYH takes real households and real
payments. Most of it is setup outside the code. Tick items off in the PR that
does them, or note where they were done.

## Configuration

- [ ] `APP_ENV=production`, `APP_DEBUG=false`, a fresh `APP_KEY`, and `APP_URL`
      set to the real `https://` domain.
- [ ] `SESSION_SECURE_COOKIE=true`.
- [ ] `TRUSTED_PROXIES` set to the load balancer's addresses, if there is one
      (not `*` in production unless the load balancer is the only way in).
- [ ] `LOG_LEVEL` at `info` or above, and `LOG_STACK` sent somewhere you'll
      read it (e.g. `daily` plus an error tracker).
- [ ] Stripe live keys: `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`,
      `STRIPE_PRICE_HOME_MONTHLY`, `STRIPE_PRICE_HOME_ANNUAL`. The webhook
      endpoint (`/stripe/webhook`) registered in the Stripe dashboard.
- [ ] `ANTHROPIC_API_KEY` with credit, `AI_MEAL_PLAN_DAILY_CAP` chosen, and
      `AI_ALERT_EMAIL` set so you hear when the AI runs out of credit.
- [ ] `VAPID_PUBLIC_KEY` / `VAPID_PRIVATE_KEY` generated once
      (`php artisan webpush:vapid`) and kept: new keys stop every existing
      push subscription working. `VAPID_SUBJECT` set.
- [ ] Real mail provider (`MAIL_*`) and a `MAIL_FROM_ADDRESS` on a domain
      with SPF/DKIM set up.
- [ ] Documents on S3: `DOCUMENTS_DISK=s3` and `AWS_*`. The bucket is
      private (downloads use short-lived signed links).
- [ ] Every `LEGAL_*`, `SUPPORT_EMAIL` and `PRIVACY_EMAIL` filled in, so
      `/terms`, `/privacy` and `/support` show no "[… to be confirmed]".

## Infrastructure

- [ ] **PHP 8.3 or newer** on the servers (typed class constants need it).
- [ ] **Deploy steps:** `composer install --no-dev --optimize-autoloader`
      (this also leaves Telescope out), `npm ci && npm run build`,
      `php artisan migrate --force`, `php artisan optimize`. OPcache on.
- [ ] **Queue workers** run under Supervisor (or Horizon), restarted on every
      deploy (`php artisan queue:restart`), with limits so a leaking worker
      recycles itself:
      `php artisan queue:work redis --tries=3 --max-time=3600 --memory=256`.
      Live notifications, pushes and emails all go through the queue, so at
      least two workers.
- [ ] **Scheduler:** cron runs `php artisan schedule:run` every minute on
      one server. It runs the nightly prunes: notifications over 90 days old,
      chores completed over 30 days ago, and shopping items ticked off over a
      week ago.
- [ ] **Redis** won't silently drop data: sessions, cache and the queue share
      it, so set `maxmemory-policy noeviction` (or move the cache to its own
      Redis with `allkeys-lru`). Turn persistence (AOF) on so queued jobs
      survive a restart.
- [ ] **Reverb** (`php artisan reverb:start`) under Supervisor, behind the
      TLS-terminating proxy (WebSocket upgrade allowed), with the open-file
      limit raised (`ulimit -n 10000` or more) since each connection is a
      file. Browser-facing `VITE_REVERB_HOST`/`PORT`/`SCHEME` point at the
      public `wss://` address and are set *before* `npm run build`. If you
      ever run more than one Reverb server, set `REVERB_SCALING_ENABLED=true`
      so they share messages through Redis.
- [ ] **MySQL:** automated daily backups kept for `LEGAL_BACKUP_RETENTION_DAYS`
      (the Privacy Policy quotes it), and a test restore done once.
- [ ] **Monitoring:** an uptime check on `/up`. Glance at `/status` after
      deploy: it checks the database, cache, queue, Reverb, AI and documents.
- [ ] **HTTPS** everywhere, with HTTP redirecting to HTTPS. Push notifications
      and installing the app both need it.

## Final checks

- [ ] Sign up a brand-new account on the live site: verification email
      arrives, trial starts, a real card subscribes, and the Stripe webhook
      lands (subscription shows as active).
- [ ] Install the app on an Android and an iPhone, turn push on, and get a
      push from another account.
- [ ] Upload and download a document (it should come from the S3 bucket).
