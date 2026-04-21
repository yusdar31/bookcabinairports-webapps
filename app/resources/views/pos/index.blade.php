@extends('layouts.app')
@section('title', 'POS Kasir')
@section('body-class', 'ops-shell font-sans antialiased min-h-screen')

@section('content')
<div class="min-h-screen p-3 sm:p-4 lg:p-5" x-data="posApp()" x-cloak>
    <div class="mx-auto flex min-h-[calc(100vh-1.5rem)] max-w-[1720px] flex-col gap-4 xl:flex-row">
        <section class="flex min-h-0 flex-1 flex-col gap-4">
            <header class="ops-panel rounded-[32px] px-5 py-5 sm:px-6">
                <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
                    <div class="flex items-start gap-4">
                        <div class="flex h-14 w-14 items-center justify-center rounded-[22px] bg-gradient-to-br from-sky-400 to-blue-600 shadow-[0_20px_36px_rgba(14,165,233,0.25)]">
                            <svg class="h-7 w-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z" />
                            </svg>
                        </div>
                        <div>
                            <p class="ops-kicker">POS Kasir</p>
                            <h1 class="ops-display mt-2 text-3xl font-extrabold text-white">Terminal transaksi untuk outlet aktif hari ini.</h1>
                            <p class="mt-2 text-sm leading-6 text-slate-300">Saya rapikan halaman kasir agar tetap cepat dipakai saat jam sibuk, dengan hierarchy yang lebih jelas antara katalog, filter, dan keranjang checkout.</p>
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-3">
                        <div class="ops-panel-soft rounded-[22px] px-4 py-3">
                            <p class="ops-kicker">Outlet aktif</p>
                            <p class="mt-2 text-sm font-semibold text-white" x-text="currentOutlet?.name || 'Pilih Outlet'"></p>
                        </div>
                        <div class="ops-panel-soft rounded-[22px] px-4 py-3">
                            <p class="ops-kicker">Kasir</p>
                            <p class="mt-2 text-sm font-semibold text-white" x-text="userName"></p>
                        </div>
                        <div class="ops-panel-soft rounded-[22px] px-4 py-3">
                            <p class="ops-kicker">Jam lokal</p>
                            <p class="mt-2 font-mono text-sm font-semibold text-sky-300" x-text="clock"></p>
                        </div>
                    </div>
                </div>
            </header>

            <div class="ops-panel rounded-[32px] p-4 sm:p-5">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex flex-1 flex-col gap-4 lg:flex-row lg:items-center">
                        <div class="min-w-0 flex-1">
                            <label class="mb-2 block text-sm font-medium text-slate-300">Cari menu</label>
                            <div class="relative">
                                <svg class="pointer-events-none absolute left-4 top-3.5 h-4 w-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                                <input type="text" x-model="search" placeholder="Cari menu, minuman, atau camilan..." class="ops-input py-3 pl-11 pr-4 text-sm">
                            </div>
                        </div>

                        <div class="w-full lg:w-[260px]">
                            <label class="mb-2 block text-sm font-medium text-slate-300">Pilih outlet</label>
                            <select x-model="selectedOutletId" @change="loadMenus()" class="ops-select px-4 py-3 text-sm">
                                <template x-for="o in outlets" :key="o.id">
                                    <option :value="o.id" x-text="o.name"></option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <a href="/dashboard" class="inline-flex items-center justify-center gap-2 rounded-2xl bg-slate-900/70 px-4 py-3 text-sm font-semibold text-slate-200 transition hover:text-white">
                        Kembali ke dashboard
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7" />
                        </svg>
                    </a>
                </div>

                <div class="mt-5 flex flex-wrap gap-2">
                    <button @click="activeCategory = ''"
                            :class="activeCategory === '' ? 'ops-chip ops-chip-active' : 'ops-chip'"
                            class="transition">
                        Semua
                    </button>
                    <template x-for="cat in categories" :key="cat">
                        <button @click="activeCategory = cat"
                                :class="activeCategory === cat ? 'ops-chip ops-chip-active' : 'ops-chip'"
                                class="capitalize transition"
                                x-text="cat"></button>
                    </template>
                </div>
            </div>

            <div class="ops-panel ops-scroll flex-1 overflow-y-auto rounded-[32px] p-4 sm:p-5">
                <div class="mb-5 flex items-center justify-between">
                    <div>
                        <p class="ops-kicker">Katalog menu</p>
                        <h2 class="ops-display mt-2 text-2xl font-bold text-white">Pilih item untuk ditambahkan ke keranjang.</h2>
                    </div>
                    <span class="ops-chip" x-text="filteredMenus.length + ' item tampil'"></span>
                </div>

                <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5">
                    <template x-for="item in filteredMenus" :key="item.id">
                        <button @click="addToCart(item)" class="ops-grid-card ops-panel-soft rounded-[26px] p-4 text-left transition hover:-translate-y-1">
                            <div class="flex aspect-square items-center justify-center rounded-[22px] bg-gradient-to-br from-slate-700 to-slate-800 text-4xl transition group-hover:from-sky-900/40 group-hover:to-blue-900/20">
                                <span x-text="item.category === 'makanan' ? 'M' : item.category === 'minuman' ? 'D' : 'S'"></span>
                            </div>
                            <div class="mt-4">
                                <p class="text-base font-semibold text-white" x-text="item.name"></p>
                                <p class="mt-1 text-xs uppercase tracking-[0.18em] text-slate-500" x-text="item.category"></p>
                                <p class="ops-display mt-4 text-xl font-bold text-sky-300" x-text="formatRupiah(item.price)"></p>
                            </div>
                        </button>
                    </template>
                </div>

                <div x-show="filteredMenus.length === 0" class="ops-panel-soft mt-6 rounded-[28px] px-6 py-14 text-center">
                    <p class="text-lg font-semibold text-white">Menu tidak ditemukan</p>
                    <p class="mt-2 text-sm text-slate-400">Ubah kata kunci pencarian atau pilih kategori lain untuk melihat item yang tersedia.</p>
                </div>
            </div>
        </section>

        <aside class="ops-panel flex w-full flex-col rounded-[32px] p-4 sm:p-5 xl:w-[420px]">
            <div class="flex items-center justify-between border-b border-slate-700/50 pb-4">
                <div>
                    <p class="ops-kicker">Cart Summary</p>
                    <h2 class="ops-display mt-2 text-2xl font-bold text-white">Keranjang checkout</h2>
                </div>
                <span x-show="cart.length > 0" class="ops-chip" x-text="totalItems + ' item'"></span>
            </div>

            <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                <div class="ops-panel-soft rounded-[22px] p-4">
                    <p class="text-sm text-slate-400">Outlet</p>
                    <p class="mt-2 text-sm font-semibold text-white" x-text="currentOutlet?.name || 'Belum dipilih'"></p>
                </div>
                <div class="ops-panel-soft rounded-[22px] p-4">
                    <p class="text-sm text-slate-400">Metode bayar aktif</p>
                    <p class="mt-2 text-sm font-semibold uppercase tracking-[0.18em] text-sky-300" x-text="paymentMethod"></p>
                </div>
            </div>

            <div class="ops-scroll mt-5 flex-1 space-y-3 overflow-y-auto pr-1">
                <template x-for="(item, idx) in cart" :key="item.menu_id + '-' + idx">
                    <article class="ops-panel-soft rounded-[24px] p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold text-white" x-text="item.name"></p>
                                <p class="mt-1 text-xs text-slate-500" x-text="formatRupiah(item.price)"></p>
                            </div>
                            <button @click="removeFromCart(idx)" class="rounded-xl p-2 text-slate-500 transition hover:text-red-300">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>

                        <div class="mt-4 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <button @click="updateQty(idx, -1)" class="flex h-8 w-8 items-center justify-center rounded-xl bg-slate-700 text-sm font-bold text-white transition hover:bg-slate-600">-</button>
                                <span class="w-8 text-center text-sm font-semibold text-white" x-text="item.qty"></span>
                                <button @click="updateQty(idx, 1)" class="flex h-8 w-8 items-center justify-center rounded-xl bg-sky-600 text-sm font-bold text-white transition hover:bg-sky-500">+</button>
                            </div>
                            <span class="text-sm font-bold text-sky-300" x-text="formatRupiah(item.price * item.qty)"></span>
                        </div>

                        <input type="text" x-model="item.notes" placeholder="Catatan item (opsional)" class="ops-input mt-4 px-4 py-2.5 text-xs">
                    </article>
                </template>

                <div x-show="cart.length === 0" class="ops-panel-soft rounded-[28px] px-6 py-14 text-center">
                    <p class="text-base font-semibold text-white">Keranjang masih kosong</p>
                    <p class="mt-2 text-sm text-slate-400">Klik menu di katalog untuk mulai menambahkan item ke transaksi.</p>
                </div>
            </div>

            <div class="mt-5 border-t border-slate-700/50 pt-5" x-show="cart.length > 0">
                <div class="space-y-4">
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-300">Diskon</label>
                        <input type="number" x-model.number="discount" min="0" step="1000" class="ops-input px-4 py-3 text-right text-sm">
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-300">Metode bayar</label>
                        <div class="grid grid-cols-3 gap-2">
                            <template x-for="method in paymentMethods" :key="method.value">
                                <button @click="paymentMethod = method.value"
                                        :class="paymentMethod === method.value ? 'ops-chip ops-chip-active justify-center' : 'ops-chip justify-center'"
                                        class="px-2 py-2 text-center text-xs"
                                        x-text="method.label"></button>
                            </template>
                        </div>
                    </div>

                    <div class="ops-panel-soft rounded-[24px] p-4">
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between"><span class="text-slate-400">Subtotal</span><span class="text-white" x-text="formatRupiah(subtotal)"></span></div>
                            <div class="flex justify-between" x-show="discount > 0"><span class="text-slate-400">Diskon</span><span class="text-red-300" x-text="'-' + formatRupiah(discount)"></span></div>
                            <div class="flex justify-between"><span class="text-slate-400">PPN 11%</span><span class="text-white" x-text="formatRupiah(tax)"></span></div>
                            <div class="flex justify-between border-t border-slate-700 pt-3 text-base font-bold"><span class="text-white">Total</span><span class="text-sky-300" x-text="formatRupiah(total)"></span></div>
                        </div>
                    </div>

                    <button @click="checkout()" :disabled="processingCheckout" class="flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-emerald-500 to-emerald-600 px-4 py-3.5 text-sm font-bold text-white shadow-[0_18px_32px_rgba(16,185,129,0.22)] transition hover:-translate-y-0.5 disabled:opacity-50">
                        <svg x-show="processingCheckout" class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        <span x-text="processingCheckout ? 'Memproses transaksi...' : 'Bayar ' + formatRupiah(total)"></span>
                    </button>

                    <button @click="clearCart()" class="w-full rounded-2xl border border-slate-700 bg-slate-900/60 px-4 py-3 text-sm font-semibold text-slate-300 transition hover:border-slate-500">
                        Hapus semua item
                    </button>
                </div>
            </div>
        </aside>
    </div>

    <div x-show="showSuccess" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 p-4 backdrop-blur-sm">
        <div x-show="showSuccess" x-transition.scale.90 class="ops-panel w-full max-w-sm rounded-[34px] p-8 text-center">
            <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full border-2 border-emerald-500 bg-emerald-500/10">
                <svg class="h-10 w-10 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h2 class="ops-display mt-5 text-2xl font-bold text-white">Transaksi berhasil diproses</h2>
            <p class="mt-2 text-sm text-slate-400" x-text="'Kode: ' + lastTransactionCode"></p>
            <p class="ops-display mt-4 text-3xl font-bold text-emerald-400" x-text="formatRupiah(lastTransactionTotal)"></p>
            <button @click="showSuccess = false" class="mt-6 w-full rounded-2xl bg-gradient-to-r from-sky-500 to-blue-600 px-4 py-3 text-sm font-semibold text-white transition hover:-translate-y-0.5">
                Buat transaksi baru
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
function posApp() {
    return {
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
            { value: 'cash', label: 'Tunai' },
            { value: 'qris', label: 'QRIS' },
            { value: 'debit', label: 'Debit' },
            { value: 'credit', label: 'Kredit' },
            { value: 'transfer', label: 'Transfer' },
        ],

        init() {
            this.updateClock();
            setInterval(() => this.updateClock(), 1000);
        },

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
            this.activeCategory = '';
            this.search = '';
        }
    }
}
</script>
@endpush
@endsection
