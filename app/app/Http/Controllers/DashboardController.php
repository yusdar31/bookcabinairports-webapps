<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\Outlet;
use App\Models\Transaction;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Display the main admin dashboard statistics.
     */
    public function index(Request $request)
    {
        $today = now()->startOfDay();

        // 1. Total Pendapatan hari ini
        $todaysRevenue = Transaction::where('status', 'success')
                                    ->whereDate('created_at', '>=', $today)
                                    ->sum('total');

        // 2. Transaksi Hari Ini
        $todaysTransactions = Transaction::whereDate('created_at', '>=', $today)->count();

        // 3. Status Sistem
        $activeMenusCount = Menu::where('is_available', true)->count();
        $outletsCount = Outlet::where('is_active', true)->count();

        // 4. Transaksi Terbaru (Limit 6)
        $recentTransactions = Transaction::with(['outlet', 'user'])
                                        ->orderBy('created_at', 'desc')
                                        ->take(6)
                                        ->get();

        return view('dashboard.index', compact(
            'todaysRevenue',
            'todaysTransactions',
            'activeMenusCount',
            'outletsCount',
            'recentTransactions'
        ));
    }
}
