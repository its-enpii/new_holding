---
paths:
  - 'app/Http/Controllers/**'
  - 'resources/js/**'
---

# Redirect lintas-origin & SSO handoff

Setiap redirect ke URL di luar origin holding (khususnya `{instance_url}/auth/holding?token=...`
hasil `SsoTokenService::create()`) WAJIB memakai `Inertia::location($url)` dari controller,
BUKAN `redirect()->away($url)` mentah, bila pemicunya bisa berupa request Inertia (XHR).

Alasan (diverifikasi via browser nyata + vendor source):
- `router.post()`/Inertia mengirim request sebagai XHR. `redirect()->away()` lintas-origin
  membuat client menembak ulang XHR GET ke origin tujuan -> CORS preflight gagal
  (`net::ERR_FAILED`), user stuck, dan token SSO tertinggal di bus redis sampai TTL habis.
- `Inertia::location()` (vendor `ResponseFactory.php:307`): request Inertia -> 409 +
  header `x-inertia-location` -> client (`@inertiajs/core` `index.js:2102`) melakukan
  `window.location` (top-level navigation, tanpa CORS). Request non-Inertia -> 302 biasa.
- Return type controller jadi `Symfony\Component\HttpFoundation\Response`.
- Console log "Failed to load resource: 409 (Conflict)" saat handoff = benign, mekanisme resmi.

Jalur superadmin (`Admin/TenantSsoController`) dijamin aman karena pemicunya `<a :href>`
(top-level navigation asli), bukan tombol XHR. Bila kelak pemicunya diubah jadi
`router.post()`, aturan yang sama berlaku.

PHPUnit `assertRedirect` TIDAK menangkap kelas bug ini (hanya membaca header). Gate
sebenarnya adalah E2E browser: lihat /root/testing-sandbox/holding-owner-sso-e2e.cjs
(login owner -> klik Quick Access -> landing http://127.0.0.1:8091/dashboard terautentikasi
-> key bus terkonsumsi).
