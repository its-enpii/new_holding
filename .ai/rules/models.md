---
paths:
  - 'app/Models/**'
---

# Models

## applications schema: no code/instance_type, base_url required
Aplikasi subsidiary (mis. akubumdes) adalah baris di tabel `applications`.
Kolom nyata: name, slug (unique), description, icon_path, base_url (NOT NULL),
has_financial_report, is_active. TIDAK ada `code` maupun `instance_type` — keduanya
di luar $fillable sehingga Model::create() diam-diam membuangnya lalu MySQL strict
gagal di "Field 'base_url' doesn't have a default value".
Identitas instance/sub-tenant hidup di pivot `tenant_applications`
(sub_tenant_code, instance_url, api_secret), bukan di Application.
Key unik yang bisa dipakai seed/stub: tenants.slug, applications.slug, users.email.
Pivot tidak punya unique (tenant_id, application_id) — gunakan updateOrCreate.
Helper smoke SSO ada di /root/tasks/akubumdes-sso-bus/smoke-stub/emit-token.php
(di luar repo, jangan di-commit) dan WAJIB idempotent.
