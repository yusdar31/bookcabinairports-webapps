@extends('layouts.app')
@section('title', 'Booking Baru')

@section('content')
<div class="min-h-screen py-8 px-4" x-data="bookingWizard()" x-cloak>
    <div class="max-w-3xl mx-auto">

        <!-- Header -->
        <div class="flex items-center gap-3 mb-8">
            <a href="/pos" class="p-2 rounded-lg bg-surface-light text-slate-400 hover:text-white transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <h1 class="text-xl font-bold text-white">Booking Baru</h1>
        </div>

        <!-- Step Indicator -->
        <div class="flex items-center mb-8">
            <template x-for="(s, i) in steps" :key="i">
                <div class="flex items-center" :class="i < steps.length - 1 ? 'flex-1' : ''">
                    <div class="flex items-center gap-2">
                        <div :class="step > i ? 'bg-emerald-500' : step === i ? 'bg-brand-500' : 'bg-slate-700'"
                             class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold text-white transition-colors">
                            <template x-if="step > i">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            </template>
                            <template x-if="step <= i">
                                <span x-text="i + 1"></span>
                            </template>
                        </div>
                        <span class="text-sm font-medium hidden sm:block"
                              :class="step >= i ? 'text-white' : 'text-slate-500'"
                              x-text="s"></span>
                    </div>
                    <div x-show="i < steps.length - 1"
                         class="flex-1 h-0.5 mx-3"
                         :class="step > i ? 'bg-emerald-500' : 'bg-slate-700'"></div>
                </div>
            </template>
        </div>

        <!-- Step 1: Pilih Kamar -->
        <div x-show="step === 0" x-transition>
            <div class="bg-surface-light rounded-2xl border border-slate-700/50 p-6">
                <h2 class="text-lg font-semibold text-white mb-4">Pilih Tanggal & Kamar</h2>

                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div>
                        <label class="block text-sm text-slate-400 mb-1">Check-in</label>
                        <input type="datetime-local" x-model="form.check_in"
                               class="w-full px-4 py-2.5 bg-surface border border-slate-600 rounded-xl text-white focus:ring-brand-500 focus:border-brand-500">
                    </div>
                    <div>
                        <label class="block text-sm text-slate-400 mb-1">Check-out</label>
                        <input type="datetime-local" x-model="form.check_out"
                               class="w-full px-4 py-2.5 bg-surface border border-slate-600 rounded-xl text-white focus:ring-brand-500 focus:border-brand-500">
                    </div>
                </div>

                <button @click="checkAvailability()" :disabled="!form.check_in || !form.check_out || loadingRooms"
                        class="mb-6 px-5 py-2.5 bg-brand-600 hover:bg-brand-500 text-white rounded-xl font-medium transition disabled:opacity-50 flex items-center gap-2">
                    <svg x-show="loadingRooms" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    Cek Ketersediaan
                </button>

                <!-- Daftar kamar tersedia -->
                <div x-show="availableRooms.length > 0" class="space-y-3">
                    <template x-for="room in availableRooms" :key="room.id">
                        <button @click="selectRoom(room)"
                                :class="form.room_id === room.id ? 'border-brand-500 bg-brand-500/10' : 'border-slate-700 hover:border-slate-600'"
                                class="w-full p-4 rounded-xl border text-left transition flex items-center justify-between">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="text-white font-semibold" x-text="room.room_number"></span>
                                    <span :class="room.type === 'vip' ? 'bg-amber-500/20 text-amber-400' : 'bg-slate-600/50 text-slate-300'"
                                          class="px-2 py-0.5 rounded-md text-xs font-medium uppercase" x-text="room.type"></span>
                                </div>
                                <p class="text-xs text-slate-500 mt-1">Lantai <span x-text="room.floor"></span></p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-bold text-brand-400" x-text="formatRupiah(room.price_per_hour) + '/jam'"></p>
                                <p class="text-xs text-slate-500" x-text="formatRupiah(room.price_per_night) + '/malam'"></p>
                            </div>
                        </button>
                    </template>
                </div>
                <p x-show="checkedAvailability && availableRooms.length === 0" class="text-sm text-slate-500 text-center py-8">
                    Tidak ada kamar tersedia untuk waktu tersebut.
                </p>
            </div>
        </div>

        <!-- Step 2: Data Tamu -->
        <div x-show="step === 1" x-transition>
            <div class="bg-surface-light rounded-2xl border border-slate-700/50 p-6 space-y-4">
                <h2 class="text-lg font-semibold text-white mb-2">Data Tamu</h2>
                <div>
                    <label class="block text-sm text-slate-400 mb-1">Nama Lengkap *</label>
                    <input type="text" x-model="form.guest_name" required
                           class="w-full px-4 py-2.5 bg-surface border border-slate-600 rounded-xl text-white focus:ring-brand-500 focus:border-brand-500" placeholder="Nama tamu">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-slate-400 mb-1">Email</label>
                        <input type="email" x-model="form.guest_email"
                               class="w-full px-4 py-2.5 bg-surface border border-slate-600 rounded-xl text-white focus:ring-brand-500 focus:border-brand-500" placeholder="email@contoh.com">
                    </div>
                    <div>
                        <label class="block text-sm text-slate-400 mb-1">No. HP</label>
                        <input type="text" x-model="form.guest_phone"
                               class="w-full px-4 py-2.5 bg-surface border border-slate-600 rounded-xl text-white focus:ring-brand-500 focus:border-brand-500" placeholder="08xxx">
                    </div>
                </div>
                <div>
                    <label class="block text-sm text-slate-400 mb-1">No. KTP / Paspor</label>
                    <input type="text" x-model="form.guest_id_number"
                           class="w-full px-4 py-2.5 bg-surface border border-slate-600 rounded-xl text-white focus:ring-brand-500 focus:border-brand-500" placeholder="Nomor identitas">
                </div>
                <div>
                    <label class="block text-sm text-slate-400 mb-1">Catatan</label>
                    <textarea x-model="form.notes" rows="2"
                              class="w-full px-4 py-2.5 bg-surface border border-slate-600 rounded-xl text-white focus:ring-brand-500 focus:border-brand-500 resize-none" placeholder="Catatan tambahan..."></textarea>
                </div>
            </div>
        </div>

        <!-- Step 3: Pembayaran & Konfirmasi -->
        <div x-show="step === 2" x-transition>
            <div class="bg-surface-light rounded-2xl border border-slate-700/50 p-6">
                <h2 class="text-lg font-semibold text-white mb-4">Ringkasan & Pembayaran</h2>

                <div class="space-y-3 mb-6">
                    <div class="flex justify-between text-sm"><span class="text-slate-400">Kamar</span><span class="text-white font-medium" x-text="selectedRoom?.room_number + ' (' + selectedRoom?.type + ')'"></span></div>
                    <div class="flex justify-between text-sm"><span class="text-slate-400">Check-in</span><span class="text-white" x-text="formatDate(form.check_in)"></span></div>
                    <div class="flex justify-between text-sm"><span class="text-slate-400">Check-out</span><span class="text-white" x-text="formatDate(form.check_out)"></span></div>
                    <div class="flex justify-between text-sm"><span class="text-slate-400">Tamu</span><span class="text-white" x-text="form.guest_name"></span></div>
                    <div class="flex justify-between text-lg font-bold pt-3 border-t border-slate-700"><span class="text-white">Total</span><span class="text-brand-400" x-text="formatRupiah(estimatedPrice)"></span></div>
                </div>

                <div class="mb-6">
                    <label class="block text-sm text-slate-400 mb-2">Metode Pembayaran</label>
                    <div class="grid grid-cols-2 gap-2">
                        <template x-for="m in ['cash','midtrans','transfer','ota']" :key="m">
                            <button @click="form.payment_method = m"
                                    :class="form.payment_method === m ? 'bg-brand-600 text-white border-brand-500' : 'bg-surface text-slate-400 border-slate-700'"
                                    class="px-4 py-2.5 rounded-xl border text-sm font-medium capitalize transition" x-text="m"></button>
                        </template>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigation Buttons -->
        <div class="flex justify-between mt-6">
            <button @click="step--" x-show="step > 0"
                    class="px-6 py-2.5 bg-surface-light border border-slate-700 text-slate-300 rounded-xl hover:border-slate-600 transition font-medium">
                ← Kembali
            </button>
            <div x-show="step === 0"></div>

            <button x-show="step < 2" @click="nextStep()"
                    :disabled="!canNext"
                    class="px-6 py-2.5 bg-brand-600 hover:bg-brand-500 text-white rounded-xl font-medium transition disabled:opacity-50">
                Lanjut →
            </button>

            <button x-show="step === 2" @click="submitBooking()" :disabled="submitting"
                    class="px-8 py-3 bg-gradient-to-r from-emerald-500 to-emerald-600 hover:from-emerald-600 hover:to-emerald-700 text-white rounded-xl font-bold shadow-lg shadow-emerald-500/20 transition disabled:opacity-50 flex items-center gap-2">
                <svg x-show="submitting" class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                Konfirmasi Booking
            </button>
        </div>
    </div>

    <!-- Success Modal -->
    <div x-show="showSuccess" x-transition.opacity class="fixed inset-0 bg-black/70 backdrop-blur-sm z-50 flex items-center justify-center p-4">
        <div x-show="showSuccess" x-transition.scale.90 class="bg-surface-light rounded-3xl p-8 max-w-sm w-full text-center border border-slate-700/50 shadow-2xl">
            <div class="w-20 h-20 rounded-full bg-emerald-500/10 border-2 border-emerald-500 flex items-center justify-center mx-auto mb-4">
                <svg class="w-10 h-10 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
            <h2 class="text-xl font-bold text-white mb-1">Booking Berhasil!</h2>
            <p class="text-sm text-slate-400 mb-1" x-text="'Kode: ' + successData.booking_code"></p>
            <p class="text-sm text-slate-400 mb-1">PIN: <span class="font-mono text-brand-400 text-lg" x-text="successData.pin_code"></span></p>
            <p class="text-2xl font-bold text-emerald-400 mb-6" x-text="formatRupiah(successData.total_price)"></p>
            <a href="/pos" class="block w-full py-3 bg-brand-600 hover:bg-brand-500 text-white rounded-xl font-semibold transition text-center">Kembali</a>
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
