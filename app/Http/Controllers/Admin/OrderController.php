<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderDeliveringMail;
use App\Mail\OrderCompletedMail;
use App\Mail\OrderCancelledMail;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['user', 'items.product.primaryImage']);

        if ($request->filled('search')) {
            $query->where('receiver_name', 'like', '%' . $request->search . '%')
                ->orWhere('id', 'like', '%' . $request->search . '%');
        }

        $orders = $query->orderBy('id', 'desc')->paginate(10)->withQueryString();

        return Inertia::render('Admin/Orders/Index', [
            'orders' => $orders,
            'filters' => [
                'search' => $request->search // Quan trọng: fix lỗi trắng trang
            ],
            'stats' => [
                'pending' => Order::whereIn('status', ['pending', 'paid'])->count(),
                'delivering' => Order::where('status', 'delivering')->count(),
                'completed' => Order::where('status', 'completed')->count(),
            ]
        ]);
    }

    public function updateStatus(Request $request, Order $order)
    {
        $request->validate([
            'status' => 'required|in:pending,paid,delivering,completed,cancelled'
        ]);

        $oldStatus = $order->status;
        $newStatus = $request->status;

        // Logic kiểm tra thứ tự chuyển trạng thái nghiêm ngặt
        if (in_array($oldStatus, ['completed', 'cancelled'])) {
            return back()->with('error', 'Đơn hàng đã kết thúc, không thể đổi trạng thái.');
        }

        if ($newStatus === 'delivering' && !in_array($oldStatus, ['pending', 'paid'])) {
            return back()->with('error', 'Chỉ có thể giao hàng khi đơn ở trạng thái Chờ duyệt hoặc Đã thanh toán.');
        }

        if (in_array($newStatus, ['completed', 'cancelled']) && $oldStatus !== 'delivering') {
            return back()->with('error', 'Cần chuyển trạng thái sang "Đang giao" trước khi hoàn thành hoặc hủy.');
        }

        $order->update(['status' => $newStatus]);

        // Gửi Mail thông báo
        $order->load(['user', 'items.product']);
        if ($oldStatus !== $newStatus) {
            switch ($newStatus) {
                case 'delivering':
                    Mail::to($order->user->email)->send(new OrderDeliveringMail($order));
                    break;
                case 'completed':
                    Mail::to($order->user->email)->send(new OrderCompletedMail($order));
                    break;
                case 'cancelled':
                    Mail::to($order->user->email)->send(new OrderCancelledMail($order));
                    break;
            }
        }

        return back()->with('success', 'Cập nhật trạng thái thành công');
    }
}