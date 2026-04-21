@extends('layouts.app')
@section('title', 'Login')
@section('body-class', 'ops-shell font-sans antialiased min-h-screen')

@section('content')
<div class="min-h-screen px-4 py-8 sm:px-6 lg:px-8" x-data="loginForm()" x-cloak>
    <div class="mx-auto grid min-h-[calc(100vh-4rem)] w-full max-w-6xl gap-6 lg:grid-cols-[minmax(0,1.05fr)_440px] lg:items-center">
        <section class="ops-panel rounded-[36px] px-6 py-8 sm:px-8 lg:px-10">
            <div class="max-w-2xl">
                <p class="ops-kicker">Access Portal</p>
                <h1 class="ops-display mt-3 text-4xl font-extrabold tracking-tight text-white sm:text-5xl">Satu pintu untuk operasional kasir, dashboard manajer, dan alur booking.</h1>
                <p class="mt-4 max-w-xl text-sm leading-7 text-slate-300">Halaman login ini saya rapikan supaya visualnya sejalan dengan dashboard baru: tetap gelap untuk ruang operasional, tapi lebih premium, lebih jelas hirarkinya, dan lebih nyaman dipakai saat shift dimulai.</p>
            </div>

            <div class="mt-8 grid gap-4 sm:grid-cols-3">
                <article class="ops-panel-soft rounded-[28px] p-5">
                    <p class="ops-kicker">Kasir</p>
                    <h2 class="mt-3 text-lg font-semibold text-white">POS siap jual</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-300">Masuk cepat ke katalog, keranjang, dan checkout tanpa perubahan API.</p>
                </article>
                <article class="ops-panel-soft rounded-[28px] p-5">
                    <p class="ops-kicker">Manajer</p>
                    <h2 class="mt-3 text-lg font-semibold text-white">Briefing harian</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-300">Pantau revenue, ritme order, dan kesiapan outlet dari satu halaman.</p>
                </article>
                <article class="ops-panel-soft rounded-[28px] p-5">
                    <p class="ops-kicker">Resepsionis</p>
                    <h2 class="mt-3 text-lg font-semibold text-white">Booking lebih rapi</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-300">Alur kamar, tamu, dan konfirmasi pembayaran lebih mudah dipindai.</p>
                </article>
            </div>

            <div class="mt-8 flex flex-wrap gap-3">
                <span class="ops-chip">Bandara Sultan Hasanuddin</span>
                <span class="ops-chip">Laravel Blade</span>
                <span class="ops-chip">Frontend refresh active</span>
            </div>
        </section>

        <section class="ops-panel rounded-[36px] p-6 sm:p-8">
            <div class="flex items-center gap-4">
                <div class="flex h-16 w-16 items-center justify-center rounded-[24px] bg-gradient-to-br from-sky-400 to-blue-600 shadow-[0_20px_40px_rgba(14,165,233,0.25)]">
                    <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z" />
                    </svg>
                </div>
                <div>
                    <p class="ops-kicker">Bookcabin POS</p>
                    <h2 class="ops-display mt-1 text-3xl font-bold text-white">Masuk ke sistem</h2>
                    <p class="mt-1 text-sm text-slate-400">Pakai akun operasional yang sesuai dengan peran Anda.</p>
                </div>
            </div>

            <form class="mt-8" @submit.prevent="login">
                <div class="space-y-5">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-300">Email</label>
                        <div class="relative">
                            <svg class="pointer-events-none absolute left-4 top-3.5 h-5 w-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                            </svg>
                            <input type="email" x-model="email" required class="ops-input py-3 pl-12 pr-4 text-sm" placeholder="kasir1@bookcabin.id">
                        </div>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-300">Password</label>
                        <div class="relative">
                            <svg class="pointer-events-none absolute left-4 top-3.5 h-5 w-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            <input type="password" x-model="password" required class="ops-input py-3 pl-12 pr-4 text-sm" placeholder="Masukkan password Anda">
                        </div>
                    </div>
                </div>

                <div x-show="error" x-transition class="mt-4 rounded-2xl border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-300">
                    <span x-text="error"></span>
                </div>

                <button type="submit" :disabled="loading" class="mt-6 flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-sky-500 to-blue-600 px-4 py-3.5 text-sm font-semibold text-white shadow-[0_18px_36px_rgba(14,165,233,0.24)] transition hover:-translate-y-0.5 disabled:opacity-50">
                    <svg x-show="loading" class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span x-text="loading ? 'Memproses...' : 'Masuk ke sistem'"></span>
                </button>
            </form>

            <div class="mt-8 border-t border-slate-700/50 pt-6">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="ops-kicker">Akun Demo</p>
                        <p class="mt-2 text-sm text-slate-400">Klik salah satu peran untuk mengisi email otomatis.</p>
                    </div>
                    <span class="ops-chip">password123</span>
                </div>

                <div class="mt-4 grid grid-cols-2 gap-3">
                    <template x-for="demo in demos" :key="demo.email">
                        <button @click="email = demo.email; password = 'password123'" class="rounded-2xl border border-slate-700/60 bg-slate-900/60 px-4 py-3 text-left transition hover:border-sky-500/40 hover:bg-slate-900">
                            <p class="text-sm font-semibold text-white" x-text="demo.label"></p>
                            <p class="mt-1 text-xs text-slate-500" x-text="demo.email"></p>
                        </button>
                    </template>
                </div>
            </div>
        </section>
    </div>
</div>

@push('scripts')
<script>
function loginForm() {
    return {
        email: '',
        password: '',
        error: '',
        loading: false,
        demos: [
            { email: 'admin@bookcabin.id', label: 'Super Admin' },
            { email: 'manajer@bookcabin.id', label: 'Manajer' },
            { email: 'kasir1@bookcabin.id', label: 'Kasir 1' },
            { email: 'resepsionis@bookcabin.id', label: 'Resepsionis' },
        ],

        async login() {
            this.loading = true;
            this.error = '';

            try {
                const res = await fetch('/api/login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify({ email: this.email, password: this.password }),
                });

                const data = await res.json();

                if (!res.ok) {
                    this.error = data.message || 'Login gagal. Periksa email dan password.';
                    return;
                }

                const redirects = {
                    kasir: '/pos',
                    resepsionis: '/booking/create',
                    manajer: '/dashboard',
                    super_admin: '/dashboard',
                };

                window.location.href = redirects[data.user.role] || '/dashboard';
            } catch (e) {
                this.error = 'Koneksi gagal. Coba lagi nanti.';
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
@endpush
@endsection
