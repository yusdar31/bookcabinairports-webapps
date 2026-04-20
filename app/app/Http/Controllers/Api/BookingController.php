<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BookingController extends Controller
{
    /**
     * GET /api/bookings
     * Daftar booking dengan filter status & tanggal.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Booking::with(['room', 'user']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date')) {
            $query->whereDate('check_in', $request->date);
        }

        $bookings = $query->orderByDesc('created_at')->paginate(15);

        return response()->json($bookings);
    }

    /**
     * POST /api/bookings
     * Buat booking baru (direct / walk-in).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'room_id'         => 'required|exists:rooms,id',
            'guest_name'      => 'required|string|max:255',
            'guest_email'     => 'nullable|email|max:255',
            'guest_phone'     => 'nullable|string|max:20',
            'guest_id_number' => 'nullable|string|max:30',
            'check_in'        => 'required|date|after_or_equal:now',
            'check_out'       => 'required|date|after:check_in',
            'payment_method'  => 'in:midtrans,cash,transfer,ota',
            'source'          => 'in:direct,ota,website',
            'notes'           => 'nullable|string|max:500',
        ]);

        return DB::transaction(function () use ($validated, $request) {
            $room = Room::lockForUpdate()->findOrFail($validated['room_id']);

            // Cek double-booking
            $conflict = Booking::where('room_id', $room->id)
                ->whereNotIn('status', ['cancelled', 'no_show', 'checked_out'])
                ->where(fn ($q) => $q
                    ->whereBetween('check_in', [$validated['check_in'], $validated['check_out']])
                    ->orWhereBetween('check_out', [$validated['check_in'], $validated['check_out']])
                    ->orWhere(fn ($q2) => $q2
                        ->where('check_in', '<=', $validated['check_in'])
                        ->where('check_out', '>=', $validated['check_out'])
                    )
                )
                ->exists();

            if ($conflict) {
                return response()->json([
                    'message' => 'Kamar sudah dipesan untuk rentang waktu tersebut.',
                ], 409);
            }

            // Hitung harga
            $checkIn  = new \DateTime($validated['check_in']);
            $checkOut = new \DateTime($validated['check_out']);
            $hours    = max(1, (int) ceil($checkIn->diff($checkOut)->h + ($checkIn->diff($checkOut)->days * 24)));
            $totalPrice = $hours <= 12
                ? $room->price_per_hour * $hours
                : $room->price_per_night * max(1, $checkIn->diff($checkOut)->days);

            $booking = Booking::create(array_merge($validated, [
                'booking_code'   => 'BK-' . now()->format('Ymd') . '-' . strtoupper(Str::random(4)),
                'user_id'        => $request->user()->id,
                'total_price'    => $totalPrice,
                'status'         => 'pending',
                'pin_code'       => str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT),
                'qr_token'       => Str::uuid()->toString(),
                'payment_method' => $validated['payment_method'] ?? 'cash',
                'source'         => $validated['source'] ?? 'direct',
            ]));

            // Update status kamar
            $room->update(['status' => 'occupied']);

            return response()->json([
                'message' => 'Booking berhasil dibuat.',
                'data'    => $booking->load('room'),
            ], 201);
        });
    }

    /**
     * GET /api/bookings/{booking}
     */
    public function show(Booking $booking): JsonResponse
    {
        return response()->json($booking->load(['room', 'user']));
    }

    /**
     * POST /api/bookings/{booking}/check-in
     * QR / PIN check-in.
     */
    public function checkIn(Request $request, Booking $booking): JsonResponse
    {
        if (!$booking->canCheckIn()) {
            return response()->json(['message' => 'Booking belum bisa di-check-in.'], 422);
        }

        $request->validate([
            'pin_code' => 'required_without:qr_token|string|size:6',
            'qr_token' => 'required_without:pin_code|string',
        ]);

        // Validasi PIN atau QR
        if ($request->filled('pin_code') && $booking->pin_code !== $request->pin_code) {
            return response()->json(['message' => 'PIN tidak valid.'], 403);
        }

        if ($request->filled('qr_token') && $booking->qr_token !== $request->qr_token) {
            return response()->json(['message' => 'QR token tidak valid.'], 403);
        }

        $booking->update([
            'status'          => 'checked_in',
            'actual_check_in' => now(),
        ]);

        return response()->json([
            'message' => 'Check-in berhasil!',
            'data'    => $booking->fresh(['room']),
        ]);
    }

    /**
     * POST /api/bookings/{booking}/check-out
     */
    public function checkOut(Booking $booking): JsonResponse
    {
        if ($booking->status !== 'checked_in') {
            return response()->json(['message' => 'Booking belum check-in.'], 422);
        }

        $booking->update([
            'status'           => 'checked_out',
            'actual_check_out' => now(),
        ]);

        $booking->room->update(['status' => 'cleaning']);

        return response()->json([
            'message' => 'Check-out berhasil.',
            'data'    => $booking->fresh(['room']),
        ]);
    }

    /**
     * GET /api/rooms/availability
     * Cek kamar tersedia pada rentang waktu tertentu.
     */
    public function availability(Request $request): JsonResponse
    {
        $request->validate([
            'check_in'  => 'required|date',
            'check_out' => 'required|date|after:check_in',
            'type'      => 'nullable|in:standard,vip',
        ]);

        $bookedRoomIds = Booking::whereNotIn('status', ['cancelled', 'no_show', 'checked_out'])
            ->where(fn ($q) => $q
                ->whereBetween('check_in', [$request->check_in, $request->check_out])
                ->orWhereBetween('check_out', [$request->check_in, $request->check_out])
                ->orWhere(fn ($q2) => $q2
                    ->where('check_in', '<=', $request->check_in)
                    ->where('check_out', '>=', $request->check_out)
                )
            )
            ->pluck('room_id');

        $query = Room::where('is_active', true)
            ->where('status', 'available')
            ->whereNotIn('id', $bookedRoomIds);

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        return response()->json([
            'available_rooms' => $query->get(),
            'total'           => $query->count(),
        ]);
    }
}
