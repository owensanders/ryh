import { defineConfig, devices } from '@playwright/test';

const baseURL = 'http://localhost:8091';

/**
 * End-to-end journeys against a separate server (port 8091, `.env.e2e`, the
 * `testing_e2e` database) so a run never touches dev data. Journeys share one
 * seeded database, so they run one at a time.
 */
export default defineConfig({
    testDir: './e2e',
    globalSetup: './e2e/global-setup.ts',
    fullyParallel: false,
    workers: 1,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 1 : 0,
    reporter: process.env.CI ? [['list'], ['html', { open: 'never' }]] : 'list',
    use: {
        baseURL,
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
    },
    projects: [
        { name: 'setup', testMatch: /auth\.setup\.ts/ },
        {
            name: 'chromium',
            use: { ...devices['Desktop Chrome'] },
            dependencies: ['setup'],
            testIgnore: /mobile\.spec\.ts/,
        },
        {
            name: 'mobile',
            use: { ...devices['Pixel 7'] },
            dependencies: ['setup'],
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
