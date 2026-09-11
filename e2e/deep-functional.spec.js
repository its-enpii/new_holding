import { expect, test } from '@playwright/test';
import {
  escapeSql,
  login,
  logout,
  openSidebar,
  row,
  searchTable,
  sqliteExec,
  sqliteRows,
  sqliteValue,
} from './helpers.js';

const timestamp = `${Date.now()}`;

function insertTenant(name) {
  const slug = `${timestamp}-${name.toLowerCase().replace(/[^a-z]+/g, '-')}`;
  const email = slug + '@qa.test';
  sqliteExec(`
    INSERT INTO tenants (name, slug, domain, email, is_active, created_at, updated_at)
    VALUES ('${escapeSql(name)}', '${escapeSql(slug)}', null, '${escapeSql(email)}', 1, datetime('now'), datetime('now'))
  `);
  return sqliteValue(`SELECT id FROM tenants WHERE name = '${escapeSql(name)}'`);
}

function insertLicense(tenantId, label, expiredAt = null, baseUrl = 'http://127.0.0.1:8124') {
  const slug = `${timestamp}-${label.toLowerCase().replace(/[^a-z]+/g, '-')}`;
  sqliteExec(`
    INSERT INTO applications (name, slug, description, icon_path, base_url, has_financial_report, is_active, created_at, updated_at)
    VALUES ('${escapeSql(label)}', '${escapeSql(slug)}', 'E2E', 'widgets', '${escapeSql(baseUrl)}', 1, 1, datetime('now'), datetime('now'))
  `);
  const applicationId = sqliteValue(`SELECT id FROM applications WHERE name = '${escapeSql(label)}'`);
  const secret = `e2e-${Math.random().toString(36).slice(2, 12)}`;
  sqliteExec(`
    INSERT INTO tenant_applications
      (tenant_id, application_id, label, instance_url, api_secret, is_active, activated_at, expired_at, created_at, updated_at)
    VALUES
      (${tenantId}, ${applicationId}, '${escapeSql(label)}', '${escapeSql(baseUrl)}', '${escapeSql(secret)}', 1, datetime('now'), ${expiredAt ? `'${expiredAt}'` : 'null'}, datetime('now'), datetime('now'))
  `);
  return sqliteValue(`SELECT id FROM tenant_applications WHERE tenant_id = ${tenantId} AND application_id = ${applicationId}`);
}

test.beforeEach(() => {
  sqliteExec('DELETE FROM sessions');
  sqliteExec('DELETE FROM cache');
  sqliteExec('DELETE FROM cache_locks');
});

test('auth protects the dashboard and supports logout', async ({ page }) => {
  await page.goto('/login');
  await page.getByLabel('Email').fill('admin@holding.local');
  await page.getByRole('textbox', { name: 'Password' }).fill('wrong-password');
  await page.getByRole('button', { name: 'Masuk' }).click();
  await expect(page).toHaveURL(/\/login$/);
  await expect(page.getByText(/Kredensial|tidak valid|tidak cocok/i)).toBeVisible();

  await login(page, 'admin@holding.local');
  await expect(page).toHaveURL(/\/dashboard$/);
  await expect(page.getByRole('heading', { name: /dashboard superadmin/i })).toBeVisible();

  await logout(page);
  await expect(page).toHaveURL(/\/login$/);
  await page.goto('/dashboard');
  await expect(page).toHaveURL(/\/login$/);
});

test('superadmin performs tenant CRUD with database evidence', async ({ page }) => {
  const tenantName = `QA Tenant ${timestamp}`;
  await login(page, 'admin@holding.local');
  await openSidebar(page, 'Tenants');
  await page.getByRole('link', { name: 'Tambah tenant' }).click();
  await page.getByLabel('Nama').fill(tenantName);
  await page.getByLabel('Slug').fill(`qa-tenant-${timestamp}`);
  await page.getByLabel('Email').fill(`qa-tenant-${timestamp}@qa.test`);
  await page.getByRole('switch', { name: 'Status' }).locator('..').click();
  await page.getByRole('button', { name: 'Buat Tenant' }).click();

  await expect(page).toHaveURL(/\/admin\/tenants$/);
  await searchTable(page, tenantName);
  await expect(await row(page, tenantName)).toBeVisible();
  expect(sqliteRows(`SELECT id FROM tenants WHERE name LIKE 'QA Tenant%'`)).toHaveLength(1);
  const tenantId = sqliteValue(`SELECT id FROM tenants WHERE name = '${escapeSql(tenantName)}'`);

  const updatedName = `${tenantName} Edited`;
  await page.getByRole('link', { name: `Edit ${tenantName}` }).click();
  await page.getByLabel('Nama').fill(updatedName);
  await page.getByRole('button', { name: 'Simpan' }).click();
  await expect(page).toHaveURL(/\/admin\/tenants$/);
  await searchTable(page, updatedName);
  await expect(await row(page, updatedName)).toBeVisible();
  expect(sqliteValue(`SELECT name FROM tenants WHERE id = ${tenantId}`)).toBe(updatedName);

  await page.getByRole('button', { name: `Hapus ${updatedName}` }).click();
  const confirmDialog = page.getByRole('dialog');
  await expect(confirmDialog).toBeVisible();
  await confirmDialog.getByRole('button', { name: 'Hapus' }).click();
  await expect(page.locator('tr', { hasText: updatedName })).toHaveCount(0);
  expect(sqliteRows(`SELECT id FROM tenants WHERE id = ${tenantId}`)).toHaveLength(0);
});

test('superadmin creates and toggles an application master', async ({ page }) => {
  const appName = `QA App ${timestamp}`;
  await login(page, 'admin@holding.local');
  await openSidebar(page, 'Master Aplikasi');
  await page.getByRole('link', { name: 'Tambah aplikasi' }).click();
  await page.getByLabel('Nama').fill(appName);
  await page.getByLabel('Slug').fill(`qa-app-${timestamp}`);
  await page.getByLabel('Base URL').fill('https://qa-app.test');
  await page.getByRole('button', { name: 'Buat Aplikasi' }).click();

  await expect(page).toHaveURL(/\/admin\/applications$/);
  await searchTable(page, appName);
  await expect(await row(page, appName)).toBeVisible();
  const applicationId = sqliteValue(`SELECT id FROM applications WHERE name = '${escapeSql(appName)}'`);
  expect(applicationId).not.toBeNull();
  expect(sqliteValue(`SELECT is_active FROM applications WHERE id = ${applicationId}`)).toBe(1);

  const applicationRow = await row(page, appName);
  await applicationRow.getByRole('button', { name: 'Nonaktifkan' }).click();
  await expect.poll(() => sqliteValue(`SELECT is_active FROM applications WHERE id = ${applicationId}`)).toBe(0);
  await applicationRow.getByRole('button', { name: 'Aktifkan' }).click();
  await expect.poll(() => sqliteValue(`SELECT is_active FROM applications WHERE id = ${applicationId}`)).toBe(1);
});

test('tenant license supports assignment, secret rotation, and connection checks', async ({ page }) => {
  await login(page, 'admin@holding.local');
  await openSidebar(page, 'Tenants');
  await page.getByRole('link', { name: 'Lisensi Aplikasi BUMDesma Contoh' }).click();
  await page.getByRole('link', { name: 'Assign Aplikasi' }).click();
  await page.getByLabel('Pilih Aplikasi Master').selectOption({ label: 'Sistem Simpan Pinjam (simpan-pinjam)' });
  await page.getByLabel('URL Instance Aplikasi').fill('https://sp.bumdesma.test');
  await page.getByRole('button', { name: 'Assign Aplikasi' }).click();
  await expect(page).toHaveURL(/\/admin\/tenants\/1\/applications\/\d+$/);

  const licenseId = page.url().match(/\/applications\/(\d+)$/)[1];
  const originalSecret = sqliteValue(`SELECT api_secret FROM tenant_applications WHERE id = ${licenseId}`);
  expect(originalSecret).toHaveLength(40);

  await page.goto('/admin/tenants/1/applications');
  await expect(page.getByText('Regenerate API Secret')).toBeHidden();
  const licenseRow = page.locator('tr', { hasText: 'Sistem Simpan Pinjam' }).first();
  await licenseRow.getByRole('button', { name: /Regenerate secret/i }).click();
  const confirmDialog = page.locator('[role="dialog"], .modal, [aria-modal="true"]').last();
  await expect(confirmDialog).toBeVisible();
  await Promise.all([
    page.waitForResponse((response) =>
      response.url().includes(`/admin/tenants/1/applications/${licenseId}/regenerate-secret`)
    ),
    confirmDialog.getByRole('button', { name: 'Regenerate Secret' }).click(),
  ]);
  await expect.poll(() => sqliteValue(`SELECT api_secret FROM tenant_applications WHERE id = ${licenseId}`)).not.toBe(originalSecret);

  sqliteExec(`
    UPDATE tenant_applications
    SET instance_url = 'http://127.0.0.1:8124', api_secret = 'e2e-subsidiary-secret'
    WHERE id = ${licenseId}
  `);
  await page.goto(`/admin/tenants/1/applications/${licenseId}`);
  await Promise.all([
    page.waitForResponse((response) =>
      response.url().includes(`/admin/tenants/1/applications/${licenseId}/test-connection`)
    ),
    page.getByRole('button', { name: 'Test Koneksi' }).click(),
  ]);
  await expect(page.getByText('Terhubung')).toBeVisible();
  expect(sqliteValue(`SELECT connection_status FROM tenant_applications WHERE id = ${licenseId}`)).toBe('connected');

  sqliteExec(`UPDATE tenant_applications SET api_secret = 'wrong-secret' WHERE id = ${licenseId}`);
  await page.reload();
  await Promise.all([
    page.waitForResponse((response) =>
      response.url().includes(`/admin/tenants/1/applications/${licenseId}/test-connection`)
    ),
    page.getByRole('button', { name: 'Test Koneksi' }).click(),
  ]);
  await expect(page.getByText('Secret/URL salah')).toBeVisible();
  expect(sqliteValue(`SELECT connection_status FROM tenant_applications WHERE id = ${licenseId}`)).toBe('auth_error');
});

test('tenant owner manages staff while role boundaries stay enforced', async ({ page }) => {
  const staffName = `QA Staff ${timestamp}`;
  await login(page, 'owner@tenant.test');
  await openSidebar(page, 'Manajemen Staff');
  await page.getByRole('link', { name: 'Tambah Staff' }).click();
  await page.getByLabel('Nama Lengkap').fill(staffName);
  await page.getByLabel('Email Pengguna').fill(`qa-staff-${timestamp}@tenant.test`);
  await page.getByLabel('Password Awal').fill('password123');
  await page.getByRole('button', { name: 'Tambah Staff' }).click();
  await expect(page).toHaveURL(/\/tenant\/staff$/);
  await searchTable(page, staffName);
  await expect(await row(page, staffName)).toBeVisible();
  const staffId = sqliteValue(`SELECT id FROM users WHERE name = '${escapeSql(staffName)}'`);
  expect(sqliteValue(`SELECT role FROM users WHERE id = ${staffId}`)).toBe('tenant_staff');
  expect(sqliteValue(`SELECT is_active FROM users WHERE id = ${staffId}`)).toBe(1);

  const staffRow = await row(page, staffName);
  await staffRow.getByRole('button', { name: 'Nonaktifkan' }).click();
  await expect.poll(() => sqliteValue(`SELECT is_active FROM users WHERE id = ${staffId}`)).toBe(0);
  await staffRow.getByRole('button', { name: 'Aktifkan' }).click();
  await expect.poll(() => sqliteValue(`SELECT is_active FROM users WHERE id = ${staffId}`)).toBe(1);

  await logout(page);
  await login(page, 'admin@holding.local');
  const response = await page.request.get('/admin/tenants');
  expect(response.status()).toBe(200);
  await expect(page.locator('body')).not.toContainText('Akses ditolak');

  await logout(page);
  await login(page, 'owner@tenant.test');
  await page.goto('/admin/activity-logs');
  await expect(page.getByText('Akses ditolak')).toBeVisible();

  await page.goto('/tenant/staff');

  const initialUpdatedAt = sqliteValue(`SELECT updated_at FROM users WHERE id = ${staffId}`);
  await page.getByRole('button', { name: `Reset password ${staffName}` }).click();
  const modal = page.locator('[role="dialog"]');
  await expect(modal).toBeVisible();
  await modal.getByLabel('Password Baru', { exact: true }).fill('newpassword123');
  await modal.getByLabel('Konfirmasi Password Baru').fill('newpassword123');
  await modal.getByRole('button', { name: 'Simpan Password Baru' }).click();
  await expect(modal).toBeHidden();
  await expect.poll(() => sqliteValue(`SELECT updated_at FROM users WHERE id = ${staffId}`)).not.toBe(initialUpdatedAt);
});

test('tenant reports render subsidiary data and export CSV', async ({ page }) => {
  const licenseId = sqliteValue(`
    SELECT id FROM tenant_applications
    WHERE tenant_id = 1 AND label = 'Instance Utama'
  `);
  sqliteExec(`
    UPDATE tenant_applications
    SET instance_url = 'http://127.0.0.1:8124', api_secret = 'e2e-subsidiary-secret', is_active = 1,
        expired_at = datetime('now', '+1 year')
    WHERE id = ${licenseId}
  `);

  await login(page, 'owner@tenant.test');
  await openSidebar(page, 'Laporan');
  await page.getByRole('checkbox', { name: 'Instance Utama' }).check();
  await expect(page.getByRole('button', { name: 'Tampilkan' })).toBeEnabled();
  await Promise.all([
    page.waitForResponse((response) =>
      response.url().includes('/tenant/reports/view') && response.request().method() === 'GET'
    ),
    page.getByRole('button', { name: 'Muat Ulang' }).click(),
  ]);
  await expect(page.getByText('Kas dan Setara Kas')).toBeVisible();

  const responsePromise = page.waitForResponse((response) =>
    response.url().includes('/tenant/reports/export/csv') && response.request().method() === 'GET'
  );
  await page.getByRole('link', { name: 'CSV', exact: true }).click();
  const response = await responsePromise;
  expect(response.status()).toBe(200);
  expect(response.headers()['content-type']).toContain('text/csv');
});

test('application routes enforce rate limits and branded errors', async ({ page, browser }) => {
  await login(page, 'owner@tenant.test');
  const statuses = [];
  for (let index = 0; index < 65; index += 1) {
    const response = await page.request.get('/dashboard');
    statuses.push(response.status());
  }
  expect(statuses.slice(-5).every((status) => status === 429)).toBe(true);
  expect(statuses.at(-1)).toBe(429);

  const response = await page.request.get('/this-page-does-not-exist');
  expect(response.status()).toBe(404);
  await page.goto('/this-page-does-not-exist');
  await expect(page.getByText('Halaman tidak ditemukan')).toBeVisible();
});

test('expired licenses and foreign tenant applications are inaccessible', async ({ page }) => {
  const foreignTenantId = insertTenant(`QA Foreign Tenant ${timestamp}`);
  const foreignLicenseId = insertLicense(foreignTenantId, `QA Foreign App ${timestamp}`);
  insertLicense(1, `QA Expired App ${timestamp}`, '2020-01-01 00:00:00');

  await login(page, 'owner@tenant.test');
  const foreignResponse = await page.request.get(`/admin/tenants/${foreignTenantId}/applications/${foreignLicenseId}`);
  expect(foreignResponse.status()).toBe(403);
  await page.goto(`/admin/tenants/${foreignTenantId}/applications/${foreignLicenseId}`);
  await expect(page.getByText('Akses ditolak')).toBeVisible();

  await page.goto('/dashboard');
  const card = page.locator('div.rounded-lg.border').filter({ hasText: `QA Expired App ${timestamp}` });
  await expect(card.getByText('Masa aktif lisensi telah habis.')).toBeVisible();
  await expect(card.getByRole('button')).toHaveCount(0);
});
