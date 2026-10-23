import { defineConfig, devices } from '@playwright/test';

const baseURL = 'http://localhost:8091';

// CI uses the Google Chrome the GitHub runner already has, so no browser (and none of its
// system libraries) is installed per run; locally it's Playwright's own Chromium.
const channel = process.env.CI ? 'chrome' : undefined;

/**
 * End-to-end journeys against a separate server (port 8091, `.env.e2e`, the
 * `testing_e2e` database) so a run never touches dev data. Each test makes its
 * own household (e2e/support/fixtures.ts), so they run in parallel; the server
 * answers several at once (PHP_CLI_SERVER_WORKERS in .env.e2e). CI's runner has
 * two cores, shared with MySQL and the server.
 */
export default defineConfig({
    testDir: './e2e',
    globalSetup: './e2e/global-setup.ts',
    fullyParallel: true,
    workers: process.env.CI ? 2 : 4,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 1 : 0,
    reporter: process.env.CI ? [['list'], ['html', { open: 'never' }]] : 'list',
    use: {
        baseURL,
        channel,
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
    },
    projects: [
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
            testIgnore: /mobile\.spec\.ts/,
        },
        {
            name: 'mobile',
            use: { ...devices['Pixel 7'] },
            testMatch: /mobile\.spec\.ts/,
        },
    ],
    webServer: {
        command: 'npm run e2e:serve',
        url: `${baseURL}/login`,
        reuseExistingServer: !process.env.CI,
        timeout: 120_000,
    },
});
