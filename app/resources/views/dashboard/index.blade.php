@extends('layouts.app')
@section('title', 'Dashboard Manajer')
@section('body-class', 'dashboard-page font-sans antialiased min-h-screen text-[#0d1c2e]')

@section('content')
<div class="dashboard-shell min-h-screen text-[#0d1c2e]" x-data="dashboardApp()" x-cloak>
    <div class="mx-auto flex min-h-screen w-full max-w-[1680px] flex-col lg:flex-row">
        <aside class="relative overflow-hidden bg-white px-5 py-5 lg:min-h-screen lg:w-[290px] lg:border-r lg:border-[#c7c4d8]/20">
            <div class="absolute inset-x-6 top-0 h-24 rounded-b-[32px] bg-gradient-to-b from-[#e2dfff] to-transparent opacity-90"></div>

            <div class="relative flex h-full flex-col">
                <div class="flex items-center gap-3 px-2 py-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-[#3525cd] to-[#4f46e5] text-sm font-black text-white shadow-[0_18px_32px_rgba(53,37,205,0.22)]">
                        BC
                    </div>
                    <div>
                        <p class="dashboard-display text-lg font-extrabold text-[#3525cd]">Bookcabin</p>
                        <p class="text-xs font-medium uppercase tracking-[0.22em] text-[#777587]">Airport Retail Suite</p>
                    </div>
                </div>

                <a href="/pos" class="mt-6 inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-[#3525cd] to-[#4f46e5] px-4 py-3 text-sm font-semibold text-white shadow-[0_20px_35px_rgba(53,37,205,0.22)] transition hover:-translate-y-0.5">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Mulai Transaksi Baru
                </a>

                <nav class="mt-8 space-y-1.5">
                    <a href="/dashboard" class="flex items-center gap-3 rounded-2xl bg-[#eff4ff] px-4 py-3 text-sm font-semibold text-[#3525cd]">
                        <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-white text-[#3525cd] shadow-[0_10px_24px_rgba(13,28,46,0.05)]">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 13h6V4H4v9zm10 7h6V4h-6v16zM4 20h6v-3H4v3zm10-7h6v-3h-6v3z" />
                            </svg>
                        </span>
                        Dashboard
                    </a>
                    <a href="/pos" class="flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium text-[#505f76] transition hover:bg-[#eff4ff]/70 hover:text-[#0d1c2e]">
                        <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-[#eff4ff] text-[#505f76]">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13 5.4 5M7 13l-2.29 2.29A1 1 0 0 0 5.41 17H17m0 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4zm-8 2a2 2 0 1 0 0 4 2 2 0 0 0 0-4z" />
                            </svg>
                        </span>
                        POS Kasir
                    </a>
                </nav>

                <div class="mt-8 rounded-[28px] bg-[#eff4ff] p-5 shadow-[0_22px_45px_rgba(13,28,46,0.05)]">
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[#777587]">Shift Aktif</p>
                    <h2 class="dashboard-display mt-3 text-2xl font-extrabold text-[#0d1c2e]">Operasional tenant dipantau dari satu kanvas.</h2>
                    <p class="mt-2 text-sm leading-6 text-[#464555]">Dashboard ini difokuskan ke kebutuhan project: memantau pendapatan, volume order, kesiapan katalog, dan aktivitas outlet tanpa mengubah kontrak backend yang sudah ada.</p>
                    <div class="mt-5 rounded-2xl bg-white/80 px-4 py-3 backdrop-blur">
                        <p class="text-xs font-medium uppercase tracking-[0.18em] text-[#777587]">Waktu Lokal</p>
                        <p class="dashboard-display mt-1 text-xl font-bold text-[#3525cd]" x-text="clock"></p>
                    </div>
                </div>

                <div class="mt-auto pt-8">
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-2xl bg-[#dce9ff] px-4 py-3 text-sm font-semibold text-[#233144] transition hover:bg-[#d5e3fc]">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0-4-4m4 4H7m6 4v1a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h4a3 3 0 0 1 3 3v1" />
                            </svg>
                            Keluar
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <main class="relative flex-1 overflow-hidden">
            <div class="relative h-full overflow-y-auto px-4 py-5 sm:px-6 lg:px-8 lg:py-8">
                <header class="dashboard-panel rounded-[30px] px-6 py-5">
                    <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.28em] text-[#777587]">Ringkasan Operasional</p>
                            <h1 class="dashboard-display mt-2 text-3xl font-extrabold tracking-tight text-[#0d1c2e] sm:text-4xl">Pusat kendali penjualan harian tenant bandara.</h1>
                            <p class="mt-3 max-w-2xl text-sm leading-6 text-[#464555]">Style Stitch tetap dipakai sebagai fondasi visual, tetapi layoutnya saya sesuaikan supaya lebih relevan untuk dashboard manajer Bookcabin: cepat dibaca, fokus ke KPI inti, dan langsung mengarah ke POS.</p>
                        </div>

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                            <div class="rounded-2xl bg-[#f8f9ff] px-4 py-3 shadow-[inset_0_0_0_1px_rgba(199,196,216,0.18)]">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-[#777587]">Filter Cepat</p>
                                <div class="relative mt-2">
                                    <svg class="pointer-events-none absolute left-3 top-3.5 h-4 w-4 text-[#777587]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35M18 10.5a7.5 7.5 0 1 1-15 0 7.5 7.5 0 0 1 15 0Z" />
                                    </svg>
                                    <input type="text" value="Pendapatan, order, outlet, katalog" disabled class="w-full min-w-[250px] rounded-2xl border border-transparent bg-white py-3 pl-10 pr-4 text-sm text-[#505f76] outline-none">
                                </div>
                            </div>

                            <div class="rounded-2xl bg-[#f8f9ff] px-4 py-3 shadow-[inset_0_0_0_1px_rgba(199,196,216,0.18)]">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-[#777587]">Manager On Duty</p>
                                <div class="mt-2 flex items-center gap-3">
                                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-[#3525cd] to-[#4f46e5] text-sm font-bold text-white">
                                        {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="font-semibold text-[#0d1c2e]">{{ auth()->user()->name ?? 'Administrator' }}</p>
                                        <p class="text-sm capitalize text-[#505f76]">{{ auth()->user()->role ?? 'manajer' }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-2xl bg-gradient-to-br from-[#eff4ff] to-white px-4 py-3 shadow-[0_18px_36px_rgba(13,28,46,0.04)]">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-[#777587]">Status Terminal</p>
                                <div class="mt-2 flex items-center gap-3">
                                    <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-[#3525cd]/10 text-[#3525cd]">
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.01-2A9 9 0 1 1 21 12a9 9 0 0 1-.99 4z" />
                                        </svg>
                                    </span>
                                    <div>
                                        <p class="font-semibold text-[#0d1c2e]">Online dan sinkron</p>
                                        <p class="text-sm text-[#505f76]">POS tetap memakai API yang sama</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </header>

                <section class="mt-6 grid gap-4 xl:grid-cols-[minmax(0,1.25fr)_minmax(320px,0.75fr)]">
                    <article class="rounded-[30px] bg-white p-6 shadow-[0_24px_50px_rgba(13,28,46,0.05)] sm:p-7">
                        <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[#777587]">Manager Brief</p>
                                <h2 class="dashboard-display mt-2 text-2xl font-bold tracking-tight text-[#0d1c2e]">Angka paling penting untuk pembukaan shift hari ini.</h2>
                                <p class="mt-2 max-w-2xl text-sm leading-6 text-[#505f76]">Ringkasan ini dirancang seperti desk briefing: cukup satu lihat untuk tahu pendapatan, volume order, kesiapan menu, dan seberapa banyak outlet yang sedang aktif.</p>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-2">
                                <div class="rounded-[24px] bg-[#eff4ff] px-4 py-4">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-[#777587]">Arah cepat</p>
                                    <p class="mt-2 text-sm font-semibold text-[#0d1c2e]">Masuk ke POS bila butuh transaksi baru atau review outlet aktif.</p>
                                </div>
                                <div class="rounded-[24px] bg-[#f8f9ff] px-4 py-4 shadow-[inset_0_0_0_1px_rgba(199,196,216,0.18)]">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-[#777587]">Status sistem</p>
                                    <p class="mt-2 text-sm font-semibold text-[#0d1c2e]">Frontend diperbarui, API transaksi tetap sama.</p>
                                </div>
                            </div>
                        </div>
                    </article>

                    <article class="rounded-[30px] bg-gradient-to-br from-[#0d1c2e] to-[#233144] p-6 text-white shadow-[0_24px_48px_rgba(13,28,46,0.22)] sm:p-7">
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-white/60">Prioritas Hari Ini</p>
                        <h2 class="dashboard-display mt-2 text-2xl font-bold tracking-tight">Pastikan katalog siap, kasir lancar, dan outlet tetap responsif.</h2>
                        <div class="mt-5 grid gap-3">
                            <div class="rounded-[22px] bg-white/8 px-4 py-3 backdrop-blur-sm">
                                <p class="text-[11px] uppercase tracking-[0.18em] text-white/55">Katalog</p>
                                <p class="mt-1 text-sm text-white/85">{{ $activeMenusCount }} menu aktif tersedia untuk dijual.</p>
                            </div>
                            <div class="rounded-[22px] bg-white/8 px-4 py-3 backdrop-blur-sm">
                                <p class="text-[11px] uppercase tracking-[0.18em] text-white/55">Outlet</p>
                                <p class="mt-1 text-sm text-white/85">{{ $outletsCount }} outlet terhubung ke alur transaksi saat ini.</p>
                            </div>
                        </div>
                    </article>
                </section>

                <section class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <article class="dashboard-kpi group rounded-[28px] bg-white p-6 shadow-[0_24px_48px_rgba(13,28,46,0.05)] transition hover:-translate-y-1">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-sm font-medium text-[#464555]">Pendapatan hari ini</p>
                                <p class="dashboard-display mt-2 text-3xl font-extrabold tracking-tight text-[#0d1c2e]" x-text="formatRupiah({{ $todaysRevenue }})"></p>
                            </div>
                            <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#eff4ff] text-[#3525cd]">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.66 0-3 .9-3 2s1.34 2 3 2 3 .9 3 2-1.34 2-3 2m0-8c1.11 0 2.08.4 2.6 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.4-2.6-1M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" />
                                </svg>
                            </span>
                        </div>
                        <div class="mt-5 flex items-center gap-2">
                            <span class="dashboard-pill">
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0 7 7m-7-7v18" />
                                </svg>
                                Live
                            </span>
                            <span class="text-xs text-[#777587]">terpantau dari transaksi masuk</span>
                        </div>
                    </article>

                    <article class="dashboard-kpi group rounded-[28px] bg-white p-6 shadow-[0_24px_48px_rgba(13,28,46,0.05)] transition hover:-translate-y-1">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-sm font-medium text-[#464555]">Transaksi hari ini</p>
                                <p class="dashboard-display mt-2 text-3xl font-extrabold tracking-tight text-[#0d1c2e]">{{ $todaysTransactions }}</p>
                            </div>
                            <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#eff4ff] text-[#3525cd]">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2m-6 9 2 2 4-4" />
                                </svg>
                            </span>
                        </div>
                        <div class="mt-5 flex items-center gap-2">
                            <span class="dashboard-pill">Kasir aktif</span>
                            <span class="text-xs text-[#777587]">ritme order berjalan stabil</span>
                        </div>
                    </article>

                    <article class="dashboard-kpi group rounded-[28px] bg-white p-6 shadow-[0_24px_48px_rgba(13,28,46,0.05)] transition hover:-translate-y-1">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-sm font-medium text-[#464555]">Menu aktif</p>
                                <p class="dashboard-display mt-2 text-3xl font-extrabold tracking-tight text-[#0d1c2e]">{{ $activeMenusCount }}</p>
                            </div>
                            <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#eff4ff] text-[#3525cd]">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v3H4V6zm0 5h16v7a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-7zm4 3h.01M12 14h4" />
                                </svg>
                            </span>
                        </div>
                        <div class="mt-5 flex items-center gap-2">
                            <span class="dashboard-pill">Siap dijual</span>
                            <span class="text-xs text-[#777587]">katalog tersaji di POS</span>
                        </div>
                    </article>

                    <article class="dashboard-kpi group rounded-[28px] bg-white p-6 shadow-[0_24px_48px_rgba(13,28,46,0.05)] transition hover:-translate-y-1">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p class="text-sm font-medium text-[#464555]">Outlet aktif</p>
                                <p class="dashboard-display mt-2 text-3xl font-extrabold tracking-tight text-[#0d1c2e]">{{ $outletsCount }}</p>
                            </div>
                            <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#eff4ff] text-[#3525cd]">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21h18M5 21V7l8-4 6 4v14M9 9h.01M9 13h.01M9 17h.01M15 9h.01M15 13h.01M15 17h.01" />
                                </svg>
                            </span>
                        </div>
                        <div class="mt-5 flex items-center gap-2">
                            <span class="dashboard-pill">Bandara</span>
                            <span class="text-xs text-[#777587]">tenant aktif terhubung</span>
                        </div>
                    </article>
                </section>

                <section class="mt-6 grid gap-6 2xl:grid-cols-[minmax(0,1.2fr)_minmax(340px,0.8fr)]">
                    <article class="rounded-[30px] bg-white p-6 shadow-[0_24px_50px_rgba(13,28,46,0.05)] sm:p-7">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[#777587]">Transaction Rhythm</p>
                                <h2 class="dashboard-display mt-2 text-2xl font-bold tracking-tight text-[#0d1c2e]">Visualisasi transaksi terbaru</h2>
                            </div>
                            <a href="/pos" class="inline-flex items-center gap-2 rounded-2xl bg-[#eff4ff] px-4 py-2.5 text-sm font-semibold text-[#3525cd] transition hover:bg-[#dce9ff]">
                                Buka POS Kasir
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7" />
                                </svg>
                            </a>
                        </div>

                        <div class="mt-8">
                            @php
                                $chartTransactions = $recentTransactions->take(6)->values();
                                $chartMax = max($chartTransactions->max('total') ?? 0, 1);
                            @endphp

                            <div class="relative rounded-[28px] bg-[#eff4ff] px-4 pb-10 pt-8 sm:px-6">
                                <div class="pointer-events-none absolute inset-x-4 top-8 bottom-10 flex flex-col justify-between sm:inset-x-6">
                                    @for ($i = 0; $i < 5; $i++)
                                        <div class="border-t border-[#c7c4d8]/20"></div>
                                    @endfor
                                </div>

                                <div class="relative flex h-72 items-end gap-3 sm:gap-4">
                                    @forelse($chartTransactions as $trx)
                                        @php
                                            $height = max(18, (int) round(($trx->total / $chartMax) * 100));
                                        @endphp
                                        <div class="group flex min-w-0 flex-1 flex-col items-center justify-end gap-3">
                                            <div class="relative flex w-full justify-center">
                                                <span class="pointer-events-none absolute -top-10 rounded-xl bg-[#0d1c2e] px-2.5 py-1 text-[11px] font-semibold text-white opacity-0 transition group-hover:opacity-100" x-text="formatRupiah({{ $trx->total }})"></span>
                                                <div class="dashboard-bar w-full max-w-[64px] rounded-t-[18px] bg-gradient-to-t from-[#3525cd] to-[#4f46e5] shadow-[0_12px_24px_rgba(53,37,205,0.22)] transition duration-300 group-hover:-translate-y-1" style="height: {{ $height }}%">
                                                    <div class="h-full w-full rounded-t-[18px] bg-[linear-gradient(180deg,rgba(255,255,255,0.18),rgba(255,255,255,0))]"></div>
                                                </div>
                                            </div>
                                            <div class="text-center">
                                                <p class="text-xs font-semibold text-[#0d1c2e]">{{ $trx->created_at->format('H:i') }}</p>
                                                <p class="mt-1 text-[11px] uppercase tracking-[0.14em] text-[#777587]">{{ \Illuminate\Support\Str::limit($trx->transaction_code, 8, '') }}</p>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="flex h-full w-full flex-col items-center justify-center rounded-[24px] bg-white/70 text-center">
                                            <svg class="h-10 w-10 text-[#777587]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-6m4 6V7m4 10v-3M5 19h14" />
                                            </svg>
                                            <p class="mt-3 text-sm font-semibold text-[#0d1c2e]">Belum ada data transaksi untuk divisualkan</p>
                                            <p class="mt-1 text-sm text-[#505f76]">Grafik ini akan terisi otomatis dari transaksi terbaru.</p>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </article>

                    <article class="rounded-[30px] bg-white p-6 shadow-[0_24px_50px_rgba(13,28,46,0.05)] sm:p-7">
                        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[#777587]">Manager Notes</p>
                        <h2 class="dashboard-display mt-2 text-2xl font-bold tracking-tight text-[#0d1c2e]">Snapshot operasional</h2>

                        <div class="mt-6 space-y-4">
                            <div class="rounded-[24px] bg-[#eff4ff] p-5">
                                <p class="text-sm font-medium text-[#464555]">Rata-rata per transaksi</p>
                                <p class="dashboard-display mt-2 text-3xl font-extrabold tracking-tight text-[#0d1c2e]">
                                    Rp {{ number_format($todaysTransactions > 0 ? $todaysRevenue / max($todaysTransactions, 1) : 0, 0, ',', '.') }}
                                </p>
                                <p class="mt-2 text-sm text-[#505f76]">Estimasi cepat untuk membaca nilai belanja rata-rata per order di terminal.</p>
                            </div>

                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="rounded-[24px] bg-[#f8f9ff] p-5 shadow-[inset_0_0_0_1px_rgba(199,196,216,0.18)]">
                                    <p class="text-sm font-medium text-[#464555]">Akses cepat</p>
                                    <a href="/pos" class="mt-3 inline-flex items-center gap-2 text-sm font-semibold text-[#3525cd]">
                                        Buka terminal kasir
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7" />
                                        </svg>
                                    </a>
                                </div>
                                <div class="rounded-[24px] bg-[#f8f9ff] p-5 shadow-[inset_0_0_0_1px_rgba(199,196,216,0.18)]">
                                    <p class="text-sm font-medium text-[#464555]">Katalog siap jual</p>
                                    <p class="dashboard-display mt-3 text-2xl font-bold tracking-tight text-[#0d1c2e]">{{ $activeMenusCount }} item</p>
                                    <p class="mt-2 text-sm text-[#505f76]">Gunakan angka ini untuk memastikan tenant tetap punya menu aktif di POS.</p>
                                </div>
                            </div>

                            <div class="rounded-[28px] bg-gradient-to-br from-[#eff4ff] to-[#dce9ff] p-6 text-[#0d1c2e] shadow-[0_18px_40px_rgba(13,28,46,0.06)]">
                                <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[#777587]">Penggunaan Dashboard</p>
                                <p class="dashboard-display mt-3 text-2xl font-bold tracking-tight">Diprioritaskan untuk keputusan cepat, bukan kontrol operasional detail.</p>
                                <p class="mt-2 text-sm leading-6 text-[#505f76]">Detail transaksi tetap diteruskan ke halaman POS. Halaman ini sengaja dibuat sebagai ringkasan premium yang ringan, mudah dipindai, dan relevan untuk pembukaan atau evaluasi shift.</p>
                            </div>
                        </div>
                    </article>
                </section>

                <section class="mt-6 rounded-[30px] bg-white p-6 shadow-[0_24px_50px_rgba(13,28,46,0.05)] sm:p-7">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-[#777587]">Recent Transactions</p>
                            <h2 class="dashboard-display mt-2 text-2xl font-bold tracking-tight text-[#0d1c2e]">Transaksi terbaru</h2>
                        </div>
                        <a href="/pos" class="inline-flex items-center gap-2 text-sm font-semibold text-[#3525cd]">
                            Lanjut ke kasir
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7" />
                            </svg>
                        </a>
                    </div>

                    <div class="mt-6 space-y-3">
                        @forelse($recentTransactions as $trx)
                            <article class="rounded-[24px] bg-[#f8f9ff] px-4 py-4 transition hover:bg-[#eff4ff] sm:px-5">
                                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                                    <div class="flex items-center gap-4">
                                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-[#3525cd] shadow-[0_10px_24px_rgba(13,28,46,0.05)]">
                                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.66 0-3 .9-3 2s1.34 2 3 2 3 .9 3 2-1.34 2-3 2m0-8c1.11 0 2.08.4 2.6 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.4-2.6-1M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <p class="font-semibold text-[#0d1c2e]">{{ $trx->transaction_code }}</p>
                                            <p class="mt-1 flex flex-wrap items-center gap-2 text-sm text-[#505f76]">
                                                <span>{{ $trx->created_at->format('H:i') }} WITA</span>
                                                <span class="dashboard-dot"></span>
                                                <span>{{ $trx->user->name ?? 'Kasir Default' }}</span>
                                                <span class="dashboard-dot"></span>
                                                <span>{{ strtoupper($trx->payment_method) }}</span>
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex items-center justify-between gap-4 lg:min-w-[260px] lg:justify-end">
                                        <span class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-semibold uppercase tracking-[0.18em] {{ $trx->status === 'success' ? 'bg-[#eff4ff] text-[#3525cd]' : 'bg-[#ffdbcc] text-[#7e3000]' }}">
                                            <span class="h-2 w-2 rounded-full {{ $trx->status === 'success' ? 'bg-[#3525cd]' : 'bg-[#7e3000]' }}"></span>
                                            {{ $trx->status === 'success' ? 'Completed' : ucfirst($trx->status) }}
                                        </span>
                                        <p class="dashboard-display text-xl font-bold tracking-tight text-[#0d1c2e]" x-text="formatRupiah({{ $trx->total }})"></p>
                                    </div>
                                </div>
                            </article>
                        @empty
                            <div class="rounded-[24px] bg-[#f8f9ff] px-6 py-12 text-center">
                                <svg class="mx-auto h-12 w-12 text-[#777587]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5.59a1 1 0 0 1 .7.29l5.42 5.42a1 1 0 0 1 .29.7V19a2 2 0 0 1-2 2Z" />
                                </svg>
                                <p class="mt-4 text-base font-semibold text-[#0d1c2e]">Belum ada transaksi hari ini.</p>
                                <p class="mt-2 text-sm text-[#505f76]">Begitu ada order masuk, daftar terbaru akan muncul di sini secara otomatis.</p>
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>
        </main>
    </div>
</div>

@push('scripts')
<script>
function dashboardApp() {
    return {
        clock: '',

        init() {
            this.updateClock();
            setInterval(() => this.updateClock(), 1000);
        },

        updateClock() {
            const now = new Date();
            this.clock = now.toLocaleTimeString('id-ID', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
            });
        },

        formatRupiah(num) {
            return 'Rp ' + Number(num).toLocaleString('id-ID');
        }
    }
}
</script>
@endpush
@endsection
