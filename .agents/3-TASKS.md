# Tasks — SLAMET

| ID | Judul | Modul | Prioritas | Status | Dependensi |
|---|---|---|---|---|---|
| T-01 | Copy template Sikarto + composer/npm install + .env (APP_NAME=SLAMET, APP_URL /slamet/public, DB slamet) | Setup | High | Done | — |
| T-02 | Register SSO client slamet di sekali_login + config/services | Setup | High | Done | T-01 |
| T-03 | Strip domain Sikarto (migrations/models/controllers/pages test/report) | Setup | High | Done | T-01 |
| T-04 | Migrations categories/items/orders/order_items + index | DB | High | Done | T-03 |
| T-05 | Models Category/Item/Order/OrderItem + relasi + availableToday() | DB | High | Done | T-04 |
| T-06 | Seeder RolesAndPermissions (admin/kasir/karyawan) + demo data | DB | High | Done | T-04 |
| T-07 | AuthController + AdminUserController adapt role SLAMET (tanpa factory) | Auth | High | Done | T-03 |
| T-08 | Routes web.php (menu/orders/kasir/items/stock/reports/masters) | Routing | High | Done | T-07 |
| T-09 | Controllers: Master(category), Item(+foto), Stock, Order, Payment, Dashboard, Report | Backend | High | Done | T-08 |
| T-10 | AppLayout adapt (bottom nav Beranda/Menu/Pesanan/Kasir/Laporan, search, FAB) | Frontend | High | Done | T-08 |
| T-11 | Halaman katalog Menu/Catalog + composable cart.ts | Frontend | High | Done | T-09 |
| T-12 | Halaman Checkout + Orders (index + show + QR) | Frontend | High | Done | T-11 |
| T-13 | Halaman Kasir/Payment (cari nota + konfirmasi lunas) | Frontend | High | Done | T-12 |
| T-14 | Halaman Items CRUD + Stock/Today + Reports + Dashboard | Frontend | Mid | Done | T-10 |
| T-15 | Feature tests OrderTest + UserApprovalTest | Test | High | Done | T-09 |
| T-16 | Pipeline docs .agents/ + AGENTS.md rewrite | Doc | Low | Done | — |

## Verifikasi
- `php artisan test` — 12 passed
- `npm run typecheck` — bersih
- `npm run build` — sukses (bundle 848 kB, wajar Varlet full import)
- `vendor/bin/pint` — rapi
- Alur manual: login → katalog → checkout → nota+QR → kasir lunas → stok turun → laporan
