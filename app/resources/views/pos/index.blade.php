@extends('layouts.app')
@section('title', 'POS Kasir')

@section('content')
<div class="min-h-screen flex" x-data="posApp()" x-cloak>

    <!-- ═══════════════ SIDEBAR: Menu Katalog ═══════════════ -->
    <div class="flex-1 flex flex-col bg-surface overflow-hidden">

        <!-- Top Bar -->
        <header class="h-16 bg-surface-light border-b border-slate-700/50 flex items-center justify-between px-6 shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-brand-400 to-brand-600 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-lg font-bold text-white leading-tight">Bookcabin POS</h1>
                    <p class="text-xs text-slate-500" x-text="currentOutlet?.name || 'Pilih Outlet'"></p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <!-- Outlet selector -->
                <select x-model="selectedOutletId" @change="loadMenus()"
                        class="bg-surface border border-slate-600 rounded-lg px-3 py-1.5 text-sm text-slate-300 focus:ring-brand-500 focus:border-brand-500">
                    <template x-for="o in outlets" :key="o.id">
                        <option :value="o.id" x-text="o.name"></option>
                    </template>
                </select>
                <!-- Clock -->
                <div class="text-sm text-slate-400 font-mono" x-text="clock"></div>
                <!-- User -->
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-full bg-brand-600/20 flex items-center justify-center text-brand-400 text-sm font-bold"
                         x-text="userName.charAt(0).toUpperCase()"></div>
                    <span class="text-sm text-slate-400 hidden lg:block" x-text="userName"></span>
                </div>
            </div>
        </header>

        <!-- Category Filter + Search -->
        <div class="px-6 py-4 border-b border-slate-800 flex items-center gap-3 shrink-0">
            <div class="relative flex-1 max-w-xs">
                <svg class="absolute left-3 top-2.5 w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" x-model="search" placeholder="Cari menu..."
                       class="w-full pl-10 pr-4 py-2 bg-surface-light border border-slate-700 rounded-xl text-sm text-white placeholder-slate-500 focus:ring-brand-500 focus:border-brand-500 transition">
            </div>
            <div class="flex gap-2 overflow-x-auto">
                <button @click="activeCategory = ''"
                        :class="activeCategory === '' ? 'bg-brand-600 text-white' : 'bg-surface-light text-slate-400 hover:text-white'"
                        class="px-4 py-2 rounded-xl text-sm font-medium transition whitespace-nowrap">
                    Semua
                </button>
                <template x-for="cat in categories" :key="cat">
                    <button @click="activeCategory = cat"
                            :class="activeCategory === cat ? 'bg-brand-600 text-white' : 'bg-surface-light text-slate-400 hover:text-white'"
                            class="px-4 py-2 rounded-xl text-sm font-medium transition capitalize whitespace-nowrap"
                            x-text="cat"></button>
                </template>
            </div>
        </div>

        <!-- Menu Grid -->
        <div class="flex-1 overflow-y-auto p-6">
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                <template x-for="item in filteredMenus" :key="item.id">
                    <button @click="addToCart(item)"
                            class="menu-card bg-surface-light rounded-2xl border border-slate-700/50 p-4 text-left hover:border-brand-500/50 group cursor-pointer">
                        <!-- Icon placeholder -->
                        <div class="w-full aspect-square rounded-xl bg-gradient-to-br from-slate-700 to-slate-800 flex items-center justify-center mb-3 group-hover:from-brand-900/30 group-hover:to-brand-800/20 transition">
                            <span class="text-3xl" x-text="item.category === 'makanan' ? '🍽️' : item.category === 'minuman' ? '🥤' : '🍿'"></span>
                        </div>
                        <h3 class="text-sm font-semibold text-white truncate" x-text="item.name"></h3>
                        <p class="text-xs text-slate-500 mt-0.5 capitalize" x-text="item.category"></p>
                        <p class="text-sm font-bold text-brand-400 mt-2" x-text="formatRupiah(item.price)"></p>
                    </button>
                </template>
            </div>

            <!-- Empty state -->
            <div x-show="filteredMenus.length === 0" class="flex flex-col items-center justify-center h-64 text-slate-500">
                <svg class="w-16 h-16 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm">Menu tidak ditemukan</p>
            </div>
        </div>
    </div>

    <!-- ═══════════════ SIDEBAR: Keranjang ═══════════════ -->
    <div class="w-96 bg-surface-light border-l border-slate-700/50 flex flex-col shrink-0">

        <!-- Cart Header -->
        <div class="h-16 border-b border-slate-700/50 flex items-center justify-between px-5 shrink-0">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-brand-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
                <span class="font-semibold text-white">Keranjang</span>
                <span x-show="cart.length > 0"
                      class="ml-1 px-2 py-0.5 bg-brand-600 text-white text-xs rounded-full font-bold"
                      x-text="totalItems"></span>
            </div>
            <button @click="clearCart()" x-show="cart.length > 0"
                    class="text-xs text-red-400 hover:text-red-300 transition">
                Hapus Semua
            </button>
        </div>

        <!-- Cart Items -->
        <div class="flex-1 overflow-y-auto px-5 py-4 space-y-3">
            <template x-for="(item, idx) in cart" :key="item.menu_id + '-' + idx">
                <div class="cart-item-enter bg-surface rounded-xl p-3 border border-slate-700/50">
                    <div class="flex items-start justify-between mb-2">
                        <div class="flex-1 min-w-0">
                            <h4 class="text-sm font-medium text-white truncate" x-text="item.name"></h4>
                            <p class="text-xs text-slate-500" x-text="formatRupiah(item.price)"></p>
                        </div>
                        <button @click="removeFromCart(idx)"
                                class="ml-2 p-1 text-slate-600 hover:text-red-400 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <button @click="updateQty(idx, -1)"
                                    class="w-7 h-7 rounded-lg bg-slate-700 hover:bg-slate-600 text-white text-sm font-bold flex items-center justify-center transition">−</button>
                            <span class="w-8 text-center text-sm font-semibold text-white" x-text="item.qty"></span>
                            <button @click="updateQty(idx, 1)"
                                    class="w-7 h-7 rounded-lg bg-brand-600 hover:bg-brand-500 text-white text-sm font-bold flex items-center justify-center transition">+</button>
                        </div>
                        <span class="text-sm font-bold text-brand-400" x-text="formatRupiah(item.price * item.qty)"></span>
                    </div>
                    <!-- Notes -->
                    <input type="text" x-model="item.notes" placeholder="Catatan (opsional)..."
                           class="mt-2 w-full px-3 py-1.5 bg-surface-lighter/50 border border-slate-700 rounded-lg text-xs text-slate-300 placeholder-slate-600 focus:ring-brand-500 focus:border-brand-500">
                </div>
            </template>

            <!-- Empty cart -->
            <div x-show="cart.length === 0" class="flex flex-col items-center justify-center h-48 text-slate-600">
                <svg class="w-12 h-12 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                </svg>
                <p class="text-sm">Keranjang kosong</p>
                <p class="text-xs mt-1">Klik menu untuk menambahkan</p>
            </div>
        </div>

        <!-- Cart Summary & Checkout -->
        <div class="border-t border-slate-700/50 px-5 py-4 space-y-3 shrink-0" x-show="cart.length > 0">
            <!-- Discount -->
            <div class="flex items-center gap-2">
                <label class="text-xs text-slate-400">Diskon</label>
                <input type="number" x-model.number="discount" min="0" step="1000"
                       class="flex-1 px-3 py-1.5 bg-surface border border-slate-700 rounded-lg text-sm text-white text-right focus:ring-brand-500 focus:border-brand-500">
            </div>

            <!-- Payment Method -->
            <div>
                <label class="text-xs text-slate-400 mb-1 block">Metode Bayar</label>
                <div class="grid grid-cols-3 gap-2">
                    <template x-for="method in paymentMethods" :key="method.value">
                        <button @click="paymentMethod = method.value"
                                :class="paymentMethod === method.value ? 'bg-brand-600 text-white border-brand-500' : 'bg-surface text-slate-400 border-slate-700 hover:border-slate-600'"
                                class="px-2 py-2 rounded-lg border text-xs font-medium transition text-center"
                                x-text="method.label"></button>
                    </template>
                </div>
            </div>

            <!-- Totals -->
            <div class="space-y-1.5 pt-2 border-t border-slate-700/30">
                <div class="flex justify-between text-sm">
                    <span class="text-slate-400">Subtotal</span>
                    <span class="text-white" x-text="formatRupiah(subtotal)"></span>
                </div>
                <div class="flex justify-between text-sm" x-show="discount > 0">
                    <span class="text-slate-400">Diskon</span>
                    <span class="text-red-400" x-text="'-' + formatRupiah(discount)"></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-slate-400">PPN 11%</span>
                    <span class="text-white" x-text="formatRupiah(tax)"></span>
                </div>
                <div class="flex justify-between text-lg font-bold pt-2 border-t border-slate-700/50">
                    <span class="text-white">Total</span>
                    <span class="text-brand-400" x-text="formatRupiah(total)"></span>
                </div>
            </div>

            <!-- Checkout Button -->
            <button @click="checkout()"
                    :disabled="processingCheckout"
                    class="w-full py-3.5 bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 text-white font-bold rounded-xl shadow-lg shadow-emerald-500/20 transition flex items-center justify-center gap-2 disabled:opacity-50">
                <svg x-show="processingCheckout" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                <svg x-show="!processingCheckout" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <span x-text="processingCheckout ? 'Memproses...' : 'Bayar ' + formatRupiah(total)"></span>
            </button>
        </div>
    </div>

    <!-- ═══════════════ SUCCESS MODAL ═══════════════ -->
    <div x-show="showSuccess" x-transition.opacity
         class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div x-show="showSuccess" x-transition.scale.90
             class="bg-surface-light rounded-3xl p-8 max-w-sm w-full text-center border border-slate-700/50 shadow-2xl">
            <div class="w-20 h-20 rounded-full bg-emerald-500/10 border-2 border-emerald-500 flex items-center justify-center mx-auto mb-4">
                <svg class="w-10 h-10 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h2 class="text-xl font-bold text-white mb-1">Transaksi Berhasil!</h2>
            <p class="text-sm text-slate-400 mb-2" x-text="'Kode: ' + lastTransactionCode"></p>
            <p class="text-2xl font-bold text-emerald-400 mb-6" x-text="formatRupiah(lastTransactionTotal)"></p>
            <button @click="showSuccess = false"
                    class="w-full py-3 bg-brand-600 hover:bg-brand-500 text-white rounded-xl font-semibold transition">
                Transaksi Baru
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
function posApp() {
    return {
        // State
        outlets: @json($outlets ?? []),
        menus: @json($menus ?? []),
        selectedOutletId: {{ $outlets[0]->id ?? 'null' }},
        activeCategory: '',
        search: '',
        cart: [],
        discount: 0,
        paymentMethod: 'cash',
        processingCheckout: false,
        showSuccess: false,
        lastTransactionCode: '',
        lastTransactionTotal: 0,
        clock: '',
        userName: '{{ auth()->user()->name ?? "Kasir" }}',

        paymentMethods: [
            { value: 'cash',     label: 'Tunai' },
            { value: 'qris',     label: 'QRIS' },
            { value: 'debit',    label: 'Debit' },
            { value: 'credit',   label: 'Kredit' },
            { value: 'transfer', label: 'Transfer' },
        ],

        init() {
            this.updateClock();
            setInterval(() => this.updateClock(), 1000);
        },

        // Computed
        get currentOutlet() {
            return this.outlets.find(o => o.id == this.selectedOutletId);
        },
        get categories() {
            const cats = [...new Set(this.menus.filter(m => m.outlet_id == this.selectedOutletId).map(m => m.category))];
            return cats.filter(Boolean);
        },
        get filteredMenus() {
            return this.menus.filter(m => {
                if (m.outlet_id != this.selectedOutletId) return false;
                if (this.activeCategory && m.category !== this.activeCategory) return false;
                if (this.search && !m.name.toLowerCase().includes(this.search.toLowerCase())) return false;
                return true;
            });
        },
        get totalItems() {
            return this.cart.reduce((sum, item) => sum + item.qty, 0);
        },
        get subtotal() {
            return this.cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
        },
        get tax() {
            return Math.round((this.subtotal - this.discount) * 0.11);
        },
        get total() {
            return this.subtotal - this.discount + this.tax;
        },

        // Methods
        updateClock() {
            const now = new Date();
            this.clock = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        },

        formatRupiah(num) {
            return 'Rp ' + Number(num).toLocaleString('id-ID');
        },

        addToCart(menu) {
            const existing = this.cart.find(c => c.menu_id === menu.id);
            if (existing) {
                existing.qty++;
            } else {
                this.cart.push({
                    menu_id: menu.id,
                    name: menu.name,
                    price: parseFloat(menu.price),
                    qty: 1,
                    notes: '',
                });
            }
        },

        updateQty(idx, delta) {
            this.cart[idx].qty += delta;
            if (this.cart[idx].qty <= 0) {
                this.cart.splice(idx, 1);
            }
        },

        removeFromCart(idx) {
            this.cart.splice(idx, 1);
        },

        clearCart() {
            if (confirm('Hapus semua item dari keranjang?')) {
                this.cart = [];
                this.discount = 0;
            }
        },

        async checkout() {
            if (this.cart.length === 0) return;
            this.processingCheckout = true;

            const payload = {
                outlet_id: this.selectedOutletId,
                items: this.cart.map(c => ({
                    menu_id: c.menu_id,
                    quantity: c.qty,
                    notes: c.notes || null,
                })),
                payment_method: this.paymentMethod,
                discount: this.discount,
            };

            try {
                const res = await fetch('/api/transactions', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(payload),
                });

                const data = await res.json();

                if (res.ok) {
                    this.lastTransactionCode = data.data.transaction_code;
                    this.lastTransactionTotal = data.data.total;
                    this.showSuccess = true;
                    this.cart = [];
                    this.discount = 0;
                } else {
                    alert(data.message || 'Gagal memproses transaksi.');
                }
            } catch (e) {
                alert('Koneksi gagal. Coba lagi.');
            } finally {
                this.processingCheckout = false;
            }
        },

        async loadMenus() {
            // In production, this would fetch from API
            // For now, menus are preloaded via blade
            this.activeCategory = '';
            this.search = '';
        }
    }
}
</script>
@endpush
@endsection
