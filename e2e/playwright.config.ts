import { defineConfig } from '@playwright/test';

export default defineConfig({
  testDir: '.',
  testMatch: /deep-functional\.spec\.js$/,
  fullyParallel: false,
  workers: 1,
  retries: 0,
  timeout: 90_000,
  globalSetup: './global-setup.ts',
  globalTeardown: './global-teardown.ts',
  outputDir: './artifacts/test-results',
  use: {
    baseURL: process.env.E2E_BASE_URL ?? 'http://127.0.0.1:8123',
    screenshot: 'only-on-failure',
    trace: 'retain-on-failure',
    video: 'retain-on-failure',
  },
  reporter: [['list'], ['html', { outputFolder: './artifacts/html-report', open: 'never' }]],
});
