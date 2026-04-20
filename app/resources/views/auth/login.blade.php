@extends('layouts.app')
@section('title', 'Login')

@section('content')
<div class="min-h-screen flex items-center justify-center px-4"
     x-data="loginForm()" x-cloak>

    <div class="w-full max-w-md">
        <!-- Logo & Title -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-brand-400 to-brand-600 mb-4 shadow-lg shadow-brand-500/25">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-white">Bookcabin POS</h1>
            <p class="text-slate-400 text-sm mt-1">Sistem Kasir — Bandara Sultan Hasanuddin</p>
        </div>

        <!-- Login Card -->
        <div class="bg-surface-light rounded-2xl border border-slate-700/50 p-8 shadow-2xl backdrop-blur-sm">
            <form @submit.prevent="login">
                <!-- Email -->
                <div class="mb-5">
                    <label class="block text-sm font-medium text-slate-300 mb-2">Email</label>
                    <div class="relative">
                        <svg class="absolute left-3 top-3 w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207"/>
                        </svg>
                        <input type="email" x-model="email" required
                               class="w-full pl-11 pr-4 py-2.5 bg-surface border border-slate-600 rounded-xl text-white placeholder-slate-500 focus:ring-2 focus:ring-brand-500 focus:border-transparent transition"
                               placeholder="kasir1@bookcabin.id">
                    </div>
                </div>

                <!-- Password -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-slate-300 mb-2">Password</label>
                    <div class="relative">
                        <svg class="absolute left-3 top-3 w-5 h-5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        <input type="password" x-model="password" required
                               class="w-full pl-11 pr-4 py-2.5 bg-surface border border-slate-600 rounded-xl text-white placeholder-slate-500 focus:ring-2 focus:ring-brand-500 focus:border-transparent transition"
                               placeholder="••••••••">
                    </div>
                </div>

                <!-- Error -->
                <div x-show="error" x-transition
                     class="mb-4 p-3 bg-red-500/10 border border-red-500/30 rounded-xl text-red-400 text-sm">
                    <span x-text="error"></span>
                </div>

                <!-- Submit -->
                <button type="submit" :disabled="loading"
                        class="w-full py-3 bg-gradient-to-r from-brand-500 to-brand-600 hover:from-brand-600 hover:to-brand-700 text-white font-semibold rounded-xl transition shadow-lg shadow-brand-500/25 disabled:opacity-50 flex items-center justify-center gap-2">
                    <svg x-show="loading" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <span x-text="loading ? 'Memproses...' : 'Masuk'"></span>
                </button>
            </form>

            <!-- Demo credentials -->
            <div class="mt-6 pt-5 border-t border-slate-700/50">
                <p class="text-xs text-slate-500 text-center mb-3">Akun Demo</p>
                <div class="grid grid-cols-2 gap-2">
                    <template x-for="demo in demos" :key="demo.email">
                        <button @click="email = demo.email; password = 'password123'"
                                class="px-3 py-2 bg-surface rounded-lg border border-slate-700 hover:border-brand-500/50 text-xs text-slate-400 hover:text-brand-400 transition truncate">
                            <span x-text="demo.label"></span>
                        </button>
                    </template>
                </div>
            </div>
        </div>
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
            { email: 'admin@bookcabin.id',       label: 'Super Admin' },
            { email: 'manajer@bookcabin.id',      label: 'Manajer' },
            { email: 'kasir1@bookcabin.id',       label: 'Kasir 1' },
            { email: 'resepsionis@bookcabin.id',  label: 'Resepsionis' },
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

                // Redirect berdasarkan role
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
