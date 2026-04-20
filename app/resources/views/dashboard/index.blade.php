@extends('layouts.app')
@section('title', 'Dashboard Manajer')

@section('content')
<div class="min-h-screen flex bg-surface" x-data="dashboardApp()" x-cloak>
    
    <!-- ═══════════════ SIDEBAR ═══════════════ -->
    <aside class="w-64 bg-surface-light border-r border-slate-700/50 flex flex-col shrink-0">
        <div class="h-16 flex items-center px-6 border-b border-slate-700/50">
            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-brand-400 to-brand-600 flex items-center justify-center mr-3 shadow-lg shadow-brand-500/25">
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>
                </svg>
            </div>
            <h1 class="text-lg font-bold text-white tracking-wide">Bookcabin</h1>
        </div>

        <nav class="flex-1 px-4 py-6 space-y-2">
            <a href="/dashboard" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-brand-600/10 text-brand-400 font-medium transition group">
                <svg class="w-5 h-5 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                </svg>
                Dashboard
            </a>
            <a href="/pos" class="flex items-center gap-3 px-4 py-3 rounded-xl text-slate-400 hover:bg-slate-800/50 hover:text-white font-medium transition group">
                <svg class="w-5 h-5 transition-transform group-hover:scale-110 group-hover:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>
                </svg>
                POS Kasir
            </a>
        </nav>

        <div class="p-4 border-t border-slate-700/50">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-surface border border-slate-700 text-slate-400 hover:text-red-400 hover:border-red-500/50 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    <span class="text-sm font-medium">Keluar</span>
                </button>
            </form>
        </div>
    </aside>

    <!-- ═══════════════ MAIN CONTENT ═══════════════ -->
    <main class="flex-1 overflow-y-auto">
        <!-- Header -->
        <header class="h-16 px-8 flex items-center justify-between sticky top-0 bg-surface/80 backdrop-blur-md z-10 border-b border-slate-700/30">
            <div>
                <h2 class="text-xl font-bold text-white">Ringkasan Operasional</h2>
            </div>
            <div class="flex items-center gap-4">
                <div class="text-sm text-slate-400 font-mono" x-text="clock"></div>
                <div class="flex items-center gap-3 pl-4 border-l border-slate-700">
                    <div class="text-right">
                        <p class="text-sm font-medium text-white">{{ auth()->user()->name ?? 'Administrator' }}</p>
                        <p class="text-xs text-brand-400 capitalize">{{ auth()->user()->role ?? 'Manajer' }}</p>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-slate-800 border-2 border-slate-700 flex items-center justify-center text-white font-bold">
                        {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 1)) }}
                    </div>
                </div>
            </div>
        </header>

        <div class="p-8 max-w-7xl mx-auto space-y-8">
            <!-- ═══════════════ KPI CARDS ═══════════════ -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <!-- Pendapatan Card -->
                <div class="relative overflow-hidden bg-gradient-to-br from-brand-900/40 to-surface-light border border-brand-500/20 rounded-2xl p-6 group hover:-translate-y-1 transition duration-300 shadow-xl shadow-brand-900/20">
                    <div class="absolute -right-4 -top-4 w-24 h-24 bg-brand-500/10 rounded-full blur-2xl group-hover:bg-brand-500/20 transition duration-500"></div>
                    <div class="flex items-start justify-between mb-2">
                        <p class="text-slate-400 font-medium text-sm">Pendapatan Hari Ini</p>
                        <span class="p-2 bg-brand-500/20 text-brand-400 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </span>
                    </div>
                    <h3 class="text-3xl font-bold text-white tracking-tight" x-text="formatRupiah({{ $todaysRevenue }})"></h3>
                    <p class="text-xs text-brand-400 mt-2 flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                        Real-time update
                    </p>
                </div>

                <!-- Transaksi Card -->
                <div class="relative overflow-hidden bg-gradient-to-br from-indigo-900/40 to-surface-light border border-indigo-500/20 rounded-2xl p-6 group hover:-translate-y-1 transition duration-300 shadow-xl shadow-indigo-900/20">
                    <div class="absolute -right-4 -top-4 w-24 h-24 bg-indigo-500/10 rounded-full blur-2xl group-hover:bg-indigo-500/20 transition duration-500"></div>
                    <div class="flex items-start justify-between mb-2">
                        <p class="text-slate-400 font-medium text-sm">Transaksi Hari Ini</p>
                        <span class="p-2 bg-indigo-500/20 text-indigo-400 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                        </span>
                    </div>
                    <h3 class="text-3xl font-bold text-white tracking-tight">{{ $todaysTransactions }}</h3>
                    <p class="text-xs text-indigo-400 mt-2">Struk diterbitkan</p>
                </div>

                <!-- Active Menu Card -->
                <div class="relative overflow-hidden bg-surface-light border border-slate-700/50 rounded-2xl p-6 hover:-translate-y-1 transition duration-300">
                    <div class="flex items-start justify-between mb-2">
                        <p class="text-slate-400 font-medium text-sm">Menu Aktif</p>
                        <span class="p-2 bg-slate-800 text-slate-300 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6z"></path></svg>
                        </span>
                    </div>
                    <h3 class="text-3xl font-bold text-white tracking-tight">{{ $activeMenusCount }}</h3>
                    <p class="text-xs text-slate-500 mt-2">Dapat dipesan pelanggan</p>
                </div>

                <!-- Outlet Card -->
                <div class="relative overflow-hidden bg-surface-light border border-slate-700/50 rounded-2xl p-6 hover:-translate-y-1 transition duration-300">
                    <div class="flex items-start justify-between mb-2">
                        <p class="text-slate-400 font-medium text-sm">Outlet / Tenant</p>
                        <span class="p-2 bg-slate-800 text-slate-300 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        </span>
                    </div>
                    <h3 class="text-3xl font-bold text-white tracking-tight">{{ $outletsCount }}</h3>
                    <p class="text-xs text-slate-500 mt-2">Cabang Bandara</p>
                </div>
            </div>

            <!-- ═══════════════ RECENT TRANSACTIONS ═══════════════ -->
            <div class="bg-surface-light border border-slate-700/50 rounded-2xl shadow-xl overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-700/50 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-white">Transaksi Terbaru</h3>
                    <a href="/pos" class="text-sm text-brand-400 hover:text-brand-300 font-medium transition flex items-center gap-1">
                        Buka POS Kasir 
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    </a>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-slate-800/50 text-slate-400">
                            <tr>
                                <th class="px-6 py-4 font-medium">ID Transaksi</th>
                                <th class="px-6 py-4 font-medium">Waktu</th>
                                <th class="px-6 py-4 font-medium">Pelanggan/Kasir</th>
                                <th class="px-6 py-4 font-medium">Metode</th>
                                <th class="px-6 py-4 font-medium">Status</th>
                                <th class="px-6 py-4 font-medium text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-700/50">
                            @forelse($recentTransactions as $trx)
                            <tr class="hover:bg-slate-800/30 transition">
                                <td class="px-6 py-4">
                                    <span class="font-mono text-white">{{ $trx->transaction_code }}</span>
                                </td>
                                <td class="px-6 py-4 text-slate-400">
                                    {{ $trx->created_at->format('H:i') }} <span class="text-xs">WITA</span>
                                </td>
                                <td class="px-6 py-4 text-slate-300">
                                    {{ $trx->user->name ?? 'Kasir Default' }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2.5 py-1 rounded-md bg-slate-800 text-slate-300 text-xs font-medium uppercase border border-slate-700">
                                        {{ $trx->payment_method }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    @if($trx->status === 'success')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-emerald-500/10 text-emerald-400 text-xs font-medium border border-emerald-500/20">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> Sukses
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-amber-500/10 text-amber-400 text-xs font-medium border border-amber-500/20">
                                            <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span> {{ ucfirst($trx->status) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="font-semibold text-white" x-text="formatRupiah({{ $trx->total }})"></span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                                    <svg class="w-12 h-12 mx-auto mb-3 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                    Belum ada transaksi hari ini.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
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
            this.clock = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        },

        formatRupiah(num) {
            return 'Rp ' + Number(num).toLocaleString('id-ID');
        }
    }
}
</script>
@endpush
@endsection
