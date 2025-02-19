<?php

namespace App\Http\Controllers;

use App\Http\Requests\Stats\GetTotalUserAnnualRevenueRateRequest;
use App\Http\Requests\Stats\OrderPriceEvolutionRequest;
use App\Models\Order;
use App\Models\Shop;
use Auth;
use Carbon\Carbon;
use DB;
use App\Services\ShopOwnershipService;
use Illuminate\Http\JsonResponse;
use function PHPUnit\Framework\isEmpty;

class StatsController extends Controller
{
    public function getOrderPriceEvolution(OrderPriceEvolutionRequest $request, ShopOwnershipService $shopOwnershipService): JsonResponse
    {
        $period = $request->filled('period') ? $request->input('period') : "Monthly";

        if ($shopOwnershipService->isShopOwner($request->input('shop_id'))) {
            $now = Carbon::now();
            $dateRange = $this->getDateRange($period, $now);
            $query = Order::where('shop_id', $request->input('shop_id'))
                ->whereBetween('orders.created_at', [$dateRange['start'], $dateRange['end']])
                ->select(
                    DB::raw('SUM(order_product.price_promotion_applied * order_product.product_quantity) as total_price'),
                    DB::raw($this->getDateFormat($period) . ' as period')
                )
                ->join('order_product', 'orders.id', '=', 'order_product.order_id')
                ->groupBy('period')
                ->orderBy('period');

            $orders = $query->get()->keyBy('period');

            $result = [];

            $currentDate = $dateRange['start'];
            while ($currentDate <= $dateRange['end']) {
                $formattedDate = $this->formatDate($currentDate, $period);
                $totalPrice = $orders->has($formattedDate) ? $orders->get($formattedDate)->total_price : 0;

                $result[] = [
                    'x' => $formattedDate,
                    'y' => $totalPrice
                ];

                $currentDate = $this->incrementDate($currentDate, $period);
            }

            return response()->json([
                'message' => 'Stats retrieved successfully',
                'result' => $result,
            ]);
        }
        return response()->json([], 403);
    }

    public function getOrderEvolution(OrderPriceEvolutionRequest $request, ShopOwnershipService $shopOwnershipService): JsonResponse
    {
        $period = $request->filled('period') ? $request->input('period') : "Monthly";

        if ($shopOwnershipService->isShopOwner($request->input('shop_id'))) {
            $now = Carbon::now();
            $dateRange = $this->getDateRange($period, $now);

            $result = [];

            $query = Order::where('shop_id', $request->input('shop_id'))
                ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
                ->select(
                    DB::raw('COUNT(*) as total_orders'),
                    DB::raw($this->getDateFormat($period) . ' as period')
                )
                ->groupBy('period')
                ->orderBy('period');

            // Use cursor to process large datasets efficiently
            foreach ($query->cursor() as $order) {
                $result[$order->period] = $order->total_orders;
            }

            // Fill missing periods
            $currentDate = $dateRange['start'];
            $filledResult = [];

            while ($currentDate <= $dateRange['end']) {
                $formattedDate = $this->formatDate($currentDate, $period);
                $totalOrders = $result[$formattedDate] ?? 0; // Use null coalescing operator to handle empty arrays

                $filledResult[] = [
                    'x' => $formattedDate,
                    'y' => $totalOrders
                ];

                $currentDate = $this->incrementDate($currentDate, $period);
            }

            return response()->json([
                'message' => 'Stats retrieved successfully',
                'result' => $filledResult,
            ]);
        }
        return response()->json([], 403);
    }

        public function getTotalOrderPrices(GetTotalUserAnnualRevenueRateRequest $request,ShopOwnershipService $shopOwnershipService): JsonResponse
    {
        $shopId = $request->input('shop_id');
        $currentYear = Carbon::now()->year;

        if ($shopId) {
            // Specific shop
            if ($shopOwnershipService->isShopOwner($shopId)){
                $shopIds = [$shopId];
            }else{
                return response()->json([], 403);
            }
        } else {
            // All shops of the user
            $shops = Shop::where('owner_id', Auth::id())->pluck('id')->toArray();
            $shopIds = $shops;
        }

        $totalPrice = Order::whereIn('shop_id', $shopIds)
            ->whereYear('orders.created_at', $currentYear)
            ->join('order_product', 'orders.id', '=', 'order_product.order_id')
            ->sum(DB::raw('order_product.price_promotion_applied * order_product.product_quantity'));

        return response()->json([
            'message' => 'Total order prices retrieved successfully',
            'total_price' => $totalPrice,
        ]);
    }

    public function getTotalOrders(GetTotalUserAnnualRevenueRateRequest $request, ShopOwnershipService $shopOwnershipService): JsonResponse
    {
        $shopId = $request->input('shop_id');

        if ($shopId) {
            // Specific shop
            if ($shopOwnershipService->isShopOwner($shopId)){
                $shopIds = [$shopId];
            }else{
                return response()->json([], 403);
            }
        } else {
            // All shops of the user
            $shops = Shop::where('owner_id', Auth::id())->pluck('id')->toArray();
            $shopIds = $shops;
        }

        $totalOrders = Order::whereIn('shop_id', $shopIds)->count();

        return response()->json([
            'message' => 'Total orders retrieved successfully',
            'total_orders' => $totalOrders,
        ]);
    }

    private function getDateFormat($period): string
    {
        return match ($period) {
            'Daily' => 'TO_CHAR(orders.created_at, \'YYYY-MM-DD\')',
            'Weekly' => 'TO_CHAR(orders.created_at, \'IYYY-IW\')',
            'Monthly' => 'TO_CHAR(orders.created_at, \'YYYY-MM\')',
            'Yearly' => 'TO_CHAR(orders.created_at, \'YYYY\')',
            default => throw new \InvalidArgumentException('Invalid period specified.'),
        };
    }

    private function formatDate($date, $period)
    {
        return match ($period) {
            'Daily' => $date->toDateString(),
            'Weekly' => $date->year . '-' . str_pad($date->weekOfYear, 2, '0', STR_PAD_LEFT),
            'Monthly' => $date->format('Y-m'),
            'Yearly' => $date->year,
            default => throw new \InvalidArgumentException('Invalid period specified.'),
        };
    }

    private function incrementDate($date, $period)
    {
        return match ($period) {
            'Daily' => $date->addDay(),
            'Weekly' => $date->addWeek(),
            'Monthly' => $date->addMonth(),
            'Yearly' => $date->addYear(),
            default => throw new \InvalidArgumentException('Invalid period specified.'),
        };
    }

    private function getDateRange($period, $now): array
    {
        return match ($period) {
            'Daily' => [
                'start' => $now->copy()->startOfWeek(),
                'end' => $now->copy()->endOfWeek()
            ],
            'Weekly' => [
                'start' => $now->copy()->startOfMonth(),
                'end' => $now->copy()->endOfMonth()
            ],
            'Monthly' => [
                'start' => $now->copy()->startOfYear(),
                'end' => $now->copy()->endOfYear()
            ],
            'Yearly' => [
                'start' => $now->copy()->subYears(3)->startOfYear(),
                'end' => $now->copy()->endOfYear()
            ],
            default => throw new \InvalidArgumentException('Invalid period specified.'),
        };
    }
}

