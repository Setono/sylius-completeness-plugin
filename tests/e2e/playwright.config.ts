import { defineConfig, devices } from '@playwright/test';

/**
 * The app under test is expected to already be running and set up (DB + fixtures + built assets).
 * Point at it with PLAYWRIGHT_BASE_URL; locally the test app's `symfony server` on :8033 is the default.
 */
const baseURL = process.env.PLAYWRIGHT_BASE_URL ?? 'http://127.0.0.1:8033';

export default defineConfig({
    testDir: './specs',
    // the specs share one database and create records, so run them serially
    fullyParallel: false,
    workers: 1,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 1 : 0,
    reporter: process.env.CI ? [['github'], ['html', { open: 'never' }]] : [['list']],
    use: {
        baseURL,
        trace: 'on-first-retry',
        screenshot: 'only-on-failure',
    },
    projects: [
        { name: 'setup', testMatch: /auth\.setup\.ts/ },
        {
            name: 'chromium',
            testIgnore: /auth\.setup\.ts/,
            use: { ...devices['Desktop Chrome'], storageState: '.auth/admin.json' },
            dependencies: ['setup'],
        },
    ],
});
