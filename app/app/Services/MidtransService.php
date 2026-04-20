<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MidtransService
{
    private string $serverKey;
    private string $baseUrl;
    private bool   $isProduction;

    public function __construct()
    {
        $this->serverKey    = config('services.midtrans.server_key', '');
        $this->isProduction = config('services.midtrans.is_production', false);
        $this->baseUrl      = $this->isProduction
            ? 'https://api.midtrans.com/v2'
            : 'https://api.sandbox.midtrans.com/v2';
    }

    /**
     * Buat Snap Token untuk pembayaran.
     * Snap Token dipakai oleh frontend (Midtrans popup).
     */
    public function createSnapToken(Booking $booking): ?string
    {
        $payload = [
            'transaction_details' => [
                'order_id'     => $booking->booking_code,
                'gross_amount' => (int) $booking->total_price,
            ],
            'customer_details' => [
                'first_name' => $booking->guest_name,
                'email'      => $booking->guest_email,
                'phone'      => $booking->guest_phone,
            ],
            'item_details' => [
                [
                    'id'       => 'room-' . $booking->room_id,
                    'price'    => (int) $booking->total_price,
                    'quantity' => 1,
                    'name'     => 'Kamar ' . ($booking->room->room_number ?? $booking->room_id),
                ],
            ],
            'callbacks' => [
                'finish' => config('app.url') . '/bookings/' . $booking->id . '/payment-complete',
            ],
        ];

        try {
            $response = Http::withBasicAuth($this->serverKey, '')
                ->post($this->getSnapUrl() . '/transactions', $payload);

            if ($response->successful()) {
                return $response->json('token');
            }

            Log::error('Midtrans Snap failed', [
                'booking' => $booking->booking_code,
                'status'  => $response->status(),
                'body'    => $response->body(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Midtrans Snap exception', [
                'booking' => $booking->booking_code,
                'error'   => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Verifikasi notifikasi pembayaran dari Midtrans webhook.
     */
    public function verifyNotification(array $payload): array
    {
        $orderId           = $payload['order_id'] ?? '';
        $transactionStatus = $payload['transaction_status'] ?? '';
        $fraudStatus       = $payload['fraud_status'] ?? 'accept';

        // Signature key verification
        $serverKey = $this->serverKey;
        $hashed    = hash('sha512',
            $orderId .
            ($payload['status_code'] ?? '') .
            ($payload['gross_amount'] ?? '') .
            $serverKey
        );

        if ($hashed !== ($payload['signature_key'] ?? '')) {
            return ['valid' => false, 'status' => 'invalid_signature'];
        }

        // Map status
        $status = match ($transactionStatus) {
            'capture'    => $fraudStatus === 'accept' ? 'confirmed' : 'pending',
            'settlement' => 'confirmed',
            'pending'    => 'pending',
            'deny', 'cancel', 'expire' => 'cancelled',
            default      => 'pending',
        };

        return [
            'valid'    => true,
            'status'   => $status,
            'order_id' => $orderId,
            'payment_reference' => $payload['transaction_id'] ?? null,
        ];
    }

    private function getSnapUrl(): string
    {
        return $this->isProduction
            ? 'https://app.midtrans.com/snap/v1'
            : 'https://app.sandbox.midtrans.com/snap/v1';
    }
}
