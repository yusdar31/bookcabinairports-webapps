# Changelog - Bookcabin Project

Seluruh modifikasi yang dilakukan oleh *AI Assistant* dicatat di bawah ini agar Sinkron dengan pengerjaan manual oleh User via *Opencode*.

## [Unreleased] - Phase 3 (Laravel & Backend Init)

### Dokumentasi Infrastruktur
- [x] Memperbarui dan menyusun `docs/architecture.md` beserta penjelasan visual arsitektur AWS Free-tier menggunakan sintaks diagram Mermaid agar terbaca apik.

### Inisialisasi Laravel (Fase 3)
- [x] Install Laravel 11 via Docker Composer ke `app/`
- [x] Buat `app/.env` dengan koneksi ke RDS MySQL (`bookcabin-free-tier-db...rds.amazonaws.com`)
- [x] Migration `0001_01_01_000000_create_users_table.php` — tabel users + RBAC (4 role), sessions, password_reset_tokens
- [x] Migration `2026_04_17_000001_create_outlets_table.php` — tabel outlets (gerai F&B bandara)
- [x] Migration `2026_04_17_000002_create_rooms_table.php` — tabel rooms (kamar kapsul standard/VIP)
- [x] Migration `2026_04_17_000003_create_menus_table.php` — tabel menus (FK ke outlets)
- [x] Migration `2026_04_17_000004_create_bookings_table.php` — tabel bookings (lifecycle, QR, PIN, OTA)
- [x] Migration `2026_04_17_000005_create_transactions_table.php` — tabel transactions + transaction_items (POS, offline sync)
- [x] Model `User.php` — RBAC helpers (`hasRole()`, `isSuperAdmin()`, dll)
- [x] Model `Outlet.php`, `Room.php`, `Menu.php` — relasi Eloquent
- [x] Model `Booking.php` — status lifecycle helpers (`canCheckIn()`, dll)
- [x] Model `Transaction.php`, `TransactionItem.php` — offline sync support

### Middleware RBAC & API Routes
- [x] Middleware `CheckRole.php` — multi-role check, super_admin bypass, is_active guard
- [x] Daftarkan alias `role` di `bootstrap/app.php` + tambah API routing
- [x] `routes/api.php` — skeleton API routes dengan pembagian akses per-role (resepsionis, kasir, manajer, super_admin)

### Seeder Data Dummy
- [x] `UserSeeder.php` — 5 user (1 super_admin, 1 manajer, 2 kasir, 1 resepsionis)
- [x] `OutletSeeder.php` — 4 gerai F&B khas Makassar di berbagai terminal
- [x] `RoomSeeder.php` — 15 kamar kapsul (10 standard, 5 VIP) dengan harga IDR
- [x] `MenuSeeder.php` — 18 menu makanan/minuman khas Makassar terhubung ke masing-masing outlet
- [x] `DatabaseSeeder.php` — orchestrator memanggil ke-4 seeder berurutan

### Docker Production Setup
- [x] `Dockerfile.app` — multi-stage build (Composer → Node → PHP 8.3-FPM + Nginx + Supervisor)
- [x] `docker/nginx/default.conf` — Nginx config untuk Laravel + PHP-FPM
- [x] `docker/php/opcache.ini` — PHP OPcache untuk production
- [x] `docker/supervisor/supervisord.conf` — Supervisor (PHP-FPM + Nginx dalam 1 container)

### API Controllers
- [x] `BookingController.php` — CRUD, double-booking prevention (SELECT FOR UPDATE), QR/PIN check-in, room availability
- [x] `TransactionController.php` — POS create, list, batch offline sync dengan deduplikasi, PPN 11%
- [x] `DashboardController.php` — occupancy rate, booking stats, revenue harian/bulanan
- [x] `routes/api.php` — Semua routes diaktifkan dengan controller bindings

### UI Kasir POS (Alpine.js + Tailwind)
- [x] `layouts/app.blade.php` — Layout premium dark-mode (Tailwind CDN, Alpine.js, Inter font, custom animations)
- [x] `auth/login.blade.php` — Login page dengan demo credentials toggle (4 akun demo)
- [x] `pos/index.blade.php` — Halaman POS kasir lengkap: katalog menu grid, keranjang interaktif, filter kategori, pencarian, 5 metode bayar, PPN 11%, success modal
- [x] `PosController.php` — Memuat outlets & menus aktif untuk halaman kasir
- [x] `AuthController.php` — Session-based login/logout untuk web POS
- [x] `routes/web.php` — Login, POS (kasir+manajer), dashboard placeholder
- [x] `routes/api.php` — Tambah endpoint login/logout

### Auto Check-out Scheduler
- [x] `AutoCheckOut.php` — Artisan command `bookings:auto-checkout` (dry-run, grace period 30 menit, tabel output, logging)
- [x] `ResetCleaningRooms.php` — Command pelengkap `rooms:reset-cleaning` (reset kamar cleaning → available setelah 60 menit)
- [x] `routes/console.php` — Scheduler: auto-checkout tiap 15 menit, room reset tiap jam (withoutOverlapping, log output)

### Midtrans Sandbox Payment
- [x] `MidtransService.php` — Snap token generation + webhook signature verification
- [x] `PaymentController.php` — Generate Snap token (`POST /api/bookings/{id}/pay`) + webhook handler (`POST /api/webhooks/midtrans`)
- [x] Email otomatis dikirim saat pembayaran confirmed via webhook

### Email Konfirmasi
- [x] `BookingConfirmation.php` — Notification (queueable) dengan detail kamar, PIN, link booking

### Laporan & Export CSV
- [x] `ReportController.php` — Revenue summary (harian/bulanan), occupancy stats, export CSV transaksi POS + bookings
- [x] `routes/api.php` — Semua routes final: payment, webhook, reports, export

### Booking Form Multi-step
- [x] `booking/create.blade.php` — Wizard 3 langkah: pilih kamar (cek ketersediaan) → data tamu → konfirmasi & bayar
- [x] `routes/web.php` — Tambah route `/booking/create` (resepsionis + manajer)

### IndexedDB Offline Mode (POS)
- [x] `public/js/offline-store.js` — IndexedDB store: save/get/sync/cleanup transaksi offline, auto-sync saat online, network status detection

### Golang Agent — MySQL Integration (Fase 3b)
- [x] `agent/database.go` — MySQL connection pool (5 max conns, health check)
- [x] `agent/handlers.go` — 4 event handlers fungsional (booking.created/cancelled/modified, payment.confirmed) dengan SELECT FOR UPDATE
- [x] `agent/main.go` — Upgrade: InitDB, health endpoint :9000, handler stubs dipindah ke handlers.go
- [x] `agent/go.mod` — Tambah dependency `go-sql-driver/mysql`

## Fase 4 — CI/CD & Testing

### GitHub Actions
- [x] `deploy.yml` — Test (PHPUnit + MySQL service) → Build Docker images → SSH deploy ke EC2 → migrate → health check
- [x] `lint.yml` — PR checks: PHPUnit + Laravel Pint (PHP) + go vet + go build (Go)

### PHPUnit Feature Tests (15 test cases)
- [x] `AuthTest.php` — Health check, login valid/invalid, inactive user, RBAC middleware, super_admin bypass (6 tests)
- [x] `BookingTest.php` — Room availability, create booking, double-booking prevention, PIN check-in, role auth (5 tests)
- [x] `TransactionTest.php` — POS create (PPN calc), offline dedup, batch sync, role auth (4 tests)

### Load Test & Monitoring
- [x] `k6-load-test.js` — Load test: health, login, rooms, POS (ramp-up → steady → spike → recovery)
- [x] `monitoring-setup.md` — UptimeRobot, CloudWatch alarms, log rotation, Docker log limits
- [x] `ci-cd-setup.md` — Panduan GitHub Secrets + persiapan EC2
