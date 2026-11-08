[← README](../README.md)

# Checks and tests

Run all of these before merging a change. CI (`.github/workflows`) runs the
same on every push and pull request: the `linter` workflow (Pint, PHPStan,
Prettier, ESLint, Vitest) and the `tests` workflow (generated types, build,
type check, PHPUnit, and the Playwright suite). The list is kept in step with
CLAUDE.md's.

```bash
./vendor/bin/sail bin pint --test
./vendor/bin/sail bin phpstan analyse            # level 8, plus the layer rules in tests/PHPStan
./vendor/bin/sail npm run format:check
./vendor/bin/sail npx eslint .
./vendor/bin/sail artisan typescript:transform   # then nothing in resources/js/types may change
./vendor/bin/sail npx vue-tsc --noEmit
./vendor/bin/sail npm test                       # Vitest: composables and lib helpers
./vendor/bin/sail artisan test                   # includes the route, naming and query-budget tests
./vendor/bin/sail npm run build                  # the e2e server serves this build
npm run e2e                                      # Playwright from the Mac, with Sail up; includes axe
```

## End-to-end tests

Playwright drives the real UI through the main user journeys (`e2e/*.spec.ts`):
sign-up with email verification, chores, shopping, meals, calendar, roles,
notifications, joining a household, budget and documents, plus a phone-sized
navigation check.

```bash
npx playwright install chromium    # once
npm run e2e                        # or npm run e2e:ui to watch it
```

With Sail up, a run serves the app on <http://localhost:8091> from inside
`ryh-app` with `APP_ENV=e2e` (`.env.e2e`), and empties the separate
`testing_e2e` database and documents disk first, so your dev data is never
touched. Each test makes its own Parker household through a test-only route
(`routes/e2e.php`, loaded only in e2e) and signs its members in directly
(`e2e/support/fixtures.ts`), so tests share nothing and run in parallel: four
workers locally, two in CI. Registration links are read from Mailpit, and AI
meal planning is a fixed fake (`App\Ai\Fakes\E2eMealPlanFake`), so no API key
or paid call is needed. CI's `e2e` job runs the same suite and uploads the
report and traces when it fails. `e2e/accessibility.spec.ts` runs axe on each
main page and fails on serious WCAG 2.1 A/AA problems, colour contrast aside.

Build the frontend in Sail (`sail npm run build`) before a run: the e2e server
serves `public/build`, and a stale build tests old code.

Run Playwright from your Mac, but install npm packages with `sail npm install`:
`node_modules` is shared with the Linux container, and a host `npm install`
strips the container's native Rollup build, which breaks Vite.
