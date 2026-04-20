<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /**
     * GET /api/admin/reports/revenue
     * Revenue summary (harian/bulanan).
     */
    public function revenue(Request $request): JsonResponse
    {
        $request->validate([
            'period' => 'in:daily,monthly',
            'from'   => 'nullable|date',
            'to'     => 'nullable|date|after_or_equal:from',
        ]);

        $period = $request->get('period', 'daily');
        $from   = $request->get('from', now()->subDays(30)->toDateString());
        $to     = $request->get('to', now()->toDateString());

        $groupFormat = $period === 'monthly' ? '%Y-%m' : '%Y-%m-%d';

        // POS Revenue
        $posRevenue = Transaction::where('status', 'completed')
            ->whereBetween('created_at', [$from, $to . ' 23:59:59'])
            ->selectRaw("DATE_FORMAT(created_at, '{$groupFormat}') as period, SUM(total) as revenue, COUNT(*) as count")
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        // Booking Revenue
        $bookingRevenue = Booking::whereIn('status', ['confirmed', 'checked_in', 'checked_out'])
            ->whereBetween('created_at', [$from, $to . ' 23:59:59'])
            ->selectRaw("DATE_FORMAT(created_at, '{$groupFormat}') as period, SUM(total_price) as revenue, COUNT(*) as count")
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        return response()->json([
            'period' => $period,
            'from'   => $from,
            'to'     => $to,
            'pos'    => $posRevenue,
            'booking' => $bookingRevenue,
            'totals' => [
                'pos_revenue'     => $posRevenue->sum('revenue'),
                'booking_revenue' => $bookingRevenue->sum('revenue'),
                'combined'        => $posRevenue->sum('revenue') + $bookingRevenue->sum('revenue'),
            ],
        ]);
    }

    /**
     * GET /api/admin/reports/occupancy
     * Statistik okupansi kamar.
     */
    public function occupancy(Request $request): JsonResponse
    {
        $request->validate([
            'from' => 'nullable|date',
            'to'   => 'nullable|date|after_or_equal:from',
        ]);

        $from = $request->get('from', now()->subDays(30)->toDateString());
        $to   = $request->get('to', now()->toDateString());

        $occupancyByDay = Booking::whereIn('status', ['confirmed', 'checked_in', 'checked_out'])
            ->whereBetween('check_in', [$from, $to . ' 23:59:59'])
            ->selectRaw("DATE(check_in) as date, COUNT(*) as bookings, COUNT(DISTINCT room_id) as rooms_used")
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'from'      => $from,
            'to'        => $to,
            'daily'     => $occupancyByDay,
            'avg_daily' => $occupancyByDay->avg('rooms_used'),
        ]);
    }

    /**
     * GET /api/admin/reports/export/transactions
     * Export transaksi POS ke CSV.
     */
    public function exportTransactions(Request $request): StreamedResponse
    {
        $request->validate([
            'from' => 'nullable|date',
            'to'   => 'nullable|date',
        ]);

        $from = $request->get('from', now()->subDays(30)->toDateString());
        $to   = $request->get('to', now()->toDateString());

        $transactions = Transaction::with(['outlet', 'user', 'items.menu'])
            ->where('status', 'completed')
            ->whereBetween('created_at', [$from, $to . ' 23:59:59'])
            ->orderBy('created_at')
            ->get();

        $filename = "transaksi-pos-{$from}-{$to}.csv";

        return response()->streamDownload(function () use ($transactions) {
            $handle = fopen('php://output', 'w');

            // Header CSV
            fputcsv($handle, [
                'Kode Transaksi', 'Tanggal', 'Outlet', 'Kasir',
                'Subtotal', 'Diskon', 'PPN', 'Total',
                'Metode Bayar', 'Status', 'Item',
            ]);

            foreach ($transactions as $tx) {
                $itemNames = $tx->items->map(fn ($i) => "{$i->menu_name} x{$i->quantity}")->join(', ');

                fputcsv($handle, [
                    $tx->transaction_code,
                    $tx->created_at->format('Y-m-d H:i'),
                    $tx->outlet->name ?? '-',
                    $tx->user->name ?? '-',
                    $tx->subtotal,
                    $tx->discount,
                    $tx->tax,
                    $tx->total,
                    $tx->payment_method,
                    $tx->status,
                    $itemNames,
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * GET /api/admin/reports/export/bookings
     * Export booking ke CSV.
     */
    public function exportBookings(Request $request): StreamedResponse
    {
        $request->validate([
            'from' => 'nullable|date',
            'to'   => 'nullable|date',
        ]);

        $from = $request->get('from', now()->subDays(30)->toDateString());
        $to   = $request->get('to', now()->toDateString());

        $bookings = Booking::with(['room', 'user'])
            ->whereBetween('created_at', [$from, $to . ' 23:59:59'])
            ->orderBy('created_at')
            ->get();

        $filename = "bookings-{$from}-{$to}.csv";

        return response()->streamDownload(function () use ($bookings) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'Kode Booking', 'Tanggal', 'Kamar', 'Tipe', 'Tamu',
                'Check-in', 'Check-out', 'Total', 'Status',
                'Metode Bayar', 'Sumber',
            ]);

            foreach ($bookings as $b) {
                fputcsv($handle, [
                    $b->booking_code,
                    $b->created_at->format('Y-m-d H:i'),
                    $b->room->room_number ?? '-',
                    $b->room->type ?? '-',
                    $b->guest_name,
                    $b->check_in->format('Y-m-d H:i'),
                    $b->check_out->format('Y-m-d H:i'),
                    $b->total_price,
                    $b->status,
                    $b->payment_method,
                    $b->source,
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
