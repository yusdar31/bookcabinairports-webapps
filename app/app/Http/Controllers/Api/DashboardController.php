<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Room;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * GET /api/admin/dashboard
     * Data operasional untuk manajer.
     */
    public function index(): JsonResponse
    {
        $today = now()->toDateString();

        // Statistik kamar
        $totalRooms     = Room::where('is_active', true)->count();
        $occupiedRooms  = Room::where('status', 'occupied')->count();
        $occupancyRate  = $totalRooms > 0 ? round(($occupiedRooms / $totalRooms) * 100, 1) : 0;

        // Booking hari ini
        $todayBookings = Booking::whereDate('check_in', $today)->count();
        $activeGuests  = Booking::where('status', 'checked_in')->count();

        // Revenue hari ini (POS)
        $todayRevenue = Transaction::where('status', 'completed')
            ->whereDate('created_at', $today)
            ->sum('total');

        // Revenue bulan ini
        $monthlyRevenue = Transaction::where('status', 'completed')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total');

        // Booking revenue bulan ini
        $monthlyBookingRevenue = Booking::whereIn('status', ['confirmed', 'checked_in', 'checked_out'])
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total_price');

        return response()->json([
            'rooms' => [
                'total'          => $totalRooms,
                'occupied'       => $occupiedRooms,
                'available'      => $totalRooms - $occupiedRooms,
                'occupancy_rate' => $occupancyRate . '%',
            ],
            'bookings' => [
                'today'         => $todayBookings,
                'active_guests' => $activeGuests,
            ],
            'revenue' => [
                'pos_today'      => $todayRevenue,
                'pos_monthly'    => $monthlyRevenue,
                'booking_monthly' => $monthlyBookingRevenue,
                'total_monthly'  => $monthlyRevenue + $monthlyBookingRevenue,
            ],
        ]);
    }
}
