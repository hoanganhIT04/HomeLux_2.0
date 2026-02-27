<?php

namespace App\Services\Admin;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;

class DashboardService
{
    /**
     * Get aggregate statistics for the dashboard
     */
    public function getStats(): array
    {
        return [
            'totalRevenue' => $this->getTotalRevenue(),
            'newOrdersCount' => $this->getNewOrdersCount(),
            'lowStockCount' => $this->getLowStockCount(),
            'totalUsersCount' => $this->getTotalUsersCount()
        ];
    }

    /**
     * Get data for the 30-day revenue chart
     */
    public function getChartData(): array
    {
        $chartLabels = [];
        $chartData = [];

        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $chartLabels[] = now()->subDays($i)->format('d/m');

            $codDaily = Order::where('payment_method', 'cod')
                ->where('status', 'completed')
                ->whereDate('updated_at', $date)
                ->sum('total_price');

            $momoDaily = Order::where('payment_method', 'momo')
                ->where('status', '!=', 'cancelled')
                ->whereDate('created_at', $date)
                ->sum('total_price');

            $chartData[] = $codDaily + $momoDaily;
        }

        return [
            'labels' => $chartLabels,
            'data' => $chartData
        ];
    }

    private function getTotalRevenue(): float
    {
        $totalCod = Order::where('payment_method', 'cod')
            ->where('status', 'completed')
            ->sum('total_price');

        $totalMomo = Order::where('payment_method', 'momo')
            ->where('status', '!=', 'cancelled')
            ->sum('total_price');

        return $totalCod + $totalMomo;
    }

    private function getNewOrdersCount(): int
    {
        return Order::whereIn('status', ['pending', 'paid'])->count();
    }

    private function getLowStockCount(): int
    {
        return Product::where('quantity', '<', 10)->count();
    }

    private function getTotalUsersCount(): int
    {
        return User::where('role', 'user')->count();
    }
}
