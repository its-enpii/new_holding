# E2E & Bug Resolution Report

## 1. Status Final

- **E2E Playwright**: 8 / 8 passed (0 failed, 0 skipped/fixme)
- **PHPUnit**: 62 / 62 passed (481 assertions) — baseline 60 tests preserved + 2 test baru
- **Frontend Build (`npm run build`)**: Sukses tanpa error
- **Code Style (`vendor/bin/pint`)**: Passed

---

## 2. Investigasi & Akar Masalah Misteri 302 (Tenant Reports)

### Akar Masalah (Root Cause)
Pada saat user menekan tombol "Tampilkan" atau "Muat Ulang" di halaman `/tenant/reports`, Inertia mengirimkan request HTTP GET ke `/tenant/reports/view` dengan query parameter `force` bernilai string `"false"` atau `"true"` (misal: `/tenant/reports/view?apps[]=1&type=balance_sheet&year=2026&month=&force=false`).

Di backend (`ReportController::validateReportQuery` dan `Admin\ReportController::show`), query divalidasi menggunakan aturan:
```php
'force' => ['nullable', 'boolean']
```
Dalam framework Laravel, validator rule `'boolean'` hanya menerima boolean riil (`true`/`false`) atau integer/string numerik (`1`, `0`, `'1'`, `'0'`). Aturan ini **menolak** string `"true"` dan `"false"` yang dikirim via URL query parameter standar HTTP GET dari browser.

Akibatnya:
1. Validator melempar `Illuminate\Validation\ValidationException`.
2. Karena request datang dari browser berbasis sesi dengan referer `/tenant/reports`, handler exception Laravel mengembalikan respons **HTTP 302 Redirect** kembali ke referer (`/tenant/reports`).
3. Request via `page.request.get()` atau test tanpa string `"false"` lolos karena tipe data tidak berbenturan dengan rule `boolean`.

### Solusi
- Mengubah validasi `'force' => ['nullable']` pada `App\Http\Controllers\Tenant\ReportController` dan `App\Http\Controllers\Admin\ReportController`.
- Menggunakan parsing boolean bawaan Laravel `$request->boolean('force')` yang mendukung representasi boolean URL (`"true"`, `"false"`, `"1"`, `"0"`, dsb).
- Menambahkan penanganan aman untuk `month` bernilai string kosong.
- Membersihkan pemanggilan klik duplikat pada E2E spec.

---

## 3. Daftar Bug yang Diperbaiki

### BUG-1: Modal Reset Password Staff Tidak Pernah Terbuka
- **File**: `resources/js/Pages/Tenant/Staff/Index.vue:186`
- **Penyebab**: Komponen `AppModal` mendefinisikan two-way binding menggunakan `defineModel()` (`v-model`), namun dipanggil dengan `:open="resetModalOpen"`. Hal serupa juga ditemukan pada `resources/js/Pages/Admin/ActivityLogs/Index.vue:163`.
- **Perbaikan**: Mengubah binding menjadi `<AppModal v-model="resetModalOpen" ...>` dan `<AppModal v-model="metadataModalOpen" ...>`.
- **E2E Spec**: Menghapus `test.fixme` di `e2e/deep-functional.spec.js` dan memulihkan alur pengisian form reset password, submit, hingga `updated_at` pengguna terverifikasi berubah di database.

### BUG-3: Error 500 Dashboard Saat Ada Lisensi Expiring ≤ 7 Hari
- **File**: `app/Http/Controllers/DashboardController.php:35-39`
- **Penyebab**: `$expiringLicenses` adalah instance `Illuminate\Database\Eloquent\Collection`. Saat di-`map(...)` menghasilkan array, pemanggilan `->merge(...)` pada Eloquent Collection memanggil `getKey()` pada setiap elemen array, memicu fatal error `Call to a member function getKey() on array`.
- **Perbaikan**: Mengonversi hasil mapping koleksi menjadi base array/collection `collect([...$expiringLicenses->map(...)->all(), ...$expiredLicenses->map(...)->all()])->take(5)->values()`.
- **Feature Test**: Menambahkan test `test_superadmin_can_view_dashboard_with_expiring_license` di `tests/Feature/DashboardTest.php`.

### BUG-Reports (Misteri 302): Validasi Boolean Query Parameter
- **File**: `app/Http/Controllers/Tenant/ReportController.php:171-182` dan `app/Http/Controllers/Admin/ReportController.php:40-52`
- **Perbaikan**: Mengganti rule validasi `force` dan parsing query parameter menggunakan `$request->boolean('force')`.
- **Feature Test**: Menambahkan `test_tenant_report_view_accepts_string_boolean_query_parameters` di `tests/Feature/SubsidiaryReportTest.php`.

---

## 4. Bukti Database & Verifikasi

### Bukti Operasi Reset Password Staff (BUG-1)
- Data staff dibuat via E2E: role `tenant_staff`, `is_active = 1`.
- Reset password via dialog `[role="dialog"]` memicu POST `/tenant/staff/{id}/reset-password`.
- Database assertion `sqliteValue("SELECT updated_at FROM users WHERE id = ...")` membuktikan timestamp `updated_at` berhasil terupdate.

### Bukti Notifikasi & License Alert (BUG-3)
- Lisensi aktif dengan `expired_at = now() + 3 hari` diproses dashboard superadmin tanpa exception 500, menghasilkan response 200 dan payload `licenseAlerts.items` dengan status `expiring`.

### Bukti Report & Cache Subsidiary
- Pemanggilan laporan `/tenant/reports/view` memuat data konsolidasi neraca dari subsidiary mock server (`http://127.0.0.1:8124`), merender baris `Kas dan Setara Kas`, mengisi tabel `report_caches`, serta mencatat aktivitas `view_report` dan `export_report` pada tabel `activity_logs`.
- Export CSV `/tenant/reports/export/csv` berhasil mengembalikan HTTP 200 dengan `Content-Type: text/csv`.

---

## 5. Ringkasan File yang Diubah

1. `resources/js/Pages/Tenant/Staff/Index.vue` — Fix `v-model` pada `AppModal`
2. `resources/js/Pages/Admin/ActivityLogs/Index.vue` — Fix `v-model` pada `AppModal`
3. `app/Http/Controllers/DashboardController.php` — Fix Eloquent Collection merge array items
4. `app/Http/Controllers/Tenant/ReportController.php` — Fix validasi query param boolean `force`
5. `app/Http/Controllers/Admin/ReportController.php` — Fix validasi query param boolean `force`
6. `tests/Feature/DashboardTest.php` — Feature test dashboard dengan lisensi expiring
7. `tests/Feature/SubsidiaryReportTest.php` — Feature test validasi query param string boolean
8. `e2e/deep-functional.spec.js` — Pemulihan alur reset password E2E dan sinkronisasi laporan
9. `e2e/REPORT.md` — Laporan dokumentasi investigasi dan hasil verifikasi
