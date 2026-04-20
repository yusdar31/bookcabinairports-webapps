<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Notifications\BookingConfirmation;
use App\Services\MidtransService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class PaymentController extends Controller
{
    /**
     * POST /api/bookings/{booking}/pay
     * Generate Midtrans Snap Token untuk pembayaran booking.
     */
    public function createPayment(Booking $booking, MidtransService $midtrans): JsonResponse
    {
        if ($booking->status !== 'pending') {
            return response()->json(['message' => 'Booking ini tidak menunggu pembayaran.'], 422);
        }

        $snapToken = $midtrans->createSnapToken($booking);

        if (!$snapToken) {
            return response()->json(['message' => 'Gagal membuat link pembayaran Midtrans.'], 500);
        }

        return response()->json([
            'snap_token'   => $snapToken,
            'booking_code' => $booking->booking_code,
            'total'        => $booking->total_price,
        ]);
    }

    /**
     * POST /api/webhooks/midtrans
     * Webhook callback dari Midtrans (public, tanpa auth).
     */
    public function midtransWebhook(Request $request, MidtransService $midtrans): JsonResponse
    {
        $payload = $request->all();

        Log::info('Midtrans webhook received', ['order_id' => $payload['order_id'] ?? 'unknown']);

        $result = $midtrans->verifyNotification($payload);

        if (!$result['valid']) {
            Log::warning('Midtrans webhook invalid signature', $payload);
            return response()->json(['message' => 'Invalid signature.'], 403);
        }

        $booking = Booking::where('booking_code', $result['order_id'])->first();

        if (!$booking) {
            return response()->json(['message' => 'Booking not found.'], 404);
        }

        // Update status booking
        $booking->update([
            'status'            => $result['status'],
            'payment_reference' => $result['payment_reference'],
        ]);

        // Kirim email konfirmasi jika confirmed
        if ($result['status'] === 'confirmed' && $booking->guest_email) {
            Notification::route('mail', $booking->guest_email)
                ->notify(new BookingConfirmation($booking));
        }

        Log::info("Midtrans webhook processed: {$booking->booking_code} → {$result['status']}");

        return response()->json(['message' => 'OK']);
    }
}
