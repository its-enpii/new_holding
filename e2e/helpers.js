import { spawnSync } from 'node:child_process';
import path from 'node:path';
import { expect } from '@playwright/test';

export const repoRoot = path.resolve(import.meta.dirname, '..');
export const dbPath = path.join(repoRoot, 'database/database.sqlite');
const sqliteArguments = ['-cmd', '.timeout 5000'];

export function sqliteRows(query) {
  const result = spawnSync('/opt/android-sdk/platform-tools/sqlite3', [...sqliteArguments, '-json', dbPath, query], {
    encoding: 'utf8',
  });
  if (result.status !== 0) throw new Error(result.stderr);
  return JSON.parse(result.stdout || '[]');
}

export function sqliteValue(query) {
  const rows = sqliteRows(query);
  return rows.length ? Object.values(rows[0])[0] : null;
}

export function sqliteExec(query) {
  const result = spawnSync('/opt/android-sdk/platform-tools/sqlite3', [...sqliteArguments, dbPath, query], {
    encoding: 'utf8',
  });
  if (result.status !== 0) throw new Error(result.stderr);
  return result.stdout;
}

export function escapeSql(value) {
  return String(value).replace(/'/g, "''");
}

export async function login(page, email, password = 'password') {
  await page.goto('/login');
  await page.getByLabel('Email').fill(email);
  await page.getByRole('textbox', { name: 'Password' }).fill(password);
  await page.getByRole('button', { name: 'Masuk' }).click();
  await page.waitForLoadState();
  await expect(page.getByRole('button', { name: 'Keluar' })).toBeVisible();
}

export async function logout(page) {
  await page.getByRole('button', { name: 'Keluar' }).click();
  await expect(page).toHaveURL(/\/login$/);
}

export async function openSidebar(page, label) {
  await page.getByRole('link', { name: label, exact: true }).first().click();
}

export async function fillInputByLabel(page, label, value) {
  await page.getByLabel(label, { exact: true }).fill(value);
}

export async function selectByLabel(page, label, optionLabel) {
  const control = page.getByLabel(label, { exact: true });
  await control.selectOption({ label: optionLabel });
}

export async function toggleSwitchByLabel(page, label) {
  const field = page.locator('label').filter({ has: page.getByRole('switch', { name: label }) });
  await field.getByRole('switch').click();
}

export async function setDatePickerValue(page, label, day, monthName, year) {
  const trigger = page.locator('label', { hasText: label }).getByRole('button');
  await trigger.click();
  const calendar = page.locator('[role="dialog"][aria-modal="false"]').last();
  await expect(calendar).toBeVisible();
  let guard = 0;
  while (!(await calendar.getByText(`${monthName} ${year}`, { exact: true }).isVisible())) {
    await calendar.getByRole('button', { name: 'Bulan sebelumnya' }).click();
    guard += 1;
    expect(guard).toBeLessThan(18);
  }
  await calendar
    .locator('[role="gridcell"]', { hasText: new RegExp(`^${day}$`) })
    .filter({ hasNot: page.locator('[disabled]') })
    .click();
}

export function searchTable(page, value) {
  return page.getByPlaceholder('Cari data...').fill(value);
}

export async function row(page, text) {
  return page.locator('tr', { hasText: text }).first();
}
