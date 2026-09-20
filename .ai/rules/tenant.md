---
paths:
  - app/Http/Controllers/Tenant/ReportController.php
---

# Tenant

## Export CSV/PDF harus menangani nilai triple kontrak v1
Kontrak v1 laba rugi membuat `comparative()['rows'][i]['values'][appId]` berupa triple `{prior,current,ytd}`
(bisa skalar/null untuk sumber legacy), dan `totals[appId]` bisa campur skalar + `laba_rugi_normalized_*`.
Export TIDAK BOLEH memanggil `formatIndonesian(int|float|null)` langsung atas nilai mentah — itu memicu
TypeError 500 pada GET /tenant/reports/export/csv. Gunakan `hasPeriodColumns()` untuk memilih mode kolom:
periodik = 3 kolom per aplikasi (`<id> s.d lalu|periode ini|s.d sekarang`, sel non-triple hanya terisi di
`s.d sekarang`), non-periodik = 1 kolom. Pola sama dipakai `resources/views/reports/comparative.blade.php`
lewat closure `$cellsFor()`/`$valueCell()`: triple dirender tiga baris dalam satu sel, Total Gabungan
dijumlah PER KOLOM. Hanya bentuk sajian yang berubah; angka sumber tidak pernah dihitung ulang/dikarang.
