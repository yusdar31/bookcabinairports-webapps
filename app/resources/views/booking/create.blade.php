@extends('layouts.app')
@section('title', 'Booking Baru')
@section('body-class', 'ops-shell font-sans antialiased min-h-screen')

@section('content')
<div class="min-h-screen px-4 py-6 sm:px-6 lg:px-8" x-data="bookingWizard()" x-cloak>
    <div class="mx-auto w-full max-w-6xl">
        <header class="ops-panel rounded-[34px] px-6 py-6 sm:px-8">
            <div class="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-start gap-4">
                    <a href="/pos" class="mt-1 flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-900/70 text-slate-300 transition hover:text-white">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                    </a>
                    <div>
                        <p class="ops-kicker">Booking Desk</p>
                        <h1 class="ops-display mt-2 text-3xl font-extrabold text-white sm:text-4xl">Buat booking baru dengan alur yang lebih jelas dan cepat dibaca.</h1>
                        <p class="mt-3 max-w-3xl text-sm leading-7 text-slate-300">Halaman ini saya rapikan jadi seperti wizard reservasi operasional: tanggal dan kamar lebih jelas, data tamu tidak tenggelam, dan ringkasan pembayaran terasa lebih meyakinkan sebelum konfirmasi.</p>
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="ops-panel-soft rounded-[24px] px-4 py-4">
                        <p class="ops-kicker">Alur</p>
                        <p class="mt-2 text-sm font-semibold text-white">Pilih kamar, isi data tamu, lalu konfirmasi pembayaran.</p>
                    </div>
                    <div class="ops-panel-soft rounded-[24px] px-4 py-4">
                        <p class="ops-kicker">Sumber</p>
                        <p class="mt-2 text-sm font-semibold text-white">Tetap memakai endpoint booking yang sama.</p>
                    </div>
                </div>
            </div>
        </header>

        <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1.15fr)_340px]">
            <section>
                <div class="ops-panel rounded-[34px] p-6 sm:p-8">
                    <div class="flex flex-wrap items-center gap-3">
                        <template x-for="(s, i) in steps" :key="i">
                            <div class="flex items-center gap-3">
                                <div class="flex items-center gap-3 rounded-2xl px-3 py-2" :class="step === i ? 'bg-sky-500/14 text-white' : 'bg-slate-900/55 text-slate-400'">
                                    <div class="flex h-9 w-9 items-center justify-center rounded-2xl text-sm font-bold"
                                         :class="step > i ? 'bg-emerald-500 text-white' : step === i ? 'bg-sky-500 text-white' : 'bg-slate-700 text-slate-300'">
                                        <template x-if="step > i">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </template>
                                        <template x-if="step <= i">
                                            <span x-text="i + 1"></span>
                                        </template>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold" x-text="s"></p>
                                        <p class="text-xs text-slate-400" x-text="i === 0 ? 'Kamar dan waktu' : i === 1 ? 'Identitas tamu' : 'Pembayaran akhir'"></p>
                                    </div>
                                </div>
                                <div x-show="i < steps.length - 1" class="hidden h-px w-8 bg-slate-700 sm:block"></div>
                            </div>
                        </template>
                    </div>

                    <div class="mt-8">
                        <div x-show="step === 0" x-transition>
                            <div class="grid gap-6">
                                <div class="grid gap-4 md:grid-cols-2">
                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-slate-300">Check-in</label>
                                        <input type="datetime-local" x-model="form.check_in" class="ops-input px-4 py-3 text-sm">
                                    </div>
                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-slate-300">Check-out</label>
                                        <input type="datetime-local" x-model="form.check_out" class="ops-input px-4 py-3 text-sm">
                                    </div>
                                </div>

                                <div class="flex flex-wrap items-center gap-3">
                                    <button @click="checkAvailability()" :disabled="!form.check_in || !form.check_out || loadingRooms" class="rounded-2xl bg-gradient-to-r from-sky-500 to-blue-600 px-5 py-3 text-sm font-semibold text-white shadow-[0_18px_32px_rgba(14,165,233,0.22)] transition hover:-translate-y-0.5 disabled:opacity-50">
                                        <span x-text="loadingRooms ? 'Memeriksa kamar...' : 'Cek ketersediaan'"></span>
                                    </button>
                                    <span class="ops-chip">Tarif dihitung otomatis dari durasi</span>
                                </div>

                                <div class="grid gap-3" x-show="availableRooms.length > 0">
                                    <template x-for="room in availableRooms" :key="room.id">
                                        <button @click="selectRoom(room)"
                                                :class="form.room_id === room.id ? 'border-sky-500/60 bg-sky-500/10' : 'border-slate-700/60 hover:border-slate-500/60'"
                                                class="ops-panel-soft w-full rounded-[26px] border p-5 text-left transition">
                                            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                                <div>
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <span class="text-lg font-semibold text-white" x-text="'Kamar ' + room.room_number"></span>
                                                        <span class="rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase" :class="room.type === 'vip' ? 'bg-amber-500/20 text-amber-300' : 'bg-slate-700 text-slate-300'" x-text="room.type"></span>
                                                        <span class="ops-chip">Lantai <span x-text="room.floor"></span></span>
                                                    </div>
                                                    <p class="mt-2 text-sm text-slate-400">Pilih kamar ini untuk melanjutkan ke data tamu dan kalkulasi total.</p>
                                                </div>
                                                <div class="text-left sm:text-right">
                                                    <p class="text-base font-semibold text-sky-300" x-text="formatRupiah(room.price_per_hour) + ' / jam'"></p>
                                                    <p class="mt-1 text-sm text-slate-400" x-text="formatRupiah(room.price_per_night) + ' / malam'"></p>
                                                </div>
                                            </div>
                                        </button>
                                    </template>
                                </div>

                                <div x-show="checkedAvailability && availableRooms.length === 0" class="ops-panel-soft rounded-[28px] px-6 py-10 text-center">
                                    <p class="text-base font-semibold text-white">Tidak ada kamar tersedia untuk rentang waktu ini.</p>
                                    <p class="mt-2 text-sm text-slate-400">Coba geser jam check-in atau check-out untuk menemukan kamar yang tersedia.</p>
                                </div>
                            </div>
                        </div>

                        <div x-show="step === 1" x-transition>
                            <div class="grid gap-5">
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-slate-300">Nama lengkap tamu *</label>
                                    <input type="text" x-model="form.guest_name" required class="ops-input px-4 py-3 text-sm" placeholder="Nama tamu">
                                </div>
                                <div class="grid gap-4 md:grid-cols-2">
                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-slate-300">Email</label>
                                        <input type="email" x-model="form.guest_email" class="ops-input px-4 py-3 text-sm" placeholder="email@contoh.com">
                                    </div>
                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-slate-300">Nomor HP</label>
                                        <input type="text" x-model="form.guest_phone" class="ops-input px-4 py-3 text-sm" placeholder="08xxxxxxxxxx">
                                    </div>
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-slate-300">No. KTP / Paspor</label>
                                    <input type="text" x-model="form.guest_id_number" class="ops-input px-4 py-3 text-sm" placeholder="Nomor identitas">
                                </div>
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-slate-300">Catatan</label>
                                    <textarea x-model="form.notes" rows="3" class="ops-textarea px-4 py-3 text-sm" placeholder="Kebutuhan khusus, preferensi kamar, atau catatan resepsionis"></textarea>
                                </div>
                            </div>
                        </div>

                        <div x-show="step === 2" x-transition>
                            <div class="grid gap-6">
                                <div class="ops-panel-soft rounded-[28px] p-5">
                                    <p class="ops-kicker">Ringkasan akhir</p>
                                    <div class="mt-4 space-y-3 text-sm">
                                        <div class="flex justify-between gap-4"><span class="text-slate-400">Kamar</span><span class="text-right font-medium text-white" x-text="selectedRoom?.room_number + ' (' + selectedRoom?.type + ')'"></span></div>
                                        <div class="flex justify-between gap-4"><span class="text-slate-400">Check-in</span><span class="text-right text-white" x-text="formatDate(form.check_in)"></span></div>
                                        <div class="flex justify-between gap-4"><span class="text-slate-400">Check-out</span><span class="text-right text-white" x-text="formatDate(form.check_out)"></span></div>
                                        <div class="flex justify-between gap-4"><span class="text-slate-400">Tamu</span><span class="text-right text-white" x-text="form.guest_name"></span></div>
                                        <div class="flex justify-between gap-4 border-t border-slate-700 pt-4 text-base font-semibold"><span class="text-white">Total estimasi</span><span class="text-sky-300" x-text="formatRupiah(estimatedPrice)"></span></div>
                                    </div>
                                </div>

                                <div>
                                    <label class="mb-3 block text-sm font-medium text-slate-300">Metode pembayaran</label>
                                    <div class="grid gap-3 sm:grid-cols-2">
                                        <template x-for="m in ['cash', 'midtrans', 'transfer', 'ota']" :key="m">
                                            <button @click="form.payment_method = m"
                                                    :class="form.payment_method === m ? 'ops-chip-active' : ''"
                                                    class="ops-chip justify-center rounded-2xl px-4 py-3 text-sm capitalize"
                                                    x-text="m"></button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-8 flex flex-col gap-3 border-t border-slate-700/50 pt-6 sm:flex-row sm:items-center sm:justify-between">
                        <button @click="step--" x-show="step > 0" class="rounded-2xl border border-slate-700 bg-slate-900/60 px-5 py-3 text-sm font-semibold text-slate-300 transition hover:border-slate-500">
                            Kembali
                        </button>
                        <div x-show="step === 0" class="hidden sm:block"></div>

                        <button x-show="step < 2" @click="nextStep()" :disabled="!canNext" class="rounded-2xl bg-gradient-to-r from-sky-500 to-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-[0_18px_32px_rgba(14,165,233,0.22)] transition hover:-translate-y-0.5 disabled:opacity-50">
                            Lanjut
                        </button>

                        <button x-show="step === 2" @click="submitBooking()" :disabled="submitting" class="flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-emerald-500 to-emerald-600 px-7 py-3 text-sm font-bold text-white shadow-[0_18px_32px_rgba(16,185,129,0.22)] transition hover:-translate-y-0.5 disabled:opacity-50">
                            <svg x-show="submitting" class="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            Konfirmasi Booking
                        </button>
                    </div>
                </div>
            </section>

            <aside class="ops-panel rounded-[34px] p-6">
                <p class="ops-kicker">Quick Summary</p>
                <h2 class="ops-display mt-2 text-2xl font-bold text-white">Snapshot reservasi aktif</h2>

                <div class="mt-6 space-y-4">
                    <div class="ops-panel-soft rounded-[24px] p-4">
                        <p class="text-sm text-slate-400">Status langkah</p>
                        <p class="mt-2 text-base font-semibold text-white" x-text="step === 0 ? 'Pilih kamar' : step === 1 ? 'Isi data tamu' : 'Final review'"></p>
                    </div>
                    <div class="ops-panel-soft rounded-[24px] p-4">
                        <p class="text-sm text-slate-400">Kamar terpilih</p>
                        <p class="mt-2 text-base font-semibold text-white" x-text="selectedRoom ? 'Kamar ' + selectedRoom.room_number : 'Belum dipilih'"></p>
                    </div>
                    <div class="ops-panel-soft rounded-[24px] p-4">
                        <p class="text-sm text-slate-400">Estimasi total</p>
                        <p class="ops-display mt-2 text-2xl font-bold text-sky-300" x-text="formatRupiah(estimatedPrice)"></p>
                    </div>
                    <div class="rounded-[28px] bg-gradient-to-br from-sky-500/16 to-blue-600/16 p-5">
                        <p class="ops-kicker">Catatan Operasional</p>
                        <p class="mt-3 text-sm leading-6 text-slate-300">Begitu booking berhasil, kode booking dan PIN akan tampil di modal konfirmasi seperti sebelumnya. Saya hanya merapikan pengalaman visual dan urutan bacaannya.</p>
                    </div>
                </div>
            </aside>
        </div>
    </div>

    <div x-show="showSuccess" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/80 p-4 backdrop-blur-sm">
        <div x-show="showSuccess" x-transition.scale.90 class="ops-panel w-full max-w-sm rounded-[34px] p-8 text-center">
            <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full border-2 border-emerald-500 bg-emerald-500/10">
                <svg class="h-10 w-10 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <h2 class="ops-display mt-5 text-2xl font-bold text-white">Booking berhasil dibuat</h2>
            <p class="mt-2 text-sm text-slate-400" x-text="'Kode: ' + successData.booking_code"></p>
            <p class="mt-1 text-sm text-slate-400">PIN: <span class="font-mono text-lg font-semibold text-sky-300" x-text="successData.pin_code"></span></p>
            <p class="ops-display mt-5 text-3xl font-bold text-emerald-400" x-text="formatRupiah(successData.total_price)"></p>
            <a href="/pos" class="mt-6 block rounded-2xl bg-gradient-to-r from-sky-500 to-blue-600 px-4 py-3 text-sm font-semibold text-white transition hover:-translate-y-0.5">Kembali ke POS</a>
        </div>
    </div>
</div>

@push('scripts')
<script>
function bookingWizard() {
    return {
        step: 0,
        steps: ['Pilih Kamar', 'Data Tamu', 'Konfirmasi'],
        form: { check_in: '', check_out: '', room_id: null, guest_name: '', guest_email: '', guest_phone: '', guest_id_number: '', notes: '', payment_method: 'cash', source: 'direct' },
        availableRooms: [],
        selectedRoom: null,
        checkedAvailability: false,
        loadingRooms: false,
        submitting: false,
        showSuccess: false,
        successData: {},

        get canNext() {
            if (this.step === 0) return this.form.room_id !== null;
            if (this.step === 1) return this.form.guest_name.trim() !== '';
            return true;
        },
        get estimatedPrice() {
            if (!this.selectedRoom || !this.form.check_in || !this.form.check_out) return 0;
            const ci = new Date(this.form.check_in), co = new Date(this.form.check_out);
            const hours = Math.max(1, Math.ceil((co - ci) / 3600000));
            return hours <= 12 ? this.selectedRoom.price_per_hour * hours : this.selectedRoom.price_per_night * Math.max(1, Math.ceil((co - ci) / 86400000));
        },

        nextStep() { if (this.canNext) this.step++; },

        selectRoom(room) { this.form.room_id = room.id; this.selectedRoom = room; },

        formatRupiah(n) { return 'Rp ' + Number(n).toLocaleString('id-ID'); },
        formatDate(d) { return d ? new Date(d).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' }) : '-'; },

        async checkAvailability() {
            this.loadingRooms = true;
            try {
                const res = await fetch(`/api/rooms/availability?check_in=${this.form.check_in}&check_out=${this.form.check_out}`, {
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    credentials: 'same-origin',
                });
                const data = await res.json();
                this.availableRooms = data.available_rooms || [];
            } catch (e) { alert('Gagal cek ketersediaan.'); }
            this.checkedAvailability = true;
            this.loadingRooms = false;
        },

        async submitBooking() {
            this.submitting = true;
            try {
                const res = await fetch('/api/bookings', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    credentials: 'same-origin',
                    body: JSON.stringify(this.form),
                });
                const data = await res.json();
                if (res.ok) { this.successData = data.data; this.showSuccess = true; }
                else alert(data.message || 'Gagal membuat booking.');
            } catch (e) { alert('Koneksi gagal.'); }
            this.submitting = false;
        },
    }
}
</script>
@endpush
@endsection
