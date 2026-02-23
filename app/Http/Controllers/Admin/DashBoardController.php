<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Inertia\Inertia;

class DashBoardController extends Controller
{
    public function index()
    {
        // 1. TỔNG DOANH THU (COD đã giao + MoMo chưa hủy)
        $totalCod = Order::where('payment_method', 'cod')
            ->where('status', 'completed')
            ->sum('total_price');

        $totalMomo = Order::where('payment_method', 'momo')
            ->where('status', '!=', 'cancelled')
            ->sum('total_price');

        $totalRevenue = $totalCod + $totalMomo;

        // 2. SỐ ĐƠN HÀNG MỚI (Trạng thái pending hoặc paid)
        $newOrdersCount = Order::whereIn('status', ['pending', 'paid'])->count();

        // 3. SẢN PHẨM SẮP HẾT HÀNG (Số lượng < 10)
        $lowStockCount = Product::where('quantity', '<', 10)->count();

        // 4. TỔNG SỐ NGƯỜI DÙNG (Chỉ đếm role 'user')
        $totalUsersCount = User::where('role', 'user')->count();

        // 5. BIỂU ĐỒ DOANH THU 30 NGÀY GẦN NHẤT
        $chartLabels = [];
        $chartData = [];

        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $chartLabels[] = now()->subDays($i)->format('d/m'); // Hiển thị ngày/tháng

            // Doanh thu COD hoàn thành trong ngày
            $codDaily = Order::where('payment_method', 'cod')
                ->where('status', 'completed')
                ->whereDate('updated_at', $date)
                ->sum('total_price');

            // Doanh thu MoMo đặt trong ngày (không bị hủy)
            $momoDaily = Order::where('payment_method', 'momo')
                ->where('status', '!=', 'cancelled')
                ->whereDate('created_at', $date)
                ->sum('total_price');

            $chartData[] = $codDaily + $momoDaily;
        }

        return Inertia::render('Admin/Dashboard', [
            'totalRevenue' => $totalRevenue,
            'newOrdersCount' => $newOrdersCount,
            'lowStockCount' => $lowStockCount,
            'totalUsersCount' => $totalUsersCount,
            'chartLabels' => $chartLabels,
            'chartData' => $chartData,
        ]);
    }
}