<?php

namespace App\Notifications;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingConfirmation extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Booking $booking
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $b = $this->booking->load('room');

        return (new MailMessage)
            ->subject("Konfirmasi Booking #{$b->booking_code} — Bookcabin")
            ->greeting("Halo, {$b->guest_name}!")
            ->line("Booking Anda telah dikonfirmasi. Berikut detailnya:")
            ->line("**Kode Booking:** {$b->booking_code}")
            ->line("**Kamar:** {$b->room->room_number} ({$b->room->type})")
            ->line("**Check-in:** {$b->check_in->format('d M Y, H:i')} WITA")
            ->line("**Check-out:** {$b->check_out->format('d M Y, H:i')} WITA")
            ->line("**Total:** Rp " . number_format($b->total_price, 0, ',', '.'))
            ->line("**PIN Kamar:** {$b->pin_code}")
            ->action('Lihat Booking', config('app.url') . '/bookings/' . $b->id)
            ->line('Gunakan PIN atau scan QR code saat check-in. Selamat beristirahat!')
            ->salutation('Salam, Tim Bookcabin');
    }
}
