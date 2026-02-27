<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Admin\OrderService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrderController extends Controller
{
    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

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
                'search' => $request->search
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

        try {
            $this->orderService->updateOrderStatus($order, $request->status);
            return back()->with('success', 'Cập nhật trạng thái thành công');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function getOrdersByStatus($status)
    {
        if ($status === 'pending') {
            $statuses = ['pending', 'paid'];
        } else {
            $statuses = [$status];
        }

        $orders = Order::with(['user', 'items.product.primaryImage'])
            ->whereIn('status', $statuses)
            ->orderBy('id', 'desc')
            ->get();

        return response()->json($orders);
    }
}
