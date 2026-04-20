<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use App\Models\Transaction;
use App\Models\TransactionItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransactionController extends Controller
{
    /**
     * GET /api/transactions
     * Daftar transaksi POS (kasir/manajer).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Transaction::with(['outlet', 'user', 'items.menu']);

        if ($request->filled('outlet_id')) {
            $query->where('outlet_id', $request->outlet_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date')) {
            $query->whereDate('created_at', $request->date);
        }

        $transactions = $query->orderByDesc('created_at')->paginate(20);

        return response()->json($transactions);
    }

    /**
     * POST /api/transactions
     * Buat transaksi POS baru (dari kasir).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'outlet_id'              => 'required|exists:outlets,id',
            'items'                  => 'required|array|min:1',
            'items.*.menu_id'        => 'required|exists:menus,id',
            'items.*.quantity'       => 'required|integer|min:1',
            'items.*.notes'          => 'nullable|string|max:255',
            'payment_method'         => 'required|in:cash,qris,debit,credit,transfer',
            'payment_reference'      => 'nullable|string|max:255',
            'discount'               => 'nullable|numeric|min:0',
            'notes'                  => 'nullable|string|max:500',
            'offline_id'             => 'nullable|string|max:255',
        ]);

        // Deduplikasi offline sync
        if (!empty($validated['offline_id'])) {
            $existing = Transaction::where('offline_id', $validated['offline_id'])->first();
            if ($existing) {
                return response()->json([
                    'message' => 'Transaksi sudah pernah disinkronkan.',
                    'data'    => $existing->load('items'),
                ], 200);
            }
        }

        return DB::transaction(function () use ($validated, $request) {
            $subtotal = 0;
            $itemsData = [];

            foreach ($validated['items'] as $item) {
                $menu = Menu::findOrFail($item['menu_id']);
                $lineSubtotal = $menu->price * $item['quantity'];
                $subtotal += $lineSubtotal;

                $itemsData[] = [
                    'menu_id'    => $menu->id,
                    'menu_name'  => $menu->name,
                    'unit_price' => $menu->price,
                    'quantity'   => $item['quantity'],
                    'subtotal'   => $lineSubtotal,
                    'notes'      => $item['notes'] ?? null,
                ];
            }

            $discount = $validated['discount'] ?? 0;
            $tax      = round(($subtotal - $discount) * 0.11, 2); // PPN 11%
            $total    = $subtotal - $discount + $tax;

            $transaction = Transaction::create([
                'transaction_code'    => 'TX-' . now()->format('Ymd') . '-' . strtoupper(Str::random(4)),
                'outlet_id'           => $validated['outlet_id'],
                'user_id'             => $request->user()->id,
                'subtotal'            => $subtotal,
                'tax'                 => $tax,
                'discount'            => $discount,
                'total'               => $total,
                'payment_method'      => $validated['payment_method'],
                'payment_reference'   => $validated['payment_reference'] ?? null,
                'status'              => 'completed',
                'offline_id'          => $validated['offline_id'] ?? null,
                'synced_from_offline' => !empty($validated['offline_id']),
                'notes'               => $validated['notes'] ?? null,
            ]);

            foreach ($itemsData as $itemData) {
                $transaction->items()->create($itemData);
            }

            return response()->json([
                'message' => 'Transaksi berhasil dicatat.',
                'data'    => $transaction->load('items'),
            ], 201);
        });
    }

    /**
     * POST /api/transactions/sync
     * Batch sync transaksi offline (dari IndexedDB kasir).
     */
    public function syncOffline(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'transactions'                          => 'required|array|min:1|max:50',
            'transactions.*.outlet_id'              => 'required|exists:outlets,id',
            'transactions.*.items'                  => 'required|array|min:1',
            'transactions.*.items.*.menu_id'        => 'required|exists:menus,id',
            'transactions.*.items.*.quantity'        => 'required|integer|min:1',
            'transactions.*.items.*.notes'          => 'nullable|string|max:255',
            'transactions.*.payment_method'         => 'required|in:cash,qris,debit,credit,transfer',
            'transactions.*.offline_id'             => 'required|string|max:255',
            'transactions.*.discount'               => 'nullable|numeric|min:0',
            'transactions.*.notes'                  => 'nullable|string|max:500',
        ]);

        $results = ['synced' => 0, 'skipped' => 0, 'errors' => []];

        foreach ($validated['transactions'] as $idx => $txData) {
            // Deduplikasi
            if (Transaction::where('offline_id', $txData['offline_id'])->exists()) {
                $results['skipped']++;
                continue;
            }

            try {
                // Reuse store logic secara internal
                $fakeRequest = new Request($txData);
                $fakeRequest->setUserResolver(fn () => $request->user());
                $this->store($fakeRequest);
                $results['synced']++;
            } catch (\Throwable $e) {
                $results['errors'][] = [
                    'index'      => $idx,
                    'offline_id' => $txData['offline_id'],
                    'error'      => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'message' => "Sync selesai: {$results['synced']} berhasil, {$results['skipped']} duplikat.",
            'results' => $results,
        ]);
    }
}
