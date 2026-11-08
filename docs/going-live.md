[← README](../README.md)

# If RYH goes live

RYH runs as a personal project, so it isn't set up for production. These are
the steps that would take it there:

- **Error tracking:** [Sentry](https://sentry.io) (`sentry/sentry-laravel`,
  plus `@sentry/vue` for the browser), so exceptions and failed queue jobs
  reach someone instead of only the log. Set `LEGAL_ERROR_TRACKER` so the
  Privacy Policy names it.
- **Uptime and logs:** an uptime check on `/up` and `/status` (e.g. Oh Dear or
  Better Stack), and logs shipped somewhere searchable rather than left on the
  server.
- **Hosting:** something that runs the web server, `queue:work`,
  `schedule:work` and Reverb as separate supervised processes, such as
  Laravel Cloud or Forge, with `php artisan migrate --force` in each deploy.
- **Production settings:** `APP_ENV=production`, `APP_DEBUG=false`,
  `SESSION_SECURE_COOKIE=true`, `DOCUMENTS_DISK=s3`, real Stripe keys and the
  webhook secret from Stripe's dashboard, VAPID keys, and every `LEGAL_*`
  detail filled in so the legal pages have nothing "to be confirmed".
- **Backups:** scheduled database backups and S3 versioning for documents,
  kept as long as `LEGAL_BACKUP_RETENTION_DAYS` says.
- **A full Content-Security-Policy:** the headers sent today stop framing and
  sniffing (`SecurityHeadersMiddleware`); a script policy needs nonces on the
  page shell's inline scripts first.
