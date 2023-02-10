<?php

namespace App\Http\Controllers;

use App\Models\Merchant;
use App\Models\Order;
use App\Services\MerchantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class MerchantController extends Controller
{
    public function __construct(
        MerchantService $merchantService
    ) {}

    /**
     * Useful order statistics for the merchant API.
     *
     * @param Request $request Will include a from and to date
     * @return JsonResponse Should be in the form {count: total number of orders in range, commission_owed: amount of unpaid commissions for orders with an affiliate, revenue: sum order subtotals}
     */
    public function orderStats(Request $request): JsonResponse
    {
        $from = Carbon::parse($request->input('from'));
        $to = Carbon::parse($request->input('to'));
        $user = Auth::user();
        $merchant = Merchant::where('user_id', $user->id)->first();

        $orders = Order::where('merchant_id', $merchant->id)
            ->whereBetween('created_at', [$from, $to])
            ->get();

        $count = $orders->count();
        $revenue = $orders->sum('subtotal');
        $commissions_owed = $orders->sum('commission_owed');

        foreach ($orders as $order) {
            if (!$order->affiliate_id) {
                $commissions_owed -= $order->commission_owed;
            }
        }

        return response()->json([
            'count' => $count,
            'revenue' => $revenue,
            'commissions_owed' => $commissions_owed
        ]);
    }
}
